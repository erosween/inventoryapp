@extends('layout.layout')

@section('content')
    @php
        $modeLabels = ['all' => 'Semua Lokasi', 'tap' => 'Stok TAP', 'sf' => 'Stok Sales Force'];
        $typeLabels = [
            'DO_MASUK' => 'DO Masuk', 'TRANSFER_TAP' => 'Transfer TAP', 'RETUR_BO' => 'Retur BO',
            'TAP_KE_SF' => 'TAP ke SF', 'RETUR_SF' => 'Retur SF', 'KELUAR_SF' => 'Keluar SF',
            'KONVERSI_KELUAR' => 'Konversi Segel', 'KONVERSI_MASUK' => 'Hasil Inject',
            'VOUCHER_RUSAK' => 'Voucher Rusak', 'ADJUSTMENT' => 'Penyesuaian',
        ];
        $locationName = function ($type, $id) use ($salesById) {
            return $type === 'SF' ? ($salesById->get($id)->namasf ?? $id) : $id;
        };
    @endphp
    <style>
        .journey-shell { max-width: 1380px; }
        .journey-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; margin-bottom:22px; }
        .journey-title { margin:0; color:#172033; font-size:27px; font-weight:700; letter-spacing:-.025em; }
        .journey-copy { margin:6px 0 0; color:#7b8497; font-size:13px; }
        .journey-filter { margin-bottom:18px; padding:18px 20px; }
        .journey-filter-grid { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:12px; align-items:end; }
        .journey-filter label { display:block; margin-bottom:7px; color:#536076; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; }
        .journey-filter .form-control { height:40px; font-size:11px; }
        .journey-filter-actions { display:flex; gap:7px; }
        .journey-filter-actions .btn { flex:1; height:40px; font-size:11px; }
        .journey-metrics { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:12px; margin-bottom:18px; }
        .journey-metric { position:relative; overflow:hidden; padding:18px 20px; border:1px solid #e7eaf1; border-radius:13px; background:#fff; box-shadow:0 10px 28px rgba(30,41,59,.055); }
        .journey-metric::after { content:''; position:absolute; right:-18px; top:-24px; width:80px; height:80px; border-radius:50%; background:rgba(79,70,229,.06); }
        .journey-metric-label { color:#8a93a4; font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; }
        .journey-metric-value { margin-top:8px; color:#172033; font-size:22px; font-weight:800; }
        .journey-metric.in .journey-metric-value { color:#169b70; }
        .journey-metric.out .journey-metric-value { color:#e11d48; }
        .journey-metric.net .journey-metric-value { color:#4f46e5; }
        .journey-metric.balance { border-color:#d8d5ff; background:linear-gradient(145deg,#fff,#f7f6ff); }
        .journey-metric.balance .journey-metric-value { color:#3730a3; }
        .journey-table-card { overflow:hidden; }
        .journey-table-head { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:16px 20px; border-bottom:1px solid #e7eaf1; }
        .journey-table-title { margin:0; color:#172033; font-size:14px; font-weight:700; }
        .journey-period { color:#7b8497; font-size:10px; }
        .journey-table-wrap { overflow-x:auto; }
        .journey-table { min-width:1080px; margin:0; }
        .journey-table thead th { padding:12px 14px!important; background:#f8f9fc; color:#7b8497; font-size:9px; letter-spacing:.07em; text-transform:uppercase; white-space:nowrap; }
        .journey-table tbody td { padding:12px 14px!important; vertical-align:middle; font-size:11px; white-space:nowrap; }
        .movement-badge { display:inline-flex; padding:5px 8px; border-radius:6px; background:#eeedff; color:#4f46e5; font-size:9px; font-weight:700; }
        .location-flow { display:flex; align-items:center; gap:8px; }
        .location-chip { max-width:145px; overflow:hidden; text-overflow:ellipsis; padding:5px 8px; border:1px solid #e7eaf1; border-radius:7px; background:#fff; color:#536076; }
        .flow-arrow { color:#a5adba; }
        .qty-positive { color:#169b70; font-weight:800; }
        .qty-negative { color:#e11d48; font-weight:800; }
        .qty-neutral { color:#7b8497; font-weight:700; }
        .running-balance { color:#3730a3; font-weight:800; background:#faf9ff; }
        .journey-empty { padding:48px 20px; text-align:center; color:#8a93a4; }
        .journey-pagination { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid #e7eaf1; background:#fbfbfd; }
        .journey-pagination-info { color:#8a93a4; font-size:10px; }
        .journey-pagination-actions { display:flex; gap:7px; }
        .journey-pagination-actions .btn { min-width:90px; padding:8px 12px; font-size:10px; }
        @media(max-width:1199.98px){.journey-filter-grid{grid-template-columns:repeat(3,1fr)}.journey-metrics{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:767.98px){.journey-heading{display:block}.journey-metrics{grid-template-columns:repeat(2,1fr)}.journey-filter-grid{grid-template-columns:1fr 1fr}}
        @media(max-width:575.98px){.journey-metrics,.journey-filter-grid{grid-template-columns:1fr}.journey-pagination-info{display:none}.journey-pagination{justify-content:flex-end}}
    </style>

    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                <div class="journey-shell">
                    <div class="journey-heading">
                        <div>
                            <h1 class="journey-title">Stock Movement History</h1>
                            <p class="journey-copy">Riwayat arus masuk, keluar, transfer, dan konversi stok dari sumber transaksi.</p>
                        </div>
                    </div>

                    <form method="GET" class="card journey-filter">
                        <div class="journey-filter-grid">
                            <div><label for="start_date">Dari tanggal</label><input id="start_date" name="start_date" type="date" class="form-control" value="{{ $startDate }}" max="{{ date('Y-m-d') }}"></div>
                            <div><label for="end_date">Sampai tanggal</label><input id="end_date" name="end_date" type="date" class="form-control" value="{{ $endDate }}"></div>
                            <div><label for="journey_tap">TAP</label><select id="journey_tap" name="idtap" class="form-control journey-select" data-placeholder="Semua TAP"><option value=""></option>@foreach($allowedTaps as $tap)<option value="{{ $tap }}" @selected(request('idtap') === $tap)>{{ $tap }}</option>@endforeach</select></div>
                            <div><label for="journey_sf">Sales Force</label><select id="journey_sf" name="idsf" class="form-control journey-select" data-placeholder="Semua SF"><option value=""></option>@foreach($sales as $sf)<option value="{{ $sf->idsf }}" @selected(request('idsf') === $sf->idsf)>{{ $sf->namasf }} · {{ $sf->idtap }}</option>@endforeach</select></div>
                            <div><label for="journey_validity">Validity</label><select id="journey_validity" name="validity" class="form-control journey-select" data-placeholder="Semua validity"><option value=""></option>@foreach($validities as $validity)<option value="{{ $validity }}" @selected(request('validity') === $validity)>{{ $validity }}</option>@endforeach</select></div>
                            <div><label for="journey_denom">Denom</label><select id="journey_denom" name="iddenom" class="form-control journey-select" data-placeholder="Semua denom"><option value=""></option>@foreach($denoms as $denom)<option value="{{ $denom->iddenom }}" @selected(request('iddenom') === $denom->iddenom)>{{ $denom->denom }}</option>@endforeach</select></div>
                            <div class="journey-filter-actions"><a href="{{ route('stock-journey.index') }}" class="btn btn-light">Reset</a><button class="btn btn-primary" type="submit">Terapkan</button></div>
                        </div>
                    </form>

                    <div class="journey-metrics">
                        <div class="journey-metric balance"><div class="journey-metric-label">Saldo Awal</div><div class="journey-metric-value">{{ number_format($openingBalance) }}</div></div>
                        <div class="journey-metric"><div class="journey-metric-label">Jumlah Pergerakan</div><div class="journey-metric-value">{{ number_format($summary->movement_count ?? 0) }}</div></div>
                        <div class="journey-metric in"><div class="journey-metric-label">Stok Masuk</div><div class="journey-metric-value">+{{ number_format($summary->stock_in ?? 0) }}</div></div>
                        <div class="journey-metric out"><div class="journey-metric-label">Stok Keluar</div><div class="journey-metric-value">-{{ number_format($summary->stock_out ?? 0) }}</div></div>
                        <div class="journey-metric net"><div class="journey-metric-label">Perubahan Bersih</div><div class="journey-metric-value">{{ ($summary->net_movement ?? 0) > 0 ? '+' : '' }}{{ number_format($summary->net_movement ?? 0) }}</div></div>
                        <div class="journey-metric balance"><div class="journey-metric-label">Sisa Stok Akhir</div><div class="journey-metric-value">{{ number_format($closingBalance) }}</div></div>
                    </div>

                    <div class="card journey-table-card">
                        <div class="journey-table-head"><h2 class="journey-table-title">Movement Details · {{ $modeLabels[$mode] }}</h2><span class="journey-period">{{ date('d/m/Y', strtotime($startDate)) }} — {{ date('d/m/Y', strtotime($endDate)) }}</span></div>
                        <div class="journey-table-wrap">
                            <table class="table table-hover journey-table">
                                <thead><tr><th>Tanggal</th><th>Jenis</th><th>Perjalanan</th><th>TAP</th><th>Validity</th><th>Denom</th><th class="text-right">Qty</th><th class="text-right">Sisa Stok</th><th>Keterangan</th><th>Sumber</th></tr></thead>
                                <tbody>
                                    @forelse($movements as $movement)
                                        <tr>
                                            <td>{{ date('d/m/Y', strtotime($movement->event_date)) }}</td>
                                            <td><span class="movement-badge">{{ $typeLabels[$movement->movement_type] ?? $movement->movement_type }}</span></td>
                                            <td><div class="location-flow"><span class="location-chip">{{ $locationName($movement->from_type, $movement->from_id) }}</span><i class="fas fa-arrow-right flow-arrow"></i><span class="location-chip">{{ $locationName($movement->to_type, $movement->to_id) }}</span></div></td>
                                            <td>{{ $movement->idtap }}</td>
                                            <td>{{ $movement->validity ?: '—' }}</td>
                                            <td>{{ $movement->denom_name ?: $movement->iddenom }}</td>
                                            <td class="text-right {{ $movement->signed_qty > 0 ? 'qty-positive' : ($movement->signed_qty < 0 ? 'qty-negative' : 'qty-neutral') }}">{{ $movement->signed_qty > 0 ? '+' : '' }}{{ number_format($movement->signed_qty) }}</td>
                                            <td class="text-right running-balance">{{ number_format($movement->remaining_balance) }}</td>
                                            <td title="{{ $movement->note }}">{{ \Illuminate\Support\Str::limit($movement->note ?: '—', 32) }}</td>
                                            <td>{{ strtoupper($movement->source_table) }} #{{ $movement->source_id }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="10" class="journey-empty"><i class="fas fa-route mb-2 d-block"></i>Tidak ada pergerakan stok pada filter ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($movements->hasPages())
                            <div class="journey-pagination">
                                <span class="journey-pagination-info">Menampilkan {{ $movements->firstItem() }}–{{ $movements->lastItem() }} dari {{ number_format($movements->total()) }} transaksi</span>
                                <div class="journey-pagination-actions">
                                    @if($movements->onFirstPage())<span class="btn btn-light disabled">Sebelumnya</span>@else<a class="btn btn-light" href="{{ $movements->previousPageUrl() }}">Sebelumnya</a>@endif
                                    @if($movements->hasMorePages())<a class="btn btn-primary" href="{{ $movements->nextPageUrl() }}">Berikutnya</a>@else<span class="btn btn-light disabled">Berikutnya</span>@endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('.journey-select').each(function() {
                $(this).select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $(this).data('placeholder'),
                    dropdownParent: $('.journey-filter')
                });
            });
        });
    </script>
@endpush
