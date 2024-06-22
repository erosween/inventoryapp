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

        if($idtap =='SBP_DUMAI'){

            $data = DB::table('stockawalsf')
                    ->select('idtap','namasf',
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
                    ->groupBy('idtap','namasf')
                    ->get();

                $gTotal =[];

                $gTotal['SEGEL'] = 0;
                $gTotal['V1'] = 0;
                $gTotal['V2'] = 0;
                $gTotal['V3'] = 0;
                $gTotal['V4'] = 0;
                $gTotal['V5'] = 0;
                $gTotal['V6'] = 0;
                $gTotal['V7'] = 0;
                $gTotal['V8'] = 0;
                $gTotal['V9'] = 0;
                $gTotal['V10'] = 0;
                $gTotal['V11'] = 0;
                $gTotal['V12'] = 0;
                $gTotal['V13'] = 0;
                $gTotal['V14'] = 0;
                $gTotal['V15'] = 0;
                $gTotal['V16'] = 0;
                $gTotal['V17'] = 0;
                $gTotal['V18'] = 0;
                $gTotal['V19'] = 0;
                $gTotal['V20'] = 0;
                $gTotal['V21'] = 0;
                $gTotal['V22'] = 0;
                $gTotal['V23'] = 0;
                $gTotal['V24'] = 0;
                $gTotal['V25'] = 0;
                $gTotal['V26'] = 0;
                $gTotal['V27'] = 0;
                $gTotal['V28'] = 0;
                $gTotal['V29'] = 0;
                $gTotal['V30'] = 0;
                $gTotal['V31'] = 0;
                $gTotal['V32'] = 0;
                $gTotal['V33'] = 0;
                $gTotal['V34'] = 0;
                $gTotal['V35'] = 0;
                $gTotal['V36'] = 0;
                $gTotal['V37'] = 0;
                $gTotal['V38'] = 0;
                $gTotal['V39'] = 0;
                $gTotal['V40'] = 0;

                foreach( $data as $item ){

                    $gTotal['SEGEL'] += $item->SEGEL;
                    $gTotal['V1'] += $item->V1;
                    $gTotal['V2'] += $item->V2;
                    $gTotal['V3'] += $item->V3;
                    $gTotal['V4'] += $item->V4;
                    $gTotal['V5'] += $item->V5;
                    $gTotal['V6'] += $item->V6;
                    $gTotal['V7'] += $item->V7;
                    $gTotal['V8'] += $item->V8;
                    $gTotal['V9'] += $item->V9;
                    $gTotal['V10'] += $item->V10;
                    $gTotal['V11'] += $item->V11;
                    $gTotal['V12'] += $item->V12;
                    $gTotal['V13'] += $item->V13;
                    $gTotal['V14'] += $item->V14;
                    $gTotal['V15'] += $item->V15;
                    $gTotal['V16'] += $item->V16;
                    $gTotal['V17'] += $item->V17;
                    $gTotal['V18'] += $item->V18;
                    $gTotal['V19'] += $item->V19;
                    $gTotal['V20'] += $item->V20;
                    $gTotal['V21'] += $item->V21;
                    $gTotal['V22'] += $item->V22;
                    $gTotal['V23'] += $item->V23;
                    $gTotal['V24'] += $item->V24;
                    $gTotal['V25'] += $item->V25;
                    $gTotal['V26'] += $item->V26;
                    $gTotal['V27'] += $item->V27;
                    $gTotal['V28'] += $item->V28;
                    $gTotal['V29'] += $item->V29;
                    $gTotal['V30'] += $item->V30;
                    $gTotal['V31'] += $item->V31;
                    $gTotal['V32'] += $item->V32;
                    $gTotal['V33'] += $item->V33;
                    $gTotal['V34'] += $item->V34;
                    $gTotal['V35'] += $item->V35;
                    $gTotal['V36'] += $item->V36;
                    $gTotal['V37'] += $item->V37;
                    $gTotal['V38'] += $item->V38;
                    $gTotal['V39'] += $item->V39;
                    $gTotal['V40'] += $item->V40;

                }

                foreach($data as $item){

                    $totalbaris = 0;
                    
                    $totalbaris += $item->SEGEL;
                    $totalbaris += $item->V1;
                    $totalbaris += $item->V2;
                    $totalbaris += $item->V3;
                    $totalbaris += $item->V4;
                    $totalbaris += $item->V5;
                    $totalbaris += $item->V6;
                    $totalbaris += $item->V7;
                    $totalbaris += $item->V8;
                    $totalbaris += $item->V9;
                    $totalbaris  += $item->V10;
                    $totalbaris  += $item->V11;
                    $totalbaris  += $item->V12;
                    $totalbaris  += $item->V13;
                    $totalbaris  += $item->V14;
                    $totalbaris  += $item->V15;
                    $totalbaris  += $item->V16;
                    $totalbaris  += $item->V17;
                    $totalbaris  += $item->V18;
                    $totalbaris  += $item->V19;
                    $totalbaris  += $item->V20;
                    $totalbaris  += $item->V21;
                    $totalbaris  += $item->V22;
                    $totalbaris  += $item->V23;
                    $totalbaris  += $item->V24;
                    $totalbaris  += $item->V25;
                    $totalbaris  += $item->V26;
                    $totalbaris  += $item->V27;
                    $totalbaris  += $item->V28;
                    $totalbaris  += $item->V29;
                    $totalbaris  += $item->V30;
                    $totalbaris  += $item->V31;
                    $totalbaris  += $item->V32;
                    $totalbaris  += $item->V33;
                    $totalbaris  += $item->V34;
                    $totalbaris  += $item->V35;
                    $totalbaris  += $item->V36;
                    $totalbaris  += $item->V37;
                    $totalbaris  += $item->V38;
                    $totalbaris  += $item->V39;
                    $totalbaris  += $item->V40;

                    $item -> totalbaris = $totalbaris;
                    
                }

                return view('stocksf',compact('data','idtap','gTotal','totalbaris'));
            
        }else{
            $data = DB::table('stockawalsf')
                    ->select('idtap','namasf',
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
                    ->where('idtap', $idtap)
                    ->groupBy('idtap','namasf')
                    ->get();

                    $gTotal =[];

                $gTotal['SEGEL'] = 0;
                $gTotal['V1'] = 0;
                $gTotal['V2'] = 0;
                $gTotal['V3'] = 0;
                $gTotal['V4'] = 0;
                $gTotal['V5'] = 0;
                $gTotal['V6'] = 0;
                $gTotal['V7'] = 0;
                $gTotal['V8'] = 0;
                $gTotal['V9'] = 0;
                $gTotal['V10'] = 0;
                $gTotal['V11'] = 0;
                $gTotal['V12'] = 0;
                $gTotal['V13'] = 0;
                $gTotal['V14'] = 0;
                $gTotal['V15'] = 0;
                $gTotal['V16'] = 0;
                $gTotal['V17'] = 0;
                $gTotal['V18'] = 0;
                $gTotal['V19'] = 0;
                $gTotal['V20'] = 0;
                $gTotal['V21'] = 0;
                $gTotal['V22'] = 0;
                $gTotal['V23'] = 0;
                $gTotal['V24'] = 0;
                $gTotal['V25'] = 0;
                $gTotal['V26'] = 0;
                $gTotal['V27'] = 0;
                $gTotal['V28'] = 0;
                $gTotal['V29'] = 0;
                $gTotal['V30'] = 0;
                $gTotal['V31'] = 0;
                $gTotal['V32'] = 0;
                $gTotal['V33'] = 0;
                $gTotal['V34'] = 0;
                $gTotal['V35'] = 0;
                $gTotal['V36'] = 0;
                $gTotal['V37'] = 0;
                $gTotal['V38'] = 0;
                $gTotal['V39'] = 0;
                $gTotal['V40'] = 0;

                foreach( $data as $item ){

                    $gTotal['SEGEL'] += $item->SEGEL;
                    $gTotal['V1'] += $item->V1;
                    $gTotal['V2'] += $item->V2;
                    $gTotal['V3'] += $item->V3;
                    $gTotal['V4'] += $item->V4;
                    $gTotal['V5'] += $item->V5;
                    $gTotal['V6'] += $item->V6;
                    $gTotal['V7'] += $item->V7;
                    $gTotal['V8'] += $item->V8;
                    $gTotal['V9'] += $item->V9;
                    $gTotal['V10'] += $item->V10;
                    $gTotal['V11'] += $item->V11;
                    $gTotal['V12'] += $item->V12;
                    $gTotal['V13'] += $item->V13;
                    $gTotal['V14'] += $item->V14;
                    $gTotal['V15'] += $item->V15;
                    $gTotal['V16'] += $item->V16;
                    $gTotal['V17'] += $item->V17;
                    $gTotal['V18'] += $item->V18;
                    $gTotal['V19'] += $item->V19;
                    $gTotal['V20'] += $item->V20;
                    $gTotal['V21'] += $item->V21;
                    $gTotal['V22'] += $item->V22;
                    $gTotal['V23'] += $item->V23;
                    $gTotal['V24'] += $item->V24;
                    $gTotal['V25'] += $item->V25;
                    $gTotal['V26'] += $item->V26;
                    $gTotal['V27'] += $item->V27;
                    $gTotal['V28'] += $item->V28;
                    $gTotal['V29'] += $item->V29;
                    $gTotal['V30'] += $item->V30;
                    $gTotal['V31'] += $item->V31;
                    $gTotal['V32'] += $item->V32;
                    $gTotal['V33'] += $item->V33;
                    $gTotal['V34'] += $item->V34;
                    $gTotal['V35'] += $item->V35;
                    $gTotal['V36'] += $item->V36;
                    $gTotal['V37'] += $item->V37;
                    $gTotal['V38'] += $item->V38;
                    $gTotal['V39'] += $item->V39;
                    $gTotal['V40'] += $item->V40;

                }

                foreach($data as $item){

                    $totalbaris = 0;
                    
                    $totalbaris += $item->SEGEL;
                    $totalbaris += $item->V1;
                    $totalbaris += $item->V2;
                    $totalbaris += $item->V3;
                    $totalbaris += $item->V4;
                    $totalbaris += $item->V5;
                    $totalbaris += $item->V6;
                    $totalbaris += $item->V7;
                    $totalbaris += $item->V8;
                    $totalbaris += $item->V9;
                    $totalbaris  += $item->V10;
                    $totalbaris  += $item->V11;
                    $totalbaris  += $item->V12;
                    $totalbaris  += $item->V13;
                    $totalbaris  += $item->V14;
                    $totalbaris  += $item->V15;
                    $totalbaris  += $item->V16;
                    $totalbaris  += $item->V17;
                    $totalbaris  += $item->V18;
                    $totalbaris  += $item->V19;
                    $totalbaris  += $item->V20;
                    $totalbaris  += $item->V21;
                    $totalbaris  += $item->V22;
                    $totalbaris  += $item->V23;
                    $totalbaris  += $item->V24;
                    $totalbaris  += $item->V25;
                    $totalbaris  += $item->V26;
                    $totalbaris  += $item->V27;
                    $totalbaris  += $item->V28;
                    $totalbaris  += $item->V29;
                    $totalbaris  += $item->V30;
                    $totalbaris  += $item->V31;
                    $totalbaris  += $item->V32;
                    $totalbaris  += $item->V33;
                    $totalbaris  += $item->V34;
                    $totalbaris  += $item->V35;
                    $totalbaris  += $item->V36;
                    $totalbaris  += $item->V37;
                    $totalbaris  += $item->V38;
                    $totalbaris  += $item->V39;
                    $totalbaris  += $item->V40;

                    $item -> totalbaris = $totalbaris;
                    
                }
                    
                    
                    return view('stocksf',compact('data','idtap','gTotal'));
                }
}

    public function exportexcel(){
        return Excel::download(new StockSfExport,'stock_SF.xlsx');
    }


}
