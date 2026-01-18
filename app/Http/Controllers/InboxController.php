<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InboxController extends Controller
{
    public function index()
    {
        $idtap = session('idtap');
        
        if($idtap == 'SBP_DUMAI'){
            $data = DB::table('keluar as k')
                    ->join('denom as d','k.iddenom', '=','d.iddenom')
                    ->where('k.status',1)
                    ->get();
        }else{
            $data = DB::table('keluar as k')
                    ->join('denom as d','k.iddenom', '=','d.iddenom')
                    ->where('k.status',1)
                    ->where('k.penerima', $idtap)
                    ->get();  

        }
        return view('inbox', compact('data','idtap'));
    }

    public function masuk(Request $request, $idkeluar)
{
    DB::transaction(function () use ($idkeluar) {

        // 🔒 LOCK DATA KELUAR
        $keluar = DB::table('keluar')
            ->where('idkeluar', $idkeluar)
            ->lockForUpdate()
            ->first();

        if (!$keluar) {
            throw new \Exception('Data tidak ditemukan');
        }

        if ($keluar->status != 1) {
            throw new \Exception('Data sudah diproses');
        }

        $pengirim = $keluar->pengirim;
        $penerima = $keluar->penerima;
        $iddenom  = $keluar->iddenom;
        $qty      = $keluar->qty;

        // 🔒 LOCK STOK PENGIRIM
        $stokPengirim = DB::table('stockawaltap')
            ->where('idtap', $pengirim)
            ->where('iddenom', $iddenom)
            ->lockForUpdate()
            ->value('stock');

        if ($stokPengirim < $qty) {
            throw new \Exception('Stok TAP pengirim tidak mencukupi');
        }

        // 🔒 LOCK STOK PENERIMA
        DB::table('stockawaltap')
            ->where('idtap', $penerima)
            ->where('iddenom', $iddenom)
            ->lockForUpdate()
            ->value('stock');

        // =====================
        // UPDATE STOK
        // =====================
        DB::table('stockawaltap')
            ->where('idtap', $pengirim)
            ->where('iddenom', $iddenom)
            ->decrement('stock', $qty);

        DB::table('stockawaltap')
            ->where('idtap', $penerima)
            ->where('iddenom', $iddenom)
            ->increment('stock', $qty);

        // =====================
        // UPDATE STATUS
        // =====================
        DB::table('keluar')
            ->where('idkeluar', $idkeluar)
            ->update([
                'status'      => 0,
                'approved_at' => now()
            ]);
    });

    return redirect('inbox')->with('success', 'Stok berhasil diterima');
}
}