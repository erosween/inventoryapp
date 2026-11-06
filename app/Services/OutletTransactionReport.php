<?php
namespace App\Services;

use App\Support\OutletReportPeriod;
use App\Support\SuryaTapScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Read-only annual outlet report; uses exactly the Detail Outlet nota source. */
class OutletTransactionReport
{
    public function data(string $family, Carbon $cutoff, string $tap = '', string $search = ''): array
    {
        $scopes = SuryaTapScope::forUser(auth()->user(), session('idtap'));
        $master = DB::table('appsdumais')->whereNotNull('pjp')->whereRaw("TRIM(pjp) <> ''")
            ->whereRaw("UPPER(TRIM(pjp)) <> 'UNMAPPING'")
            ->when($scopes !== null, fn ($q) => $q->whereIn('tap', $scopes))
            ->orderBy('id_outlet')->get(['id_outlet','nama_outlet','nomor_rs','tap','cluster','kecamatan','sf','pjp'])->unique('id_outlet');
        $taps = $master->pluck('tap')->filter()->unique()->sort()->values();
        $master = $master->filter(function ($o) use ($tap, $search) {
            return ($tap === '' || $o->tap === $tap) && ($search === '' || mb_stripos(implode(' ', (array)$o), $search) !== false);
        });
        $rows = $master->mapWithKeys(fn ($o) => [(string)$o->id_outlet => (array)$o + ['months'=>array_fill(1,12,null),'compare'=>null]]);
        $nota = new OutletNotaSales;
        for ($m = 1; $m <= $cutoff->month; $m++) {
            $start = $cutoff->copy()->startOfYear()->month($m)->startOfMonth();
            $end = $m === $cutoff->month ? $cutoff->copy() : $start->copy()->endOfMonth();
            $totals = $this->totals($nota, $start, $end);
            $rows = $rows->map(function ($row, $id) use ($totals, $family, $m) {
                $row['months'][$m] = (int)($totals[$id][$family] ?? 0); return $row;
            });
        }
        $previousEnd = OutletReportPeriod::previousEnd($cutoff);
        $comparison = $cutoff->month > 1 && $previousEnd->day < $previousEnd->daysInMonth;
        if ($comparison) {
            $totals = $this->totals($nota, $previousEnd->copy()->startOfMonth(), $previousEnd, true);
            $rows = $rows->map(function ($row, $id) use ($totals, $family) {
                $row['compare'] = (int)($totals[$id][$family] ?? 0); return $row;
            });
        }
        $rows = $rows->map(fn ($row) => $this->calculate($row, $cutoff, $comparison))->values();
        $summary = $rows->groupBy('tap')->map(fn ($items, $name) => $this->aggregate($items, $name, $cutoff, $comparison))->sortKeys()->values();
        $clusters = collect();
        foreach (['Dumai Bengkalis'=>['DUMAI','DURI','BENGKALIS','RUPAT','SEI PAKNING'], 'Rokan Hilir'=>['BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG']] as $name=>$members) {
            $items = $rows->whereIn('tap', $members);
            if ($items->isNotEmpty()) $clusters->push($this->aggregate($items, $name, $cutoff, $comparison));
        }
        return compact('rows','summary','clusters','taps','cutoff','previousEnd','comparison') + [
            'grand'=>$this->aggregate($rows, 'GRAND TOTAL', $cutoff, $comparison),
        ];
    }

    private function totals(OutletNotaSales $nota, Carbon $start, Carbon $end, bool $historical = false): array
    {
        // Shared by SA/PV and users. Apply outlet access before exposing any rows.
        // Short TTL bounds freshness; never cache a full rendered page or user access.
        $key = 'outlet-transactions-v1:'.$start->toDateString().':'.$end->toDateString().':'.(int)$historical;
        return Cache::remember($key, 60, fn () => $nota->totals($start, $end, null, $historical)->get()
            ->mapWithKeys(fn ($r) => [(string)$r->id_outlet => ['sa'=>(int)$r->sa,'pv'=>(int)$r->pv]])->all());
    }

    private function calculate(array $row, Carbon $cutoff, bool $comparison): array
    {
        $row['total'] = array_sum($row['months']);
        for ($m=1; $m<=12; $m++) $row['m'.$m] = $row['months'][$m];
        for ($m=2; $m<=12; $m++) {
            $current=$row['months'][$m]; $previous=$comparison && $m===$cutoff->month ? $row['compare'] : $row['months'][$m-1];
            $row['gap_m'.$m]=$current===null || $previous===null ? null : $current-$previous;
            $row['mom_pct_m'.$m]=$current===null || !$previous ? null : round(($current/$previous-1)*100,1);
        }
        return $row;
    }

    private function aggregate(Collection $rows, string $name, Carbon $cutoff, bool $comparison): array
    {
        $row=['tap'=>$name,'months'=>array_fill(1,12,null),'compare'=>$comparison ? $rows->sum('compare') : null];
        for ($m=1; $m<=$cutoff->month; $m++) $row['months'][$m]=$rows->sum(fn ($r) => $r['months'][$m]);
        return $this->calculate($row,$cutoff,$comparison);
    }
}
