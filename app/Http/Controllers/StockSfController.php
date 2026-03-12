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
         * 1. MASTER DENOM (SF)
         * ======================================
         */
        $denoms = DB::table('stockawalsf as s')
                ->join('denom as d', 'd.iddenom', '=', 's.iddenom')
                ->select('d.iddenom', 'd.denom')
                ->distinct()
                ->orderBy('d.iddenom')
                ->get();

        /**
         * ======================================
         * 2. GROUP DENOM (SAMA SEMUA VIEW)
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
            '28 HARI' => [],
            '30 HARI' => [],
            'VOICE'   => [],
            'LAINNYA' => [],
        ];

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
            } elseif (str_contains($name, '28hari')) {
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
         * 3. BASE QUERY (SF ONLY)
         * ======================================
         */
        $base = DB::table('stockawalsf')
            ->select('idtap', 'namasf', 'iddenom', 'stock');

        if (!$isAllTap) {
            $base->where('idtap', $user->idtap);
        }

        /**
         * ======================================
         * 4. SELECT DINAMIS (PIVOT)
         * ======================================
         */
        $selects = ['idtap', 'namasf'];

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
