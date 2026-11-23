<?php

namespace App\Services;

use App\Support\OutletReportPeriod;
use Carbon\Carbon;

class OutletSalesInsight
{
    public function data($outlets, Carbon $cutoff): array
    {
        $outlets = $outlets->keyBy(fn ($outlet) => (string) $outlet->id_outlet);
        $monthStart = $cutoff->copy()->startOfMonth();
        $previousStart = $monthStart->copy()->subMonth();
        $previousFullEnd = $previousStart->copy()->endOfMonth();
        $previousEnd = OutletReportPeriod::previousEnd($cutoff);
        $ids = $outlets->keys()->all();
        $nota = app(OutletNotaSales::class);
        $periods = [
            'fm1' => $nota->totals($previousStart, $previousFullEnd, $ids, true)->get()->keyBy(fn ($row) => (string) $row->id_outlet),
            'm1' => $nota->totals($previousStart, $previousEnd, $ids, true)->get()->keyBy(fn ($row) => (string) $row->id_outlet),
            'mtd' => $nota->totals($monthStart, $cutoff, $ids)->get()->keyBy(fn ($row) => (string) $row->id_outlet),
        ];

        $tapRows = $this->aggregate($outlets, $periods, 'tap');
        $districtRows = $this->aggregate($outlets, $periods, 'kecamatan');
        $grand = $this->sumRows($tapRows, 'Grand Total');
        $clusters = [];
        foreach ([
            'Dumai Bengkalis' => ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING'],
            'Rokan Hilir' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
        ] as $name => $members) {
            $rows = array_values(array_filter($tapRows, fn ($row) => in_array(strtoupper($row['area']), $members, true)));
            if ($rows !== []) $clusters[] = $this->sumRows($rows, $name) + ['cluster' => true];
        }

        return compact('cutoff', 'previousEnd', 'tapRows', 'districtRows', 'grand', 'clusters')
            + ['outletCount' => $outlets->count()];
    }

    private function aggregate($outlets, array $periods, string $areaColumn): array
    {
        $groups = [];
        foreach ($outlets as $id => $outlet) {
            $area = trim((string) ($outlet->{$areaColumn} ?? '')) ?: 'Belum dipetakan';
            $pjp = trim((string) ($outlet->pjp ?? '')) ?: 'Belum diatur';
            $key = mb_strtoupper($area).'|'.mb_strtoupper($pjp);
            $groups[$key] ??= [
                'area' => $area,
                'pjp' => $pjp,
                'outlet_total' => 0,
                'values' => ['fm1' => 0, 'm1' => 0, 'mtd' => 0],
            ];
            $groups[$key]['outlet_total']++;
            foreach ($periods as $period => $sales) {
                if ((int) ($sales->get((string) $id)?->pv ?? 0) > 0) $groups[$key]['values'][$period]++;
            }
        }

        $rows = array_values($groups);
        usort($rows, fn ($a, $b) => strnatcasecmp($a['area'], $b['area']) ?: strnatcasecmp($a['pjp'], $b['pjp']));
        return $rows;
    }

    private function sumRows(array $rows, string $name): array
    {
        $total = ['area' => $name, 'pjp' => '', 'outlet_total' => 0, 'values' => ['fm1' => 0, 'm1' => 0, 'mtd' => 0]];
        foreach ($rows as $row) {
            $total['outlet_total'] += $row['outlet_total'];
            foreach (['fm1', 'm1', 'mtd'] as $period) $total['values'][$period] += $row['values'][$period];
        }
        return $total;
    }
}
