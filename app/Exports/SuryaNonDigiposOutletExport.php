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
            $row->visited_at ? \Carbon\Carbon::parse($row->visited_at)->format('d/m/Y H:i') : null,
            (string) $row->id_outlet,
            $row->outlet_name,
            $row->owner_name,
            $row->phone_number,
            $row->outlet_address,
            $row->namasf ?: $row->idsf,
            $row->stock_owner_name ?: ($row->stock_owner_idsf ?: '—'),
            $row->idtap,
            (int) $row->product_count,
            (int) $row->total_qty,
            (float) $row->total_amount,
            $row->product_summary ?: '—',
            $row->sales_status ?: 'BELUM ADA PENJUALAN',
        ];
    }

    public function headings(): array
    {
        return ['Tanggal Visit', 'ID Outlet', 'Nama Outlet', 'Nama Owner', 'Nomor HP', 'Alamat', 'SF Penginput', 'SF Pemilik Stok', 'TAP', 'Jumlah Produk', 'Total Qty', 'Total Belanja', 'Rincian Belanja', 'Status'];
    }

    public function title(): string { return 'Outlet Non Digipos'; }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(1, $this->rows->count() + 1);
        $sheet->freezePane('C2');
        $sheet->setAutoFilter("A1:N{$lastRow}");
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C8102E']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getStyle("L2:L{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getColumnDimension('F')->setWidth(42);
        $sheet->getColumnDimension('M')->setWidth(48);

        return [];
    }
}
