@extends('surya.layouts.workspace')

@section('title', 'Cek SN SF')
@section('active-page', 'product-tracking-sf-stock-lookup')
@section('description', 'Cari Sales Force pemilik rentang serial number.')
@section('content-class', 'pt-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">
@if($tab === 'add')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
    .pt-page .pt-field .select2-container { width:100%!important; }
    .pt-page .pt-field .select2-selection--single { height:44px!important;padding:7px 10px!important;border:1px solid #d7dfeb!important;border-radius:10px!important;background:#fff!important; }
    .pt-page .pt-field .select2-selection__rendered { padding-left:2px!important;color:#172033!important;font-size:14px;line-height:28px!important; }
    .pt-page .pt-field .select2-selection__arrow { top:8px!important;right:8px!important; }
    .pt-page .select2-container--open .select2-dropdown { z-index:1080;border-color:#d7dfeb;border-radius:10px;overflow:hidden; }
    .pt-page .select2-search__field { min-height:38px;border:1px solid #d7dfeb!important;border-radius:7px;padding:7px 10px;outline:none; }
    .pt-page .select2-results__option { padding:9px 11px;font-size:13px; }
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
@if($tab === 'add')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endif
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.jQuery && $.fn.select2) {
        $('.pt-search-select').select2({
            width: '100%',
            placeholder: function () { return $(this).data('placeholder'); },
            allowClear: true,
            minimumResultsForSearch: 0,
            language: { noResults: () => 'Tidak ditemukan' }
        });
        $(document).on('select2:open', () => {
            setTimeout(() => document.querySelector('.select2-container--open .select2-search__field')?.focus(), 30);
        });
    }
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
