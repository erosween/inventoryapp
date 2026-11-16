<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SuryaAdminSalesExport implements FromCollection, WithColumnWidths, WithHeadings, WithTitle
{
    public function __construct(private readonly Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows->map(function ($row) {
            $values = is_array($row) ? $row : (array) $row;
            $outletId = trim((string) ($values[4] ?? ''));
            $isNumericOutlet = preg_match('/^\d+$/', $outletId) === 1;
            $isNonDigiposOutlet = str_starts_with(strtoupper($outletId), 'NON-DIGIPOS');
            $isBlankOutlet = $outletId === '';
            $isDashOutlet = preg_match('/^[-–—]+$/u', $outletId) === 1;
            if (! $isNumericOutlet && ! $isBlankOutlet && ! $isDashOutlet && ! $isNonDigiposOutlet) {
                $values[4] = 'NON ID / PLAYER';
            }

            return array_slice($values, 0, 7);
        });
    }

    public function title(): string
    {
        return 'Penjualan Admin SF';
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 18, 'C' => 16, 'D' => 26, 'E' => 30, 'F' => 34, 'G' => 12];
    }

    public function headings(): array
    {
        return [
            'Tanggal', 'TAP', 'SF Code', 'Nama SF', 'ID/Detail Outlet',
            'Produk', 'Qty',
        ];
    }
}
