@extends('surya.layouts.workspace')

@section('title', 'Tracking SN')
@section('active-page', 'product-tracking-lookup')
@section('description', 'Lihat detail ST Nota Surya dan ST Digipos berdasarkan SN.')
@section('content-class', 'pt-page')

@push('styles')<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">@endpush

@section('content')
    <section class="pt-card pt-lookup-card">
        <div class="pt-lookup-heading"><span class="pt-lookup-icon"><i class="fas fa-barcode"></i></span><div><h2>Cari Produk by SN</h2><p class="pt-subtitle mb-0">Masukkan serial number untuk melihat riwayat penjualan dan scan.</p></div></div>
        <form method="get" class="pt-lookup">
            <input type="search" name="q" value="{{ $queryValue }}" inputmode="numeric" minlength="1" maxlength="24" placeholder="Masukkan Serial Number (SN)" aria-label="Serial Number" required>
            <button class="pt-button primary" type="submit"><i class="fas fa-magnifying-glass"></i><span>Lacak</span></button>
        </form>
    </section>

    @if($queryValue !== '')
        <div class="pt-tracking-results">
            <section class="pt-card pt-detail-card">
                <div class="pt-result-head"><div><h2>ST Nota Surya</h2><span class="pt-muted">Penjualan yang dicatat melalui Product Tracking dan penjualan Mobile SURYA yang disetujui.</span></div><span class="pt-chip">{{ $queryValue }}</span></div>
                @php $notaRows = $sales->map(fn ($row) => ['tanggal' => $row->sale_date, 'sn' => $queryValue, 'outlet_id' => $row->id_outlet, 'outlet_name' => $row->outlet_name, 'sales_force' => $row->namasf ?: $row->sf, 'denom' => $row->denom_name ?: $row->iddenom])->merge($mobile->map(fn ($row) => ['tanggal' => $row->tgl, 'sn' => $queryValue, 'outlet_id' => $row->id_outlet, 'outlet_name' => $row->nama_outlet, 'sales_force' => $row->namasf ?: $row->idsf, 'denom' => $row->denom_name ?: $row->iddenom])); @endphp
                @forelse($notaRows as $row)
                    <dl class="pt-detail-list">
                        <div><dt>Tanggal ST</dt><dd>{{ \Carbon\Carbon::parse($row['tanggal'])->format('d/m/Y') }}</dd></div>
                        <div><dt>SN</dt><dd>{{ $row['sn'] }}</dd></div>
                        <div><dt>ID Outlet</dt><dd>{{ $row['outlet_id'] }}</dd></div>
                        <div><dt>Nama Outlet</dt><dd>{{ $row['outlet_name'] ?: 'Nama outlet tidak tersedia' }}</dd></div>
                        <div><dt>Sales Force</dt><dd>{{ $row['sales_force'] }}</dd></div>
                        <div><dt>Denom</dt><dd>{{ $row['denom'] }}</dd></div>
                    </dl>
                @empty
                    <div class="pt-empty">Tidak ditemukan pada ST Nota Surya.</div>
                @endforelse
            </section>

            <section class="pt-card pt-detail-card">
                <div class="pt-result-head"><div><h2>ST Digipos</h2><span class="pt-muted">Riwayat scan Digipos yang memuat SN ini.</span></div></div>
                @forelse($scans as $row)
                    <dl class="pt-detail-list">
                        <div><dt>Tanggal Scan</dt><dd>{{ $row->scan_date ? \Carbon\Carbon::parse($row->scan_date)->format('d/m/Y') : '—' }}</dd></div>
                        <div><dt>SN</dt><dd>{{ $queryValue }}</dd></div>
                        <div><dt>ID Outlet</dt><dd>{{ $row->id_outlet }}</dd></div>
                        <div><dt>Nama Outlet</dt><dd>{{ $row->nama_outlet ?: 'Nama outlet tidak tersedia' }}</dd></div>
                        <div><dt>Sales Force</dt><dd>{{ $row->namasf ?: ($row->sf ?: '—') }}</dd></div>
                        <div><dt>Denom</dt><dd>{{ $row->product_name ?: '—' }}</dd></div>
                    </dl>
                @empty
                    <div class="pt-empty">Belum ditemukan pada hasil scan harian.</div>
                @endforelse
            </section>
        </div>
    @endif
@endsection
