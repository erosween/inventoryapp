<?php
namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Schema;

class SfVisibility
{
    /** Web selection/display only: never filter transaction history or mobile login. */
    public static function visible(Builder $query, string $alias = 'idsf'): Builder
    {
        if (Schema::hasColumn('idsf', 'is_hidden')) {
            $query->where($alias.'.is_hidden', false);
        }
        return $query;
    }
}
