@extends('layout.layout')

@section('content')
<style>
    .kpi-card { border: none; border-radius: 12px; transition: transform 0.2s; }
    .kpi-card:hover { transform: translateY(-3px); }
    .enterprise-shadow { box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-radius: 10px; border:1px solid #f0f0f0; }
    .heatmap-cell { transition: all 0.2s; }
    .heatmap-cell:hover { opacity: 0.8; font-weight: bold; cursor: pointer; }
    .feed-item { border-left: 3px solid #e0e0e0; padding-left: 15px; margin-bottom: 12px; position:relative; }
    .feed-item::before { content:''; position:absolute; left:-7px; top:5px; width:11px; height:11px; border-radius:50%; background:#fff; border:2px solid #4e73df; }
    .feed-item.danger-feed::before { border-color:#e74a3b; }
    .icon-bg { padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.2); }

    /* HEATMAP FREEZE BOX */
    .table-scroll-heatmap {
        overflow: auto;
        /* max-height removed to allow full page view */
    }

    /* COMPACT HEATMAP */
    .table-scroll-heatmap td, .table-scroll-heatmap th {
        padding: 6px 8px !important;
        vertical-align: middle !important;
    }

    /* STICKY HEADER */
    .table-scroll-heatmap thead th {
        position: sticky;
        top: 0;
        z-index: 100 !important;
        background: #f8fafc !important;
        border-bottom: 2px solid #ddd !important;
    }

    /* STICKY FOOTER (CLUSTER & GRAND TOTAL) */
    .table-scroll-heatmap tfoot tr td {
        position: sticky;
        bottom: 0;
        z-index: 100 !important;
        background: #f8fafc !important; /* Solid background to prevent bleed-through */
        box-shadow: 0 -4px 8px rgba(0,0,0,0.05);
        border-top: 2px solid #ddd !important;
    }

    

    

    /* Header specific overrides */
    .table-scroll-heatmap thead th.sticky-tap-col { z-index: 110 !important; }
    .table-scroll-heatmap thead th.sticky-validity-col { z-index: 105 !important; background: #f8fafc !important; }

    /* HEATMAP STICKY SYSTEM */
    .sticky-tap-col, .sticky-validity-col {
        position: sticky !important;
        left: 0 !important;
        white-space: nowrap !important;
        overflow: hidden;
        text-overflow: ellipsis;
        background: #fff !important;
        box-shadow: 4px 0 8px rgba(0,0,0,0.06);
    }
    .sticky-tap-col {
        z-index: 95 !important;
        width: 240px !important;
        min-width: 240px !important;
    }
    .sticky-validity-col {
        z-index: 90 !important;
        width: 240px !important;
        min-width: 240px !important;
        padding-left: 45px !important;
    }
    .table-scroll-heatmap thead th.sticky-tap-col { z-index: 110 !important; background: #f8fafc !important; }
    .table-scroll-heatmap thead th.sticky-validity-col { z-index: 105 !important; background: #f8fafc !important; }
    .table-scroll-heatmap tfoot td.sticky-tap-col { z-index: 110 !important; background: #f8fafc !important; }
    .export-actions {
        gap: 10px;
        flex-wrap: wrap;
    }
    .export-action-btn {
        border: 0 !important;
        border-radius: 14px !important;
        padding: 9px 18px !important;
        min-width: 86px;
        background: #f1f3f5 !important;
        color: #1f2933 !important;
        font-weight: 700 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        line-height: 1.2 !important;
        vertical-align: middle !important;
        gap: 6px !important;
        box-shadow: inset 0 -1px 0 rgba(0,0,0,0.04), 0 4px 12px rgba(31,41,51,0.06);
    }
    .export-action-btn:hover {
        background: #e9ecef !important;
        color: #111827 !important;
        transform: translateY(-1px);
    }
    .export-action-btn i {
        flex: 0 0 auto;
        opacity: 0.72;
        margin: 0 !important;
    }
    .export-status { display: none; font-weight: 600; }

    .monthly-sales-view-toggle {
        display: inline-flex;
        padding: 3px;
        border-radius: 12px;
        background: #eef1f5;
        gap: 2px;
    }
    .monthly-sales-view-btn {
        min-width: 74px;
        padding: 7px 13px !important;
        border: 0 !important;
        border-radius: 9px !important;
        color: #6b7280 !important;
        background: transparent !important;
        font-weight: 700 !important;
        box-shadow: none !important;
    }
    .monthly-sales-view-btn.active {
        color: #fff !important;
        background: #4e73df !important;
        box-shadow: 0 3px 8px rgba(78, 115, 223, .22) !important;
    }
    #monthly-sales-chart-wrap {
        height: 430px;
        padding: 22px 24px 18px;
    }
    #heatmap-sales-card {
        scroll-margin-top: 88px;
    }
    .annual-sales-table tfoot td {
        position: static !important;
        box-shadow: none !important;
    }
    .monthly-sales-filter-form {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .monthly-sales-year-select {
        min-width: 126px;
        border-radius: 10px !important;
        border-color: #e5e7eb !important;
    }
    .sales-reload-btn {
        min-width: 42px !important;
        padding-left: 12px !important;
        padding-right: 12px !important;
        color: #4b5563 !important;
        font-size: 14px !important;
    }



    /* MOM SALES TAP - polished web table layout */
    #mom-cluster-card .card-header {
        padding: 16px 20px 14px !important;
    }
    #mom-cluster-table-wrap {
        overflow-x: auto;
    }
    #mom-cluster-table-wrap > table {
        table-layout: fixed;
        min-width: 920px;
    }
    #mom-cluster-table-wrap th,
    #mom-cluster-table-wrap td {
        padding: 9px 14px !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }
    #mom-cluster-table-wrap thead th {
        background: #f8fafc;
        color: #4b5563;
        font-size: 11.5px;
        letter-spacing: .02em;
        text-transform: uppercase;
        border-bottom: 1px solid #edf0f2 !important;
    }
    #mom-cluster-table-wrap tbody td,
    #mom-cluster-table-wrap tfoot td {
        font-size: 13px;
        border-top: 1px solid #edf0f2 !important;
    }
    #mom-cluster-table-wrap .mom-location-col { width: 34%; }
    #mom-cluster-table-wrap .mom-number-col { width: 16.5%; }
    #mom-cluster-table-wrap .mom-growth-col { width: 16.5%; }
    #mom-cluster-table-wrap .toggle-sf {
        width: 24px;
        height: 24px;
        line-height: 1;
        padding: 0 !important;
        margin-right: 9px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #374151;
        background: #ffffff;
        border-color: #e5e7eb !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }
    #mom-cluster-table-wrap tfoot tr {
        background: #f8fafc;
    }
    #mom-cluster-table-wrap tfoot tr:last-child {
        background: #e3f2fd;
    }
    #mom-cluster-table-wrap .sf-row > td > div {
        padding: 10px 14px !important;
    }
    #mom-cluster-table-wrap .badge {
        font-weight: 700;
        letter-spacing: .01em;
    }

    @media (max-width: 576px) {
        .export-status { text-align: center; }
        #monthly-sales-chart-wrap {
            height: 360px;
            padding: 16px 10px;
        }
    }
</style>

