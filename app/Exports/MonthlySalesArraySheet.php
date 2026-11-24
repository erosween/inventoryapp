<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlySalesArraySheet implements FromArray, WithTitle, WithStyles, WithColumnFormatting
{
    public function __construct(private readonly string $sheetTitle, private readonly array $rows)
    {
    }

    public function array(): array { return $this->rows; }

    public function title(): string { return $this->sheetTitle; }

    public function columnFormats(): array
    {
        $formats = [];
        if ($this->sheetTitle === 'Rekap TAP') {
            for ($column = 3; $column <= 25; $column += 2) {
                $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
                $formats[$letter] = NumberFormat::FORMAT_PERCENTAGE_0;
            }
        }
        return $formats;
    }

    public function styles(Worksheet $sheet): array
    {
        $last = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->rows[0] ?? []));
        $sheet->freezePane('B2');
        $sheet->setAutoFilter('A1:'.$last.count($this->rows));
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getStyle('A1:'.$last.'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '173D5B']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);
        return [];
    }
}
