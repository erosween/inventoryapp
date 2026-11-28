{{--
    Dashboard bersama Web Inventory dan SURYA. HomeController menyiapkan data, route aktif memilih
    layout.
--}}@extends(request()->routeIs('surya.inventory-dashboard') ? 'surya.layouts.inventory-dashboard' : 'layout.layout')

@section('content')
@php
    $isSuryaDashboard = request()->routeIs('surya.inventory-dashboard');
    $salesView = $isSuryaDashboard && !request()->has('sales_view') ? 'chart' : $salesView;
    $formatDashboardNumber = fn ($value, int $decimals) => number_format($value, $decimals, ',', '.');
    $momCurrentLabel = strtoupper($momSelectedDate->copy()->locale('id')->translatedFormat('d M'));
    $momPreviousLabel = strtoupper($momEndPrevMonthPartial->copy()->locale('id')->translatedFormat('d M'));
@endphp
<link rel="stylesheet" href="{{ asset('css/surya-validity-mom.css') }}?v={{ filemtime(public_path('css/surya-validity-mom.css')) }}">
<style>
    .distribution-controls { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin:0; }
    .distribution-controls label { margin:0 0 0 6px; font-size:11px; color:#667085; font-weight:700; }
    .distribution-product,.distribution-filter-select { height:40px; min-width:145px; padding:0 34px 0 12px; border:1px solid #dce4eb; border-radius:10px; color:#344054; background:#fff; font-family:inherit;font-size:12px;font-weight:650; cursor:pointer; transition:border-color .15s,box-shadow .15s; }
    .distribution-product { min-width:155px; }
    .distribution-filter-select:focus,.distribution-product:focus { outline:0; border-color:#c8102e; box-shadow:0 0 0 3px #c8102e18; }
    .distribution-status { font-size:12px; color:#667085; }
    .distribution-product[aria-busy="true"],.distribution-filter-select[aria-busy="true"] { opacity:.6; }
    @media(max-width:760px){.distribution-controls{width:100%;display:grid;grid-template-columns:auto minmax(0,1fr);gap:8px}.distribution-controls label{margin-left:0}.distribution-product,.distribution-filter-select{width:100%;min-width:0}}
    .card.sales-summary-card { height:auto!important; padding:20px 22px; border:1px solid #e8e2d9; border-radius:20px; background:#fffaf4; box-shadow:0 5px 20px rgba(40,36,28,.04); color:#292923; }
    .sales-summary-top { display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .sales-summary-label { font-size:12px; font-weight:750; letter-spacing:1.1px; text-transform:uppercase; color:#78766f; }
    .sales-summary-tag { flex-shrink:0; padding:5px 9px; border-radius:7px; background:#eee9df; color:#777164; font-size:10px; font-weight:750; letter-spacing:.6px; }
    .sales-summary-value { margin:12px 0 5px; font-size:clamp(28px,2.2vw,38px); font-weight:800; line-height:1.1; letter-spacing:-1.2px; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .sales-summary-value span { font-size:16px; font-weight:600; color:#8b857b; letter-spacing:0; }
    .sales-summary-cutoff { margin:8px 0 14px; color:#8b857b; font-size:12px; }
    .sales-summary-footer { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding-top:12px; border-top:1px solid #e8e2d9; }
    .sales-summary-growth { font-size:18px; font-weight:800; letter-spacing:-.3px; }
    .sales-summary-growth.is-up { color:#287757; }.sales-summary-growth.is-down { color:#c8102e; }.sales-summary-growth.is-flat { color:#78766f; }
    .sales-summary-insight { margin:8px 0 0; font-size:13px; font-weight:600; }
    .sales-summary-detail-link { display:inline-flex; align-items:center; gap:6px; margin-top:10px; color:#a30d27; font-size:12px; font-weight:750; text-decoration:none!important; }
    .sales-summary-detail-link:hover { color:#7f1025; text-decoration:underline!important; }
    #mom-cluster-card { scroll-margin-top:110px; }
    .surya-inventory-dashboard-page .kpi-card { height:auto!important; }
    .surya-inventory-dashboard-page .kpi-card .card-body { padding:18px 20px!important; }
    .surya-inventory-dashboard-page .kpi-card h2 { font-size:clamp(18px,1.35vw,23px)!important; white-space:nowrap; letter-spacing:-.5px; }
    .sales-summary-comparison { color:#78766f; font-size:12px; font-weight:500; }
    @media(max-width:640px) { .card.sales-summary-card { padding:22px; } }
    #mom-cluster-card { position:relative; }
    #mom-cluster-card .mom-loading { position:absolute; inset:0; z-index:120; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,.82); border-radius:inherit; backdrop-filter:blur(2px); }
    #mom-cluster-card .mom-loading span { display:flex; align-items:center; gap:12px; padding:14px 20px; background:white; border:1px solid #e5e7eb; border-radius:12px; color:#344054; font-size:13px; font-weight:600; box-shadow:0 8px 24px #17203312; }
    #mom-cluster-card .mom-loading i { width:20px; height:20px; border:2px solid #e5e7eb; border-top-color:#c8102e; border-radius:50%; animation:mom-spin .7s linear infinite; }
    @keyframes mom-spin { to { transform:rotate(360deg); } }
    @media(prefers-reduced-motion:reduce) { #mom-cluster-card .mom-loading i { animation:none; } }
    #mom-cluster-card .mom-card-heading { display:grid; grid-template-columns:minmax(260px,1fr) auto; align-items:center; gap:14px 24px; }
    #mom-cluster-card .mom-card-title { min-width:0; }
    #mom-cluster-card .mom-toolbar { display:flex; align-items:flex-end!important; justify-content:flex-end; gap:10px; flex-wrap:nowrap; min-width:0; }
    #mom-cluster-card form.mom-filter-form { display:flex!important; flex-direction:row!important; align-items:flex-end!important; gap:10px; margin:0; }
    #mom-cluster-card .mom-filter-field { display:flex; flex-direction:column; gap:6px; min-width:0; }
    #mom-cluster-card .mom-filter-field label { margin:0; color:#667085; font-size:11px; font-weight:700; letter-spacing:.3px; }
    #mom-cluster-card .mom-filter-field .form-control { width:clamp(150px,14vw,184px)!important; height:42px!important; min-height:42px; margin:0!important; padding:9px 12px; border:1px solid #dce4eb; border-radius:10px!important; background-color:#fff; color:#344054; font-size:13px; font-weight:600; box-shadow:none; }
    #mom-cluster-card .mom-filter-field .form-control:focus { border-color:#c8102e; box-shadow:0 0 0 3px rgba(200,16,46,.08); outline:0; }
    #mom-cluster-card .mom-toolbar .export-action-btn { display:inline-flex!important; justify-content:center; align-items:center; height:42px; min-width:88px; padding:8px 14px!important; border-radius:10px!important; white-space:nowrap; }
    #mom-cluster-card #mom-copy-btn,
    #mom-cluster-card #mom-download-btn,
    #mom-cluster-card #mom-copy-btn:disabled,
    #mom-cluster-card #mom-download-btn:disabled {
        background: #f1f3f5 !important;
        border: 1px solid #d8e0e9 !important;
        color: #263449 !important;
        opacity: 1 !important;
    }
    #mom-cluster-card #mom-copy-btn:hover,
    #mom-cluster-card #mom-download-btn:hover {
        background: #e8edf3 !important;
        color: #172033 !important;
    }
    #mom-cluster-card .mom-export-status:empty { display:none; }
    #mom-cluster-card .mom-export-status { grid-column:1/-1; margin:0; text-align:right; }
    @media(max-width:900px) {
        #mom-cluster-card .mom-card-heading { grid-template-columns:minmax(0,1fr); align-items:start; }
        #mom-cluster-card .mom-toolbar { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); width:100%; }
        #mom-cluster-card form.mom-filter-form { display:contents!important; }
        #mom-cluster-card .mom-filter-field .form-control { width:100%!important; }
        #mom-cluster-card .mom-toolbar .export-action-btn { width:100%; }
        #mom-cluster-card .mom-export-status { text-align:left; }
    }
    .kpi-card { border: none; border-radius: 12px; transition: transform 0.2s; }
    .kpi-card:hover { transform: translateY(-3px); }
    .enterprise-shadow { box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-radius: 10px; border:1px solid #f0f0f0; }
    .heatmap-cell { transition: all 0.2s; }
    .table-scroll-heatmap > table > tbody > tr:not(.validity-row) > td.heatmap-cell:hover { opacity: 0.8; font-weight: bold; cursor: pointer; }
    /* Expanded detail tables use a fixed, high-contrast header with no hover effect. */
    .table-scroll-heatmap .validity-row .rincian-content > table thead th,
    .table-scroll-heatmap .validity-row > td > div > table thead th {
        background: #263449 !important;
        color: #fff !important;
        opacity: 1 !important;
        cursor: default !important;
        transition: none !important;
    }
    .table-scroll-heatmap .validity-row .rincian-content > table thead th.sticky-validity-col,
    .table-scroll-heatmap .validity-row > td > div > table thead th.sticky-validity-col {
        background: #f8fafc !important;
        color: #263449 !important;
    }
    .table-scroll-heatmap .validity-row .rincian-content > table thead th:hover,
    .table-scroll-heatmap .validity-row > td > div > table thead th:hover {
        background: #263449 !important;
        color: #fff !important;
        opacity: 1 !important;
        font-weight: inherit !important;
    }
    .table-scroll-heatmap .validity-row .rincian-content > table thead th.sticky-validity-col:hover,
    .table-scroll-heatmap .validity-row > td > div > table thead th.sticky-validity-col:hover {
        background: #f8fafc !important;
        color: #263449 !important;
    }
    .table-scroll-heatmap .rincian-tabs .btn-toggle-rincian.btn-primary {
        background: #c8102e !important;
        border-color: #c8102e !important;
        color: #fff !important;
        opacity: 1 !important;
        font-weight: 700;
    }
    .table-scroll-heatmap .rincian-tabs .btn-toggle-rincian.btn-outline-primary {
        background: #fff !important;
        border-color: #c8102e !important;
        color: #a30d27 !important;
        opacity: 1 !important;
        font-weight: 700;
    }
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

    /* Pin the report header. Expanded details scroll with their summary rows. */
    .table-scroll-heatmap > table > thead > tr > th {
        position: sticky;
        top: 0;
        z-index: 100 !important;
        background: #f8fafc !important;
        border-bottom: 2px solid #ddd !important;
    }

    .table-scroll-heatmap > table > tfoot > tr:not(.validity-row) > td {
        background: #f8fafc !important;
        border-top: 2px solid #ddd !important;
    }

    .table-scroll-heatmap > .annual-sales-table > tfoot > tr > td {
        position: sticky;
        bottom: 0;
        z-index: 100 !important;
        box-shadow: 0 -4px 8px rgba(0,0,0,0.05);
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
    .table-scroll-heatmap > table > tfoot > tr > td.sticky-tap-col { background: #f8fafc !important; }
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
    .export-action-btn:disabled,
    .export-action-btn[disabled] {
        color: #111827 !important;
        opacity: 1 !important;
        cursor: not-allowed;
    }
    .export-action-btn:disabled i,
    .export-action-btn[disabled] i { opacity: 1; }
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
        color: #111827 !important;
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
    .monthly-product-select {
        width: 126px !important;
        min-width: 126px !important;
        flex: 0 0 126px;
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
        min-width: 1030px;
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
    #mom-cluster-card .card-header h6 { font-size: 16px; letter-spacing: -.01em; }
    #mom-cluster-card .card-header small { color: #7b8497 !important; font-weight: 600; }
    .mom-insights { display:flex; flex-wrap:wrap; gap:8px 20px; padding:12px 16px; background:#f7f9fc; border-top:1px solid #e5ebf2; font-size:13px; }
    #mom-cluster-card .mom-cluster-tabs { display:inline-flex; flex-wrap:wrap; align-items:center; gap:4px; margin:12px 20px 14px; padding:4px; border:1px solid #e5e9ef; border-radius:12px; background:#f4f6f9; }
    #mom-cluster-card .mom-cluster-tabs [role="tab"] { display:inline-flex; align-items:center; justify-content:center; min-height:36px; border:1px solid transparent; border-radius:8px; padding:0 15px; background:transparent; color:#5d6879; font:inherit; font-size:12px; font-weight:700; line-height:1; white-space:nowrap; cursor:pointer; transition:background-color .15s,border-color .15s,color .15s,box-shadow .15s; }
    #mom-cluster-card .mom-cluster-tabs [role="tab"][aria-selected="true"] { border-color:#b81232; background:#b81232; color:#fff; box-shadow:0 2px 5px rgba(184,18,50,.18); }
    #mom-cluster-card .mom-cluster-tabs [role="tab"]:focus-visible { outline:2px solid #c8102e; outline-offset:2px; }
    #mom-cluster-table-wrap > table > tbody > tr:not(.sf-row) > td:not(.text-success):not(.text-danger),
    #mom-cluster-table-wrap > table > tfoot > tr > td:not(.text-success):not(.text-danger) { color: #171717 !important; }
    #mom-cluster-table-wrap .sf-row .table thead th { background:#263449!important; color:#fff!important; border-color:#42516a!important; }
    #mom-cluster-table-wrap > table > thead > tr > th {
        background: #263449 !important;
        color: #fff !important;
        border-color: #42516a !important;
        opacity: 1 !important;
    }
    #mom-cluster-card .mom-cluster-tabs [role="tab"]:hover {
        border-color: #f0bdc8;
        color: #a30d27;
        background: #fff;
    }
    #mom-cluster-card .mom-cluster-tabs [role="tab"][aria-selected="true"],
    #mom-cluster-card .mom-cluster-tabs [role="tab"][aria-selected="true"]:hover {
        border-color: #a30d27;
        background: #b81232;
        color: #fff;
    }
    #mom-cluster-table-wrap .mom-impact-up { background: #edf8f2; }
    #mom-cluster-table-wrap .mom-impact-down { background: #fff0f2; }
    #mom-cluster-table-wrap :is(.metric-daily-st, .metric-stock, .metric-stock-days) { font-weight: 800; }
    #mom-cluster-card tfoot tr:last-child td { border-top: 2px solid #bfdbfe !important; }
    #mom-cluster-table-wrap tbody td,
    #mom-cluster-table-wrap tfoot td {
        font-size: 13px;
        border-top: 1px solid #edf0f2 !important;
    }
    #mom-cluster-table-wrap .mom-location-col { width: 20%; }
    #mom-cluster-table-wrap .mom-number-col { width: 10%; }
    #mom-cluster-table-wrap .toggle-sf[aria-expanded="true"] {
        background: #475467 !important;
        color: #fff !important;
    }
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
            </div>

            {{-- ================= KPI ================= --}}
            <div class="row" id="dashboard-kpi-row">
        @foreach ([['PV', $stokSegel, $stokInject], ['SA', $stokSaSegel, $stokSaInject]] as [$stockFamily, $sealedStock, $injectedStock])
                    <div class="col-md-6 col-xl-6 mb-3">
                        <article class="card kpi-card">
                            <div class="card-body py-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <strong class="text-dark">STOK {{ $stockFamily }}</strong>
                                    <span class="text-muted small">Gudang + SF · {{ $momSelectedDate->copy()->locale('id')->translatedFormat('d M Y') }}</span>
                                </div>
                                <div class="row">
                                    <div class="col-6" style="border-right:1px solid #e8edf2">
                                        <small class="text-muted">{{ $stockFamily }} Segel</small>
                                        <h2 class="mt-2 mb-0 font-weight-bold text-dark">{{ $formatDashboardNumber($sealedStock, 0) }}</h2>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">{{ $stockFamily }} Inject</small>
                                        <h2 class="mt-2 mb-0 font-weight-bold text-dark">{{ $formatDashboardNumber($injectedStock, 0) }}</h2>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
            <div class="row" id="dashboard-sales-kpi-row">
                @php
                    $pvClusterChanges = collect(['dumai_bengkalis' => 'DUMAI BENGKALIS', 'rokan_hilir' => 'ROKAN HILIR'])
                        ->filter(fn ($label, $key) => $momClusterSalesRows[$key]->curr_qty || $momClusterSalesRows[$key]->prev_partial_qty)
                        ->mapWithKeys(fn ($label, $key) => [$label => $momClusterSalesRows[$key]->curr_qty - $momClusterSalesRows[$key]->prev_partial_qty])
                        ->sortDesc();
                @endphp
                @foreach ([['PV', $salesBulanIniPv, $momPv, $momPvPrevious], ['SA', $salesBulanIniSa, $momSa, $momSaPrevious]] as [$product, $quantity, $growth, $previous])
                @php
                    $change = $quantity - $previous;
                    $clusterInsight = $product === 'PV' && $pvClusterChanges->count() > 1
                        ? ($change > 0 ? ', kenaikan terbesar di '.$pvClusterChanges->keys()->first() : ', penurunan terbesar di '.$pvClusterChanges->keys()->last())
                        : '';
                @endphp
                <div class="col-md-6 col-xl-6 mb-3">
                    <article class="card sales-summary-card" aria-label="Penjualan {{ $product }} bulan berjalan">
                        <div class="sales-summary-top">
                            <span class="sales-summary-label">Penjualan {{ $product }} · {{ $momSelectedDate->copy()->locale('id')->translatedFormat('F Y') }}</span>
                            <span class="sales-summary-tag">MTD</span>
                        </div>
                        <div class="sales-summary-value">{{ $formatDashboardNumber($quantity, 0) }} <span>pcs</span></div>
                        <p class="sales-summary-cutoff">Cutoff {{ $momSelectedDate->copy()->locale('id')->translatedFormat('d F Y') }}</p>
                        <div class="sales-summary-footer">
                            @if (!$isSuryaDashboard || $previous > 0)
                            <span class="sales-summary-growth {{ $growth < 0 ? 'is-down' : ($growth > 0 ? 'is-up' : 'is-flat') }}">
                                {{ $growth < 0 ? '▼' : ($growth > 0 ? '▲' : '—') }} {{ $formatDashboardNumber(abs($growth), 2) }}%
                            </span>
                            @endif
                            <span class="sales-summary-comparison">@if($isSuryaDashboard)1–{{ $momSelectedDate->copy()->locale('id')->translatedFormat('d M Y') }} vs @else MoM vs @endif 1–{{ $momEndPrevMonthPartial->copy()->locale('id')->translatedFormat('d M Y') }}</span>
                        </div>
                        @if ($isSuryaDashboard)
                            <p class="sales-summary-insight">
                                @if ($previous <= 0)
                                    Belum ada volume pembanding untuk menghitung MoM.
                                @elseif ($change === 0)
                                    Volume penjualan sama dengan periode pembanding.
                                @else
                                    {{ $change > 0 ? 'Naik' : 'Turun' }} {{ $formatDashboardNumber(abs($change), 0) }} pcs{{ $clusterInsight }}.
                                @endif
                            </p>
                            <a class="sales-summary-detail-link" href="#mom-cluster-card">Lihat detail MoM per TAP <span aria-hidden="true">→</span></a>
                        @endif
                    </article>
                </div>
                @endforeach
            </div>

            {{-- ================= SALES ANALYSIS ================= --}}
            <div class="card enterprise-shadow mb-4" id="surya-sales-analysis-card">
                <div class="card-header bg-white border-0 pt-4 pb-0 d-flex align-items-start justify-content-between flex-wrap" style="gap:12px">
                    <div>
                        <h6 class="font-weight-bold text-dark mb-0">Analisa Penjualan (Bulan Berjalan)</h6>
                        <small class="text-muted">Validasi dan 10 denom terlaris · Cutoff {{ $momSelectedDate->copy()->locale('id')->translatedFormat('d M Y') }}</small>
                    </div>
                    @include('surya.partials.distribution-filter', ['kind'=>'sales', 'selectedProduct'=>$distributionSalesProduct, 'clusters'=>$allowedDistributionClusters, 'taps'=>$distributionSalesTaps, 'selectedCluster'=>$distributionSalesCluster, 'selectedTap'=>$distributionSalesTap])
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6 mb-3 mb-lg-0">
                            <h6 class="font-weight-bold text-dark">Penjualan Per Validity</h6>
                            <div style="height:300px"><canvas id="pieSales"></canvas></div>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="font-weight-bold text-dark">10 Denom Terlaris</h6>
                            <div style="height:300px"><canvas id="topDenoms"></canvas></div>
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
                            <small class="text-muted">{{ $salesPeriod === 'annual' ? 'Perbandingan total penjualan setiap TAP per tahun · '.$annualCutoffLabel : 'Transaksi bulanan per TAP · Cutoff MTD '.$fairCutoffThisMonth->locale('id')->translatedFormat('d M Y') }}</small>
                        </div>
                        <div class="d-flex align-items-center export-actions">
                            <form method="GET" action="{{ request()->routeIs('surya.inventory-dashboard') ? route('surya.inventory-dashboard') : url('home') }}#heatmap-sales-card" class="monthly-sales-filter-form mb-0" id="monthly-sales-filter-form">
                                <input type="hidden" name="year" value="{{ $selectedYear }}">
                                <input type="hidden" name="mode" value="{{ $mode }}">
                                <input type="hidden" name="mom_date" value="{{ $momSelectedDate->toDateString() }}">
                                <input type="hidden" name="sales_view" id="monthly-sales-view-input" value="{{ $salesView }}">
                                <input type="hidden" name="sales_period" id="monthly-sales-period" value="{{ $salesPeriod }}">
                                <input type="hidden" name="inject_product" value="{{ $injectProductFilter }}">
                                <select name="sales_product" id="monthly-sales-product" class="form-control form-control-sm font-weight-bold monthly-sales-year-select monthly-product-select" aria-label="Kategori produk penjualan">
                                    <optgroup label="PV"><option value="pv_all" {{ $salesProductFilter === 'pv_all' ? 'selected' : '' }}>ALL PV</option><option value="pv_reg" {{ $salesProductFilter === 'pv_reg' ? 'selected' : '' }}>PV Reguler</option><option value="pv_byu" {{ $salesProductFilter === 'pv_byu' ? 'selected' : '' }}>PV By.U</option></optgroup>
                                    <optgroup label="SA"><option value="sa_all" {{ $salesProductFilter === 'sa_all' ? 'selected' : '' }}>ALL SA</option><option value="sa_simpati" {{ $salesProductFilter === 'sa_simpati' ? 'selected' : '' }}>SA Simpati</option><option value="sa_byu" {{ $salesProductFilter === 'sa_byu' ? 'selected' : '' }}>SA By.U</option></optgroup>
                                </select>
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
                            <button type="button" class="btn btn-sm export-action-btn d-inline-flex align-items-center justify-content-center" id="monthly-sales-download-excel-btn" title="Download tabel penjualan ke Excel" style="gap:6px;">
                                <i data-feather="download" style="width:14px;height:14px"></i> Excel
                            </button>
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
<style>
    @if($salesPeriod === 'monthly' && (int) $salesYear === (int) $currentDate->year)
    /* Keep historical MoM; current-month comparison lives in the MoM card. */
    #heatmap-sales-wrap > table > thead > tr > :nth-child({{ 2 * $currentDate->month + 1 }}),
    #heatmap-sales-wrap > table > tbody > tr:not(.validity-row) > :nth-child({{ 2 * $currentDate->month + 1 }}),
    #heatmap-sales-wrap > table > tfoot > tr:not(.validity-row) > :nth-child({{ 2 * $currentDate->month + 1 }}),
    #heatmap-sales-wrap > table > thead > tr > :nth-child(n+{{ 2 * $currentDate->month + 2 }}),
    #heatmap-sales-wrap > table > tbody > tr:not(.validity-row) > :nth-child(n+{{ 2 * $currentDate->month + 2 }}),
    #heatmap-sales-wrap > table > tfoot > tr:not(.validity-row) > :nth-child(n+{{ 2 * $currentDate->month + 2 }}) {
        display: none !important;
    }
    @endif
</style>
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
                                $annualYtdYears = [$annualCurrentYear - 2, $annualPreviousYear, $annualCurrentYear];
                                $annualClusterDumaiCurrentYtd = array_fill_keys($annualYtdYears, 0);
                                $annualClusterRohilCurrentYtd = array_fill_keys($annualYtdYears, 0);
                                $annualGrandCurrentYtd = array_fill_keys($annualYtdYears, 0);
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
                                    foreach ($annualYtdYears as $year) {
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
                                            $tapPreviousYtd = $calculateAnnualYtd($annualCurrentYtdSales[$tap] ?? [], $annualPreviousYear);
                                            $tapCurrentYtd = $calculateAnnualYtd($annualCurrentYtdSales[$tap] ?? [], $annualCurrentYear);
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
                                    @if(in_array(session('idtap'), ['SBP_DUMAI', 'ALL TAP', 'CLUSTER_DUMAI']))
                                    @php
                                        $clusterDumaiTotal = array_sum($annualClusterDumaiValues);
                                        $clusterDumaiPreviousYtd = $calculateAnnualYtd($annualClusterDumaiCurrentYtd, $annualPreviousYear);
                                        $clusterDumaiCurrentYtd = $calculateAnnualYtd($annualClusterDumaiCurrentYtd, $annualCurrentYear);
                                        $clusterDumaiYoy = $calculatePeriodGrowth($annualClusterDumaiComparison['current_month'], $annualClusterDumaiComparison['previous_year_month']);
                                        $clusterDumaiMom = $annualComparisonMonth > 1 ? $calculatePeriodGrowth($annualClusterDumaiComparison['current_month'], $annualClusterDumaiComparison['previous_month']) : null;
                                    @endphp
                                    <tr style="background:#f8fafc;color:#374151;border-top:2px solid #d1d5db">
                                        <td class="text-left sticky-tap-col" style="background:#f8fafc !important"><span class="badge badge-light border mr-2">CLUSTER</span>DUMAI BENGKALIS</td>
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
                                    @if(in_array(session('idtap'), ['SBP_DUMAI', 'ALL TAP', 'CLUSTER_ROHIL']))
                                    @php
                                        $clusterRohilTotal = array_sum($annualClusterRohilValues);
                                        $clusterRohilPreviousYtd = $calculateAnnualYtd($annualClusterRohilCurrentYtd, $annualPreviousYear);
                                        $clusterRohilCurrentYtd = $calculateAnnualYtd($annualClusterRohilCurrentYtd, $annualCurrentYear);
                                        $clusterRohilYoy = $calculatePeriodGrowth($annualClusterRohilComparison['current_month'], $annualClusterRohilComparison['previous_year_month']);
                                        $clusterRohilMom = $annualComparisonMonth > 1 ? $calculatePeriodGrowth($annualClusterRohilComparison['current_month'], $annualClusterRohilComparison['previous_month']) : null;
                                    @endphp
                                    <tr style="background:#f8fafc;color:#374151">
                                        <td class="text-left sticky-tap-col" style="background:#f8fafc !important"><span class="badge badge-light border mr-2">CLUSTER</span>ROKAN HILIR</td>
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
                                        $annualGrandPreviousYtd = $calculateAnnualYtd($annualGrandCurrentYtd, $annualPreviousYear);
                                        $annualGrandCurrentYtdGrowth = $calculateAnnualYtd($annualGrandCurrentYtd, $annualCurrentYear);
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
                        @php
                            $previousMtdMonthNumber = $previousMtdMonth->month;
                            $showPreviousMtdComparison = $salesYear === $momSelectedDate->year && $previousMtdMonthNumber < $momSelectedDate->month;
                            $previousMtdMonthLabel = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'][$previousMtdMonthNumber - 1];
                        @endphp
                        <table class="table table-bordered text-center mb-0" style="font-size:13px; border-bottom:0">
                            <thead class="bg-light">
                                <tr class="text-secondary">
                                    <th class="text-left font-weight-bold border-0 text-secondary sticky-tap-col">NAMA TAP</th>
                                    @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $monthIndex => $m)
                                        <th class="border-light">{{ $m }}</th>
                                        <th class="border-light text-muted" style="font-size: 10px;">{{ $showPreviousMtdComparison && $monthIndex + 1 === $previousMtdMonthNumber ? $previousMtdMonthLabel.'-MoM Qty' : 'MoM %' }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matrixSales as $tap => $months)
                                    <tr>
                                        <td class="text-left font-weight-bold bg-white text-dark sticky-tap-col">
                                            <button class="btn btn-sm btn-light border toggle-validity rounded py-0 px-2 mr-2"
                                                type="button" aria-expanded="false" aria-controls="val-sales-{{ Str::slug($tap) }}" aria-label="Tampilkan rincian">+</button>
                                            {{ $tap }}
                                        </td>
                                        @for($i=1; $i<=12; $i++)
                                            @php 
                                                $val = $months[$i] ?? 0; 
                                                $intensity = $val > 0 ? min($val / 200000, 1) : 0;
                                                $opacity = $val > 0 ? 0.12 + (0.2 * $intensity) : 0;
                                                $bg = $val > 0 ? "rgba(29, 78, 216, $opacity)" : "transparent";
                                                $color = '#172033';

                                                // MoM Logic Sales
                                                $isCurrentMonth = ($salesYear == $currentDate->year && $i == $currentDate->month);
                                                $prevVal = ($i == 1) ? ($prevDecSales[$tap] ?? 0) : ($months[$i-1] ?? 0);
                                                
                                                $isPreviousMtdMonth = $showPreviousMtdComparison && $i === $previousMtdMonthNumber;
                                                if($isCurrentMonth) {
                                                    $momData = $momTapSales->firstWhere('idtap', $tap);
                                                    $pct = $momData ? $momData->mom : 0;
                                                    $hasPrev = $momData && $momData->prev_partial_qty > 0;
                                                } elseif ($isPreviousMtdMonth) {
                                                    $comparison = $previousMtdComparisonByTap[$tap] ?? ['current' => 0, 'previous' => 0];
                                                    $momQty = $comparison['current'];
                                                    $hasPrev = $comparison['current'] > 0 || $comparison['previous'] > 0;
                                                    $momColor = 'text-dark';
                                                    $icon = '';
                                                } else {
                                                    $pct = $prevVal > 0 ? (($val - $prevVal) / $prevVal) * 100 : 0;
                                                    $hasPrev = $prevVal > 0;
                                                }
                                                if ($isPreviousMtdMonth) {
                                                    $momColor = 'text-dark';
                                                    $icon = '';
                                                } else {
                                                    $momColor = $pct > 0 ? 'text-success' : ($pct < 0 ? 'text-danger' : 'text-muted');
                                                    $icon = $pct > 0 ? '↑' : ($pct < 0 ? '↓' : '');
                                                }
                                            @endphp
                                            <td class="heatmap-cell border-light" style="background-color: {{ $bg }}; color: {{ $color }};">
                                                {{ $val > 0 ? number_format($val) : '-' }}
                                            </td>
                                            <td class="border-light {{ $momColor }} font-weight-bold" style="font-size: 10px; vertical-align: middle; background: #fafafa">
                                                @if($isPreviousMtdMonth)
                                                    {{ $hasPrev ? number_format($momQty) : '-' }}
                                                @elseif($hasPrev)
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
                                                <div class="rincian-tabs" role="group" aria-label="Pilih rincian penjualan">
                                                    <button class="btn btn-xs btn-primary mr-1 btn-toggle-rincian" data-target="sf-sales-{{ Str::slug($tap) }}" data-parent="val-sales-{{ Str::slug($tap) }}">Petugas</button>
                                                    <button class="btn btn-xs btn-outline-primary btn-toggle-rincian" data-target="validity-sales-{{ Str::slug($tap) }}" data-parent="val-sales-{{ Str::slug($tap) }}">{{ $salesDetailHeading }}</button>
                                                </div>

                                                {{-- SUB-SECTION: PERFORMANCE SELURUH PETUGAS --}}
                                                <div id="sf-sales-{{ Str::slug($tap) }}" class="rincian-content">
                                                    <table class="table table-sm table-bordered mb-0 bg-white shadow-sm rounded text-center" style="font-size: 11px;">
                                                        <thead class="bg-white">
                                                            <tr class="text-primary">
                                                                <th class="text-left border-0 sticky-validity-col" style="background: #f8fafc !important;">Nama Petugas</th>
                                                                @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $monthIndex => $m)
                                                                    <th class="border-0">{{ $m }}</th>
                                                                    @if($showPreviousMtdComparison && $monthIndex + 1 === $previousMtdMonthNumber)
                                                                        <th class="border-0 text-muted">{{ $previousMtdMonthLabel }}-MoM Qty</th>
                                                                    @endif
                                                                @endforeach
                                                                <th class="border-0 bg-light">TOTAL</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($sfByTap[$tap] ?? [] as $sf)
                                                                @php $sfTotal = 0; @endphp
                                                                <tr>
                                                                    <td class="text-left font-weight-bold sticky-validity-col">{{ $sf->namasf }}</td>
                                                                    @php
                                                                        $sfIds = $sf->idsfs ?? [$sf->idsf];
                                                                        $sfMtdCurrent = 0;
                                                                        $sfMtdPrevious = 0;
                                                                    @endphp
                                                                    @for($m=1; $m<=12; $m++)
                                                                        @php
                                                                            $mv = collect($sfIds)->sum(fn ($idsf) => $matrixSalesSf[$tap][$idsf][$m] ?? 0);
                                                                            $sfTotal += $mv;
                                                                            if ($showPreviousMtdComparison && $m === $previousMtdMonthNumber) {
                                                                                foreach ($sfIds as $idsf) {
                                                                                    $comparison = $previousMtdComparisonBySf[$tap][$idsf] ?? ['current' => 0, 'previous' => 0];
                                                                                    $sfMtdCurrent += $comparison['current'];
                                                                                    $sfMtdPrevious += $comparison['previous'];
                                                                                }
                                                                            }
                                                                        @endphp
                                                                        <td>{{ $mv > 0 ? number_format($mv) : '-' }}</td>
                                                                        @if($showPreviousMtdComparison && $m === $previousMtdMonthNumber)
                                                                            <td class="text-dark font-weight-bold">{{ $sfMtdCurrent > 0 ? number_format($sfMtdCurrent) : '-' }}</td>
                                                                        @endif
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
                                                                <th class="text-left border-0 sticky-validity-col" style="background: #f8fafc !important;">{{ $salesDetailHeading }}</th>
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
                                                            @foreach($salesDetailGroups as $vg)
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
                                @if(in_array(session('idtap'), ['SBP_DUMAI', 'ALL TAP', 'CLUSTER_DUMAI']))
                                <tr>
                                    <td class="text-left border-0 sticky-tap-col">
                                        <button class="btn btn-sm btn-light border toggle-validity rounded py-0 px-2 mr-2"
                                            type="button" aria-expanded="false" aria-controls="val-sales-cluster-dumai" aria-label="Tampilkan rincian">+</button>
                                        <span class="badge badge-light text-dark mr-1">CLUSTER</span> DUMAI BENGKALIS
                                    </td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $totalDumai = 0;
                                            $prevTotalDumai = 0;
                                            $mtdCurrentDumai = 0;
                                            $mtdPreviousDumai = 0;
                                            foreach($clusterDumai as $tap) { 
                                                $totalDumai += ($matrixSales[$tap][$i] ?? 0); 
                                                $prevTotalDumai += ($i == 1) ? ($prevDecSales[$tap] ?? 0) : ($matrixSales[$tap][$i-1] ?? 0);
                                                $mtdCurrentDumai += $previousMtdComparisonByTap[$tap]['current'] ?? 0;
                                                $mtdPreviousDumai += $previousMtdComparisonByTap[$tap]['previous'] ?? 0;
                                            }

                                            $isCurrentMonth = ($salesYear == $currentDate->year && $i == $currentDate->month);
                                            $isPreviousMtdMonth = $showPreviousMtdComparison && $i === $previousMtdMonthNumber;
                                            if($isCurrentMonth) {
                                                $pct = $momClusterSales['dumai_bengkalis'] ?? 0;
                                            } elseif ($isPreviousMtdMonth) {
                                                $momQty = $mtdCurrentDumai;
                                            } else {
                                                $pct = $prevTotalDumai > 0 ? (($totalDumai - $prevTotalDumai) / $prevTotalDumai) * 100 : 0;
                                            }
                                            if ($isPreviousMtdMonth) {
                                                $momColor = 'text-dark';
                                                $icon = '';
                                            } else {
                                                $momColor = $pct > 0 ? 'text-success' : ($pct < 0 ? 'text-danger' : 'text-muted');
                                                $icon = $pct > 0 ? '↑' : ($pct < 0 ? '↓' : '');
                                            }
                                        @endphp
                                        <td class="border-light opacity-75">{{ $totalDumai > 0 ? number_format($totalDumai) : '-' }}</td>
                                        <td class="border-light {{ $momColor }} font-weight-bold" style="font-size: 10px; vertical-align: middle; background: #fafafa">
                                            @if($isPreviousMtdMonth)
                                                {{ $mtdCurrentDumai > 0 || $mtdPreviousDumai > 0 ? number_format($momQty) : '-' }}
                                            @elseif($prevTotalDumai > 0 || ($isCurrentMonth && $pct != 0))
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
                                                        <th class="text-left border-0 sticky-validity-col">{{ $salesDetailHeading }} (Cluster Dumai)</th>
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
                                                    @foreach($salesDetailGroups as $vg)
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

                                @if(in_array(session('idtap'), ['SBP_DUMAI', 'ALL TAP', 'CLUSTER_ROHIL']))
                                {{-- FOOTER CLUSTER ROKAN HILIR --}}
                                <tr>
                                    <td class="text-left border-0 sticky-tap-col">
                                        <button class="btn btn-sm btn-light border toggle-validity rounded py-0 px-2 mr-2"
                                            type="button" aria-expanded="false" aria-controls="val-sales-cluster-rohil" aria-label="Tampilkan rincian">+</button>
                                        <span class="badge badge-light text-dark mr-1">CLUSTER</span> ROKAN HILIR
                                    </td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $totalRohil = 0;
                                            $prevTotalRohil = 0;
                                            $mtdCurrentRohil = 0;
                                            $mtdPreviousRohil = 0;
                                            foreach($clusterRohil as $tap) { 
                                                $totalRohil += ($matrixSales[$tap][$i] ?? 0); 
                                                $prevTotalRohil += ($i == 1) ? ($prevDecSales[$tap] ?? 0) : ($matrixSales[$tap][$i-1] ?? 0);
                                                $mtdCurrentRohil += $previousMtdComparisonByTap[$tap]['current'] ?? 0;
                                                $mtdPreviousRohil += $previousMtdComparisonByTap[$tap]['previous'] ?? 0;
                                            }

                                            $isCurrentMonth = ($salesYear == $currentDate->year && $i == $currentDate->month);
                                            $isPreviousMtdMonth = $showPreviousMtdComparison && $i === $previousMtdMonthNumber;
                                            if($isCurrentMonth) {
                                                $pct = $momClusterSales['rokan_hilir'] ?? 0;
                                            } elseif ($isPreviousMtdMonth) {
                                                $momQty = $mtdCurrentRohil;
                                            } else {
                                                $pct = $prevTotalRohil > 0 ? (($totalRohil - $prevTotalRohil) / $prevTotalRohil) * 100 : 0;
                                            }
                                            if ($isPreviousMtdMonth) {
                                                $momColor = 'text-dark';
                                                $icon = '';
                                            } else {
                                                $momColor = $pct > 0 ? 'text-success' : ($pct < 0 ? 'text-danger' : 'text-muted');
                                                $icon = $pct > 0 ? '↑' : ($pct < 0 ? '↓' : '');
                                            }
                                        @endphp
                                        <td class="border-light opacity-75">{{ $totalRohil > 0 ? number_format($totalRohil) : '-' }}</td>
                                        <td class="border-light {{ $momColor }} font-weight-bold" style="font-size: 10px; vertical-align: middle; background: #fafafa">
                                            @if($isPreviousMtdMonth)
                                                {{ $mtdCurrentRohil > 0 || $mtdPreviousRohil > 0 ? number_format($momQty) : '-' }}
                                            @elseif($prevTotalRohil > 0 || ($isCurrentMonth && $pct != 0))
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
                                                        <th class="text-left border-0 sticky-validity-col">{{ $salesDetailHeading }} (Cluster Rohil)</th>
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
                                                    @foreach($salesDetailGroups as $vg)
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

                                @if(in_array(session('idtap'), ['SBP_DUMAI', 'ALL TAP']))
                                {{-- GRAND TOTAL KESELURUHAN --}}
                                <tr style="background-color: #e3f2fd; color: #0d47a1;">
                                    <td class="text-left border-0 font-weight-bold sticky-tap-col">
                                        <button class="btn btn-sm btn-primary border toggle-validity rounded py-0 px-2 mr-2"
                                            type="button" aria-expanded="false" aria-controls="val-sales-grand-total" aria-label="Tampilkan rincian">+</button>
                                        ✨ GRAND TOTAL
                                    </td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $totalGT = 0;
                                            $prevTotalGT = 0;
                                            $mtdCurrentGT = 0;
                                            $mtdPreviousGT = 0;
                                            foreach($matrixSales as $tap => $months) { 
                                                $totalGT += ($months[$i] ?? 0); 
                                                $prevTotalGT += ($i == 1) ? ($prevDecSales[$tap] ?? 0) : ($months[$i-1] ?? 0);
                                                $mtdCurrentGT += $previousMtdComparisonByTap[$tap]['current'] ?? 0;
                                                $mtdPreviousGT += $previousMtdComparisonByTap[$tap]['previous'] ?? 0;
                                            }

                                            $isCurrentMonth = ($salesYear == $currentDate->year && $i == $currentDate->month);
                                            $isPreviousMtdMonth = $showPreviousMtdComparison && $i === $previousMtdMonthNumber;
                                            if($isCurrentMonth) {
                                                $pct = $mom; // Global Sales MoM
                                            } elseif ($isPreviousMtdMonth) {
                                                $momQty = $mtdCurrentGT;
                                            } else {
                                                $pct = $prevTotalGT > 0 ? (($totalGT - $prevTotalGT) / $prevTotalGT) * 100 : 0;
                                            }
                                            if ($isPreviousMtdMonth) {
                                                $momColor = 'text-dark';
                                                $icon = '';
                                            } else {
                                                $momColor = $pct > 0 ? 'text-success' : ($pct < 0 ? 'text-danger' : 'text-muted');
                                                $icon = $pct > 0 ? '↑' : ($pct < 0 ? '↓' : '');
                                            }
                                        @endphp
                                        <td class="border-light font-weight-bold">{{ $totalGT > 0 ? number_format($totalGT) : '-' }}</td>
                                        <td class="border-light {{ $momColor }} font-weight-bold" style="font-size: 10px; vertical-align: middle; background: rgba(0,0,0,0.05)">
                                            @if($isPreviousMtdMonth)
                                                {{ $mtdCurrentGT > 0 || $mtdPreviousGT > 0 ? number_format($momQty) : '-' }}
                                            @elseif($prevTotalGT > 0 || ($isCurrentMonth && $pct != 0))
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
                                                        <th class="text-left border-0 sticky-validity-col">{{ $salesDetailHeading }} (Grand Total)</th>
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
                                                    @foreach($salesDetailGroups as $vg)
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
                        @if($isSuryaDashboard)
                        <div class="surya-sales-chart-layout">
                            <section class="surya-sales-trend" aria-labelledby="surya-sales-trend-title">
                                <div class="surya-sales-trend-heading">
                                    <div><span class="surya-sales-eyebrow">TOTAL SELURUH WILAYAH</span><h3 id="surya-sales-trend-title">Penjualan bulanan <span>{{ $salesYear }}</span></h3><p>Perkembangan penjualan per bulan</p></div>
                                    <details class="surya-sales-compare"><summary>Bandingkan wilayah</summary><div id="surya-sales-compare-options" class="surya-sales-compare-options" role="group" aria-label="Pilih maksimal tiga wilayah"></div></details>
                                </div>
                                <div class="surya-sales-canvas-wrap"><canvas id="monthly-sales-chart"></canvas></div>
                                <p class="surya-sales-mtd-note" id="surya-sales-mtd-note" hidden></p>
                            </section>
                            <aside class="surya-sales-ranking" aria-labelledby="surya-sales-ranking-title">
                                <span class="surya-sales-eyebrow">KONTRIBUSI PENJUALAN</span><h3 id="surya-sales-ranking-title">Peringkat wilayah</h3>
                                <label class="surya-sales-ranking-month">Bulan <select id="surya-sales-ranking-month" aria-label="Bulan untuk peringkat"></select></label>
                                <ol id="surya-sales-ranking-list" class="surya-sales-ranking-list"></ol>
                                <div class="surya-sales-ranking-total"><span>Total bulan terpilih</span><strong id="surya-sales-ranking-total">—</strong></div>
                            </aside>
                        </div>
                        @else
                        <canvas id="monthly-sales-chart"></canvas>
                        @endif
                    </div>
                    @php
                        $monthlySalesChartPayload = [
                            'monthly' => $matrixSales,
                            'annual' => $annualSales,
                            'period' => $salesPeriod,
                            'year' => $salesYear,
                            'view' => $salesView,
                            'cutoff' => $annualCutoff,
                            'currentMonth' => $salesYear === (int) $momSelectedDate->year && $momSelectedDate->day < $momSelectedDate->daysInMonth ? (int) $momSelectedDate->month : null,
                            'cutoffDate' => $salesYear === (int) $momSelectedDate->year && $momSelectedDate->day < $momSelectedDate->daysInMonth ? $momSelectedDate->toDateString() : null,
                            'cutoffLabel' => $salesYear === (int) $momSelectedDate->year && $momSelectedDate->day < $momSelectedDate->daysInMonth ? $momSelectedDate->copy()->locale('id')->translatedFormat('d M Y') : null,
                            'suryaPresentation' => $isSuryaDashboard,
                        ];
                    @endphp
                    <script type="application/json" id="monthly-sales-chart-data">@json($monthlySalesChartPayload)</script>
                </div>
            </div>

            {{-- ================= YEARLY MATRIX INJECT (HEATMAP) ================= --}}
            <div class="card enterprise-shadow mb-4" id="heatmap-inject-card">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <div class="dashboard-period-header">
                        <div>
                            <h6 class="font-weight-bold text-dark mb-0">🪄Inject Bulanan (Tahun {{ $selectedYear }})</h6>
                            <small class="text-muted">Inject per TAP</small>
                        </div>
                        <div class="inject-period-toolbar">
                            <form method="GET" action="{{ request()->routeIs('surya.inventory-dashboard') ? route('surya.inventory-dashboard') : url('home') }}#heatmap-inject-card" class="dashboard-period-filters" id="monthly-inject-filter-form">
                                <label class="dashboard-period-field">Tahun
                                    <select name="year" id="monthly-inject-year" aria-label="Tahun inject">
                                        @for ($year = (int) date('Y'); $year >= 2023; $year--)
                                            <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
                                        @endfor
                                    </select>
                                </label>
                                <label class="dashboard-period-field">Produk
                                    <select name="inject_product" id="monthly-inject-product" aria-label="Kategori produk inject">
                                        <optgroup label="PV"><option value="pv_all" {{ $injectProductFilter === 'pv_all' ? 'selected' : '' }}>ALL PV</option><option value="pv_reg" {{ $injectProductFilter === 'pv_reg' ? 'selected' : '' }}>PV Reguler</option><option value="pv_byu" {{ $injectProductFilter === 'pv_byu' ? 'selected' : '' }}>PV By.U</option></optgroup>
                                        <optgroup label="SA"><option value="sa_all" {{ $injectProductFilter === 'sa_all' ? 'selected' : '' }}>ALL SA</option><option value="sa_simpati" {{ $injectProductFilter === 'sa_simpati' ? 'selected' : '' }}>SA Simpati</option><option value="sa_byu" {{ $injectProductFilter === 'sa_byu' ? 'selected' : '' }}>SA By.U</option></optgroup>
                                    </select>
                                </label>
                            </form>
                            <div class="inject-period-exports">
                                <button type="button" class="btn btn-sm export-action-btn d-inline-flex align-items-center justify-content-center" id="heatmap-inject-copy-btn" title="Copy heatmap inject sebagai gambar" style="gap:6px;">
                                    <i data-feather="copy" style="width:14px;height:14px"></i> Copy
                                </button>
                                <button type="button" class="btn btn-sm export-action-btn d-inline-flex align-items-center justify-content-center" id="heatmap-inject-download-btn" title="Download heatmap inject PNG" style="gap:6px;">
                                    <i data-feather="download" style="width:14px;height:14px"></i> PNG
                                </button>
                            </div>
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
                                                type="button" aria-expanded="false" aria-controls="val-inject-{{ Str::slug($tap) }}" aria-label="Tampilkan rincian">+</button>
                                            {{ $tap }}
                                        </td>
                                        @for($i=1; $i<=12; $i++)
                                            @php 
                                                $val = $months[$i] ?? 0; 
                                                $intensity = $val > 0 ? min($val / 20000, 1) : 0;
                                                $opacity = $val > 0 ? 0.12 + (0.2 * $intensity) : 0;
                                                $bg = $val > 0 ? "rgba(25, 135, 84, $opacity)" : "transparent"; 
                                                $color = '#172033';

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
                                                            <th class="text-left border-0 sticky-validity-col">{{ $injectDetailHeading }}</th>
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
                                                        @foreach($injectDetailGroups as $vg)
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
                                @if(in_array(session('idtap'), ['SBP_DUMAI', 'ALL TAP', 'CLUSTER_DUMAI']))
                                <tr>
                                    <td class="text-left border-0 sticky-tap-col">
                                        <button class="btn btn-sm btn-light border toggle-validity rounded py-0 px-2 mr-2"
                                            type="button" aria-expanded="false" aria-controls="val-inject-cluster-dumai" aria-label="Tampilkan rincian">+</button>
                                        <span class="badge badge-light text-dark mr-1">CLUSTER</span> DUMAI BENGKALIS
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
                                                        <th class="text-left border-0 sticky-validity-col">{{ $injectDetailHeading }} (Cluster Dumai)</th>
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
                                                    @foreach($injectDetailGroups as $vg)
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

                                @if(in_array(session('idtap'), ['SBP_DUMAI', 'ALL TAP', 'CLUSTER_ROHIL']))
                                {{-- FOOTER CLUSTER ROKAN HILIR --}}
                                <tr>
                                    <td class="text-left border-0 sticky-tap-col">
                                        <button class="btn btn-sm btn-light border toggle-validity rounded py-0 px-2 mr-2"
                                            type="button" aria-expanded="false" aria-controls="val-inject-cluster-rohil" aria-label="Tampilkan rincian">+</button>
                                        <span class="badge badge-light text-dark mr-1">CLUSTER</span> ROKAN HILIR
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
                                                        <th class="text-left border-0 sticky-validity-col">{{ $injectDetailHeading }} (Cluster Rohil)</th>
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
                                                    @foreach($injectDetailGroups as $vg)
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

                                @if(in_array(session('idtap'), ['SBP_DUMAI', 'ALL TAP']))
                                {{-- GRAND TOTAL KESELURUHAN --}}
                                <tr style="background-color: #e8f5e9; color: #1b5e20;">
                                    <td class="text-left border-0 font-weight-bold sticky-tap-col">
                                        <button class="btn btn-sm btn-success border toggle-validity rounded py-0 px-2 mr-2"
                                            type="button" aria-expanded="false" aria-controls="val-inject-grand-total" aria-label="Tampilkan rincian">+</button>
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
                                                        <th class="text-left border-0 sticky-validity-col">{{ $injectDetailHeading }} (Grand Total)</th>
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
                                                    @foreach($injectDetailGroups as $vg)
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
            <div class="row mb-4 align-items-stretch" id="dashboard-operations-row">
                <div class="col-12 mb-4">
                    @include('surya.partials.validity-mom')
                    <script src="{{ asset('js/surya-validity-mom.js') }}?v={{ filemtime(public_path('js/surya-validity-mom.js')) }}" defer></script>
                </div>

                <div class="col-12">
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
                                        <div class="d-flex justify-content-between"><strong class="text-dark">{{ $tap }}</strong>
                                            @if (is_null($latest)) <span class="badge badge-secondary badge-pill">No Data</span>
                                            @elseif ($diff <= 1) <span class="badge badge-success badge-pill bg-success text-white">Aktif ({{ $diff }} hr)</span>
                                            @elseif ($diff <= 3) <span class="badge badge-warning badge-pill text-dark">Warning ({{ $diff }} hr)</span>
                                            @else <span class="badge badge-danger badge-pill bg-danger text-white">Drop ({{ $diff }} hr)</span> @endif
                                        </div>
                                        <div class="small mt-1 text-muted"><i data-feather="download" style="width:12px;height:12px"></i> In: {{ $masuk ? \Carbon\Carbon::parse($masuk)->format('d/m H:i') : 'N/A' }} &nbsp; <i data-feather="upload" style="width:12px;height:12px"></i> Out: {{ $keluar ? \Carbon\Carbon::parse($keluar)->format('d/m H:i') : 'N/A' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ================= ACTIVITY & MoM DAILY ================= --}}
            {{-- ================= MoM PER TAP ================= --}}
            @include('surya.partials.mom-sales-card', $momCardData ?? [])

            {{-- close --}}
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const dashboardNumberLocale = @json($isSuryaDashboard ? 'en-US' : 'id-ID');
        const nf = v => new Intl.NumberFormat(dashboardNumberLocale).format(v);

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
                .replace(/^[+−]\s*/, '')
                .trim();
        }

        function getMomExportRows() {
            const table = document.querySelector('#mom-cluster-table-wrap > table');
            if (!table) return [];

            const rows = [];
            const mainBodyRows = Array.from(table.tBodies?.[0]?.rows || [])
                .filter(tr => !tr.classList.contains('sf-row') && !tr.hidden);
            const mainFootRows = Array.from(table.tFoot?.rows || []).filter(tr => !tr.hidden);

            [...mainBodyRows, ...mainFootRows].forEach(tr => {
                // Penting: pakai tr.cells dari row utama saja, jangan querySelectorAll,
                // supaya baris nested Performance Petugas/SF tidak ikut ke-export.
                const cells = Array.from(tr.cells || []);
                if (cells.length !== 9) return;
                const firstText = getMomCellText(cells[0]).replace(/^CLUSTER\s+/i, '');
                const growthText = getMomCellText(cells[5]);
                rows.push({
                    type: tr.parentElement?.tagName === 'TFOOT'
                        ? (firstText.toUpperCase().includes('GRAND TOTAL') ? 'grand' : 'cluster')
                        : 'tap',
                    cells: [firstText, ...cells.slice(1).map(getMomCellText)],
                    locationBackground: cells[0].classList.contains('mom-impact-up') || cells[0].classList.contains('mom-impact-down')
                        ? getComputedStyle(cells[0]).backgroundColor : null,
                    gapColor: cells[4].classList.contains('text-danger') ? '#e75757'
                        : (cells[4].classList.contains('text-success') ? '#32a852' : null),
                    growth1Down: growthText.includes('▼') || /^-/.test(growthText)
                });
            });
            return rows;
        }

        function getMomExportHeaders() {
            const table = document.querySelector('#mom-cluster-table-wrap > table');
            const headerCells = Array.from(table?.tHead?.rows?.[0]?.cells || []);

            if (headerCells.length !== 9) {
                return [
                    'LOKASI (TAP / SF)',
                    'MTD',
                    'M-1',
                    'M-1 FULL',
                    'GAP MOM QTY',
                    'GROWTH (M-1)', 'DAILY ST', 'STOCK GUDANG', 'STOCK DAYS'
                ];
            }

            return headerCells.map(cell => getMomCellText(cell));
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
                font = '600 22px DM Sans, sans-serif',
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
            ctx.font = options.font || '700 22px DM Sans, sans-serif';
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
            if (document.getElementById('mom-cluster-card')?.getAttribute('aria-busy') === 'true') {
                throw new Error('Tunggu data produk selesai dimuat sebelum ekspor.');
            }
            const rows = getMomExportRows();
            if (!rows.length) return null;
            const headers = getMomExportHeaders();
            const heading = getCardExportHeading(
                'mom-cluster-card',
                '📊 MoM Sales Tap',
                'Perbandingan MTD'
            );

            // Render dibuat mengikuti tampilan card asli, bukan versi export yang beda layout.
            const pixelRatio = 2;
            const cols = [350, 160, 180, 180, 160, 180, 150, 180, 160];
            const width = cols.reduce((total, col) => total + col, 0);
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

            const lefts = cols.reduce((acc, col, i) => {
                acc.push(i === 0 ? 0 : acc[i - 1] + cols[i - 1]);
                return acc;
            }, []);

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, width, height);

            // Header card seperti UI.
            drawText(ctx, heading.title, 28, 38, {
                font: '700 15px DM Sans, sans-serif',
                color: '#171717'
            });
            drawText(ctx, heading.subtitle, 28, 62, {
                font: '400 16px DM Sans, sans-serif',
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
                    font: '700 13px DM Sans, sans-serif',
                    color: '#111827',
                    align: 'center'
                });
            }
            const product = document.getElementById('mom-product');
            const selectedProduct = product?.selectedOptions?.[0]?.textContent?.trim() || 'PV All';
            const cutoff = document.getElementById('mom_date')?.value || '';
            drawPill(selectedProduct, width - 380, 28, 170);
            drawPill('Cutoff ' + cutoff, width - 200, 28, 184);

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
                    font: '700 16px DM Sans, sans-serif',
                    color: '#171717',
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
                if (row.locationBackground) {
                    ctx.fillStyle = row.locationBackground;
                    ctx.fillRect(0, y, cols[0], rowHeight);
                }

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
                        font: '400 14px DM Sans, sans-serif',
                        color: '#343a40',
                        align: 'center'
                    });
                    drawText(ctx, row.cells[0], 68, centerY, {
                        font: '700 16px DM Sans, sans-serif',
                        color: '#171717',
                        maxWidth: cols[0] - 78
                    });
                } else if (row.type === 'cluster') {
                    roundRect(ctx, 18, centerY - 15, 90, 30, 14);
                    ctx.fillStyle = '#ffffff';
                    ctx.fill();
                    ctx.strokeStyle = '#dfe4ea';
                    ctx.stroke();
                    drawText(ctx, 'CLUSTER', 63, centerY, {
                        font: '400 14px DM Sans, sans-serif',
                        color: '#171717',
                        align: 'center'
                    });
                    drawText(ctx, row.cells[0], 118, centerY, {
                        font: '700 16px DM Sans, sans-serif',
                        color: '#171717',
                        maxWidth: cols[0] - 126
                    });
                } else {
                    drawText(ctx, row.cells[0], 18, centerY, {
                        font: '700 16px DM Sans, sans-serif',
                        color: '#171717',
                        maxWidth: cols[0] - 36
                    });
                }

                for (let i = 1; i < row.cells.length; i++) {
                    let color = '#171717';
                    let font = '400 16px DM Sans, sans-serif';
                    if (i === 1 || isCluster || isGrand) {
                        font = '700 16px DM Sans, sans-serif';
                    }
                    if (i === 4 && row.gapColor) {
                        color = row.gapColor;
                        font = '700 16px DM Sans, sans-serif';
                    }
                    if (i === 5) {
                        color = row.growth1Down ? '#e75757' : '#32a852';
                        font = '700 16px DM Sans, sans-serif';
                    }
                    if (i >= 6 && !isGrand && !isCluster) {
                        font = '700 16px DM Sans, sans-serif';
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
                font: '700 24px DM Sans, sans-serif',
                color: '#343a40'
            });
            drawText(ctx, heading.subtitle, 38, 76, {
                font: '400 23px DM Sans, sans-serif',
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
                font: '700 21px DM Sans, sans-serif',
                color: '#55554c'
            });

            monthNames.forEach((month, index) => {
                const x = firstCol + (index * (valueCol + momCol));
                drawText(ctx, month, x + (valueCol / 2), titleHeight + 28, {
                    font: '700 18px DM Sans, sans-serif',
                    color: '#55554c',
                    align: 'center'
                });
                drawText(ctx, 'MoM', x + valueCol + (momCol / 2), titleHeight + 28, {
                    font: '700 15px DM Sans, sans-serif',
                    color: '#7b858e',
                    align: 'center'
                });
                drawText(ctx, '%', x + valueCol + (momCol / 2), titleHeight + 52, {
                    font: '700 13px DM Sans, sans-serif',
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
                        font: '400 18px DM Sans, sans-serif',
                        color: '#343a40',
                        align: 'center'
                    });
                    drawWrappedText(ctx, row.name, 78, centerY, firstCol - 96, 25, {
                        font: '700 21px DM Sans, sans-serif',
                        color: '#343a40',
                        maxLines: 2
                    });
                } else if (row.type === 'cluster') {
                    roundRect(ctx, 20, centerY - 19, 112, 38, 19);
                    ctx.fillStyle = config.accent;
                    ctx.fill();
                    drawText(ctx, 'CLUSTER', 76, centerY, {
                        font: '400 17px DM Sans, sans-serif',
                        color: '#ffffff',
                        align: 'center'
                    });
                    drawWrappedText(ctx, row.name, 148, centerY, firstCol - 166, 24, {
                        font: '700 20px DM Sans, sans-serif',
                        color: '#343a40',
                        maxLines: 2
                    });
                } else {
                    drawWrappedText(ctx, row.name, 22, centerY, firstCol - 42, 25, {
                        font: '700 21px DM Sans, sans-serif',
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
                        font: (isGrand || isCluster) ? '700 18px DM Sans, sans-serif' : '600 17px DM Sans, sans-serif',
                        color: valueColor,
                        align: 'right',
                        maxWidth: valueCol - 18
                    });

                    let momColor = '#9aa3aa';
                    if (month.momDown) momColor = '#ff5b66';
                    if (!month.momDown && month.momUp && month.mom !== '-') momColor = '#32a852';
                    drawText(ctx, month.mom, x + valueCol + momCol - 9, centerY, {
                        font: '700 15px DM Sans, sans-serif',
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
            const ranking = document.querySelector('.surya-sales-ranking');
            const rankingScale = ranking && source.getBoundingClientRect().width ? source.width / source.getBoundingClientRect().width : 1;
            const rankingWidth = ranking && !ranking.classList.contains('is-hidden') ? Math.round(300 * rankingScale) : 0;
            const titleHeight = 150;
            const canvas = document.createElement('canvas');
            canvas.width = source.width + rankingWidth;
            canvas.height = source.height + titleHeight;
            const ctx = canvas.getContext('2d');
            if (!ctx) return null;

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            drawText(ctx, heading.title, 42, 46, {
                font: `700 ${30 * rankingScale}px Manrope, sans-serif`,
                color: '#343a40'
            });
            drawText(ctx, heading.subtitle, 42, 91, {
                font: `400 ${24 * rankingScale}px DM Sans, sans-serif`,
                color: '#7b858e'
            });
            ctx.strokeStyle = '#e9ecef';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(0, titleHeight - 1);
            ctx.lineTo(canvas.width, titleHeight - 1);
            ctx.stroke();
            ctx.drawImage(source, 0, titleHeight);
            if (rankingWidth && ranking) {
                const left = source.width;
                const pad = 20 * rankingScale;
                ctx.fillStyle = '#edf0f4'; ctx.fillRect(left, titleHeight, 1, source.height);
                drawText(ctx, 'KONTRIBUSI PENJUALAN', left + pad, titleHeight + 24 * rankingScale, {font:`800 ${9 * rankingScale}px DM Sans, sans-serif`,color:'#98a2b3'});
                drawText(ctx, document.getElementById('surya-sales-ranking-title')?.textContent || 'Peringkat wilayah', left + pad, titleHeight + 52 * rankingScale, {font:`800 ${16 * rankingScale}px Manrope, sans-serif`,color:'#202b40'});
                const month = Number(document.getElementById('surya-sales-ranking-month')?.value || 1);
                const matrix = getMonthlySalesPayload().monthly || {};
                const rows = Object.entries(matrix).map(([name, values]) => ({name,value:Number(values?.[month]||0)})).sort((a,b)=>b.value-a.value||a.name.localeCompare(b.name)).filter(row=>row.value>0).slice(0,8);
                rows.forEach((row,index)=>{
                    const y = titleHeight + (88 + index * 34) * rankingScale;
                    drawText(ctx,String(index+1).padStart(2,'0'),left+pad,y,{font:`700 ${9*rankingScale}px DM Sans, sans-serif`,color:'#98a2b3'});
                    drawText(ctx,row.name,left+pad+24*rankingScale,y,{font:`500 ${10*rankingScale}px DM Sans, sans-serif`,color:'#475467',maxWidth:125*rankingScale});
                    drawText(ctx,nf(row.value),left+rankingWidth-pad,y,{font:`700 ${10*rankingScale}px Manrope, sans-serif`,color:'#344054',align:'right'});
                    ctx.fillStyle='#eef1f5';ctx.fillRect(left+pad+24*rankingScale,y+8*rankingScale,rankingWidth-pad*2-24*rankingScale,4*rankingScale);
                    ctx.fillStyle=index===0?'#c8102e':'#c7cfda';ctx.fillRect(left+pad+24*rankingScale,y+8*rankingScale,(rankingWidth-pad*2-24*rankingScale)*(row.value/rows[0].value),4*rankingScale);
                });
            }
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
            drawText(ctx, heading.title, 28, 38, { font: "700 23px 'DM Sans', sans-serif", color: '#343a40' });
            drawText(ctx, heading.subtitle, 28, 73, { font: "400 19px 'DM Sans', sans-serif", color: '#7b858e' });

            ctx.fillStyle = '#f8f9fa';
            ctx.fillRect(0, titleHeight, width, headerHeight);
            let x = 0;
            headers.forEach((header, index) => {
                drawText(ctx, header, index === 0 ? 18 : x + columns[index] / 2, titleHeight + headerHeight / 2, {
                    font: "700 17px 'DM Sans', sans-serif",
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
                            font: "600 12px 'DM Sans', sans-serif",
                            color: '#4b5563',
                            align: 'center'
                        });
                        drawText(ctx, cell.replace(/^CLUSTER\s*/i, ''), 118, y + rowHeight / 2, {
                            font: "700 16px 'DM Sans', sans-serif",
                            color: '#343a40',
                            maxWidth: columns[index] - 130
                        });
                        left += columns[index];
                        return;
                    }
                    drawText(ctx, cell, index === 0 ? 18 : left + columns[index] - 16, y + rowHeight / 2, {
                        font: (isGrand || isCluster || index === 0 || isGrowth || headers[index] === 'TOTAL')
                            ? "700 16px 'DM Sans', sans-serif"
                            : "400 16px 'DM Sans', sans-serif",
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

        function setMomSfDetail(button, expanded = button.getAttribute('aria-expanded') !== 'true') {
            const target = document.getElementById(button.getAttribute('aria-controls'));
            if (!target) return;

            target.classList.toggle('d-none', !expanded);
            button.textContent = expanded ? '−' : '+';
            button.setAttribute('aria-expanded', String(expanded));
            button.setAttribute('aria-label', expanded ? 'Tutup rincian' : 'Tampilkan rincian');
        }

        let activeMomCluster = document.querySelector('#mom-cluster-card [data-mom-cluster-tab][aria-selected="true"]')?.dataset.momClusterTab || 'all';
        function applyMomClusterFilter(cluster = activeMomCluster) {
            const card = document.getElementById('mom-cluster-card');
            if (!card) return;
            const tabs = [...card.querySelectorAll('[data-mom-cluster-tab]')];
            if (tabs.length && !tabs.some(tab => tab.dataset.momClusterTab === cluster)) {
                cluster = tabs.find(tab => tab.getAttribute('aria-selected') === 'true')?.dataset.momClusterTab || tabs[0].dataset.momClusterTab;
            }
            activeMomCluster = cluster;
            tabs.forEach(tab => tab.setAttribute('aria-selected', String(tab.dataset.momClusterTab === cluster)));
            card.querySelectorAll('#mom-cluster-table-wrap tr[data-mom-cluster]').forEach(row => {
                row.hidden = cluster !== 'all' && row.dataset.momCluster !== cluster;
            });
            const momBody = card.querySelector('#mom-cluster-table-wrap tbody');
            if (momBody) {
                const originalRows = [...momBody.rows];
                originalRows.forEach((row, index) => {
                    if (row.dataset.momOriginalOrder === undefined) row.dataset.momOriginalOrder = String(index);
                });
                if (cluster === 'all') {
                    const tapRows = originalRows.filter(row => row.querySelector('.mom-tap-name'));
                    const pairedRows = new Set();
                    const orderedPairs = tapRows
                        .sort((a, b) => Number(a.dataset.momAllOrder) - Number(b.dataset.momAllOrder))
                        .map(row => {
                            const detailId = row.querySelector('.toggle-sf')?.getAttribute('aria-controls');
                            const detailRow = detailId ? momBody.querySelector(`#${CSS.escape(detailId)}`) : null;
                            pairedRows.add(row);
                            if (detailRow) pairedRows.add(detailRow);
                            return detailRow ? [row, detailRow] : [row];
                        });
                    const trailingRows = originalRows.filter(row => !pairedRows.has(row));
                    [...orderedPairs.flat(), ...trailingRows].forEach(row => momBody.appendChild(row));
                } else {
                    originalRows
                        .sort((a, b) => Number(a.dataset.momOriginalOrder) - Number(b.dataset.momOriginalOrder))
                        .forEach(row => momBody.appendChild(row));
                }
            }
            card.querySelectorAll('#mom-cluster-table-wrap .mom-tap-name').forEach(cell => {
                const useGlobal = cluster === 'all';
                cell.classList.toggle('mom-impact-up', cell.classList.contains(useGlobal ? 'mom-impact-up-global' : 'mom-impact-up-cluster'));
                cell.classList.toggle('mom-impact-down', cell.classList.contains(useGlobal ? 'mom-impact-down-global' : 'mom-impact-down-cluster'));
            });
            const insightItems = [...card.querySelectorAll('.mom-insights [data-mom-cluster]')];
            insightItems.forEach(item => {
                item.hidden = cluster === 'all'
                    ? item.dataset.momCluster !== 'all'
                    : item.dataset.momCluster !== cluster;
            });
            const insights = card.querySelector('.mom-insights');
            if (insights) insights.hidden = insightItems.length > 0 && insightItems.every(item => item.hidden);
        }

        applyMomClusterFilter(activeMomCluster);

        let momLoadController = null;
        async function loadMomDateWithoutRefresh(input) {
            const form = input.form;
            const currentCard = document.getElementById('mom-cluster-card');
            if (!form || !currentCard) return;

            const expandedTargets = Array.from(currentCard.querySelectorAll('.toggle-sf[aria-expanded="true"]'))
                .map(button => button.getAttribute('aria-controls'));
            const scrollPosition = { x: window.scrollX, y: window.scrollY };
            const requestUrl = new URL(form.action, window.location.href);
            requestUrl.search = window.location.search;
            const fields = new FormData(form);
            for (const name of ['mom_date', 'mom_product']) {
                if (fields.has(name)) requestUrl.searchParams.set(name, fields.get(name));
            }

            momLoadController?.abort();
            const controller = new AbortController();
            momLoadController = controller;
            currentCard.querySelector('.mom-loading')?.remove();
            const loader = document.createElement('div');
            loader.className = 'mom-loading';
            loader.setAttribute('role', 'status');
            loader.setAttribute('aria-live', 'polite');
            loader.innerHTML = '<span><i aria-hidden="true"></i>Memuat data MoM…</span>';
            currentCard.appendChild(loader);
            currentCard.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(requestUrl.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Surya-Card': 'mom' },
                    signal: controller.signal
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const html = await response.text();
                const nextDocument = new DOMParser().parseFromString(html, 'text/html');
                const nextCard = nextDocument.getElementById('mom-cluster-card');
                if (!nextCard) throw new Error('MoM card tidak ditemukan');
                if (controller !== momLoadController) return;

                currentCard.replaceWith(nextCard);
                applyMomClusterFilter(activeMomCluster);
                expandedTargets.forEach(targetId => {
                    const button = nextCard.querySelector(`.toggle-sf[aria-controls="${targetId}"]`);
                    if (button) setMomSfDetail(button, true);
                });

                if (window.feather) feather.replace();
                window.history.replaceState({}, '', requestUrl.toString());
                requestAnimationFrame(() => window.scrollTo(scrollPosition.x, scrollPosition.y));
            } catch (error) {
                if (error.name === 'AbortError' || controller !== momLoadController) return;
                console.error('Gagal memperbarui MoM', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Data MoM gagal diperbarui',
                    text: 'Silakan coba pilih produk atau tanggal kembali.'
                });
            } finally {
                if (controller === momLoadController) {
                    loader.remove();
                    currentCard.removeAttribute('aria-busy');
                }
            }
        }

        document.addEventListener('change', function(event) {
            if (event.target.id === 'mom_date' || event.target.id === 'mom-product') loadMomDateWithoutRefresh(event.target);
        });

        document.addEventListener('click', function(event) {
            const clusterTab = event.target.closest('[data-mom-cluster-tab]');
            if (clusterTab) {
                applyMomClusterFilter(clusterTab.dataset.momClusterTab);
                return;
            }
            const sfButton = event.target.closest('.toggle-sf');
            if (sfButton) {
                setMomSfDetail(sfButton);
                return;
            }
            if (event.target.closest('#mom-copy-btn')) copyMomClusterAsImage();
            if (event.target.closest('#mom-download-btn')) downloadMomClusterAsPng();
        });

        // Tabel dan grafik memakai sumber data tahunan yang sama agar angkanya selalu konsisten.
        const monthlySalesLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        const monthlySalesColors = ['#4E79A7', '#F28E2B', '#E15759', '#76B7B2', '#59A14F', '#EDC948', '#B07AA1', '#FF5A8A', '#9C755F', '#17A2B8'];
        const monthlySalesCompareColors = ['#c8102e', '#2e8b7d', '#5278c8'];
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
            if (qty >= 1000000) return `${Math.round(qty / 1000).toLocaleString(dashboardNumberLocale)}k`;
            if (qty >= 1000) return `${Math.round(qty / 1000).toLocaleString(dashboardNumberLocale)}k`;
            return qty.toLocaleString(dashboardNumberLocale);
        }

        function updateSuryaSalesRanking() {
            const payload = getMonthlySalesPayload();
            const matrix = payload.monthly || {};
            const month = Number(document.getElementById('surya-sales-ranking-month')?.value || payload.currentMonth || 1);
            const isMtd = Boolean(payload.cutoffDate) && Number(payload.currentMonth) === month;
            const rows = Object.entries(matrix).map(([name, values]) => ({ name, value: Number(values?.[month] || 0) }))
                .sort((a, b) => b.value - a.value || a.name.localeCompare(b.name)).filter(row => row.value > 0);
            const list = document.getElementById('surya-sales-ranking-list');
            const total = rows.reduce((sum, row) => sum + row.value, 0);
            if (!list) return;
            list.innerHTML = rows.slice(0, 8).map((row, index) => `<li><span class="surya-rank-no">${String(index + 1).padStart(2, '0')}</span><div class="surya-rank-content"><div><span>${escapeHtml(row.name)}</span><strong>${nf(row.value)}</strong></div><i><b style="width:${total ? Math.max(3, row.value / rows[0].value * 100) : 0}%"></b></i></div></li>`).join('') || '<li class="surya-sales-ranking-empty">Belum ada data penjualan.</li>';
            const totalNode = document.getElementById('surya-sales-ranking-total');
            if (totalNode) totalNode.textContent = `${nf(total)} unit${isMtd ? ' · MTD' : ''}`;
            const note = document.getElementById('surya-sales-mtd-note');
            if (note) {
                note.hidden = !isMtd;
                note.textContent = isMtd ? `Bulan berjalan · data sampai ${payload.cutoffLabel || payload.cutoffDate || ''}. Bandingkan dengan rentang tanggal yang setara.` : '';
            }
            const title = document.getElementById('surya-sales-ranking-title');
            if (title) title.textContent = `Peringkat wilayah${isMtd ? ' · MTD' : ''}`;
        }

        function escapeHtml(value) {
            const node = document.createElement('span');
            node.textContent = String(value ?? '');
            return node.innerHTML;
        }

        function monthlySalesDatasets(payload, labels) {
            const matrix = payload.monthly || {};
            const tapNames = Object.keys(matrix);
            if (!payload.suryaPresentation) return tapNames.map((tap, index) => ({
                label: tap,
                data: labels.map((_, monthIndex) => Number(matrix[tap]?.[monthIndex + 1] || 0)),
                borderColor: monthlySalesColors[index % monthlySalesColors.length],
                backgroundColor: monthlySalesColors[index % monthlySalesColors.length],
                borderWidth: 2.25, pointRadius: 3, pointHoverRadius: 5, tension: .28, fill: false
            }));
            const selected = [...document.querySelectorAll('#surya-sales-compare-options input:checked')].map(input => input.value).slice(0, 3);
            const comparing = selected.length > 0;
            const series = comparing ? selected : ['__all__'];
            const currentIndex = Number(payload.currentMonth) - 1;
            return series.map((key, index) => {
                const name = key === '__all__' ? 'Semua wilayah' : key;
                const values = labels.map((_, monthIndex) => key === '__all__'
                    ? tapNames.reduce((sum, tap) => sum + Number(matrix[tap]?.[monthIndex + 1] || 0), 0)
                    : Number(matrix[key]?.[monthIndex + 1] || 0));
                const color = key === '__all__' ? '#c8102e' : monthlySalesCompareColors[index % monthlySalesCompareColors.length];
                return {
                    label: name, data: values, borderColor: color, backgroundColor: key === '__all__' ? 'rgba(200,16,46,.08)' : color,
                    borderWidth: key === '__all__' ? 3 : 2.5, pointRadius: context => context.dataIndex === currentIndex ? 5 : 2.5,
                    pointHoverRadius: 6, pointBackgroundColor: '#fff', pointBorderWidth: 2, tension: .3,
                    fill: key === '__all__', segment: { borderDash: context => context.p1DataIndex === currentIndex ? [7, 6] : undefined }
                };
            });
        }

        const annualBarValuePlugin = {
            id: 'annualBarValuePlugin',
            afterDatasetsDraw(chart) {
                if (getMonthlySalesPayload().period !== 'annual') return;
                const ctx = chart.ctx;
                ctx.save();
                ctx.fillStyle = '#4b5563';
                ctx.font = getMonthlySalesPayload().suryaPresentation ? '700 9px Manrope, sans-serif' : '700 9px sans-serif';
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
                document.querySelector('.surya-sales-chart-layout')?.classList.add('is-annual');
                document.querySelector('.surya-sales-ranking')?.classList.add('is-hidden');
                document.querySelector('.surya-sales-compare')?.classList.add('is-hidden');
            } else {
                document.querySelector('.surya-sales-chart-layout')?.classList.remove('is-annual');
                const lastDataMonth = Math.max(1, Number(payload.currentMonth) || 0, ...tapNames.flatMap(tap =>
                    monthlySalesLabels.map((_, index) => Number(monthlySalesMatrix[tap]?.[index + 1] || 0) > 0 ? index + 1 : 0)
                ));
                labels = monthlySalesLabels.slice(0, lastDataMonth).map((label, index) => payload.suryaPresentation && index + 1 === Number(payload.currentMonth) ? `${label} · MTD` : label);
                datasets = monthlySalesDatasets(payload, labels);
                const compareOptions = document.getElementById('surya-sales-compare-options');
                if (compareOptions && !compareOptions.dataset.loaded) {
                    compareOptions.innerHTML = tapNames.sort((a, b) => a.localeCompare(b)).map((tap, index) => `<label><input type="checkbox" value="${escapeHtml(tap)}"><span class="surya-compare-dot" style="--compare-color:${monthlySalesCompareColors[index % monthlySalesCompareColors.length]}"></span>${escapeHtml(tap)}</label>`).join('');
                    compareOptions.dataset.loaded = 'true';
                }
                const rankingMonth = document.getElementById('surya-sales-ranking-month');
                if (rankingMonth && !rankingMonth.options.length) {
                    labels.forEach((label, index) => rankingMonth.add(new Option(label, String(index + 1))));
                    rankingMonth.value = String(payload.currentMonth || labels.length);
                }
                updateSuryaSalesRanking();
                document.querySelector('.surya-sales-ranking')?.classList.remove('is-hidden');
                document.querySelector('.surya-sales-compare')?.classList.remove('is-hidden');
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
                            labels: { usePointStyle: true, boxWidth: 8, padding: 18, font: { family: payload.suryaPresentation ? 'DM Sans' : '"Geist Variable"', size: 11 } }
                        },
                        tooltip: {
                            titleFont: { family: payload.suryaPresentation ? 'Manrope' : '"Geist Variable"', weight: '700' },
                            bodyFont: { family: payload.suryaPresentation ? 'Manrope' : '"Geist Variable"', weight: '600' },
                            callbacks: { label: context => ` ${context.dataset.label}: ${nf(context.parsed.y)}` }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: context => payload.suryaPresentation && context.index === Number(payload.currentMonth) - 1 ? '#c8102e' : '#778397', font: { family: payload.suryaPresentation ? 'DM Sans' : '"Geist Variable"', weight: '600' } } },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#e8edf3', drawBorder: false },
                            ticks: { color: '#778397', font: { family: payload.suryaPresentation ? 'Manrope' : '"Geist Variable"', weight: '600' }, callback: value => nf(value) }
                        }
                    }
                }
            });

            return monthlySalesChart;
        }

        document.addEventListener('change', event => {
            const compareInput = event.target.closest('#surya-sales-compare-options input');
            if (compareInput) {
                const chosen = [...document.querySelectorAll('#surya-sales-compare-options input:checked')];
                if (chosen.length > 3) compareInput.checked = false;
                const chosenCount = document.querySelectorAll('#surya-sales-compare-options input:checked').length;
                const compareSummary = document.querySelector('.surya-sales-compare summary');
                if (compareSummary) compareSummary.textContent = chosenCount ? `${chosenCount} wilayah dipilih` : 'Bandingkan wilayah';
                const payload = getMonthlySalesPayload();
                const labels = monthlySalesChart?.data.labels || [];
                if (monthlySalesChart && payload.period !== 'annual') {
                    monthlySalesChart.data.datasets = monthlySalesDatasets(payload, labels);
                    monthlySalesChart.update();
                }
            }
            if (event.target.matches('#surya-sales-ranking-month')) updateSuryaSalesRanking();
        });

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
            requestUrl.search = window.location.search;
            const fields = new FormData(form);
            for (const name of ['sales_year', 'sales_view', 'sales_period', 'sales_annual_cutoff', 'sales_product']) {
                if (fields.has(name)) requestUrl.searchParams.set(name, fields.get(name));
            }
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

        async function reloadMonthlyInjectCard(form) {
            const currentCard = document.getElementById('heatmap-inject-card');
            if (!form || !currentCard || currentCard.dataset.loading === 'true') return;

            const requestUrl = new URL(form.action, window.location.href);
            requestUrl.search = window.location.search;
            const fields = new FormData(form);
            for (const name of ['year', 'inject_product']) {
                if (fields.has(name)) requestUrl.searchParams.set(name, fields.get(name));
            }
            const currentTableWrap = currentCard.querySelector('#heatmap-inject-wrap');
            const horizontalScroll = currentTableWrap?.scrollLeft || 0;
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
                const nextCard = nextDocument.getElementById('heatmap-inject-card');
                if (!nextCard) throw new Error('Card Inject tidak ditemukan');

                currentCard.replaceWith(nextCard);
                const nextTableWrap = nextCard.querySelector('#heatmap-inject-wrap');
                if (nextTableWrap) nextTableWrap.scrollLeft = horizontalScroll;
                if (window.feather?.replace) window.feather.replace();
                window.history.replaceState({}, '', `${requestUrl.pathname}?${requestUrl.searchParams.toString()}#heatmap-inject-card`);
            } catch (error) {
                currentCard.dataset.loading = 'false';
                currentCard.style.opacity = '';
                currentCard.style.pointerEvents = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Card inject gagal dimuat ulang',
                    text: 'Silakan coba kembali.'
                });
            }
        }

        document.addEventListener('submit', event => {
            if (event.target.id === 'monthly-sales-filter-form') {
                event.preventDefault();
                reloadMonthlySalesCard(event.target);
            } else if (event.target.id === 'monthly-inject-filter-form') {
                event.preventDefault();
                reloadMonthlyInjectCard(event.target);
            }
        });

        document.addEventListener('change', event => {
            if (['monthly-inject-product', 'monthly-inject-year'].includes(event.target.id)) {
                reloadMonthlyInjectCard(event.target.form);
                return;
            }
            if (!['monthly-sales-year', 'annual-sales-cutoff', 'monthly-sales-product'].includes(event.target.id)) return;
            reloadMonthlySalesCard(event.target.form);
        });

        document.addEventListener('click', event => {
            if (event.target.closest('#monthly-sales-download-excel-btn')) {
                const form = document.getElementById('monthly-sales-filter-form');
                if (!form) return;
                const params = new URLSearchParams(new FormData(form));
                params.set('sales_download', '1');
                window.location.assign(`${form.action.split('#')[0]}?${params.toString()}`);
                return;
            }
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
            get title() {
                return document.querySelector('#heatmap-inject-card h6')?.textContent.trim() || 'Inject Bulanan';
            },
            subtitle: 'Kerapatan transaksi inject per TAP. Warna makin pekat = performa makin tinggi.',
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
            if (event.target.closest('#heatmap-inject-copy-btn')) {
                copyCanvasExport(() => renderHeatmapToCanvas(heatmapInjectConfig), 'heatmap-inject-status', 'heatmap-inject');
            }
            if (event.target.closest('#heatmap-inject-download-btn')) {
                downloadCanvasExport(() => renderHeatmapToCanvas(heatmapInjectConfig), 'heatmap-inject-status', 'heatmap-inject');
            }
        });

        // Make sure feather icons inside the header buttons render.
        if (window.feather && typeof window.feather.replace === 'function') {
            window.feather.replace();
        }

        // Horizontal ranking, with values drawn at the end of every bar.
        function createDistributionBars(canvasId, labels, values, accent) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            Chart.getChart(canvas)?.destroy();
            const rows = labels.map((label, i) => ({label: label || 'Lainnya', value: Number(values[i]) || 0}))
                .sort((a, b) => b.value - a.value);
            canvas.parentElement.style.height = Math.max(260, rows.length * 38 + 28) + 'px';
            canvas.setAttribute('role', 'img');
            canvas.setAttribute('aria-label', rows.map(row => row.label + ': ' + nf(row.value)).join('; ') || 'Belum ada data');
            const valueLabels = {
                id: 'distributionValues',
                afterDatasetsDraw(chart) {
                    const ctx = chart.ctx;
                    ctx.save();
                    ctx.font = '700 12px DM Sans, sans-serif';
                    ctx.fillStyle = '#344054';
                    ctx.textAlign = 'left';
                    ctx.textBaseline = 'middle';
                    chart.getDatasetMeta(0).data.forEach((bar, index) => {
                        ctx.fillText(nf(rows[index].value), bar.x + 9, bar.y);
                    });
                    if (!rows.length) {
                        ctx.textAlign = 'center';
                        ctx.fillStyle = '#98a2b3';
                        ctx.fillText('Belum ada data', chart.width / 2, chart.height / 2);
                    }
                    ctx.restore();
                }
            };
            new Chart(canvas, {
                type: 'bar',
                plugins: [valueLabels],
                data: {
                    labels: rows.map(row => row.label),
                    datasets: [{data: rows.map(row => row.value), backgroundColor: rows.map((_, i) => i === 0 ? accent : '#e2e8f0'), borderRadius: 5, borderSkipped: false, maxBarThickness: 24}]
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    animation: {duration: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 350},
                    layout: {padding: {right: 85, top: 8, bottom: 8}},
                    plugins: {legend: {display: false}, tooltip: {callbacks: {label: c => ' ' + nf(c.parsed.x) + ' pcs'}}},
                    scales: {
                        x: {beginAtZero: true, display: false},
                        y: {grid: {display: false}, border: {display: false}, ticks: {autoSkip: false, color: '#475467', font: {size: 12, weight: '600'}, padding: 12}}
                    }
                }
            });
        }
        createDistributionBars('pieSales', @json($pieSales->pluck('group_name')), @json($pieSales->pluck('total')), '#c8102e');
        createDistributionBars('topDenoms', @json($pieDenoms->pluck('denom_name')), @json($pieDenoms->pluck('total')), '#099268');
        const distributionControls = document.querySelector('[data-distribution-controls]');
        if (distributionControls) {
            const productSelect = distributionControls.querySelector('.distribution-product');
            const clusterSelect = distributionControls.querySelector('#distribution-cluster');
            const tapSelect = distributionControls.querySelector('#distribution-tap');
            const status = distributionControls.querySelector('.distribution-status');
            const filterSelects = [productSelect, clusterSelect, tapSelect].filter(Boolean);
            let controller;
            let committed = Object.fromEntries(filterSelects.map(select => [select.name || select.id, select.value]));
            const updateTapOptions = () => {
                const cluster = clusterSelect?.value || 'all';
                let selectedStillAvailable = false;
                [...(tapSelect?.options || [])].forEach((option, index) => {
                    if (index === 0) return;
                    const clusters = (option.dataset.clusters || '').split(',').filter(Boolean);
                    option.hidden = cluster !== 'all' && !clusters.includes(cluster);
                    if (!option.hidden && option.value === tapSelect.value) selectedStillAvailable = true;
                });
                if (tapSelect && !selectedStillAvailable) tapSelect.value = 'all';
            };
            const refreshDistribution = async () => {
                controller?.abort();
                const active = new AbortController();
                controller = active;
                status.textContent = 'Memuat data…';
                filterSelects.forEach(select => select.setAttribute('aria-busy', 'true'));
                const url = new URL(window.location.href);
                url.searchParams.set('distribution_sales_product', productSelect.value);
                url.searchParams.set('distribution_sales_cluster', clusterSelect.value);
                url.searchParams.set('distribution_sales_tap', tapSelect.value);
                try {
                    const response = await fetch(url, {signal:active.signal, headers:{'X-Surya-Card':'distribution-sales', 'Accept':'application/json'}});
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    const data = await response.json();
                    if (controller !== active) return;
                    createDistributionBars('pieSales', data.labels, data.values, '#c8102e');
                    createDistributionBars('topDenoms', data.denom_labels, data.denom_values, '#099268');
                    committed = Object.fromEntries(filterSelects.map(select => [select.name || select.id, select.value]));
                    status.textContent = '';
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('distribution_sales_product', productSelect.value);
                    currentUrl.searchParams.set('distribution_sales_cluster', clusterSelect.value);
                    currentUrl.searchParams.set('distribution_sales_tap', tapSelect.value);
                    window.history.replaceState({}, '', currentUrl);
                } catch (error) {
                    if (error.name !== 'AbortError' && controller === active) {
                        filterSelects.forEach(select => { select.value = committed[select.name || select.id]; });
                        updateTapOptions();
                        status.textContent = 'Gagal memuat. Silakan coba lagi.';
                    }
                } finally {
                    if (controller === active) filterSelects.forEach(select => select.removeAttribute('aria-busy'));
                }
            };
            clusterSelect?.addEventListener('change', () => { updateTapOptions(); refreshDistribution(); });
            productSelect?.addEventListener('change', refreshDistribution);
            tapSelect?.addEventListener('change', refreshDistribution);
            updateTapOptions();
        }

        // ================= INTERACTIVE TOGGLES ================= 
        $(document).off('click.dashboardToggle', '.toggle-validity').on('click.dashboardToggle', '.toggle-validity', function() {
            const target = document.getElementById(this.getAttribute('aria-controls'));
            if (!target) return;

            const expanded = target.classList.contains('d-none');
            target.classList.toggle('d-none', !expanded);
            this.textContent = expanded ? '−' : '+';
            this.setAttribute('aria-expanded', String(expanded));
            this.setAttribute('aria-label', expanded ? 'Tutup rincian' : 'Tampilkan rincian');
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
