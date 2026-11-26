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
    .sf-stock-search-picker{position:relative;min-width:0}
    .sf-stock-search-picker input{padding-right:32px}
    .sf-stock-search-picker:after{content:'⌄';position:absolute;right:12px;top:7px;color:#64748b;font-size:17px;pointer-events:none}
    .sf-stock-search-options{position:absolute;z-index:1080;top:calc(100% + 5px);left:0;right:0;max-height:250px;overflow:auto;padding:5px;border:1px solid #d7dfeb;border-radius:10px;background:#fff;box-shadow:0 12px 28px rgba(15,23,42,.16)}
    .sf-stock-search-options[hidden]{display:none}
    .sf-stock-search-option{display:block;width:100%;padding:9px;border:0;border-radius:7px;background:#fff;color:#334155;text-align:left;font:inherit;font-size:13px}
    .sf-stock-search-option:hover,.sf-stock-search-option:focus{outline:0;background:#fff1f2;color:#be123c}
    .sf-stock-search-empty{padding:9px;color:#64748b;font-size:12px}
    .sf-stock-native-select-hidden{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}
    .sf-stock-section-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:13px}
    .sf-stock-section-heading h2{margin:0}
    .sf-stock-inline-detail>td{padding:16px!important;background:#f5f8fc!important}
    .sf-stock-inline-detail .pt-table-wrap{margin-top:10px}
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
            <label>Sales Force<select name="sf" class="sf-stock-search-select" data-placeholder="Cari nama atau kode SF…"><option value="">Semua SF</option>@foreach($salesForces as $sf)<option value="{{ $sf->idsf }}" @selected($filters['sf']===$sf->idsf)>{{ $sf->namasf }} · {{ $sf->idsf }}</option>@endforeach</select></label>
            <label>Produk<select name="product"><option value="">Semua produk</option><option value="PV" @selected($filters['product']==='PV')>PV</option><option value="SA" @selected($filters['product']==='SA')>SA</option></select></label>
            <label>Denom<select name="iddenom" class="sf-stock-search-select" data-placeholder="Cari nama atau kode denom…"><option value="">Semua denom</option>@foreach($eligibleDenoms as $denom)<option value="{{ $denom->iddenom }}" @selected($filters['iddenom']===$denom->iddenom)>{{ $denom->denom }} · {{ $denom->iddenom }}</option>@endforeach</select></label>
            <label>Sumber<select name="source"><option value="">Semua sumber</option>@foreach(['SF Masuk','Upload Stok SN Sales','Add Label SN'] as $source)<option value="{{ $source }}" @selected($filters['source']===$source)>{{ $source }}</option>@endforeach</select></label>
            <label>Cari SN<input name="sn" value="{{ $filters['sn'] }}" inputmode="numeric" maxlength="16" placeholder="Masukkan SN"></label>
            <label>Urutkan ringkasan<select name="sort"><option value="sf" @selected($filters['sort']==='sf')>Sales Force</option><option value="tap" @selected($filters['sort']==='tap')>TAP</option><option value="total" @selected($filters['sort']==='total')>Total SN</option></select></label>
            <label>Arah<select name="direction"><option value="asc" @selected($filters['direction']==='asc')>Naik</option><option value="desc" @selected($filters['direction']==='desc')>Turun</option></select></label>
            <button class="pt-button primary" type="submit"><i class="fas fa-filter"></i> Terapkan</button>
        </form>
    </section>

    <section class="pt-card">
        <div class="sf-stock-section-heading"><div><h2>Ringkasan per Sales Force</h2><p class="pt-muted mb-0">Total SN unik per SF. Buka kolom SN untuk melihat rincian; menampilkan 10 Sales Force per halaman.</p></div></div>
        <div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>Sales Force</th><th>TAP</th><th>Total SN</th><th>SN</th></tr></thead><tbody>
            @forelse($summaryPaginator as $row)
                <tr id="sf-row-{{ $row->sfcode }}">
                    <td><strong>{{ $row->namasf }}</strong><small style="display:block;color:#64748b">{{ $row->sfcode }}</small></td>
                    <td>{{ $row->idtap ?: '—' }}</td>
                    <td><strong>{{ number_format($row->serial_count) }}</strong></td>
                    <td>
                        @if($detailSf === $row->sfcode)
                            <a class="pt-button" href="{{ request()->fullUrlWithQuery(['detail_sf' => null, 'sn_page' => null]) }}#sf-row-{{ $row->sfcode }}">Tutup</a>
                        @else
                            <a class="pt-button primary" href="{{ request()->fullUrlWithQuery(['detail_sf' => $row->sfcode, 'sn_page' => 1]) }}#sf-row-{{ $row->sfcode }}">Lihat SN</a>
                        @endif
                    </td>
                </tr>
                @if($detailSf === $row->sfcode)
                    <tr class="sf-stock-inline-detail"><td colspan="4">
                        <div class="sf-stock-section-heading"><div><strong>Rincian SN · {{ $row->namasf }}</strong><div class="pt-muted">{{ number_format($detailTotal) }} SN · {{ number_format($perPage) }} per halaman</div></div></div>
                        <div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>SN</th><th>Denom</th><th>Produk</th><th>Sumber</th></tr></thead><tbody>
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
            @empty<tr><td colspan="4" class="pt-empty">Tidak ada Sales Force yang cocok dengan filter.</td></tr>@endforelse
        </tbody></table></div>
        <div class="sf-stock-pager"><span>Menampilkan {{ number_format($summaryPaginator->firstItem() ?? 0) }}–{{ number_format($summaryPaginator->lastItem() ?? 0) }} dari {{ number_format($summaryPaginator->total()) }} Sales Force</span><div>
            @if($summaryPaginator->onFirstPage())<a class="disabled" href="#">‹ Sebelumnya</a>@else<a href="{{ $summaryPaginator->previousPageUrl() }}">‹ Sebelumnya</a>@endif
            <span>Halaman {{ $summaryPaginator->currentPage() }} / {{ max(1,$summaryPaginator->lastPage()) }}</span>
            @if($summaryPaginator->hasMorePages())<a href="{{ $summaryPaginator->nextPageUrl() }}">Berikutnya ›</a>@else<a class="disabled" href="#">Berikutnya ›</a>@endif
        </div></div>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.sf-stock-search-select').forEach((select) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'sf-stock-search-picker';
        const input = document.createElement('input');
        input.type = 'search';
        input.placeholder = select.dataset.placeholder || 'Ketik untuk mencari…';
        input.autocomplete = 'off';
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-label', select.closest('label')?.firstChild?.textContent?.trim() || 'Cari pilihan');
        const options = document.createElement('div');
        options.className = 'sf-stock-search-options';
        options.setAttribute('role', 'listbox');
        options.hidden = true;
        const selected = select.selectedOptions[0];
        if (selected?.value) input.value = selected.textContent.trim();
        select.classList.add('sf-stock-native-select-hidden');
        wrapper.append(input, options);
        select.after(wrapper);

        const close = () => { options.hidden = true; input.setAttribute('aria-expanded', 'false'); };
        const render = (term = input.value) => {
            const query = term.trim().toLocaleLowerCase();
            const matches = Array.from(select.options).filter(option => option.textContent.toLocaleLowerCase().includes(query));
            options.replaceChildren();
            if (!matches.length) {
                const empty = document.createElement('div');
                empty.className = 'sf-stock-search-empty';
                empty.textContent = 'Tidak ditemukan';
                options.append(empty);
            } else {
                matches.slice(0, 80).forEach(option => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'sf-stock-search-option';
                    button.setAttribute('role', 'option');
                    button.textContent = option.textContent.trim();
                    button.addEventListener('click', () => {
                        select.value = option.value;
                        input.value = option.textContent.trim();
                        input.setCustomValidity('');
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        close();
                    });
                    options.append(button);
                });
                if (matches.length > 80) {
                    const more = document.createElement('div');
                    more.className = 'sf-stock-search-empty';
                    more.textContent = 'Ketik lagi agar hasil lebih spesifik.';
                    options.append(more);
                }
            }
            options.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        };
        input.addEventListener('focus', () => {
            if (select.value) { input.select(); render(''); }
            else render();
        });
        input.addEventListener('input', () => { select.value = ''; input.setCustomValidity(''); render(); });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') close();
            if (event.key === 'ArrowDown' && !options.hidden) { event.preventDefault(); options.querySelector('.sf-stock-search-option')?.focus(); }
            if (event.key === 'Enter' && !options.hidden) {
                const first = options.querySelector('.sf-stock-search-option');
                if (first) { event.preventDefault(); first.click(); }
            }
        });
        options.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') { close(); input.focus(); }
            if (event.key === 'ArrowDown') { event.preventDefault(); event.target.nextElementSibling?.focus(); }
            if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (event.target.previousElementSibling?.matches('.sf-stock-search-option')) event.target.previousElementSibling.focus();
                else input.focus();
            }
        });
        select.closest('form')?.addEventListener('submit', (event) => {
            if (input.value.trim() && !select.value) {
                event.preventDefault();
                input.setCustomValidity('Pilih salah satu hasil pencarian.');
                input.reportValidity();
            }
        });
        document.addEventListener('click', (event) => { if (!wrapper.contains(event.target)) close(); });
    });
});
</script>
@endpush
