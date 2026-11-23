@extends('surya.layouts.workspace')

@section('title', 'Add Label SN')
@section('active-page', 'product-tracking-sf-stock-lookup')
@section('description', 'Tambahkan label kepemilikan untuk rentang serial number.')
@section('content-class', 'pt-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">
@if($tab === 'add')
<style>
    .pt-search-picker { position:relative; }
    .pt-search-picker input { width:100%;min-height:44px;padding:10px 38px 10px 12px;border:1px solid #d7dfeb;border-radius:10px;background:#fff;color:#172033;font:inherit;font-size:14px; }
    .pt-search-picker input:focus { outline:0;border-color:#c8102e;box-shadow:0 0 0 3px rgba(200,16,46,.1); }
    .pt-search-picker:after { content:'⌄';position:absolute;right:13px;top:8px;color:#64748b;font-size:18px;pointer-events:none; }
    .pt-search-options { position:absolute;z-index:1080;top:calc(100% + 5px);left:0;right:0;max-height:250px;overflow:auto;padding:5px;border:1px solid #d7dfeb;border-radius:10px;background:#fff;box-shadow:0 12px 28px rgba(15,23,42,.16); }
    .pt-search-options[hidden] { display:none; }
    .pt-search-option { display:block;width:100%;padding:10px;border:0;border-radius:7px;background:#fff;color:#334155;text-align:left;font:inherit;font-size:13px; }
    .pt-search-option:hover,.pt-search-option:focus { outline:0;background:#fff1f2;color:#be123c; }
    .pt-search-empty { padding:10px;color:#64748b;font-size:13px; }
    .pt-native-select-hidden { position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important; }
    .pt-manual-form { grid-template-columns:repeat(4,minmax(0,1fr));align-items:end;gap:12px; }
    .pt-manual-form .pt-actions { grid-column:1/-1;align-items:center;justify-content:flex-start;gap:14px;margin:2px 0 0; }
    .pt-manual-form .pt-actions .pt-muted { margin-left:auto;font-size:12px; }
    .pt-manual-actions { display:flex;align-items:center;gap:6px; }
    .pt-manual-actions form { margin:0; }
    .pt-manual-action { min-height:34px;padding:6px 9px;border:1px solid #dbe2ed;border-radius:8px;background:#fff;color:#344054;font-size:12px;font-weight:750;text-decoration:none; }
    .pt-manual-action.danger { border-color:#fecdd3;color:#be123c; }
    @media(max-width:900px){.pt-manual-form{grid-template-columns:repeat(2,minmax(0,1fr))}.pt-manual-form .pt-actions{grid-column:1/-1}}
    @media(max-width:640px){.pt-manual-form{grid-template-columns:minmax(0,1fr)}.pt-manual-form .pt-actions{grid-column:1;align-items:stretch;flex-direction:column}.pt-manual-form .pt-actions .pt-muted{margin-left:0}.pt-manual-actions{flex-wrap:wrap}}
</style>
@endif
@endpush

@section('content')
        <section class="pt-card">
            <div class="pt-lookup-heading">
                <span class="pt-lookup-icon"><i class="fas fa-barcode"></i></span>
                <div><h2>{{ $editingManual ? 'Edit Label SN' : 'Add Label SN' }}</h2></div>
            </div>
            <p class="pt-muted mb-3">Tambahkan label pemilik dan rentang SN untuk ditampilkan pada Tracking SN.</p>
            @if($errors->any())<div class="pt-empty mb-3" style="padding:12px 16px;border-radius:10px;background:#fff0f1;color:#c8102e;text-align:left">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ $editingManual ? route('surya.product-tracking.sf-stock-lookup.update', $editingManual->id) : route('surya.product-tracking.sf-stock-lookup.store') }}" class="pt-grid pt-manual-form" data-loading-message="Menyimpan rentang SN…">
                @csrf
                @if($editingManual) @method('PATCH') @endif
                <label class="pt-field"><span>Sales Force</span><select class="pt-search-select" name="sfcode" data-placeholder="Cari kode atau nama SF…" required><option value=""></option>@foreach($salesForces as $sf)<option value="{{ $sf->idsf }}" @selected(old('sfcode', $editingManual->sfcode ?? '') === $sf->idsf)>{{ $sf->idsf }} · {{ $sf->namasf }} · {{ $sf->idtap }}</option>@endforeach</select></label>
                <label class="pt-field"><span>Denom</span><select class="pt-search-select" name="iddenom" data-placeholder="Cari kode atau nama denom…" required><option value=""></option>@foreach($eligibleDenoms as $denom)<option value="{{ $denom->iddenom }}" @selected(old('iddenom', $editingManual->iddenom ?? '') === $denom->iddenom)>{{ $denom->iddenom }} · {{ $denom->denom }} · {{ \App\Support\DenomGrouping::infer($denom)['product'] }}</option>@endforeach</select></label>
                <label class="pt-field"><span>SN Awal</span><input name="serial_start" type="text" inputmode="numeric" maxlength="16" value="{{ old('serial_start', $editingManual->serial_start ?? '') }}" placeholder="SN awal" required></label>
                <label class="pt-field"><span>SN Akhir</span><input name="serial_end" type="text" inputmode="numeric" maxlength="16" value="{{ old('serial_end', $editingManual->serial_end ?? '') }}" placeholder="SN akhir" required></label>
                <div class="pt-actions"><button class="pt-button primary" type="submit"><i class="fas {{ $editingManual ? 'fa-check' : 'fa-plus' }}"></i> {{ $editingManual ? 'Simpan Perubahan' : 'Tambah Label SN' }}</button>@if($editingManual)<a class="pt-button" href="{{ route('surya.product-tracking.sf-stock-lookup', ['tab'=>'add']) }}">Batal</a>@endif<span class="pt-muted">PV 12 digit · SA 15 digit (atau 16 diawali 0) · Maks. 2 juta SN</span></div>
            </form>
        </section>

        @if($recentManual)
            <section class="pt-card">
                <div class="pt-result-head"><div><h2>Riwayat input manual</h2><p class="pt-subtitle mb-0">{{ $recentManual->total() }} rentang tercatat</p></div></div>
                <div class="pt-table-wrap"><table class="pt-table">
                    <thead><tr><th>SF</th><th>NAMA SF</th><th>TAP</th><th>DENOM</th><th>SN AWAL</th><th>SN AKHIR</th><th>QTY RENTANG</th><th>DIINPUT OLEH</th><th>WAKTU</th><th>AKSI</th><th>KETERANGAN</th></tr></thead>
                    <tbody>@forelse($recentManual as $row)<tr>
                        <td><strong>{{ $row->sfcode }}</strong></td><td>{{ $row->namasf ?: '—' }}</td><td>{{ $row->idtap ?: '—' }}</td>
                        <td>{{ $row->denom ?: $row->iddenom }}</td><td>{{ $row->serial_start }}</td><td>{{ $row->serial_end }}</td>
                        <td>{{ number_format($row->qty) }}</td><td>{{ $row->username }}</td>
                        <td>{{ \Carbon\Carbon::parse($row->created_at, 'Asia/Jakarta')->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('j M Y H:i') }} WIB</td>
                        <td><div class="pt-manual-actions"><a class="pt-manual-action" href="{{ route('surya.product-tracking.sf-stock-lookup', ['tab'=>'add','edit'=>$row->id,'manual_page'=>$recentManual->currentPage()]) }}"><i class="fas fa-pen"></i> Edit</a><form method="post" action="{{ route('surya.product-tracking.sf-stock-lookup.delete',$row->id) }}" onsubmit="return confirm('Hapus rentang SN {{ $row->serial_start }}–{{ $row->serial_end }}?')">@csrf @method('DELETE')<button class="pt-manual-action danger" type="submit"><i class="fas fa-trash"></i> Hapus</button></form></div></td><td>{{ $row->keterangan ?: '—' }}</td>
                    </tr>@empty<tr><td colspan="11" class="pt-empty">Belum ada rentang SN manual.</td></tr>@endforelse</tbody>
                </table></div>
                <nav class="pt-outlet-pagination" aria-label="Pagination riwayat input manual">
                    <span class="pt-outlet-pagination-summary">Menampilkan {{ number_format($recentManual->firstItem() ?? 0) }}–{{ number_format($recentManual->lastItem() ?? 0) }} dari {{ number_format($recentManual->total()) }} rentang</span>
                    <div class="pt-outlet-pagination-controls">
                        @if($recentManual->onFirstPage())<span class="pt-page-button disabled" aria-disabled="true">‹ Sebelumnya</span>@else<a class="pt-page-button" href="{{ $recentManual->previousPageUrl() }}">‹ Sebelumnya</a>@endif
                        <span class="pt-page-count">Halaman {{ $recentManual->currentPage() }} dari {{ max(1, $recentManual->lastPage()) }}</span>
                        @if($recentManual->hasMorePages())<a class="pt-page-button primary" href="{{ $recentManual->nextPageUrl() }}">Berikutnya ›</a>@else<span class="pt-page-button disabled" aria-disabled="true">Berikutnya ›</span>@endif
                    </div>
                </nav>
            </section>
        @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.pt-search-select').forEach((select) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'pt-search-picker';
        const input = document.createElement('input');
        input.type = 'search';
        input.placeholder = select.dataset.placeholder || 'Ketik untuk mencari…';
        input.autocomplete = 'off';
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.required = select.required;
        input.setAttribute('aria-label', select.closest('.pt-field')?.querySelector('span')?.textContent || 'Cari pilihan');
        const options = document.createElement('div');
        options.className = 'pt-search-options';
        options.setAttribute('role', 'listbox');
        options.hidden = true;
        const selected = select.selectedOptions[0];
        if (selected?.value) input.value = selected.textContent.trim();
        select.required = false;
        select.classList.add('pt-native-select-hidden');
        wrapper.append(input, options);
        select.after(wrapper);

        const close = () => { options.hidden = true; input.setAttribute('aria-expanded', 'false'); };
        const choose = (option) => {
            select.value = option.value;
            input.value = option.textContent.trim();
            input.setCustomValidity('');
            select.dispatchEvent(new Event('change', { bubbles: true }));
            close();
        };
        const render = (searchValue = input.value) => {
            const query = searchValue.trim().toLocaleLowerCase();
            const matches = Array.from(select.options).filter(option => option.value && option.textContent.toLocaleLowerCase().includes(query));
            options.replaceChildren();
            if (!matches.length) {
                const empty = document.createElement('div');
                empty.className = 'pt-search-empty';
                empty.textContent = 'Tidak ditemukan';
                options.append(empty);
            } else {
                matches.slice(0, 80).forEach(option => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'pt-search-option';
                    button.setAttribute('role', 'option');
                    button.textContent = option.textContent.trim();
                    button.addEventListener('click', () => choose(option));
                    options.append(button);
                });
                if (matches.length > 80) {
                    const more = document.createElement('div');
                    more.className = 'pt-search-empty';
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
        input.addEventListener('input', () => {
            select.value = '';
            input.setCustomValidity('Pilih salah satu hasil pencarian.');
            render();
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') close();
            if (event.key === 'ArrowDown' && !options.hidden) {
                event.preventDefault(); options.querySelector('.pt-search-option')?.focus();
            }
            if (event.key === 'Enter' && !options.hidden) {
                const first = options.querySelector('.pt-search-option');
                if (first) { event.preventDefault(); first.click(); }
            }
        });
        options.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') { close(); input.focus(); }
            if (event.key === 'ArrowDown') { event.preventDefault(); event.target.nextElementSibling?.focus(); }
            if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (event.target.previousElementSibling?.matches('.pt-search-option')) event.target.previousElementSibling.focus();
                else input.focus();
            }
        });
        document.addEventListener('click', (event) => { if (!wrapper.contains(event.target)) close(); });
    });
    document.querySelectorAll('form.pt-lookup[data-loading-message]').forEach((form) => {
        form.addEventListener('submit', () => {
            if (form.dataset.submitting === 'true') return;
            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');
            const button = form.querySelector('button[type="submit"]');
            const status = form.querySelector('.pt-loading-status');
            if (button) { button.disabled = true; button.innerHTML = '<span class="pt-spinner" aria-hidden="true"></span><span>Mencari…</span>'; }
            if (status) { status.textContent = form.dataset.loadingMessage; status.hidden = false; }
        });
    });
});
</script>
@endpush
