<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class StockAllExport implements FromCollection, WithHeadings
{

    
 
    public function headings(): array
    {
        return [
            'NAMA TAP',
            'DENOM',
            'QUANTITY'
            
        ];
    }
    
    public function collection()
    {
        $idtap = session('idtap');

        if($idtap == 'SBP_DUMAI'){

            return DB::table('stockawalall')
                        ->select('idtap', 'denom', 'stock')
                        ->orderBy('idtap')
                        ->get();
        }else{

            return DB::table('stockawalall')
                        ->select('idtap', 'denom', 'stock')
                        ->where('idtap',$idtap)
                        ->orderBy('idtap')
                        ->get();
        }
    }

}
