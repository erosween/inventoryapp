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


    public function proseskeluartapform(Request $request){

        
        $tgl = $request->input('tgl');
        $pengirim = $request->input('pengirim');
        $penerima = $request->input('penerima');
        $iddenom = $request->input('iddenom');
        $qty = $request->input('qty');
        $sn = $request->input('sn');
        $tambahket = $request->input('tambahket');

        //update stock awal tap (pengirim)
        $stapengirim = DB::table('stockawaltap')
                    ->select('stock')
                    ->where('idtap', $pengirim)
                    ->where('iddenom', $iddenom)
                    ->first();

        if($stapengirim ->stock < $qty){

            return redirect('form/formkeluartap')->withErrors(['error' => 'Stok Tap Tidak Mencukupi!']);

        }else{

            // //update stock awal all (pengirim)
            // $sallpengirim = DB::table('stockawalall')
            //             ->select('stock')
            //             ->where('idtap', $pengirim)
            //             ->where('iddenom', $iddenom)
            //             ->first();
            // //update stock awal tap (pengirim)
            // $stapengirim = DB::table('stockawaltap')
            //             ->select('stock')
            //             ->where('idtap', $pengirim)
            //             ->where('iddenom', $iddenom)
            //             ->first();

            // //update stock awal all (penerima)
            // $sallpenerima = DB::table('stockawalall')
            //             ->select('stock')
            //             ->where('idtap', $penerima)
            //             ->where('iddenom', $iddenom)
            //             ->first();
            // //update stock awal tap (penerima)
            // $stapenerima = DB::table('stockawaltap')
            //             ->select('stock')
            //             ->where('idtap', $penerima)
            //             ->where('iddenom', $iddenom)
            //             ->first();

            // $finalstokallpengirim = $sallpengirim -> stock - $qty;
            // $finalstoktappengirim = $stapengirim -> stock - $qty;
            // $finalstokallpenerima = $sallpenerima -> stock + $qty;
            // $finalstoktappenerima = $stapenerima -> stock + $qty;

            // //update ke table

            // DB::table('stockawalall')
            //     ->where('idtap', $pengirim)
            //     ->where('iddenom',$iddenom)
            //     ->update([
            //         'stock' => $finalstokallpengirim
            //     ]);

            // DB::table('stockawaltap')
            //     ->where('idtap', $pengirim)
            //     ->where('iddenom', $iddenom)
            //     ->update([
            //         'stock' => $finalstoktappengirim
            //     ]);

            // DB::table('stockawalall')
            //     ->where('idtap', $penerima)
            //     ->where('iddenom',$iddenom)
            //     ->update([
            //         'stock' => $finalstokallpenerima
            //     ]);

            // DB::table('stockawaltap')
            //     ->where('idtap', $penerima)
            //     ->where('iddenom', $iddenom)
            //     ->update([
            //         'stock' => $finalstoktappenerima
            //     ]);

            
            //update ke table keluar
            DB::table('keluar')
                ->insert([
                    'iddenom' => $iddenom,
                    'pengirim' => $pengirim,
                    'penerima' => $penerima,
                    'qty' => $qty,
                    'tgl' => $tgl,
                    'sn' => $sn,
                    'tambahanket' => $tambahket,
                    'idtap' => $pengirim,
                    'status' => 1,

                ]);
            }

        return redirect("keluar")->with('status', 'Data Berhasil Ditambahkan!');        






    }
    
}
