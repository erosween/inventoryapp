<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SuryaStockOnHandExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly string $family,
        private readonly Collection $rows,
    ) {
    }

    public function array(): array
    {
        return $this->rows->flatMap(function (array $row): array {
            return $row['items']->map(fn (array $item): array => [
                $row['idsf'],
                $row['namasf'],
                $row['idtap'],
                $row['cluster'],
                $item['validity'],
                $item['target'],
                $item['daily'],
                $item['actual'],
                $item['stock'],
                $item['recommendation'],
                $item['status'],
                $item['status_detail'],
            ])->all();
        })->values()->all();
    }

    public function headings(): array
    {
        return [
            'SF Code', 'Nama SF', 'TAP', 'Cluster', 'Validity', 'Target Bulanan',
            'Target Harian', 'Actual MTD', 'Stock on Hand', 'Rekomendasi', 'Status', 'Detail Status',
        ];
    }

    public function title(): string
    {
        return 'Stock On Hand '.$this->family;
    }
}
