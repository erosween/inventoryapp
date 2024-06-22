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
        if($idtap =='SBP_DUMAI'){
            $data = DB::table('stockawalall')
                ->select('idtap',
                    DB::raw('SUM(IF(iddenom="SEGEL", stock, 0)) AS SEGEL'),
                    DB::raw('SUM(IF(iddenom="V1", stock, 0)) AS V1'),
                    DB::raw('SUM(IF(iddenom="V2", stock, 0)) AS V2'),
                    DB::raw('SUM(IF(iddenom="V3", stock, 0)) AS V3'),
                    DB::raw('SUM(IF(iddenom="V4", stock, 0)) AS V4'),
                    DB::raw('SUM(IF(iddenom="V5", stock, 0)) AS V5'),
                    DB::raw('SUM(IF(iddenom="V6", stock, 0)) AS V6'),
                    DB::raw('SUM(IF(iddenom="V7", stock, 0)) AS V7'),
                    DB::raw('SUM(IF(iddenom="V8", stock, 0)) AS V8'),
                    DB::raw('SUM(IF(iddenom="V9", stock, 0)) AS V9'),
                    DB::raw('SUM(IF(iddenom="V10", stock, 0)) AS V10'),
                    DB::raw('SUM(IF(iddenom="V11", stock, 0)) AS V11'),
                    DB::raw('SUM(IF(iddenom="V12", stock, 0)) AS V12'),
                    DB::raw('SUM(IF(iddenom="V13", stock, 0)) AS V13'),
                    DB::raw('SUM(IF(iddenom="V14", stock, 0)) AS V14'),
                    DB::raw('SUM(IF(iddenom="V15", stock, 0)) AS V15'),
                    DB::raw('SUM(IF(iddenom="V16", stock, 0)) AS V16'),
                    DB::raw('SUM(IF(iddenom="V17", stock, 0)) AS V17'),
                    DB::raw('SUM(IF(iddenom="V18", stock, 0)) AS V18'),
                    DB::raw('SUM(IF(iddenom="V19", stock, 0)) AS V19'),
                    DB::raw('SUM(IF(iddenom="V20", stock, 0)) AS V20'),
                    DB::raw('SUM(IF(iddenom="V21", stock, 0)) AS V21'),
                    DB::raw('SUM(IF(iddenom="V22", stock, 0)) AS V22'),
                    DB::raw('SUM(IF(iddenom="V23", stock, 0)) AS V23'),
                    DB::raw('SUM(IF(iddenom="V24", stock, 0)) AS V24'),
                    DB::raw('SUM(IF(iddenom="V25", stock, 0)) AS V25'),
                    DB::raw('SUM(IF(iddenom="V26", stock, 0)) AS V26'),
                    DB::raw('SUM(IF(iddenom="V27", stock, 0)) AS V27'),
                    DB::raw('SUM(IF(iddenom="V28", stock, 0)) AS V28'),
                    DB::raw('SUM(IF(iddenom="V29", stock, 0)) AS V29'),
                    DB::raw('SUM(IF(iddenom="V30", stock, 0)) AS V30'),
                    DB::raw('SUM(IF(iddenom="V31", stock, 0)) AS V31'),
                    DB::raw('SUM(IF(iddenom="V32", stock, 0)) AS V32'),
                    DB::raw('SUM(IF(iddenom="V33", stock, 0)) AS V33'),
                    DB::raw('SUM(IF(iddenom="V34", stock, 0)) AS V34'),
                    DB::raw('SUM(IF(iddenom="V35", stock, 0)) AS V35'),
                    DB::raw('SUM(IF(iddenom="V36", stock, 0)) AS V36'),
                    DB::raw('SUM(IF(iddenom="V37", stock, 0)) AS V37'),
                    DB::raw('SUM(IF(iddenom="V38", stock, 0)) AS V38'),
                    DB::raw('SUM(IF(iddenom="V39", stock, 0)) AS V39'),
                    DB::raw('SUM(IF(iddenom="V40", stock, 0)) AS V40')
                )
                ->groupBy('idtap')
                ->get();
        
                return view('stock',compact('data','idtap'));

        }else{

        
        $data = DB::table('stockawalall')
                ->select('idtap',
                DB::raw('SUM(IF(iddenom="SEGEL", stock, 0)) AS SEGEL'),
                DB::raw('SUM(IF(iddenom="V1", stock, 0)) AS V1'),
                DB::raw('SUM(IF(iddenom="V2", stock, 0)) AS V2'),
                DB::raw('SUM(IF(iddenom="V3", stock, 0)) AS V3'),
                DB::raw('SUM(IF(iddenom="V4", stock, 0)) AS V4'),
                DB::raw('SUM(IF(iddenom="V5", stock, 0)) AS V5'),
                DB::raw('SUM(IF(iddenom="V6", stock, 0)) AS V6'),
                DB::raw('SUM(IF(iddenom="V7", stock, 0)) AS V7'),
                DB::raw('SUM(IF(iddenom="V8", stock, 0)) AS V8'),
                DB::raw('SUM(IF(iddenom="V9", stock, 0)) AS V9'),
                DB::raw('SUM(IF(iddenom="V10", stock, 0)) AS V10'),
                DB::raw('SUM(IF(iddenom="V11", stock, 0)) AS V11'),
                DB::raw('SUM(IF(iddenom="V12", stock, 0)) AS V12'),
                DB::raw('SUM(IF(iddenom="V13", stock, 0)) AS V13'),
                DB::raw('SUM(IF(iddenom="V14", stock, 0)) AS V14'),
                DB::raw('SUM(IF(iddenom="V15", stock, 0)) AS V15'),
                DB::raw('SUM(IF(iddenom="V16", stock, 0)) AS V16'),
                DB::raw('SUM(IF(iddenom="V17", stock, 0)) AS V17'),
                DB::raw('SUM(IF(iddenom="V18", stock, 0)) AS V18'),
                DB::raw('SUM(IF(iddenom="V19", stock, 0)) AS V19'),
                DB::raw('SUM(IF(iddenom="V20", stock, 0)) AS V20'),
                DB::raw('SUM(IF(iddenom="V21", stock, 0)) AS V21'),
                DB::raw('SUM(IF(iddenom="V22", stock, 0)) AS V22'),
                DB::raw('SUM(IF(iddenom="V23", stock, 0)) AS V23'),
                DB::raw('SUM(IF(iddenom="V24", stock, 0)) AS V24'),
                DB::raw('SUM(IF(iddenom="V25", stock, 0)) AS V25'),
                DB::raw('SUM(IF(iddenom="V26", stock, 0)) AS V26'),
                DB::raw('SUM(IF(iddenom="V27", stock, 0)) AS V27'),
                DB::raw('SUM(IF(iddenom="V28", stock, 0)) AS V28'),
                DB::raw('SUM(IF(iddenom="V29", stock, 0)) AS V29'),
                DB::raw('SUM(IF(iddenom="V30", stock, 0)) AS V30'),
                DB::raw('SUM(IF(iddenom="V31", stock, 0)) AS V31'),
                DB::raw('SUM(IF(iddenom="V32", stock, 0)) AS V32'),
                DB::raw('SUM(IF(iddenom="V33", stock, 0)) AS V33'),
                DB::raw('SUM(IF(iddenom="V34", stock, 0)) AS V34'),
                DB::raw('SUM(IF(iddenom="V35", stock, 0)) AS V35'),
                DB::raw('SUM(IF(iddenom="V36", stock, 0)) AS V36'),
                DB::raw('SUM(IF(iddenom="V37", stock, 0)) AS V37'),
                DB::raw('SUM(IF(iddenom="V38", stock, 0)) AS V38'),
                DB::raw('SUM(IF(iddenom="V39", stock, 0)) AS V39'),
                DB::raw('SUM(IF(iddenom="V40", stock, 0)) AS V40')
                )
                ->where('idtap',$idtap)
                ->groupBy('idtap')
                ->get();
        
        return view('stock',compact('data','idtap'));
    }
}


    public function exportexcel(){

        return Excel::download(new StockAllExport,'stockall.xlsx');
    }
}
