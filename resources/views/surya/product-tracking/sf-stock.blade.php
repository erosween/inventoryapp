@extends('surya.layouts.workspace')

@section('title', 'Stock SF (SN)')
@section('active-page', 'product-tracking-sf-stock')
@section('description', 'Lihat sisa stok serial number per Sales Force dan gudang TAP.')
@section('content-class', 'pt-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">
<style>
    .sf-stock-section-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:13px}
    .sf-stock-section-heading h2{margin:0}
    .sf-stock-inline-detail>td{padding:16px!important;background:#f5f8fc!important}
    .sf-stock-inline-detail .pt-table-wrap{margin-top:10px}
    .sf-stock-sn{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-variant-numeric:tabular-nums;letter-spacing:.02em;white-space:nowrap}
    .sf-stock-source{display:inline-block;padding:4px 8px;border-radius:999px;background:#eef2f7;color:#34445d;font-size:11px;font-weight:750;white-space:nowrap}
    .sf-stock-pager{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px;color:#64748b;font-size:13px}
    .sf-stock-pager a{padding:8px 12px;border:1px solid #d8e0ea;border-radius:8px;color:#34445d;text-decoration:none;font-weight:700}
    .sf-stock-pager a.disabled{pointer-events:none;opacity:.45}
    .sf-stock-table thead th:hover{background:#26354a!important;color:#fff!important}
    .sf-stock-table thead th a{color:inherit;text-decoration:none}
    .sf-stock-table thead th a:hover{color:inherit;text-decoration:none}
    .sf-stock-table .sf-stock-filter-row th{padding:7px;background:#f8fafc!important}
    .sf-stock-table .sf-stock-filter-row input,.sf-stock-table .sf-stock-filter-row select{width:100%;min-width:70px;padding:6px 8px;border:1px solid #d7dfeb;border-radius:7px;background:#fff;color:#172033;font:inherit;font-size:12px}
    .sf-stock-row-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .sf-stock-sn-button{display:inline-flex;align-items:center;gap:8px;white-space:nowrap}
    @media(max-width:540px){.sf-stock-pager{align-items:stretch;flex-wrap:wrap}.sf-stock-pager>div{display:flex;justify-content:space-between;width:100%}.sf-stock-section-heading{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@section('content')
    <section class="pt-card">
        <div class="sf-stock-section-heading"><div><h2>Ringkasan per SF & Gudang</h2><p class="pt-muted mb-0">SA dan PV dihitung terpisah. SN dari DO ditampilkan di bawah penerima dengan label Stok Gudang.</p></div></div>
        <form id="summary-filter-form" method="get" action="{{ route('surya.product-tracking.sf-stock') }}">
            <input type="hidden" name="summary_sort" value="{{ $filters['summary_sort'] }}"><input type="hidden" name="summary_direction" value="{{ $filters['summary_direction'] }}">
            <input type="hidden" name="detail_sf" value="{{ $detailSf }}"><input type="hidden" name="detail_sort" value="{{ $filters['detail_sort'] }}"><input type="hidden" name="detail_direction" value="{{ $filters['detail_direction'] }}">
            <input type="hidden" name="detail_sn" value="{{ $filters['detail_sn'] }}"><input type="hidden" name="detail_denom" value="{{ $filters['detail_denom'] }}"><input type="hidden" name="detail_product" value="{{ $filters['detail_product'] }}"><input type="hidden" name="detail_source" value="{{ $filters['detail_source'] }}">
        </form>
        <div class="pt-table-wrap"><table class="pt-table sf-stock-table"><thead>
            <tr>
                @foreach(['sf'=>'SF / Penerima DO','tap'=>'TAP','sa'=>'Total SN SA','pv'=>'Total SN PV'] as $key=>$label)
                    @php($nextDirection = $filters['summary_sort']===$key && $filters['summary_direction']==='asc' ? 'desc' : 'asc')
                    <th><a href="{{ request()->fullUrlWithQuery(['summary_sort'=>$key,'summary_direction'=>$nextDirection,'page'=>1]) }}">{{ $label }} @if($filters['summary_sort']===$key)<i class="fas fa-arrow-{{ $filters['summary_direction']==='desc'?'down':'up' }}"></i>@endif</a></th>
                @endforeach
                <th>SN</th><th>Unduh</th>
            </tr>
            <tr class="sf-stock-filter-row">
                <th><input form="summary-filter-form" name="summary_sf" value="{{ $filters['summary_sf'] }}" placeholder="Cari SF/penerima DO…" aria-label="Filter SF atau penerima DO" onchange="this.form.requestSubmit()"></th>
                <th><input form="summary-filter-form" name="summary_tap" value="{{ $filters['summary_tap'] }}" placeholder="Cari TAP…" aria-label="Filter TAP" onchange="this.form.requestSubmit()"></th>
                <th><input form="summary-filter-form" name="summary_sa" value="{{ $filters['summary_sa'] }}" inputmode="numeric" placeholder="SA" aria-label="Filter total SA" onchange="this.form.requestSubmit()"></th>
                <th><input form="summary-filter-form" name="summary_pv" value="{{ $filters['summary_pv'] }}" inputmode="numeric" placeholder="PV" aria-label="Filter total PV" onchange="this.form.requestSubmit()"></th>
                <th></th><th></th>
            </tr>
        </thead><tbody>
            @forelse($summaryPaginator as $row)
                <tr id="sf-row-{{ $row->sfcode }}">
                    <td><strong>{{ $row->namasf }}</strong>@if(str_starts_with($row->sfcode, 'GUDANG:'))<small style="display:block;color:#64748b">Stok Gudang</small>@else<small style="display:block;color:#64748b">{{ $row->sfcode }}</small>@endif</td>
                    <td>{{ $row->idtap ?: '—' }}</td>
                    <td><strong>{{ number_format($row->sa_count) }}</strong></td>
                    <td><strong>{{ number_format($row->pv_count) }}</strong></td>
                    <td class="sf-stock-row-actions">
                        @if($detailSf === $row->sfcode)
                            <a class="pt-button sf-stock-sn-button" href="{{ request()->fullUrlWithQuery(['detail_sf' => null, 'sn_page' => null]) }}#sf-row-{{ $row->sfcode }}">Tutup</a>
                        @else
                            <a class="pt-button sf-stock-sn-button" href="{{ request()->fullUrlWithQuery(['detail_sf' => $row->sfcode, 'sn_page' => 1]) }}#sf-row-{{ $row->sfcode }}"><i class="fas fa-barcode"></i> Lihat SN</a>
                        @endif
                    </td>
                    <td><a class="pt-button sf-stock-sn-button" href="{{ request()->fullUrlWithQuery(['download_sf'=>$row->sfcode,'detail_sf'=>$row->sfcode]) }}"><i class="fas fa-download"></i> Unduh</a></td>
                </tr>
                @if($detailSf === $row->sfcode)
                    <tr class="sf-stock-inline-detail"><td colspan="6">
                        <div class="sf-stock-section-heading"><div><strong>Rincian SN · {{ $row->namasf }}</strong><div class="pt-muted">{{ number_format($detailTotal) }} SN · {{ number_format($perPage) }} per halaman</div></div></div>
                        <form id="detail-filter-form" method="get" action="{{ route('surya.product-tracking.sf-stock') }}">
                            <input type="hidden" name="detail_sf" value="{{ $row->sfcode }}"><input type="hidden" name="detail_sort" value="{{ $filters['detail_sort'] }}"><input type="hidden" name="detail_direction" value="{{ $filters['detail_direction'] }}">
                            <input type="hidden" name="summary_sf" value="{{ $filters['summary_sf'] }}"><input type="hidden" name="summary_tap" value="{{ $filters['summary_tap'] }}"><input type="hidden" name="summary_sa" value="{{ $filters['summary_sa'] }}"><input type="hidden" name="summary_pv" value="{{ $filters['summary_pv'] }}"><input type="hidden" name="summary_sort" value="{{ $filters['summary_sort'] }}"><input type="hidden" name="summary_direction" value="{{ $filters['summary_direction'] }}"><input type="hidden" name="page" value="{{ $summaryPaginator->currentPage() }}">
                        </form>
                        <div class="pt-table-wrap"><table class="pt-table sf-stock-table"><thead><tr>
                            @foreach(['sn'=>'SN','denom'=>'Denom','product'=>'Produk','source'=>'Sumber'] as $key=>$label)
                                @php($nextDirection = $filters['detail_sort']===$key && $filters['detail_direction']==='asc' ? 'desc' : 'asc')
                                <th><a href="{{ request()->fullUrlWithQuery(['detail_sf'=>$row->sfcode,'detail_sort'=>$key,'detail_direction'=>$nextDirection,'sn_page'=>1]) }}">{{ $label }} @if($filters['detail_sort']===$key)<i class="fas fa-arrow-{{ $filters['detail_direction']==='desc'?'down':'up' }}"></i>@endif</a></th>
                            @endforeach
                        </tr><tr class="sf-stock-filter-row">
                            <th><input form="detail-filter-form" name="detail_sn" value="{{ $filters['detail_sn'] }}" inputmode="numeric" maxlength="16" placeholder="Cari SN…" aria-label="Filter SN" onchange="this.form.requestSubmit()"></th>
                            <th><input form="detail-filter-form" name="detail_denom" value="{{ $filters['detail_denom'] }}" placeholder="Cari denom…" aria-label="Filter denom" onchange="this.form.requestSubmit()"></th>
                            <th><select form="detail-filter-form" name="detail_product" aria-label="Filter produk" onchange="this.form.requestSubmit()"><option value="">Semua</option><option value="SA" @selected($filters['detail_product']==='SA')>SA</option><option value="PV" @selected($filters['detail_product']==='PV')>PV</option></select></th>
                            <th><select form="detail-filter-form" name="detail_source" aria-label="Filter sumber" onchange="this.form.requestSubmit()"><option value="">Semua</option>@foreach(['SF Masuk','Stok Gudang','Upload Stok SN Sales','Add Label SN','Retur SF'] as $source)<option value="{{ $source }}" @selected($filters['detail_source']===$source)>{{ $source }}</option>@endforeach</select></th>
                        </tr></thead><tbody>
                            @forelse($serials as $item)<tr><td class="sf-stock-sn">{{ $item->sn }}</td><td>{{ $item->denom }}</td><td>{{ $item->product }}</td><td><span class="sf-stock-source">{{ $item->source }}</span></td></tr>
                            @empty<tr><td colspan="4" class="pt-empty">Tidak ada SN untuk filter ini.</td></tr>@endforelse
                        </tbody></table></div>
                        <div class="sf-stock-pager"><span>Menampilkan {{ number_format($serials->firstItem() ?? 0) }}–{{ number_format($serials->lastItem() ?? 0) }} dari {{ number_format($serials->total()) }} SN</span><div>
                            @if($serials->onFirstPage())<a class="disabled" href="#">‹ Sebelumnya</a>@else<a href="{{ $serials->previousPageUrl() }}#sf-row-{{ $row->sfcode }}">‹ Sebelumnya</a>@endif
                            <span>Halaman {{ $serials->currentPage() }} / {{ max(1,$serials->lastPage()) }}</span>
                            @if($serials->hasMorePages())<a href="{{ $serials->nextPageUrl() }}#sf-row-{{ $row->sfcode }}">Berikutnya ›</a>@else<a class="disabled" href="#">Berikutnya ›</a>@endif
                        </div></div>
                    </td></tr>
                @endif
            @empty<tr><td colspan="6" class="pt-empty">Tidak ada Sales Force yang cocok dengan filter.</td></tr>@endforelse
        </tbody></table></div>
        <div class="sf-stock-pager"><span>Menampilkan {{ number_format($summaryPaginator->firstItem() ?? 0) }}–{{ number_format($summaryPaginator->lastItem() ?? 0) }} dari {{ number_format($summaryPaginator->total()) }} baris</span><div>
            @if($summaryPaginator->onFirstPage())<a class="disabled" href="#">‹ Sebelumnya</a>@else<a href="{{ $summaryPaginator->previousPageUrl() }}">‹ Sebelumnya</a>@endif
            <span>Halaman {{ $summaryPaginator->currentPage() }} / {{ max(1,$summaryPaginator->lastPage()) }}</span>
            @if($summaryPaginator->hasMorePages())<a href="{{ $summaryPaginator->nextPageUrl() }}">Berikutnya ›</a>@else<a class="disabled" href="#">Berikutnya ›</a>@endif
        </div></div>
    </section>
@endsection
