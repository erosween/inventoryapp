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
        'remaining_qty' => 'Stock Outlet', 'st_qty' => 'ST '.$product, 'so_qty' => 'SO '.$product,
        'days_sf_to_outlet' => 'Rata-rata SF → Outlet (hari)', 'days_outlet_to_customer' => 'Rata-rata Outlet → Pelanggan (hari)',
    ];
@endphp
<section class="pt-card">
    <div class="pt-result-head">
    <div><h2>Stock Outlet</h2><p class="pt-subtitle mb-0">Stock Outlet = SN ST Surya dikurangi SN SO yang cocok. Durasi adalah rata-rata hari untuk SN yang tanggalnya cocok.</p></div>
        <a class="pt-button primary" href="{{ route('surya.product-tracking.stock-outlet.download', request()->query()) }}"><i class="fas fa-download"></i> Unduh</a>
    </div>
    <nav class="pt-sold-tabs" aria-label="Kategori produk Stock Outlet">
        <a class="{{ $product==='PV'?'active':'' }}" href="{{ route('surya.product-tracking.stock-outlet',['product'=>'PV']) }}">PV</a>
        <a class="{{ $product==='SA'?'active':'' }}" href="{{ route('surya.product-tracking.stock-outlet',['product'=>'SA']) }}">SA</a>
    </nav>
    <nav class="pt-sold-tabs pt-stock-outlet-views" aria-label="Tampilan ringkasan Stock Outlet">
        <a class="{{ $viewMode==='outlet'?'active':'' }}" href="{{ route('surya.product-tracking.stock-outlet', ['product'=>$product, 'view'=>'outlet']) }}">Per Outlet</a>
        <a class="{{ $viewMode==='tap'?'active':'' }}" href="{{ route('surya.product-tracking.stock-outlet', ['product'=>$product, 'view'=>'tap']) }}">Rekap TAP</a>
    </nav>
    @if($viewMode !== 'outlet')
    <p class="pt-subtitle">Ringkasan {{ $viewMode === 'tap' ? 'per TAP' : 'per Cluster' }}. Rata-rata durasi dihitung berbobot berdasarkan jumlah SN yang memiliki tanggal lengkap.</p>
    <div class="pt-table-wrap">
        <table class="pt-table detail-table pt-stock-outlet-table">
            <thead><tr><th>TAP</th><th>Total Outlet</th><th>Stock Outlet</th><th>ST {{ $product }}</th><th>SO {{ $product }}</th><th class="pt-duration-column">Rata-rata SF → Outlet (hari)</th><th class="pt-duration-column">Rata-rata Outlet → Pelanggan (hari)</th></tr></thead>
            <tbody>
                @forelse($outlets as $summary)
                    <tr class="{{ $summary->row_type === 'grand-total' ? 'pt-grand-total-row' : ($summary->row_type === 'cluster-total' ? 'pt-cluster-summary-row' : '') }}"><td><strong>{{ $summary->group_label }}</strong></td><td>{{ number_format($summary->outlet_count) }}</td><td><strong>{{ number_format($summary->remaining_qty) }}</strong></td><td>{{ number_format($summary->st_qty) }}</td><td>{{ number_format($summary->so_qty) }}</td><td class="pt-duration-column">{{ $summary->days_sf_to_outlet === null ? '—' : $summary->days_sf_to_outlet.' hari' }}</td><td class="pt-duration-column">{{ $summary->days_outlet_to_customer === null ? '—' : $summary->days_outlet_to_customer.' hari' }}</td></tr>
                @empty
                    <tr><td colspan="7" class="pt-empty">Belum ada data untuk rekap TAP.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @else
    <div class="pt-table-wrap">
        <table class="pt-table detail-table pt-stock-outlet-table">
            <thead><tr>
                @foreach($filterColumns as $column => $label)
                    <th class="{{ in_array($column, ['days_sf_to_outlet', 'days_outlet_to_customer'], true) ? 'pt-duration-column' : '' }}"><div class="tf-column-heading"><span>{{ $label }}@if(($stockTableOptions['sort'] ?? '') === $column)<i class="fas fa-arrow-{{ ($stockTableOptions['direction'] ?? 'asc') === 'desc' ? 'down' : 'up' }} tf-sort-indicator"></i>@endif</span><button type="button" class="tf-column-filter {{ array_key_exists($column, $stockTableOptions['columns'] ?? []) || array_key_exists($column, $stockTableOptions['exclude'] ?? []) ? 'is-active' : '' }}" data-column="{{ $column }}" data-label="{{ $label }}" aria-label="Filter dan urutkan {{ $label }}" aria-expanded="false"><i class="fas fa-filter"></i></button></div></th>
                @endforeach
            </tr></thead>
            <tbody>
                @forelse($outlets as $outlet)
                    <tr>
                        <td>{{ $outlet->id_outlet }}</td>
                        <td><strong>{{ $outlet->nama_outlet ?: 'Outlet tanpa nama' }}</strong></td>
                        <td>{{ $outlet->kecamatan ?: '—' }}</td>
                        <td>{{ $outlet->tap ?: 'Tanpa TAP' }}</td>
                        <td>{{ $outlet->cluster ?: '—' }}</td>
                        <td>{{ $outlet->sf ?: '—' }}</td>
                        <td>{{ $outlet->kategori_outlet ?: 'Reguler' }}</td>
                        <td><strong>{{ number_format($outlet->remaining_qty) }}</strong></td>
                        <td>{{ number_format($outlet->st_qty) }}</td>
                        <td>{{ number_format($outlet->so_qty) }}</td>
                        <td class="pt-duration-column">{{ $outlet->days_sf_to_outlet === null ? '—' : $outlet->days_sf_to_outlet.' hari' }}</td>
                        <td class="pt-duration-column">{{ $outlet->days_outlet_to_customer === null ? '—' : $outlet->days_outlet_to_customer.' hari' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="pt-empty">Belum ada data ST atau upload SO untuk produk {{ $product }}.</td></tr>
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
<div id="tf-column-menu" class="tf-column-menu" role="dialog" aria-labelledby="tf-column-title" hidden data-standalone="true" data-values-url="{{ route('surya.product-tracking.stock-outlet.filter-values', ['product' => $product]) }}" data-selection="{{ json_encode($stockTableOptions['columns'] ?? new \stdClass()) }}" data-excluded="{{ json_encode($stockTableOptions['exclude'] ?? new \stdClass()) }}">
    <div class="tf-column-menu-body"><div class="tf-column-menu-heading"><strong id="tf-column-title">Filter kolom</strong><button type="button" data-filter-close aria-label="Tutup filter">×</button></div>
        <div class="tf-sort-menu"><button type="button" data-sort-direction="asc"><i class="fas fa-arrow-up"></i> A ke Z / terkecil</button><button type="button" data-sort-direction="desc"><i class="fas fa-arrow-down"></i> Z ke A / terbesar</button><button type="button" data-sort-clear>Urutan default</button></div>
        <input class="input" type="search" data-filter-search placeholder="Cari nilai…" aria-label="Cari nilai filter"><div class="tf-column-menu-tools"><button type="button" data-filter-all>Pilih semua</button><button type="button" data-filter-none>Kosongkan pilihan</button></div><div data-filter-values class="tf-column-values"></div><p data-filter-message role="status" aria-live="polite"></p><button type="button" data-filter-more hidden>Muat lainnya</button>
    </div><div class="tf-column-menu-actions"><button type="button" class="btn btn-secondary" data-filter-reset>Hapus filter</button><button type="button" class="btn" data-filter-apply>Terapkan</button></div>
</div>
<script src="{{ asset('js/surya-taskforce-columns.js') }}?v={{ filemtime(public_path('js/surya-taskforce-columns.js')) }}" defer></script>
@endsection
