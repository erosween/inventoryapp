<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\StockAllExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StockController extends Controller
{
    public function index()

    {
        $idtap = session('idtap');
        if ($idtap == 'SBP_DUMAI') {
            // Membuat array denominasi mulai dari SEGEL, V1 hingga V40
            $denoms = ['SEGEL'];
            for ($i = 1; $i <= 50; $i++) {
                $denoms[] = 'V' . $i;
            }
        
            // Membangun array selects dengan DB::raw secara dinamis
            $selects = ['idtap'];
            foreach ($denoms as $denom) {
                $selects[] = DB::raw('SUM(IF(iddenom="' . $denom . '", stock, 0)) AS ' . $denom);
            }
        
            // Menjalankan query dengan array selects
            $data = DB::table('stockawalall')
                ->select($selects)
                ->groupBy('idtap')
                ->get();
        
        }else{

        
        // Membuat array denominasi mulai dari SEGEL, V1 hingga V40
        $denoms = ['SEGEL'];
        for ($i = 1; $i <= 50; $i++) {
            $denoms[] = 'V' . $i;
        }
    
        // Membangun array selects dengan DB::raw secara dinamis
        $selects = ['idtap'];
        foreach ($denoms as $denom) {
            $selects[] = DB::raw('SUM(IF(iddenom="' . $denom . '", stock, 0)) AS ' . $denom);
        }
    
        // Menjalankan query dengan array selects
        $data = DB::table('stockawalall')
                ->select($selects)
                ->where('idtap',$idtap)
                ->groupBy('idtap')
                ->get();
        
            }
            return view('stock',compact('data','idtap'));
}


    public function exportexcel(){

        return Excel::download(new StockAllExport,'stockall.xlsx');
    }
}
