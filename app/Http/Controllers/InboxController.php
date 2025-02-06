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
        $pengirim = $request->input('pengirim');
        $penerima = $request->input('penerima');
        $iddenom = $request->input('iddenom');
        $qty = $request->input('qty');
    
        // Validasi stok pengirim
        $stapStock = $this->getStock('stockawaltap', $pengirim, $iddenom);
    
        if ($stapStock < $qty) {
            return redirect('masuk')->withErrors(['error' => 'Stok Tap Pengirim Tidak Mencukupi!']);
        }
    
        // Ambil stok pengirim
        $pallStock = $this->getStock('stockawalall', $pengirim, $iddenom);
        $ptapStock = $this->getStock('stockawaltap', $pengirim, $iddenom);
    
        // Hitung stok baru pengirim
        $newPallStock = $pallStock - $qty;
        $newPtapStock = $ptapStock - $qty;
    
        // Ambil stok penerima
        $penallStock = $this->getStock('stockawalall', $penerima, $iddenom);
        $pentapStock = $this->getStock('stockawaltap', $penerima, $iddenom);
    
        // Hitung stok baru penerima
        $newPenallStock = $penallStock + $qty;
        $newPentapStock = $pentapStock + $qty;
    
        // Update stok pengirim
        $this->updateStock('stockawalall', $pengirim, $iddenom, $newPallStock);
        $this->updateStock('stockawaltap', $pengirim, $iddenom, $newPtapStock);
    
        // Update stok penerima
        $this->updateStock('stockawalall', $penerima, $iddenom, $newPenallStock);
        $this->updateStock('stockawaltap', $penerima, $iddenom, $newPentapStock);
    
        // Update status di tabel keluar
        DB::table('keluar')
            ->where('idkeluar', $idkeluar)
            ->update(['status' => 0]);
    
        return redirect('inbox')->with('status', 'Stock Berhasil Diterima');
    }
    
    private function getStock($table, $idtap, $iddenom)
    {
        return DB::table($table)
            ->where('idtap', $idtap)
            ->where('iddenom', $iddenom)
            ->value('stock');
    }
    
    private function updateStock($table, $idtap, $iddenom, $newStock)
    {
        DB::table($table)
            ->where('idtap', $idtap)
            ->where('iddenom', $iddenom)
            ->update(['stock' => $newStock]);
    }
    
}

