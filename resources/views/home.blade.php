@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                {{-- ================= KPI ================= --}}
                <div class="row">
                    @php
                        $cards = [
                            ['STOK SEGEL', $stokSegel, 'primary', 'shield', 'Total stok aktif'],
                            ['STOK INJECT', $stokInject, 'success', 'zap', 'Total stok inject'],
                            ['SALES BULAN INI', $salesBulanIni, 'info', 'bar-chart', 'Akumulasi bulan berjalan'],
                            [
                                'MoM',
                                number_format($mom, 2) . ' %',
                                $mom >= 0 ? 'success' : 'danger',
                                $mom >= 0 ? 'trending-up' : 'trending-down',
                                $mom >= 0 ? '📈 Growth vs bulan lalu' : '⚠️ Minus Cek',
                            ],
                        ];
                    @endphp

                    @foreach ($cards as $c)
                        <div class="col-md-3">
                            <div class="card bg-{{ $c[2] }} text-white shadow-sm h-100">
                                <div class="card-body py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-uppercase opacity-75">{{ $c[0] }}</small>
                                            <h3 class="mb-0 font-weight-bold">
                                                {{ is_numeric($c[1]) ? number_format($c[1]) : $c[1] }}
                                            </h3>
                                            <small class="opacity-75">{{ $c[4] }}</small>
                                        </div>
                                        <i data-feather="{{ $c[3] }}" style="width:38px;height:38px;opacity:.9"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ================= CHART SALES ================= --}}
                <div class="card mt-4 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>📈 Tren Sales</strong>
                            <div class="small text-muted">
                                {{ ucfirst($mode) }} ·
                                {{ $mom >= 0 ? 'Performa Naik' : 'Performa Turun' }}
                            </div>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <a href="?mode=daily"
                                class="btn {{ $mode == 'daily' ? 'btn-primary' : 'btn-light' }}">Harian</a>
                            <a href="?mode=monthly"
                                class="btn {{ $mode == 'monthly' ? 'btn-primary' : 'btn-light' }}">Bulanan</a>
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
                        <div class="small text-muted">Distribusi inject antar cluster</div>
                    </div>
                    <div class="card-body">
                        <div style="height:260px">
                            <canvas id="chartInject"></canvas>
                        </div>
                    </div>
                </div>

                {{-- ================= TAP ACTIVITY ================= --}}
                <div class="row mt-4">

                    <div class="col-md-6">
                        <div class="card mt-4 shadow-sm">
                            <div class="card-header">
                                <strong>🗓️ Aktivitas Input TAP</strong>
                                <div class="small text-muted">
                                    Monitoring terakhir input SF Masuk & SF Keluar
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>TAP</th>
                                            <th class="text-center">Last SF Masuk</th>
                                            <th class="text-center">Last SF Keluar</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($tapList as $tap)
                                            @php
                                                $masuk = $tapMasuk[$tap] ?? null;
                                                $keluar = $tapKeluar[$tap] ?? null;

                                                $latest = max($masuk, $keluar);
                                                $diff = $latest
                                                    ? \Carbon\Carbon::parse($latest)->diffInDays(now())
                                                    : null;
                                            @endphp
                                            <tr>
                                                <td><strong>{{ $tap }}</strong></td>

                                                <td class="text-center">
                                                    {{ $masuk ? \Carbon\Carbon::parse($masuk)->format('d M Y') : '—' }}
                                                </td>

                                                <td class="text-center">
                                                    {{ $keluar ? \Carbon\Carbon::parse($keluar)->format('d M Y') : '—' }}
                                                </td>

                                                <td class="text-center">
                                                    @if (is_null($latest))
                                                        <span class="badge badge-secondary">Belum Ada Data</span>
                                                    @elseif ($diff <= 1)
                                                        <span class="badge badge-success">Aktif</span>
                                                    @elseif ($diff <= 3)
                                                        <span class="badge badge-warning">Perlu Dicek</span>
                                                    @else
                                                        <span class="badge badge-danger">Tidak Aktif</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        {{-- ================= MoM HARIAN ================= --}}
                        <div class="card mt-4 shadow-sm">
                            <div class="card-header">
                                <strong>📉 Perbandingan Sales Harian (MoM)</strong>
                                <div class="small text-muted">Day-to-day vs bulan sebelumnya</div>
                            </div>
                            <div class="card-body">
                                <div style="height:280px">
                                    <canvas id="chartMomDaily"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ================= MoM PER TAP ================= --}}

                <div class="card mt-4 shadow-sm">
                    <div class="card-header">
                        <strong>📊 MoM per TAP</strong>
                        <div class="small text-muted">
                            Bulan ini (MTD) vs M-1 (partial) • referensi full month bulan lalu
                        </div>
                    </div>

                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>TAP</th>
                                <th class="text-end">MTD</th>
                                <th class="text-end text-muted">M-1</th>
                                <th class="text-end">Bulan Lalu (Full)</th>
                                <th class="text-end">MoM</th>
                            </tr>
                        </thead>

                        <tbody>
                        <tbody>
                            @foreach ($momTap as $r)
                                {{-- ================= TAP ROW ================= --}}
                                <tr class="border-top border-primary">
                                    <td>
                                        <button class="btn btn-sm btn-link toggle-sf"
                                            data-target="sf-{{ Str::slug($r->idtap) }}">
                                            ➕
                                        </button>
                                        <strong>{{ $r->idtap }}</strong>
                                    </td>

                                    <td class="text-end font-weight-bold">
                                        {{ number_format($r->curr_qty) }}
                                    </td>

                                    <td class="text-end text-muted">
                                        {{ number_format($r->prev_partial_qty) }}
                                    </td>

                                    <td class="text-end">
                                        {{ number_format($r->prev_full_qty) }}
                                    </td>

                                    <td
                                        class="text-end font-weight-bold {{ $r->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                        {!! $r->mom >= 0 ? '▲' : '▼' !!}
                                        {{ number_format(abs($r->mom), 2) }} %
                                    </td>
                                </tr>

                                {{-- ================= SF DETAIL ROW ================= --}}
                                <tr id="sf-{{ Str::slug($r->idtap) }}" class="sf-row d-none bg-light">
                                    <td colspan="5" class="p-0">
                                        <table class="table table-sm mb-0 table-bordered">
                                            <thead class="bg-white">
                                                <tr>
                                                    <th>SF</th>
                                                    <th class="text-end">MTD</th>
                                                    <th class="text-end">M-1</th>
                                                    <th class="text-end">MoM</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($sfByTap[$r->idtap] ?? [] as $sf)
                                                    <tr>
                                                        <td>{{ $sf->namasf ?? $sf->idsf }}</td>
                                                        <td class="text-end">{{ number_format($sf->curr_qty) }}</td>
                                                        <td class="text-end text-muted">
                                                            {{ number_format($sf->prev_qty) }}
                                                        </td>
                                                        <td
                                                            class="text-end {{ $sf->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                                            {{ number_format($sf->mom, 2) }} %
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>


                        {{-- ================= CLUSTER SUMMARY ================= --}}
                        <tfoot class="bg-white font-weight-bold">
                            <tr class="border-top border-primary">
                                <td>
                                    <span class="badge badge-primary mr-2">CLUSTER</span>
                                    Dumai Bengkalis
                                </td>
                                <td class="text-end">{{ number_format($momCluster['dumai_bengkalis']->curr_qty) }}</td>
                                <td class="text-end text-muted">
                                    {{ number_format($momCluster['dumai_bengkalis']->prev_partial_qty) }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($momCluster['dumai_bengkalis']->prev_full_qty) }}
                                </td>
                                <td
                                    class="text-end {{ $momCluster['dumai_bengkalis']->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $r->mom >= 0 ? '▲' : '▼' !!}
                                    {{ number_format($momCluster['dumai_bengkalis']->mom, 2) }} %
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <span class="badge badge-success mr-2">CLUSTER</span>
                                    Rokan Hilir
                                </td>
                                <td class="text-end">{{ number_format($momCluster['rokan_hilir']->curr_qty) }}</td>
                                <td class="text-end text-muted">
                                    {{ number_format($momCluster['rokan_hilir']->prev_partial_qty) }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($momCluster['rokan_hilir']->prev_full_qty) }}
                                </td>
                                <td
                                    class="text-end {{ $momCluster['rokan_hilir']->mom >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $r->mom >= 0 ? '▲' : '▼' !!}
                                    {{ number_format($momCluster['rokan_hilir']->mom, 2) }} %
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- ================= TOP SF ================= --}}
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header text-success">
                                <strong>🚀 Top Growth SF</strong>
                                <div class="small text-muted">SF dengan pertumbuhan terbaik</div>
                            </div>
                            <table class="table table-sm mb-0">
                                @foreach ($topGrowth as $r)
                                    <tr>
                                        <td>{{ $r->namasf ?? $r->idsf }}</td>
                                        <td class="text-end text-success font-weight-bold">
                                            +{{ number_format($r->mom, 2) }} %
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header text-danger">
                                <strong>📉 Top Drop SF</strong>
                                <div class="small text-muted">Perlu follow-up & evaluasi</div>
                            </div>
                            <table class="table table-sm mb-0">
                                @foreach ($topDrop as $r)
                                    <tr>
                                        <td>{{ $r->namasf ?? $r->idsf }}</td>
                                        <td class="text-end text-danger font-weight-bold">
                                            {{ number_format($r->mom, 2) }} %
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
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
        const nf = v => new Intl.NumberFormat('id-ID').format(
            v
        ); // ================= SALES ================= 
        new Chart(document.getElementById('chartSales'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartSales->pluck('label')) !!},
                datasets: [{
                    data: {!! json_encode($chartSales->pluck('total')) !!},
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78,115,223,.12)',
                    fill: true,
                    tension: .4,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
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
                    },
                    x: {
                        grid: {
                            display: false
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
                }, {
                    label: 'Rokan Hilir',
                    data: {!! json_encode($chartInject->pluck('rohil')) !!},
                    backgroundColor: '#36b9cc'
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
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        // ================= MoM DAILY ================= 
        new Chart(document.getElementById('chartMomDaily'), {
            type: 'line',
            data: {
                labels: {!! json_encode($momDaily->pluck('day')) !!},
                datasets: [{
                    label: 'Bulan Ini',
                    data: {!! json_encode($momDaily->pluck('curr_qty')) !!},
                    borderColor: '#4e73df',
                    tension: .4
                }, {
                    label: 'Bulan Lalu',
                    data: {!! json_encode($momDaily->pluck('prev_qty')) !!},
                    borderColor: '#e74a3b',
                    tension: .4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: c => nf(c.parsed.y)
                        }
                    }
                }
            }
        });
    </script>
    <script>
        $('#filterTap').on('change', function() {
            let tap = $(this).val().toLowerCase();
            $('[id^="tap-"]').each(function() {
                let parentTap = $(this).attr('id').replace('tap-', '').toLowerCase();
                tap === '' || parentTap.includes(tap) ? $(this).prev().show() : $(this).prev().hide();
            });
        });
    </script>
    <script>
        // ================= MoM DAILY ================= 
        new Chart(document.getElementById('chartMomDaily'), {
            type: 'line',
            data: {
                labels: {!! json_encode($momDaily->pluck('day')) !!},
                datasets: [{
                    label: 'Bulan Ini',
                    data: {!! json_encode($momDaily->pluck('curr_qty')) !!},
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78,115,223,.1)',
                    tension: .4
                }, {
                    label: 'Bulan Lalu',
                    data: {!! json_encode($momDaily->pluck('prev_qty')) !!},
                    borderColor: '#e74a3b',
                    backgroundColor: 'rgba(231,74,59,.08)',
                    tension: .4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: c => ' ' + nf(c.parsed.y)
                        }
                    }
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
                    this.innerText = '➖';
                } else {
                    target.classList.add('d-none');
                    this.innerText = '➕';
                }
            });
        });
    </script>
@endpush