<div class="main-panel">
    <div class="content">
        <div class="page-inner">

            {{-- ================= TOP CONTROL BAR ================= --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0 text-dark">Executive Summary</h4>
                <form action="" method="GET" class="d-flex shadow-sm rounded">
                    <input type="hidden" name="sales_year" value="{{ $salesYear }}">
                    <input type="hidden" name="sales_view" value="{{ $salesView }}">
                    <input type="hidden" name="sales_period" value="{{ $salesPeriod }}">
                    <input type="hidden" name="sales_annual_cutoff" value="{{ $annualCutoff }}">
                    <select name="year" class="form-control mr-2 border-0 bg-white text-dark font-weight-bold" onchange="this.form.submit()">
                        @for($i = date('Y'); $i >= 2023; $i--)
                            <option value="{{ $i }}" {{ $selectedYear == $i ? 'selected' : '' }}>Tahun {{ $i }}</option>
                        @endfor
                    </select>
                    <select name="mode" class="form-control border-0 bg-white text-dark font-weight-bold" onchange="this.form.submit()">
                        <option value="daily" {{ $mode == 'daily' ? 'selected' : '' }}>Harian</option>
                        <option value="monthly" {{ $mode == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                    </select>
                </form>
            </div>

            {{-- ================= KPI ================= --}}
            <div class="row">
                @php
                    $cards = [
                        ['STOK SEGEL', $stokSegel, '#4285F4', 'shield', 'Total stok aktif di sistem'],
                        ['STOK INJECT', $stokInject, '#0F9D58', 'zap', 'Total PV Injected'],
                        ['SALES BULAN INI', $salesBulanIni, '#8E24AA', 'bar-chart', 'Akumulasi MTD Berjalan'],
                        [
                            'MoM GROWTH',
                            number_format($mom, 2) . ' %',
                            $mom >= 0 ? '#F4B400' : '#DB4437',
                            $mom >= 0 ? 'trending-up' : 'trending-down',
                            $mom >= 0 ? '📈 Kinerja Naik vs Bulan Lalu' : '⚠️ Perhatian: Kinerja Turun',
                        ],
                    ];
                @endphp

                @foreach ($cards as $c)
                    <div class="col-md-3 mb-4">
                        <div class="card kpi-card text-white h-100" style="background: {{ $c[2] }}; box-shadow: 0 5px 15px {{ $c[2] }}40;">
                            <div class="card-body py-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="w-100">
                                        <small class="text-uppercase font-weight-bold" style="letter-spacing:1px; opacity:0.85">{{ $c[0] }}</small>
                                        <h2 class="mb-1 mt-2 font-weight-bold" style="font-size:2rem; text-shadow: 0 2px 4px rgba(0,0,0,0.1)">
                                            {{ is_numeric($c[1]) ? number_format($c[1]) : $c[1] }}
                                        </h2>
                                        <small style="opacity: 0.9">{{ $c[4] }}</small>
                                    </div>
                                    <div class="icon-bg">
                                        <i data-feather="{{ $c[3] }}" style="width:36px;height:36px;opacity:1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ================= DOUGHNUT CHARTS (VALIDITY DISTRIBUTION) ================= --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card enterprise-shadow h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-0">
                            <h6 class="font-weight-bold text-dark mb-0">Distribusi Sales (Bulan Berjalan)</h6>
                            <small class="text-muted">Porsi penjualan per validity / grup</small>
                        </div>
                        <div class="card-body">
                            <div style="height:260px">
                                <canvas id="pieSales"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card enterprise-shadow h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-0">
                            <h6 class="font-weight-bold text-dark mb-0">Inject Voucher Fisik (Bulan Berjalan)</h6>
                            <small class="text-muted">Porsi Inject Voucher Fisik</small>
                        </div>
                        <div class="card-body">
                            <div style="height:260px">
                                <canvas id="pieInject"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= YEARLY MATRIX (HEATMAP) ================= --}}
            @php
                $annualMonthLabels = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
                $annualCutoffLabel = $annualCutoff === 'full' ? 'Full' : $annualMonthLabels[(int) $annualCutoff];
                $clusterDumai = ['DUMAI','BENGKALIS','DURI','RUPAT','SEI PAKNING'];
                $clusterRohil = ['BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG'];
            @endphp
            <div class="card enterprise-shadow mb-4" id="heatmap-sales-card">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <div class="d-flex align-items-start justify-content-between flex-wrap" style="gap:12px;">
                        <div>
                            <h6 class="font-weight-bold text-dark mb-0">🗺️ Penjualan {{ $salesPeriod === 'annual' ? 'Tahunan (2024–'.date('Y').')' : 'Bulanan (Tahun '.$salesYear.')' }}</h6>
                            <small class="text-muted">{{ $salesPeriod === 'annual' ? 'Perbandingan total penjualan setiap TAP per tahun · '.$annualCutoffLabel : 'Transaksi bulanan per TAP' }}</small>
                        </div>
                        <div class="d-flex align-items-center export-actions">
                            <form method="GET" action="{{ url('home') }}#heatmap-sales-card" class="monthly-sales-filter-form mb-0" id="monthly-sales-filter-form">
                                <input type="hidden" name="year" value="{{ $selectedYear }}">
                                <input type="hidden" name="mode" value="{{ $mode }}">
                                <input type="hidden" name="mom_date" value="{{ $momSelectedDate->toDateString() }}">
                                <input type="hidden" name="sales_view" id="monthly-sales-view-input" value="{{ $salesView }}">
                                <input type="hidden" name="sales_period" id="monthly-sales-period" value="{{ $salesPeriod }}">
                                <div class="monthly-sales-view-toggle" role="group" aria-label="Pilih periode penjualan">
                                    <button type="button" class="btn btn-sm monthly-sales-view-btn sales-period-btn {{ $salesPeriod === 'monthly' ? 'active' : '' }}" data-period="monthly">Bulanan</button>
                                    <button type="button" class="btn btn-sm monthly-sales-view-btn sales-period-btn {{ $salesPeriod === 'annual' ? 'active' : '' }}" data-period="annual">Tahunan</button>
                                </div>
                                <div id="monthly-sales-year-wrap" class="{{ $salesPeriod === 'annual' ? 'd-none' : '' }}">
                                    <select name="sales_year" id="monthly-sales-year" class="form-control form-control-sm font-weight-bold monthly-sales-year-select" aria-label="Tahun Penjualan Bulanan">
                                        @for($i = date('Y'); $i >= 2023; $i--)
                                            <option value="{{ $i }}" {{ $salesYear == $i ? 'selected' : '' }}>Tahun {{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div id="annual-sales-cutoff-wrap" class="{{ $salesPeriod === 'annual' ? '' : 'd-none' }}">
                                    <select name="sales_annual_cutoff" id="annual-sales-cutoff" class="form-control form-control-sm font-weight-bold monthly-sales-year-select" aria-label="Batas bulan perbandingan tahunan">
                                        <option value="full" {{ $annualCutoff === 'full' ? 'selected' : '' }}>Full</option>
                                        @foreach($annualMonthLabels as $monthNumber => $monthLabel)
                                            <option value="{{ $monthNumber }}" {{ (string) $annualCutoff === (string) $monthNumber ? 'selected' : '' }}>{{ $monthLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                            <div class="monthly-sales-view-toggle" role="group" aria-label="Pilih tampilan penjualan bulanan">
                                <button type="button" class="btn btn-sm monthly-sales-view-btn {{ $salesView === 'table' ? 'active' : '' }}" id="monthly-sales-table-btn" aria-pressed="{{ $salesView === 'table' ? 'true' : 'false' }}">Tabel</button>
                                <button type="button" class="btn btn-sm monthly-sales-view-btn {{ $salesView === 'chart' ? 'active' : '' }}" id="monthly-sales-chart-btn" aria-pressed="{{ $salesView === 'chart' ? 'true' : 'false' }}">Grafik</button>
                            </div>
                            <button type="button" class="btn btn-sm export-action-btn sales-reload-btn" id="sales-card-reload-btn" title="Muat ulang card Penjualan" aria-label="Muat ulang card Penjualan">
                                <i class="fas fa-sync-alt" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="btn btn-sm export-action-btn d-inline-flex align-items-center justify-content-center" id="heatmap-sales-copy-btn" title="Copy heatmap penjualan sebagai gambar" style="gap:6px;">
                                <i data-feather="copy" style="width:14px;height:14px"></i> Copy
                            </button>
                            <button type="button" class="btn btn-sm export-action-btn d-inline-flex align-items-center justify-content-center" id="heatmap-sales-download-btn" title="Download heatmap penjualan PNG" style="gap:6px;">
                                <i data-feather="download" style="width:14px;height:14px"></i> PNG
                            </button>
                        </div>
                        <small id="heatmap-sales-status" class="text-muted w-100 export-status"></small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-scroll-heatmap {{ $salesView === 'chart' ? 'd-none' : '' }}" id="heatmap-sales-wrap">
                        @if($salesPeriod === 'annual')
                            @php
                                $annualYears = range(2024, (int) date('Y'));
                                $annualYearTotals = array_fill_keys($annualYears, 0);
                                $annualGrandTotal = 0;
                                $annualCurrentYear = (int) date('Y');
                                $annualPreviousYear = $annualCurrentYear - 1;
                                $calculateAnnualYtd = function (array $values, int $targetYear) {
                                    $base = (int) ($values[$targetYear - 1] ?? 0);
                                    return $base > 0
                                        ? (((int) ($values[$targetYear] ?? 0) - $base) / $base) * 100
                                        : null;
                                };
                                $calculatePeriodGrowth = function (int $current, int $base) {
                                    return $base > 0
                                        ? (($current - $base) / $base) * 100
                                        : null;
                                };
                                $annualClusterDumaiValues = array_fill_keys($annualYears, 0);
                                $annualClusterRohilValues = array_fill_keys($annualYears, 0);
                                $annualClusterDumaiComparison = ['current_month' => 0, 'previous_year_month' => 0, 'previous_month' => 0];
                                $annualClusterRohilComparison = ['current_month' => 0, 'previous_year_month' => 0, 'previous_month' => 0];
                                $annualGrandComparison = ['current_month' => 0, 'previous_year_month' => 0, 'previous_month' => 0];
                                $annualClusterDumaiCurrentYtd = [$annualPreviousYear => 0, $annualCurrentYear => 0];
                                $annualClusterRohilCurrentYtd = [$annualPreviousYear => 0, $annualCurrentYear => 0];
                                $annualGrandCurrentYtd = [$annualPreviousYear => 0, $annualCurrentYear => 0];
                                foreach ($annualYears as $year) {
                                    foreach ($clusterDumai as $tap) {
                                        $annualClusterDumaiValues[$year] += (int) ($annualSales[$tap][$year] ?? 0);
                                    }
                                    foreach ($clusterRohil as $tap) {
                                        $annualClusterRohilValues[$year] += (int) ($annualSales[$tap][$year] ?? 0);
                                    }
                                }
                                foreach ($annualPeriodComparisons as $tap => $comparison) {
                                    foreach (array_keys($annualGrandComparison) as $metric) {
                                        $value = (int) ($comparison[$metric] ?? 0);
                                        $annualGrandComparison[$metric] += $value;
                                        if (in_array($tap, $clusterDumai)) $annualClusterDumaiComparison[$metric] += $value;
                                        if (in_array($tap, $clusterRohil)) $annualClusterRohilComparison[$metric] += $value;
                                    }
                                }
                                foreach ($annualCurrentYtdSales as $tap => $yearValues) {
                                    foreach ([$annualPreviousYear, $annualCurrentYear] as $year) {
                                        $value = (int) ($yearValues[$year] ?? 0);
                                        $annualGrandCurrentYtd[$year] += $value;
                                        if (in_array($tap, $clusterDumai)) $annualClusterDumaiCurrentYtd[$year] += $value;
                                        if (in_array($tap, $clusterRohil)) $annualClusterRohilCurrentYtd[$year] += $value;
                                    }
                                }
                            @endphp
                            <table class="table table-bordered text-center mb-0 annual-sales-table" style="font-size:13px; border-bottom:0">
                                <thead class="bg-light">
                                    <tr class="text-secondary">
                                        <th class="text-left font-weight-bold border-0 sticky-tap-col">NAMA TAP</th>
                                        @foreach($annualYears as $year)
                                            <th class="border-light">{{ $year }}</th>
                                        @endforeach
                                        <th class="border-light">TOTAL</th>
                                        <th class="border-light">% YTD {{ $annualPreviousYear }}</th>
                                        <th class="border-light">% YTD {{ $annualCurrentYear }}</th>
                                        <th class="border-light">YoY {{ $annualCurrentYear }}</th>
                                        <th class="border-light">MoM {{ $annualCurrentYear }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($annualSales as $tap => $yearValues)
                                        @php
                                            $tapAnnualTotal = 0;
                                            $tapPreviousYtd = $calculateAnnualYtd($yearValues, $annualPreviousYear);
                                            $tapCurrentYtd = $calculateAnnualYtd($annualCutoff === 'full' ? ($annualCurrentYtdSales[$tap] ?? []) : $yearValues, $annualCurrentYear);
                                            $tapComparison = $annualPeriodComparisons[$tap] ?? ['current_month' => 0, 'previous_year_month' => 0, 'previous_month' => 0];
                                            $tapYoy = $calculatePeriodGrowth($tapComparison['current_month'], $tapComparison['previous_year_month']);
                                            $tapMom = $annualComparisonMonth > 1
                                                ? $calculatePeriodGrowth($tapComparison['current_month'], $tapComparison['previous_month'])
                                                : null;
                                        @endphp
                                        <tr>
                                            <td class="text-left font-weight-bold bg-white text-dark sticky-tap-col">{{ $tap }}</td>
                                            @foreach($annualYears as $year)
                                                @php
                                                    $annualValue = (int) ($yearValues[$year] ?? 0);
                                                    $tapAnnualTotal += $annualValue;
                                                    $annualYearTotals[$year] += $annualValue;
                                                    $annualGrandTotal += $annualValue;
                                                @endphp
                                                <td class="border-light">{{ $annualValue > 0 ? number_format($annualValue) : '-' }}</td>
                                            @endforeach
                                            <td class="border-light bg-light font-weight-bold">{{ number_format($tapAnnualTotal) }}</td>
                                            <td class="border-light font-weight-bold {{ is_null($tapPreviousYtd) ? 'text-muted' : ($tapPreviousYtd >= 0 ? 'text-success' : 'text-danger') }}">
                                                {{ is_null($tapPreviousYtd) ? '-' : (($tapPreviousYtd >= 0 ? '▲ ' : '▼ ').number_format(abs($tapPreviousYtd), 1).'%') }}
                                            </td>
                                            <td class="border-light font-weight-bold {{ is_null($tapCurrentYtd) ? 'text-muted' : ($tapCurrentYtd >= 0 ? 'text-success' : 'text-danger') }}">
                                                {{ is_null($tapCurrentYtd) ? '-' : (($tapCurrentYtd >= 0 ? '▲ ' : '▼ ').number_format(abs($tapCurrentYtd), 1).'%') }}
                                            </td>
                                            <td class="border-light font-weight-bold {{ is_null($tapYoy) ? 'text-muted' : ($tapYoy >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($tapYoy) ? '-' : (($tapYoy >= 0 ? '▲ ' : '▼ ').number_format(abs($tapYoy), 1).'%') }}</td>
                                            <td class="border-light font-weight-bold {{ is_null($tapMom) ? 'text-muted' : ($tapMom >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($tapMom) ? '-' : (($tapMom >= 0 ? '▲ ' : '▼ ').number_format(abs($tapMom), 1).'%') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="font-weight-bold">
                                    @if(in_array(session('idtap'), ['SBP_DUMAI', 'CLUSTER_DUMAI']))
                                    @php
                                        $clusterDumaiTotal = array_sum($annualClusterDumaiValues);
                                        $clusterDumaiPreviousYtd = $calculateAnnualYtd($annualClusterDumaiValues, $annualPreviousYear);
                                        $clusterDumaiCurrentYtd = $calculateAnnualYtd($annualCutoff === 'full' ? $annualClusterDumaiCurrentYtd : $annualClusterDumaiValues, $annualCurrentYear);
                                        $clusterDumaiYoy = $calculatePeriodGrowth($annualClusterDumaiComparison['current_month'], $annualClusterDumaiComparison['previous_year_month']);
                                        $clusterDumaiMom = $annualComparisonMonth > 1 ? $calculatePeriodGrowth($annualClusterDumaiComparison['current_month'], $annualClusterDumaiComparison['previous_month']) : null;
                                    @endphp
                                    <tr style="background:#f8fafc;color:#374151;border-top:2px solid #d1d5db">
                                        <td class="text-left sticky-tap-col" style="background:#f8fafc !important"><span class="badge badge-light border mr-2">CLUSTER</span>Dumai Bengkalis</td>
                                        @foreach($annualYears as $year)
                                            <td>{{ number_format($annualClusterDumaiValues[$year]) }}</td>
                                        @endforeach
                                        <td>{{ number_format($clusterDumaiTotal) }}</td>
                                        <td class="{{ is_null($clusterDumaiPreviousYtd) ? 'text-muted' : ($clusterDumaiPreviousYtd >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($clusterDumaiPreviousYtd) ? '-' : (($clusterDumaiPreviousYtd >= 0 ? '▲ ' : '▼ ').number_format(abs($clusterDumaiPreviousYtd), 1).'%') }}</td>
                                        <td class="{{ is_null($clusterDumaiCurrentYtd) ? 'text-muted' : ($clusterDumaiCurrentYtd >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($clusterDumaiCurrentYtd) ? '-' : (($clusterDumaiCurrentYtd >= 0 ? '▲ ' : '▼ ').number_format(abs($clusterDumaiCurrentYtd), 1).'%') }}</td>
                                        <td class="{{ is_null($clusterDumaiYoy) ? 'text-muted' : ($clusterDumaiYoy >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($clusterDumaiYoy) ? '-' : (($clusterDumaiYoy >= 0 ? '▲ ' : '▼ ').number_format(abs($clusterDumaiYoy), 1).'%') }}</td>
                                        <td class="{{ is_null($clusterDumaiMom) ? 'text-muted' : ($clusterDumaiMom >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($clusterDumaiMom) ? '-' : (($clusterDumaiMom >= 0 ? '▲ ' : '▼ ').number_format(abs($clusterDumaiMom), 1).'%') }}</td>
                                    </tr>
                                    @endif
                                    @if(in_array(session('idtap'), ['SBP_DUMAI', 'CLUSTER_ROHIL']))
                                    @php
                                        $clusterRohilTotal = array_sum($annualClusterRohilValues);
                                        $clusterRohilPreviousYtd = $calculateAnnualYtd($annualClusterRohilValues, $annualPreviousYear);
                                        $clusterRohilCurrentYtd = $calculateAnnualYtd($annualCutoff === 'full' ? $annualClusterRohilCurrentYtd : $annualClusterRohilValues, $annualCurrentYear);
                                        $clusterRohilYoy = $calculatePeriodGrowth($annualClusterRohilComparison['current_month'], $annualClusterRohilComparison['previous_year_month']);
                                        $clusterRohilMom = $annualComparisonMonth > 1 ? $calculatePeriodGrowth($annualClusterRohilComparison['current_month'], $annualClusterRohilComparison['previous_month']) : null;
                                    @endphp
                                    <tr style="background:#f8fafc;color:#374151">
                                        <td class="text-left sticky-tap-col" style="background:#f8fafc !important"><span class="badge badge-light border mr-2">CLUSTER</span>Rokan Hilir</td>
                                        @foreach($annualYears as $year)
                                            <td>{{ number_format($annualClusterRohilValues[$year]) }}</td>
                                        @endforeach
                                        <td>{{ number_format($clusterRohilTotal) }}</td>
                                        <td class="{{ is_null($clusterRohilPreviousYtd) ? 'text-muted' : ($clusterRohilPreviousYtd >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($clusterRohilPreviousYtd) ? '-' : (($clusterRohilPreviousYtd >= 0 ? '▲ ' : '▼ ').number_format(abs($clusterRohilPreviousYtd), 1).'%') }}</td>
                                        <td class="{{ is_null($clusterRohilCurrentYtd) ? 'text-muted' : ($clusterRohilCurrentYtd >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($clusterRohilCurrentYtd) ? '-' : (($clusterRohilCurrentYtd >= 0 ? '▲ ' : '▼ ').number_format(abs($clusterRohilCurrentYtd), 1).'%') }}</td>
                                        <td class="{{ is_null($clusterRohilYoy) ? 'text-muted' : ($clusterRohilYoy >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($clusterRohilYoy) ? '-' : (($clusterRohilYoy >= 0 ? '▲ ' : '▼ ').number_format(abs($clusterRohilYoy), 1).'%') }}</td>
                                        <td class="{{ is_null($clusterRohilMom) ? 'text-muted' : ($clusterRohilMom >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($clusterRohilMom) ? '-' : (($clusterRohilMom >= 0 ? '▲ ' : '▼ ').number_format(abs($clusterRohilMom), 1).'%') }}</td>
                                    </tr>
                                    @endif
                                    @php
                                        $annualGrandPreviousYtd = $calculateAnnualYtd($annualYearTotals, $annualPreviousYear);
                                        $annualGrandCurrentYtdGrowth = $calculateAnnualYtd($annualCutoff === 'full' ? $annualGrandCurrentYtd : $annualYearTotals, $annualCurrentYear);
                                        $annualGrandYoy = $calculatePeriodGrowth($annualGrandComparison['current_month'], $annualGrandComparison['previous_year_month']);
                                        $annualGrandMom = $annualComparisonMonth > 1 ? $calculatePeriodGrowth($annualGrandComparison['current_month'], $annualGrandComparison['previous_month']) : null;
                                    @endphp
                                    <tr style="background:#e3f2fd;color:#0d47a1;border-top:2px solid #90caf9">
                                        <td class="text-left sticky-tap-col" style="background:#e3f2fd !important">GRAND TOTAL</td>
                                        @foreach($annualYears as $year)
                                            <td>{{ number_format($annualYearTotals[$year]) }}</td>
                                        @endforeach
                                        <td>{{ number_format($annualGrandTotal) }}</td>
                                        <td class="{{ is_null($annualGrandPreviousYtd) ? 'text-muted' : ($annualGrandPreviousYtd >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($annualGrandPreviousYtd) ? '-' : (($annualGrandPreviousYtd >= 0 ? '▲ ' : '▼ ').number_format(abs($annualGrandPreviousYtd), 1).'%') }}</td>
                                        <td class="{{ is_null($annualGrandCurrentYtdGrowth) ? 'text-muted' : ($annualGrandCurrentYtdGrowth >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($annualGrandCurrentYtdGrowth) ? '-' : (($annualGrandCurrentYtdGrowth >= 0 ? '▲ ' : '▼ ').number_format(abs($annualGrandCurrentYtdGrowth), 1).'%') }}</td>
                                        <td class="{{ is_null($annualGrandYoy) ? 'text-muted' : ($annualGrandYoy >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($annualGrandYoy) ? '-' : (($annualGrandYoy >= 0 ? '▲ ' : '▼ ').number_format(abs($annualGrandYoy), 1).'%') }}</td>
                                        <td class="{{ is_null($annualGrandMom) ? 'text-muted' : ($annualGrandMom >= 0 ? 'text-success' : 'text-danger') }}">{{ is_null($annualGrandMom) ? '-' : (($annualGrandMom >= 0 ? '▲ ' : '▼ ').number_format(abs($annualGrandMom), 1).'%') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        @else
                        <table class="table table-bordered text-center mb-0" style="font-size:13px; border-bottom:0">
                            <thead class="bg-light">
                                <tr class="text-secondary">
                                    <th class="text-left font-weight-bold border-0 text-secondary sticky-tap-col">NAMA TAP</th>
                                    @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $m)
                                        <th class="border-light">{{ $m }}</th>
                                        <th class="border-light text-muted" style="font-size: 10px;">MoM %</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matrixSales as $tap => $months)
                                    <tr>
                                        <td class="text-left font-weight-bold bg-white text-dark sticky-tap-col">
                                            <button class="btn btn-sm btn-light border toggle-validity rounded py-0 px-2 mr-2"
                                                data-target="val-sales-{{ Str::slug($tap) }}">
                                                +
                                            </button>
                                            {{ $tap }}
                                        </td>
                                        @for($i=1; $i<=12; $i++)
                                            @php 
                                                $val = $months[$i] ?? 0; 
                                                $opacity = $val > 0 ? min($val / 200000, 1) : 0; 
                                                $opacity = $opacity > 0 && $opacity < 0.15 ? 0.15 : $opacity;
                                                $bg = $val > 0 ? "rgba(66, 133, 244, $opacity)" : "transparent"; 
                                                $color = $opacity > 0.5 ? '#fff' : '#444';

                                                // MoM Logic Sales
                                                $isCurrentMonth = ($salesYear == $currentDate->year && $i == $currentDate->month);
                                                $prevVal = ($i == 1) ? ($prevDecSales[$tap] ?? 0) : ($months[$i-1] ?? 0);
                                                
                                                if($isCurrentMonth) {
                                                    $momData = $momTap->firstWhere('idtap', $tap);
                                                    $pct = $momData ? $momData->mom : 0;
                                                    $hasPrev = $momData && $momData->prev_partial_qty > 0;
                                                } else {
                                                    $pct = $prevVal > 0 ? (($val - $prevVal) / $prevVal) * 100 : 0;
                                                    $hasPrev = $prevVal > 0;
                                                }
                                                $momColor = $pct > 0 ? 'text-success' : ($pct < 0 ? 'text-danger' : 'text-muted');
                                                $icon = $pct > 0 ? '↑' : ($pct < 0 ? '↓' : '');
                                            @endphp
                                            <td class="heatmap-cell border-light" style="background-color: {{ $bg }}; color: {{ $color }};">
                                                {{ $val > 0 ? number_format($val) : '-' }}
                                            </td>
                                            <td class="border-light {{ $momColor }} font-weight-bold" style="font-size: 10px; vertical-align: middle; background: #fafafa">
                                                @if($hasPrev)
                                                    {{ number_format($pct, 1) }}% <span style="font-size: 8px;">{{ $icon }}</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        @endfor
                                    </tr>

                                    {{-- EXPANSION ROW: SF & VALIDITY SALES --}}
                                    <tr id="val-sales-{{ Str::slug($tap) }}" class="validity-row d-none bg-light">
                                        <td colspan="25" class="p-0 border-0">
                                            <div class="py-2 pr-2 bg-light">
                                                <div class="d-flex mb-2 pl-5">
                                                    <button class="btn btn-xs btn-primary mr-1 btn-toggle-rincian" data-target="sf-sales-{{ Str::slug($tap) }}" data-parent="val-sales-{{ Str::slug($tap) }}">👤 Performance Petugas</button>
                                                    <button class="btn btn-xs btn-outline-primary btn-toggle-rincian" data-target="validity-sales-{{ Str::slug($tap) }}" data-parent="val-sales-{{ Str::slug($tap) }}">🎫 Validity Group</button>
                                                </div>

                                                {{-- SUB-SECTION: PERFORMANCE SELURUH PETUGAS --}}
                                                <div id="sf-sales-{{ Str::slug($tap) }}" class="rincian-content">
                                                    <table class="table table-sm table-bordered mb-0 bg-white shadow-sm rounded text-center" style="font-size: 11px;">
                                                        <thead class="bg-white">
                                                            <tr class="text-primary">
                                                                <th class="text-left border-0 sticky-validity-col" style="background: #f8fafc !important;">Nama Petugas</th>
                                                                @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $m)
                                                                    <th class="border-0">{{ $m }}</th>
                                                                @endforeach
                                                                <th class="border-0 bg-light">TOTAL</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($sfByTap[$tap] ?? [] as $sf)
                                                                @php $sfTotal = 0; @endphp
                                                                <tr>
                                                                    <td class="text-left font-weight-bold sticky-validity-col">{{ $sf->namasf }}</td>
                                                                    @for($m=1; $m<=12; $m++)
                                                                        @php $mv = $matrixSalesSf[$sf->idsf][$m] ?? 0; $sfTotal += $mv; @endphp
                                                                        <td>{{ $mv > 0 ? number_format($mv) : '-' }}</td>
                                                                    @endfor
                                                                    <td class="bg-light font-weight-bold">{{ number_format($sfTotal) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>

                                                {{-- SUB-SECTION: VALIDITY GROUP --}}
                                                <div id="validity-sales-{{ Str::slug($tap) }}" class="rincian-content d-none">
                                                    <table class="table table-sm table-bordered mb-0 bg-white shadow-sm rounded text-center" style="font-size: 11px;">
                                                        <thead class="bg-white">
                                                            <tr class="text-secondary">
                                                                <th class="text-left border-0 sticky-validity-col" style="background: #f8fafc !important;">Validity Group</th>
                                                                @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $m)
                                                                    <th class="border-0">{{ $m }}</th>
                                                                @endforeach
                                                                <th class="border-0 bg-light-primary">TOTAL</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @php 
                                                                $footTotals = array_fill(1, 12, 0); 
                                                                $footGrand = 0;
                                                            @endphp
                                                            @foreach($validityGroups as $vg)
                                                                @php 
                                                                    $totalVG = 0; 
                                                                    $mRow = [];
                                                                    for($m=1;$m<=12;$m++) {
                                                                        $v = $validityMatrixSales[$tap][$vg][$m] ?? 0;
                                                                        $mRow[$m] = $v;
                                                                        $totalVG += $v;
                                                                        $footTotals[$m] += $v;
                                                                    }
                                                                    $footGrand += $totalVG;
                                                                @endphp
                                                                @if($totalVG > 0)
                                                                <tr>
                                                                    <td class="text-left font-weight-bold sticky-validity-col">{{ $vg }}</td>
                                                                    @for($m=1; $m<=12; $m++)
                                                                        <td>{{ $mRow[$m] > 0 ? number_format($mRow[$m]) : '-' }}</td>
                                                                    @endfor
                                                                    <td class="bg-light font-weight-bold">{{ number_format($totalVG) }}</td>
                                                                </tr>
                                                                @endif
                                                            @endforeach
                                                        </tbody>
                                                        <tfoot class="bg-light font-weight-bold">
                                                            <tr>
                                                                <td class="text-left sticky-validity-col">TOTAL</td>
                                                                @for($m=1; $m<=12; $m++)
                                                                    <td>{{ $footTotals[$m] > 0 ? number_format($footTotals[$m]) : '-' }}</td>
                                                                @endfor
                                                                <td class="bg-secondary text-white">{{ number_format($footGrand) }}</td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light text-dark font-weight-bold" style="border-top: 2px solid #ddd">
                                @php
                                    $clusterDumai = ['DUMAI','BENGKALIS','DURI','RUPAT','SEI PAKNING'];
                                    $clusterRohil = ['BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG'];
                                @endphp
                                
                                {{-- FOOTER CLUSTER DUMAI BENGKALIS --}}
                                @if(in_array(session('idtap'), ['SBP_DUMAI', 'CLUSTER_DUMAI']))
                                <tr>
                                    <td class="text-left border-0 sticky-tap-col">
                                        <button class="btn btn-sm btn-light border toggle-validity rounded py-0 px-2 mr-2"
                                            data-target="val-sales-cluster-dumai">
                                            +
                                        </button>
                                        <span class="badge badge-light text-dark mr-1">CLUSTER</span> Dumai Bengkalis
                                    </td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $totalDumai = 0;
                                            $prevTotalDumai = 0;
                                            foreach($clusterDumai as $tap) { 
                                                $totalDumai += ($matrixSales[$tap][$i] ?? 0); 
                                                $prevTotalDumai += ($i == 1) ? ($prevDecSales[$tap] ?? 0) : ($matrixSales[$tap][$i-1] ?? 0);
                                            }

                                            $isCurrentMonth = ($salesYear == $currentDate->year && $i == $currentDate->month);
                                            if($isCurrentMonth) {
                                                $pct = $momCluster['dumai_bengkalis']->mom ?? 0;
                                            } else {
                                                $pct = $prevTotalDumai > 0 ? (($totalDumai - $prevTotalDumai) / $prevTotalDumai) * 100 : 0;
                                            }
                                            $momColor = $pct > 0 ? 'text-success' : ($pct < 0 ? 'text-danger' : 'text-muted');
                                            $icon = $pct > 0 ? '↑' : ($pct < 0 ? '↓' : '');
                                        @endphp
                                        <td class="border-light opacity-75">{{ $totalDumai > 0 ? number_format($totalDumai) : '-' }}</td>
                                        <td class="border-light {{ $momColor }} font-weight-bold" style="font-size: 10px; vertical-align: middle; background: #fafafa">
                                            @if($prevTotalDumai > 0 || ($isCurrentMonth && $pct != 0))
                                                {{ number_format($pct, 1) }}% <span style="font-size: 8px;">{{ $icon }}</span>
                                            @else - @endif
                                        </td>
                                    @endfor
                                </tr>
                                                                <tr id="val-sales-cluster-dumai" class="validity-row d-none bg-light">
                                    <td colspan="25" class="p-0 border-0">
                                        <div class="py-2 pr-2" style="background: #f0f7ff">
                                            <table class="table table-sm table-bordered mb-0 bg-white shadow-sm rounded text-center" style="font-size: 11px;">
                                                <thead class="bg-white">
                                                    <tr class="text-primary">
                                                        <th class="text-left border-0 sticky-validity-col">Validity (Cluster Dumai)</th>
                                                        @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $m)
                                                            <th class="border-0">{{ $m }}</th>
                                                        @endforeach
                                                        <th class="border-0 bg-light">TOTAL</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php 
                                                        $footTotals = array_fill(1, 12, 0); 
                                                        $footGrand = 0;
                                                    @endphp
                                                    @foreach($validityGroups as $vg)
                                                        @php 
                                                            $totalVG = 0; 
                                                            $mTotals = array_fill(1, 12, 0);
                                                            foreach($clusterDumai as $tap) {
                                                                foreach($validityMatrixSales[$tap][$vg] ?? [] as $m => $val) {
                                                                    $mTotals[$m] += $val;
                                                                    $totalVG += $val;
                                                                }
                                                            }
                                                            for($m=1;$m<=12;$m++) { $footTotals[$m] += $mTotals[$m]; }
                                                            $footGrand += $totalVG;
                                                        @endphp
                                                        @if($totalVG > 0)
                                                        <tr>
                                                            <td class="text-left font-weight-bold sticky-validity-col">{{ $vg }}</td>
                                                            @for($m=1; $m<=12; $m++)
                                                                <td>{{ $mTotals[$m] > 0 ? number_format($mTotals[$m]) : '-' }}</td>
                                                            @endfor
                                                            <td class="bg-light font-weight-bold">{{ number_format($totalVG) }}</td>
                                                        </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-light font-weight-bold">
                                                    <tr>
                                                        <td class="text-left sticky-validity-col">TOTAL</td>
                                                        @for($m=1; $m<=12; $m++)
                                                            <td>{{ $footTotals[$m] > 0 ? number_format($footTotals[$m]) : '-' }}</td>
                                                        @endfor
                                                        <td class="bg-primary text-white">{{ number_format($footGrand) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </td>
                                </tr>

                                @endif

                                @if(in_array(session('idtap'), ['SBP_DUMAI', 'CLUSTER_ROHIL']))
                                {{-- FOOTER CLUSTER ROKAN HILIR --}}
                                <tr>
                                    <td class="text-left border-0 sticky-tap-col">
                                        <button class="btn btn-sm btn-light border toggle-validity rounded py-0 px-2 mr-2"
                                            data-target="val-sales-cluster-rohil">
                                            +
                                        </button>
                                        <span class="badge badge-light text-dark mr-1">CLUSTER</span> Rokan Hilir
                                    </td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $totalRohil = 0;
                                            $prevTotalRohil = 0;
                                            foreach($clusterRohil as $tap) { 
                                                $totalRohil += ($matrixSales[$tap][$i] ?? 0); 
                                                $prevTotalRohil += ($i == 1) ? ($prevDecSales[$tap] ?? 0) : ($matrixSales[$tap][$i-1] ?? 0);
                                            }

                                            $isCurrentMonth = ($salesYear == $currentDate->year && $i == $currentDate->month);
                                            if($isCurrentMonth) {
                                                $pct = $momCluster['rokan_hilir']->mom ?? 0;
                                            } else {
                                                $pct = $prevTotalRohil > 0 ? (($totalRohil - $prevTotalRohil) / $prevTotalRohil) * 100 : 0;
                                            }
                                            $momColor = $pct > 0 ? 'text-success' : ($pct < 0 ? 'text-danger' : 'text-muted');
                                            $icon = $pct > 0 ? '↑' : ($pct < 0 ? '↓' : '');
                                        @endphp
                                        <td class="border-light opacity-75">{{ $totalRohil > 0 ? number_format($totalRohil) : '-' }}</td>
                                        <td class="border-light {{ $momColor }} font-weight-bold" style="font-size: 10px; vertical-align: middle; background: #fafafa">
                                            @if($prevTotalRohil > 0 || ($isCurrentMonth && $pct != 0))
                                                {{ number_format($pct, 1) }}% <span style="font-size: 8px;">{{ $icon }}</span>
                                            @else - @endif
                                        </td>
                                    @endfor
                                </tr>
                                                                <tr id="val-sales-cluster-rohil" class="validity-row d-none bg-light">
                                    <td colspan="25" class="p-0 border-0">
                                        <div class="py-2 pr-2" style="background: #f0f7ff">
                                            <table class="table table-sm table-bordered mb-0 bg-white shadow-sm rounded text-center" style="font-size: 11px;">
                                                <thead class="bg-white">
                                                    <tr class="text-primary">
                                                        <th class="text-left border-0 sticky-validity-col">Validity (Cluster Rohil)</th>
                                                        @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $m)
                                                            <th class="border-0">{{ $m }}</th>
                                                        @endforeach
                                                        <th class="border-0 bg-light">TOTAL</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php 
                                                        $footTotals = array_fill(1, 12, 0); 
                                                        $footGrand = 0;
                                                    @endphp
                                                    @foreach($validityGroups as $vg)
                                                        @php 
                                                            $totalVG = 0; 
                                                            $mTotals = array_fill(1, 12, 0);
                                                            foreach($clusterRohil as $tap) {
                                                                foreach($validityMatrixSales[$tap][$vg] ?? [] as $m => $val) {
                                                                    $mTotals[$m] += $val;
                                                                    $totalVG += $val;
                                                                }
                                                            }
                                                            for($m=1;$m<=12;$m++) { $footTotals[$m] += $mTotals[$m]; }
                                                            $footGrand += $totalVG;
                                                        @endphp
                                                        @if($totalVG > 0)
                                                        <tr>
                                                            <td class="text-left font-weight-bold sticky-validity-col">{{ $vg }}</td>
                                                            @for($m=1; $m<=12; $m++)
                                                                <td>{{ $mTotals[$m] > 0 ? number_format($mTotals[$m]) : '-' }}</td>
                                                            @endfor
                                                            <td class="bg-light font-weight-bold">{{ number_format($totalVG) }}</td>
                                                        </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-light font-weight-bold">
                                                    <tr>
                                                        <td class="text-left sticky-validity-col">TOTAL</td>
                                                        @for($m=1; $m<=12; $m++)
                                                            <td>{{ $footTotals[$m] > 0 ? number_format($footTotals[$m]) : '-' }}</td>
                                                        @endfor
                                                        <td class="bg-primary text-white">{{ number_format($footGrand) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </td>
                                </tr>

                                @endif

                                @if(session('idtap') == 'SBP_DUMAI')
                                {{-- GRAND TOTAL KESELURUHAN --}}
                                <tr style="background-color: #e3f2fd; color: #0d47a1;">
                                    <td class="text-left border-0 font-weight-bold sticky-tap-col">
                                        <button class="btn btn-sm btn-primary border toggle-validity rounded py-0 px-2 mr-2"
                                            data-target="val-sales-grand-total">
                                            +
                                        </button>
                                        ✨ GRAND TOTAL
                                    </td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $totalGT = 0;
                                            $prevTotalGT = 0;
                                            foreach($matrixSales as $tap => $months) { 
                                                $totalGT += ($months[$i] ?? 0); 
                                                $prevTotalGT += ($i == 1) ? ($prevDecSales[$tap] ?? 0) : ($months[$i-1] ?? 0);
                                            }

                                            $isCurrentMonth = ($salesYear == $currentDate->year && $i == $currentDate->month);
                                            if($isCurrentMonth) {
                                                $pct = $mom; // Global Sales MoM
                                            } else {
                                                $pct = $prevTotalGT > 0 ? (($totalGT - $prevTotalGT) / $prevTotalGT) * 100 : 0;
                                            }
                                            $momColor = $pct > 0 ? 'text-success' : ($pct < 0 ? 'text-danger' : 'text-muted');
                                            $icon = $pct > 0 ? '↑' : ($pct < 0 ? '↓' : '');
                                        @endphp
                                        <td class="border-light font-weight-bold">{{ $totalGT > 0 ? number_format($totalGT) : '-' }}</td>
                                        <td class="border-light {{ $momColor }} font-weight-bold" style="font-size: 10px; vertical-align: middle; background: rgba(0,0,0,0.05)">
                                            @if($prevTotalGT > 0 || ($isCurrentMonth && $pct != 0))
                                                {{ number_format($pct, 1) }}% <span style="font-size: 8px;">{{ $icon }}</span>
                                            @else - @endif
                                        </td>
                                    @endfor
                                </tr>
                                                                <tr id="val-sales-grand-total" class="validity-row d-none" style="background-color: #f8fafc">
                                    <td colspan="25" class="p-0 border-0">
                                        <div class="py-2 pr-2" style="background: #e3f2fd">
                                            <table class="table table-sm table-bordered mb-0 bg-white shadow-sm rounded text-center" style="font-size: 11px;">
                                                <thead class="bg-white">
                                                    <tr style="color: #0d47a1">
                                                        <th class="text-left border-0 sticky-validity-col">Validity (Grand Total)</th>
                                                        @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $m)
                                                            <th class="border-0">{{ $m }}</th>
                                                        @endforeach
                                                        <th class="border-0 bg-light">TOTAL</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php 
                                                        $footTotals = array_fill(1, 12, 0); 
                                                        $footGrand = 0;
                                                    @endphp
                                                    @foreach($validityGroups as $vg)
                                                        @php 
                                                            $totalVG = 0; 
                                                            $mTotals = array_fill(1, 12, 0);
                                                            foreach($matrixSales as $tap => $months) {
                                                                foreach($validityMatrixSales[$tap][$vg] ?? [] as $m => $val) {
                                                                    $mTotals[$m] += $val;
                                                                    $totalVG += $val;
                                                                }
                                                            }
                                                            for($m=1;$m<=12;$m++) { $footTotals[$m] += $mTotals[$m]; }
                                                            $footGrand += $totalVG;
                                                        @endphp
                                                        @if($totalVG > 0)
                                                        <tr>
                                                            <td class="text-left font-weight-bold sticky-validity-col">{{ $vg }}</td>
                                                            @for($m=1; $m<=12; $m++)
                                                                <td>{{ $mTotals[$m] > 0 ? number_format($mTotals[$m]) : '-' }}</td>
                                                            @endfor
                                                            <td class="bg-light font-weight-bold">{{ number_format($totalVG) }}</td>
                                                        </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-light font-weight-bold">
                                                    <tr>
                                                        <td class="text-left sticky-validity-col">TOTAL</td>
                                                        @for($m=1; $m<=12; $m++)
                                                            <td>{{ $footTotals[$m] > 0 ? number_format($footTotals[$m]) : '-' }}</td>
                                                        @endfor
                                                        <td class="bg-primary text-white">{{ number_format($footGrand) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </td>
                                </tr>

                                @endif
                            </tfoot>
                        </table>
                        @endif
                    </div>
                    <div id="monthly-sales-chart-wrap" class="{{ $salesView === 'table' ? 'd-none' : '' }}" aria-hidden="{{ $salesView === 'chart' ? 'false' : 'true' }}">
                        <canvas id="monthly-sales-chart"></canvas>
                    </div>
                    @php
                        $monthlySalesChartPayload = [
                            'monthly' => $matrixSales,
                            'annual' => $annualSales,
                            'period' => $salesPeriod,
                            'year' => $salesYear,
                            'view' => $salesView,
                            'cutoff' => $annualCutoff,
                        ];
                    @endphp
                    <script type="application/json" id="monthly-sales-chart-data">@json($monthlySalesChartPayload)</script>
                </div>
            </div>

            {{-- ================= YEARLY MATRIX INJECT (HEATMAP) ================= --}}
            <div class="card enterprise-shadow mb-4" id="heatmap-inject-card">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <div class="d-flex align-items-start justify-content-between flex-wrap" style="gap:12px;">
                        <div>
                            <h6 class="font-weight-bold text-dark mb-0">🪄Inject PV Bulanan (Tahun {{ $selectedYear }})</h6>
                            <small class="text-muted">Inject PV per TAP</small>
                        </div>
                        <div class="d-flex align-items-center export-actions">
                            <button type="button" class="btn btn-sm export-action-btn d-inline-flex align-items-center justify-content-center" id="heatmap-inject-copy-btn" title="Copy heatmap inject sebagai gambar" style="gap:6px;">
                                <i data-feather="copy" style="width:14px;height:14px"></i> Copy
                            </button>
                            <button type="button" class="btn btn-sm export-action-btn d-inline-flex align-items-center justify-content-center" id="heatmap-inject-download-btn" title="Download heatmap inject PNG" style="gap:6px;">
                                <i data-feather="download" style="width:14px;height:14px"></i> PNG
                            </button>
                        </div>
                        <small id="heatmap-inject-status" class="text-muted w-100 export-status"></small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-scroll-heatmap" id="heatmap-inject-wrap">
                        <table class="table table-bordered text-center mb-0" style="font-size:13px; border-bottom:0">
                            <thead class="bg-light">
                                <tr class="text-secondary">
                                    <th class="text-left font-weight-bold border-0 text-secondary sticky-tap-col">NAMA TAP</th>
                                    @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $m)
                                        <th class="border-light">{{ $m }}</th>
                                        <th class="border-light text-muted" style="font-size: 10px;">MoM %</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matrixInject as $tap => $months)
                                    <tr>
                                        <td class="text-left font-weight-bold bg-white text-dark sticky-tap-col">
                                            <button class="btn btn-sm btn-light border toggle-validity rounded py-0 px-2 mr-2"
                                                data-target="val-inject-{{ Str::slug($tap) }}">
                                                +
                                            </button>
                                            {{ $tap }}
                                        </td>
                                        @for($i=1; $i<=12; $i++)
                                            @php 
                                                $val = $months[$i] ?? 0; 
                                                $opacity = $val > 0 ? min($val / 20000, 1) : 0; 
                                                $opacity = $opacity > 0 && $opacity < 0.15 ? 0.15 : $opacity;
                                                $bg = $val > 0 ? "rgba(25, 135, 84, $opacity)" : "transparent"; 
                                                $color = $opacity > 0.5 ? '#fff' : '#444';

                                                // MoM Logic Inject
                                                $isCurrentMonth = ($selectedYear == $currentDate->year && $i == $currentDate->month);
                                                $prevVal = ($i == 1) ? ($prevDecInject[$tap] ?? 0) : ($months[$i-1] ?? 0);
                                                
                                                if($isCurrentMonth) {
                                                    $pct = $momTapInject[$tap] ?? 0;
                                                    $hasPrev = isset($momTapInject[$tap]);
                                                } else {
                                                    $pct = $prevVal > 0 ? (($val - $prevVal) / $prevVal) * 100 : 0;
                                                    $hasPrev = $prevVal > 0;
                                                }
                                                $momColor = $pct > 0 ? 'text-success' : ($pct < 0 ? 'text-danger' : 'text-muted');
                                                $icon = $pct > 0 ? '↑' : ($pct < 0 ? '↓' : '');
                                            @endphp
                                            <td class="heatmap-cell border-light" style="background-color: {{ $bg }}; color: {{ $color }};">
                                                {{ $val > 0 ? number_format($val) : '-' }}
                                            </td>
                                            <td class="border-light {{ $momColor }} font-weight-bold" style="font-size: 10px; vertical-align: middle; background: #fafafa">
                                                @if($hasPrev)
                                                    {{ number_format($pct, 1) }}% <span style="font-size: 8px;">{{ $icon }}</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        @endfor
                                    </tr>

                                    {{-- EXPANSION ROW: VALIDITY INJECT --}}
                                    <tr id="val-inject-{{ Str::slug($tap) }}" class="validity-row d-none bg-light">
                                        <td colspan="25" class="p-0 border-0">
                                            <div class="py-2 pr-2 bg-light">
                                                <table class="table table-sm table-bordered mb-0 bg-white shadow-sm rounded text-center" style="font-size: 11px;">
                                                    <thead class="bg-white">
                                                        <tr class="text-secondary">
                                                            <th class="text-left border-0 sticky-validity-col">Validity Group</th>
                                                            @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $m)
                                                                <th class="border-0">{{ $m }}</th>
                                                            @endforeach
                                                            <th class="border-0 bg-light-success">TOTAL</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php 
                                                            $footTotals = array_fill(1, 12, 0); 
                                                            $footGrand = 0;
                                                        @endphp
                                                        @foreach($validityGroups as $vg)
                                                            @php 
                                                                $totalVG = 0; 
                                                                $mRow = [];
                                                                for($m=1;$m<=12;$m++) {
                                                                    $v = $validityMatrixInject[$tap][$vg][$m] ?? 0;
                                                                    $mRow[$m] = $v;
                                                                    $totalVG += $v;
                                                                    $footTotals[$m] += $v;
                                                                }
                                                                $footGrand += $totalVG;
                                                            @endphp
                                                            @if($totalVG > 0)
                                                            <tr>
                                                                <td class="text-left font-weight-bold sticky-validity-col">{{ $vg }}</td>
                                                                @for($m=1; $m<=12; $m++)
                                                                    <td>{{ $mRow[$m] > 0 ? number_format($mRow[$m]) : '-' }}</td>
                                                                @endfor
                                                                <td class="bg-light font-weight-bold">{{ number_format($totalVG) }}</td>
                                                            </tr>
                                                            @endif
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot class="bg-light font-weight-bold">
                                                        <tr>
                                                            <td class="text-left sticky-validity-col">TOTAL</td>
                                                            @for($m=1; $m<=12; $m++)
                                                                <td>{{ $footTotals[$m] > 0 ? number_format($footTotals[$m]) : '-' }}</td>
                                                            @endfor
                                                            <td class="bg-success text-white">{{ number_format($footGrand) }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                      {{-- FOOTER CLUSTER DUMAI BENGKALIS --}}
                                @if(in_array(session('idtap'), ['SBP_DUMAI', 'CLUSTER_DUMAI']))
                                <tr>
                                    <td class="text-left border-0 sticky-tap-col">
                                        <button class="btn btn-sm btn-light border toggle-validity rounded py-0 px-2 mr-2"
                                            data-target="val-inject-cluster-dumai">
                                            +
                                        </button>
                                        <span class="badge badge-light text-dark mr-1">CLUSTER</span> Dumai Bengkalis
                                    </td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $totalDumai = 0;
                                            $prevTotalDumai = 0;
                                            foreach($clusterDumai as $tap) { 
                                                $totalDumai += ($matrixInject[$tap][$i] ?? 0); 
                                                $prevTotalDumai += ($i == 1) ? ($prevDecInject[$tap] ?? 0) : ($matrixInject[$tap][$i-1] ?? 0);
                                            }

                                            $isCurrentMonth = ($selectedYear == $currentDate->year && $i == $currentDate->month);
                                            if($isCurrentMonth) {
                                                $pct = $momClusterInject['dumai_bengkalis'] ?? 0;
                                            } else {
                                                $pct = $prevTotalDumai > 0 ? (($totalDumai - $prevTotalDumai) / $prevTotalDumai) * 100 : 0;
                                            }
                                            $momColor = $pct > 0 ? 'text-success' : ($pct < 0 ? 'text-danger' : 'text-muted');
                                            $icon = $pct > 0 ? '↑' : ($pct < 0 ? '↓' : '');
                                        @endphp
                                        <td class="border-light opacity-75">{{ $totalDumai > 0 ? number_format($totalDumai) : '-' }}</td>
                                        <td class="border-light {{ $momColor }} font-weight-bold" style="font-size: 10px; vertical-align: middle; background: #fafafa">
                                            @if($prevTotalDumai > 0 || ($isCurrentMonth && $pct != 0))
                                                {{ number_format($pct, 1) }}% <span style="font-size: 8px;">{{ $icon }}</span>
                                            @else - @endif
                                        </td>
                                    @endfor
                                </tr>
                                                                <tr id="val-inject-cluster-dumai" class="validity-row d-none bg-light">
                                    <td colspan="25" class="p-0 border-0">
                                        <div class="py-2 pr-2" style="background: #e9f7ef">
                                            <table class="table table-sm table-bordered mb-0 bg-white shadow-sm rounded text-center" style="font-size: 11px;">
                                                <thead class="bg-white">
                                                    <tr class="text-success">
                                                        <th class="text-left border-0 sticky-validity-col">Validity (Cluster Dumai)</th>
                                                        @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $m)
                                                            <th class="border-0">{{ $m }}</th>
                                                        @endforeach
                                                        <th class="border-0 bg-light">TOTAL</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php 
                                                        $footTotals = array_fill(1, 12, 0); 
                                                        $footGrand = 0;
                                                    @endphp
                                                    @foreach($validityGroups as $vg)
                                                        @php 
                                                            $totalVG = 0; 
                                                            $mTotals = array_fill(1, 12, 0);
                                                            foreach($clusterDumai as $tap) {
                                                                foreach($validityMatrixInject[$tap][$vg] ?? [] as $m => $val) {
                                                                    $mTotals[$m] += $val;
                                                                    $totalVG += $val;
                                                                }
                                                            }
                                                            for($m=1;$m<=12;$m++) { $footTotals[$m] += $mTotals[$m]; }
                                                            $footGrand += $totalVG;
                                                        @endphp
                                                        @if($totalVG > 0)
                                                        <tr>
                                                            <td class="text-left font-weight-bold sticky-validity-col">{{ $vg }}</td>
                                                            @for($m=1; $m<=12; $m++)
                                                                <td>{{ $mTotals[$m] > 0 ? number_format($mTotals[$m]) : '-' }}</td>
                                                            @endfor
                                                            <td class="bg-light font-weight-bold">{{ number_format($totalVG) }}</td>
                                                        </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-light font-weight-bold">
                                                    <tr>
                                                        <td class="text-left sticky-validity-col">TOTAL</td>
                                                        @for($m=1; $m<=12; $m++)
                                                            <td>{{ $footTotals[$m] > 0 ? number_format($footTotals[$m]) : '-' }}</td>
                                                        @endfor
                                                        <td class="bg-success text-white">{{ number_format($footGrand) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </td>
                                </tr>

                                @endif

                                @if(in_array(session('idtap'), ['SBP_DUMAI', 'CLUSTER_ROHIL']))
                                {{-- FOOTER CLUSTER ROKAN HILIR --}}
                                <tr>
                                    <td class="text-left border-0 sticky-tap-col">
                                        <button class="btn btn-sm btn-light border toggle-validity rounded py-0 px-2 mr-2"
                                            data-target="val-inject-cluster-rohil">
                                            +
                                        </button>
                                        <span class="badge badge-light text-dark mr-1">CLUSTER</span> Rokan Hilir
                                    </td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $totalRohil = 0;
                                            $prevTotalRohil = 0;
                                            foreach($clusterRohil as $tap) { 
                                                $totalRohil += ($matrixInject[$tap][$i] ?? 0); 
                                                $prevTotalRohil += ($i == 1) ? ($prevDecInject[$tap] ?? 0) : ($matrixInject[$tap][$i-1] ?? 0);
                                            }

                                            $isCurrentMonth = ($selectedYear == $currentDate->year && $i == $currentDate->month);
                                            if($isCurrentMonth) {
                                                $pct = $momClusterInject['rokan_hilir'] ?? 0;
                                            } else {
                                                $pct = $prevTotalRohil > 0 ? (($totalRohil - $prevTotalRohil) / $prevTotalRohil) * 100 : 0;
                                            }
                                            $momColor = $pct > 0 ? 'text-success' : ($pct < 0 ? 'text-danger' : 'text-muted');
                                            $icon = $pct > 0 ? '↑' : ($pct < 0 ? '↓' : '');
                                        @endphp
                                        <td class="border-light opacity-75">{{ $totalRohil > 0 ? number_format($totalRohil) : '-' }}</td>
                                        <td class="border-light {{ $momColor }} font-weight-bold" style="font-size: 10px; vertical-align: middle; background: #fafafa">
                                            @if($prevTotalRohil > 0 || ($isCurrentMonth && $pct != 0))
                                                {{ number_format($pct, 1) }}% <span style="font-size: 8px;">{{ $icon }}</span>
                                            @else - @endif
                                        </td>
                                    @endfor
                                </tr>
                                                                <tr id="val-inject-cluster-rohil" class="validity-row d-none bg-light">
                                    <td colspan="25" class="p-0 border-0">
                                        <div class="py-2 pr-2" style="background: #e9f7ef">
                                            <table class="table table-sm table-bordered mb-0 bg-white shadow-sm rounded text-center" style="font-size: 11px;">
                                                <thead class="bg-white">
                                                    <tr class="text-success">
                                                        <th class="text-left border-0 sticky-validity-col">Validity (Cluster Rohil)</th>
                                                        @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $m)
                                                            <th class="border-0">{{ $m }}</th>
                                                        @endforeach
                                                        <th class="border-0 bg-light">TOTAL</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php 
                                                        $footTotals = array_fill(1, 12, 0); 
                                                        $footGrand = 0;
                                                    @endphp
                                                    @foreach($validityGroups as $vg)
                                                        @php 
                                                            $totalVG = 0; 
                                                            $mTotals = array_fill(1, 12, 0);
                                                            foreach($clusterRohil as $tap) {
                                                                foreach($validityMatrixInject[$tap][$vg] ?? [] as $m => $val) {
                                                                    $mTotals[$m] += $val;
                                                                    $totalVG += $val;
                                                                }
                                                            }
                                                            for($m=1;$m<=12;$m++) { $footTotals[$m] += $mTotals[$m]; }
                                                            $footGrand += $totalVG;
                                                        @endphp
                                                        @if($totalVG > 0)
                                                        <tr>
                                                            <td class="text-left font-weight-bold sticky-validity-col">{{ $vg }}</td>
                                                            @for($m=1; $m<=12; $m++)
                                                                <td>{{ $mTotals[$m] > 0 ? number_format($mTotals[$m]) : '-' }}</td>
                                                            @endfor
                                                            <td class="bg-light font-weight-bold">{{ number_format($totalVG) }}</td>
                                                        </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-light font-weight-bold">
                                                    <tr>
                                                        <td class="text-left sticky-validity-col">TOTAL</td>
                                                        @for($m=1; $m<=12; $m++)
                                                            <td>{{ $footTotals[$m] > 0 ? number_format($footTotals[$m]) : '-' }}</td>
                                                        @endfor
                                                        <td class="bg-success text-white">{{ number_format($footGrand) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </td>
                                </tr>

                                @endif

                                @if(session('idtap') == 'SBP_DUMAI')
                                {{-- GRAND TOTAL KESELURUHAN --}}
                                <tr style="background-color: #e8f5e9; color: #1b5e20;">
                                    <td class="text-left border-0 font-weight-bold sticky-tap-col">
                                        <button class="btn btn-sm btn-success border toggle-validity rounded py-0 px-2 mr-2"
                                            data-target="val-inject-grand-total">
                                            +
                                        </button>
                                        ✨ GRAND TOTAL
                                    </td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $totalGT = 0;
                                            $prevTotalGT = 0;
                                            foreach($matrixInject as $tap => $months) { 
                                                $totalGT += ($months[$i] ?? 0); 
                                                $prevTotalGT += ($i == 1) ? ($prevDecInject[$tap] ?? 0) : ($months[$i-1] ?? 0);
                                            }

                                            $isCurrentMonth = ($selectedYear == $currentDate->year && $i == $currentDate->month);
                                            if($isCurrentMonth) {
                                                $pct = $momGTInject;
                                            } else {
                                                $pct = $prevTotalGT > 0 ? (($totalGT - $prevTotalGT) / $prevTotalGT) * 100 : 0;
                                            }
                                            $momColor = $pct > 0 ? 'text-success' : ($pct < 0 ? 'text-danger' : 'text-white');
                                            $icon = $pct > 0 ? '↑' : ($pct < 0 ? '↓' : '');
                                        @endphp
                                        <td class="border-light font-weight-bold">{{ $totalGT > 0 ? number_format($totalGT) : '-' }}</td>
                                        <td class="border-light {{ $momColor }} font-weight-bold" style="font-size: 10px; vertical-align: middle; background: rgba(0,0,0,0.05)">
                                            @if($prevTotalGT > 0 || ($isCurrentMonth && $pct != 0))
                                                {{ number_format($pct, 1) }}% <span style="font-size: 8px;">{{ $icon }}</span>
                                            @else - @endif
                                        </td>
                                    @endfor
                                </tr>
                                                                <tr id="val-inject-grand-total" class="validity-row d-none" style="background-color: #f8fafc">
                                    <td colspan="25" class="p-0 border-0">
                                        <div class="py-2 pr-2" style="background: #e8f5e9">
                                            <table class="table table-sm table-bordered mb-0 bg-white shadow-sm rounded text-center" style="font-size: 11px;">
                                                <thead class="bg-white">
                                                    <tr style="color: #1b5e20">
                                                        <th class="text-left border-0 sticky-validity-col">Validity (Grand Total)</th>
                                                        @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $m)
                                                            <th class="border-0">{{ $m }}</th>
                                                        @endforeach
                                                        <th class="border-0 bg-light">TOTAL</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php 
                                                        $footTotals = array_fill(1, 12, 0); 
                                                        $footGrand = 0;
                                                    @endphp
                                                    @foreach($validityGroups as $vg)
                                                        @php 
                                                            $totalVG = 0; 
                                                            $mTotals = array_fill(1, 12, 0);
                                                            foreach($matrixInject as $tap => $months) {
                                                                foreach($validityMatrixInject[$tap][$vg] ?? [] as $m => $val) {
                                                                    $mTotals[$m] += $val;
                                                                    $totalVG += $val;
                                                                }
                                                            }
                                                            for($m=1;$m<=12;$m++) { $footTotals[$m] += $mTotals[$m]; }
                                                            $footGrand += $totalVG;
                                                        @endphp
                                                        @if($totalVG > 0)
                                                        <tr>
                                                            <td class="text-left font-weight-bold sticky-validity-col">{{ $vg }}</td>
                                                            @for($m=1; $m<=12; $m++)
                                                                <td>{{ $mTotals[$m] > 0 ? number_format($mTotals[$m]) : '-' }}</td>
                                                            @endfor
                                                            <td class="bg-light font-weight-bold">{{ number_format($totalVG) }}</td>
                                                        </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-light font-weight-bold">
                                                    <tr>
                                                        <td class="text-left sticky-validity-col">TOTAL</td>
                                                        @for($m=1; $m<=12; $m++)
                                                            <td>{{ $footTotals[$m] > 0 ? number_format($footTotals[$m]) : '-' }}</td>
                                                        @endfor
                                                        <td class="bg-success text-white">{{ number_format($footGrand) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </td>
                                </tr>

                                @endif
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>


            {{-- ================= CHARTS LINE & BAR ================= --}}
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card enterprise-shadow h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between">
                            <div>
                                <h6 class="font-weight-bold text-dark mb-0">📈 Tren Sales ({{ ucfirst($mode) }})</h6>
                                <small class="text-muted">Analisa riwayat panjang penjualan</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div style="height:300px">
                                <canvas id="chartSales"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card enterprise-shadow h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-0">
                            <h6 class="font-weight-bold text-dark mb-0">⚡ Inject per Cluster</h6>
                            <small class="text-muted">Quantity Inject Cluster</small>
                        </div>
                        <div class="card-body">
                            <div style="height:300px">
                                <canvas id="chartInject"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= ACTIVITY & MoM DAILY ================= --}}
            <div class="row mb-4">
                <div class="col-md-5">
                    <div class="card enterprise-shadow h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-0">
                            <h6 class="font-weight-bold text-dark mb-0">🗓️ Feed Aktivitas TAP</h6>
                            <small class="text-muted">Pantau input masuk/keluar harian terakhir</small>
                        </div>
                        <div class="card-body pt-4">
                            <div class="feed-container">
                                @foreach ($tapList as $tap)
                                    @php
                                        $masuk = $tapMasuk[$tap] ?? null;
                                        $keluar = $tapKeluar[$tap] ?? null;
                                        $latest = max($masuk, $keluar);
                                        $diff = $latest ? \Carbon\Carbon::parse($latest)->diffInDays(now()) : null;
                                        
                                        $isDanger = (is_null($latest) || $diff > 3);
                                    @endphp
                                    <div class="feed-item {{ $isDanger ? 'danger-feed' : '' }}">
                                        <div class="d-flex justify-content-between">
                                            <strong class="text-dark">{{ $tap }}</strong>
                                            @if (is_null($latest))
                                                <span class="badge badge-secondary badge-pill">No Data</span>
                                            @elseif ($diff <= 1)
                                                <span class="badge badge-success badge-pill bg-success text-white">Aktif ({{ $diff }} hr)</span>
                                            @elseif ($diff <= 3)
                                                <span class="badge badge-warning badge-pill text-dark">Warning ({{ $diff }} hr)</span>
                                            @else
                                                <span class="badge badge-danger badge-pill bg-danger text-white">Drop ({{ $diff }} hr)</span>
                                            @endif
                                        </div>
                                        <div class="small mt-1 text-muted">
                                            <i data-feather="download" style="width:12px;height:12px"></i> In: {{ $masuk ? \Carbon\Carbon::parse($masuk)->format('d/m H:i') : 'N/A' }} &nbsp;
                                            <i data-feather="upload" style="width:12px;height:12px"></i> Out: {{ $keluar ? \Carbon\Carbon::parse($keluar)->format('d/m H:i') : 'N/A' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card enterprise-shadow h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-0">
                            <h6 class="font-weight-bold text-dark mb-0">📉 Komparasi Sales (Day-to-day MoM)</h6>
                            <small class="text-muted">Adu laju pencapaian bulan berjalan vs bulan lalu</small>
                        </div>
                        <div class="card-body">
                            <div style="height:400px">
                                <canvas id="chartMomDaily"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= MoM PER TAP ================= --}}
            @php
                $momCurrentLabel = strtoupper($momSelectedDate->copy()->locale('id')->translatedFormat('d M'));
                $momPreviousLabel = strtoupper($momEndPrevMonthPartial->copy()->locale('id')->translatedFormat('d M'));
            @endphp
            <div class="card enterprise-shadow mb-4" id="mom-cluster-card">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <div class="d-flex align-items-start justify-content-between flex-wrap" style="gap:12px;">
                        <div>
                            <h6 class="font-weight-bold text-dark mb-0">📊 MoM Sales Tap</h6>
                            <small class="text-muted">
                                1–{{ $momCurrentLabel }} vs 1–{{ $momPreviousLabel }}
                            </small>
                        </div>
                        <div class="d-flex align-items-center export-actions">
                            <form method="GET" action="{{ url('home') }}#mom-cluster-card"
                                class="d-flex align-items-center mb-0">
                                <input type="hidden" name="year" value="{{ $selectedYear }}">
                                <input type="hidden" name="sales_year" value="{{ $salesYear }}">
                                <input type="hidden" name="sales_view" value="{{ $salesView }}">
                                <input type="hidden" name="sales_period" value="{{ $salesPeriod }}">
                                <input type="hidden" name="sales_annual_cutoff" value="{{ $annualCutoff }}">
                                <input type="hidden" name="mode" value="{{ $mode }}">
                                <label for="mom_date" class="mb-0 mr-2 small font-weight-bold text-muted">Tanggal MoM</label>
                                <input type="date" name="mom_date" id="mom_date"
                                    class="form-control form-control-sm"
                                    value="{{ $momSelectedDate->toDateString() }}"
                                    max="{{ $currentDate->toDateString() }}">
                            </form>
                            <button type="button" class="btn btn-sm export-action-btn d-inline-flex align-items-center justify-content-center" id="mom-copy-btn" title="Copy sebagai gambar" style="gap:6px;">
                                <i data-feather="copy" style="width:14px;height:14px"></i> Copy
                            </button>
                            <button type="button" class="btn btn-sm export-action-btn d-inline-flex align-items-center justify-content-center" id="mom-download-btn" title="Download PNG" style="gap:6px;">
                                <i data-feather="download" style="width:14px;height:14px"></i> PNG
                            </button>
                        </div>
                        <small id="mom-export-status" class="text-muted w-100 export-status"></small>
                    </div>
                </div>
                <div class="table-responsive" id="mom-cluster-table-wrap">
                    <table class="table table-hover mb-0">
                        <colgroup>
                            <col class="mom-location-col">
                            <col class="mom-number-col">
                            <col class="mom-number-col">
                            <col class="mom-number-col">
                            <col class="mom-growth-col">
                        </colgroup>
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th class="border-0">LOKASI (TAP / SF)</th>
                                <th class="text-right border-0">MTD ({{ $momCurrentLabel }})</th>
                                <th class="text-right border-0">M-1 ({{ $momPreviousLabel }})</th>
                                <!-- <th class="text-right border-0">M-2 PARTIAL</th> -->
                                <th class="text-right border-0">M-1 FULL</th>
                                <th class="text-right border-0">GROWTH (M-1)</th>
                                <!-- <th class="text-right border-0">GROWTH (M-2)</th> -->
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($momTap as $r)
                                <tr class="bg-white">
                                    <td class="font-weight-bold text-dark">
                                        <button class="btn btn-sm btn-light border toggle-sf rounded py-0 px-2 mr-2"
                                            data-target="sf-{{ Str::slug($r->idtap) }}">
                                            +
                                        </button>
                                        {{ $r->idtap }}
                                    </td>
                                    <td class="text-right font-weight-bold text-dark">{{ number_format($r->curr_qty) }}</td>
                                    <td class="text-right text-muted">{{ number_format($r->prev_partial_qty) }}</td>
                                    <!-- <td class="text-right text-muted">{{ number_format($r->m2_partial_qty) }}</td> -->
                                    <td class="text-right text-muted">{{ number_format($r->prev_full_qty) }}</td>
                                    <td class="text-right font-weight-bold {{ $r->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                        {!! $r->mom >= 0 ? '▲' : '▼' !!} {{ number_format(abs($r->mom), 2) }} %
                                    </td>
                                    <!-- <td class="text-right font-weight-bold {{ $r->mom_m2 >= 0 ? 'text-success' : 'text-danger' }}">
                                        {!! $r->mom_m2 >= 0 ? '▲' : '▼' !!} {{ number_format(abs($r->mom_m2), 2) }} %
                                    </td> -->
                                </tr>

                                <tr id="sf-{{ Str::slug($r->idtap) }}" class="sf-row d-none bg-light">
                                    <td colspan="7" class="p-0 border-0">
                                        <div class="p-3 bg-light border-left border-right">
                                            <table class="table table-sm mb-0 bg-white shadow-sm rounded">
                                                <thead class="bg-white">
                                                    <tr>
                                                        <th class="border-top-0">Nama Petugas</th>
                                                        <th class="text-right border-top-0">Capai MTD</th>
                                                        <th class="text-right border-top-0">M-1</th>
                                                        <!-- <th class="text-right border-top-0">M-2</th> -->
                                                        <th class="text-right border-top-0">Growth (M-1)</th>
                                                        <!-- <th class="text-right border-top-0">Growth (M-2)</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($sfByTap[$r->idtap] ?? [] as $sf)
                                                        <tr>
                                                            <td class="text-muted border-bottom-0"><i data-feather="user" style="width:14px;height:14px"></i> {{ $sf->namasf ?? $sf->idsf }}</td>
                                                            <td class="text-right font-weight-bold border-bottom-0">{{ number_format($sf->curr_qty) }}</td>
                                                            <td class="text-right text-muted border-bottom-0">{{ number_format($sf->prev_qty) }}</td>
                                                            <td class="text-right text-muted border-bottom-0">{{ number_format($sf->m2_qty) }}</td>
                                                            <td class="text-right border-bottom-0 {{ $sf->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                                                <span class="badge badge-{{ $sf->mom >= 0 ? 'success' : 'danger' }} badge-pill bg-{{ $sf->mom >= 0 ? 'success' : 'danger' }} text-white font-weight-bold px-2 py-1">
                                                                    {!! $sf->mom >= 0 ? '&#43;' : '' !!} {{ number_format($sf->mom, 2) }} %
                                                                </span>
                                                            </td>
                                                            <!-- <td class="text-right border-bottom-0 {{ $sf->mom_m2 >= 0 ? 'text-success' : 'text-danger' }}">
                                                                <span class="badge badge-{{ $sf->mom_m2 >= 0 ? 'success' : 'danger' }} badge-pill bg-{{ $sf->mom_m2 >= 0 ? 'success' : 'danger' }} text-white font-weight-bold px-2 py-1">
                                                                    {!! $sf->mom_m2 >= 0 ? '&#43;' : '' !!} {{ number_format($sf->mom_m2, 2) }} %
                                                                </span>
                                                            </td> -->
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot class="bg-light text-dark font-weight-bold" style="border-top: 2px solid #ddd">
                            @if(in_array(session('idtap'), ['SBP_DUMAI', 'CLUSTER_DUMAI']))
                            <tr>
                                <td class="border-0"><span class="badge badge-secondary mr-2">CLUSTER</span> Dumai Bengkalis</td>
                                <td class="text-right border-0">{{ number_format($momCluster['dumai_bengkalis']->curr_qty) }}</td>
                                <td class="text-right opacity-75 border-0">{{ number_format($momCluster['dumai_bengkalis']->prev_partial_qty) }}</td>
                                <!-- <td class="text-right opacity-75 border-0">{{ number_format($momCluster['dumai_bengkalis']->m2_partial_qty) }}</td> -->
                                <td class="text-right opacity-75 border-0">{{ number_format($momCluster['dumai_bengkalis']->prev_full_qty) }}</td>
                                <td class="text-right border-0 {{ $momCluster['dumai_bengkalis']->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $momCluster['dumai_bengkalis']->mom >= 0 ? '▲' : '▼' !!} {{ number_format(abs($momCluster['dumai_bengkalis']->mom), 2) }} %
                                </td>
                                <!-- <td class="text-right border-0 {{ $momCluster['dumai_bengkalis']->mom_m2 >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $momCluster['dumai_bengkalis']->mom_m2 >= 0 ? '▲' : '▼' !!} {{ number_format(abs($momCluster['dumai_bengkalis']->mom_m2), 2) }} %
                                </td> -->
                            </tr>
                            @endif
                            @if(in_array(session('idtap'), ['SBP_DUMAI', 'CLUSTER_ROHIL']))
                            <tr>
                                <td class="border-0"><span class="badge badge-light text-dark mr-2">CLUSTER</span> Rokan Hilir</td>
                                <td class="text-right border-0">{{ number_format($momCluster['rokan_hilir']->curr_qty) }}</td>
                                <td class="text-right opacity-75 border-0">{{ number_format($momCluster['rokan_hilir']->prev_partial_qty) }}</td>
                                <!-- <td class="text-right opacity-75 border-0">{{ number_format($momCluster['rokan_hilir']->m2_partial_qty) }}</td> -->
                                <td class="text-right opacity-75 border-0">{{ number_format($momCluster['rokan_hilir']->prev_full_qty) }}</td>
                                <td class="text-right border-0 {{ $momCluster['rokan_hilir']->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $momCluster['rokan_hilir']->mom >= 0 ? '▲' : '▼' !!} {{ number_format(abs($momCluster['rokan_hilir']->mom), 2) }} %
                                </td>
                                <!-- <td class="text-right border-0 {{ $momCluster['rokan_hilir']->mom_m2 >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $momCluster['rokan_hilir']->mom_m2 >= 0 ? '▲' : '▼' !!} {{ number_format(abs($momCluster['rokan_hilir']->mom_m2), 2) }} %
                                </td> -->
                            </tr>
                            @endif
                            @if(session('idtap') == 'SBP_DUMAI')
                            @php
                                $grandCurr = $momCluster['dumai_bengkalis']->curr_qty + $momCluster['rokan_hilir']->curr_qty;
                                $grandPrevPart = $momCluster['dumai_bengkalis']->prev_partial_qty + $momCluster['rokan_hilir']->prev_partial_qty;
                                $grandPrevFull = $momCluster['dumai_bengkalis']->prev_full_qty + $momCluster['rokan_hilir']->prev_full_qty;
                                $grandMom = $grandPrevPart > 0 ? (($grandCurr - $grandPrevPart) / $grandPrevPart) * 100 : 0;
                            @endphp
                            <tr style="background-color: #e3f2fd; color: #0d47a1;">
                                <td class="border-0 font-weight-bold">✨ GRAND TOTAL MOM</td>
                                <td class="text-right border-0 font-weight-bold">{{ number_format($grandCurr) }}</td>
                                <td class="text-right opacity-75 border-0 font-weight-bold">{{ number_format($grandPrevPart) }}</td>
                                <!-- <td class="text-right opacity-75 border-0 font-weight-bold">{{ number_format($momCluster['dumai_bengkalis']->m2_partial_qty + $momCluster['rokan_hilir']->m2_partial_qty) }}</td> -->
                                <td class="text-right opacity-75 border-0 font-weight-bold">{{ number_format($grandPrevFull) }}</td>
                                <td class="text-right border-0 font-weight-bold {{ $grandMom >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $grandMom >= 0 ? '▲' : '▼' !!} {{ number_format(abs($grandMom), 2) }} %
                                </td>
                                @php
                                    $grandM2Part = $momCluster['dumai_bengkalis']->m2_partial_qty + $momCluster['rokan_hilir']->m2_partial_qty;
                                    $grandMomM2 = $grandM2Part > 0 ? (($grandCurr - $grandM2Part) / $grandM2Part) * 100 : 0;
                                @endphp
                                <!-- <td class="text-right border-0 font-weight-bold {{ $grandMomM2 >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $grandMomM2 >= 0 ? '▲' : '▼' !!} {{ number_format(abs($grandMomM2), 2) }} %
                                </td> -->
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- close --}}
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const nf = v => new Intl.NumberFormat('id-ID').format(v); 

        function setMomExportStatus(text, isError) {
            const el = document.getElementById('mom-export-status');
            if (!el) return;
            el.style.display = text ? 'block' : 'none';
            el.classList.remove('text-danger', 'text-success', 'text-muted');
            el.classList.add(isError ? 'text-danger' : 'text-success');
            el.textContent = text || '';
        }

        function getMomCellText(cell) {
            return (cell?.innerText || '')
                .replace(/\s+/g, ' ')
                .replace(/^\+\s*/, '')
                .trim();
        }

        function getMomExportRows() {
            const table = document.querySelector('#mom-cluster-table-wrap > table');
            if (!table) return [];

            const rows = [];
            const mainBodyRows = Array.from(table.tBodies?.[0]?.rows || [])
                .filter(tr => !tr.classList.contains('sf-row'));
            const mainFootRows = Array.from(table.tFoot?.rows || []);

            [...mainBodyRows, ...mainFootRows].forEach(tr => {
                // Penting: pakai tr.cells dari row utama saja, jangan querySelectorAll,
                // supaya baris nested Performance Petugas/SF tidak ikut ke-export.
                const cells = Array.from(tr.cells || []);
                if (cells.length < 5) return;
                const firstText = getMomCellText(cells[0]).replace(/^CLUSTER\s+/i, '');
                const growthText = getMomCellText(cells[4]);
                rows.push({
                    type: tr.parentElement?.tagName === 'TFOOT'
                        ? (firstText.toUpperCase().includes('GRAND TOTAL') ? 'grand' : 'cluster')
                        : 'tap',
                    cells: [
                        firstText,
                        getMomCellText(cells[1]),
                        getMomCellText(cells[2]),
                        getMomCellText(cells[3]),
                        growthText
                    ],
                    growth1Down: growthText.includes('▼') || /^-/.test(growthText)
                });
            });
            return rows;
        }

        function getMomExportHeaders() {
            const table = document.querySelector('#mom-cluster-table-wrap > table');
            const headerCells = Array.from(table?.tHead?.rows?.[0]?.cells || []);

            if (headerCells.length < 5) {
                return [
                    'LOKASI (TAP / SF)',
                    @json("MTD ({$momCurrentLabel})"),
                    @json("M-1 ({$momPreviousLabel})"),
                    'M-1 FULL',
                    'GROWTH (M-1)'
                ];
            }

            return headerCells.slice(0, 5).map(cell => getMomCellText(cell));
        }

        function roundRect(ctx, x, y, width, height, radius) {
            const r = Math.min(radius, width / 2, height / 2);
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + width, y, x + width, y + height, r);
            ctx.arcTo(x + width, y + height, x, y + height, r);
            ctx.arcTo(x, y + height, x, y, r);
            ctx.arcTo(x, y, x + width, y, r);
            ctx.closePath();
        }

        function drawText(ctx, text, x, y, options = {}) {
            const {
                color = '#343a40',
                font = '600 22px Arial, sans-serif',
                align = 'left',
                baseline = 'middle',
                maxWidth
            } = options;
            ctx.fillStyle = color;
            ctx.font = font;
            ctx.textAlign = align;
            ctx.textBaseline = baseline;
            if (maxWidth) {
                ctx.fillText(text, x, y, maxWidth);
            } else {
                ctx.fillText(text, x, y);
            }
        }

        function wrapText(ctx, text, maxWidth) {
            const words = String(text).split(' ');
            const lines = [];
            let line = '';
            words.forEach(word => {
                const testLine = line ? `${line} ${word}` : word;
                if (ctx.measureText(testLine).width <= maxWidth || !line) {
                    line = testLine;
                } else {
                    lines.push(line);
                    line = word;
                }
            });
            if (line) lines.push(line);
            return lines;
        }

        function drawWrappedText(ctx, text, x, centerY, maxWidth, lineHeight, options = {}) {
            ctx.font = options.font || '700 22px Arial, sans-serif';
            const lines = wrapText(ctx, text, maxWidth).slice(0, options.maxLines || 3);
            const startY = centerY - ((lines.length - 1) * lineHeight / 2);
            lines.forEach((line, index) => {
                drawText(ctx, line, x, startY + (index * lineHeight), options);
            });
        }

        function getCardExportHeading(cardId, fallbackTitle, fallbackSubtitle) {
            const card = document.getElementById(cardId);
            return {
                title: card?.querySelector('.card-header h6')?.innerText?.trim() || fallbackTitle,
                subtitle: card?.querySelector('.card-header small.text-muted')?.innerText?.trim() || fallbackSubtitle
            };
        }

        async function renderMomClusterCardToCanvas() {
            const rows = getMomExportRows();
            if (!rows.length) return null;
            const headers = getMomExportHeaders();
            const heading = getCardExportHeading(
                'mom-cluster-card',
                '📊 MoM Sales Tap',
                @json("1–{$momCurrentLabel} vs 1–{$momPreviousLabel}")
            );

            // Render dibuat mengikuti tampilan card asli, bukan versi export yang beda layout.
            const pixelRatio = 2;
            const width = 1120;
            const titleHeight = 118;
            const headHeight = 62;
            const rowHeight = 62;
            const height = titleHeight + headHeight + (rows.length * rowHeight);
            const canvas = document.createElement('canvas');
            canvas.width = Math.ceil(width * pixelRatio);
            canvas.height = Math.ceil(height * pixelRatio);
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            if (!ctx) return null;
            ctx.scale(pixelRatio, pixelRatio);

            const cols = [350, 160, 220, 230, 160];
            const lefts = cols.reduce((acc, col, i) => {
                acc.push(i === 0 ? 0 : acc[i - 1] + cols[i - 1]);
                return acc;
            }, []);

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, width, height);

            // Header card seperti UI.
            drawText(ctx, heading.title, 28, 38, {
                font: '700 15px Arial, sans-serif',
                color: '#2f3437'
            });
            drawText(ctx, heading.subtitle, 28, 62, {
                font: '400 16px Arial, sans-serif',
                color: '#7b858e'
            });

            function drawPill(text, x, y, w) {
                roundRect(ctx, x, y, w, 44, 18);
                ctx.fillStyle = '#f5f6f8';
                ctx.fill();
                ctx.strokeStyle = '#eef0f2';
                ctx.lineWidth = 1;
                ctx.stroke();
                drawText(ctx, text, x + (w / 2), y + 22, {
                    font: '700 13px Arial, sans-serif',
                    color: '#111827',
                    align: 'center'
                });
            }
            drawPill('Copy', width - 210, 28, 92);
            drawPill('PNG', width - 105, 28, 92);

            ctx.strokeStyle = '#edf0f2';
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.moveTo(0, titleHeight);
            ctx.lineTo(width, titleHeight);
            ctx.stroke();

            // Header tabel.
            ctx.fillStyle = '#f8f9fa';
            ctx.fillRect(0, titleHeight, width, headHeight);
            headers.forEach((header, index) => {
                const x = index === 0 ? lefts[index] + 18 : lefts[index] + cols[index] - 18;
                drawText(ctx, header, x, titleHeight + 32, {
                    font: '700 16px Arial, sans-serif',
                    color: '#55554c',
                    align: index === 0 ? 'left' : 'right'
                });
            });

            rows.forEach((row, rowIndex) => {
                const y = titleHeight + headHeight + (rowIndex * rowHeight);
                const centerY = y + (rowHeight / 2);
                const isGrand = row.type === 'grand';
                const isCluster = row.type === 'cluster';

                ctx.fillStyle = isGrand ? '#e3f2fd' : (isCluster ? '#f8f9fa' : '#ffffff');
                ctx.fillRect(0, y, width, rowHeight);

                ctx.strokeStyle = isCluster || isGrand ? '#dfe3e7' : '#edf0f2';
                ctx.lineWidth = isCluster || isGrand ? 1.6 : 1;
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(width, y);
                ctx.stroke();

                if (row.type === 'tap') {
                    roundRect(ctx, 18, centerY - 12, 32, 24, 4);
                    ctx.fillStyle = '#ffffff';
                    ctx.fill();
                    ctx.strokeStyle = '#dfe4ea';
                    ctx.stroke();
                    drawText(ctx, '+', 34, centerY, {
                        font: '400 14px Arial, sans-serif',
                        color: '#343a40',
                        align: 'center'
                    });
                    drawText(ctx, row.cells[0], 68, centerY, {
                        font: '700 16px Arial, sans-serif',
                        color: '#2f3437',
                        maxWidth: cols[0] - 78
                    });
                } else if (row.type === 'cluster') {
                    const purple = rowIndex % 2 === 0;
                    roundRect(ctx, 18, centerY - 15, 90, 30, 14);
                    ctx.fillStyle = purple ? '#7467d4' : '#ffffff';
                    ctx.fill();
                    ctx.strokeStyle = purple ? '#7467d4' : '#dfe4ea';
                    ctx.stroke();
                    drawText(ctx, 'CLUSTER', 63, centerY, {
                        font: '400 14px Arial, sans-serif',
                        color: purple ? '#ffffff' : '#55554c',
                        align: 'center'
                    });
                    drawText(ctx, row.cells[0], 118, centerY, {
                        font: '700 16px Arial, sans-serif',
                        color: '#2f3437',
                        maxWidth: cols[0] - 126
                    });
                } else {
                    drawText(ctx, row.cells[0], 18, centerY, {
                        font: '700 16px Arial, sans-serif',
                        color: '#1657b7',
                        maxWidth: cols[0] - 36
                    });
                }

                for (let i = 1; i < row.cells.length; i++) {
                    let color = '#8a8f98';
                    let font = '400 16px Arial, sans-serif';
                    if (i === 1 || isCluster || isGrand) {
                        color = isGrand ? '#1657b7' : '#2f3437';
                        font = '700 16px Arial, sans-serif';
                    }
                    if (i === 4) {
                        color = row.growth1Down ? '#e75757' : '#32a852';
                        font = '700 16px Arial, sans-serif';
                    }

                    drawText(ctx, row.cells[i], lefts[i] + cols[i] - 18, centerY, {
                        font,
                        color,
                        align: 'right',
                        maxWidth: cols[i] - 28
                    });
                }
            });

            return canvas;
        }

        async function canvasToPngBlob(canvas) {
            if (canvas.toBlob) {
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                if (blob) return blob;
            }

            const dataUrl = canvas.toDataURL('image/png');
            const response = await fetch(dataUrl);
            return response.blob();
        }

        function setExportStatus(statusId, text, isError) {
            const el = document.getElementById(statusId);
            if (!el) return;
            el.style.display = text ? 'block' : 'none';
            el.classList.remove('text-danger', 'text-success', 'text-muted');
            el.classList.add(isError ? 'text-danger' : 'text-success');
            el.textContent = text || '';
        }

        function renderArrowText(text) {
            return text.replace('↑', '▲').replace('↓', '▼');
        }

        function getHeatmapExportRows(wrapId) {
            const wrap = document.getElementById(wrapId);
            const table = wrap?.querySelector('table');
            if (!table) return [];

            const rowSelector = 'tbody > tr:not(.validity-row), tfoot > tr:not(.validity-row)';
            return Array.from(table.querySelectorAll(rowSelector)).map(tr => {
                const cells = Array.from(tr.children);
                if (cells.length < 25) return null;
                const firstRaw = getMomCellText(cells[0]);
                const firstText = firstRaw.replace(/^CLUSTER\s+/i, '');
                const months = [];
                for (let i = 1; i < cells.length; i += 2) {
                    const valueCell = cells[i];
                    const momCell = cells[i + 1];
                    months.push({
                        value: getMomCellText(valueCell),
                        mom: renderArrowText(getMomCellText(momCell)),
                        valueBg: valueCell?.style?.backgroundColor || '',
                        valueColor: valueCell?.style?.color || '',
                        momDown: getMomCellText(momCell).includes('↓') || /^-\d/.test(getMomCellText(momCell)),
                        momUp: getMomCellText(momCell).includes('↑') || /^\d/.test(getMomCellText(momCell))
                    });
                }

                return {
                    type: firstText.toUpperCase().includes('GRAND TOTAL')
                        ? 'grand'
                        : (firstRaw.toUpperCase().includes('CLUSTER') ? 'cluster' : 'tap'),
                    name: firstText,
                    months
                };
            }).filter(Boolean);
        }

        async function renderHeatmapToCanvas(config) {
            const rows = getHeatmapExportRows(config.wrapId);
            if (!rows.length) return null;
            const heading = getCardExportHeading(config.cardId, config.title, config.subtitle);

            const pixelRatio = 1.4;
            const monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
            const firstCol = 340;
            const valueCol = 116;
            const momCol = 84;
            const width = firstCol + (monthNames.length * (valueCol + momCol));
            const titleHeight = 118;
            const headHeight = 78;
            const rowHeight = 72;
            const height = titleHeight + headHeight + (rows.length * rowHeight);
            const canvas = document.createElement('canvas');
            canvas.width = Math.ceil(width * pixelRatio);
            canvas.height = Math.ceil(height * pixelRatio);
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            if (!ctx) return null;
            ctx.scale(pixelRatio, pixelRatio);

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, width, height);

            drawText(ctx, heading.title, 38, 42, {
                font: '700 24px Arial, sans-serif',
                color: '#343a40'
            });
            drawText(ctx, heading.subtitle, 38, 76, {
                font: '400 23px Arial, sans-serif',
                color: '#7b858e'
            });

            ctx.strokeStyle = '#e9ecef';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(0, titleHeight);
            ctx.lineTo(width, titleHeight);
            ctx.stroke();

            ctx.fillStyle = '#f8f9fa';
            ctx.fillRect(0, titleHeight, width, headHeight);
            drawText(ctx, 'NAMA TAP', 24, titleHeight + 39, {
                font: '700 21px Arial, sans-serif',
                color: '#55554c'
            });

            monthNames.forEach((month, index) => {
                const x = firstCol + (index * (valueCol + momCol));
                drawText(ctx, month, x + (valueCol / 2), titleHeight + 28, {
                    font: '700 18px Arial, sans-serif',
                    color: '#55554c',
                    align: 'center'
                });
                drawText(ctx, 'MoM', x + valueCol + (momCol / 2), titleHeight + 28, {
                    font: '700 15px Arial, sans-serif',
                    color: '#7b858e',
                    align: 'center'
                });
                drawText(ctx, '%', x + valueCol + (momCol / 2), titleHeight + 52, {
                    font: '700 13px Arial, sans-serif',
                    color: '#9aa3aa',
                    align: 'center'
                });
            });

            rows.forEach((row, rowIndex) => {
                const y = titleHeight + headHeight + (rowIndex * rowHeight);
                const centerY = y + (rowHeight / 2);
                const isGrand = row.type === 'grand';
                const isCluster = row.type === 'cluster';

                ctx.fillStyle = isGrand ? config.grandBg : (isCluster ? '#f8f9fa' : '#ffffff');
                ctx.fillRect(0, y, width, rowHeight);

                ctx.strokeStyle = isCluster || isGrand ? '#dedede' : '#e9ecef';
                ctx.lineWidth = isCluster || isGrand ? 2 : 1.3;
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(width, y);
                ctx.stroke();

                if (row.type === 'tap') {
                    roundRect(ctx, 22, centerY - 16, 40, 32, 6);
                    ctx.fillStyle = '#ffffff';
                    ctx.fill();
                    ctx.strokeStyle = '#dde2e7';
                    ctx.stroke();
                    drawText(ctx, '+', 42, centerY, {
                        font: '400 18px Arial, sans-serif',
                        color: '#343a40',
                        align: 'center'
                    });
                    drawWrappedText(ctx, row.name, 78, centerY, firstCol - 96, 25, {
                        font: '700 21px Arial, sans-serif',
                        color: '#343a40',
                        maxLines: 2
                    });
                } else if (row.type === 'cluster') {
                    roundRect(ctx, 20, centerY - 19, 112, 38, 19);
                    ctx.fillStyle = config.accent;
                    ctx.fill();
                    drawText(ctx, 'CLUSTER', 76, centerY, {
                        font: '400 17px Arial, sans-serif',
                        color: '#ffffff',
                        align: 'center'
                    });
                    drawWrappedText(ctx, row.name, 148, centerY, firstCol - 166, 24, {
                        font: '700 20px Arial, sans-serif',
                        color: '#343a40',
                        maxLines: 2
                    });
                } else {
                    drawWrappedText(ctx, row.name, 22, centerY, firstCol - 42, 25, {
                        font: '700 21px Arial, sans-serif',
                        color: config.grandColor,
                        maxLines: 2
                    });
                }

                row.months.forEach((month, index) => {
                    const x = firstCol + (index * (valueCol + momCol));
                    const valueBg = month.valueBg && month.valueBg !== 'transparent'
                        ? month.valueBg
                        : (isGrand ? config.grandBg : '#ffffff');

                    ctx.fillStyle = valueBg;
                    ctx.fillRect(x, y, valueCol, rowHeight);
                    ctx.fillStyle = isGrand ? 'rgba(0,0,0,0.05)' : '#fafafa';
                    ctx.fillRect(x + valueCol, y, momCol, rowHeight);

                    ctx.strokeStyle = '#edf0f2';
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.moveTo(x, y);
                    ctx.lineTo(x, y + rowHeight);
                    ctx.moveTo(x + valueCol, y);
                    ctx.lineTo(x + valueCol, y + rowHeight);
                    ctx.stroke();

                    const valueColor = isGrand ? config.grandColor : (month.valueColor || '#444');
                    drawText(ctx, month.value, x + valueCol - 12, centerY, {
                        font: (isGrand || isCluster) ? '700 18px Arial, sans-serif' : '600 17px Arial, sans-serif',
                        color: valueColor,
                        align: 'right',
                        maxWidth: valueCol - 18
                    });

                    let momColor = '#9aa3aa';
                    if (month.momDown) momColor = '#ff5b66';
                    if (!month.momDown && month.momUp && month.mom !== '-') momColor = '#32a852';
                    drawText(ctx, month.mom, x + valueCol + momCol - 9, centerY, {
                        font: '700 15px Arial, sans-serif',
                        color: momColor,
                        align: 'right',
                        maxWidth: momCol - 12
                    });
                });
            });

            return canvas;
        }

        async function renderActiveSalesChartToCanvas() {
            const source = document.getElementById('monthly-sales-chart');
            if (!source || !monthlySalesChart) return null;

            const heading = getCardExportHeading('heatmap-sales-card', 'Penjualan', '');
            const titleHeight = 150;
            const canvas = document.createElement('canvas');
            canvas.width = source.width;
            canvas.height = source.height + titleHeight;
            const ctx = canvas.getContext('2d');
            if (!ctx) return null;

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            drawText(ctx, heading.title, 42, 46, {
                font: '700 30px Arial, sans-serif',
                color: '#343a40'
            });
            drawText(ctx, heading.subtitle, 42, 91, {
                font: '400 24px Arial, sans-serif',
                color: '#7b858e'
            });
            ctx.strokeStyle = '#e9ecef';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(0, titleHeight - 1);
            ctx.lineTo(canvas.width, titleHeight - 1);
            ctx.stroke();
            ctx.drawImage(source, 0, titleHeight);
            return canvas;
        }

        async function renderAnnualSalesTableToCanvas() {
            const table = document.querySelector('#heatmap-sales-wrap .annual-sales-table');
            if (!table) return null;

            const headers = Array.from(table.querySelectorAll('thead th')).map(getMomCellText);
            const rows = Array.from(table.querySelectorAll('tbody > tr, tfoot > tr')).map(row => ({
                element: row,
                cells: Array.from(row.children).map((cell, index) => {
                    const text = getMomCellText(cell);
                    return index === 0 ? text.replace(/^CLUSTER\s*/i, 'CLUSTER ') : text;
                })
            }));
            if (!headers.length || !rows.length) return null;

            const heading = getCardExportHeading('heatmap-sales-card', 'Penjualan Tahunan', '');
            const pixelRatio = 1.5;
            const firstColumn = 310;
            const otherColumn = 178;
            const columns = headers.map((_, index) => index === 0 ? firstColumn : otherColumn);
            const width = columns.reduce((total, value) => total + value, 0);
            const titleHeight = 112;
            const headerHeight = 68;
            const rowHeight = 62;
            const height = titleHeight + headerHeight + (rows.length * rowHeight);
            const canvas = document.createElement('canvas');
            canvas.width = Math.ceil(width * pixelRatio);
            canvas.height = Math.ceil(height * pixelRatio);
            const ctx = canvas.getContext('2d');
            if (!ctx) return null;
            ctx.scale(pixelRatio, pixelRatio);

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, width, height);
            drawText(ctx, heading.title, 28, 38, { font: "700 23px 'Inter', Arial, sans-serif", color: '#343a40' });
            drawText(ctx, heading.subtitle, 28, 73, { font: "400 19px 'Inter', Arial, sans-serif", color: '#7b858e' });

            ctx.fillStyle = '#f8f9fa';
            ctx.fillRect(0, titleHeight, width, headerHeight);
            let x = 0;
            headers.forEach((header, index) => {
                drawText(ctx, header, index === 0 ? 18 : x + columns[index] / 2, titleHeight + headerHeight / 2, {
                    font: "700 17px 'Inter', Arial, sans-serif",
                    color: '#55554c',
                    align: index === 0 ? 'left' : 'center',
                    maxWidth: columns[index] - 20
                });
                x += columns[index];
            });

            rows.forEach((row, rowIndex) => {
                const y = titleHeight + headerHeight + (rowIndex * rowHeight);
                const firstText = row.cells[0]?.toUpperCase() || '';
                const isGrand = firstText.includes('GRAND TOTAL');
                const isCluster = firstText.includes('CLUSTER');
                ctx.fillStyle = isGrand ? '#e3f2fd' : (isCluster ? '#f8fafc' : '#ffffff');
                ctx.fillRect(0, y, width, rowHeight);
                ctx.strokeStyle = isGrand || isCluster ? '#d1d5db' : '#e9ecef';
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(width, y);
                ctx.stroke();

                let left = 0;
                row.cells.forEach((cell, index) => {
                    const isGrowth = /YTD|YoY|MoM/i.test(headers[index] || '');
                    const isDown = cell.includes('▼');
                    const isUp = cell.includes('▲');
                    const color = isGrowth
                        ? (isDown ? '#ff5b66' : (isUp ? '#32a852' : '#8a8f98'))
                        : (isGrand ? '#1657b7' : '#343a40');
                    if (index === 0 && isCluster) {
                        roundRect(ctx, 16, y + (rowHeight - 32) / 2, 88, 32, 16);
                        ctx.fillStyle = '#ffffff';
                        ctx.fill();
                        ctx.strokeStyle = '#d1d5db';
                        ctx.stroke();
                        drawText(ctx, 'CLUSTER', 60, y + rowHeight / 2, {
                            font: "600 12px 'Inter', Arial, sans-serif",
                            color: '#4b5563',
                            align: 'center'
                        });
                        drawText(ctx, cell.replace(/^CLUSTER\s*/i, ''), 118, y + rowHeight / 2, {
                            font: "700 16px 'Inter', Arial, sans-serif",
                            color: '#343a40',
                            maxWidth: columns[index] - 130
                        });
                        left += columns[index];
                        return;
                    }
                    drawText(ctx, cell, index === 0 ? 18 : left + columns[index] - 16, y + rowHeight / 2, {
                        font: (isGrand || isCluster || index === 0 || isGrowth || headers[index] === 'TOTAL')
                            ? "700 16px 'Inter', Arial, sans-serif"
                            : "400 16px 'Inter', Arial, sans-serif",
                        color,
                        align: index === 0 ? 'left' : 'right',
                        maxWidth: columns[index] - 24
                    });
                    left += columns[index];
                });
            });

            return canvas;
        }

        async function renderCurrentSalesCardToCanvas() {
            const payload = getMonthlySalesPayload();
            if (getCurrentSalesView() === 'chart') return renderActiveSalesChartToCanvas();
            if (payload.period === 'annual') return renderAnnualSalesTableToCanvas();
            return renderHeatmapToCanvas(heatmapSalesConfig);
        }

        function getCurrentSalesView() {
            const chartWrap = document.getElementById('monthly-sales-chart-wrap');
            return chartWrap && !chartWrap.classList.contains('d-none') ? 'chart' : 'table';
        }

        function buildExportFileName(filePrefix) {
            const ts = new Date();
            const yyyy = ts.getFullYear();
            const mm = String(ts.getMonth() + 1).padStart(2, '0');
            const dd = String(ts.getDate()).padStart(2, '0');
            return `${filePrefix}-${yyyy}${mm}${dd}.png`;
        }

        async function writePngBlobToClipboard(blob) {
            if (!navigator.clipboard || !window.ClipboardItem) return false;
            if (window.isSecureContext === false) return false;

            const clipboardType = blob.type || 'image/png';
            const payloads = [
                { [clipboardType]: blob },
                { 'image/png': blob },
                { 'image/png': Promise.resolve(blob) }
            ];

            for (const payload of payloads) {
                try {
                    if (window.ClipboardItem.supports && !window.ClipboardItem.supports('image/png')) {
                        return false;
                    }
                    await navigator.clipboard.write([new ClipboardItem(payload)]);
                    return true;
                } catch (error) {
                    console.warn('Clipboard image write attempt failed', error);
                }
            }

            return false;
        }

        async function shareOrDownloadPngBlob(blob, filePrefix, statusSetter) {
            const filename = buildExportFileName(filePrefix);
            const file = new File([blob], filename, { type: 'image/png' });

            if (navigator.canShare && navigator.share && navigator.canShare({ files: [file] })) {
                await navigator.share({ files: [file], title: filename });
                statusSetter('Gambar siap dibagikan.', false);
                return;
            }

            const link = document.createElement('a');
            link.download = filename;
            link.href = URL.createObjectURL(blob);
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(link.href), 30000);
            statusSetter('Clipboard tidak didukung browser ini. PNG otomatis didownload.', true);
        }

        async function copyCanvasExport(renderFn, statusId, filePrefix = 'heatmap') {
            try {
                setExportStatus(statusId, 'Membuat gambar...', false);
                const canvas = await renderFn();
                if (!canvas) throw new Error('capture-failed');
                const blob = await canvasToPngBlob(canvas);
                if (!blob) throw new Error('png-failed');

                if (await writePngBlobToClipboard(blob)) {
                    setExportStatus(statusId, 'Tersalin. Tinggal paste di chat.', false);
                    return;
                }

                await shareOrDownloadPngBlob(blob, filePrefix, (text, isError) => setExportStatus(statusId, text, isError));
            } catch (e) {
                console.error('Canvas copy/share failed', e);
                setExportStatus(statusId, 'Gagal copy gambar. Pakai tombol PNG.', true);
            }
        }

        async function downloadCanvasExport(renderFn, statusId, filePrefix) {
            try {
                setExportStatus(statusId, 'Membuat PNG...', false);
                const canvas = await renderFn();
                if (!canvas) throw new Error('capture-failed');
                const blob = await canvasToPngBlob(canvas);
                if (!blob) throw new Error('png-failed');
                const link = document.createElement('a');
                link.download = buildExportFileName(filePrefix);
                link.href = URL.createObjectURL(blob);
                document.body.appendChild(link);
                link.click();
                link.remove();
                setTimeout(() => URL.revokeObjectURL(link.href), 30000);
                setExportStatus(statusId, 'PNG siap didownload.', false);
            } catch (e) {
                console.error('Canvas download failed', e);
                setExportStatus(statusId, 'Gagal download PNG.', true);
            }
        }

        async function copyMomClusterAsImage() {
            try {
                setMomExportStatus('Membuat gambar...', false);
                const canvas = await renderMomClusterCardToCanvas();
                if (!canvas) throw new Error('capture-failed');

                const blob = await canvasToPngBlob(canvas);
                if (!blob) throw new Error('png-failed');

                if (await writePngBlobToClipboard(blob)) {
                    setMomExportStatus('Tersalin. Tinggal paste di chat.', false);
                    return;
                }

                await shareOrDownloadPngBlob(blob, 'rapor-mom', setMomExportStatus);
            } catch (e) {
                console.error('MoM copy/share failed', e);
                setMomExportStatus('Gagal copy gambar. Pakai tombol PNG.', true);
            }
        }

        async function downloadMomClusterAsPng() {
            try {
                setMomExportStatus('Membuat PNG...', false);
                const canvas = await renderMomClusterCardToCanvas();
                if (!canvas) throw new Error('capture-failed');

                const link = document.createElement('a');
                link.download = buildExportFileName('rapor-mom');
                const blob = await canvasToPngBlob(canvas);
                if (!blob) throw new Error('png-failed');
                link.href = URL.createObjectURL(blob);
                document.body.appendChild(link);
                link.click();
                link.remove();
                setTimeout(() => URL.revokeObjectURL(link.href), 30000);
                setMomExportStatus('PNG siap didownload.', false);
            } catch (e) {
                console.error('MoM download failed', e);
                setMomExportStatus('Gagal download PNG.', true);
            }
        }

        function toggleMomSfDetail(button) {
            const target = document.getElementById(button.dataset.target);
            if (!target) return;
            const willOpen = target.classList.contains('d-none');
            target.classList.toggle('d-none', !willOpen);
            button.innerText = willOpen ? '-' : '+';
            button.classList.toggle('bg-secondary', willOpen);
            button.classList.toggle('text-white', willOpen);
        }

        async function loadMomDateWithoutRefresh(input) {
            const form = input.form;
            const currentCard = document.getElementById('mom-cluster-card');
            if (!form || !currentCard) return;

            const expandedTargets = Array.from(currentCard.querySelectorAll('.toggle-sf'))
                .map(button => button.dataset.target)
                .filter(targetId => !document.getElementById(targetId)?.classList.contains('d-none'));
            const scrollPosition = { x: window.scrollX, y: window.scrollY };
            const requestUrl = new URL(form.action, window.location.href);
            requestUrl.search = new URLSearchParams(new FormData(form)).toString();

            input.disabled = true;
            currentCard.classList.add('opacity-75');

            try {
                const response = await fetch(requestUrl.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const html = await response.text();
                const nextDocument = new DOMParser().parseFromString(html, 'text/html');
                const nextCard = nextDocument.getElementById('mom-cluster-card');
                if (!nextCard) throw new Error('MoM card tidak ditemukan');

                currentCard.replaceWith(nextCard);
                expandedTargets.forEach(targetId => {
                    const target = document.getElementById(targetId);
                    const button = nextCard.querySelector(`.toggle-sf[data-target="${targetId}"]`);
                    if (!target || !button) return;
                    target.classList.remove('d-none');
                    button.innerText = '-';
                    button.classList.add('bg-secondary', 'text-white');
                });

                if (window.feather) feather.replace();
                window.history.replaceState({}, '', requestUrl.toString());
                requestAnimationFrame(() => window.scrollTo(scrollPosition.x, scrollPosition.y));
            } catch (error) {
                console.error('Gagal memperbarui MoM', error);
                currentCard.classList.remove('opacity-75');
                input.disabled = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Data MoM gagal diperbarui',
                    text: 'Silakan coba pilih tanggal kembali.'
                });
            }
        }

        document.addEventListener('change', function(event) {
            if (event.target.id === 'mom_date') loadMomDateWithoutRefresh(event.target);
        });

        document.addEventListener('click', function(event) {
            const sfButton = event.target.closest('.toggle-sf');
            if (sfButton) {
                toggleMomSfDetail(sfButton);
                return;
            }
            if (event.target.closest('#mom-copy-btn')) copyMomClusterAsImage();
            if (event.target.closest('#mom-download-btn')) downloadMomClusterAsPng();
        });

        // Tabel dan grafik memakai sumber data tahunan yang sama agar angkanya selalu konsisten.
        const monthlySalesLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        const monthlySalesColors = ['#4E79A7', '#F28E2B', '#E15759', '#76B7B2', '#59A14F', '#EDC948', '#B07AA1', '#FF5A8A', '#9C755F', '#17A2B8'];
        let monthlySalesChart = null;

        function getMonthlySalesPayload() {
            const dataElement = document.getElementById('monthly-sales-chart-data');
            if (!dataElement) return { monthly: {}, annual: {}, period: 'monthly', year: new Date().getFullYear(), view: 'table' };
            try {
                return JSON.parse(dataElement.textContent);
            } catch (error) {
                return { monthly: {}, annual: {}, period: 'monthly', year: new Date().getFullYear(), view: 'table' };
            }
        }

        function compactSalesQty(value) {
            const qty = Number(value || 0);
            if (qty >= 1000000) return `${Math.round(qty / 1000).toLocaleString('id-ID')}k`;
            if (qty >= 1000) return `${Math.round(qty / 1000).toLocaleString('id-ID')}k`;
            return qty.toLocaleString('id-ID');
        }

        const annualBarValuePlugin = {
            id: 'annualBarValuePlugin',
            afterDatasetsDraw(chart) {
                if (getMonthlySalesPayload().period !== 'annual') return;
                const ctx = chart.ctx;
                ctx.save();
                ctx.fillStyle = '#4b5563';
                ctx.font = '700 9px sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                chart.data.datasets.forEach((dataset, datasetIndex) => {
                    const meta = chart.getDatasetMeta(datasetIndex);
                    meta.data.forEach((bar, index) => {
                        const value = Number(dataset.data[index] || 0);
                        if (value > 0) ctx.fillText(compactSalesQty(value), bar.x, bar.y - 4);
                    });
                });
                ctx.restore();
            }
        };

        function buildMonthlySalesChart() {
            if (monthlySalesChart) return monthlySalesChart;

            const canvas = document.getElementById('monthly-sales-chart');
            if (!canvas || typeof Chart === 'undefined') return null;

            const payload = getMonthlySalesPayload();
            const monthlySalesMatrix = payload.monthly || {};
            const annualSalesMatrix = payload.annual || {};
            const monthlySalesPeriod = payload.period || 'monthly';
            const tapNames = Object.keys(monthlySalesMatrix);
            let labels;
            let datasets;
            let chartType = 'line';

            if (monthlySalesPeriod === 'annual') {
                labels = Array.from(new Set(
                    Object.values(annualSalesMatrix).flatMap(yearValues => Object.keys(yearValues || {}))
                ))
                    .map(Number)
                    .filter(year => year >= 2024 && year <= Number(payload.year))
                    .sort((a, b) => a - b)
                    .map(String);
                if (!labels.length) labels = [String(Math.max(2024, Number(payload.year)))];
                datasets = tapNames.map((tap, index) => ({
                    type: 'bar',
                    label: tap,
                    data: labels.map(year => Number(annualSalesMatrix[tap]?.[year] || 0)),
                    borderColor: monthlySalesColors[index % monthlySalesColors.length],
                    backgroundColor: `${monthlySalesColors[index % monthlySalesColors.length]}CC`,
                    borderWidth: 1,
                    borderRadius: 3,
                    order: 2
                }));
                chartType = 'bar';
            } else {
                const lastDataMonth = Math.max(1, ...tapNames.flatMap(tap =>
                    monthlySalesLabels.map((_, index) => Number(monthlySalesMatrix[tap]?.[index + 1] || 0) > 0 ? index + 1 : 0)
                ));
                labels = monthlySalesLabels.slice(0, lastDataMonth);
                datasets = tapNames.map((tap, index) => ({
                    label: tap,
                    data: labels.map((_, monthIndex) => Number(monthlySalesMatrix[tap]?.[monthIndex + 1] || 0)),
                    borderColor: monthlySalesColors[index % monthlySalesColors.length],
                    backgroundColor: monthlySalesColors[index % monthlySalesColors.length],
                    borderWidth: 2.25,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.28,
                    fill: false
                }));
            }

            monthlySalesChart = new Chart(canvas, {
                type: chartType,
                data: { labels, datasets },
                plugins: [annualBarValuePlugin],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, boxWidth: 8, padding: 18, font: { size: 11 } }
                        },
                        tooltip: {
                            callbacks: { label: context => ` ${context.dataset.label}: ${nf(context.parsed.y)}` }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { weight: '600' } } },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#eef1f5' },
                            ticks: { callback: value => nf(value) }
                        }
                    }
                }
            });

            return monthlySalesChart;
        }

        function setMonthlySalesView(view) {
            const showChart = view === 'chart';
            const tableWrap = document.getElementById('heatmap-sales-wrap');
            const chartWrap = document.getElementById('monthly-sales-chart-wrap');
            const tableButton = document.getElementById('monthly-sales-table-btn');
            const chartButton = document.getElementById('monthly-sales-chart-btn');
            const viewInput = document.getElementById('monthly-sales-view-input');
            const periodSelect = document.getElementById('monthly-sales-period');
            if (!tableWrap || !chartWrap || !tableButton || !chartButton) return;

            tableWrap.classList.toggle('d-none', showChart);
            chartWrap.classList.toggle('d-none', !showChart);
            chartWrap.setAttribute('aria-hidden', showChart ? 'false' : 'true');
            tableButton.classList.toggle('active', !showChart);
            chartButton.classList.toggle('active', showChart);
            tableButton.setAttribute('aria-pressed', showChart ? 'false' : 'true');
            chartButton.setAttribute('aria-pressed', showChart ? 'true' : 'false');
            if (viewInput) viewInput.value = showChart ? 'chart' : 'table';
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('sales_view', showChart ? 'chart' : 'table');
            window.history.replaceState({}, '', `${currentUrl.pathname}?${currentUrl.searchParams.toString()}${currentUrl.hash}`);

            if (showChart) {
                const chart = buildMonthlySalesChart();
                window.requestAnimationFrame(() => chart?.resize());
            }
        }

        async function reloadMonthlySalesCard(form) {
            const currentCard = document.getElementById('heatmap-sales-card');
            if (!form || !currentCard || currentCard.dataset.loading === 'true') return;

            const requestUrl = new URL(form.action, window.location.href);
            requestUrl.search = new URLSearchParams(new FormData(form)).toString();
            currentCard.dataset.loading = 'true';
            currentCard.style.opacity = '.62';
            currentCard.style.pointerEvents = 'none';

            try {
                const response = await fetch(requestUrl.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const html = await response.text();
                const nextDocument = new DOMParser().parseFromString(html, 'text/html');
                const nextCard = nextDocument.getElementById('heatmap-sales-card');
                if (!nextCard) throw new Error('Card Penjualan tidak ditemukan');

                monthlySalesChart?.destroy();
                monthlySalesChart = null;
                currentCard.replaceWith(nextCard);
                const payload = getMonthlySalesPayload();
                setMonthlySalesView(payload.view);
                if (window.feather?.replace) window.feather.replace();
                window.history.replaceState({}, '', `${requestUrl.pathname}?${requestUrl.searchParams.toString()}#heatmap-sales-card`);
            } catch (error) {
                currentCard.dataset.loading = 'false';
                currentCard.style.opacity = '';
                currentCard.style.pointerEvents = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Card penjualan gagal dimuat ulang',
                    text: 'Silakan coba kembali.'
                });
            }
        }

        document.addEventListener('submit', event => {
            if (event.target.id !== 'monthly-sales-filter-form') return;
            event.preventDefault();
            reloadMonthlySalesCard(event.target);
        });

        document.addEventListener('change', event => {
            if (!['monthly-sales-year', 'annual-sales-cutoff'].includes(event.target.id)) return;
            reloadMonthlySalesCard(event.target.form);
        });

        document.addEventListener('click', event => {
            const periodButton = event.target.closest('.sales-period-btn');
            if (periodButton) {
                const form = periodButton.closest('form');
                const periodInput = form?.querySelector('#monthly-sales-period');
                const viewInput = form?.querySelector('#monthly-sales-view-input');
                if (periodInput) periodInput.value = periodButton.dataset.period;
                if (viewInput) viewInput.value = 'chart';
                reloadMonthlySalesCard(form);
                return;
            }

            if (event.target.closest('#sales-card-reload-btn')) {
                reloadMonthlySalesCard(document.getElementById('monthly-sales-filter-form'));
                return;
            }
            if (event.target.closest('#monthly-sales-table-btn')) {
                setMonthlySalesView('table');
                return;
            }
            if (event.target.closest('#monthly-sales-chart-btn')) setMonthlySalesView('chart');
        });

        setMonthlySalesView(getMonthlySalesPayload().view);

        window.addEventListener('load', () => {
            if (getMonthlySalesPayload().view !== 'chart') return;
            window.requestAnimationFrame(() => {
                document.getElementById('heatmap-sales-card')?.scrollIntoView({ block: 'start' });
            });
        });

        const heatmapSalesConfig = {
            cardId: 'heatmap-sales-card',
            wrapId: 'heatmap-sales-wrap',
            title: '🗺️ Heatmap Penjualan Bulanan (Tahun {{ $salesYear }})',
            subtitle: 'Kerapatan transaksi per TAP. Warna makin gelap = performa makin tinggi.',
            accent: '#4285F4',
            grandBg: '#e3f2fd',
            grandColor: '#0d47a1'
        };

        const heatmapInjectConfig = {
            cardId: 'heatmap-inject-card',
            wrapId: 'heatmap-inject-wrap',
            title: '🪄 Heatmap Inject PV Bulanan (Tahun {{ $selectedYear }})',
            subtitle: 'Kerapatan transaksi Inject PV per TAP. Warna makin pekat = performa makin tinggi.',
            accent: '#198754',
            grandBg: '#e8f5e9',
            grandColor: '#1b5e20'
        };

        document.addEventListener('click', event => {
            if (event.target.closest('#heatmap-sales-copy-btn')) {
                const payload = getMonthlySalesPayload();
                const activeView = getCurrentSalesView();
                copyCanvasExport(
                    renderCurrentSalesCardToCanvas,
                    'heatmap-sales-status',
                    `penjualan-${payload.period}-${activeView}`
                );
            }
            if (event.target.closest('#heatmap-sales-download-btn')) {
                const payload = getMonthlySalesPayload();
                const activeView = getCurrentSalesView();
                downloadCanvasExport(
                    renderCurrentSalesCardToCanvas,
                    'heatmap-sales-status',
                    `penjualan-${payload.period}-${activeView}`
                );
            }
        });
        document.getElementById('heatmap-inject-copy-btn')?.addEventListener('click', () => {
            copyCanvasExport(() => renderHeatmapToCanvas(heatmapInjectConfig), 'heatmap-inject-status', 'heatmap-inject');
        });
        document.getElementById('heatmap-inject-download-btn')?.addEventListener('click', () => {
            downloadCanvasExport(() => renderHeatmapToCanvas(heatmapInjectConfig), 'heatmap-inject-status', 'heatmap-inject');
        });

        // Make sure feather icons inside the header buttons render.
        if (window.feather && typeof window.feather.replace === 'function') {
            window.feather.replace();
        }

        // ================= DOUGHNUT CHART SETUP =================
        const pieColors = ['#4285F4','#DB4437','#F4B400','#0F9D58','#AB47BC','#00ACC1','#FF7043','#9E9D24', '#5C6BC0'];

        const psLabels = {!! json_encode($pieSales->pluck('group_name')) !!};
        const psData = {!! json_encode($pieSales->pluck('total')) !!};
        const psTotal = psData.reduce((a, b) => Number(a) + Number(b), 0);
        const psLabelsPercent = psLabels.map((label, i) => label + ' (' + (psTotal > 0 ? ((psData[i] / psTotal) * 100).toFixed(1) : 0) + '%)');

        new Chart(document.getElementById('pieSales'), {
            type: 'doughnut',
            data: { 
                labels: psLabelsPercent, 
                datasets: [{ 
                    data: psData, 
                    backgroundColor: pieColors,
                    borderWidth: 2,
                    hoverOffset: 10
                }] 
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 15, padding: 15, font: { size: 11 } } },
                    tooltip: { callbacks: { label: c => ' ' + nf(c.parsed) + ' Pcs' } }
                },
                cutout: '70%'
            }
        });

        const piLabels = {!! json_encode($pieInject->pluck('group_name')) !!};
        const piData = {!! json_encode($pieInject->pluck('total')) !!};
        const piTotal = piData.reduce((a, b) => Number(a) + Number(b), 0);
        const piLabelsPercent = piLabels.map((label, i) => label + ' (' + (piTotal > 0 ? ((piData[i] / piTotal) * 100).toFixed(1) : 0) + '%)');

        new Chart(document.getElementById('pieInject'), {
            type: 'doughnut',
            data: { 
                labels: piLabelsPercent, 
                datasets: [{ 
                    data: piData, 
                    backgroundColor: pieColors,
                    borderWidth: 2,
                    hoverOffset: 10
                }] 
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 15, padding: 15, font: { size: 11 } } },
                    tooltip: { callbacks: { label: c => ' ' + nf(c.parsed) + ' Pcs' } }
                },
                cutout: '70%'
            }
        });

        // ================= SALES LINE ================= 
        new Chart(document.getElementById('chartSales'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartSales->pluck('label')) !!},
                datasets: [{
                    label: 'Total Sales',
                    data: {!! json_encode($chartSales->pluck('total')) !!},
                    borderColor: '#4285F4',
                    backgroundColor: 'rgba(66,133,244,0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#4285F4'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => ' ' + nf(c.parsed.y) } }
                },
                scales: {
                    y: { ticks: { callback: v => nf(v) }, grid:{ borderDash: [2, 4], color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // ================= INJECT BAR ================= 
        new Chart(document.getElementById('chartInject'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartInject->pluck('label')) !!},
                datasets: [{
                    label: 'Dumai Bengkalis',
                    data: {!! json_encode($chartInject->pluck('dumai')) !!},
                    backgroundColor: '#0F9D58',
                    borderRadius: 4
                }, {
                    label: 'Rokan Hilir',
                    data: {!! json_encode($chartInject->pluck('rohil')) !!},
                    backgroundColor: '#F4B400',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + nf(c.parsed.y) } }
                },
                scales: {
                    y: { ticks: { callback: v => nf(v) }, grid:{ display: false } },
                    x: { stacked: true, grid: { display: false } }
                }
            }
        });

        // ================= MoM DAILY LINE ================= 
        new Chart(document.getElementById('chartMomDaily'), {
            type: 'line',
            data: {
                labels: {!! json_encode($momDaily->pluck('day')) !!},
                datasets: [{
                    label: 'Bulan Ini',
                    data: {!! json_encode($momDaily->pluck('curr_qty')) !!},
                    borderColor: '#8E24AA',
                    backgroundColor: 'rgba(142,36,170,0.15)',
                    borderWidth: 3,
                    pointRadius: 3,
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Bulan Lalu',
                    data: {!! json_encode($momDaily->pluck('prev_qty')) !!},
                    borderColor: '#9E9E9E',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 2,
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { mode: 'index', intersect: false, callbacks: { label: c => ' ' + c.dataset.label + ': ' + nf(c.parsed.y) } }
                },
                scales: {
                    y: { ticks: { callback: v => nf(v) }, grid: { borderDash: [2, 4], color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    
        // ================= INTERACTIVE TOGGLES ================= 
        document.querySelectorAll('.toggle-validity').forEach(btn => {
            btn.addEventListener('click', function() {
                const target = document.getElementById(this.dataset.target);
                if (target.classList.contains('d-none')) {
                    target.classList.remove('d-none');
                    this.innerText = '-';
                    this.classList.add('bg-secondary', 'text-white');
                } else {
                    target.classList.add('d-none');
                    this.innerText = '+';
                    this.classList.remove('bg-secondary', 'text-white');
                }
            });
        });
    
        // Logic for toggling rincian sub-sections (SF vs Validity)
        $(document).on("click", ".btn-toggle-rincian", function() {
            var target = $(this).data("target");
            var parent = $(this).data("parent");
            var isInject = target.includes("inject");
            var btnClass = isInject ? "btn-success" : "btn-primary";
            var btnOutlineClass = isInject ? "btn-outline-success" : "btn-outline-primary";
            
            // Update button styles
            $("#" + parent + " .btn-toggle-rincian").removeClass(btnClass).addClass(btnOutlineClass);
            $(this).removeClass(btnOutlineClass).addClass(btnClass);
            
            // Show/hide content
            $("#" + parent + " .rincian-content").addClass("d-none");
            $("#" + target).removeClass("d-none");
        });
    </script>
@endpush
