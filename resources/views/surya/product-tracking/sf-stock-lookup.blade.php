@extends('surya.layouts.workspace')

@section('title', 'Cek SN SF')
@section('active-page', 'product-tracking-sf-stock-lookup')
@section('description', 'Cari Sales Force pemilik rentang serial number.')
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
</style>
@endif
@endpush

@section('content')
    <nav class="pt-nav" aria-label="Cek SN SF">
        <a class="{{ $tab === 'tracking' ? 'active' : '' }}" href="{{ route('surya.product-tracking.sf-stock-lookup', ['tab' => 'tracking']) }}"><i class="fas fa-magnifying-glass"></i> Tracking</a>
        @if(auth()->user()?->isSuperAdmin())
            <a class="{{ $tab === 'add' ? 'active' : '' }}" href="{{ route('surya.product-tracking.sf-stock-lookup', ['tab' => 'add']) }}"><i class="fas fa-plus"></i> Add SN</a>
        @endif
    </nav>

    @if($tab === 'tracking')
        <section class="pt-card pt-lookup-card">
            <div class="pt-lookup-heading">
                <span class="pt-lookup-icon"><i class="fas fa-user-tag"></i></span>
                <div><h2>Cek SN SF</h2><p class="pt-subtitle mb-0">Cari SF dan denom yang mencatat rentang SN.</p></div>
            </div>
            <form method="get" class="pt-lookup" data-loading-message="Memeriksa rentang SN SF…">
                <input type="hidden" name="tab" value="tracking">
                <input type="search" name="q" value="{{ $queryValue }}" inputmode="numeric" maxlength="16" placeholder="Masukkan SN PV 12 digit atau SA 15/16 digit" aria-label="Serial Number" required>
                <button class="pt-button primary" type="submit"><i class="fas fa-magnifying-glass"></i><span>Cek SN</span></button>
                <span class="pt-loading-status" role="status" aria-live="polite" hidden></span>
            </form>
            <p class="pt-muted mt-3 mb-0">Mencakup rentang SF Masuk admin, Upload Stok SN Sales, dan SN yang ditambahkan manual. Hasil menunjukkan SN terdaftar, bukan sisa stok terkini.</p>
        </section>

        @if($error)
            <section class="pt-card"><div class="pt-empty text-danger">{{ $error }}</div></section>
        @elseif($queryValue !== '')
            <section class="pt-card">
                <div class="pt-result-head">
                    <div><h2>Hasil pencarian</h2><p class="pt-subtitle mb-0">SN {{ $queryValue }} · {{ $matches->count() }} catatan rentang cocok</p></div>
                    @if($matches->count() > 1)<span class="pt-chip">Periksa kemungkinan duplikasi</span>@endif
                </div>
                @if($matches->isEmpty())
                    <div class="pt-empty">SN ini tidak ditemukan pada rentang SF Masuk admin, Upload Stok SN Sales, maupun input manual.</div>
                @else
                    <div class="pt-table-wrap">
                        <table class="pt-table">
                            <thead><tr><th>SF CODE</th><th>NAMA SALES FORCE</th><th>TAP</th><th>CLUSTER</th><th>DENOM</th><th>SUMBER</th><th>RENTANG TERCATAT</th><th>TANGGAL</th></tr></thead>
                            <tbody>
                                @foreach($matches as $row)
                                    <tr>
                                        <td><strong>{{ $row->sfcode }}</strong></td>
                                        <td>{{ $row->namasf ?: 'Nama SF tidak ditemukan' }}</td>
                                        <td>{{ $row->tap ?: '—' }}</td>
                                        <td>{{ $row->cluster ?: '—' }}</td>
                                        <td>{{ $row->denom ?: $row->iddenom }}</td>
                                        <td><span class="pt-chip">{{ $row->source }}</span></td>
                                        <td>{{ $row->serial_range }}</td>
                                        <td>{{ $row->date ? \Carbon\Carbon::parse($row->date)->locale('id')->translatedFormat('j M Y') : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    @else
        <section class="pt-card">
            <div class="pt-lookup-heading">
                <span class="pt-lookup-icon"><i class="fas fa-barcode"></i></span>
                <div><h2>Daftarkan Rentang SN ke SF</h2><p class="pt-subtitle mb-0">Catat SN yang terlewat supaya dapat divalidasi saat SF melakukan penjualan.</p></div>
            </div>
            <div class="pt-empty mb-3" style="padding:12px 16px;border-radius:10px;background:#fff7ed;color:#9a3412;text-align:left">
                Ini hanya mendaftarkan rentang SN ke SF dan denom. Tidak menambah stok, tidak mencatat SF Masuk, dan tidak mengubah saldo stok.
            </div>
            @if(session('success'))<div class="pt-chip good mb-3">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="pt-empty mb-3" style="padding:12px 16px;border-radius:10px;background:#fff0f1;color:#c8102e;text-align:left">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ route('surya.product-tracking.sf-stock-lookup.store') }}" class="pt-grid" data-loading-message="Mencatat rentang SN untuk SF…">
                @csrf
                <label class="pt-field"><span>Sales Force</span><select class="pt-search-select" name="sfcode" data-placeholder="Cari kode atau nama SF…" required><option value=""></option>@foreach($salesForces as $sf)<option value="{{ $sf->idsf }}" @selected(old('sfcode') === $sf->idsf)>{{ $sf->idsf }} · {{ $sf->namasf }} · {{ $sf->idtap }}</option>@endforeach</select></label>
                <label class="pt-field"><span>Denom</span><select class="pt-search-select" name="iddenom" data-placeholder="Cari kode atau nama denom…" required><option value=""></option>@foreach($eligibleDenoms as $denom)<option value="{{ $denom->iddenom }}" @selected(old('iddenom') === $denom->iddenom)>{{ $denom->iddenom }} · {{ $denom->denom }} · {{ \App\Support\DenomGrouping::infer($denom)['product'] }}</option>@endforeach</select></label>
                <label class="pt-field"><span>SN Awal</span><input name="serial_start" type="text" inputmode="numeric" maxlength="16" value="{{ old('serial_start') }}" placeholder="SN awal" required></label>
                <label class="pt-field"><span>SN Akhir</span><input name="serial_end" type="text" inputmode="numeric" maxlength="16" value="{{ old('serial_end') }}" placeholder="SN akhir" required></label>
                <div class="pt-actions"><button class="pt-button primary" type="submit"><i class="fas fa-plus"></i> Daftarkan Rentang SN</button><span class="pt-muted">PV: 12 digit · SA: 15 digit atau 16 digit dengan 0 di depan · Maksimal 2.000.000 SN.</span></div>
            </form>
        </section>

        @if($recentManual)
            <section class="pt-card">
                <div class="pt-result-head"><div><h2>Riwayat input manual</h2><p class="pt-subtitle mb-0">{{ $recentManual->total() }} rentang tercatat</p></div></div>
                <div class="pt-table-wrap"><table class="pt-table">
                    <thead><tr><th>SF</th><th>NAMA SF</th><th>TAP</th><th>DENOM</th><th>SN AWAL</th><th>SN AKHIR</th><th>QTY RENTANG</th><th>DIINPUT OLEH</th><th>WAKTU</th></tr></thead>
                    <tbody>@forelse($recentManual as $row)<tr>
                        <td><strong>{{ $row->sfcode }}</strong></td><td>{{ $row->namasf ?: '—' }}</td><td>{{ $row->idtap ?: '—' }}</td>
                        <td>{{ $row->denom ?: $row->iddenom }}</td><td>{{ $row->serial_start }}</td><td>{{ $row->serial_end }}</td>
                        <td>{{ number_format($row->qty) }}</td><td>{{ $row->username }}</td>
                        <td>{{ \Carbon\Carbon::parse($row->created_at)->locale('id')->translatedFormat('j M Y H:i') }}</td>
                    </tr>@empty<tr><td colspan="9" class="pt-empty">Belum ada rentang SN yang ditambahkan manual.</td></tr>@endforelse</tbody>
                </table></div>
                <div class="mt-3">{{ $recentManual->links() }}</div>
            </section>
        @endif
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
