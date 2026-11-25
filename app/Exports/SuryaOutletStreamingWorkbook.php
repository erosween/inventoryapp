<?php

namespace App\Exports;

use App\Services\OutletConsignmentQuantities;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

class SuryaOutletStreamingWorkbook
{
    public function write(Collection $digiposRows, Collection $nonDigiposRows, string $path): void
    {
        $writer = new Writer();
        $writer->openToFile($path);

        try {
            $this->writeDigiposSheet($writer, $digiposRows);
            $this->writeNonDigiposSheet($writer, $nonDigiposRows);
        } finally {
            $writer->close();
        }
    }

    private function writeDigiposSheet(Writer $writer, Collection $rows): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Detail Outlet');
        $sheet->setSheetView((new SheetView())->setFreezeRow(2)->setFreezeColumn('H'));
        $sheet->setAutoFilter(new AutoFilter(0, 1, count($this->detailHeadings()) - 1, $rows->count() + 1));
        $sheet->setColumnWidth(16, 1);
        $sheet->setColumnWidth(28, 2);
        $sheet->setColumnWidth(22, 3);
        $sheet->setColumnWidth(24, 4);
        $sheet->setColumnWidth(18, 5);
        $sheet->setColumnWidth(14, 6);
        $sheet->setColumnWidth(20, 7);
        $sheet->setColumnWidthForRange(13, 7, count($this->detailHeadings()) - 1);

        $writer->addRow(Row::fromValues($this->detailHeadings(), $this->headerStyle()));
        $percentageStyle = (new Style())->setFormat('0.0"%"');
        $percentageColumns = [];
        for ($column = 11; $column <= 46; $column += 5) {
            $percentageColumns[$column] = $percentageStyle;
        }

        foreach ($rows as $row) {
            $values = [
                (string) $row->id_outlet,
                $row->nama_outlet,
                $row->kecamatan,
                $row->sf,
                $row->tap,
                $row->pjp,
                $row->kategori_outlet ?? 'Reguler',
            ];

            foreach (['st_sa', 'st_pv', 'cvm', 'super_seru', 'comsak', 'hot_promo', 'byu', 'biometrik'] as $metric) {
                foreach (['fm1', 'm1', 'mtd', 'gap', 'mom_pct'] as $period) {
                    $values[] = $row->{$metric.'_'.$period} ?? 0;
                }
            }

            foreach (array_keys(OutletConsignmentQuantities::COLUMNS) as $column) {
                $values[] = (int) ($row->{$column} ?? 0);
            }

            $writer->addRow(Row::fromValuesWithStyles($values, null, $percentageColumns));
        }
    }

    private function writeNonDigiposSheet(Writer $writer, Collection $rows): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Outlet Non Digipos');
        $sheet->setSheetView((new SheetView())->setFreezeRow(2));
        $sheet->setAutoFilter(new AutoFilter(0, 1, 7, $rows->count() + 1));
        $sheet->setColumnWidth(42, 4);
        $writer->addRow(Row::fromValues($this->nonDigiposHeadings(), $this->headerStyle()));
        $quantityStyle = (new Style())->setFormat('#,##0');

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValuesWithStyles([
                $row->outlet_name,
                $row->owner_name,
                $row->phone_number,
                $row->outlet_address,
                $row->namasf ?: $row->idsf,
                $row->idtap,
                (int) $row->total_sa,
                (int) $row->total_pv,
            ], null, [6 => $quantityStyle, 7 => $quantityStyle]));
        }
    }

    private function detailHeadings(): array
    {
        $headings = ['ID Outlet', 'Nama Outlet', 'Kecamatan', 'SF', 'TAP', 'Hari PJP', 'Kategori Outlet'];
        foreach (['ST SA', 'ST PV', 'CVM', 'Super Seru', 'Comsak', 'Hot Promo', 'Renewal BYU', 'Biometrik'] as $metric) {
            foreach (['FM1', 'M1', 'MTD', 'Gap MoM', '%MoM'] as $period) {
                $headings[] = "{$metric} {$period}";
            }
        }

        foreach (OutletConsignmentQuantities::COLUMNS as $label) {
            $headings[] = 'Titip Jual '.$label;
        }

        return $headings;
    }

    private function nonDigiposHeadings(): array
    {
        return ['Outlet', 'Owner', 'Contact', 'Alamat', 'SF', 'TAP', 'SA', 'PV'];
    }

    private function headerStyle(): Style
    {
        return (new Style())
            ->setFontBold()
            ->setFontColor('FFFFFF')
            ->setBackgroundColor('C8102E')
            ->setCellAlignment('center');
    }
}
