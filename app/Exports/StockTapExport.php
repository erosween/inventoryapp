<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class StockTapExport implements FromCollection, WithHeadings
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
            return DB::table('stockawaltap')
            ->select('idtap', 'denom', 'stock')
            ->get();

        }else{

            return DB::table('stockawaltap')
                ->select('idtap', 'denom', 'stock')
                ->where('idtap', $idtap)
                ->get();
        }
    }

}
