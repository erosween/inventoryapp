<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SuryaConsignmentExport implements FromCollection, WithColumnWidths, WithHeadings, WithTitle
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection { return $this->rows; }

    public function title(): string { return 'Titip Jual - Nomor Perdana'; }

    public function columnWidths(): array
    {
        return ['A' => 24, 'B' => 18, 'C' => 16, 'D' => 26, 'E' => 18, 'F' => 34, 'G' => 32,
            'H' => 24, 'I' => 20, 'J' => 36, 'K' => 22, 'L' => 18, 'M' => 18];
    }

    public function headings(): array
    {
        return ['Tanggal Input / Pindah', 'TAP', 'SF Code', 'Nama SF', 'ID Outlet', 'Nama Outlet', 'Denom Konsinyasi', 'Serial Number', 'Status', 'Catatan', 'Jenis Data', 'Dari Outlet', 'Ke Outlet'];
    }
}
