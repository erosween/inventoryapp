<?php

namespace App\Support;

class StockProduct
{
    public static function isSa(object $denom): bool
    {
        if (in_array($denom->product ?? null, ['PV', 'SA'], true)) return $denom->product === 'SA';
        $id = strtoupper(trim((string) $denom->iddenom));
        if (in_array($id, ['SP6', 'SP7'], true)) return true;
        if (in_array($id, ['SEGEL', 'V16', 'V33'], true)) return false;

        return strtoupper(trim((string) $denom->kategori_inject)) === 'SA'
            || strtoupper(trim((string) $denom->group_name)) === 'SA';
    }

    public static function isSealed(object $denom): bool
    {
        if (in_array($denom->stock_form ?? null, ['SEGEL', 'INJECT'], true)) return $denom->stock_form === 'SEGEL';
        return in_array(strtoupper(trim((string) $denom->iddenom)), ['SEGEL', 'V16', 'V33', 'SP6', 'SP7'], true)
            || strtoupper(trim((string) $denom->group_name)) === 'SEGEL';
    }

    public static function canonical($denoms)
    {
        // A stock row is keyed by iddenom. Conflicting master rows must never
        // make that same balance appear in multiple columns or product totals.
        return $denoms->sortBy(fn ($d) => strtoupper((string) $d->iddenom).'|'
            .(strtoupper((string) $d->kategori_inject) === 'SA' ? '0' : '1').'|'
            .strtoupper((string) $d->group_name).'|'.strtoupper((string) $d->denom))
            ->unique(fn ($d) => strtoupper(trim((string) $d->iddenom)))
            ->map(function ($denom) {
                $row = clone $denom;
                if (self::isSa($row)) $row->kategori_inject = 'SA';
                elseif (strtoupper((string) $row->iddenom) === 'V33') $row->kategori_inject = 'BYU';
                return $row;
            })->values();
    }
}
