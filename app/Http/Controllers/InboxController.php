<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InboxController extends Controller
{
    public function index()
    {
        $idtap = session('idtap');

        $denomLookup = DB::table('denom')
            ->select('iddenom', DB::raw('MAX(denom) as denom'))
            ->groupBy('iddenom');

        // Inbox previously read raw keluar rows, so a duplicated transfer
        // produced duplicated inbox entries. Keep the oldest ID for approval.
        $transferLookup = DB::table('keluar')
            ->selectRaw('MIN(idkeluar) as idkeluar, tgl, idtap, penerima, pengirim, iddenom, qty, sn, status')
            ->where('status', 1)
            ->when($idtap !== 'SBP_DUMAI', fn ($query) => $query->where('penerima', $idtap))
            ->groupBy('tgl', 'idtap', 'penerima', 'pengirim', 'iddenom', 'qty', 'sn', 'status');

        $data = DB::query()
            ->fromSub($transferLookup, 'k')
            ->joinSub($denomLookup, 'd', 'k.iddenom', '=', 'd.iddenom')
            ->select('k.*', 'd.denom')
            ->get();

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
