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
    $idtap = $request->input('idtap');
    $iddenom = $request->input('iddenom');
    $qty = $request->input('qty');
    $sn = $request->input('sn');
    $tgl = $request->input('tgl');

    // Cek stok segel TAP
    if (!$this->cekStok('stockawaltap', $idtap, 'SEGEL', $qty)) {
        return redirect('form/forminject')->withErrors(['error' => 'Stock Segel TAP tidak mencukupi!']);
    }

    // Insert data ke injectvf
    DB::table('injectvf')->insert([
        'idtap' => $idtap,
        'iddenom' => $iddenom,
        'qty' => $qty,
        'sn' => $sn,
        'tgl' => $tgl,
        'kategori' => 'SEGEL',
    ]);

    // Update stok segel dan stok denom
    $this->updateStok('stockawalall', $idtap, 'SEGEL', -$qty);
    $this->updateStok('stockawaltap', $idtap, 'SEGEL', -$qty);
    $this->updateStok('stockawalall', $idtap, $iddenom, $qty);
    $this->updateStok('stockawaltap', $idtap, $iddenom, $qty);

    return redirect('injectvf')->with('status', 'Data Berhasil Ditambahkan!');
}

/**
 * Cek apakah stok mencukupi.
 */
private function cekStok($table, $idtap, $iddenom, $qty)
{
    $stok = DB::table($table)
        ->where('idtap', $idtap)
        ->where('iddenom', $iddenom)
        ->value('stock');

    return $stok >= $qty;
}

/**
 * Update stok di tabel tertentu.
 */
private function updateStok($table, $idtap, $iddenom, $qtyChange)
{
    DB::table($table)
        ->where('idtap', $idtap)
        ->where('iddenom', $iddenom)
        ->increment('stock', $qtyChange);
}

    
}
