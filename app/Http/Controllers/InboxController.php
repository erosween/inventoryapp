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

// proses terima stock
public function masuk(Request $request, $idkeluar)
{
    $pengirim = $request->input('pengirim');
    $penerima = $request->input('penerima');
    $iddenom = $request -> input('iddenom');
    $qty = $request -> input('qty');
    $status = $request -> input('status');

    //validasi
    $stap = DB::table('stockawaltap')
                ->select('stock')
                ->where('idtap', $pengirim)
                ->where('iddenom', $iddenom)
                ->first();

    if($stap->stock < $qty){

        return redirect('masuk')->withErrors(['error' => 'Stok Tap Pengirim Tidak Mencukupi!']);

    }else{

        //cek stok tap pengirim
        $pall = DB::table('stockawalall')
                ->select('stock')
                ->where('idtap', $pengirim)
                ->where('iddenom', $iddenom)
                ->first();

        $ptap = DB::table('stockawaltap')
                ->select('stock')
                ->where('idtap', $pengirim)
                ->where('iddenom',$iddenom)
                ->first();

        //kalkulasi
        $nall = $pall -> stock - $qty;
        $ntap = $ptap -> stock - $qty;

        // cek stok penerima
        $penall = DB::table('stockawalall')
                ->select('stock')
                ->where('idtap', $penerima)
                ->where('iddenom', $iddenom)
                ->first();

        $pentap = DB::table('stockawaltap')
                ->select('stock')
                ->where('idtap', $penerima)
                ->where('iddenom',$iddenom)
                ->first();

        //kalkulasi
        $npall = $penall -> stock + $qty;
        $nptap = $pentap -> stock + $qty;

        //update ke stok terbaru

        //pengirim

        DB::table('stockawalall')
            ->where('idtap', $pengirim)
            ->where('iddenom', $iddenom)
            ->update([
                'stock' => $nall
            ]);

        DB::table('stockawaltap')
            ->where('idtap', $pengirim)
            ->where('iddenom', $iddenom)
            ->update([
                'stock' => $ntap
            ]);

        // penerima
        
        DB::table('stockawalall')
        ->where('idtap', $penerima)
        ->where('iddenom', $iddenom)
        ->update([
            'stock' => $npall
        ]);

        DB::table('stockawaltap')
            ->where('idtap', $penerima)
            ->where('iddenom', $iddenom)
            ->update([
                'stock' => $nptap
            ]);

        //update status

        DB::table('keluar')
            ->where('idkeluar', $idkeluar)
            ->update([
                'status' => 0
            ]);
        
        return redirect('inbox')->with('status','Stock Berhasil Diterima');
    }
}

    
}

