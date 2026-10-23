<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuryaNonDigiposOutletExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection { return $this->rows; }

    public function map($row): array
    {
        return [
            $row->outlet_name,
            $row->owner_name,
            $row->phone_number,
            $row->outlet_address,
            $row->namasf ?: $row->idsf,
            $row->idtap,
            (int) $row->total_sa,
            (int) $row->total_pv,
        ];
    }

    public function headings(): array
    {
        return ['Outlet', 'Owner', 'Contact', 'Alamat', 'SF', 'TAP', 'SA', 'PV'];
    }

    public function title(): string { return 'Outlet Non Digipos'; }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(1, $this->rows->count() + 1);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:H{$lastRow}");
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C8102E']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getStyle("G2:H{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getColumnDimension('D')->setWidth(42);

        return [];
    }
}
