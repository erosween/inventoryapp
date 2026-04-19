<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAllTap = $user->idtap === 'SBP_DUMAI';

        /**
         * ======================================
         * 1. AMBIL MASTER DENOM
         * ======================================
         */
        /**
         * ======================================
         * 1. AMBIL MASTER DENOM & GROUPING
         * ======================================
         */
        $denoms = DB::table('denom')->orderBy('iddenom')->get();

        $groups = [];
        foreach ($denoms as $d) {
            $groupName = $d->group_name;
            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [];
            }
            $groups[$groupName][] = $d;
        }

        // Urutkan grup sesuai urutan standar
        $standardOrder = ['SEGEL', '1 HARI', '2 HARI', '3 HARI', '5 HARI', '7 HARI', '14 HARI', '28 HARI', '30 HARI', 'VOICE', 'LAINNYA'];
        $sortedGroups = [];
        foreach ($standardOrder as $so) {
            if (isset($groups[$so])) {
                $sortedGroups[$so] = $groups[$so];
                unset($groups[$so]);
            }
        }
        foreach ($groups as $name => $items) {
            $sortedGroups[$name] = $items;
        }
        $groups = $sortedGroups;

        /**
         * ======================================
         * 2. DATA CALCULATION (EXISTING)
         * ======================================
         */

        /**
         * ======================================
         * 4. STOCK GUDANG + SF (FILTER TAP)
         * ======================================
         */
        $gudang = DB::table('stockawaltap')
            ->select('idtap', 'iddenom', 'stock');

        $sf = DB::table('stockawalsf as sf')
            ->join('idsf', 'sf.idsf', '=', 'idsf.idsf')
            ->select('idsf.idtap', 'sf.iddenom', 'sf.stock as stock');

        $idtap = session('idtap') ?? $user->idtap;
        $applyFilter = function($q, $col = 'idtap') use ($idtap) {
            if ($idtap === 'CLUSTER_DUMAI') {
                $q->whereIn($col, ['DUMAI','BENGKALIS','DURI','RUPAT','SEI PAKNING']);
            } elseif ($idtap === 'CLUSTER_ROHIL') {
                $q->whereIn($col, ['BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG']);
            } elseif ($idtap !== 'SBP_DUMAI') {
                $q->where($col, $idtap);
            }
        };

        $applyFilter($gudang);
        $applyFilter($sf);

        $base = $gudang->unionAll($sf);

        /**
         * ======================================
         * 5. SELECT DINAMIS
         * ======================================
         */
        $selects = ['idtap', DB::raw('SUM(stock) AS grand_total')];
        foreach ($denoms as $d) {
            $selects[] = DB::raw(
                'SUM(CASE WHEN iddenom = "'.$d->iddenom.'" THEN stock ELSE 0 END) AS '.$d->iddenom
            );
        }

        /**
         * ======================================
         * 6. QUERY FINAL
         * ======================================
         */
        $data = DB::query()
            ->fromSub($base, 'x')
            ->select($selects)
            ->groupBy('idtap')
            ->orderBy('idtap')
            ->get();

        return view('stock', compact('data', 'groups'));
    }
}
