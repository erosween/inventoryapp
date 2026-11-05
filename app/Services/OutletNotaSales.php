<?php
namespace App\Services;

use App\Support\SalesSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Read-only outlet reporting source. Never posts inventory or dashboard transactions. */
class OutletNotaSales
{
    public const FROM = '2026-10-01';
    public const TO = '2026-10-15';

    public static function excludeSpecialPeriod($query, string $dateColumn): void
    {
        $query->whereNotBetween($dateColumn, [self::FROM, self::TO.' 23:59:59']);
    }

    public function totals(Carbon $start, Carbon $end, ?array $ids = null, bool $historical = false)
    {
        // Preserve the established uploaded history for other comparison months.
        if ($historical && $start->format('Y-m') !== '2026-10') {
            if (!Schema::hasTable('surya_outlet_sales_history')) return DB::query()->selectRaw('NULL AS id_outlet, 0 AS sa, 0 AS pv')->whereRaw('1=0');
            return DB::table('surya_outlet_sales_history')->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                ->when($ids !== null, fn ($q) => $q->whereIn('id_outlet', $ids))
                ->select('id_outlet')->selectRaw('SUM(sa) AS sa, SUM(pv) AS pv')->groupBy('id_outlet');
        }
        $union = null;
        foreach (['keluarsf', 'mobile_penjualan'] as $table) {
            $q = $table === 'keluarsf' ? SalesSource::legacy('t') : SalesSource::mobile('t');
            $q->leftJoin('denom as d', 'd.iddenom', '=', 't.iddenom')
                ->whereBetween('t.tgl', [$start->toDateString(), $end->copy()->endOfDay()->toDateTimeString()])
                ->when($ids !== null, fn ($q) => $q->whereIn('t.id_outlet', $ids));
            self::excludeSpecialPeriod($q, 't.tgl');
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
}
