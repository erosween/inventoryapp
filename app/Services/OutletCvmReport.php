<?php

namespace App\Services;

use App\Support\OutletReportPeriod;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OutletCvmReport
{
    public function data(Builder $profiles, Collection $outletIds, Carbon $cutoff): array
    {
        $previousEnd = OutletReportPeriod::previousEnd($cutoff);
        $comparison = $cutoff->month > 1 && $previousEnd->day < $previousEnd->daysInMonth;

        $details = $outletIds->isEmpty() ? collect() : $this->monthlyQuery($cutoff, $previousEnd, $comparison)
            ->whereIn('sales.id_outlet', $outletIds->map(fn ($id) => (string) $id))
            ->selectRaw('sales.id_outlet')
            ->groupBy('sales.id_outlet')
            ->get()->groupBy('id_outlet')
            ->map(fn (Collection $rows) => $this->series($rows, $cutoff, $comparison));

        // Production profiles use BIGINT, while uploaded IDs are stored as text.
        $profileId = $profiles->getConnection()->getDriverName() === 'sqlite'
            ? 'CAST(id_outlet AS TEXT)'
            : 'CAST(id_outlet AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci';
        $outletTaps = (clone $profiles)->selectRaw("$profileId AS id_outlet, MIN(tap) AS tap")
            ->groupBy('appsdumais.id_outlet');
        $tapMonths = $this->monthlyQuery($cutoff, $previousEnd, $comparison)
            ->joinSub($outletTaps, 'profile', 'profile.id_outlet', '=', 'sales.id_outlet')
            ->selectRaw('profile.tap')
            ->groupBy('profile.tap')->get();

        $summary = $tapMonths->groupBy('tap')->sortKeys()->map(
            fn (Collection $rows) => $this->series($rows, $cutoff, $comparison)
        );
        $clusters = collect([
            'Dumai Bengkalis' => ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING'],
            'Rokan Hilir' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
        ])->map(fn (array $taps) => $tapMonths->whereIn('tap', $taps))
            ->filter(fn (Collection $rows) => $rows->isNotEmpty())
            ->map(fn (Collection $rows) => $this->series($rows, $cutoff, $comparison));
        $grand = $this->series($tapMonths, $cutoff, $comparison);

        return compact('details', 'summary', 'clusters', 'grand', 'previousEnd', 'comparison');
    }

    private function monthlyQuery(Carbon $cutoff, Carbon $previousEnd, bool $comparison): Builder
    {
        $month = 'SUBSTR(sales.transaction_date, 6, 2)';
        $query = DB::table('surya_outlet_cvm as sales')
            ->whereBetween('sales.transaction_date', [$cutoff->copy()->startOfYear()->toDateString(), $cutoff->toDateString()])
            ->selectRaw("$month AS month_number")
            ->groupByRaw($month);

        foreach (array_keys(OutletCvmImport::METRICS) as $metric) {
            $query->selectRaw("SUM(sales.{$metric}) AS {$metric}");
            if ($comparison) {
                $query->selectRaw(
                    "SUM(CASE WHEN sales.transaction_date <= ? THEN sales.{$metric} END) AS compare_{$metric}",
                    [$previousEnd->toDateString()]
                );
            }
        }

        return $query;
    }

    private function series(Collection $rows, Carbon $cutoff, bool $comparison): array
    {
        $byMonth = $rows->groupBy(fn (object $row) => (int) $row->month_number);
        $series = [];
        foreach (array_keys(OutletCvmImport::METRICS) as $metric) {
            $months = $byMonth->map(fn (Collection $rows) => $this->sum($rows, $metric));
            $compare = $comparison
                ? $this->sum($byMonth->get($cutoff->month - 1, collect()), 'compare_'.$metric)
                : null;
            $values = [
                'compare' => $compare,
                'total' => $this->sum($rows, $metric),
            ];
            for ($month = 1; $month <= $cutoff->month; $month++) {
                $current = $months->get($month);
                $values['m'.$month] = $current;
                if ($month > 1) {
                    $previous = $comparison && $month === $cutoff->month ? $compare : $months->get($month - 1);
                    $values['gap_m'.$month] = $current === null || $previous === null ? null : $current - $previous;
                    $values['mom_pct_m'.$month] = $current === null || !$previous ? null : round(($current / $previous - 1) * 100, 1);
                }
            }
            $series[$metric] = $values;
        }

        return $series;
    }

    private function sum(Collection $rows, string $metric): ?int
    {
        $values = $rows->pluck($metric)->whereNotNull();

        return $values->isEmpty() ? null : (int) $values->sum();
    }
}
