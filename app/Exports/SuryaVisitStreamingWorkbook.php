<?php

namespace App\Exports;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

class SuryaVisitStreamingWorkbook
{
    public function write(iterable $visitRows, iterable $salesRows, iterable $adminSalesRows, iterable $consignmentRows, string $path): void
    {
        $writer = new Writer();
        $writer->openToFile($path);

        try {
            $sheets = [
                ['Visit & Koordinat', ['Tanggal Visit', 'Check In', 'Check Out', 'SF Code', 'Nama SF', 'TAP', 'Cluster', 'ID Outlet', 'Nama Outlet', 'Hari PJP', 'Latitude Digipos', 'Longitude Digipos', 'Latitude Visit', 'Longitude Visit', 'Jarak Visit ke Digipos (Meter)', 'Kesesuaian dengan Digipos (≤10 m)', 'Status Approval', 'Alasan Check-out', 'Dominasi Branding', 'Kelayakan Branding'], [14, 12, 12, 16, 26, 18, 22, 18, 34, 14, 17, 17, 17, 17, 25, 34, 20, 28, 22, 22], $visitRows],
                ['Penjualan per Produk', ['Tanggal', 'SF Code Pemilik Outlet', 'Nama SF Pemilik Outlet', 'Helper', 'TAP', 'ID Outlet', 'Nama Outlet', 'Produk', 'Kategori', 'Qty', 'Harga Satuan', 'Total', 'Status Approval'], [14, 23, 28, 26, 18, 18, 34, 34, 18, 12, 18, 18, 22], $salesRows],
                ['Penjualan Admin SF', ['Tanggal', 'TAP', 'SF Code', 'Nama SF', 'ID/Detail Outlet', 'Produk', 'Group Product', 'Sub Produk', 'Validity', 'Qty'], [14, 18, 16, 26, 30, 34, 16, 22, 18, 12], $adminSalesRows],
                ['Titip Jual - Nomor Perdana', ['Tanggal Input / Pindah', 'TAP', 'SF Code', 'Nama SF', 'ID Outlet', 'Nama Outlet', 'Denom Konsinyasi', 'Serial Number', 'Status', 'Catatan', 'Jenis Data', 'Dari Outlet', 'Ke Outlet'], [24, 18, 16, 26, 18, 34, 32, 24, 20, 36, 22, 18, 18], $consignmentRows],
            ];
            $headerStyle = (new Style())->setFontBold()->setFontColor('FFFFFF')->setBackgroundColor('C8102E');

            foreach ($sheets as $index => [$title, $headings, $widths, $rows]) {
                $sheet = $index === 0 ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
                $sheet->setName($title);
                $sheet->setSheetView((new SheetView())->setFreezeRow(2));
                foreach ($widths as $column => $width) $sheet->setColumnWidth($width, $column + 1);
                $writer->addRow(Row::fromValues($headings, $headerStyle));
                $lastRow = 1;

                foreach ($rows as $row) {
                    if ($index === 2) {
                        $values = is_array($row) ? $row : (array) $row;
                        $outletId = trim((string) ($values[4] ?? ''));
                        $validOutlet = preg_match('/^\d+$/', $outletId) === 1
                            || $outletId === ''
                            || preg_match('/^[-–—]+$/u', $outletId) === 1
                            || str_starts_with(strtoupper($outletId), 'NON-DIGIPOS');
                        if (! $validOutlet) $values[4] = 'NON ID / PLAYER';
                        $row = array_slice($values, 0, 10);
                    }

                    $writer->addRow(Row::fromValues(is_array($row) ? array_values($row) : (array) $row));
                    $lastRow++;
                }

                $sheet->setAutoFilter(new AutoFilter(0, 1, count($headings) - 1, $lastRow));
            }
        } finally {
            $writer->close();
        }
    }
}
