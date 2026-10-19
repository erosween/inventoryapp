<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SuryaAdminSalesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Penjualan Admin SF';
    }

    public function headings(): array
    {
        return [
            'Tanggal', 'TAP', 'SF Code', 'Nama SF', 'ID/Detail Outlet',
            'Produk', 'Qty', 'Keterangan',
        ];
    }
}
