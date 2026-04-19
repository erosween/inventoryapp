<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class StockSfController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAllTap = strtoupper($user->idtap) === 'SBP_DUMAI';

        /**
         * ======================================
         * 1. MASTER DENOM & GROUPING
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
         * 3. BASE QUERY (SF ONLY)
         * ======================================
         */
        $base = DB::table('stockawalsf as sf')
            ->join('idsf', 'sf.idsf', '=', 'idsf.idsf')
            ->select('idsf.idtap', 'idsf.namasf', 'sf.iddenom', 'sf.stock as stock');

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

        $applyFilter($base);

        /**
         * ======================================
         * 4. SELECT DINAMIS (PIVOT)
         * ======================================
         */
        $selects = ['idtap', 'namasf', DB::raw('SUM(stock) AS grand_total')];

        foreach ($denoms as $d) {
            $selects[] = DB::raw(
                'SUM(CASE WHEN iddenom = "'.$d->iddenom.'" THEN stock ELSE 0 END) AS '.$d->iddenom
            );
        }

        /**
         * ======================================
         * 5. QUERY FINAL
         * ======================================
         */
        $data = DB::query()
            ->fromSub($base, 'x')
            ->select($selects)
            ->groupBy('idtap', 'namasf')
            ->orderBy('idtap')
            ->orderBy('namasf')
            ->get();

        return view('stocksf', compact('data', 'groups'));
    }
}
