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

        $denom = DB::table('kategori_inject')
                    ->select('*')
                    ->where('kategori','BYU')
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
    $idtap = $request->input('idtap');
    $qty = $request->input('qty');
    $iddenom = $request->input('iddenom');
    $sn = $request->input('sn');
    $tgl = $request->input('tgl');

    // Cek stok segel TAP
    $eksstoksegeltap = $this->getStock('stockawaltap', $idtap, 'V33');

    // Validasi stok segel
    if ($qty > $eksstoksegeltap) {
        return redirect('form/forminjectbyu')->withErrors(['error' => 'Stock BYU TAP tidak mencukupi!']);
    }

    // Insert ke tabel injectvf
    DB::table('injectvf')->insert([
        'idtap' => $idtap,
        'iddenom' => $iddenom,
        'qty' => $qty,
        'sn' => $sn,
        'tgl' => $tgl,
        'kategori' => 'V33',
    ]);

    // Ambil stok saat ini
    $eksstoksegelall = $this->getStock('stockawalall', $idtap, 'V33');
    $eksstoksegeltap = $this->getStock('stockawaltap', $idtap, 'V33');
    $existingStockAll = $this->getStock('stockawalall', $idtap, $iddenom);
    $existingStockTap = $this->getStock('stockawaltap', $idtap, $iddenom);

    // Hitung stok baru
    $newStocksegelAll = $eksstoksegelall - $qty;
    $newStocksegelTAP = $eksstoksegeltap - $qty;
    $newStockAll = $existingStockAll + $qty;
    $newStockTAP = $existingStockTap + $qty;

    // Update stok segel
    $this->updateStock('stockawalall', $idtap, 'V33', $newStocksegelAll);
    $this->updateStock('stockawaltap', $idtap, 'V33', $newStocksegelTAP);

    // Update stok denom
    $this->updateStock('stockawalall', $idtap, $iddenom, $newStockAll);
    $this->updateStock('stockawaltap', $idtap, $iddenom, $newStockTAP);

    return redirect('injectvf')->with('status', 'Data Berhasil Ditambahkan!');
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
