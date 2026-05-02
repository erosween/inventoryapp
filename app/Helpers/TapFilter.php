<?php

namespace App\Helpers;

class TapFilter
{
    /**
     * Apply TAP filtering to a query builder instance.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param string $column
     * @return void
     */
    public static function apply($query, $column = 'idtap')
    {
        $idtap = session('idtap');

        if ($idtap === 'CLUSTER_DUMAI') {
            $query->whereIn($column, ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING']);
        } elseif ($idtap === 'CLUSTER_ROHIL') {
            $query->whereIn($column, ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG']);
        } elseif ($idtap && $idtap !== 'SBP_DUMAI') {
            $query->where($column, $idtap);
        }
    }
}
