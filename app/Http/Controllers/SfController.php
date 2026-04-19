<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SfController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->check() && in_array(session('idtap'), ['CLUSTER_DUMAI', 'CLUSTER_ROHIL'])) {
                abort(403, 'Akses Ditolak! Hanya superadmin yang diizinkan mengakses menu ini.');
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
        $request->validate([
            'idsf' => 'required|unique:idsf,idsf',
            'idtap' => 'required',
            'namasf' => 'required'
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
                'namasf' => strtoupper($request->namasf) 
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
        $request->validate([
            'idtap' => 'required',
            'namasf' => 'required'
        ], [
            'idtap.required' => 'Kolom TAP wajib dipilih.',
            'namasf.required' => 'Nama SF wajib diisi.'
        ]);

        try {
            DB::table('idsf')->where('idsf', $idsf)->update([
                'idtap' => $request->idtap,
                'namasf' => strtoupper($request->namasf)
            ]);

            return redirect()->back()->with('success', 'Data SF berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui data SF.');
        }
    }

    public function destroy($idsf)
    {
        try {
            DB::table('idsf')->where('idsf', $idsf)->delete();
            DB::table('stockawalsf')->where('idsf', $idsf)->delete();
            
            return redirect()->back()->with('success', 'SF dan data stok terkait berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus SF.');
        }
    }
}
