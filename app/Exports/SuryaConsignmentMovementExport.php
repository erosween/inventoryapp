<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SuryaConsignmentMovementExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly Collection $rows) {}
    public function collection(): Collection { return $this->rows; }
    public function title(): string { return 'Riwayat Migrasi Konsinyasi'; }
    public function headings(): array
    {
        return ['Tanggal Pindah', 'Serial Number', 'Dari ID Outlet', 'Nama Outlet Asal', 'Ke ID Outlet', 'Nama Outlet Tujuan', 'TAP', 'SF Code', 'Nama SF', 'Receipt ID'];
    }
}
