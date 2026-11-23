<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OutletSalesInsightSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    private array $rows;

    public function __construct(private readonly array $insight, private readonly string $type)
    {
        $this->rows = $type === 'tap'
            ? array_merge($insight['tapRows'], $insight['clusters'])
            : $insight['districtRows'];
        $this->rows[] = $insight['grand'] + ['grand' => true];
    }

    public function title(): string
    {
        return $this->type === 'tap' ? 'Summary TAP' : 'Summary Kecamatan';
    }

    public function headings(): array
    {
        $area = $this->type === 'tap' ? 'TAP' : 'Kecamatan';
        return [
            [$this->title().' · RS Aktif PV'],
            ['PJP = jumlah outlet terjadwal; Rate = RS aktif MTD ÷ jumlah outlet PJP di wilayah.'],
            [$area, 'PJP', 'RS Aktif PV', '', '', '', '', ''],
            ['', '', 'FM-1', 'M-1', 'MTD', 'MoM', '%MoM', 'Rate'],
        ];
    }

    public function array(): array
    {
        return array_map(function (array $row): array {
            $fm1 = $row['values']['fm1'];
            $m1 = $row['values']['m1'];
            $mtd = $row['values']['mtd'];
            return [
                $row['area'], $row['pjp'], $fm1, $m1, $mtd, $mtd - $m1,
                $m1 > 0 ? ($mtd / $m1) - 1 : null,
                $row['pjp'] > 0 ? $mtd / $row['pjp'] : null,
            ];
        }, $this->rows);
    }

    public function styles(Worksheet $sheet): array
    {
        $end = 4 + count($this->rows);
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('C3:H3');
        $sheet->mergeCells('A3:A4');
        $sheet->mergeCells('B3:B4');
        $sheet->freezePane('C5');
        $sheet->setAutoFilter('A4:H'.$end);
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(22);
        foreach (['C','D','E','F'] as $column) $sheet->getColumnDimension($column)->setWidth(15);
        foreach (['G','H'] as $column) {
            $sheet->getColumnDimension($column)->setWidth(14);
            $sheet->getStyle($column.'5:'.$column.$end)->getNumberFormat()->setFormatCode('0.0%');
        }
        $sheet->getStyle('A3:H4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C8102E']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setSize(15);
        $sheet->getStyle('A2:H2')->getFont()->getColor()->setRGB('667085');
        $sheet->getStyle('A'.$end.':H'.$end)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '174E95']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EAF3FF']],
        ]);
        return [];
    }
}
