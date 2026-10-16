<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SuryaSalesProductExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
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
        return 'Penjualan per Produk';
    }

    public function headings(): array
    {
        return [
            'Tanggal', 'SF Code', 'Nama SF', 'TAP', 'ID Outlet', 'Nama Outlet',
            'Produk', 'Kategori', 'Qty', 'Harga Satuan', 'Total', 'Status Approval',
        ];
    }
}
