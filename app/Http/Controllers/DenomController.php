<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DenomController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->check() && auth()->user()->username !== 'admin_cluster') {
                abort(403, 'Akses Ditolak! Hanya admin_cluster yang diizinkan mengakses menu ini.');
            }
            return $next($request);
        });
    }
    public function index()
    {
        $denoms = DB::table('denom')->orderBy('iddenom')->get();
        // Sugest ID Denom berikutnya (Mencari numerik tertinggi jika polanya DXX)
        $lastId = DB::table('denom')->where('iddenom', 'LIKE', 'D%')->orderByRaw('CAST(SUBSTRING(iddenom, 2) AS UNSIGNED) DESC')->first();
        $suggestedId = 'D001';
        if ($lastId) {
            $num = (int) substr($lastId->iddenom, 1);
            $suggestedId = 'D' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
        }

        $groups = ['SEGEL', '1 HARI', '2 HARI', '3 HARI', '5 HARI', '7 HARI', '14 HARI', '28 HARI', '30 HARI', 'VOICE', 'LAINNYA'];
        $kategoriInjects = ['SEGEL', 'BYU', 'ROAMAX'];

        return view('denom.index', compact('denoms', 'suggestedId', 'groups', 'kategoriInjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'iddenom' => 'required|unique:denom,iddenom',
            'denom' => 'required',
            'group_name' => 'required'
        ]);

        DB::beginTransaction();
        try {
            // 1. Simpan ke tabel master denom
            DB::table('denom')->insert([
                'iddenom' => $request->iddenom,
                'denom' => $request->denom,
                'group_name' => $request->group_name,
                'kategori_inject' => $request->kategori_inject
            ]);

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

        DB::beginTransaction();
        try {
            DB::table('denom')->where('iddenom', $iddenom)->update([
                'denom' => $request->denom,
                'group_name' => $request->group_name,
                'kategori_inject' => $request->kategori_inject
            ]);

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
        // Peringatan: Menghapus denom bisa merusak integritas jika sudah ada mutasi.
        // Untuk tahap ini kita izinkan hapus jika stok awal masih 0? 
        // Lebih aman hanya biarkan admin hapus manual di DB jika sudah ada transaksi.
        try {
            DB::table('denom')->where('iddenom', $iddenom)->delete();
            DB::table('stockawaltap')->where('iddenom', $iddenom)->delete();
            DB::table('stockawalfeb')->where('iddenom', $iddenom)->delete();
            DB::table('stockawalsf')->where('iddenom', $iddenom)->delete();
            
            return redirect()->back()->with('success', 'Denom dan data stok awal terkait berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus denom.');
        }
    }
}
