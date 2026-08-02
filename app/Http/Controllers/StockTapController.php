<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class StocktapController extends Controller
{
    public function index()
    {
        return redirect()->route('sisastock.index', ['mode' => 'tap']);

        $user = auth()->user();
        $isAllTap = strtoupper($user->idtap) === 'SBP_DUMAI';

        /**
         * ======================================
         * 1. MASTER DENOM & GROUPING
         * ======================================
         */
        $denoms = DB::table('denom')
            ->where('group_name', '!=', 'VOICE')
            ->where(function ($query) {
                $query->whereNull('kategori_inject')
                    ->orWhere('kategori_inject', '!=', 'ROAMAX');
            })
            ->orderBy('iddenom')
            ->get();
        $visibleDenomIds = $denoms->pluck('iddenom')->all();

        $groups = ['VOUCHER FISIK' => [], 'VOUCHER by.U' => []];
        foreach ($denoms as $d) {
            $groupName = $d->group_name;
            $voucherType = strtoupper((string) $d->kategori_inject) === 'BYU'
                ? 'VOUCHER by.U'
                : 'VOUCHER FISIK';
            if (!isset($groups[$voucherType][$groupName])) {
                $groups[$voucherType][$groupName] = [];
            }
            $groups[$voucherType][$groupName][] = $d;
        }

        // Urutkan grup sesuai urutan standar
        $standardOrder = ['SEGEL', '1 HARI', '2 HARI', '3 HARI', '5 HARI', '7 HARI', '14 HARI', '28 HARI', '30 HARI', 'VOICE', 'LAINNYA'];
        $sortDenomsByQuota = function (array &$items) {
            usort($items, function ($a, $b) {
                preg_match('/(\d+(?:[.,]\d+)?)\s*GB/i', $a->denom, $aQuota);
                preg_match('/(\d+(?:[.,]\d+)?)\s*GB/i', $b->denom, $bQuota);

                $aSize = isset($aQuota[1]) ? (float) str_replace(',', '.', $aQuota[1]) : PHP_FLOAT_MAX;
                $bSize = isset($bQuota[1]) ? (float) str_replace(',', '.', $bQuota[1]) : PHP_FLOAT_MAX;

                return $aSize <=> $bSize ?: strnatcasecmp($a->denom, $b->denom);
            });
        };
        foreach ($groups as $voucherType => $validityGroups) {
            $sortedGroups = [];
            foreach ($validityGroups as &$items) {
                $sortDenomsByQuota($items);
            }
            unset($items);
            foreach ($standardOrder as $so) {
                if (isset($validityGroups[$so])) {
                    $sortedGroups[$so] = $validityGroups[$so];
                    unset($validityGroups[$so]);
                }
            }
            foreach ($validityGroups as $name => $items) {
                $sortedGroups[$name] = $items;
            }
            $groups[$voucherType] = $sortedGroups;
        }

        /**
         * ======================================
         * 3. BASE QUERY (GUDANG ONLY)
         * ======================================
         */
        $base = DB::table('stockawaltap')
            ->select('idtap', 'iddenom', 'stock')
            ->whereIn('iddenom', $visibleDenomIds);

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
        $selects = ['idtap', DB::raw('SUM(stock) AS grand_total')];

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
            ->groupBy('idtap')
            ->orderBy('idtap')
            ->get();

        $activeDenomIds = $denoms
            ->filter(fn ($denom) => (int) $data->sum($denom->iddenom) > 0)
            ->pluck('iddenom')
            ->all();
        foreach ($groups as $voucherType => $validityGroups) {
            foreach ($validityGroups as $groupName => $items) {
                $items = array_values(array_filter(
                    $items,
                    fn ($denom) => in_array($denom->iddenom, $activeDenomIds, true)
                ));
                if ($items === []) {
                    unset($groups[$voucherType][$groupName]);
                } else {
                    $groups[$voucherType][$groupName] = $items;
                }
            }
        }
        $data = $data
            ->filter(fn ($row) => (int) $row->grand_total > 0)
            ->values();

        return view('stocktap', compact('data', 'groups'));
    }
}
