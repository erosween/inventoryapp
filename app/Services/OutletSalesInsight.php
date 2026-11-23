<?php

namespace App\Services;

use App\Support\OutletReportPeriod;
use App\Support\SuryaTransactions;
use Carbon\Carbon;

class OutletSalesInsight
{
    public const GROUPS = [
        'st_sa' => ['label' => 'ST SA', 'threshold' => 5],
        'st_pv' => ['label' => 'ST PV', 'threshold' => 40],
        'cvm' => ['label' => 'CVM', 'threshold' => 5],
        'super_seru' => ['label' => 'Super Seru', 'threshold' => 5],
        'comsak' => ['label' => 'Comsak', 'threshold' => 5],
        'hot_promo' => ['label' => 'Hot Promo', 'threshold' => 5],
        'renewal_byu' => ['label' => 'Renewal BYU', 'threshold' => 5],
        'biometrik' => ['label' => 'Biometrik', 'threshold' => 1],
    ];

    private const TAP_ORDER = [
        'DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING',
        'BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG',
    ];

    public function data($outlets, Carbon $cutoff): array
    {
        $outlets = $outlets->keyBy(fn ($outlet) => (string) $outlet->id_outlet);
        $monthStart = $cutoff->copy()->startOfMonth();
        $previousStart = $monthStart->copy()->subMonth();
        $previousFullEnd = $previousStart->copy()->endOfMonth();
        $previousEnd = OutletReportPeriod::previousEnd($cutoff);
        $ids = $outlets->keys()->all();
        $activity = [];

        $nota = app(OutletNotaSales::class);
        $salesPeriods = [
            'fm1' => $nota->totals($previousStart, $previousFullEnd, $ids, true)->get()->keyBy(fn ($row) => (string) $row->id_outlet),
            'm1' => $nota->totals($previousStart, $previousEnd, $ids, true)->get()->keyBy(fn ($row) => (string) $row->id_outlet),
            'mtd' => $nota->totals($monthStart, $cutoff, $ids)->get()->keyBy(fn ($row) => (string) $row->id_outlet),
        ];
        foreach ($salesPeriods as $period => $rows) {
            foreach ($rows as $id => $row) {
                $activity[(string) $id]['st_sa'][$period] = (int) $row->sa;
                $activity[(string) $id]['st_pv'][$period] = (int) $row->pv;
            }
        }

        $cvmRows = app(OutletCvmReport::class)
            ->outletPeriodsQuery($cutoff, ['cvm', 'super_seru', 'comsak', 'hot_promo', 'byu'])
            ->whereIn('sales.id_outlet', $ids)
            ->get();
        foreach ($cvmRows as $row) {
            foreach (['fm1', 'm1', 'mtd'] as $period) {
                foreach (['cvm', 'super_seru', 'comsak', 'hot_promo'] as $metric) {
                    $activity[(string) $row->id_outlet][$metric][$period] = (int) ($row->{$metric.'_'.$period} ?? 0);
                }
                $activity[(string) $row->id_outlet]['renewal_byu'][$period] = (int) ($row->{'byu_'.$period} ?? 0);
            }
        }

        if ($ids !== []) {
            $biometrikRows = SuryaTransactions::query()
                ->whereIn('id_outlet', $ids)
                ->whereBetween('metric_date', [$monthStart->toDateString(), $cutoff->toDateString()])
                ->orderByDesc('metric_date')->orderByDesc('id')->get()
                ->groupBy(fn ($row) => (string) $row->id_outlet);
            foreach ($biometrikRows as $id => $rows) {
                $bio = $rows->first(function ($row) {
                    $uploaded = array_filter(explode(',', (string) ($row->uploaded_metrics ?? '')));
                    return $uploaded === [] || in_array('bio', $uploaded, true);
                });
                if ($bio === null) continue;
                $activity[(string) $id]['biometrik'] = [
                    'fm1' => (int) ($bio->bio_fm1 ?? 0),
                    'm1' => (int) ($bio->bio_m1 ?? 0),
                    'mtd' => (int) ($bio->bio_m ?? 0),
                ];
            }
        }

        $tapRows = $this->aggregate($outlets, $activity, 'tap');
        $districtRows = $this->aggregate($outlets, $activity, 'kecamatan');
        $grand = $this->sumRows($tapRows, 'Grand Total');
        $clusters = [];
        foreach ([
            'Dumai Bengkalis' => ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING'],
            'Rokan Hilir' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
        ] as $name => $members) {
            $rows = array_values(array_filter($tapRows, fn ($row) => in_array(mb_strtoupper($row['area']), $members, true)));
            if ($rows !== []) $clusters[] = $this->sumRows($rows, $name) + ['cluster' => true];
        }

        return compact('cutoff', 'previousEnd', 'tapRows', 'districtRows', 'grand', 'clusters')
            + ['outletCount' => $outlets->count(), 'groups' => self::GROUPS];
    }

    private function aggregate($outlets, array $activity, string $areaColumn): array
    {
        $groups = [];
        foreach ($outlets as $id => $outlet) {
            $area = trim((string) ($outlet->{$areaColumn} ?? '')) ?: 'Belum dipetakan';
            $key = mb_strtoupper($area);
            $groups[$key] ??= [
                'area' => $area,
                'pjp' => 0,
                'values' => array_fill_keys(array_keys(self::GROUPS), ['fm1' => 0, 'm1' => 0, 'mtd' => 0]),
            ];
            $pjp = trim((string) ($outlet->pjp ?? ''));
            if ($pjp !== '' && mb_strtoupper($pjp) !== 'UNMAPPING') $groups[$key]['pjp']++;
            foreach (self::GROUPS as $metric => $config) {
                foreach (['fm1', 'm1', 'mtd'] as $period) {
                    $amount = (int) ($activity[(string) $id][$metric][$period] ?? 0);
                    if ($amount >= $config['threshold']) $groups[$key]['values'][$metric][$period]++;
                }
            }
        }

        $rows = array_values($groups);
        if ($areaColumn === 'tap') {
            usort($rows, fn ($a, $b) => $this->tapRank($a['area']) <=> $this->tapRank($b['area']) ?: strnatcasecmp($a['area'], $b['area']));
        } else {
            usort($rows, fn ($a, $b) => strnatcasecmp($a['area'], $b['area']));
        }
        return $rows;
    }

    private function tapRank(string $tap): int
    {
        $rank = array_search(mb_strtoupper(trim($tap)), self::TAP_ORDER, true);
        return $rank === false ? count(self::TAP_ORDER) : $rank;
    }

    private function sumRows(array $rows, string $name): array
    {
        $total = [
            'area' => $name,
            'pjp' => 0,
            'values' => array_fill_keys(array_keys(self::GROUPS), ['fm1' => 0, 'm1' => 0, 'mtd' => 0]),
        ];
        foreach ($rows as $row) {
            $total['pjp'] += $row['pjp'];
            foreach (self::GROUPS as $metric => $_) {
                foreach (['fm1', 'm1', 'mtd'] as $period) $total['values'][$metric][$period] += $row['values'][$metric][$period];
            }
        }
        return $total;
    }
}
