<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormKeluartapController extends Controller
{
    public function index(){
        $idtap = session('idtap');

        if($idtap == 'SBP_DUMAI'){

            $data = DB::table('kodetap')
                    ->select('*')
                    ->get();

        }else{
            $data = DB::table('kodetap')
                    ->select('*')
                    ->where('idtap', $idtap)
                    ->get();

        }

        $tappenerima=DB::table('kodetap')
                    ->select('*')
                    ->where('idtap','<>',$idtap)
                    ->get();

        $denom = DB::table('denom')
                ->select('*')
                ->get();

        return view('form/formkeluartap',compact('data','idtap','denom','tappenerima'));
    }

// get stock
public function getStockTapPengirim(Request $request)
{
    $request->validate([
        'idtap'   => 'required',
        'iddenom' => 'required'
    ]);

    $stock = DB::table('stockawaltap')
        ->where('idtap', $request->idtap)
        ->where('iddenom', $request->iddenom)
        ->value('stock') ?? 0;

    return response()->json([
        'stock' => (int) $stock
    ]);
}



public function proseskeluartapform(Request $request)
{
    DB::transaction(function () use ($request) {

        $tgl        = $request->tgl;
        $pengirim   = $request->pengirim;
        $penerima   = $request->penerima;
        $iddenom    = $request->iddenom;
        $qty        = $request->qty;
        $sn         = $request->sn;
        $tambahket  = $request->tambahket;

        // LOCK stok pengirim
        $stock = DB::table('stockawaltap')
            ->where('idtap', $pengirim)
            ->where('iddenom', $iddenom)
            ->lockForUpdate()
            ->value('stock');

        if ($stock < $qty) {
            throw new \Exception('Stok TAP Tidak Mencukupi');
        }

        // INSERT keluar (stok BELUM pindah)
        DB::table('keluar')->insert([
            'iddenom'     => $iddenom,
            'pengirim'    => $pengirim,
            'penerima'    => $penerima,
            'qty'         => $qty,
            'tgl'         => $tgl,
            'sn'          => $sn,
            'tambahanket' => $tambahket,
            'idtap'       => $pengirim,
            'status'      => 1 // pending
        ]);
    });

    return redirect('keluar')->with('success', 'Menunggu approval TAP penerima');
}

public function getAllStockTap(Request $request)
    {
        $request->validate([
            'idtap' => 'required',
        ]);

        $stocks = DB::table('stockawaltap')
            ->where('idtap', $request->idtap)
            ->pluck('stock', 'iddenom');

        return response()->json($stocks);
    }
}