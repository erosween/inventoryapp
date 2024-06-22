<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class InjectExport implements FromCollection, WithHeadings
{

    protected $penjualanData;

    public function __construct($penjualanData)
    {
        $this->penjualanData = $penjualanData;
    }

    public function collection()
    {
        return $this->penjualanData;
    }

    public function headings(): array
    {
        return [
            'TANGGAL',
            'SN',
            'TAP',
            'DENOM',
            'QUANTITY'
            
        ];
    }

}

