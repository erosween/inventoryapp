<?php
namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SuryaTransactions
{
    public const GROUPS = ['cvm' => ['ss', 'hot_promo', 'cvm', 'insak', 'comsak', 'digital'], 'biometrik' => ['bio']];

    public static function query()
    {
        // During rollout, readers remain compatible until both new tables exist.
        if (!Schema::hasTable('surya_trx_cvm') || !Schema::hasTable('surya_biometrik')) {
            return DB::table('surya_daily_metrics');
        }
        $queries = [];
        foreach (self::GROUPS as $kind => $metrics) {
            $query = DB::table($kind === 'cvm' ? 'surya_trx_cvm' : 'surya_biometrik')
                ->select('id', 'id_outlet', 'metric_date', 'source_updated_at', 'uploaded_metrics', 'created_at', 'updated_at');
            foreach (array_merge(...array_values(self::GROUPS)) as $metric) {
                foreach (['fm1', 'm1', 'm'] as $period) {
                    $column = $metric.'_'.$period;
                    if (in_array($metric, $metrics, true)) $query->addSelect($column);
                    else $query->selectRaw('0 AS '.$column);
                }
            }
            $queries[] = $query;
        }
        return DB::query()->fromSub($queries[0]->unionAll($queries[1]), 'surya_transactions');
    }
}
