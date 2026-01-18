@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                {{-- ================= KPI ================= --}}
                <div class="row">
                    @php
                        $cards = [
                            ['STOK SEGEL', $stokSegel, 'primary', 'shield'],
                            ['STOK INJECT', $stokInject, 'success', 'zap'],
                            ['SALES BULAN INI', $salesBulanIni, 'info', 'bar-chart'],
                            ['MoM', number_format($mom, 2) . ' %', $mom >= 0 ? 'success' : 'danger', 'trending-up'],
                        ];
                    @endphp

                    @foreach ($cards as $c)
                        <div class="col-md-3">
                            <div class="card bg-{{ $c[2] }} text-white shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-uppercase">{{ $c[0] }}</small>
                                            <h3 class="mb-0">
                                                {{ is_numeric($c[1]) ? number_format($c[1]) : $c[1] }}
                                            </h3>
                                        </div>
                                        <i data-feather="{{ $c[3] }}" style="width:36px;height:36px;opacity:.8"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ================= CHART SALES ================= --}}
                <div class="card mt-4 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>📈 Tren Sales</strong>
                        <div>
                            <a href="?mode=daily"
                                class="btn btn-sm {{ $mode == 'daily' ? 'btn-primary' : 'btn-light' }}">Harian</a>
                            <a href="?mode=weekly"
                                class="btn btn-sm {{ $mode == 'weekly' ? 'btn-primary' : 'btn-light' }}">Mingguan</a>
                            <a href="?mode=monthly"
                                class="btn btn-sm {{ $mode == 'monthly' ? 'btn-primary' : 'btn-light' }}">Bulanan</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height:320px">
                            <canvas id="chartSales"></canvas>
                        </div>
                    </div>
                </div>

                {{-- ================= CHART INJECT ================= --}}
                <div class="card mt-4 shadow-sm">
                    <div class="card-header">
                        <strong>⚡ Tren Inject per Cluster</strong>
                    </div>
                    <div class="card-body">
                        <div style="height:260px">
                            <canvas id="chartInject"></canvas>
                        </div>
                    </div>
                </div>

                {{-- ================= TABLE ================= --}}
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header"><strong>Sales per TAP</strong></div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    @foreach ($salesTap as $r)
                                        <tr>
                                            <td>{{ $r->idtap }}</td>
                                            <td class="text-right">{{ number_format($r->qty) }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header"><strong>🔥 Produk Terlaris</strong></div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    @foreach ($topProduk as $r)
                                        <tr>
                                            <td>{{ $r->denom }}</td>
                                            <td class="text-right text-success">{{ number_format($r->qty) }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const nf = v => new Intl.NumberFormat('id-ID').format(v);

        // ================= SALES =================
        new Chart(document.getElementById('chartSales'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartSales->pluck('label')) !!},
                datasets: [{
                    label: 'Sales',
                    data: {!! json_encode($chartSales->pluck('total')) !!},
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78,115,223,.15)',
                    fill: true,
                    tension: .4,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: c => nf(c.parsed.y)
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: v => nf(v)
                        }
                    }
                }
            }
        });

        // ================= INJECT =================
        new Chart(document.getElementById('chartInject'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartInject->pluck('label')) !!},
                datasets: [{
                        label: 'Dumai Bengkalis',
                        data: {!! json_encode($chartInject->pluck('dumai')) !!},
                        backgroundColor: '#1cc88a'
                    },
                    {
                        label: 'Rokan Hilir',
                        data: {!! json_encode($chartInject->pluck('rohil')) !!},
                        backgroundColor: '#36b9cc'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: c => nf(c.parsed.y)
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: v => nf(v)
                        }
                    }
                }
            }
        });
    </script>
@endpush
