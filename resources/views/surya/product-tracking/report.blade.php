@extends('surya.layouts.workspace')

@section('title', 'Rekonsiliasi Product Tracking')
@section('active-page', 'product-tracking-report')
@section('description', 'Cocokkan identitas penjualan Surya dengan warehouse dan hasil scan Digipos.')
@section('content-class', 'pt-page')

@push('styles')<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">@endpush

@section('content')
    <form method="get" class="pt-card pt-grid">
        <div class="pt-field"><label>Dari tanggal ST</label><input type="date" name="date_from" value="{{ $from }}" max="{{ now('Asia/Jakarta')->toDateString() }}"></div>
        <div class="pt-field"><label>Sampai tanggal ST</label><input type="date" name="date_to" value="{{ $to }}" max="{{ now('Asia/Jakarta')->toDateString() }}"></div>
        <div class="pt-field"><label>TAP</label><select name="tap"><option value="">Semua TAP</option>@foreach($taps as $tap)<option value="{{ $tap }}" @selected(($filters['tap']??'')===$tap)>{{ $tap }}</option>@endforeach</select></div>
        <div class="pt-field"><label>Sales Force</label><select name="sf"><option value="">Semua SF</option>@foreach($sfs as $sf)<option value="{{ $sf->sf }}" @selected(($filters['sf']??'')===$sf->sf)>{{ $sf->namasf ?: $sf->sf }}</option>@endforeach</select></div>
        <div class="pt-field"><label>Baris per halaman</label><select name="per_page">@foreach([25,50,100] as $n)<option value="{{ $n }}" @selected(($filters['per_page']??'25')==$n)>{{ $n }}</option>@endforeach</select></div>
        <div class="pt-field d-flex align-items-end"><button class="pt-button primary" type="submit"><i class="fas fa-filter"></i> Tampilkan</button></div>
    </form>
    @if(!$warehouseId)<div class="pt-alert error">Warehouse Digipos belum diunggah. Input Surya tetap tercatat, tetapi status pencocokan warehouse belum tersedia.</div>@endif
    <section class="pt-card"><div class="pt-result-head"><div><h2>Hasil pencocokan</h2><span class="pt-muted">{{ number_format($sales->total()) }} transaksi · Scan dihitung pada tanggal {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}–{{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</span></div><a class="pt-button" href="{{ route('surya.product-tracking.tracking') }}">Cari SN / Nomor</a></div>
        <div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>Tanggal ST</th><th>Outlet</th><th>TAP</th><th>SF</th><th>Identitas Surya</th><th>Qty</th><th>Warehouse</th><th>Scan Digipos</th></tr></thead><tbody>
        @forelse($sales as $row)
            @php($warehouseOk=(int)$row->warehouse_matches>0)
            @php($warehouseOutletOk=(int)$row->warehouse_outlet_matches>0)
            @php($scanOk=(int)$row->scan_matches>0)
            @php($scanOutletOk=(int)$row->scan_outlet_matches>0)
            <tr>
                <td>{{ \Carbon\Carbon::parse($row->sale_date)->format('d/m/Y') }}</td>
                <td>{{ $row->outlet_name?:$row->id_outlet }}<small class="d-block pt-muted">{{ $row->id_outlet }}</small></td>
                <td>{{ $row->tap??'—' }}</td><td>{{ $row->namasf ?: $row->sf }}</td>
                <td><span class="pt-chip">{{ strtoupper($row->identifier_type) }}</span> {{ $row->serial_start }}@if($row->serial_end!==$row->serial_start) – {{ $row->serial_end }}@endif</td>
                <td>{{ number_format($row->qty) }}</td>
                <td>@if($warehouseOk)<span class="pt-chip good">SN cocok · {{ number_format($row->warehouse_matches) }}</span><small class="d-block pt-muted">{{ $warehouseOutletOk?'Outlet cocok':'Outlet berbeda' }}</small>@else<span class="pt-chip bad">Belum ditemukan</span>@endif</td>
                <td>@if($scanOk)<span class="pt-chip good">Terpindai · {{ number_format($row->scan_matches) }}</span><small class="d-block pt-muted">{{ $scanOutletOk?'Outlet cocok':'Outlet berbeda' }}</small>@else<span class="pt-chip">Belum ada scan</span>@endif</td>
            </tr>
        @empty<tr><td colspan="8" class="pt-empty">Belum ada input penjualan untuk filter ini.</td></tr>@endforelse
        </tbody></table></div>
        <div class="pt-pagination">{{ $sales->links() }}</div>
    </section>
@endsection
