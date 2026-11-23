@extends('surya.layouts.workspace')

@section('title', 'Stock Outlet')
@section('active-page', 'product-tracking-stock-outlet')
@section('description', 'Pantau SN PV dan SA yang masuk ke outlet dan sudah redeem.')
@section('content-class', 'pt-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">
<link rel="stylesheet" href="{{ asset('css/surya-outlet-columns.css') }}?v={{ filemtime(public_path('css/surya-outlet-columns.css')) }}">
@endpush

@section('content')
@php
    $filterColumns = [
        'id_outlet' => 'ID Outlet', 'nama_outlet' => 'Nama Outlet', 'kecamatan' => 'Kecamatan',
        'tap' => 'TAP', 'cluster' => 'Cluster', 'sf' => 'Sales Force', 'kategori_outlet' => 'Kategori Outlet',
        'remaining_qty_pv' => 'Stock Outlet PV', 'st_qty_pv' => 'ST PV', 'so_qty_pv' => 'SO PV',
        'days_sf_to_outlet_pv' => 'Rata-rata SF → Outlet PV (hari)', 'days_outlet_to_customer_pv' => 'Rata-rata Outlet → Pelanggan PV (hari)',
        'remaining_qty_sa' => 'Stock Outlet SA', 'st_qty_sa' => 'ST SA', 'so_qty_sa' => 'SO SA',
        'days_sf_to_outlet_sa' => 'Rata-rata SF → Outlet SA (hari)', 'days_outlet_to_customer_sa' => 'Rata-rata Outlet → Pelanggan SA (hari)',
    ];
    $durationColumns = ['days_sf_to_outlet_pv', 'days_outlet_to_customer_pv', 'days_sf_to_outlet_sa', 'days_outlet_to_customer_sa'];
    $metricColumns = [
        ['remaining_qty_pv', 'Stock Outlet PV', true], ['st_qty_pv', 'ST PV', false], ['so_qty_pv', 'SO PV', false],
        ['days_sf_to_outlet_pv', 'Rata-rata SF → Outlet PV (hari)', false], ['days_outlet_to_customer_pv', 'Rata-rata Outlet → Pelanggan PV (hari)', false],
        ['remaining_qty_sa', 'Stock Outlet SA', true], ['st_qty_sa', 'ST SA', false], ['so_qty_sa', 'SO SA', false],
        ['days_sf_to_outlet_sa', 'Rata-rata SF → Outlet SA (hari)', false], ['days_outlet_to_customer_sa', 'Rata-rata Outlet → Pelanggan SA (hari)', false],
    ];
@endphp
<section class="pt-card">
    <div class="pt-result-head">
        <div><h2>Stock Outlet</h2><p class="pt-subtitle mb-0">Pantau stok PV dan SA sekaligus. Stock outlet = SN ST Surya dikurangi SN SO yang cocok; durasi adalah rata-rata hari dari SN yang tanggalnya cocok.</p></div>
        <a class="pt-button primary" href="{{ route('surya.product-tracking.stock-outlet.download', array_merge(request()->query(), ['product' => 'PV'])) }}"><i class="fas fa-download"></i> Unduh</a>
    </div>
    <nav class="pt-sold-tabs pt-stock-outlet-views" aria-label="Tampilan Stock Outlet">
        <a class="{{ $viewMode==='outlet'?'active':'' }}" href="{{ route('surya.product-tracking.stock-outlet', ['product'=>'PV', 'view'=>'outlet']) }}">Per Outlet</a>
        <a class="{{ $viewMode==='tap'?'active':'' }}" href="{{ route('surya.product-tracking.stock-outlet', ['product'=>'PV', 'view'=>'tap']) }}">Rekap TAP</a>
    </nav>
    @if($viewMode === 'tap')
    <p class="pt-subtitle">Ringkasan per TAP. Rata-rata durasi dihitung berbobot berdasarkan jumlah SN yang memiliki tanggal lengkap.</p>
    <div class="pt-table-wrap">
        <table class="pt-table detail-table pt-stock-outlet-table">
            <thead><tr><th>TAP</th><th>Total Outlet</th>
                @foreach($metricColumns as [$column, $label, $bold])<th class="{{ in_array($column, $durationColumns, true) ? 'pt-duration-column' : '' }}">{{ $label }}</th>@endforeach
            </tr></thead>
            <tbody>
                @forelse($outlets as $summary)
                    <tr class="{{ $summary->row_type === 'grand-total' ? 'pt-grand-total-row' : ($summary->row_type === 'cluster-total' ? 'pt-cluster-summary-row' : '') }}">
                        <td><strong>{{ $summary->group_label }}</strong></td><td>{{ number_format($summary->outlet_count) }}</td>
                        @foreach($metricColumns as [$column, $label, $bold])
                            <td class="{{ in_array($column, $durationColumns, true) ? 'pt-duration-column' : '' }}">@if($bold)<strong>@endif{{ str_starts_with($column, 'days_') ? ($summary->{$column} === null ? '—' : $summary->{$column}.' hari') : number_format($summary->{$column} ?? 0) }}@if($bold)</strong>@endif</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="12" class="pt-empty">Belum ada data untuk rekap TAP.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @else
    <div class="pt-table-wrap">
        <table class="pt-table detail-table pt-stock-outlet-table pt-stock-outlet-detail">
            <thead><tr>
                @foreach($filterColumns as $column => $label)
                    <th class="{{ in_array($column, $durationColumns, true) ? 'pt-duration-column' : '' }}"><div class="tf-column-heading"><span>{{ $label }}@if(($stockTableOptions['sort'] ?? '') === $column)<i class="fas fa-arrow-{{ ($stockTableOptions['direction'] ?? 'asc') === 'desc' ? 'down' : 'up' }} tf-sort-indicator"></i>@endif</span><button type="button" class="tf-column-filter {{ array_key_exists($column, $stockTableOptions['columns'] ?? []) || array_key_exists($column, $stockTableOptions['exclude'] ?? []) ? 'is-active' : '' }}" data-column="{{ $column }}" data-label="{{ $label }}" aria-label="Filter dan urutkan {{ $label }}" aria-expanded="false"><i class="fas fa-filter"></i></button></div></th>
                @endforeach
            </tr></thead>
            <tbody>
                @forelse($outlets as $outlet)
                    <tr>
                        <td>{{ $outlet->id_outlet }}</td><td><strong>{{ $outlet->nama_outlet ?: 'Outlet tanpa nama' }}</strong></td>
                        <td>{{ $outlet->kecamatan ?: '—' }}</td><td>{{ $outlet->tap ?: 'Tanpa TAP' }}</td><td>{{ $outlet->cluster ?: '—' }}</td>
                        <td>{{ $outlet->sf ?: '—' }}</td><td>{{ $outlet->kategori_outlet ?: 'Reguler' }}</td>
                        @foreach($metricColumns as [$column, $label, $bold])
                            <td class="{{ in_array($column, $durationColumns, true) ? 'pt-duration-column' : '' }}">@if($bold)<strong>@endif{{ str_starts_with($column, 'days_') ? (($outlet->{$column} ?? null) === null ? '—' : $outlet->{$column}.' hari') : number_format($outlet->{$column} ?? 0) }}@if($bold)</strong>@endif</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="17" class="pt-empty">Belum ada data ST atau upload SO PV/SA.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
    <div class="pt-pagination d-flex align-items-center justify-content-between" aria-label="Navigasi halaman outlet">
        <span class="pt-muted">Halaman {{ $outlets->currentPage() }} · menampilkan {{ $outlets->firstItem() ?? 0 }}–{{ $outlets->lastItem() ?? 0 }} {{ $viewMode === 'outlet' ? 'outlet' : 'baris rekap' }}</span>
        <div class="d-flex align-items-center" style="gap:8px">
            @if($outlets->onFirstPage())<span class="pt-button" aria-disabled="true">Sebelumnya</span>@else<a class="pt-button" href="{{ $outlets->previousPageUrl() }}">Sebelumnya</a>@endif
            @if($outlets->hasMorePages())<a class="pt-button" href="{{ $outlets->nextPageUrl() }}">Berikutnya</a>@else<span class="pt-button" aria-disabled="true">Berikutnya</span>@endif
        </div>
    </div>
</section>
<section class="pt-card">
    <h2>Arti insight durasi</h2>
    <p class="pt-subtitle mb-0"><strong>0 hari</strong> berarti ada SN bertanggal yang cocok dan rata-ratanya dibulatkan menjadi nol. <strong>—</strong> berarti belum ada SN cocok yang punya tanggal lengkap untuk dihitung. <strong>SF → Outlet</strong> dihitung dari tanggal SN masuk SF sampai tanggal ST Surya; sumber tanggal SF adalah SF Masuk admin, lalu tanggal upload Stok SN Sales sebagai cadangan. <strong>Outlet → Pelanggan</strong> dihitung dari tanggal ST Surya sampai tanggal SO.</p>
</section>
<div id="tf-column-menu" class="tf-column-menu" role="dialog" aria-labelledby="tf-column-title" hidden data-standalone="true" data-values-url="{{ route('surya.product-tracking.stock-outlet.filter-values', ['product' => 'PV']) }}" data-selection="{{ json_encode($stockTableOptions['columns'] ?? new \stdClass()) }}" data-excluded="{{ json_encode($stockTableOptions['exclude'] ?? new \stdClass()) }}">
    <div class="tf-column-menu-body"><div class="tf-column-menu-heading"><strong id="tf-column-title">Filter kolom</strong><button type="button" data-filter-close aria-label="Tutup filter">×</button></div>
        <div class="tf-sort-menu"><button type="button" data-sort-direction="asc"><i class="fas fa-arrow-up"></i> A ke Z / terkecil</button><button type="button" data-sort-direction="desc"><i class="fas fa-arrow-down"></i> Z ke A / terbesar</button><button type="button" data-sort-clear>Urutan default</button></div>
        <input class="input" type="search" data-filter-search placeholder="Cari nilai…" aria-label="Cari nilai filter"><div class="tf-column-menu-tools"><button type="button" data-filter-all>Pilih semua</button><button type="button" data-filter-none>Kosongkan pilihan</button></div><div data-filter-values class="tf-column-values"></div><p data-filter-message role="status" aria-live="polite"></p><button type="button" data-filter-more hidden>Muat lainnya</button>
    </div><div class="tf-column-menu-actions"><button type="button" class="btn btn-secondary" data-filter-reset>Hapus filter</button><button type="button" class="btn" data-filter-apply>Terapkan</button></div>
</div>
<script src="{{ asset('js/surya-taskforce-columns.js') }}?v={{ filemtime(public_path('js/surya-taskforce-columns.js')) }}" defer></script>
@endsection
