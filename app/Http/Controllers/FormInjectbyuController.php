<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FormInjectbyuController extends Controller
{   
    public function index(Request $request)
    {
        $idtap = session('idtap');

        $denom = DB::table('denom')
                    ->select('iddenom', 'denom')
                    ->where('kategori_inject','BYU')
                    ->where('iddenom', '!=', 'V33')
                    ->get();

        if($idtap == 'SBP_DUMAI'){

            $data = DB::table('kodetap')
                    ->select('*')
                    ->get();

                    $n = DB::table('keluar')
                    ->select(DB::raw('sum(status) as qty'))
                    ->get();
    
                $notif = $n->sum('qty');
    
        }else{
            $data = DB::table('kodetap')
                    ->select('*')
                    ->where('idtap',$idtap)
                    ->get();
        }
        return view('form/forminjectbyu',compact('data','idtap','denom'));

    }

public function injectProses(Request $request)
{
    try {
        DB::transaction(function () use ($request) {

            $idtap   = $request->idtap;
            $iddenom = $request->iddenom; // paket BYU
            $qty     = (int) $request->qty;
            $sn      = $request->sn;
            $tgl     = $request->tgl;

            $kategoriSegel = 'V33'; // BYU

            // 🔒 LOCK STOK SEGEL BYU (TAP)
            $stokSegelTap = DB::table('stockawaltap')
                ->where('idtap', $idtap)
                ->where('iddenom', $kategoriSegel)
                ->lockForUpdate()
                ->value('stock');

            if ($stokSegelTap === null) {
                throw new \Exception('Stok BYU TAP tidak ditemukan');
            }

            if ($stokSegelTap < $qty) {
                throw new \Exception('Stok BYU TAP tidak mencukupi');
            }

            // 📝 INSERT INJECT
            DB::table('injectvf')->insert([
                'idtap'    => $idtap,
                'iddenom'  => $iddenom,
                'qty'      => $qty,
                'sn'       => $sn,
                'tgl'      => $tgl,
                'kategori' => $kategoriSegel,
            ]);

            // 🔻 KURANGI STOK SEGEL BYU
            DB::table('stockawaltap')
                ->where('idtap', $idtap)
                ->where('iddenom', $kategoriSegel)
                ->decrement('stock', $qty);

            // 🔺 TAMBAH STOK PAKET BYU
            DB::table('stockawaltap')
                ->where('idtap', $idtap)
                ->where('iddenom', $iddenom)
                ->increment('stock', $qty);
        });

        return redirect('injectvf')->with('status', 'Inject BYU berhasil');

    } catch (\Exception $e) {
        return redirect('form/forminjectbyu')
            ->withErrors(['error' => $e->getMessage()]);
    }
}


public function getStockSegelTap(Request $request)
{
    $stock = DB::table('stockawaltap')
        ->where('idtap', $request->idtap)
        ->where('iddenom', 'V33') // BYU
        ->value('stock') ?? 0;

    return response()->json(['stock' => $stock]);
}

    
}
