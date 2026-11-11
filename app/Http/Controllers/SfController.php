<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SfMasterExport;

// Master sales force. Perubahan profil SF, login mobile, dan ekspor ditangani di sini.
class SfController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->check() && !auth()->user()->isSuperAdmin()) {
                abort(403, 'Akses Ditolak! Master Data hanya dapat diakses admin super.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        // Get all SF with their TAP names
        $sfs = DB::table('idsf')->orderBy('idtap')->orderBy('idsf')->get();
        // Get all valid TAPs for the dropdown
        $taps = DB::table('kodetap')->orderBy('idtap')->get();

        return view('sf.index', compact('sfs', 'taps'));
    }

    public function store(Request $request)
    {
        $this->normalizeDigiposId($request);
        $request->validate([
            'idsf' => 'required|unique:idsf,idsf',
            'digipos_id' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/D', 'unique:idsf,digipos_id'],
            'idtap' => 'required',
            'namasf' => 'required|string|max:100'
        ], [
            'idsf.unique' => 'Peringatan: ID SF "' . $request->idsf . '" sudah ada di sistem! Silakan gunakan ID yang berbeda.',
            'idsf.required' => 'ID SF wajib diisi.',
            'idtap.required' => 'Kolom TAP wajib dipilih.',
            'namasf.required' => 'Nama SF wajib diisi.'
        ]);

        DB::beginTransaction();
        try {
            // 1. Simpan ke tabel master idsf
            DB::table('idsf')->insert([
                'idsf' => $request->idsf,
                'idtap' => $request->idtap,
                // Nama SF distandarisasi huruf kapital
                'namasf' => strtoupper($request->namasf),
                'digipos_id' => $request->input('digipos_id'),
                'is_hidden' => false,
                'login_code' => strtoupper(Str::random(6)),
                'password' => Hash::make('123')
            ]);

            // 2. Automasi Inisialisasi Stok Awal ke 0 untuk semua Denom pada tabel stockawalsf
            $denoms = DB::table('denom')->get();
            foreach ($denoms as $denom) {
                DB::table('stockawalsf')->insert([
                    'idsf' => $request->idsf,
                    'iddenom' => $denom->iddenom,
                    'stock' => 0
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'SF berhasil ditambahkan dan stok awal diinisialisasi ke semua denom dengan saldo 0.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding SF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menambah SF: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $idsf)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        abort_unless(DB::table('idsf')->where('idsf', $idsf)->exists(), 404);
        $this->normalizeDigiposId($request);
        $data = $request->validate([
            'idtap' => 'required|string|exists:kodetap,idtap',
            'namasf' => 'required|string|max:100',
            'is_hidden' => ['sometimes', 'boolean'],
            'digipos_id' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/D', Rule::unique('idsf', 'digipos_id')->ignore($idsf, 'idsf')],
        ]);
        DB::table('idsf')->where('idsf', $idsf)->update([
            'idtap' => $data['idtap'],
            'namasf' => mb_strtoupper(trim($data['namasf'])),
            'digipos_id' => $data['digipos_id'] ?? null,
            'is_hidden' => $request->boolean('is_hidden'),
        ]);
        return redirect('sf')->with('success', 'Data SF berhasil diperbarui.');
    }

    public function destroy($idsf)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        return DB::transaction(function () use ($idsf) {
            $sf = DB::table('idsf')->where('idsf', $idsf)->lockForUpdate()->first();
            abort_unless($sf, 404);
            $stock = Schema::hasTable('stockawalsf')
                ? DB::table('stockawalsf')->where('idsf', $idsf)->lockForUpdate()->get()
                : collect();
            if ($stock->contains(fn ($row) => (float) $row->stock != 0)) {
                return redirect('sf')->with('error', 'SF tidak dapat dihapus karena masih memiliki saldo stok.');
            }
            // Preserve every reference to an SF identity, including mobile owner/helper history.
            $references = [
                'masuksf' => ['idsf'], 'keluarsf' => ['idsf'], 'retursf' => ['idsf'],
                'masuk' => ['pengirim', 'penerima'], 'keluar' => ['pengirim', 'penerima'],
                'mobile_penjualan' => ['idsf', 'owner_idsf'],
                'mobile_sales_receipts' => ['idsf'],
                'surya_visits' => ['idsf', 'stock_owner_idsf'],
                'surya_sf_targets' => ['idsf'],
                'surya_sa_consignment_movements' => ['idsf'], 'surya_sa_consignment_numbers' => ['idsf'],
                'surya_competitor_surveys' => ['idsf'], 'surya_network_reports' => ['idsf'],
            ];
            foreach ($references as $table => $columns) {
                if (! Schema::hasTable($table)) continue;
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)
                        && DB::table($table)->where($column, $idsf)->exists()) {
                        return redirect('sf')->with('error', 'SF tidak dapat dihapus karena sudah digunakan dalam transaksi, visit, atau penugasan. Riwayat tetap dipertahankan.');
                    }
                }
            }
            if (Schema::hasTable('stockawalsf')) {
                DB::table('stockawalsf')->where('idsf', $idsf)->delete();
            }
            DB::table('idsf')->where('idsf', $idsf)->delete();
            return redirect('sf')->with('success', 'SF berhasil dihapus.');
        });
    }

    private function normalizeDigiposId(Request $request): void
    {
        if (is_string($request->input('namasf'))) {
            $request->merge(['namasf' => mb_strtoupper(trim($request->input('namasf')))]);
        }
        $value = $request->input('digipos_id');
        if (is_string($value)) {
            $value = strtoupper(trim($value));
            $request->merge(['digipos_id' => $value === '' ? null : $value]);
        }
    }

    public function checkId($id)
    {
        $exists = DB::table('idsf')->where('idsf', $id)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function syncLogin()
    {
        $sfs = DB::table('idsf')->whereNull('login_code')->get();
        $count = 0;

        foreach ($sfs as $sf) {
            DB::table('idsf')->where('idsf', $sf->idsf)->update([
                'login_code' => strtoupper(Str::random(6)),
                'password' => Hash::make('123')
            ]);
            $count++;
        }

        return redirect()->back()->with('success', $count . ' SF berhasil di-sinkronisasi data login-nya (Password Default: 123).');
    }

    public function export()
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $sfs = DB::table('idsf')->orderBy('idtap')->orderBy('idsf')->get();
        return Excel::download(new SfMasterExport($sfs), 'MASTER_SF_' . now()->format('Ymd_His') . '.xlsx');
    }
}
