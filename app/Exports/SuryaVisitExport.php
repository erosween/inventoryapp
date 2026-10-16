<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuryaVisitExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Tanggal Visit', 'Check In', 'Check Out', 'SF Code', 'Nama SF', 'TAP', 'Cluster',
            'ID Outlet', 'Nama Outlet', 'Hari PJP', 'Latitude Master', 'Longitude Master',
            'Latitude Visit', 'Longitude Visit', 'Jarak (Meter)', 'Kesesuaian Longlat',
            'Status Approval', 'Alasan Check-out',
        ];
    }

    public function title(): string
    {
        return 'Visit & Koordinat';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(1, $this->rows->count() + 1);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:R{$lastRow}");
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C8102E']],
            'alignment' => ['vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        for ($row = 2; $row <= $lastRow; $row++) {
            if ($sheet->getCell("P{$row}")->getValue() === 'TIDAK SESUAI') {
                $sheet->getStyle("A{$row}:R{$row}")->getFill()
                    ->setFillType('solid')
                    ->getStartColor()->setRGB('FEE2E2');
            }
        }

        return [];
    }
}
