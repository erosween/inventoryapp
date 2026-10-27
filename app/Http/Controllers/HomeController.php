<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->query();
        ksort($query);
        $scope = strtoupper(trim((string) session('idtap')));
        $momOnly = $request->header('X-Surya-Card') === 'mom';
        $chartOnly = in_array($request->header('X-Surya-Card'), ['distribution-sales', 'distribution-inject'], true);
        $cacheKey = 'home-dashboard:v11:'.sha1($scope.'|'.$request->header('X-Surya-Card', 'full').'|'.http_build_query($query));

        $data = app()->environment('testing')
            ? $this->dashboardData($request)
            : Cache::remember($cacheKey, now()->addMinutes(15), fn () => $this->dashboardData($request));

        if ($chartOnly) return response()->json($data);
        return view($momOnly ? 'surya.partials.mom-sales-card' : 'home', $data);
    }

    private function dashboardData(Request $request): array
    {
        /* ================= MODE ================= */
        $mode = $request->query('mode', 'daily');
        $idtap = session('idtap');

        $applyFilter = function ($q, $col = 'idtap') use ($idtap) {
            if ($idtap === 'CLUSTER_DUMAI') {
                $q->whereIn($col, ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING']);
            } elseif ($idtap === 'CLUSTER_ROHIL') {
                $q->whereIn($col, ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG']);
            } elseif (! in_array($idtap, ['SBP_DUMAI', 'ALL TAP'], true)) {
                $q->where($col, $idtap);
            }
        };

        /* ================= DETECT CURRENT DATE IN SYSTEM ================= */
        $latestRecord = $this->combinedSalesQuery()->max('tgl');
        $currentDate = $latestRecord ? Carbon::parse($latestRecord) : now();

        /* ================= FAIR MOM CUTOFF: ALL PV, ALL TAP ================= */
        // Cutoff adalah input terakhir paling awal antar-TAP dari sfkeluar + mobile approved.
        $momCutoffQuery = $this->combinedSalesQuery()
            ->whereYear('tgl', $currentDate->year)
            ->whereMonth('tgl', $currentDate->month)
            ->whereExists(function ($productQuery) {
                $productQuery->selectRaw('1')
                    ->from('denom as product_denom')
                    ->whereColumn('product_denom.iddenom', 'combined_sales.iddenom')
                    ->whereRaw("UPPER(COALESCE(product_denom.kategori_inject, '')) <> 'SA'");
            })
            ->selectRaw('idtap, MAX(tgl) AS last_input')
            ->groupBy('idtap');
        $applyFilter($momCutoffQuery);
        $momCutoffDates = $momCutoffQuery->pluck('last_input');
        $fairCutoffThisMonth = $momCutoffDates->isEmpty()
            ? $currentDate->copy()
            : Carbon::parse($momCutoffDates->min());

        $request->validate(['mom_date' => 'nullable|date']);
        // Semua KPI MTD memakai cutoff global yang sama agar angka dashboard lurus.
        $momSelectedDate = $fairCutoffThisMonth->copy()->endOfDay();
        $momStartThisMonth = $momSelectedDate->copy()->startOfMonth();
        $momStartPrevMonth = $momSelectedDate->copy()->subMonthNoOverflow()->startOfMonth();
        $momEndPrevMonthFull = $momStartPrevMonth->copy()->endOfMonth();
        $isMomMonthEnd = $momSelectedDate->day === $momSelectedDate->daysInMonth;
        $momEndPrevMonthPartial = $isMomMonthEnd
            ? $momEndPrevMonthFull->copy()
            : $momStartPrevMonth->copy()
                ->day(min($momSelectedDate->day, $momEndPrevMonthFull->day))
                ->endOfDay();
        $momStartM2Month = $momSelectedDate->copy()->subMonthsNoOverflow(2)->startOfMonth();
        $momEndM2MonthFull = $momStartM2Month->copy()->endOfMonth();
        $momEndM2MonthPartial = $isMomMonthEnd
            ? $momEndM2MonthFull->copy()
            : $momStartM2Month->copy()
                ->day(min($momSelectedDate->day, $momEndM2MonthFull->day))
                ->endOfDay();

        $startThisMonth = $currentDate->copy()->startOfMonth();
        $endThisMonth = $currentDate->copy(); // Dipakai untuk akumulasi total (Sales Bulan Ini, Pie, dll)

        /* ================= FAIR GLOBAL CUTOFF (MoM 16 vs 16) ================= */
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

        $tapKeluar = $this->combinedSalesQuery()
            ->selectRaw('idtap, MAX(tgl) AS last_keluar')
            ->groupBy('idtap');
        $applyFilter($tapKeluar);
        $tapKeluar = $tapKeluar->pluck('last_keluar', 'idtap');

        // Sumber dan cakupannya sama dengan $tapKeluar, jadi tidak perlu scan UNION
        // penjualan sekali lagi hanya untuk mengambil daftar TAP.
        $tapList = $tapKeluar->keys()->sort()->values();

        /* ================= KPI ================= */
        $qSalesBulanIni = $this->combinedSalesQuery()->whereBetween('tgl', [$startThisMonth, $fairCutoffThisMonth->copy()->endOfDay()]);
        $applyFilter($qSalesBulanIni);
        $salesBulanIni = $qSalesBulanIni->sum('qty');

        $qSalesPrev = $this->combinedSalesQuery()->whereBetween('tgl', [$startPrevMonth, $endPrevMonthPartial]);
        $applyFilter($qSalesPrev);
        $salesPrev = $qSalesPrev->sum('qty');

        $mom = $salesPrev > 0
            ? (($salesBulanIni - $salesPrev) / $salesPrev) * 100
            : 0;

        /* ================= CHART SALES ================= */
        if ($mode === 'monthly') {
            $qChartSales = $this->combinedSalesQuery()
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
            $qChartSales = $this->combinedSalesQuery()
                ->selectRaw("DATE(tgl) AS label, SUM(qty) AS total")
                ->whereBetween('tgl', [$startThisMonth, $endThisMonth])
                ->groupBy(DB::raw('DATE(tgl)'))
                ->orderBy('label');
            $applyFilter($qChartSales);
            $chartSales = $qChartSales->get();
        }

        /* ================= CLUSTER SUMMARY ================= */
        $clusterMap = [
            'dumai_bengkalis' => ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING'],
            'rokan_hilir' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
        ];

        /* ================= MoM PER SF ================= */
        $buildMomSfSource = function (string $table) use (
            $applyFilter, $momStartThisMonth, $momSelectedDate,
            $momStartPrevMonth, $momEndPrevMonthPartial,
            $momStartM2Month, $momEndM2MonthPartial
        ) {
            $sfColumn = $table === 'mobile_penjualan'
                ? DB::raw('COALESCE(owner_idsf, idsf)')
                : 'idsf';
            $sfSelect = $table === 'mobile_penjualan'
                ? 'COALESCE(owner_idsf, idsf) AS idsf'
                : 'idsf';
            $query = DB::table($table)
                ->whereBetween('tgl', [$momStartM2Month, $momSelectedDate])
                ->selectRaw("{$sfSelect},
                    SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS curr_qty,
                    SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS prev_qty,
                    SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS m2_qty", [
                    $momStartThisMonth, $momSelectedDate,
                    $momStartPrevMonth, $momEndPrevMonthPartial,
                    $momStartM2Month, $momEndM2MonthPartial,
                ])
                ->groupBy($sfColumn);
            if ($table === 'mobile_penjualan') {
                $query->where('status', 'approved');
            }
            $applyFilter($query);

            return $query->get()->keyBy('idsf');
        };

        $legacyMomSf = $buildMomSfSource('keluarsf');
        $mobileMomSf = $buildMomSfSource('mobile_penjualan');
        $sfRoster = DB::table('idsf')->select('idtap', 'idsf', 'namasf');
        $applyFilter($sfRoster);
        $momSf = $sfRoster->get()
            ->map(function ($r) use ($legacyMomSf, $mobileMomSf) {
                $legacy = $legacyMomSf->get($r->idsf);
                $mobile = $mobileMomSf->get($r->idsf);
                $r->curr_qty = (int) ($legacy->curr_qty ?? 0) + (int) ($mobile->curr_qty ?? 0);
                $r->prev_qty = (int) ($legacy->prev_qty ?? 0) + (int) ($mobile->prev_qty ?? 0);
                $r->m2_qty = (int) ($legacy->m2_qty ?? 0) + (int) ($mobile->m2_qty ?? 0);
                $r->mom = $r->prev_qty > 0
                    ? (($r->curr_qty - $r->prev_qty) / $r->prev_qty) * 100
                    : 0;
                $r->mom_m2 = $r->m2_qty > 0
                    ? (($r->curr_qty - $r->m2_qty) / $r->m2_qty) * 100
                    : 0;
                return $r;
            });

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

        /* ================= COLLAPSE SELURUH PETUGAS BY TAP ================= */
        $sfByTap = $momSf
            ->groupBy('idtap')
            ->map(fn($rows) => $rows->sortBy('namasf')->values());

        /* ================= NEW: MATRIX TAHUNAN PER TAP ================= */
        $selectedYear = $request->query('year', date('Y'));
        $salesYear = (int) $request->query('sales_year', date('Y'));
        if ($salesYear < 2023 || $salesYear > (int) date('Y')) {
            $salesYear = (int) date('Y');
        }
        $salesView = $request->query('sales_view') === 'chart' ? 'chart' : 'table';
        $salesPeriod = $request->query('sales_period') === 'annual' ? 'annual' : 'monthly';
        $productFilterOptions = ['pv_all', 'pv_reg', 'pv_byu', 'sa_all', 'sa_simpati', 'sa_byu'];
        $distributionSalesProduct = (string) $request->query('distribution_sales_product', 'pv_all');
        $distributionInjectProduct = (string) $request->query('distribution_inject_product', 'pv_all');
        if (!in_array($distributionSalesProduct, $productFilterOptions, true)) $distributionSalesProduct = 'pv_all';
        if (!in_array($distributionInjectProduct, $productFilterOptions, true)) $distributionInjectProduct = 'pv_all';
        $momProductFilter = strtolower((string) $request->query('mom_product', 'pv_all'));
        if (!in_array($momProductFilter, $productFilterOptions, true)) $momProductFilter = 'pv_all';
        $salesProductFilter = strtolower((string) $request->query('sales_product', 'pv_all'));
        $injectProductFilter = strtolower((string) $request->query('inject_product', 'pv_all'));
        // Legacy value "all" must remain ALL PV, never an unfiltered sales table.
        if ($salesProductFilter === 'all') $salesProductFilter = 'pv_all';
        if ($injectProductFilter === 'all') $injectProductFilter = 'pv_all';
        if (!in_array($salesProductFilter, $productFilterOptions, true)) $salesProductFilter = 'pv_all';
        if (!in_array($injectProductFilter, $productFilterOptions, true)) $injectProductFilter = 'pv_all';

        $applyProductFilter = function ($query, string $table, string $filter) {
            if ($filter === 'all') return;

            $query->whereExists(function ($denomQuery) use ($table, $filter) {
                $denomQuery->selectRaw('1')
                    ->from('denom as product_denom')
                    ->whereColumn('product_denom.iddenom', $table.'.iddenom');

                if ($table === 'injectvf') {
                    $sourceCategories = match ($filter) {
                        'pv_all' => ['SEGEL', 'V33'],
                        'pv_reg' => ['SEGEL'],
                        'pv_byu' => ['V33'],
                        'sa_all' => ['SP6', 'SP7'],
                        'sa_simpati' => ['SP6'],
                        'sa_byu' => ['SP7'],
                        default => [],
                    };
                    $denomQuery->whereIn($table.'.kategori', $sourceCategories);
                } elseif ($filter === 'pv_all') {
                    $denomQuery->whereRaw("UPPER(COALESCE(product_denom.kategori_inject, '')) <> 'SA'");
                } elseif ($filter === 'pv_reg') {
                    $denomQuery->whereRaw("UPPER(COALESCE(product_denom.kategori_inject, '')) NOT IN ('BYU', 'SA')");
                } elseif ($filter === 'pv_byu') {
                    $denomQuery->whereRaw("UPPER(COALESCE(product_denom.kategori_inject, '')) = 'BYU'");
                } elseif ($filter === 'sa_all') {
                    $denomQuery->whereRaw("UPPER(COALESCE(product_denom.kategori_inject, '')) = 'SA'");
                } elseif ($filter === 'sa_simpati') {
                    $denomQuery->whereRaw("UPPER(COALESCE(product_denom.kategori_inject, '')) = 'SA'")
                        ->whereRaw("UPPER(COALESCE(product_denom.denom, '')) NOT LIKE 'SA BYU%'");
                } elseif ($filter === 'sa_byu') {
                    $denomQuery->whereRaw("UPPER(COALESCE(product_denom.kategori_inject, '')) = 'SA'")
                        ->whereRaw("UPPER(COALESCE(product_denom.denom, '')) LIKE 'SA BYU%'");
                }
            });
        };

        $chartKind = $request->header('X-Surya-Card');
        $pieSales = collect();
        $pieInject = collect();
        if ($chartKind !== 'mom') {
            foreach (['sales', 'inject'] as $kind) {
                if (str_starts_with((string) $chartKind, 'distribution-') && $chartKind !== 'distribution-'.$kind) continue;
                $source = $kind === 'sales' ? 'combined_sales' : 'injectvf';
                $query = $kind === 'sales' ? $this->combinedSalesQuery() : DB::table('injectvf');
                $query->join('denom as distribution_denom', $source.'.iddenom', '=', 'distribution_denom.iddenom')
                    ->whereBetween($source.'.tgl', [$momStartThisMonth, $momSelectedDate])
                    ->selectRaw('distribution_denom.group_name, SUM('.$source.'.qty) AS total')
                    ->groupBy('distribution_denom.group_name')->orderByDesc('total');
                $applyFilter($query, $source.'.idtap');
                $applyProductFilter($query, $source, $kind === 'sales' ? $distributionSalesProduct : $distributionInjectProduct);
                if ($kind === 'sales') $pieSales = $query->get(); else $pieInject = $query->get();
            }
            if (in_array($chartKind, ['distribution-sales', 'distribution-inject'], true)) {
                $rows = $chartKind === 'distribution-sales' ? $pieSales : $pieInject;
                return ['labels' => $rows->pluck('group_name'), 'values' => $rows->pluck('total'), 'cutoff' => $momSelectedDate->toDateString()];
            }
        }

        // KPI penjualan bulan berjalan dipisah agar total PV tidak tercampur dengan SA.
        $salesBulanIniByProduct = [];
        foreach (['pv_all', 'sa_all'] as $product) {
            $qSalesBulanIniProduct = $this->combinedSalesQuery()
                ->whereBetween('tgl', [$startThisMonth, $fairCutoffThisMonth->copy()->endOfDay()]);
            $applyFilter($qSalesBulanIniProduct);
            $applyProductFilter($qSalesBulanIniProduct, 'combined_sales', $product);
            $salesBulanIniByProduct[$product] = (int) $qSalesBulanIniProduct->sum('qty');
        }
        $salesBulanIniPv = $salesBulanIniByProduct['pv_all'];
        $salesBulanIniSa = $salesBulanIniByProduct['sa_all'];

        $momByProduct = [];
        foreach (['pv_all' => $salesBulanIniPv, 'sa_all' => $salesBulanIniSa] as $product => $currentQty) {
            $qSalesPrevProduct = $this->combinedSalesQuery()
                ->whereBetween('tgl', [$startPrevMonth, $endPrevMonthPartial]);
            $applyFilter($qSalesPrevProduct);
            $applyProductFilter($qSalesPrevProduct, 'combined_sales', $product);
            $previousQty = (int) $qSalesPrevProduct->sum('qty');
            $momByProduct[$product] = $previousQty > 0
                ? (($currentQty - $previousQty) / $previousQty) * 100
                : 0;
        }
        $momPv = $momByProduct['pv_all'];
        $momSa = $momByProduct['sa_all'];

        // Use the exact historical balance and visible denominations from Stock Gudang.
        $stockSnapshot = app(sisaStockController::class)->data(new Request([
            'date' => $momSelectedDate->toDateString(), 'mode' => 'all',
        ]), true);
        $stockDenoms = $stockSnapshot['denoms'];
        $matchesStockProduct = static function ($denom, string $filter): bool {
            $isSa = strtoupper((string) $denom->group_name) === 'SA';
            $isByu = strtoupper((string) $denom->kategori_inject) === 'BYU';
            $isSaByu = str_starts_with(strtoupper((string) $denom->denom), 'SA BYU');
            return match ($filter) {
                'pv_all' => !$isSa,
                'pv_reg' => !$isSa && !$isByu,
                'pv_byu' => !$isSa && $isByu,
                'sa_all' => $isSa,
                'sa_simpati' => $isSa && !$isSaByu,
                'sa_byu' => $isSa && $isSaByu,
                default => false,
            };
        };
        $selectedStockDenoms = $stockDenoms->filter(fn ($d) => $matchesStockProduct($d, $momProductFilter));
        $stockGudangByTap = $stockSnapshot['rows']->mapWithKeys(function ($row) use ($selectedStockDenoms) {
            return [$row['idtap'] => $selectedStockDenoms->sum(fn ($d) => (int) ($row[$d->iddenom] ?? 0))];
        });
        $stockTotals = ['pv_segel'=>0, 'pv_inject'=>0, 'sa_segel'=>0, 'sa_inject'=>0];
        foreach ($stockDenoms as $denom) {
            $family = strtoupper((string) $denom->group_name) === 'SA' ? 'sa' : 'pv';
            $sealed = in_array($denom->iddenom, ['SEGEL','V16','V33','SP6','SP7'], true)
                || strtoupper((string) $denom->group_name) === 'SEGEL';
            $stockTotals[$family.'_'.($sealed ? 'segel' : 'inject')] += $stockSnapshot['rows']->sum(fn ($row) => (int) ($row[$denom->iddenom] ?? 0));
        }
        $stokSegel = $stockTotals['pv_segel'];
        $stokInject = $stockTotals['pv_inject'];
        $stokSaSegel = $stockTotals['sa_segel'];
        $stokSaInject = $stockTotals['sa_inject'];

        $qMomTapSales = $this->combinedSalesQuery()
            ->whereBetween('tgl', [$momStartPrevMonth, $momSelectedDate])
            ->selectRaw('idtap,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS curr_qty,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS prev_partial_qty,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS prev_full_qty', [
                $momStartThisMonth, $momSelectedDate,
                $momStartPrevMonth, $momEndPrevMonthPartial,
                $momStartPrevMonth, $momEndPrevMonthFull,
            ])
            ->groupBy('idtap');
        $applyFilter($qMomTapSales);
        $qMomSelectedProduct = clone $qMomTapSales;
        $applyProductFilter($qMomTapSales, 'combined_sales', 'pv_all');
        $momTapSales = $qMomTapSales->get()->map(function ($row) {
            $row->mom = $row->prev_partial_qty > 0
                ? (($row->curr_qty - $row->prev_partial_qty) / $row->prev_partial_qty) * 100
                : 0;
            return $row;
        });
        // Satu sumber angka untuk card PV, tabel per-TAP, dan Grand Total MoM.
        $salesBulanIniPv = (int) $momTapSales->sum('curr_qty');
        $momPvPrevious = (int) $momTapSales->sum('prev_partial_qty');
        $momPv = $momPvPrevious > 0
            ? (($salesBulanIniPv - $momPvPrevious) / $momPvPrevious) * 100
            : 0;

        $momClusterSales = [];
        $momClusterSalesRows = [];
        foreach ($clusterMap as $key => $taps) {
            $clusterSales = $momTapSales->whereIn('idtap', $taps);
            $currQty = (int) $clusterSales->sum('curr_qty');
            $prevQty = (int) $clusterSales->sum('prev_partial_qty');
            $prevFullQty = (int) $clusterSales->sum('prev_full_qty');
            $momClusterSales[$key] = $prevQty > 0
                ? (($currQty - $prevQty) / $prevQty) * 100
                : 0;
            $momClusterSalesRows[$key] = (object) [
                'curr_qty' => $currQty,
                'prev_partial_qty' => $prevQty,
                'prev_full_qty' => $prevFullQty,
                'mom' => $momClusterSales[$key],
            ];
        }
        $applyProductFilter($qMomSelectedProduct, 'combined_sales', $momProductFilter);
        $momProductRows = $momProductFilter === 'pv_all' ? $momTapSales : $qMomSelectedProduct->get()->map(function ($row) {
            $row->mom = $row->prev_partial_qty > 0 ? (($row->curr_qty - $row->prev_partial_qty) / $row->prev_partial_qty) * 100 : 0;
            return $row;
        });
        $momProductClusters = [];
        foreach ($clusterMap as $key => $taps) {
            $rows = $momProductRows->whereIn('idtap', $taps);
            $curr = (int) $rows->sum('curr_qty');
            $prev = (int) $rows->sum('prev_partial_qty');
            $momProductClusters[$key] = (object) [
                'curr_qty' => $curr, 'prev_partial_qty' => $prev,
                'prev_full_qty' => (int) $rows->sum('prev_full_qty'),
                'mom' => $prev > 0 ? (($curr - $prev) / $prev) * 100 : 0,
            ];
        }
        $momSfQuery = $this->combinedSalesQuery()
            ->whereBetween('tgl', [$momStartPrevMonth, $momSelectedDate])
            ->selectRaw('idsf, SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS curr_qty,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS prev_qty,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS prev_full_qty', [
                $momStartThisMonth, $momSelectedDate, $momStartPrevMonth, $momEndPrevMonthPartial,
                $momStartPrevMonth, $momEndPrevMonthFull,
            ])->groupBy('idsf');
        $applyFilter($momSfQuery);
        $applyProductFilter($momSfQuery, 'combined_sales', $momProductFilter);
        $momSfTotals = $momSfQuery->get()->keyBy('idsf');
        $sfSnapshot = app(sisaStockController::class)->data(new Request([
            'date' => $momSelectedDate->toDateString(), 'mode' => 'sf',
        ]), true);
        $sfStocks = $sfSnapshot['rows']->mapWithKeys(function ($row) use ($selectedStockDenoms) {
            return [$row['idsf'] => $selectedStockDenoms->sum(fn ($d) => (int) ($row[$d->iddenom] ?? 0))];
        });
        $momProductSfByTap = $momSf->map(function ($sf) use ($momSfTotals, $sfStocks, $momSelectedDate) {
            $row = clone $sf;
            $totals = $momSfTotals->get($sf->idsf);
            $row->curr_qty = (int) ($totals->curr_qty ?? 0);
            $row->prev_qty = (int) ($totals->prev_qty ?? 0);
            $row->prev_full_qty = (int) ($totals->prev_full_qty ?? 0);
            $row->daily_st = $row->curr_qty / max(1, $momSelectedDate->day);
            $row->stock_qty = (float) ($sfStocks[$sf->idsf] ?? 0);
            $row->stock_days = $row->daily_st > 0 ? $row->stock_qty / $row->daily_st : null;
            $row->mom = $row->prev_qty > 0 ? (($row->curr_qty - $row->prev_qty) / $row->prev_qty) * 100 : 0;
            return $row;
        })->groupBy('idtap');

        $annualCutoff = $request->query('sales_annual_cutoff', 'full');
        if ($annualCutoff !== 'full' && (! ctype_digit((string) $annualCutoff) || (int) $annualCutoff < 1 || (int) $annualCutoff > 12)) {
            $annualCutoff = 'full';
        }
        // Product changes only need the MoM card: skip annual, matrix, validity,
        // inject and chart datasets that are otherwise computed below.
        if ($request->header('X-Surya-Card') === 'mom') {
            return compact('momProductFilter', 'momProductRows', 'momProductClusters',
                'momProductSfByTap', 'stockGudangByTap', 'momSelectedDate',
                'momEndPrevMonthPartial', 'fairCutoffThisMonth', 'salesProductFilter',
                'injectProductFilter', 'selectedYear', 'salesYear', 'salesView',
                'salesPeriod', 'annualCutoff', 'mode');
        }
        if ($salesPeriod === 'annual') {
            $salesYear = (int) date('Y');
        }

        $qMatrixData = $this->combinedSalesQuery()
            ->selectRaw('idtap, MONTH(tgl) as bulan, SUM(qty) as total')
            ->whereYear('tgl', $salesYear)
            ->groupBy('idtap', DB::raw('MONTH(tgl)'));
        if ($salesYear === $currentDate->year) {
            $qMatrixData->where(function ($query) use ($currentDate, $momSelectedDate) {
                $query->whereMonth('tgl', '<', $currentDate->month)
                    ->orWhere(function ($monthQuery) use ($currentDate, $momSelectedDate) {
                        $monthQuery->whereMonth('tgl', $currentDate->month)
                            ->where('tgl', '<=', $momSelectedDate);
                    });
            });
        }
        $applyFilter($qMatrixData);
        $applyProductFilter($qMatrixData, 'combined_sales', $salesProductFilter);
        $matrixSalesData = $qMatrixData->get();

        $matrixSales = [];
        foreach ($tapList as $tap) {
            $matrixSales[$tap] = array_fill(1, 12, 0); // isi 0 untuk 12 bln
        }
        foreach ($matrixSalesData as $row) {
            $matrixSales[$row->idtap][$row->bulan] = $row->total;
        }
        // ALL PV bulan berjalan harus identik dengan dataset MoM bawah (cutoff yang sama).
        if ($salesProductFilter === 'pv_all' && $salesYear === $currentDate->year) {
            foreach ($tapList as $tap) {
                $momRow = $momTapSales->firstWhere('idtap', $tap);
                $matrixSales[$tap][$currentDate->month] = (int) ($momRow->curr_qty ?? 0);
            }
        }

        /* ================= SALES TAHUNAN PER TAP ================= */
        $annualSalesData = DB::table('keluarsf')
            ->selectRaw('idtap, YEAR(tgl) as tahun, SUM(qty) as total')
            ->whereBetween(DB::raw('YEAR(tgl)'), [2024, $salesYear])
            ->groupBy('idtap', DB::raw('YEAR(tgl)'));
        if ($annualCutoff !== 'full') {
            $annualSalesData->whereMonth('tgl', '<=', (int) $annualCutoff);
        }
        $applyFilter($annualSalesData);
        $applyProductFilter($annualSalesData, 'keluarsf', $salesProductFilter);

        $annualSales = [];
        foreach ($tapList as $tap) {
            $annualSales[$tap] = [];
        }
        foreach ($annualSalesData->get() as $row) {
            $annualSales[$row->idtap][(int) $row->tahun] = (int) $row->total;
        }

        /*
         * Fair cutoff: ambil tanggal input terakhir masing-masing TAP, lalu pilih
         * yang paling rendah. Contoh 7 TAP tanggal 18 dan 1 TAP tanggal 17 => 17.
         */
        $annualComparisonYear = (int) date('Y');
        $annualComparisonMonth = $annualCutoff === 'full'
            ? (int) $currentDate->month
            : (int) $annualCutoff;
        $annualTapLatestDates = DB::table('keluarsf')
            ->selectRaw('idtap, MAX(tgl) AS last_input')
            ->whereYear('tgl', $annualComparisonYear)
            ->whereMonth('tgl', $annualComparisonMonth)
            ->groupBy('idtap');
        $applyFilter($annualTapLatestDates);
        $applyProductFilter($annualTapLatestDates, 'keluarsf', $salesProductFilter);
        $annualLowestLatestDate = $annualTapLatestDates->pluck('last_input')->filter()->min();
        $annualComparisonDate = $annualLowestLatestDate
            ? Carbon::parse($annualLowestLatestDate)->endOfDay()
            : Carbon::create($annualComparisonYear, $annualComparisonMonth, 1)->endOfMonth()->endOfDay();
        $annualComparisonDay = (int) $annualComparisonDate->day;

        /* YTD setiap tahun memakai bulan dan hari cutoff yang sama. */
        $annualCurrentYtdSales = [];
        foreach ($tapList as $tap) {
            $annualCurrentYtdSales[$tap] = [];
        }
        $annualCurrentYtdQuery = DB::table('keluarsf')
            ->selectRaw('idtap, YEAR(tgl) as tahun, SUM(qty) as total')
            ->whereBetween(DB::raw('YEAR(tgl)'), [2024, $annualComparisonYear])
            ->where(function ($query) use ($annualComparisonMonth, $annualComparisonDay) {
                $query->whereMonth('tgl', '<', $annualComparisonMonth)
                    ->orWhere(function ($monthQuery) use ($annualComparisonMonth, $annualComparisonDay) {
                        $monthQuery->whereMonth('tgl', $annualComparisonMonth)
                            ->whereDay('tgl', '<=', $annualComparisonDay);
                    });
            })
            ->groupBy('idtap', DB::raw('YEAR(tgl)'));
        $applyFilter($annualCurrentYtdQuery);
        $applyProductFilter($annualCurrentYtdQuery, 'keluarsf', $salesProductFilter);
        foreach ($annualCurrentYtdQuery->get() as $row) {
            $annualCurrentYtdSales[$row->idtap][(int) $row->tahun] = (int) $row->total;
        }

        $annualPeriodComparisons = [];
        if ($annualComparisonMonth >= 1) {
            $comparisonMonth = $annualComparisonMonth;
            $comparisonYear = $annualComparisonYear;
            $previousComparisonDate = Carbon::create($comparisonYear, $comparisonMonth, 1)->subMonth();
            $currentPeriodStart = Carbon::create($comparisonYear, $comparisonMonth, 1)->startOfDay();
            $currentPeriodEnd = $annualComparisonDate->copy();
            $previousYearStart = $currentPeriodStart->copy()->subYear();
            $previousYearEnd = $previousYearStart->copy()
                ->day(min($annualComparisonDay, $previousYearStart->daysInMonth))
                ->endOfDay();
            $previousMonthStart = $previousComparisonDate->copy()->startOfMonth();
            $previousMonthEnd = $previousMonthStart->copy()
                ->day(min($annualComparisonDay, $previousMonthStart->daysInMonth))
                ->endOfDay();
            $comparisonQuery = DB::table('keluarsf')
                ->whereBetween('tgl', [$previousYearStart, $currentPeriodEnd])
                ->selectRaw('idtap,
                    SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS current_month,
                    SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS previous_year_month,
                    SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS previous_month', [
                    $currentPeriodStart, $currentPeriodEnd,
                    $previousYearStart, $previousYearEnd,
                    $previousMonthStart, $previousMonthEnd,
                ])
                ->groupBy('idtap');
            $applyFilter($comparisonQuery);
            $applyProductFilter($comparisonQuery, 'keluarsf', $salesProductFilter);
            foreach ($comparisonQuery->get() as $row) {
                $annualPeriodComparisons[$row->idtap] = [
                    'current_month' => (int) $row->current_month,
                    'previous_year_month' => (int) $row->previous_year_month,
                    'previous_month' => (int) $row->previous_month,
                ];
            }
        }

        /* ================= NEW: PREV YEAR DECEMBER FOR JAN MoM ================= */
        $prevDecSalesData = DB::table('keluarsf')
            ->selectRaw('idtap, SUM(qty) as total')
            ->whereYear('tgl', $salesYear - 1)
            ->whereMonth('tgl', 12)
            ->groupBy('idtap');
        $applyFilter($prevDecSalesData);
        $applyProductFilter($prevDecSalesData, 'keluarsf', $salesProductFilter);
        $prevDecSales = $prevDecSalesData->pluck('total', 'idtap');

        $prevDecInjectData = DB::table('injectvf')
            ->selectRaw('idtap, SUM(qty) as total')
            ->whereYear('tgl', $selectedYear - 1)
            ->whereMonth('tgl', 12)
            ->groupBy('idtap');
        $applyFilter($prevDecInjectData);
        $applyProductFilter($prevDecInjectData, 'injectvf', $injectProductFilter);
        $prevDecInject = $prevDecInjectData->pluck('total', 'idtap');

        /* ================= NEW: MoM INJECT PER TAP (FAIR CUTOFF) ================= */
        $qMomTapInject = DB::table('injectvf')
            ->whereBetween('tgl', [$startPrevMonth, $fairCutoffThisMonth])
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
        $applyProductFilter($qMomTapInject, 'injectvf', $injectProductFilter);
        $momTapInjectRows = $qMomTapInject->get();
        $momTapInject = $momTapInjectRows
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
        $applyProductFilter($qMatrixInjectData, 'injectvf', $injectProductFilter);
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
            ->whereYear('tgl', $salesYear)
            ->groupBy('idsf', DB::raw('MONTH(tgl)'));
        $applyFilter($qMatrixSalesSfData, 'idtap');
        $applyProductFilter($qMatrixSalesSfData, 'keluarsf', $salesProductFilter);
        $matrixSalesSfResults = $qMatrixSalesSfData->get();

        $matrixSalesSf = [];
        foreach ($matrixSalesSfResults as $row) {
            $matrixSalesSf[$row->idsf][$row->bulan] = $row->total;
        }

        /* ================= NEW: VALIDITY MATRIX FOR HEATMAP EXPANSION ================= */
        $validityGroups = ['SEGEL', 'UNLIMITED 6 JAM', 'UNLIMITED 12 JAM', 'UNLIMITED 24 JAM', 'UNLIMITED 48 JAM', '1 HARI', '2 HARI', '3 HARI', '5 HARI', '7 HARI', '14 HARI', '28 HARI', '30 HARI', 'VOICE'];

        $fetchValidityMatrix = function ($table, $year, $applyFilter, $applyProductFilter, $productFilter) use ($validityGroups) {
            $data = DB::table($table)
                ->join('denom', $table . '.iddenom', '=', 'denom.iddenom')
                ->selectRaw('idtap, denom.group_name, MONTH(tgl) as bulan, SUM(qty) as total')
                ->whereYear('tgl', $year)
                ->whereIn('denom.group_name', $validityGroups)
                ->groupBy('idtap', 'denom.group_name', DB::raw('MONTH(tgl)'));
            $applyFilter($data, $table . '.idtap');
            $applyProductFilter($data, $table, $productFilter);
            $rows = $data->get();

            $matrix = [];
            foreach ($rows as $row) {
                $matrix[$row->idtap][$row->group_name][$row->bulan] = $row->total;
            }
            return $matrix;
        };

        $validityMatrixSales = $fetchValidityMatrix('keluarsf', $salesYear, $applyFilter, $applyProductFilter, $salesProductFilter);
        $validityMatrixInject = $fetchValidityMatrix('injectvf', $selectedYear, $applyFilter, $applyProductFilter, $injectProductFilter);


        /* ================= NEW: MoM INJECT SUMMARY (CLUSTER & GT) ================= */
        $momClusterInject = [];
        foreach ($clusterMap as $key => $taps) {
            $clusterInject = $momTapInjectRows->whereIn('idtap', $taps);
            $curr = (int) $clusterInject->sum('curr_qty');
            $prev = (int) $clusterInject->sum('prev_partial_qty');
            $momClusterInject[$key] = $prev > 0 ? (($curr - $prev) / $prev) * 100 : 0;
        }
        $currGTInjectVal = (int) $momTapInjectRows->sum('curr_qty');
        $prevGTInjectVal = (int) $momTapInjectRows->sum('prev_partial_qty');
        $momGTInject = $prevGTInjectVal > 0 ? (($currGTInjectVal - $prevGTInjectVal) / $prevGTInjectVal) * 100 : 0;


        return compact(
            'stokSaSegel', 'stokSaInject',
            'distributionSalesProduct', 'distributionInjectProduct',
            'momProductFilter', 'momProductRows', 'momProductClusters', 'momProductSfByTap',
            'mode',
            'selectedYear',
            'salesYear',
            'salesView',
            'salesPeriod',
            'salesProductFilter',
            'injectProductFilter',
            'momTapSales',
            'momClusterSales',
            'momClusterSalesRows',
            'annualSales',
            'annualCurrentYtdSales',
            'annualComparisonMonth',
            'annualComparisonDate',
            'annualCutoff',
            'annualPeriodComparisons',
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
            'salesBulanIniPv',
            'salesBulanIniSa',
            'momPv',
            'momSa',
            'stockGudangByTap',
            'mom',
            'chartSales',
            'momSf',
            'topGrowth',
            'topDrop',
            'sfByTap',
            'tapMasuk',
            'tapKeluar',
            'tapList',
            'prevDecSales',
            'prevDecInject',
            'momTapInject',
            'currentDate',
            'cutoffDay',
            'fairCutoffThisMonth',
            'momSelectedDate',
            'momEndPrevMonthPartial',
            'momClusterInject',
            'momGTInject'
        );
    }

    /**
     * Unified sales source for legacy SF stock-outs and approved Surya sales.
     */
    private function combinedSalesQuery()
    {
        $stockOut = DB::table('keluarsf')->select([
            'tgl', 'idtap', 'idsf', 'iddenom', 'qty',
        ]);

        $surya = DB::table('mobile_penjualan')
            ->where('status', 'approved')
            ->select([
                'tgl', 'idtap', DB::raw('COALESCE(owner_idsf, idsf) AS idsf'), 'iddenom', 'qty',
            ]);

        return DB::query()->fromSub($stockOut->unionAll($surya), 'combined_sales');
    }
}
