<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Illuminate\Http\Request;

class FormInjectsegelController extends Controller
{
    public function index(Request $request)
    {
        $idtap = session('idtap');

        $denom = DB::table('denom')
                    ->select('iddenom', 'denom')
                    ->where('kategori_inject','SEGEL')
                    ->where('iddenom', '!=', 'SEGEL')
                    ->get();

        if($idtap == 'SBP_DUMAI'){

            $data = DB::table('kodetap')
                    ->select('*')
                    ->get();
        }else{
            $data = DB::table('kodetap')
                    ->select('*')
                    ->where('idtap',$idtap)
                    ->get();

        }

        return view('form/forminject',compact('data','idtap','denom'));

    }

    public function injectProses(Request $request)
{
    DB::transaction(function () use ($request) {

        $idtap   = $request->idtap;
        $iddenom = $request->iddenom;
        $qty     = $request->qty;
        $sn      = $request->sn;
        $tgl     = $request->tgl;

        // 🔒 LOCK STOK SEGEL TAP
        $stokSegel = DB::table('stockawaltap')
            ->where('idtap', $idtap)
            ->where('iddenom', 'SEGEL')
            ->lockForUpdate()
            ->value('stock');

        if ($stokSegel < $qty) {
            throw new \Exception('Stok Segel TAP tidak mencukupi');
        }

        // INSERT INJECT
        DB::table('injectvf')->insert([
            'idtap'     => $idtap,
            'iddenom'   => $iddenom,
            'qty'       => $qty,
            'sn'        => $sn,
            'tgl'       => $tgl,
            'kategori'  => 'SEGEL',
        ]);

        // UPDATE STOK
        DB::table('stockawaltap')
            ->where('idtap', $idtap)
            ->where('iddenom', 'SEGEL')
            ->decrement('stock', $qty);

        // DB::table('stockawalall')
        //     ->where('idtap', $idtap)
        //     ->where('iddenom', 'SEGEL')
        //     ->decrement('stock', $qty);

        DB::table('stockawaltap')
            ->where('idtap', $idtap)
            ->where('iddenom', $iddenom)
            ->increment('stock', $qty);

        // DB::table('stockawalall')
        //     ->where('idtap', $idtap)
        //     ->where('iddenom', $iddenom)
        //     ->increment('stock', $qty);
    });

    return redirect('injectvf')->with('status', 'Inject berhasil, stok diperbarui');
}


public function getStockSegelTap(Request $request)
{
    $request->validate([
        'idtap' => 'required'
    ]);

    $stock = DB::table('stockawaltap')
        ->where('idtap', $request->idtap)
        ->where('iddenom', 'SEGEL')
        ->value('stock');

    return response()->json([
        'stock' => (int) ($stock ?? 0)
    ]);
}
}