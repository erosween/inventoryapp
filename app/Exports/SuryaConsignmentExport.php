<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SuryaConsignmentExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection { return $this->rows; }

    public function title(): string { return 'Titip Jual - Nomor Perdana'; }

    public function headings(): array
    {
        return ['Tanggal Input / Pindah', 'TAP', 'SF Code', 'Nama SF', 'ID Outlet', 'Nama Outlet', 'Serial Number', 'Status', 'Catatan', 'Jenis Data', 'Dari Outlet', 'Ke Outlet'];
    }
}
