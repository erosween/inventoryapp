<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuryaVisitExport implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
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
            'ID Outlet', 'Nama Outlet', 'Hari PJP', 'Latitude Digipos', 'Longitude Digipos',
            'Latitude Visit', 'Longitude Visit', 'Jarak Visit ke Digipos (Meter)', 'Kesesuaian dengan Digipos (≤10 m)',
            'Status Approval', 'Alasan Check-out', 'Dominasi Branding', 'Kelayakan Branding',
        ];
    }

    public function title(): string
    {
        return 'Visit & Koordinat';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, 'B' => 12, 'C' => 12, 'D' => 16, 'E' => 26, 'F' => 18, 'G' => 22,
            'H' => 18, 'I' => 34, 'J' => 14, 'K' => 17, 'L' => 17, 'M' => 17, 'N' => 17,
            'O' => 25, 'P' => 34, 'Q' => 20, 'R' => 28, 'S' => 22, 'T' => 22,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(1, $this->rows->count() + 1);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:T{$lastRow}");
        $sheet->getStyle('A1:T1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C8102E']],
            'alignment' => ['vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        if ($this->rows->isNotEmpty()) {
            $mismatchHighlight = new Conditional();
            $mismatchHighlight->setConditionType(Conditional::CONDITION_EXPRESSION);
            $mismatchHighlight->addCondition('$P2="TIDAK SESUAI"');
            $mismatchHighlight->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
            $mismatchHighlight->getStyle()->getFill()->getStartColor()->setRGB('FEE2E2');
            $sheet->getStyle("A2:T{$lastRow}")->setConditionalStyles([$mismatchHighlight]);
        }

        return [];
    }
}
