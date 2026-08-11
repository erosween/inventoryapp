<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitaDumaiController extends Controller
{
    public function index()
    {
        $showLeaderDashboard = session()->has('monita_leader_auth');

        if (!$showLeaderDashboard) {
            return view('monitadumai.login');
        }

        $dashboard = $this->buildLeaderDashboard();

        return view('monitadumai.index', compact('dashboard', 'showLeaderDashboard'));
    }

    public function showLeaderLogin()
    {
        return redirect('/monitadumai');
    }

    public function leaderLogin(Request $request)
    {
        $request->validate([
            'access_code' => ['required', 'string', 'max:80'],
        ], [
            'access_code.required' => 'Kode akses wajib diisi.',
        ]);

        $expectedCode = (string) env('MONITA_ACCESS_KEY', 'msp123');

        if (!hash_equals($expectedCode, (string) $request->input('access_code'))) {
            return back()
                ->withInput()
                ->with('error', 'Kode akses Monita belum sesuai.');
        }

        $request->session()->put('monita_leader_auth', true);
        $request->session()->put('monita_leader_login_at', now()->toDateTimeString());

        return redirect('/monitadumai')->with('success', 'Dashboard leader Monita aktif.');
    }

    public function leaderLogout(Request $request)
    {
        $request->session()->forget(['monita_leader_auth', 'monita_leader_login_at']);

        return redirect('/monitadumai')->with('success', 'Akses dashboard leader ditutup.');
    }

    private function buildLeaderDashboard(): array
    {
        $areaTaps = [
            'Kota Dumai' => ['DUMAI'],
            'Kabupaten Rokan Hilir' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
            'Kabupaten Bengkalis' => ['BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING'],
        ];

        $tapRows = collect($areaTaps)
            ->flatten()
            ->unique()
            ->map(fn ($tap) => $this->aggregateTapPerformance($tap))
            ->filter(fn ($tap) => $tap['outlets'] > 0)
            ->values();

        $areas = collect($areaTaps)->map(function ($taps, $name) use ($tapRows) {
            $items = $tapRows->whereIn('tap', $taps)->values();
            $current = $items->sum('current');
            $previous = $items->sum('previous');
            $outlets = $items->sum('outlets');
            $productive = $items->sum('productive_outlets');
            $mom = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
            $productivity = $outlets > 0 ? ($productive / $outlets) * 100 : 0;

            return [
                'name' => $name,
                'taps' => $items->pluck('tap')->all(),
                'outlets' => $outlets,
                'productive_outlets' => $productive,
                'productivity' => round($productivity, 1),
                'st_sa' => $items->sum('st_sa'),
                'st_pv' => $items->sum('st_pv'),
                'trx_m' => $items->sum('trx_m'),
                'trx_cvm' => $items->sum('trx_cvm'),
                'current' => $current,
                'previous' => $previous,
                'mom' => round($mom, 1),
                'latitude' => $items->whereNotNull('latitude')->avg('latitude'),
                'longitude' => $items->whereNotNull('longitude')->avg('longitude'),
                'risk' => $this->areaRiskLabel($mom, $productivity),
            ];
        })->values();

        $totalCurrent = $tapRows->sum('current');
        $totalPrevious = $tapRows->sum('previous');
        $totalOutlets = $tapRows->sum('outlets');
        $totalProductive = $tapRows->sum('productive_outlets');
        $totalMom = $totalPrevious > 0 ? (($totalCurrent - $totalPrevious) / $totalPrevious) * 100 : 0;
        $totalProductivity = $totalOutlets > 0 ? ($totalProductive / $totalOutlets) * 100 : 0;

        $riskTaps = $tapRows
            ->sortBy(function ($tap) {
                return [$tap['mom'], $tap['productivity']];
            })
            ->take(5)
            ->values();

        $growthTaps = $tapRows
            ->sortByDesc('mom')
            ->take(4)
            ->values();

        $leaderActions = $this->leaderActions($riskTaps, $areas);
        $outletPoints = $this->leaderOutletPoints($areaTaps);
        $activeThresholds = [
            'sa' => 5,
            'pv' => 40,
            'cvm' => 11,
        ];
        $clusterCoverage = $this->leaderClusterCoverage($activeThresholds);
        $coveragePoints = $this->leaderCoveragePoints();
        $competitionPoints = $this->leaderCompetitionPoints();
        $monitoringUpdateDate = $this->leaderMonitoringUpdateDate();
        $hotOutletCount = collect($outletPoints)->where('status', 'hot')->count();
        $coldOutletCount = collect($outletPoints)->where('status', 'cold')->count();
        $unmappedOutletCount = collect($outletPoints)->where('status', 'unmapped')->count();
        $pvMonitoring = $this->buildPvMonitoring();

        return [
            'summary' => [
                'outlets' => $totalOutlets,
                'productive_outlets' => $totalProductive,
                'productivity' => round($totalProductivity, 1),
                'current' => $totalCurrent,
                'previous' => $totalPrevious,
                'mom' => round($totalMom, 1),
                'st_sa' => $tapRows->sum('st_sa'),
                'st_pv' => $tapRows->sum('st_pv'),
                'trx_m' => $tapRows->sum('trx_m'),
                'trx_cvm' => $tapRows->sum('trx_cvm'),
                'mapped_outlets' => count($outletPoints),
                'hot_outlets' => $hotOutletCount,
                'cold_outlets' => $coldOutletCount,
                'unmapped_outlets' => $unmappedOutletCount,
            ],
            'areas' => $areas->all(),
            'tapRows' => $tapRows->all(),
            'riskTaps' => $riskTaps->all(),
            'growthTaps' => $growthTaps->all(),
            'leaderActions' => $leaderActions,
            'outletPoints' => $outletPoints,
            'clusterCoverage' => $clusterCoverage,
            'coveragePoints' => $coveragePoints,
            'competitionPoints' => $competitionPoints,
            'monitoringUpdateDate' => $monitoringUpdateDate,
            'pvMonitoring' => $pvMonitoring,
        ];
    }

    private function buildPvMonitoring(): array
    {
        $now = now();
        $mtdStart = $now->copy()->startOfMonth();
        $mtdEnd = $now->copy()->endOfDay();
        $m1Start = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $m1End = $m1Start->copy()->addDays(min($now->day, $m1Start->daysInMonth) - 1)->endOfDay();

        $rows = DB::table('keluarsf as k')
            ->join('denom as d', 'd.iddenom', '=', 'k.iddenom')
            ->join('idsf as s', 's.idsf', '=', 'k.idsf')
            ->whereBetween('k.tgl', [$m1Start->toDateTimeString(), $mtdEnd->toDateTimeString()])
            ->whereIn(DB::raw("UPPER(COALESCE(d.kategori_inject, 'SEGEL'))"), ['SEGEL', 'BYU'])
            ->whereNotNull('d.group_name')
            ->select('k.idtap', 'k.idsf', 's.namasf', 'd.group_name')
            ->selectRaw("CASE WHEN UPPER(COALESCE(d.kategori_inject, 'SEGEL')) = 'BYU' THEN 'byu' ELSE 'reguler' END as category")
            ->selectRaw('SUM(CASE WHEN k.tgl BETWEEN ? AND ? THEN k.qty ELSE 0 END) as m1', [$m1Start, $m1End])
            ->selectRaw('SUM(CASE WHEN k.tgl BETWEEN ? AND ? THEN k.qty ELSE 0 END) as mtd', [$mtdStart, $mtdEnd])
            ->groupBy('k.idtap', 'k.idsf', 's.namasf', 'd.group_name', 'category')
            ->get();

        $validities = $rows->pluck('group_name')->unique()->sortBy(function ($validity) {
            preg_match('/\d+/', (string) $validity, $match);
            return (int) ($match[0] ?? 9999);
        })->values();

        $sfDistrictMap = DB::table('appsdumais')
            ->whereNotNull('sf')->whereNotNull('kecamatan')
            ->where('sf', '!=', '')->where('kecamatan', '!=', '')
            ->select('tap', 'sf', 'kecamatan', DB::raw('COUNT(*) as total'))
            ->groupBy('tap', 'sf', 'kecamatan')
            ->get()
            ->groupBy(fn ($item) => strtoupper(trim($item->tap)) . '|' . strtoupper(trim($item->sf)))
            ->map(fn ($items) => $items->sortByDesc('total')->first()->kecamatan);

        $makeValues = function ($items) use ($validities) {
            $values = [];
            foreach (['all', 'reguler', 'byu'] as $category) {
                foreach ($validities as $validity) {
                    $matching = $items->where('group_name', $validity);
                    if ($category !== 'all') $matching = $matching->where('category', $category);
                    $m1 = (int) $matching->sum('m1');
                    $mtd = (int) $matching->sum('mtd');
                    $values[$category][$validity] = [
                        'm1' => $m1,
                        'mtd' => $mtd,
                        'mom' => $m1 > 0 ? round((($mtd - $m1) / $m1) * 100, 1) : ($mtd > 0 ? 100 : 0),
                    ];
                }
                $totalM1 = collect($values[$category])->sum('m1');
                $totalMtd = collect($values[$category])->sum('mtd');
                $values[$category]['grand_total'] = [
                    'm1' => $totalM1,
                    'mtd' => $totalMtd,
                    'mom' => $totalM1 > 0 ? round((($totalMtd - $totalM1) / $totalM1) * 100, 1) : ($totalMtd > 0 ? 100 : 0),
                ];
            }
            return $values;
        };

        $sfRows = $rows->groupBy(fn ($row) => $row->idtap . '|' . $row->idsf)->map(function ($items) use ($makeValues, $sfDistrictMap) {
            $first = $items->first();
            $mapKey = strtoupper(trim($first->idtap)) . '|' . strtoupper(trim($first->namasf));
            return [
                'dimension' => 'sf', 'name' => $first->namasf, 'tap' => $first->idtap,
                'district' => $sfDistrictMap->get($mapKey, 'Belum termapping'),
                'values' => $makeValues($items),
            ];
        })->sortBy(fn ($row) => $row['tap'] . '|' . $row['name'])->values();

        $districtRows = $sfRows->groupBy(fn ($row) => $row['tap'] . '|' . $row['district'])->map(function ($items) use ($validities) {
            $values = [];
            foreach (['all', 'reguler', 'byu'] as $category) {
                foreach ($validities as $validity) {
                    $m1 = $items->sum(fn ($row) => $row['values'][$category][$validity]['m1']);
                    $mtd = $items->sum(fn ($row) => $row['values'][$category][$validity]['mtd']);
                    $values[$category][$validity] = ['m1' => $m1, 'mtd' => $mtd, 'mom' => $m1 > 0 ? round((($mtd - $m1) / $m1) * 100, 1) : ($mtd > 0 ? 100 : 0)];
                }
                $totalM1 = collect($values[$category])->sum('m1');
                $totalMtd = collect($values[$category])->sum('mtd');
                $values[$category]['grand_total'] = ['m1' => $totalM1, 'mtd' => $totalMtd, 'mom' => $totalM1 > 0 ? round((($totalMtd - $totalM1) / $totalM1) * 100, 1) : ($totalMtd > 0 ? 100 : 0)];
            }
            return ['dimension' => 'kecamatan', 'name' => $items->first()['district'], 'tap' => $items->first()['tap'], 'values' => $values];
        })->sortBy(fn ($row) => $row['tap'] . '|' . $row['name'])->values();

        $tapRows = $sfRows->groupBy('tap')->map(function ($items, $tap) use ($validities) {
            $values = [];
            foreach (['all', 'reguler', 'byu'] as $category) {
                foreach ($validities as $validity) {
                    $m1 = $items->sum(fn ($row) => $row['values'][$category][$validity]['m1']);
                    $mtd = $items->sum(fn ($row) => $row['values'][$category][$validity]['mtd']);
                    $values[$category][$validity] = ['m1' => $m1, 'mtd' => $mtd, 'mom' => $m1 > 0 ? round((($mtd - $m1) / $m1) * 100, 1) : ($mtd > 0 ? 100 : 0)];
                }
                $totalM1 = collect($values[$category])->sum('m1');
                $totalMtd = collect($values[$category])->sum('mtd');
                $values[$category]['grand_total'] = ['m1' => $totalM1, 'mtd' => $totalMtd, 'mom' => $totalM1 > 0 ? round((($totalMtd - $totalM1) / $totalM1) * 100, 1) : ($totalMtd > 0 ? 100 : 0)];
            }
            return ['dimension' => 'tap', 'name' => $tap, 'tap' => $tap, 'values' => $values];
        })->sortBy('name')->values();

        return [
            'validities' => $validities->all(),
            'rows' => $sfRows->concat($districtRows)->concat($tapRows)->values()->all(),
            'period' => ['m1' => $m1Start->format('d M') . '–' . $m1End->format('d M Y'), 'mtd' => $mtdStart->format('d M') . '–' . $mtdEnd->format('d M Y')],
        ];
    }

    private function leaderMonitoringUpdateDate(): ?string
    {
        $latestDate = DB::table('appsdumais')
            ->whereNotNull('tgl_pack')
            ->where('tgl_pack', '!=', '')
            ->max('tgl_pack');

        if (!$latestDate) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($latestDate)->format('d-M');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function leaderCompetitionPoints(): array
    {
        if (!DB::getSchemaBuilder()->hasTable('peta_kompetisi')) {
            return [];
        }

        $operators = [
            ['key' => 'tsel', 'label' => 'TSEL', 'mtd' => 'tsel_mtd', 'mom' => 'tsel_mom', 'color' => '#e30613'],
            ['key' => 'isat', 'label' => 'ISAT', 'mtd' => 'isat_mtd', 'mom' => 'isat_mom', 'color' => '#f5c400'],
            ['key' => 'xl', 'label' => 'XL', 'mtd' => 'xl_mtd', 'mom' => 'xl_mom', 'color' => '#0057ff'],
            ['key' => 'tri', 'label' => '3', 'mtd' => '3_mtd', 'mom' => '3_mom', 'color' => '#111827'],
            ['key' => 'sfren', 'label' => 'SFREN', 'mtd' => 'sfren_mtd', 'mom' => 'sfren_mom', 'color' => '#ff4b8b'],
            ['key' => 'istri', 'label' => 'ISAT+3', 'mtd' => 'istri_mtd', 'mom' => 'istri_mom', 'color' => '#f97316'],
            ['key' => 'xlsf', 'label' => 'XL+SF', 'mtd' => 'xlsf_mtd', 'mom' => 'xlsf_mom', 'color' => '#00a7e1'],
        ];

        $competitionRows = DB::table('peta_kompetisi')
            ->orderBy('cluster')
            ->orderBy('tap')
            ->orderBy('kecamatan')
            ->get();

        if ($competitionRows->isEmpty()) {
            return [];
        }

        $outletCoordinates = DB::table('appsdumais')
            ->whereIn('tap', $competitionRows->pluck('tap')->filter()->unique()->values()->all())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->whereNotNull('sf')
            ->where('sf', '!=', '')
            ->where('sf', '!=', 'UNMAPPING')
            ->whereBetween(DB::raw('CAST(latitude AS DECIMAL(12,8))'), [0, 3])
            ->whereBetween(DB::raw('CAST(longitude AS DECIMAL(12,8))'), [100, 102.5])
            ->whereNotNull('kecamatan')
            ->where('kecamatan', '!=', '')
            ->select('kecamatan', 'tap', 'latitude', 'longitude')
            ->get()
            ->groupBy(fn ($row) => $this->competitionCenterKey((string) $row->kecamatan, (string) $row->tap));

        return $competitionRows
            ->map(function ($row) use ($operators, $outletCoordinates) {
                $values = collect($operators)->map(function ($operator) use ($row) {
                    return [
                        'key' => $operator['key'],
                        'label' => $operator['label'],
                        'color' => $operator['color'],
                        'share' => $this->parsePercentValue($row->{$operator['mtd']} ?? 0),
                        'mom' => $this->parsePercentValue($row->{$operator['mom']} ?? 0),
                    ];
                })->values();

                $winner = $values->sortByDesc('share')->first();
                $coordinateRows = $outletCoordinates->get($this->competitionCenterKey((string) $row->kecamatan, (string) $row->tap), collect());
                $points = $coordinateRows
                    ->map(fn ($point) => [
                        'lat' => (float) $point->latitude,
                        'lng' => (float) $point->longitude,
                    ])
                    ->filter(fn ($point) => $point['lat'] && $point['lng'])
                    ->values();

                if ($points->isEmpty()) {
                    return null;
                }

                $latitude = $points->avg('lat');
                $longitude = $points->avg('lng');

                return [
                    'kecamatan' => $row->kecamatan,
                    'tap' => $row->tap,
                    'cluster' => $row->cluster,
                    'latitude' => (float) $latitude,
                    'longitude' => (float) $longitude,
                    'outlet_count' => $points->count(),
                    'winner_key' => $winner['key'],
                    'winner_operator' => $winner['label'],
                    'winner_share' => round($winner['share'], 2),
                    'winner_mom' => round($winner['mom'], 2),
                    'winner_color' => $winner['color'],
                    'operators' => $values->sortByDesc('share')->values()->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function parsePercentValue($value): float
    {
        $raw = trim((string) ($value ?? '0'));
        $hasPercent = str_contains($raw, '%');
        $number = (float) str_replace(['%', ',', ' '], ['', '.', ''], $raw);

        if (!$hasPercent && abs($number) <= 1) {
            return $number * 100;
        }

        return $number;
    }

    private function normalizeDistrictName(string $name): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($name)) ?: '';
    }

    private function competitionCenterKey(string $district, string $tap): string
    {
        return $this->normalizeDistrictName($district) . '|' . $this->normalizeDistrictName($tap);
    }

    private function leaderCoveragePoints(): array
    {
        $clusterGroups = [
            'dumai_bengkalis' => ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING'],
            'rokan_hilir' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
        ];

        $tapGroupMap = collect($clusterGroups)
            ->flatMap(fn ($taps, $key) => collect($taps)->mapWithKeys(fn ($tap) => [$tap => $key]));

        $outlets = DB::table('appsdumais')
            ->whereIn('tap', $tapGroupMap->keys()->all())
            ->whereNotNull('sf')
            ->where('sf', '!=', '')
            ->where('sf', '!=', 'UNMAPPING')
            ->select('id_outlet', 'tap', 'kecamatan', 'sf', 'm_cvm', 'm1_cvm')
            ->get();

        $performance = $outlets->isEmpty()
            ? collect()
            : DB::table('outlet_performance')
                ->whereIn('id_outlet', $outlets->pluck('id_outlet'))
                ->select('id_outlet', 'total_sp_m', 'total_sp_m1', 'total_m', 'total_m1')
                ->get()
                ->keyBy('id_outlet');

        return $outlets->map(function ($outlet) use ($performance, $tapGroupMap) {
            $perf = $performance->get($outlet->id_outlet);

            return [
                'group' => $tapGroupMap->get($outlet->tap),
                'tap' => $outlet->tap ?: 'TAP BELUM ADA',
                'kecamatan' => $outlet->kecamatan ?: 'KECAMATAN BELUM ADA',
                'sf' => $outlet->sf ?: 'SF BELUM ADA',
                'st_sa' => (int) ($perf->total_sp_m ?? 0),
                'st_sa_m1' => (int) ($perf->total_sp_m1 ?? 0),
                'st_pv' => (int) ($perf->total_m ?? 0),
                'st_pv_m1' => (int) ($perf->total_m1 ?? 0),
                'trx_cvm' => (int) ($outlet->m_cvm ?? 0),
                'trx_cvm_m1' => (int) ($outlet->m1_cvm ?? 0),
            ];
        })->filter(fn ($point) => !empty($point['group']))->values()->all();
    }

    private function leaderClusterCoverage(array $thresholds): array
    {
        $clusterGroups = [
            'dumai_bengkalis' => [
                'label' => 'Dumai Bengkalis',
                'taps' => ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING'],
            ],
            'rokan_hilir' => [
                'label' => 'Rokan Hilir',
                'taps' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
            ],
        ];

        $tapGroupMap = collect($clusterGroups)
            ->flatMap(fn ($group, $key) => collect($group['taps'])->mapWithKeys(fn ($tap) => [$tap => $key]));

        $outlets = DB::table('appsdumais')
            ->whereIn('tap', $tapGroupMap->keys()->all())
            ->whereNotNull('sf')
            ->where('sf', '!=', '')
            ->where('sf', '!=', 'UNMAPPING')
            ->select('id_outlet', 'tap', 'm_cvm')
            ->get();

        $performance = $outlets->isEmpty()
            ? collect()
            : DB::table('outlet_performance')
                ->whereIn('id_outlet', $outlets->pluck('id_outlet'))
                ->select('id_outlet', 'total_sp_m', 'total_m')
                ->get()
                ->keyBy('id_outlet');

        $coverage = collect($clusterGroups)->mapWithKeys(function ($group, $key) {
            return [$key => [
                'label' => $group['label'],
                'pjp' => 0,
                'sa' => 0,
                'pv' => 0,
                'cvm' => 0,
            ]];
        })->all();

        foreach ($outlets as $outlet) {
            $groupKey = $tapGroupMap->get($outlet->tap);
            if (!$groupKey) continue;

            $perf = $performance->get($outlet->id_outlet);
            $coverage[$groupKey]['pjp']++;
            $coverage[$groupKey]['sa'] += (int) ($perf->total_sp_m ?? 0) >= $thresholds['sa'] ? 1 : 0;
            $coverage[$groupKey]['pv'] += (int) ($perf->total_m ?? 0) >= $thresholds['pv'] ? 1 : 0;
            $coverage[$groupKey]['cvm'] += (int) ($outlet->m_cvm ?? 0) >= $thresholds['cvm'] ? 1 : 0;
        }

        return [
            'thresholds' => $thresholds,
            'groups' => array_values($coverage),
        ];
    }

    private function aggregateTapPerformance(string $tap): array
    {
        $currentExpr = $this->appsdumaiTransactionExpression('m_');
        $previousExpr = $this->appsdumaiTransactionExpression('m1_');
        $outletQuery = DB::table('appsdumais')->where('tap', $tap);

        $base = (clone $outletQuery)
            ->selectRaw("
                COUNT(*) as outlets,
                SUM(CASE WHEN sf IS NULL OR sf = '' OR sf = 'UNMAPPING' THEN 1 ELSE 0 END) as unmapped_outlets,
                AVG(NULLIF(latitude, '')) as avg_latitude,
                AVG(NULLIF(longitude, '')) as avg_longitude,
                SUM($currentExpr) as trx_m,
                SUM($previousExpr) as trx_m1,
                SUM(COALESCE(m_cvm, 0)) as trx_cvm
            ")
            ->first();

        $outletIds = (clone $outletQuery)->pluck('id_outlet');
        $performance = $outletIds->isEmpty()
            ? null
            : DB::table('outlet_performance')
                ->whereIn('id_outlet', $outletIds)
                ->selectRaw('
                    SUM(COALESCE(total_sp_m, 0)) as st_sa,
                    SUM(COALESCE(total_sp_m1, 0)) as st_sa_m1,
                    SUM(COALESCE(total_m, 0)) as st_pv,
                    SUM(COALESCE(total_m1, 0)) as st_pv_m1
                ')
                ->first();

        $appProductiveIds = (clone $outletQuery)
            ->whereRaw("$currentExpr > 0")
            ->pluck('id_outlet');

        $performanceProductiveIds = $outletIds->isEmpty()
            ? collect()
            : DB::table('outlet_performance')
                ->whereIn('id_outlet', $outletIds)
                ->whereRaw('COALESCE(total_sp_m, 0) + COALESCE(total_m, 0) > 0')
                ->pluck('id_outlet');

        $productiveOutlets = $appProductiveIds
            ->merge($performanceProductiveIds)
            ->unique()
            ->count();

        $stSa = (int) ($performance->st_sa ?? 0);
        $stPv = (int) ($performance->st_pv ?? 0);
        $trxM = (int) ($base->trx_m ?? 0);
        $trxCvm = (int) ($base->trx_cvm ?? 0);
        $current = $stSa + $stPv + $trxM;
        $previous = (int) ($performance->st_sa_m1 ?? 0) + (int) ($performance->st_pv_m1 ?? 0) + (int) ($base->trx_m1 ?? 0);
        $outlets = (int) ($base->outlets ?? 0);
        $productivity = $outlets > 0 ? ($productiveOutlets / $outlets) * 100 : 0;
        $mom = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'tap' => $tap,
            'outlets' => $outlets,
            'unmapped_outlets' => (int) ($base->unmapped_outlets ?? 0),
            'productive_outlets' => $productiveOutlets,
            'productivity' => round($productivity, 1),
            'st_sa' => $stSa,
            'st_pv' => $stPv,
            'trx_m' => $trxM,
            'trx_cvm' => $trxCvm,
            'current' => $current,
            'previous' => $previous,
            'mom' => round($mom, 1),
            'latitude' => $base->avg_latitude ? (float) $base->avg_latitude : null,
            'longitude' => $base->avg_longitude ? (float) $base->avg_longitude : null,
        ];
    }

    private function appsdumaiTransactionExpression(string $prefix): string
    {
        $columns = ['digipos', 'cvm', 'comsak', 'hot', 'insak', 'digital', 'voice', 'renewal', 'super', 'hyper'];

        return collect($columns)
            ->map(fn ($column) => 'COALESCE(' . $prefix . $column . ', 0)')
            ->implode(' + ');
    }

    private function leaderOutletPoints(array $areaTaps): array
    {
        $tapAreaMap = collect($areaTaps)
            ->flatMap(fn ($taps, $area) => collect($taps)->mapWithKeys(fn ($tap) => [$tap => $area]));

        $currentExpr = $this->appsdumaiTransactionExpression('m_');
        $previousExpr = $this->appsdumaiTransactionExpression('m1_');
        $taps = $tapAreaMap->keys()->all();

        $outlets = DB::table('appsdumais')
            ->whereIn('tap', $taps)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->whereNotNull('sf')
            ->where('sf', '!=', '')
            ->where('sf', '!=', 'UNMAPPING')
            ->select(
                'id_outlet',
                'nama_outlet',
                'tap',
                'kecamatan',
                'sf',
                'latitude',
                'longitude',
                'm_cvm',
                'm1_cvm'
            )
            ->selectRaw("($currentExpr) as trx_m")
            ->selectRaw("($previousExpr) as trx_m1")
            ->orderByDesc('trx_m')
            ->limit(1000)
            ->get();

        $performance = $outlets->isEmpty()
            ? collect()
            : DB::table('outlet_performance')
                ->whereIn('id_outlet', $outlets->pluck('id_outlet'))
                ->select('id_outlet', 'total_sp_m', 'total_sp_m1', 'total_m', 'total_m1')
                ->get()
                ->keyBy('id_outlet');

        return $outlets->map(function ($outlet) use ($performance, $tapAreaMap) {
            $perf = $performance->get($outlet->id_outlet);
            $stSa = (int) ($perf->total_sp_m ?? 0);
            $stSaM1 = (int) ($perf->total_sp_m1 ?? 0);
            $stPv = (int) ($perf->total_m ?? 0);
            $stPvM1 = (int) ($perf->total_m1 ?? 0);
            $trx = (int) ($outlet->trx_m ?? 0);
            $trxM1 = (int) ($outlet->trx_m1 ?? 0);
            $trxCvm = (int) ($outlet->m_cvm ?? 0);
            $trxCvmM1 = (int) ($outlet->m1_cvm ?? 0);
            $current = $stSa + $stPv + $trx;

            return [
                'id_outlet' => $outlet->id_outlet,
                'nama_outlet' => $outlet->nama_outlet,
                'tap' => $outlet->tap,
                'kecamatan' => $outlet->kecamatan ?: 'KECAMATAN BELUM ADA',
                'area' => $tapAreaMap->get($outlet->tap, 'Area lain'),
                'sf' => $outlet->sf ?: 'UNMAPPING',
                'latitude' => (float) $outlet->latitude,
                'longitude' => (float) $outlet->longitude,
                'trx_m' => $trx,
                'trx_m1' => $trxM1,
                'trx_cvm' => $trxCvm,
                'trx_cvm_m1' => $trxCvmM1,
                'st_sa' => $stSa,
                'st_sa_m1' => $stSaM1,
                'st_pv' => $stPv,
                'st_pv_m1' => $stPvM1,
                'current' => $current,
                'status' => $current > 0 ? 'hot' : 'cold',
            ];
        })->values()->all();
    }

    private function areaRiskLabel(float $mom, float $productivity): string
    {
        if ($mom < -15 || $productivity < 35) return 'Prioritas';
        if ($mom < 0 || $productivity < 50) return 'Pantau';
        return 'Aman';
    }

    private function leaderActions($riskTaps, $areas): array
    {
        $topRisk = $riskTaps->first();
        $lowestArea = $areas->sortBy('productivity')->first();

        return [
            [
                'title' => 'Recovery TAP prioritas',
                'body' => $topRisk
                    ? 'Fokuskan kunjungan leader ke ' . $topRisk['tap'] . ' karena produktivitas ' . $topRisk['productivity'] . '% dan MoM ' . $topRisk['mom'] . '%.'
                    : 'Semua TAP terlihat stabil, lanjutkan monitoring harian.',
                'tone' => 'danger',
            ],
            [
                'title' => 'Aktivasi outlet tidur',
                'body' => $lowestArea
                    ? $lowestArea['name'] . ' punya produktivitas outlet ' . $lowestArea['productivity'] . '%. Dorong sampling, retensi, dan follow-up outlet tanpa transaksi.'
                    : 'Data area belum cukup untuk menentukan outlet tidur.',
                'tone' => 'warning',
            ],
            [
                'title' => 'Jaga momentum SA dan PV',
                'body' => 'Bandingkan ST SA, ST PV, dan transaksi appsdumais di tiap TAP sebelum briefing pagi agar CTA sales lebih tajam.',
                'tone' => 'success',
            ],
        ];
    }

    public function search(Request $request)
    {
        if (!$request->session()->has('monita_leader_auth')) {
            return response()->json(['message' => 'Akses dashboard diperlukan.'], 403);
        }

        $keyword = $request->input('keyword');
        $data = DB::table('appsdumais')
            ->leftJoin('outlet_performance', 'appsdumais.id_outlet', '=', 'outlet_performance.id_outlet')
            ->select(
                'appsdumais.*',
                'outlet_performance.sp_simpati_fm1 as fm1_stsa',
                'outlet_performance.total_sp_m as m_stsa',
                'outlet_performance.tgl_update as tgl_sa',
                'outlet_performance.total_fm1 as fm1_stpv',
                'outlet_performance.total_m as m_stpv',
                'outlet_performance.total_m1 as m1_stpv',
                'outlet_performance.total_mom as mom_stpv',
                'outlet_performance.tgl_update as tgl_pv'
            )
            ->where('appsdumais.id_outlet', $keyword)
            ->orWhere('appsdumais.nama_outlet', 'like', '%' . $keyword . '%')
            ->get();

        return response()->json($data);
    }

    public function suggest(Request $request)
    {
        if (!$request->session()->has('monita_leader_auth')) {
            return response()->json(['message' => 'Akses dashboard diperlukan.'], 403);
        }

        $keyword = $request->input('keyword');
        $data = DB::table('appsdumais')
            ->where('nama_outlet', 'like', '%' . $keyword . '%')
            ->orWhere('id_outlet', 'like', '%' . $keyword . '%')
            ->select('id_outlet', 'nama_outlet', 'sf', 'tap')
            ->limit(10)
            ->get();

        return response()->json($data);
    }

    public function outlets(Request $request)
    {
        if (!$request->session()->has('monita_leader_auth')) {
            return response()->json(['message' => 'Akses dashboard diperlukan.'], 403);
        }

        $outlets = DB::table('appsdumais')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->where('sf', '!=', 'UNMAPPING')
            ->whereBetween(DB::raw('CAST(latitude AS DECIMAL(12,8))'), [0, 3])
            ->whereBetween(DB::raw('CAST(longitude AS DECIMAL(12,8))'), [100, 102.5])
            ->select(
                'id_outlet',
                'nama_outlet',
                'sf',
                'tap',
                'latitude',
                'longitude',
                'm_cvm'
            )
            ->get();

        $performanceByOutlet = DB::table('outlet_performance')
            ->whereIn('id_outlet', $outlets->pluck('id_outlet')->map(fn ($id) => (string) $id)->all())
            ->select('id_outlet', 'total_sp_m', 'total_m')
            ->get()
            ->keyBy(fn ($row) => (string) $row->id_outlet);

        $data = $outlets->map(function ($outlet) use ($performanceByOutlet) {
            $performance = $performanceByOutlet->get((string) $outlet->id_outlet);
            $outlet->m_stsa = $performance->total_sp_m ?? 0;
            $outlet->m_stpv = $performance->total_m ?? 0;

            return $outlet;
        });

        return response()->json($data);
    }

    public function nearby(Request $request)
    {
        if (!$request->session()->has('monita_leader_auth')) {
            return response()->json(['message' => 'Akses dashboard diperlukan.'], 403);
        }

        $lat = $request->input('latitude');
        $long = $request->input('longitude');
        $radius = $request->input('radius', 0.3);

        if (!$lat || !$long) {
            return response()->json(['error' => 'Latitude and longitude are required'], 400);
        }

        // Optimization: Bounding Box filter to use indices and reduce candidate set
        // 1 degree of latitude is approximately 111 km
        $lat_delta = $radius / 111.0;
        // 1 degree of longitude is approximately 111 km * cos(latitude)
        $lon_delta = $radius / (111.0 * cos(deg2rad($lat)));

        // Haversine formula to find outlets within preferred radius
        $data = DB::table('appsdumais')
            ->whereBetween('latitude', [$lat - $lat_delta, $lat + $lat_delta])
            ->whereBetween('longitude', [$long - $lon_delta, $long + $lon_delta])
            ->leftJoin('outlet_performance', 'appsdumais.id_outlet', '=', 'outlet_performance.id_outlet')
            ->select(
                'appsdumais.*',
                'outlet_performance.sp_simpati_fm1 as fm1_stsa',
                'outlet_performance.total_sp_m as m_stsa',
                'outlet_performance.tgl_update as tgl_sa',
                'outlet_performance.total_fm1 as fm1_stpv',
                'outlet_performance.total_m as m_stpv',
                'outlet_performance.total_m1 as m1_stpv',
                'outlet_performance.total_mom as mom_stpv',
                'outlet_performance.tgl_update as tgl_pv'
            )
            ->selectRaw(
                '( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
                [$lat, $long, $lat]
            )
            ->where('sf', '!=', 'UNMAPPING')
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->limit(50)
            ->get();

        return response()->json($data);
    }

    public function performance(Request $request)
    {
        if (!$request->session()->has('monita_leader_auth')) {
            return response()->json(['message' => 'Akses dashboard diperlukan.'], 403);
        }

        $id_outlet = $request->input('id_outlet');
        $data = DB::table('outlet_performance')
            ->where('id_outlet', $id_outlet)
            ->first();

        return response()->json($data);
    }
}
