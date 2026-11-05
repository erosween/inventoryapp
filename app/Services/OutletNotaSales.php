<?php
namespace App\Services;

use App\Support\SalesSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Read-only outlet reporting source. Never posts inventory or dashboard transactions. */
class OutletNotaSales
{
    public const FROM = '2026-01-01';
    public const TO = '2026-10-15';
    private ?array $availableMonths = null;

    public function excludeSpecialPeriod($query, string $dateColumn): void
    {
        $query->whereNotBetween($dateColumn, ['2026-10-01', self::TO.' 23:59:59']);
        foreach ($this->historyMonths() as $month) {
            $start = Carbon::parse($month.'-01');
            $query->whereNotBetween($dateColumn, [$start->toDateString(), $start->endOfMonth()->endOfDay()->toDateTimeString()]);
        }
    }

    private function historyMonths(): array
    {
        if ($this->availableMonths !== null) return $this->availableMonths;
        $months = [];
        foreach (['surya_outlet_nota_backfill', 'surya_outlet_sales_history'] as $table) {
            if (!Schema::hasTable($table)) continue;
            $months = array_merge($months, DB::table($table)->whereBetween('transaction_date', [self::FROM, '2026-09-30'])
                ->selectRaw('SUBSTR(transaction_date,1,7) AS month')->distinct()->pluck('month')->all());
        }
        return $this->availableMonths = array_unique($months);
    }

    public function totals(Carbon $start, Carbon $end, ?array $ids = null, bool $historical = false)
    {
        if (in_array($start->format('Y-m'), $this->historyMonths(), true)
            || ($historical && $start->format('Y-m') !== '2026-10')) {
            return DB::query()->fromSub($this->historyRows($start, $end, $ids), 'history')
                ->select('id_outlet')->selectRaw('SUM(sa) AS sa, SUM(pv) AS pv')->groupBy('id_outlet');
        }
        $union = null;
        foreach (['keluarsf', 'mobile_penjualan'] as $table) {
            $q = $table === 'keluarsf' ? SalesSource::legacy('t') : SalesSource::mobile('t');
            $q->leftJoin('denom as d', 'd.iddenom', '=', 't.iddenom')
                ->whereBetween('t.tgl', [$start->toDateString(), $end->copy()->endOfDay()->toDateTimeString()])
                ->when($ids !== null, fn ($q) => $q->whereIn('t.id_outlet', $ids));
            $this->excludeSpecialPeriod($q, 't.tgl');
            $q->select('t.id_outlet')->selectRaw("CASE WHEN UPPER(COALESCE(d.product_group,'PV')) IN ('SA','SP') THEN t.qty ELSE 0 END AS sa, CASE WHEN UPPER(COALESCE(d.product_group,'PV'))='PV' THEN t.qty ELSE 0 END AS pv");
            $union = $union ? $union->unionAll($q) : $q;
        }
        if (Schema::hasTable('surya_outlet_nota_backfill')) {
            $backfill = DB::table('surya_outlet_nota_backfill')->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                ->whereBetween('transaction_date', [self::FROM, self::TO])
                ->when($ids !== null, fn ($q) => $q->whereIn('id_outlet', $ids))->select('id_outlet', 'sa', 'pv');
            $union->unionAll($backfill);
        }
        return DB::query()->fromSub($union, 'nota')->select('id_outlet')->selectRaw('SUM(sa) AS sa, SUM(pv) AS pv')->groupBy('id_outlet');
    }

    /** A new month snapshot replaces old uploaded history for the whole month, including omitted outlets. */
    public function historyRows(Carbon $start, Carbon $end, ?array $ids = null)
    {
        $monthStart = $start->copy()->startOfMonth()->toDateString();
        $monthEnd = $start->copy()->endOfMonth()->toDateString();
        $hasSnapshot = Schema::hasTable('surya_outlet_nota_backfill') && DB::table('surya_outlet_nota_backfill')
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])->exists();
        $table = $hasSnapshot || $start->format('Y-m') === '2026-10' ? 'surya_outlet_nota_backfill' : 'surya_outlet_sales_history';
        if (!Schema::hasTable($table)) return DB::query()->selectRaw('NULL AS id_outlet, 0 AS sa, 0 AS pv')->whereRaw('1=0');
        return DB::table($table)->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->when($ids !== null, fn ($q) => $q->whereIn('id_outlet', $ids))->select('id_outlet', 'sa', 'pv');
    }
}
