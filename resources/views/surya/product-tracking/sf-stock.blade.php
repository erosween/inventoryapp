@extends('surya.layouts.workspace')

@section('title', 'Stock SF (SN)')
@section('active-page', 'product-tracking-sf-stock')
@section('description', 'Lihat sisa stok serial number per Sales Force.')
@section('content-class', 'pt-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">
<style>
    .sf-stock-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px}
    .sf-stock-stat{padding:15px 18px;border:1px solid #e2e8f0;border-radius:13px;background:#fff}
    .sf-stock-stat span{display:block;color:#64748b;font-size:12px;font-weight:700}
    .sf-stock-stat strong{display:block;margin-top:5px;color:#172033;font-size:23px;font-variant-numeric:tabular-nums}
    .sf-stock-filter{display:grid;grid-template-columns:repeat(4,minmax(130px,1fr));gap:11px;align-items:end}
    .sf-stock-filter label{display:grid;gap:6px;color:#475569;font-size:12px;font-weight:750}
    .sf-stock-filter select,.sf-stock-filter input{width:100%;min-height:41px;padding:8px 10px;border:1px solid #d7dfeb;border-radius:9px;background:#fff;color:#172033;font:inherit;font-size:13px}
    .sf-stock-filter .pt-button{min-height:41px}
    .sf-stock-section-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:13px}
    .sf-stock-section-heading h2{margin:0}
    .sf-stock-sn{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-variant-numeric:tabular-nums;letter-spacing:.02em;white-space:nowrap}
    .sf-stock-source{display:inline-block;padding:4px 8px;border-radius:999px;background:#eef2f7;color:#34445d;font-size:11px;font-weight:750;white-space:nowrap}
    .sf-stock-pager{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px;color:#64748b;font-size:13px}
    .sf-stock-pager a{padding:8px 12px;border:1px solid #d8e0ea;border-radius:8px;color:#34445d;text-decoration:none;font-weight:700}
    .sf-stock-pager a.disabled{pointer-events:none;opacity:.45}
    @media(max-width:760px){.sf-stock-filter{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:540px){.sf-stock-stats{grid-template-columns:1fr}.sf-stock-filter{grid-template-columns:1fr}.sf-stock-pager{align-items:stretch;flex-wrap:wrap}.sf-stock-pager>div{display:flex;justify-content:space-between;width:100%}.sf-stock-section-heading{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@section('content')
    <section class="pt-card">
        <div class="pt-result-head"><div><h2>Stock SF (SN)</h2><p class="pt-subtitle mb-0">Gabungan SN dari form SF Masuk, upload stok SN Sales, dan Add Label SN. SN ganda dihitung satu kali; koreksi Add Label menentukan SF yang ditampilkan. Daftar dimuat 100 SN per halaman.</p></div></div>
        <div class="sf-stock-stats">
            <div class="sf-stock-stat"><span>Sales Force</span><strong>{{ number_format($summaryTotals->sales_forces) }}</strong></div>
            <div class="sf-stock-stat"><span>Rentang SN unik</span><strong>{{ number_format($summaryTotals->ranges) }}</strong></div>
            <div class="sf-stock-stat"><span>Total SN unik</span><strong>{{ number_format($summaryTotals->serials) }}</strong></div>
        </div>
        <form method="get" action="{{ route('surya.product-tracking.sf-stock') }}" class="sf-stock-filter">
            <label>Sales Force<select name="sf"><option value="">Semua SF</option>@foreach($salesForces as $sf)<option value="{{ $sf->idsf }}" @selected($filters['sf']===$sf->idsf)>{{ $sf->namasf }} · {{ $sf->idsf }}</option>@endforeach</select></label>
            <label>Produk<select name="product"><option value="">Semua produk</option><option value="PV" @selected($filters['product']==='PV')>PV</option><option value="SA" @selected($filters['product']==='SA')>SA</option></select></label>
            <label>Denom<select name="iddenom"><option value="">Semua denom</option>@foreach($eligibleDenoms as $denom)<option value="{{ $denom->iddenom }}" @selected($filters['iddenom']===$denom->iddenom)>{{ $denom->denom }} · {{ $denom->iddenom }}</option>@endforeach</select></label>
            <label>Sumber<select name="source"><option value="">Semua sumber</option>@foreach(['SF Masuk','Upload Stok SN Sales','Add Label SN'] as $source)<option value="{{ $source }}" @selected($filters['source']===$source)>{{ $source }}</option>@endforeach</select></label>
            <label>Cari SN<input name="sn" value="{{ $filters['sn'] }}" inputmode="numeric" maxlength="16" placeholder="Masukkan SN"></label>
            <label>Urutkan<select name="sort"><option value="sf" @selected($filters['sort']==='sf')>Nama SF</option><option value="denom" @selected($filters['sort']==='denom')>Denom</option><option value="sn" @selected($filters['sort']==='sn')>Nomor SN</option><option value="source" @selected($filters['sort']==='source')>Sumber</option></select></label>
            <label>Arah<select name="direction"><option value="asc" @selected($filters['direction']==='asc')>Naik</option><option value="desc" @selected($filters['direction']==='desc')>Turun</option></select></label>
            <button class="pt-button primary" type="submit"><i class="fas fa-filter"></i> Terapkan</button>
        </form>
    </section>

    <section class="pt-card">
        <div class="sf-stock-section-heading"><div><h2>Ringkasan per Sales Force</h2><p class="pt-muted mb-0">Total di bawah sudah dihitung unik setelah rentang antar sumber digabung.</p></div></div>
        <div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>Sales Force</th><th>Kode SF</th><th>TAP</th><th>Rentang SN unik</th><th>Total SN unik</th></tr></thead><tbody>
            @forelse($summary as $row)<tr><td><strong>{{ $row->namasf }}</strong></td><td>{{ $row->sfcode }}</td><td>{{ $row->idtap ?: '—' }}</td><td>{{ number_format($row->range_count) }}</td><td><strong>{{ number_format($row->serial_count) }}</strong></td></tr>
            @empty<tr><td colspan="5" class="pt-empty">Tidak ada SN yang cocok dengan filter.</td></tr>@endforelse
        </tbody></table></div>
    </section>

    <section class="pt-card">
        <div class="sf-stock-section-heading"><div><h2>Rincian SN</h2><p class="pt-muted mb-0">{{ number_format($detailTotal) }} SN unik sesuai filter.</p></div></div>
        <div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>SN</th><th>Denom</th><th>Produk</th><th>Sales Force</th><th>Kode SF</th><th>TAP</th><th>Sumber label</th></tr></thead><tbody>
            @forelse($serials as $item)<tr><td class="sf-stock-sn">{{ $item->sn }}</td><td>{{ $item->denom }}</td><td>{{ $item->product }}</td><td>{{ $item->namasf }}</td><td>{{ $item->sfcode }}</td><td>{{ $item->idtap ?: '—' }}</td><td><span class="sf-stock-source">{{ $item->source }}</span></td></tr>
            @empty<tr><td colspan="7" class="pt-empty">Tidak ada SN pada filter ini.</td></tr>@endforelse
        </tbody></table></div>
        <div class="sf-stock-pager"><span>Menampilkan {{ number_format($serials->firstItem() ?? 0) }}–{{ number_format($serials->lastItem() ?? 0) }} dari {{ number_format($serials->total()) }} SN</span><div>
            @if($serials->onFirstPage())<a class="disabled" href="#">‹ Sebelumnya</a>@else<a href="{{ $serials->previousPageUrl() }}">‹ Sebelumnya</a>@endif
            <span>Halaman {{ $serials->currentPage() }} / {{ max(1,$serials->lastPage()) }}</span>
            @if($serials->hasMorePages())<a href="{{ $serials->nextPageUrl() }}">Berikutnya ›</a>@else<a class="disabled" href="#">Berikutnya ›</a>@endif
        </div></div>
    </section>
@endsection
