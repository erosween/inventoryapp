<?php

namespace App\Services;

use App\Support\DenomGrouping;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class OutletConsignmentQuantities
{
    public const COLUMNS = [
        'titip_sa_simpati' => 'SA SIMPATI',
        'titip_sa_byu' => 'SA BYU',
        'titip_sa_all' => 'SA ALL',
        'titip_pv_reg' => 'PV REG',
        'titip_pv_byu' => 'PV BYU',
        'titip_pv_all' => 'PV ALL',
    ];

    public function query(): ?Builder
    {
        if (!Schema::hasColumn('surya_sa_consignment_numbers', 'iddenom')) {
            return null;
        }

        $query = DB::table('surya_sa_consignment_numbers as consignment')
            ->whereIn('consignment.status', ['consigned', 'approved'])
            ->select('consignment.id_outlet')
            ->groupBy('consignment.id_outlet');

        foreach (array_keys(self::COLUMNS) as $column) {
            $filter = substr($column, strlen('titip_'));
            $denoms = DenomGrouping::filterDenoms(DB::table('denom')->select('iddenom'), $filter);
            $matches = 'consignment.iddenom IN ('.$denoms->toSql().')';
            // Entries predating denom capture came from the original SA-only form.
            if ($filter === 'sa_all') {
                $matches .= " OR consignment.iddenom IS NULL OR consignment.iddenom = ''";
            }
            $query->selectRaw("COUNT(DISTINCT CASE WHEN $matches THEN consignment.serial_number END) AS $column", $denoms->getBindings());
        }

        return $query;
    }
}
