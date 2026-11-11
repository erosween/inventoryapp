<?php

namespace App\Services;

use App\Support\OutletReportPeriod;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OutletCvmReport
{
    // Rekap TAP memakai profil outlet yang sudah dibatasi oleh controller. Cache mengikuti kueri
    // profil, cutoff, dan revisi upload.
    public function summary(Builder $profiles, Carbon $cutoff): array
    {
        $previousEnd = OutletReportPeriod::previousEnd($cutoff);
        $comparison = $cutoff->month > 1 && $previousEnd->day < $previousEnd->daysInMonth;

        $outletTaps = (clone $profiles)->selectRaw('id_outlet, MIN(tap) AS tap')->groupBy('id_outlet');
        $tapQuery = $this->monthlyQuery($cutoff, $previousEnd, $comparison)
            ->joinSub($outletTaps, 'profile', function ($join) use ($profiles) {
                if ($profiles->getConnection()->getDriverName() === 'sqlite') {
                    $join->on(DB::raw('CAST(profile.id_outlet AS TEXT)'), '=', 'sales.id_outlet');
                    return;
                }

                // Numeric lookup uses the native profile ID key. Keep the exact text
                // match as well so uploaded IDs with a different spelling stay unmatched.
                $join->on('profile.id_outlet', '=', DB::raw('CAST(sales.id_outlet AS UNSIGNED)'))
                    ->on(DB::raw('CAST(profile.id_outlet AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci'), '=', 'sales.id_outlet');
            })
            ->addSelect('profile.tap')
            ->groupBy('profile.tap');

        $key = 'surya-cvm-summary:'.hash('sha256', serialize([
            $tapQuery->toSql(), $tapQuery->getBindings(),
            Cache::get('surya-cvm-upload-revision'),
        ]));
        $tapMonths = Cache::remember($key, 60, fn () => $tapQuery->get());

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

        return compact('summary', 'clusters', 'grand', 'previousEnd', 'comparison');
    }

    public function details(Builder|Collection $outletIds, Carbon $cutoff, ?array $metrics = null): Collection
    {
        if ($outletIds instanceof Collection && $outletIds->isEmpty()) {
            return collect();
        }

        $previousEnd = OutletReportPeriod::previousEnd($cutoff);
        $comparison = $cutoff->month > 1 && $previousEnd->day < $previousEnd->daysInMonth;
        $ids = $outletIds instanceof Builder
            ? (clone $outletIds)->selectRaw($this->profileId($outletIds))
            : $outletIds->map(fn ($id) => (string) $id);

        return $this->monthlyQuery($cutoff, $previousEnd, $comparison, $metrics)
            ->whereIn('sales.id_outlet', $ids)
            ->selectRaw('sales.id_outlet')
            ->groupBy('sales.id_outlet')
            ->get()->groupBy('id_outlet')
            ->map(fn (Collection $rows) => $this->series($rows, $cutoff, $comparison, $metrics));
    }

    private function profileId(Builder $profiles): string
    {
        // Production profiles use BIGINT, while uploaded IDs are stored as text.
        return $profiles->getConnection()->getDriverName() === 'sqlite'
            ? 'CAST(id_outlet AS TEXT)'
            : 'CAST(id_outlet AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci';
    }

    private function monthlyQuery(Carbon $cutoff, Carbon $previousEnd, bool $comparison, ?array $metrics = null): Builder
    {
        $month = 'SUBSTR(sales.transaction_date, 6, 2)';
        $query = DB::table('surya_outlet_cvm as sales')
            ->whereBetween('sales.transaction_date', [$cutoff->copy()->startOfYear()->toDateString(), $cutoff->toDateString()])
            ->selectRaw("$month AS month_number")
            ->groupByRaw($month);

        foreach ($metrics ?? array_keys(OutletCvmImport::METRICS) as $metric) {
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

    private function series(Collection $rows, Carbon $cutoff, bool $comparison, ?array $metrics = null): array
    {
        $byMonth = $rows->groupBy(fn (object $row) => (int) $row->month_number);
        $series = [];
        foreach ($metrics ?? array_keys(OutletCvmImport::METRICS) as $metric) {
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
