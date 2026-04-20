<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        /* ================= MODE ================= */
        $mode = $request->query('mode', 'daily');
        $idtap = session('idtap');

        $applyFilter = function ($q, $col = 'idtap') use ($idtap) {
            if ($idtap === 'CLUSTER_DUMAI') {
                $q->whereIn($col, ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING']);
            } elseif ($idtap === 'CLUSTER_ROHIL') {
                $q->whereIn($col, ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG']);
            } elseif ($idtap !== 'SBP_DUMAI') {
                $q->where($col, $idtap);
            }
        };

        /* ================= DETECT CURRENT DATE IN SYSTEM ================= */
        $latestRecord = DB::table('keluarsf')->max('tgl');
        $currentDate = $latestRecord ? Carbon::parse($latestRecord) : now();

        $startThisMonth = $currentDate->copy()->startOfMonth();
        $endThisMonth = $currentDate->copy(); // Dipakai untuk akumulasi total (Sales Bulan Ini, Pie, dll)

        /* ================= FAIR GLOBAL CUTOFF (MoM 16 vs 16) ================= */
        $maxTglHarian = DB::table('keluarsf')
            ->whereYear('tgl', $currentDate->year)
            ->whereMonth('tgl', $currentDate->month)
            ->selectRaw('idtap, MAX(tgl) AS last_keluar')
            ->groupBy('idtap');
        $applyFilter($maxTglHarian);
        $maxTglHarian = $maxTglHarian->pluck('last_keluar');

        $fairCutoffThisMonth = $maxTglHarian->isEmpty() ? $currentDate->copy() : Carbon::parse($maxTglHarian->min());
        $cutoffDay = $fairCutoffThisMonth->day;

        /* ================= PREV MONTH TIME RANGE (M-1) ================= */
        $startPrevMonth = $currentDate->copy()->subMonth()->startOfMonth();
        $endPrevMonthFull = $currentDate->copy()->subMonth()->endOfMonth();
        $endPrevMonthPartial = $startPrevMonth->copy()->addDays($cutoffDay - 1);

        /* ================= TWO MONTHS AGO TIME RANGE (M-2) ================= */
        $startM2Month = $currentDate->copy()->subMonths(2)->startOfMonth();
        $endM2MonthFull = $currentDate->copy()->subMonths(2)->endOfMonth();
        $endM2MonthPartial = $startM2Month->copy()->addDays($cutoffDay - 1);

        /* ================= TAP ACTIVITY ================= */
        $tapMasuk = DB::table('masuksf')
            ->selectRaw('idtap, MAX(tgl) AS last_masuk')
            ->groupBy('idtap');
        $applyFilter($tapMasuk);
        $tapMasuk = $tapMasuk->pluck('last_masuk', 'idtap');

        $tapKeluar = DB::table('keluarsf')
            ->selectRaw('idtap, MAX(tgl) AS last_keluar')
            ->groupBy('idtap');
        $applyFilter($tapKeluar);
        $tapKeluar = $tapKeluar->pluck('last_keluar', 'idtap');

        $tapList = DB::table('keluarsf')
            ->select('idtap')
            ->distinct()
            ->orderBy('idtap');
        $applyFilter($tapList);
        $tapList = $tapList->pluck('idtap');

        /* ================= KPI ================= */
        $qStokSegel1 = DB::table('stockawaltap')->whereIn('iddenom', ['SEGEL', 'V16', 'V33']);
        $applyFilter($qStokSegel1);
        $qStokSegel2 = DB::table('stockawalsf as sf')->join('idsf', 'sf.idsf', '=', 'idsf.idsf')->whereIn('sf.iddenom', ['SEGEL', 'V16', 'V33']);
        $applyFilter($qStokSegel2, 'idsf.idtap');
        $stokSegel = $qStokSegel1->sum('stock') + $qStokSegel2->sum('sf.stock');

        $qStokInject1 = DB::table('stockawaltap')->whereNotIn('iddenom', ['SEGEL', 'V16', 'V33']);
        $applyFilter($qStokInject1);
        $qStokInject2 = DB::table('stockawalsf as sf')->join('idsf', 'sf.idsf', '=', 'idsf.idsf')->whereNotIn('sf.iddenom', ['SEGEL', 'V16', 'V33']);
        $applyFilter($qStokInject2, 'idsf.idtap');
        $stokInject = $qStokInject1->sum('stock') + $qStokInject2->sum('sf.stock');

        $qSalesBulanIni = DB::table('keluarsf')->whereBetween('tgl', [$startThisMonth, $endThisMonth]);
        $applyFilter($qSalesBulanIni);
        $salesBulanIni = $qSalesBulanIni->sum('qty');

        $qSalesPrev = DB::table('keluarsf')->whereBetween('tgl', [$startPrevMonth, $endPrevMonthPartial]);
        $applyFilter($qSalesPrev);
        $salesPrev = $qSalesPrev->sum('qty');

        $mom = $salesPrev > 0
            ? (($salesBulanIni - $salesPrev) / $salesPrev) * 100
            : 0;

        /* ================= CHART SALES ================= */
        if ($mode === 'monthly') {
            $qChartSales = DB::table('keluarsf')
                ->selectRaw("DATE_FORMAT(tgl,'%Y-%m') AS label, SUM(qty) AS total")
                ->whereBetween('tgl', [
                    $currentDate->copy()->subMonths(5)->startOfMonth(),
                    $currentDate
                ])
                ->groupBy(DB::raw("DATE_FORMAT(tgl,'%Y-%m')"))
                ->orderBy(DB::raw("DATE_FORMAT(tgl,'%Y-%m')"));
            $applyFilter($qChartSales);
            $chartSales = $qChartSales->get();
        } else {
            $qChartSales = DB::table('keluarsf')
                ->selectRaw("DATE(tgl) AS label, SUM(qty) AS total")
                ->whereBetween('tgl', [$startThisMonth, $endThisMonth])
                ->groupBy(DB::raw('DATE(tgl)'))
                ->orderBy('label');
            $applyFilter($qChartSales);
            $chartSales = $qChartSales->get();
        }

        /* ================= CHART INJECT ================= */
        $qChartInject = DB::table('injectvf')
            ->selectRaw("
                DATE(tgl) AS label,
                SUM(CASE WHEN idtap IN ('DUMAI','BENGKALIS','DURI','RUPAT','SEI PAKNING') THEN qty ELSE 0 END) AS dumai,
                SUM(CASE WHEN idtap IN ('BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG') THEN qty ELSE 0 END) AS rohil
            ")
            ->whereBetween('tgl', [$startThisMonth, $endThisMonth])
            ->groupBy(DB::raw('DATE(tgl)'))
            ->orderBy('label');
        $applyFilter($qChartInject);
        $chartInject = $qChartInject->get();

        /* ================= MoM PER TAP (FAIR 16 vs 16 CUTOFF) ================= */
        $qMomTap = DB::table('keluarsf')
            ->selectRaw("
                idtap,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS curr_qty,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS prev_partial_qty,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS prev_full_qty,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS m2_partial_qty,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS m2_full_qty
            ", [
                $startThisMonth, $fairCutoffThisMonth,
                $startPrevMonth, $endPrevMonthPartial,
                $startPrevMonth, $endPrevMonthFull,
                $startM2Month, $endM2MonthPartial,
                $startM2Month, $endM2MonthFull
            ])
            ->groupBy('idtap')
            ->orderBy('idtap');
        $applyFilter($qMomTap);
        $momTap = $qMomTap->get()
            ->map(function ($r) {
                $r->mom = $r->prev_partial_qty > 0
                    ? (($r->curr_qty - $r->prev_partial_qty) / $r->prev_partial_qty) * 100
                    : 0;
                $r->mom_m2 = $r->m2_partial_qty > 0
                    ? (($r->curr_qty - $r->m2_partial_qty) / $r->m2_partial_qty) * 100
                    : 0;
                return $r;
            });

        /* ================= CLUSTER SUMMARY ================= */
        $clusterMap = [
            'dumai_bengkalis' => ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING'],
            'rokan_hilir' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
        ];

        $momCluster = collect();

        foreach ($clusterMap as $key => $taps) {
            $curr = DB::table('keluarsf')->whereIn('idtap', $taps)
                ->whereBetween('tgl', [$startThisMonth, $fairCutoffThisMonth])->sum('qty');

            $prevPartial = DB::table('keluarsf')->whereIn('idtap', $taps)
                ->whereBetween('tgl', [$startPrevMonth, $endPrevMonthPartial])->sum('qty');

            $prevFull = DB::table('keluarsf')->whereIn('idtap', $taps)
                ->whereBetween('tgl', [$startPrevMonth, $endPrevMonthFull])->sum('qty');

            $m2Partial = DB::table('keluarsf')->whereIn('idtap', $taps)
                ->whereBetween('tgl', [$startM2Month, $endM2MonthPartial])->sum('qty');

            $m2Full = DB::table('keluarsf')->whereIn('idtap', $taps)
                ->whereBetween('tgl', [$startM2Month, $endM2MonthFull])->sum('qty');

            $momCluster[$key] = (object) [
                'curr_qty' => $curr,
                'prev_partial_qty' => $prevPartial,
                'prev_full_qty' => $prevFull,
                'm2_partial_qty' => $m2Partial,
                'm2_full_qty' => $m2Full,
                'mom' => $prevPartial > 0 ? (($curr - $prevPartial) / $prevPartial) * 100 : 0,
                'mom_m2' => $m2Partial > 0 ? (($curr - $m2Partial) / $m2Partial) * 100 : 0
            ];
        }

        /* ================= MoM PER SF ================= */
        $qMomSf = DB::table('keluarsf as k')
            ->join('idsf as s', 'k.idsf', '=', 's.idsf')
            ->selectRaw("
                k.idtap, k.idsf, s.namasf,
                SUM(CASE WHEN k.tgl BETWEEN ? AND ? THEN k.qty ELSE 0 END) AS curr_qty,
                SUM(CASE WHEN k.tgl BETWEEN ? AND ? THEN k.qty ELSE 0 END) AS prev_qty,
                SUM(CASE WHEN k.tgl BETWEEN ? AND ? THEN k.qty ELSE 0 END) AS m2_qty
            ", [
                $startThisMonth, $fairCutoffThisMonth, 
                $startPrevMonth, $endPrevMonthPartial,
                $startM2Month, $endM2MonthPartial
            ])
            ->groupBy('k.idtap', 'k.idsf', 's.namasf');
        $applyFilter($qMomSf, 'k.idtap');
        $momSf = $qMomSf->get()
            ->map(function ($r) {
                $r->mom = $r->prev_qty > 0
                    ? (($r->curr_qty - $r->prev_qty) / $r->prev_qty) * 100
                    : 0;
                $r->mom_m2 = $r->m2_qty > 0
                    ? (($r->curr_qty - $r->m2_qty) / $r->m2_qty) * 100
                    : 0;
                return $r;
            });

        /* ================= MOM DAILY ================= */
        $qMomDaily = DB::table('keluarsf')
            ->selectRaw("
                DAY(tgl) AS day,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS curr_qty,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS prev_qty
            ", [$startThisMonth, $fairCutoffThisMonth, $startPrevMonth, $endPrevMonthPartial])
            ->groupBy(DB::raw('DAY(tgl)'))
            ->orderBy('day');
        $applyFilter($qMomDaily);
        $momDaily = $qMomDaily->get();
        /* ================= TOP SF ================= */
        $topGrowth = $momSf
            ->filter(fn($r) => Str::startsWith($r->idsf, 'SF') && $r->mom > 0)
            ->sortByDesc('mom')
            ->take(5)
            ->values();

        $topDrop = $momSf
            ->filter(fn($r) => Str::startsWith($r->idsf, 'SF') && $r->mom < 0)
            ->sortBy('mom')
            ->take(5)
            ->values();

        /* ================= COLLAPSE SF BY TAP ================= */
        $sfByTap = $momSf
            ->filter(fn($r) => Str::startsWith($r->idsf, 'SF'))
            ->groupBy('idtap')
            ->map(fn($rows) => $rows->sortBy('mom')->values());

        /* ================= NEW: MATRIX TAHUNAN PER TAP ================= */
        $selectedYear = $request->query('year', date('Y'));

        $qMatrixData = DB::table('keluarsf')
            ->selectRaw('idtap, MONTH(tgl) as bulan, SUM(qty) as total')
            ->whereYear('tgl', $selectedYear)
            ->groupBy('idtap', DB::raw('MONTH(tgl)'));
        $applyFilter($qMatrixData);
        $matrixSalesData = $qMatrixData->get();

        $matrixSales = [];
        foreach ($tapList as $tap) {
            $matrixSales[$tap] = array_fill(1, 12, 0); // isi 0 untuk 12 bln
        }
        foreach ($matrixSalesData as $row) {
            $matrixSales[$row->idtap][$row->bulan] = $row->total;
        }

        /* ================= NEW: PREV YEAR DECEMBER FOR JAN MoM ================= */
        $prevDecSalesData = DB::table('keluarsf')
            ->selectRaw('idtap, SUM(qty) as total')
            ->whereYear('tgl', $selectedYear - 1)
            ->whereMonth('tgl', 12)
            ->groupBy('idtap');
        $applyFilter($prevDecSalesData);
        $prevDecSales = $prevDecSalesData->pluck('total', 'idtap');

        $prevDecInjectData = DB::table('injectvf')
            ->selectRaw('idtap, SUM(qty) as total')
            ->whereYear('tgl', $selectedYear - 1)
            ->whereMonth('tgl', 12)
            ->groupBy('idtap');
        $applyFilter($prevDecInjectData);
        $prevDecInject = $prevDecInjectData->pluck('total', 'idtap');

        /* ================= NEW: MoM INJECT PER TAP (FAIR CUTOFF) ================= */
        $qMomTapInject = DB::table('injectvf')
            ->selectRaw("
                idtap,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS curr_qty,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS prev_partial_qty
            ", [
                $startThisMonth,
                $fairCutoffThisMonth,
                $startPrevMonth,
                $endPrevMonthPartial
            ])
            ->groupBy('idtap')
            ->orderBy('idtap');
        $applyFilter($qMomTapInject);
        $momTapInject = $qMomTapInject->get()
            ->mapWithKeys(function ($r) {
                $mom = $r->prev_partial_qty > 0
                    ? (($r->curr_qty - $r->prev_partial_qty) / $r->prev_partial_qty) * 100
                    : 0;
                return [$r->idtap => $mom];
            });

        /* ================= NEW: MATRIX TAHUNAN INJECT PV PER TAP ================= */
        $qMatrixInjectData = DB::table('injectvf')
            ->selectRaw('idtap, MONTH(tgl) as bulan, SUM(qty) as total')
            ->whereYear('tgl', $selectedYear)
            ->groupBy('idtap', DB::raw('MONTH(tgl)'));
        $applyFilter($qMatrixInjectData);
        $matrixInjectData = $qMatrixInjectData->get();

        $matrixInject = [];
        foreach ($tapList as $tap) {
            $matrixInject[$tap] = array_fill(1, 12, 0); // isi 0 untuk 12 bln
        }
        foreach ($matrixInjectData as $row) {
            $matrixInject[$row->idtap][$row->bulan] = $row->total;
        }

        /* ================= NEW: MATRIX TAHUNAN PER SF ================= */
        $qMatrixSalesSfData = DB::table('keluarsf')
            ->selectRaw('idsf, MONTH(tgl) as bulan, SUM(qty) as total')
            ->whereYear('tgl', $selectedYear)
            ->groupBy('idsf', DB::raw('MONTH(tgl)'));
        $applyFilter($qMatrixSalesSfData, 'idtap');
        $matrixSalesSfResults = $qMatrixSalesSfData->get();

        $matrixSalesSf = [];
        foreach ($matrixSalesSfResults as $row) {
            $matrixSalesSf[$row->idsf][$row->bulan] = $row->total;
        }

        /* ================= NEW: VALIDITY MATRIX FOR HEATMAP EXPANSION ================= */
        $validityGroups = ['SEGEL', '1 HARI', '2 HARI', '3 HARI', '5 HARI', '7 HARI', '14 HARI', '28 HARI', '30 HARI', 'VOICE'];

        $fetchValidityMatrix = function ($table, $year, $applyFilter) use ($validityGroups) {
            $data = DB::table($table)
                ->join('denom', $table . '.iddenom', '=', 'denom.iddenom')
                ->selectRaw('idtap, denom.group_name, MONTH(tgl) as bulan, SUM(qty) as total')
                ->whereYear('tgl', $year)
                ->whereIn('denom.group_name', $validityGroups)
                ->groupBy('idtap', 'denom.group_name', DB::raw('MONTH(tgl)'));
            $applyFilter($data, $table . '.idtap');
            $rows = $data->get();

            $matrix = [];
            foreach ($rows as $row) {
                $matrix[$row->idtap][$row->group_name][$row->bulan] = $row->total;
            }
            return $matrix;
        };

        $validityMatrixSales = $fetchValidityMatrix('keluarsf', $selectedYear, $applyFilter);
        $validityMatrixInject = $fetchValidityMatrix('injectvf', $selectedYear, $applyFilter);

        /* ================= NEW: DOUGHNUT CHART VALIDITY ================= */
        // PIE SALES
        $qPieSales = DB::table('keluarsf')
            ->join('denom', 'keluarsf.iddenom', '=', 'denom.iddenom')
            ->selectRaw('denom.group_name, SUM(keluarsf.qty) as total')
            ->whereBetween('keluarsf.tgl', [$startThisMonth, $endThisMonth])
            // ->whereNotIn('denom.group_name', ['SEGEL', 'VOICE', 'LAINNYA']) // Opt: Filter if needed
            ->groupBy('denom.group_name')
            ->orderBy('total', 'desc');
        $applyFilter($qPieSales, 'keluarsf.idtap');
        $pieSales = $qPieSales->get();

        // PIE INJECT
        $qPieInject = DB::table('injectvf')
            ->join('denom', 'injectvf.iddenom', '=', 'denom.iddenom')
            ->selectRaw('denom.group_name, SUM(injectvf.qty) as total')
            ->whereBetween('injectvf.tgl', [$startThisMonth, $endThisMonth])
            ->groupBy('denom.group_name')
            ->orderBy('total', 'desc');
        $applyFilter($qPieInject, 'injectvf.idtap');
        $pieInject = $qPieInject->get();

        /* ================= NEW: MoM INJECT SUMMARY (CLUSTER & GT) ================= */
        $momClusterInject = [];
        foreach ($clusterMap as $key => $taps) {
            $curr = DB::table('injectvf')->whereIn('idtap', $taps)->whereBetween('tgl', [$startThisMonth, $fairCutoffThisMonth])->sum('qty');
            $prev = DB::table('injectvf')->whereIn('idtap', $taps)->whereBetween('tgl', [$startPrevMonth, $endPrevMonthPartial])->sum('qty');
            $momClusterInject[$key] = $prev > 0 ? (($curr - $prev) / $prev) * 100 : 0;
        }

        $currGTInject = DB::table('injectvf')->whereBetween('tgl', [$startThisMonth, $fairCutoffThisMonth]);
        $applyFilter($currGTInject);
        $currGTInjectVal = $currGTInject->sum('qty');

        $prevGTInject = DB::table('injectvf')->whereBetween('tgl', [$startPrevMonth, $endPrevMonthPartial]);
        $applyFilter($prevGTInject);
        $prevGTInjectVal = $prevGTInject->sum('qty');
        $momGTInject = $prevGTInjectVal > 0 ? (($currGTInjectVal - $prevGTInjectVal) / $prevGTInjectVal) * 100 : 0;


        return view('home', compact(
            'mode',
            'selectedYear',
            'matrixSales',
            'matrixInject',
            'matrixSalesSf',
            'validityMatrixSales',
            'validityMatrixInject',
            'validityGroups',
            'pieSales',
            'pieInject',
            'stokSegel',
            'stokInject',
            'salesBulanIni',
            'mom',
            'chartSales',
            'chartInject',
            'momTap',
            'momSf',
            'topGrowth',
            'topDrop',
            'sfByTap',
            'tapMasuk',
            'tapKeluar',
            'tapList',
            'momCluster',
            'momDaily',
            'prevDecSales',
            'prevDecInject',
            'momTapInject',
            'currentDate',
            'cutoffDay',
            'momClusterInject',
            'momGTInject'
        ));
    }
}
