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

        $denom = DB::table('kategori_inject')
                    ->select('*')
                    ->where('kategori','SEGEL')
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
        $idtaps= session('idtap');

        $qty = $request->input('qty');
        $idtap = $request->input('idtap');
        $tgl = $request->input('tgl');
        $iddenom = $request->input('iddenom');
        $sn = $request->input('sn');

        // cek stok sesuai kategori segel

        $eksstoksegeltap = DB::table('stockawaltap')
                            ->select('stock')
                            ->where('idtap', $request->input('idtap'))
                            ->where('iddenom','SEGEL')
                            ->first();
        
        if($qty > $eksstoksegeltap->stock ){

            return redirect('form/forminject')->withErrors(['error' => 'Stock Segel TAP tidak mencukupi!']);
            
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
                    'kategori' => "SEGEL",
                ]
            );

            //cek stok segel
            $eksstoksegelall = DB::table('stockawalall')
                                ->select('stock')
                                ->where('idtap', $request->input('idtap'))
                                ->where('iddenom','SEGEL')
                                ->first();
            
            
            $eksstoksegeltap = DB::table('stockawaltap')
                                ->select('stock')
                                ->where('idtap', $request->input('idtap'))
                                ->where('iddenom','SEGEL')
                                ->first();
            
            //stok eksisting
            $existingStockAll = DB::table('stockawalall')
                                ->select('stock')
                                ->where('idtap',$request->input('idtap'))
                                ->where('iddenom',$request->input('iddenom'))
                                ->first();

            $existingStockTap  = DB::table('stockawaltap')
                                ->select('stock')
                                ->where('idtap',$request->input('idtap'))
                                ->where('iddenom',$request->input('iddenom'))
                                ->first();
            
            $newStocksegelAll = $eksstoksegelall->stock - $request->input('qty');
            $newStocksegelTAP = $eksstoksegeltap->stock - $request->input('qty');
            $newStockAll = $existingStockAll->stock + $request->input('qty');
            $newStockTAP = $existingStockTap->stock + $request->input('qty');


            // update stok segel
            DB::table('stockawalall')
                ->where('idtap', $request->input('idtap'))
                ->where('iddenom', 'SEGEL')
                ->update([
                    'stock' => $newStocksegelAll
                    ]);

                DB::table('stockawaltap')
                    ->where('idtap', $request->input('idtap'))
                    ->where('iddenom', 'SEGEL')
                    ->update([
                        'stock' => $newStocksegelTAP
                        ]);

            // update stok denom
            DB::table('stockawalall')
                ->where('idtap', $request->input('idtap'))
                ->where('iddenom', $request->input('iddenom'))
                    ->update([
                    'stock' => $newStockAll
                    ]);

            DB::table('stockawaltap')
                ->where('idtap', $request->input('idtap'))
                ->where('iddenom', $request->input('iddenom'))
                    ->update([
                    'stock' => $newStockTAP
                    ]);

            return redirect('injectvf')->with('status','Data Berhasil Ditambahkan!');
 

         }
        }

    
}
