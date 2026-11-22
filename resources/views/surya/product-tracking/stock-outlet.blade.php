@extends('surya.layouts.workspace')

@section('title', 'Stock Outlet')
@section('active-page', 'product-tracking-stock-outlet')
@section('description', 'Pantau SN PV dan SA yang masuk ke outlet dan sudah redeem.')
@section('content-class', 'pt-page')

@push('styles')<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">@endpush

@section('content')
<section class="pt-card">
    <div class="pt-result-head"><div><h2>Stock Outlet</h2><p class="pt-subtitle mb-0">Stock Outlet = SN ST Surya dikurangi SN SO yang cocok di outlet yang sama.</p></div>
        @if(auth()->user()?->isSuperAdmin())<a class="pt-button primary" href="{{ route('surya.product-tracking.imports',['kind'=>$soKind]) }}"><i class="fas fa-upload"></i> Upload SO {{ $product }}</a>@endif
    </div>
    <nav class="pt-sold-tabs" aria-label="Kategori produk Stock Outlet">
        <a class="{{ $product==='PV'?'active':'' }}" href="{{ route('surya.product-tracking.stock-outlet',['product'=>'PV']) }}">PV</a>
        <a class="{{ $product==='SA'?'active':'' }}" href="{{ route('surya.product-tracking.stock-outlet',['product'=>'SA']) }}">SA</a>
    </nav>
    <div class="pt-table-wrap">
        <table class="pt-table">
            <thead><tr><th>Nama Outlet</th><th>ID Outlet</th><th>Kecamatan</th><th>TAP</th><th>Sales Force</th><th>Kategori Outlet</th><th>Stock Outlet</th><th>ST {{ $product }}</th><th>SO {{ $product }}</th><th>SF → Outlet</th><th>Outlet → Pelanggan</th></tr></thead>
            <tbody>
                @forelse($outlets as $outlet)
                    <tr>
                        <td><strong>{{ $outlet->nama_outlet ?: 'Outlet tanpa nama' }}</strong></td>
                        <td>{{ $outlet->id_outlet }}</td>
                        <td>{{ $outlet->kecamatan ?: '—' }}</td>
                        <td>{{ $outlet->tap ?: 'Tanpa TAP' }}</td>
                        <td>{{ $outlet->sf ?: '—' }}</td>
                        <td>{{ $outlet->kategori_outlet ?: 'Reguler' }}</td>
                        <td><strong>{{ number_format($outlet->remaining_qty) }}</strong></td>
                        <td>{{ number_format($outlet->st_qty) }}</td>
                        <td>{{ number_format($outlet->so_qty) }}</td>
                        <td>{{ $outlet->days_sf_to_outlet === null ? '—' : $outlet->days_sf_to_outlet.' hari' }}</td>
                        <td>{{ $outlet->days_outlet_to_customer === null ? '—' : $outlet->days_outlet_to_customer.' hari' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="pt-empty">Belum ada data ST atau upload SO untuk produk {{ $product }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($outlets->hasPages())
        <nav class="pt-pagination d-flex align-items-center justify-content-between" aria-label="Navigasi halaman outlet">
            <span class="pt-muted">{{ $outlets->firstItem() }}–{{ $outlets->lastItem() }} dari {{ $outlets->total() }} outlet · 20 per halaman</span>
            <div class="d-flex align-items-center" style="gap:8px">
                @if($outlets->onFirstPage())<span class="pt-button" aria-disabled="true">Sebelumnya</span>@else<a class="pt-button" href="{{ $outlets->previousPageUrl() }}">Sebelumnya</a>@endif
                <span class="pt-muted">Halaman {{ $outlets->currentPage() }}</span>
                @if($outlets->hasMorePages())<a class="pt-button" href="{{ $outlets->nextPageUrl() }}">Berikutnya</a>@else<span class="pt-button" aria-disabled="true">Berikutnya</span>@endif
            </div>
        </nav>
    @endif
</section>
<section class="pt-card">
    <h2>Arti insight durasi</h2>
    <p class="pt-subtitle mb-0"><strong>SF → Outlet</strong> dihitung dari tanggal SN masuk ke SF sampai tanggal ST Surya. Tanggal masuk SF diambil dari SF Masuk admin, atau tanggal upload Stok SN Sales jika SN tidak tercatat di SF Masuk. <strong>Outlet → Pelanggan</strong> dihitung dari tanggal ST Surya sampai tanggal SO. Durasi adalah rata-rata tertimbang berdasarkan jumlah SN yang cocok; — berarti belum ada pasangan SN bertanggal.</p>
</section>
@endsection
