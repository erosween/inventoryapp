<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\StockSfExport;
use App\Exports\StockTapExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;


class StockSfController extends Controller
{
    public function index()
    {
        $idtap = session('idtap');

        if ($idtap == 'SBP_DUMAI') {

            // Membuat array denominasi mulai dari SEGEL, V1 hingga V40
            $denoms = ['SEGEL'];
            for ($i = 1; $i <= 40; $i++) {
                $denoms[] = 'V' . $i;
            }
        
            // Membangun array selects dengan DB::raw secara dinamis
            $selects = ['idtap', 'namasf'];
            foreach ($denoms as $denom) {
                $selects[] = DB::raw('SUM(IF(iddenom="' . $denom . '", stock, 0)) AS ' . $denom);
            }
        
            // Menjalankan query dengan array selects
            $data = DB::table('stockawalsf')
                ->select($selects)
                ->groupBy('idtap', 'namasf')
                ->get();
        
            // Inisialisasi array gTotal secara dinamis
            $gTotal = array_fill_keys($denoms, 0);
        
            // Menghitung total per denominasi
            foreach ($data as $item) {
                foreach ($denoms as $denom) {
                    $gTotal[$denom] += $item->$denom;
                }
            }
        
            // Menghitung totalbaris untuk setiap item
            foreach ($data as $item) {
                $item->totalbaris = array_reduce($denoms, function ($total, $denom) use ($item) {
                    return $total + $item->$denom;
                }, 0);
            }
        
        
        
        }else{
            // Membuat array denominasi mulai dari SEGEL, V1 hingga V40
            $denoms = ['SEGEL'];
            for ($i = 1; $i <= 40; $i++) {
                $denoms[] = 'V' . $i;
            }
        
            // Membangun array selects dengan DB::raw secara dinamis
            $selects = ['idtap', 'namasf'];
            foreach ($denoms as $denom) {
                $selects[] = DB::raw('SUM(IF(iddenom="' . $denom . '", stock, 0)) AS ' . $denom);
            }
        
            // Menjalankan query dengan array selects
            $data = DB::table('stockawalsf')
                ->select($selects)
                ->where('idtap',$idtap)
                ->groupBy('idtap', 'namasf')
                ->get();
        
            // Inisialisasi array gTotal secara dinamis
            $gTotal = array_fill_keys($denoms, 0);
        
            // Menghitung total per denominasi
            foreach ($data as $item) {
                foreach ($denoms as $denom) {
                    $gTotal[$denom] += $item->$denom;
                }
            }
        
            // Menghitung totalbaris untuk setiap item
            foreach ($data as $item) {
                $item->totalbaris = array_reduce($denoms, function ($total, $denom) use ($item) {
                    return $total + $item->$denom;
                }, 0);
            }
                                
        }
        return view('stocksf',compact('data','idtap','gTotal'));
}

    public function exportexcel(){
        return Excel::download(new StockSfExport,'stock_SF.xlsx');
    }


}
