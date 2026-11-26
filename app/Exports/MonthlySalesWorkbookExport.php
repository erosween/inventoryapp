<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonthlySalesWorkbookExport implements WithMultipleSheets
{
    public function __construct(private readonly array $data)
    {
    }

    public function sheets(): array
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        if (($this->data['salesPeriod'] ?? 'monthly') === 'annual') {
            $years = range(2024, (int) date('Y'));
            $rows = [];
            foreach ($this->data['annualSales'] ?? [] as $tap => $values) {
                $rows[] = array_merge([$tap], array_map(fn ($year) => (int) ($values[$year] ?? 0), $years));
            }

            return [new MonthlySalesArraySheet('Penjualan Tahunan', array_merge([['TAP', ...$years]], $rows))];
        }

        $momSelectedDate = $this->data['momSelectedDate'] ?? null;
        $previousMtdMonth = $momSelectedDate?->copy()->subMonthNoOverflow();
        $showPreviousMtdComparison = $previousMtdMonth
            && (int) ($this->data['salesYear'] ?? 0) === $momSelectedDate->year
            && $previousMtdMonth->month < $momSelectedDate->month;
        $previousMtdMonthNumber = $showPreviousMtdComparison ? $previousMtdMonth->month : null;
        $previousMtdMonthLabel = $showPreviousMtdComparison ? $months[$previousMtdMonthNumber - 1] : null;

        $headers = ['NAMA TAP'];
        foreach ($months as $index => $month) array_push(
            $headers,
            $month,
            $showPreviousMtdComparison && $index + 1 === $previousMtdMonthNumber ? $previousMtdMonthLabel.'-MoM Qty' : 'MoM %'
        );
        $tapRows = [];
        foreach ($this->data['matrixSales'] ?? [] as $tap => $values) {
            $line = [$tap];
            for ($month = 1; $month <= 12; $month++) {
                $current = (int) ($values[$month] ?? 0);
                $line[] = $current;
                if ($showPreviousMtdComparison && $month === $previousMtdMonthNumber) {
                    $comparison = $this->data['previousMtdComparisonByTap'][$tap] ?? ['current' => 0, 'previous' => 0];
                    $line[] = (int) $comparison['current'];
                } else {
                    $previous = $month === 1
                        ? (int) (($this->data['prevDecSales'][$tap] ?? 0))
                        : (int) ($values[$month - 1] ?? 0);
                    $line[] = $previous > 0 ? ($current / $previous) - 1 : null;
                }
            }
            $tapRows[] = $line;
        }

        $sfHeaders = ['TAP', 'Nama Petugas'];
        foreach ($months as $index => $month) {
            $sfHeaders[] = $month;
            if ($showPreviousMtdComparison && $index + 1 === $previousMtdMonthNumber) {
                $sfHeaders[] = $previousMtdMonthLabel.'-MoM Qty';
            }
        }
        $sfHeaders[] = 'TOTAL';
        $sfRows = [];
        foreach ($this->data['sfByTap'] ?? [] as $tap => $staff) {
            foreach ($staff as $sf) {
                $ids = $sf->idsfs ?? [$sf->idsf];
                $line = [$tap, $sf->namasf];
                $total = 0;
                $mtdCurrent = 0;
                foreach (range(1, 12) as $month) {
                    $value = 0;
                    foreach ($ids as $id) $value += (int) ($this->data['matrixSalesSf'][$tap][$id][$month] ?? 0);
                    $line[] = $value;
                    $total += $value;
                    if ($showPreviousMtdComparison && $month === $previousMtdMonthNumber) {
                        foreach ($ids as $id) {
                            $comparison = $this->data['previousMtdComparisonBySf'][$tap][$id] ?? ['current' => 0, 'previous' => 0];
                            $mtdCurrent += (int) $comparison['current'];
                        }
                        $line[] = $mtdCurrent;
                    }
                }
                $line[] = $total;
                $sfRows[] = $line;
            }
        }

        return [
            new MonthlySalesArraySheet('Rekap TAP', array_merge([$headers], $tapRows)),
            new MonthlySalesArraySheet('Rincian Petugas', array_merge([$sfHeaders], $sfRows)),
        ];
    }
}
