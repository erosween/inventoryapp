<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SuryaSalesProductExport implements FromCollection, WithColumnWidths, WithHeadings, WithTitle
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

    public function columnWidths(): array
    {
        return [
            'A' => 14, 'B' => 23, 'C' => 28, 'D' => 26, 'E' => 18, 'F' => 18, 'G' => 34,
            'H' => 34, 'I' => 18, 'J' => 12, 'K' => 18, 'L' => 18, 'M' => 22,
        ];
    }

    public function headings(): array
    {
        return [
            'Tanggal', 'SF Code Pemilik Outlet', 'Nama SF Pemilik Outlet', 'Helper', 'TAP', 'ID Outlet', 'Nama Outlet',
            'Produk', 'Kategori', 'Qty', 'Harga Satuan', 'Total', 'Status Approval',
        ];
    }
}
