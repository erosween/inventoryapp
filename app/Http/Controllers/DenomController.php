<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\DenomGrouping;

class DenomController extends Controller
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
        $denoms = DB::table('denom')->orderBy('iddenom')->get();
        // Sugest ID Denom berikutnya (Mencari numerik tertinggi jika polanya DXX)
        $lastId = DB::table('denom')->where('iddenom', 'LIKE', 'V%')->orderByRaw('CAST(SUBSTRING(iddenom, 2) AS UNSIGNED) DESC')->first();
        $suggestedId = 'V1';
        if ($lastId) {
            $num = (int) substr($lastId->iddenom, 1);
            $suggestedId = 'V' . ($num + 1);
        }

        $groups = [
            'SEGEL',
            'SA',
            'UNLIMITED 6 JAM',
            'UNLIMITED 12 JAM',
            'UNLIMITED 24 JAM',
            'UNLIMITED 48 JAM',
            '1 HARI',
            '2 HARI',
            '3 HARI',
            '5 HARI',
            '7 HARI',
            '14 HARI',
            '28 HARI',
            '30 HARI',
            'VOICE',
            'LAINNYA',
        ];
        $kategoriInjects = [
            'VOUCHER FISIK' => [
                'SEGEL' => 'PV Reguler (SEGEL)',
            ],
            'VOUCHER BY.U' => [
                'BYU' => 'BYU (termasuk Unlimited)',
            ],
            'STARTER PACK' => [
                'SA' => 'Starter Pack / SA',
            ],
            'PRODUK LAIN' => [
                'ROAMAX' => 'ROAMAX',
            ],
        ];

        return view('denom.index', compact('denoms', 'suggestedId', 'groups', 'kategoriInjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'iddenom' => 'required|unique:denom,iddenom',
            'denom' => 'required',
            'group_name' => 'required'
        ]);

        $metadata = $this->groupingMetadata($request, (string) $request->iddenom);

        DB::beginTransaction();
        try {

            // 1. Simpan ke tabel master denom
            DB::table('denom')->insert([
                'iddenom' => $request->iddenom,
                'denom' => $request->denom,
                'group_name' => $metadata['stock_form'] === 'SEGEL' ? 'SEGEL' : $request->group_name,
                'kategori_inject' => $this->legacyCategory($metadata),
                'product_group' => $this->legacyProductGroup($metadata, (string) $request->iddenom),
                'harga_jual' => $request->harga_jual ?? 0
            ] + $metadata);

            // 2. Automasi Inisialisasi Stok Awal (Saldo Awal) ke 0 untuk semua TAP
            $taps = DB::table('kodetap')->get();
            foreach ($taps as $tap) {
                // Ke stockawaltap
                DB::table('stockawaltap')->insert([
                    'idtap' => $tap->idtap,
                    'iddenom' => $request->iddenom,
                    'stock' => 0
                ]);
                // Ke stockawalfeb (Saldo Awal Utama)
                DB::table('stockawalfeb')->insert([
                    'idtap' => $tap->idtap,
                    'iddenom' => $request->iddenom,
                    'stock' => 0
                ]);
            }

            // 3. Automasi Inisialisasi Stok Awal ke 0 untuk semua SF
            $sfs = DB::table('idsf')->get();
            foreach ($sfs as $sf) {
                DB::table('stockawalsf')->insert([
                    'idsf' => $sf->idsf,
                    'iddenom' => $request->iddenom,
                    'stock' => 0
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Denom berhasil ditambahkan dan stok awal diinisialisasi untuk semua TAP & SF.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding denom: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menambah denom: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $iddenom)
    {
        $request->validate([
            'denom' => 'required',
            'group_name' => 'required'
        ]);

        $metadata = $this->groupingMetadata($request, (string) $iddenom);

        DB::beginTransaction();
        try {

            DB::table('denom')->where('iddenom', $iddenom)->update([
                'denom' => $request->denom,
                'group_name' => $metadata['stock_form'] === 'SEGEL' ? 'SEGEL' : $request->group_name,
                'kategori_inject' => $this->legacyCategory($metadata),
                'product_group' => $this->legacyProductGroup($metadata, (string) $iddenom),
                'harga_jual' => $request->harga_jual ?? 0
            ] + $metadata);

            // Update master denom saja karena relasi stock awal akan membaca dari sini.
            DB::commit();
            return redirect()->back()->with('success', 'Denom berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui denom.');
        }
    }

    public function destroy($iddenom)
    {
        return redirect()->back()->with('error', 'Fitur penghapusan dinonaktifkan untuk menjaga integritas history data.');
    }

    public function checkId($id)
    {
        $exists = DB::table('denom')->where('iddenom', $id)->exists();
        return response()->json(['exists' => $exists]);
    }

    private function productGroupForCategory(?string $category): string
    {
        return match (strtoupper(trim((string) $category))) {
            'SA' => 'SA',
            'BYU' => 'PV',
            'SEGEL' => 'PV',
            default => 'PV',
        };
    }

    private function groupingMetadata(Request $request, string $id): array
    {
        $request->validate([
            'product'=>'required|in:PV,SA',
            'product_variant'=>'required|in:REGULER,SIMPATI,BYU,ROAMAX',
            'stock_form'=>'required|in:SEGEL,INJECT',
        ]);
        return DenomGrouping::forMaster($id, $request->all());
    }

    private function legacyProductGroup(array $metadata, string $id): string
    {
        $existing = DB::table('denom')->where('iddenom', $id)->value('product_group');
        return $metadata['product'] === 'SA' && ($existing === 'SP' || in_array($id, ['SP6','SP7'], true))
            ? 'SP' : $metadata['product'];
    }

    private function legacyCategory(array $metadata): string
    {
        if ($metadata['product'] === 'SA') return 'SA';
        return match ($metadata['product_variant']) { 'BYU'=>'BYU', 'ROAMAX'=>'ROAMAX', default=>'SEGEL' };
    }
}
