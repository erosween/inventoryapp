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
        return $this->rows->map(function ($row) {
            $values = is_array($row) ? $row : (array) $row;
            $note = trim((string) ($values[7] ?? ''));
            if ($note !== '' && preg_match('/^\d+$/', $note) !== 1) {
                $values[7] = 'NON ID/PLAYER';
            }

            return $values;
        });
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
