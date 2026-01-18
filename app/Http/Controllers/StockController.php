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
        $denoms = DB::table('stockawaltap')
            ->select('iddenom', 'denom')
            ->distinct()
            ->orderBy('iddenom')
            ->get();

        /**
         * ======================================
         * 2. INIT GROUP
         * ======================================
         */
        $groups = [
            'SEGEL'   => [],
            '1 HARI'  => [],
            '2 HARI'  => [],
            '3 HARI'  => [],
            '5 HARI'  => [],
            '7 HARI'  => [],
            '14 HARI' => [],
            '30 HARI' => [],
            'VOICE'   => [],
            'LAINNYA' => [],
        ];

        /**
         * ======================================
         * 3. GROUPING DENOM
         * ======================================
         */
        foreach ($denoms as $d) {
            $name = strtolower($d->denom);

            if (str_contains($name, 'segel')) {
                $groups['SEGEL'][] = $d;
            } elseif (str_contains($name, '1hari')) {
                $groups['1 HARI'][] = $d;
            } elseif (str_contains($name, '2hari')) {
                $groups['2 HARI'][] = $d;
            } elseif (str_contains($name, '3hari')) {
                $groups['3 HARI'][] = $d;
            } elseif (str_contains($name, '5hari')) {
                $groups['5 HARI'][] = $d;
            } elseif (str_contains($name, '7hari')) {
                $groups['7 HARI'][] = $d;
            } elseif (str_contains($name, '14hari')) {
                $groups['14 HARI'][] = $d;
            } elseif (str_contains($name, '30hari')) {
                $groups['30 HARI'][] = $d;
            } elseif (str_contains($name, 'voice')) {
                $groups['VOICE'][] = $d;
            } else {
                $groups['LAINNYA'][] = $d;
            }
        }

        /**
         * ======================================
         * 4. STOCK GUDANG + SF (FILTER TAP)
         * ======================================
         */
        $gudang = DB::table('stockawaltap')
            ->select('idtap', 'iddenom', 'stock');

        $sf = DB::table('stockawalsf')
            ->select('idtap', 'iddenom', 'stock');

        if (!$isAllTap) {
            $gudang->where('idtap', $user->idtap);
            $sf->where('idtap', $user->idtap);
        }

        $base = $gudang->unionAll($sf);

        /**
         * ======================================
         * 5. SELECT DINAMIS
         * ======================================
         */
        $selects = ['idtap'];
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
