<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuryaOutletDetailExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function map($row): array
    {
        $values = [
            (string) $row->id_outlet,
            $row->nama_outlet,
            $row->kecamatan,
            $row->sf,
            $row->tap,
            $row->pjp,
        ];

        foreach (['st_sa', 'st_pv', 'cvm', 'super_seru', 'comsak', 'hot_promo', 'biometrik'] as $metric) {
            foreach (['fm1', 'm1', 'mtd', 'gap', 'mom_pct'] as $period) {
                $values[] = $row->{$metric.'_'.$period};
            }
        }

        return $values;
    }

    public function headings(): array
    {
        $headings = ['ID Outlet', 'Nama Outlet', 'Kecamatan', 'SF', 'TAP', 'Hari PJP'];
        foreach (['ST SA', 'ST PV', 'CVM', 'Super Seru', 'Comsak', 'Hot Promo', 'Biometrik'] as $metric) {
            foreach (['FM1', 'M1', 'MTD', 'Gap MoM', '%MoM'] as $period) {
                $headings[] = "{$metric} {$period}";
            }
        }

        return $headings;
    }

    public function title(): string
    {
        return 'Detail Outlet';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(1, $this->rows->count() + 1);
        $sheet->freezePane('G2');
        $sheet->setAutoFilter("A1:AO{$lastRow}");
        $sheet->getStyle('A1:AO1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C8102E']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);
        foreach (['K', 'P', 'U', 'Z', 'AE', 'AJ', 'AO'] as $column) {
            $sheet->getStyle("{$column}2:{$column}{$lastRow}")->getNumberFormat()->setFormatCode('0.0"%"');
        }
        $sheet->getColumnDimension('A')->setWidth(16);
        foreach (['B' => 28, 'C' => 22, 'D' => 24, 'E' => 18, 'F' => 14] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        foreach (range('G', 'Z') as $column) {
            $sheet->getColumnDimension($column)->setWidth(13);
        }
        foreach (['AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO'] as $column) {
            $sheet->getColumnDimension($column)->setWidth(13);
        }

        return [];
    }
}
