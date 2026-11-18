@extends('surya.layouts.workspace')

@section('title', 'Tracking SN')
@section('active-page', 'product-tracking-lookup')
@section('description', 'Lihat detail ST Nota Surya dan ST Digipos berdasarkan SN.')
@section('content-class', 'pt-page')

@push('styles')<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">@endpush

@section('content')
    <section class="pt-card"><h2>Cari Produk by SN</h2><p class="pt-subtitle">Masukkan SN yang ingin ditelusuri.</p><form method="get" class="pt-lookup"><input type="search" name="q" value="{{ $queryValue }}" inputmode="numeric" minlength="1" maxlength="24" placeholder="Serial Number (SN)" required><button class="pt-button primary" type="submit"><i class="fas fa-magnifying-glass"></i> Lacak</button></form></section>
    @if($queryValue!=='')
        <section class="pt-card"><div class="pt-result-head"><div><h2>ST Nota Surya</h2><span class="pt-muted">Penjualan yang dicatat melalui Product Tracking dan penjualan mobile yang disetujui.</span></div><span class="pt-chip">{{ $queryValue }}</span></div>
            <div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>Tanggal ST</th><th>SN</th><th>ID Outlet</th><th>Nama Outlet</th><th>Sales Force</th><th>Denom</th></tr></thead><tbody>
            @foreach($sales as $row)<tr><td>{{ \Carbon\Carbon::parse($row->sale_date)->format('d/m/Y') }}</td><td>SN · {{ $queryValue }}</td><td>{{ $row->id_outlet }}</td><td>{{ $row->outlet_name ?: 'Nama outlet tidak tersedia' }}</td><td>{{ $row->namasf ?: $row->sf }}</td><td>{{ $row->denom_name ?: $row->iddenom }}</td></tr>@endforeach
            @foreach($mobile as $row)<tr><td>{{ \Carbon\Carbon::parse($row->tgl)->format('d/m/Y') }}</td><td>SN · {{ $queryValue }}</td><td>{{ $row->id_outlet }}</td><td>{{ $row->nama_outlet ?: 'Nama outlet tidak tersedia' }}</td><td>{{ $row->namasf ?: $row->idsf }}</td><td>{{ $row->denom_name ?: $row->iddenom }}</td></tr>@endforeach
            @if($sales->isEmpty()&&$mobile->isEmpty())<tr><td colspan="6" class="pt-empty">Tidak ditemukan pada ST Nota Surya.</td></tr>@endif
            </tbody></table></div>
        </section>
        <section class="pt-card"><div class="pt-result-head"><div><h2>ST Digipos</h2><span class="pt-muted">Riwayat scan Digipos yang memuat SN ini.</span></div></div>
            <div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>Tanggal Scan</th><th>SN</th><th>ID Outlet</th><th>Nama Outlet</th><th>Sales Force</th><th>Denom</th></tr></thead><tbody>@forelse($scans as $row)<tr><td>{{ $row->scan_date?\Carbon\Carbon::parse($row->scan_date)->format('d/m/Y'):'—' }}</td><td>{{ $queryValue }}</td><td>{{ $row->id_outlet }}</td><td>{{ $row->nama_outlet ?: 'Nama outlet tidak tersedia' }}</td><td>{{ $row->namasf ?: ($row->sf ?: '—') }}</td><td>{{ $row->product_name ?: '—' }}</td></tr>@empty<tr><td colspan="6" class="pt-empty">Belum ditemukan pada hasil scan harian.</td></tr>@endforelse</tbody></table></div>
        </section>
        <section class="pt-card"><div class="pt-result-head"><div><h2>Warehouse</h2><span class="pt-muted">Snapshot warehouse terakhir yang berhasil diimpor.</span></div></div>
            <div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>SN</th><th>ID Outlet</th><th>Nama Outlet</th><th>Sales Force</th><th>Tanggal ST</th><th>Denom</th></tr></thead><tbody>@forelse($warehouse as $row)<tr><td>{{ $queryValue }}</td><td>{{ $row->id_outlet }}</td><td>{{ $row->nama_outlet ?: 'Nama outlet tidak tersedia' }}</td><td>{{ $row->namasf ?: ($row->sf ?: '—') }}</td><td>{{ $row->sold_at?\Carbon\Carbon::parse($row->sold_at)->format('d/m/Y'):'—' }}</td><td>{{ $row->product_name?:'—' }}</td></tr>@empty<tr><td colspan="6" class="pt-empty">Tidak ditemukan pada warehouse Digipos.</td></tr>@endforelse</tbody></table></div>
        </section>
    @endif
@endsection
