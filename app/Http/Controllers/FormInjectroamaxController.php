<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FormInjectroamaxController extends Controller
{   
    public function index(Request $request)
    {
        $idtap = session('idtap');

        $denom = DB::table('kategori_inject')
                    ->select('*')
                    ->where('kategori','ROAMAX')
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

        return view('form/forminjectroamax',compact('data','idtap','denom'));

    }

    public function injectProses(Request $request)
    {
        $idtap = session('idtap');

        $qty = $request->input('qty');
        $idtap = $request->input('idtap');
        $tgl = $request->input('tgl');
        $iddenom = $request->input('iddenom');
        $sn = $request->input('sn');
        
        // cek stok sesuai kategori segel

        $eksstoksegeltap = DB::table('stockawaltap')
                            ->select('stock')
                            ->where('idtap', $request->input('idtap'))
                            ->where('iddenom','V16')
                            ->first();
        
        if($request->input('qty') > $eksstoksegeltap->stock ){

            return redirect('form/forminjectroamax')->withErrors(['error' => 'Stock Roamax TAP tidak mencukupi!']);
            
        }else{                
            //insert to table inject vf
            $insertinjectvf = DB::table('injectvf')
            ->insert(
                [
                    'idtap' => $request -> idtap,
                    'iddenom' => $request -> iddenom,
                    'qty' => $request -> qty,
                    'sn' => $request -> sn,
                    'tgl' => $request -> tgl,
                    'kategori' => 'V16',
                ]
            );

            //cek stok segel
            $eksstoksegelall = DB::table('stockawalall')
                                ->select('stock')
                                ->where('idtap', $idtap)
                                ->where('iddenom','V16')
                                ->first();
            
            
            $eksstoksegeltap = DB::table('stockawaltap')
                                ->select('stock')
                                ->where('idtap', $idtap)
                                ->where('iddenom','V16')
                                ->first();
            
            //stok eksisting
            $existingStockAll = DB::table('stockawalall')
                                ->select('stock')
                                ->where('idtap',$idtap)
                                ->where('iddenom',$iddenom)
                                ->first();

            $existingStockTap  = DB::table('stockawaltap')
                                ->select('stock')
                                ->where('idtap',$idtap)
                                ->where('iddenom',$iddenom)
                                ->first();
            
            $newStocksegelAll = $eksstoksegelall->stock - $qty;
            $newStocksegelTAP = $eksstoksegeltap->stock - $qty;
            $newStockAll = $existingStockAll->stock + $qty;
            $newStockTAP = $existingStockTap->stock + $qty;


            // update stok segel
            DB::table('stockawalall')
                ->where('idtap', $idtap)
                ->where('iddenom', 'V16')
                ->update([
                    'stock' => $newStocksegelAll
                    ]);

                DB::table('stockawaltap')
                    ->where('idtap', $idtap)
                    ->where('iddenom', 'V16')
                    ->update([
                        'stock' => $newStocksegelTAP
                        ]);

            // update stok denom
            DB::table('stockawalall')
                ->where('idtap', $idtap)
                ->where('iddenom', $iddenom)
                    ->update([
                    'stock' => $newStockAll
                    ]);

            DB::table('stockawaltap')
                ->where('idtap', $idtap)
                ->where('iddenom', $iddenom)
                    ->update([
                    'stock' => $newStockTAP
                    ]);

            return redirect('injectvf')->with('status','Data Berhasil Ditambahkan!');
 

         }
        }

    
}
