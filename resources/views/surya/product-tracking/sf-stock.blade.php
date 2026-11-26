@extends('surya.layouts.workspace')

@section('title', 'Stock SF (SN)')
@section('active-page', 'product-tracking-sf-stock')
@section('description', 'Lihat stok serial number per Sales Force.')
@section('content-class', 'pt-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">
<style>
    .sf-stock-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px}
    .sf-stock-stat{padding:16px 18px;border:1px solid #e2e8f0;border-radius:13px;background:#fff}
    .sf-stock-stat span{display:block;color:#64748b;font-size:12px;font-weight:700}
    .sf-stock-stat strong{display:block;margin-top:5px;color:#172033;font-size:24px;font-variant-numeric:tabular-nums}
    .sf-stock-filter{display:grid;grid-template-columns:minmax(220px,1.5fr) repeat(3,minmax(140px,1fr)) minmax(170px,1fr) auto;gap:12px;align-items:end}
    .sf-stock-filter label{display:grid;gap:6px;color:#475569;font-size:12px;font-weight:750}
    .sf-stock-filter select,.sf-stock-filter input{width:100%;min-height:42px;padding:8px 10px;border:1px solid #d7dfeb;border-radius:9px;background:#fff;color:#172033;font:inherit;font-size:13px}
    .sf-stock-filter .pt-button{min-height:42px}
    .sf-stock-section-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
    .sf-stock-section-heading h2{margin:0}
    .sf-stock-code{font-weight:800;color:#24344d;white-space:nowrap}
    .sf-stock-sn{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-variant-numeric:tabular-nums;letter-spacing:.02em}
    .sf-stock-pager{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px;color:#64748b;font-size:13px}
    .sf-stock-pager a{padding:8px 12px;border:1px solid #d8e0ea;border-radius:8px;color:#34445d;text-decoration:none;font-weight:700}
    .sf-stock-pager a.disabled{pointer-events:none;opacity:.45}
    @media(max-width:850px){.sf-stock-filter{grid-template-columns:repeat(2,minmax(0,1fr))}.sf-stock-filter label:first-child{grid-column:1/-1}}
    @media(max-width:560px){.sf-stock-stats{grid-template-columns:1fr}.sf-stock-stat{padding:12px 14px}.sf-stock-stat strong{font-size:21px}.sf-stock-filter{grid-template-columns:1fr}.sf-stock-filter label:first-child{grid-column:auto}.sf-stock-filter .pt-button{width:100%}.sf-stock-section-heading{align-items:flex-start;flex-direction:column}.sf-stock-pager{align-items:stretch;flex-wrap:wrap}.sf-stock-pager > div{display:flex;justify-content:space-between;width:100%}}
</style>
@endpush

@section('content')
    <section class="pt-card">
        <div class="pt-result-head">
            <div><h2>Stock SF (SN)</h2><p class="pt-subtitle mb-0">Daftar SN dari snapshot Stok SN Sales yang dipilih. Rincian SN ditampilkan 100 nomor per halaman.</p></div>
        </div>
        @if(!$selectedUpload)
            <div class="pt-empty">Belum ada snapshot stok SN Sales. Unggah datanya melalui submenu Upload terlebih dahulu.</div>
        @else
            <div class="sf-stock-stats">
                <div class="sf-stock-stat"><span>Sales Force</span><strong>{{ number_format($summaryTotals->sales_forces) }}</strong></div>
                <div class="sf-stock-stat"><span>Rentang SN</span><strong>{{ number_format($summaryTotals->ranges) }}</strong></div>
                <div class="sf-stock-stat"><span>Total SN</span><strong>{{ number_format($summaryTotals->serials) }}</strong></div>
            </div>
            <form method="get" action="{{ route('surya.product-tracking.sf-stock') }}" class="sf-stock-filter mb-3">
                <label>Snapshot stok
                    <select name="upload" onchange="this.form.submit()">
                        @foreach($uploads as $upload)
                            <option value="{{ $upload->id }}" @selected($selectedUploadId === (int)$upload->id)>{{ $upload->filename }} · {{ \Carbon\Carbon::parse($upload->created_at,'Asia/Jakarta')->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Produk
                    <select name="product"><option value="">Semua</option><option value="PV" @selected($filters['product']==='PV')>PV</option><option value="SA" @selected($filters['product']==='SA')>SA</option></select>
                </label>
                <label>Sales Force
                    <select name="sf"><option value="">Semua SF</option>@foreach($salesForces as $sf)<option value="{{ $sf->idsf }}" @selected($filters['sf']===$sf->idsf)>{{ $sf->namasf }} · {{ $sf->idsf }}</option>@endforeach</select>
                </label>
                <label>Denom
                    <select name="iddenom"><option value="">Semua denom</option>@foreach($eligibleDenoms as $denom)<option value="{{ $denom->iddenom }}" @selected($filters['iddenom']===$denom->iddenom)>{{ $denom->denom }} · {{ $denom->iddenom }}</option>@endforeach</select>
                </label>
                <label>Cari SN
                    <input name="sn" value="{{ $filters['sn'] }}" inputmode="numeric" maxlength="16" placeholder="Masukkan SN">
                </label>
                <button class="pt-button primary" type="submit"><i class="fas fa-filter"></i> Terapkan</button>
            </form>
            <p class="pt-muted">Snapshot: <strong>{{ $selectedUpload->filename }}</strong> · Diunggah {{ \Carbon\Carbon::parse($selectedUpload->created_at,'Asia/Jakarta')->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
        @endif
    </section>

    @if($selectedUpload)
        @if($filters['sf']==='')
            <section class="pt-card">
                <div class="sf-stock-section-heading"><div><h2>Ringkasan stok per Sales Force</h2><p class="pt-muted mb-0">Pilih nama SF untuk membuka daftar SN.</p></div></div>
                <div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>Sales Force</th><th>Kode SF</th><th>TAP</th><th>Rentang SN</th><th>Total SN</th><th></th></tr></thead><tbody>
                    @forelse($summary as $row)
                        <tr><td><strong>{{ $row->namasf ?: $row->sfcode }}</strong></td><td class="sf-stock-code">{{ $row->sfcode }}</td><td>{{ $row->idtap ?: '—' }}</td><td>{{ number_format($row->range_count) }}</td><td><strong>{{ number_format($row->serial_count) }}</strong></td><td><a class="pt-button" href="{{ route('surya.product-tracking.sf-stock', array_merge(request()->query(), ['sf'=>$row->sfcode])) }}">Lihat SN</a></td></tr>
                    @empty<tr><td colspan="6" class="pt-empty">Tidak ada stok SN pada filter ini.</td></tr>@endforelse
                </tbody></table></div>
            </section>
        @else
            <section class="pt-card">
                <div class="sf-stock-section-heading"><div><h2>Daftar SN · {{ $selectedSf->namasf ?: $selectedSf->idsf }}</h2><p class="pt-muted mb-0">{{ $selectedSf->idsf }} · {{ $selectedSf->idtap ?: 'Tanpa TAP' }} · {{ number_format($detailTotal) }} SN</p></div><a class="pt-button" href="{{ route('surya.product-tracking.sf-stock', request()->except(['sf','page'])) }}"><i class="fas fa-arrow-left"></i> Ringkasan SF</a></div>
                <div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>SN</th><th>Denom</th><th>Kode Denom</th><th>Sales Force</th><th>TAP</th></tr></thead><tbody>
                    @forelse($serials as $item)
                        <tr><td class="sf-stock-sn">{{ $item->sn }}</td><td>{{ $item->denom ?: '—' }}</td><td>{{ $item->iddenom }}</td><td>{{ $selectedSf->namasf ?: $selectedSf->idsf }}</td><td>{{ $selectedSf->idtap ?: '—' }}</td></tr>
                    @empty<tr><td colspan="5" class="pt-empty">Tidak ada SN pada filter ini.</td></tr>@endforelse
                </tbody></table></div>
                @if($serials)
                    <div class="sf-stock-pager"><span>Menampilkan {{ number_format($serials->firstItem() ?? 0) }}–{{ number_format($serials->lastItem() ?? 0) }} dari {{ number_format($serials->total()) }} SN</span><div><a class="{{ $serials->onFirstPage()?'disabled':'' }}" href="{{ $serials->previousPageUrl() ?: '#' }}">‹ Sebelumnya</a> <a class="{{ $serials->hasMorePages()?'':'disabled' }}" href="{{ $serials->nextPageUrl() ?: '#' }}">Berikutnya ›</a></div></div>
                @endif
            </section>
        @endif
    @endif
@endsection
