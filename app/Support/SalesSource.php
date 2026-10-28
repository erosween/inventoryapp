<?php
namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SalesSource
{
    /** The posted stock-out journal is authoritative once an approval has posted. */
    public static function legacy(string $alias = 'keluarsf')
    {
        return DB::table($alias === 'keluarsf' ? 'keluarsf' : 'keluarsf as '.$alias);
    }

    /** Include approved mobile sales only while they have no posted journal entry.
     * Match identity, never mutable qty/date/product: edits must not create a second sale.
     */
    public static function excludePostedMobile($query, string $alias = 'mobile_penjualan'): void
    {
        if (!Schema::hasColumn('keluarsf', 'tambahanket')) return;
        $position = strlen('Mobile Approval ID: ') + 1;
        $cast = DB::getDriverName() === 'sqlite' ? 'INTEGER' : 'UNSIGNED';
        $concat = DB::getDriverName() === 'sqlite'
            ? "('Mobile Approval ID: ' || CAST(SUBSTR(posted_sale.tambahanket,$position) AS $cast))"
            : "CONCAT('Mobile Approval ID: ',CAST(SUBSTR(posted_sale.tambahanket,$position) AS $cast))";
        $query->whereNotIn($alias.'.id', function ($q) use ($position, $cast, $concat) {
            $q->from('keluarsf as posted_sale')
                ->selectRaw("CAST(SUBSTR(posted_sale.tambahanket,$position) AS $cast)")
                ->where('posted_sale.tambahanket', 'like', 'Mobile Approval ID: %')
                ->whereRaw("posted_sale.tambahanket = $concat");
        });
    }

    public static function mobile(string $alias = 'mobile_penjualan')
    {
        $query = DB::table($alias === 'mobile_penjualan' ? 'mobile_penjualan' : 'mobile_penjualan as '.$alias)
            ->where($alias.'.status', 'approved');
        self::excludePostedMobile($query, $alias);
        return $query;
    }
}
