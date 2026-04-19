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
</style>

<div class="main-panel">
    <div class="content">
        <div class="page-inner">

            {{-- ================= TOP CONTROL BAR ================= --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0 text-dark">Executive Summary</h4>
                <form action="" method="GET" class="d-flex shadow-sm rounded">
                    <select name="year" class="form-control mr-2 border-0 bg-white text-dark font-weight-bold" onchange="this.form.submit()">
                        @for($i = date('Y'); $i >= 2023; $i--)
                            <option value="{{ $i }}" {{ $selectedYear == $i ? 'selected' : '' }}>📅 Tahun {{ $i }}</option>
                        @endfor
                    </select>
                    <select name="mode" class="form-control border-0 bg-white text-dark font-weight-bold" onchange="this.form.submit()">
                        <option value="daily" {{ $mode == 'daily' ? 'selected' : '' }}>⏱ Harian</option>
                        <option value="monthly" {{ $mode == 'monthly' ? 'selected' : '' }}>📊 Bulanan</option>
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
                            <h6 class="font-weight-bold text-dark mb-0">🍩 Distribusi Sales (Bulan Berjalan)</h6>
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
                            <h6 class="font-weight-bold text-dark mb-0">🍩 Distribusi Inject (Bulan Berjalan)</h6>
                            <small class="text-muted">Porsi tembak injeksi ke Outlet</small>
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
            <div class="card enterprise-shadow mb-4">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <h6 class="font-weight-bold text-dark mb-0">🗺️ Heatmap Penjualan Bulanan (Tahun {{ $selectedYear }})</h6>
                    <small class="text-muted">Kerapatan transaksi per TAP. Warna makin gelap = performa makin tinggi.</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered text-center mb-0" style="font-size:13px; border-bottom:0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-left font-weight-bold border-0 text-secondary">NAMA TAP</th>
                                    @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $m)
                                        <th class="border-0 text-secondary">{{ $m }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matrixSales as $tap => $months)
                                    <tr>
                                        <td class="text-left font-weight-bold bg-white text-dark">{{ $tap }}</td>
                                        @for($i=1; $i<=12; $i++)
                                            @php 
                                                $val = $months[$i] ?? 0; 
                                                $opacity = $val > 0 ? min($val / 20000, 1) : 0; 
                                                // Enterprise Blue heatmap
                                                $opacity = $opacity > 0 && $opacity < 0.15 ? 0.15 : $opacity;
                                                $bg = $val > 0 ? "rgba(66, 133, 244, $opacity)" : "transparent"; 
                                                $color = $opacity > 0.5 ? '#fff' : '#444';
                                            @endphp
                                            <td class="heatmap-cell border-light" style="background-color: {{ $bg }}; color: {{ $color }};">
                                                {{ $val > 0 ? number_format($val) : '-' }}
                                            </td>
                                        @endfor
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
                                    <td class="text-left border-0"><span class="badge badge-light text-dark mr-1">CLUSTER</span> Dumai Bengkalis</td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $totalDumai = 0;
                                            foreach($clusterDumai as $tap) { $totalDumai += ($matrixSales[$tap][$i] ?? 0); }
                                        @endphp
                                        <td class="border-0 opacity-75">{{ $totalDumai > 0 ? number_format($totalDumai) : '-' }}</td>
                                    @endfor
                                </tr>
                                @endif

                                @if(in_array(session('idtap'), ['SBP_DUMAI', 'CLUSTER_ROHIL']))
                                {{-- FOOTER CLUSTER ROKAN HILIR --}}
                                <tr>
                                    <td class="text-left border-0"><span class="badge badge-light text-dark mr-1">CLUSTER</span> Rokan Hilir</td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $totalRohil = 0;
                                            foreach($clusterRohil as $tap) { $totalRohil += ($matrixSales[$tap][$i] ?? 0); }
                                        @endphp
                                        <td class="border-0 opacity-75">{{ $totalRohil > 0 ? number_format($totalRohil) : '-' }}</td>
                                    @endfor
                                </tr>
                                @endif

                                @if(session('idtap') == 'SBP_DUMAI')
                                {{-- GRAND TOTAL KESELURUHAN --}}
                                <tr style="background-color: #e3f2fd; color: #0d47a1;">
                                    <td class="text-left border-0 font-weight-bold">✨ GRAND TOTAL</td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $grandTotal = 0;
                                            foreach($matrixSales as $tap => $months) {
                                                $grandTotal += ($months[$i] ?? 0);
                                            }
                                        @endphp
                                        <td class="border-0 font-weight-bold">{{ $grandTotal > 0 ? number_format($grandTotal) : '-' }}</td>
                                    @endfor
                                </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ================= YEARLY MATRIX INJECT (HEATMAP) ================= --}}
            <div class="card enterprise-shadow mb-4">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <h6 class="font-weight-bold text-dark mb-0">🪄 Heatmap Inject PV Bulanan (Tahun {{ $selectedYear }})</h6>
                    <small class="text-muted">Kerapatan transaksi Inject PV per TAP. Warna makin pekat = performa makin tinggi.</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered text-center mb-0" style="font-size:13px; border-bottom:0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-left font-weight-bold border-0 text-secondary">NAMA TAP</th>
                                    @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'] as $m)
                                        <th class="border-0 text-secondary">{{ $m }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matrixInject as $tap => $months)
                                    <tr>
                                        <td class="text-left font-weight-bold bg-white text-dark">{{ $tap }}</td>
                                        @for($i=1; $i<=12; $i++)
                                            @php 
                                                $val = $months[$i] ?? 0; 
                                                $opacity = $val > 0 ? min($val / 20000, 1) : 0; 
                                                // Enterprise Green heatmap
                                                $opacity = $opacity > 0 && $opacity < 0.15 ? 0.15 : $opacity;
                                                $bg = $val > 0 ? "rgba(25, 135, 84, $opacity)" : "transparent"; 
                                                $color = $opacity > 0.5 ? '#fff' : '#444';
                                            @endphp
                                            <td class="heatmap-cell border-light" style="background-color: {{ $bg }}; color: {{ $color }};">
                                                {{ $val > 0 ? number_format($val) : '-' }}
                                            </td>
                                        @endfor
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
                                    <td class="text-left border-0"><span class="badge badge-light text-dark mr-1">CLUSTER</span> Dumai Bengkalis</td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $totalDumai = 0;
                                            foreach($clusterDumai as $tap) { $totalDumai += ($matrixInject[$tap][$i] ?? 0); }
                                        @endphp
                                        <td class="border-0 opacity-75">{{ $totalDumai > 0 ? number_format($totalDumai) : '-' }}</td>
                                    @endfor
                                </tr>
                                @endif

                                @if(in_array(session('idtap'), ['SBP_DUMAI', 'CLUSTER_ROHIL']))
                                {{-- FOOTER CLUSTER ROKAN HILIR --}}
                                <tr>
                                    <td class="text-left border-0"><span class="badge badge-light text-dark mr-1">CLUSTER</span> Rokan Hilir</td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $totalRohil = 0;
                                            foreach($clusterRohil as $tap) { $totalRohil += ($matrixInject[$tap][$i] ?? 0); }
                                        @endphp
                                        <td class="border-0 opacity-75">{{ $totalRohil > 0 ? number_format($totalRohil) : '-' }}</td>
                                    @endfor
                                </tr>
                                @endif

                                @if(session('idtap') == 'SBP_DUMAI')
                                {{-- GRAND TOTAL KESELURUHAN --}}
                                <tr style="background-color: #e8f5e9; color: #1b5e20;">
                                    <td class="text-left border-0 font-weight-bold">✨ GRAND TOTAL</td>
                                    @for($i=1; $i<=12; $i++)
                                        @php
                                            $grandTotal = 0;
                                            foreach($matrixInject as $tap => $months) {
                                                $grandTotal += ($months[$i] ?? 0);
                                            }
                                        @endphp
                                        <td class="border-0 font-weight-bold">{{ $grandTotal > 0 ? number_format($grandTotal) : '-' }}</td>
                                    @endfor
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
                            <small class="text-muted">Volume injeksi antar distrik</small>
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
                            <div class="feed-container" style="max-height: 320px; overflow-y: auto;">
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
                            <div style="height:300px">
                                <canvas id="chartMomDaily"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= MoM PER TAP ================= --}}
            <div class="card enterprise-shadow mb-4">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <h6 class="font-weight-bold text-dark mb-0">📊 Rapor MoM Performa Cluster</h6>
                    <small class="text-muted">Bulan ini (MTD) vs Bulan lalu (partial M-1)</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th class="border-0">LOKASI (TAP / SF)</th>
                                <th class="text-right border-0">ACH MTD</th>
                                <th class="text-right border-0">M-1 PARTIAL</th>
                                <th class="text-right border-0">BULAN LALU (FULL)</th>
                                <th class="text-right border-0">GROWTH (%)</th>
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
                                    <td class="text-right text-muted">{{ number_format($r->prev_full_qty) }}</td>
                                    <td class="text-right font-weight-bold {{ $r->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                        {!! $r->mom >= 0 ? '▲' : '▼' !!} {{ number_format(abs($r->mom), 2) }} %
                                    </td>
                                </tr>

                                <tr id="sf-{{ Str::slug($r->idtap) }}" class="sf-row d-none bg-light">
                                    <td colspan="5" class="p-0 border-0">
                                        <div class="p-3 bg-light border-left border-right">
                                            <table class="table table-sm mb-0 bg-white shadow-sm rounded">
                                                <thead class="bg-white">
                                                    <tr>
                                                        <th class="border-top-0">Anggota Tim SF</th>
                                                        <th class="text-right border-top-0">Capai MTD</th>
                                                        <th class="text-right border-top-0">M-1</th>
                                                        <th class="text-right border-top-0">Kinerja Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($sfByTap[$r->idtap] ?? [] as $sf)
                                                        <tr>
                                                            <td class="text-muted border-bottom-0"><i data-feather="user" style="width:14px;height:14px"></i> {{ $sf->namasf ?? $sf->idsf }}</td>
                                                            <td class="text-right font-weight-bold border-bottom-0">{{ number_format($sf->curr_qty) }}</td>
                                                            <td class="text-right text-muted border-bottom-0">{{ number_format($sf->prev_qty) }}</td>
                                                            <td class="text-right border-bottom-0 {{ $sf->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                                                <span class="badge badge-{{ $sf->mom >= 0 ? 'success' : 'danger' }} badge-pill bg-{{ $sf->mom >= 0 ? 'success' : 'danger' }} text-white font-weight-bold px-2 py-1">
                                                                    {!! $sf->mom >= 0 ? '&#43;' : '' !!} {{ number_format($sf->mom, 2) }} %
                                                                </span>
                                                            </td>
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
                                <td class="text-right opacity-75 border-0">{{ number_format($momCluster['dumai_bengkalis']->prev_full_qty) }}</td>
                                <td class="text-right border-0 {{ $momCluster['dumai_bengkalis']->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $momCluster['dumai_bengkalis']->mom >= 0 ? '▲' : '▼' !!} {{ number_format(abs($momCluster['dumai_bengkalis']->mom), 2) }} %
                                </td>
                            </tr>
                            @endif
                            @if(in_array(session('idtap'), ['SBP_DUMAI', 'CLUSTER_ROHIL']))
                            <tr>
                                <td class="border-0"><span class="badge badge-light text-dark mr-2">CLUSTER</span> Rokan Hilir</td>
                                <td class="text-right border-0">{{ number_format($momCluster['rokan_hilir']->curr_qty) }}</td>
                                <td class="text-right opacity-75 border-0">{{ number_format($momCluster['rokan_hilir']->prev_partial_qty) }}</td>
                                <td class="text-right opacity-75 border-0">{{ number_format($momCluster['rokan_hilir']->prev_full_qty) }}</td>
                                <td class="text-right border-0 {{ $momCluster['rokan_hilir']->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $momCluster['rokan_hilir']->mom >= 0 ? '▲' : '▼' !!} {{ number_format(abs($momCluster['rokan_hilir']->mom), 2) }} %
                                </td>
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
                                <td class="text-right opacity-75 border-0 font-weight-bold">{{ number_format($grandPrevFull) }}</td>
                                <td class="text-right border-0 font-weight-bold {{ $grandMom >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $grandMom >= 0 ? '▲' : '▼' !!} {{ number_format(abs($grandMom), 2) }} %
                                </td>
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
    </script>

    <script>
        document.querySelectorAll('.toggle-sf').forEach(btn => {
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
    </script>
@endpush
