<?php
namespace App\Support;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SalesSource
{
    /** Suppress only proven approval copies when legacy and mobile are combined. */
    public static function excludeApprovalCopies($query, string $alias = 'keluarsf'): void
    {
        if (!Schema::hasColumn('keluarsf','tambahanket')) return;
        $position = strlen('Mobile Approval ID: ') + 1;
        $cast = DB::getDriverName() === 'sqlite' ? 'INTEGER' : 'UNSIGNED';
        $concat = DB::getDriverName() === 'sqlite'
            ? "('Mobile Approval ID: ' || sale_origin.id)"
            : "CONCAT('Mobile Approval ID: ',sale_origin.id)";
        $query->whereNotExists(function ($q) use ($alias,$position,$cast,$concat) {
            $q->selectRaw('1')->from('mobile_penjualan as sale_origin')
                ->whereRaw("sale_origin.id = CAST(SUBSTR($alias.tambahanket,$position) AS $cast)")
                ->whereRaw("$alias.tambahanket = $concat")
                ->where('sale_origin.status','approved')
                ->whereColumn('sale_origin.qty',$alias.'.qty')
                ->whereColumn('sale_origin.iddenom',$alias.'.iddenom')
                ->whereColumn('sale_origin.idtap',$alias.'.idtap')
                ->whereColumn('sale_origin.tgl',$alias.'.tgl');
        });
    }
    public static function legacy(string $alias = 'keluarsf')
    {
        $q=DB::table($alias==='keluarsf'?'keluarsf':'keluarsf as '.$alias);
        self::excludeApprovalCopies($q,$alias);
        return $q;
    }
}
