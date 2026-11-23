<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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
        $groups = [$area, 'PJP'];
        $periods = ['', ''];
        foreach ($this->insight['groups'] as $group) {
            $groups[] = $group['label'];
            array_push($groups, '', '', '', '', '');
            array_push($periods, 'FM-1', 'M-1', 'MTD', 'MoM', '%MoM', 'Rate');
        }
        return [
            [$this->title().' · Outlet Aktif'],
            ['PJP = jumlah outlet terjadwal; Rate tiap metrik = outlet aktif MTD ÷ total outlet PJP di wilayah.'],
            $groups,
            $periods,
        ];
    }

    public function array(): array
    {
        return array_map(function (array $row): array {
            $values = [$row['area'], $row['pjp']];
            foreach ($this->insight['groups'] as $metric => $_) {
                $fm1 = $row['values'][$metric]['fm1'];
                $m1 = $row['values'][$metric]['m1'];
                $mtd = $row['values'][$metric]['mtd'];
                array_push(
                    $values,
                    $fm1,
                    $m1,
                    $mtd,
                    $mtd - $m1,
                    $m1 > 0 ? ($mtd / $m1) - 1 : null,
                    $row['pjp'] > 0 ? $mtd / $row['pjp'] : null,
                );
            }
            return $values;
        }, $this->rows);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = Coordinate::stringFromColumnIndex(2 + count($this->insight['groups']) * 6);
        $end = 4 + count($this->rows);
        $sheet->mergeCells('A1:'.$lastColumn.'1');
        $sheet->mergeCells('A2:'.$lastColumn.'2');
        $sheet->mergeCells('A3:A4');
        $sheet->mergeCells('B3:B4');
        foreach (array_keys($this->insight['groups']) as $index => $_) {
            $first = Coordinate::stringFromColumnIndex(3 + $index * 6);
            $last = Coordinate::stringFromColumnIndex(8 + $index * 6);
            $sheet->mergeCells($first.'3:'.$last.'3');
            $momPercent = Coordinate::stringFromColumnIndex(7 + $index * 6);
            $rate = Coordinate::stringFromColumnIndex(8 + $index * 6);
            $sheet->getStyle($momPercent.'5:'.$momPercent.$end)->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle($rate.'5:'.$rate.$end)->getNumberFormat()->setFormatCode('0.0%');
        }
        $sheet->freezePane('C5');
        $sheet->setAutoFilter('A4:'.$lastColumn.$end);
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(14);
        for ($column = 3; $column <= 2 + count($this->insight['groups']) * 6; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(13);
        }
        $sheet->getStyle('A3:'.$lastColumn.'4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C8102E']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getStyle('A1:'.$lastColumn.'1')->getFont()->setBold(true)->setSize(15);
        $sheet->getStyle('A2:'.$lastColumn.'2')->getFont()->getColor()->setRGB('667085');
        $sheet->getStyle('A'.$end.':'.$lastColumn.$end)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '174E95']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EAF3FF']],
        ]);
        return [];
    }
}
