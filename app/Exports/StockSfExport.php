<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class StockSfExport implements FromCollection, WithHeadings
{

    
 
    public function headings(): array
    {
        return [
            'NAMA TAP',
            'NAMA SF',
            'DENOM',
            'QUANTITY'
            
        ];
    }
 
    public function collection()
    {   
        $idtap = session('idtap');

        if($idtap == 'SBP_DUMAI'){

            return DB::table('stockawalsf')
                        ->join('denom', 'stockawalsf.iddenom', '=', 'denom.iddenom')
                        ->select('stockawalsf.idtap', 'stockawalsf.namasf','denom.denom', 'stockawalsf.stock')
                        ->get();
        }else{
            
            return DB::table('stockawalsf')
                        ->join('denom', 'stockawalsf.iddenom', '=', 'denom.iddenom')
                        ->select('stockawalsf.idtap', 'stockawalsf.namasf','denom.denom', 'stockawalsf.stock')
                        ->where('stockawalsf.idtap', $idtap)
                        ->get();
        }
    }

}
