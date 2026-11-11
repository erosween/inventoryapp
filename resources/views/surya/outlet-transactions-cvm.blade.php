{{--
    Transaksi Outlet CVM. Detail outlet dirender dulu, rekap TAP dimuat dari respons terpisah.
--}}@extends('surya.layouts.workspace')

@section('title', 'Transaksi CVM')
@section('active-page', 'transactions-cvm')
@section('description', 'Tren bulanan CVM dan Sub CVM per TAP dan outlet.')
@section('content-class', 'ngrs-page transaction-report')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/surya-ngrs.css') }}?v={{ filemtime(public_path('css/surya-ngrs.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/surya-outlet-transactions.css') }}?v={{ filemtime(public_path('css/surya-outlet-transactions.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/surya-transaction-tables.css') }}?v={{ filemtime(public_path('css/surya-transaction-tables.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/surya-outlet-columns.css') }}?v={{ filemtime(public_path('css/surya-outlet-columns.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/surya-transaction-tables.js') }}?v={{ filemtime(public_path('js/surya-transaction-tables.js')) }}" defer></script>
    <script src="{{ asset('js/surya-ngrs-columns.js') }}?v={{ filemtime(public_path('js/surya-ngrs-columns.js')) }}" defer></script>
    <script src="{{ asset('js/surya-taskforce-columns.js') }}?v={{ filemtime(public_path('js/surya-taskforce-columns.js')) }}" defer></script>
@endpush

@section('content')
    <form class="ngrs-toolbar outlet-transactions-toolbar cvm-transactions-toolbar" method="get" action="{{ route('surya.outlet-cvm') }}" aria-label="Filter transaksi CVM">
        <label>Cutoff transaksi
            <input name="cutoff" type="date" value="{{ $cutoff->toDateString() }}" max="{{ $today }}" required>
        </label>
        <label>TAP
            <select name="tap">
                <option value="">Semua TAP</option>
                @foreach($taps as $option)
                    <option value="{{ $option }}" @selected($tap === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>
        <label class="ngrs-search">Cari outlet
            <input name="q" value="{{ $search }}" placeholder="ID outlet, nama, RS, SF, kecamatan">
        </label>
        <label>Per halaman
            <select name="per_page">
                @foreach([25, 50, 100] as $size)
                    <option @selected($size === $pageSize)>{{ $size }}</option>
                @endforeach
            </select>
        </label>
        <span data-column-options hidden>
            @foreach(['columns', 'exclude'] as $mode)
                @foreach($options[$mode] ?? [] as $key => $values)
                    @foreach($values as $value)
                        <input type="hidden" name="{{ $mode }}[{{ $key }}][]" value="{{ $value }}">
                    @endforeach
                @endforeach
            @endforeach
            @if(!empty($options['sort']))
                <input type="hidden" name="sort" value="{{ $options['sort'] }}">
                <input type="hidden" name="direction" value="{{ $options['direction'] ?? 'desc' }}">
            @endif
        </span>
        <button class="ngrs-button" type="submit">Tampilkan</button>
        <a class="ngrs-button secondary" href="{{ route('surya.outlet-cvm.export', array_merge(request()->only(['tap', 'q', 'columns', 'exclude', 'sort', 'direction']), ['cutoff' => $cutoff->toDateString()])) }}">Download Outlet</a>
    </form>

    @if($latest === null)
        <p class="ngrs-note">Belum ada data CVM atau Sub CVM yang diupload.</p>
    @endif

    @php
        $frozenColumns = [
            'nama_outlet' => 'ngrs-frozen-name',
            'tap' => 'ngrs-frozen-tap',
            'cluster' => 'ngrs-frozen-cluster',
        ];
    @endphp

    <div class="ngrs-report-heading">
        <div>
            <h2>Rekap per TAP</h2>
            <p>Cutoff {{ $cutoff->translatedFormat('d M Y') }}@if($comparison)
                · Pembanding MTD 1–{{ $previousEnd->translatedFormat('d M Y') }}
            @endif</p>
        </div>
        <a class="ngrs-button secondary" href="{{ route('surya.outlet-cvm.export', ['cutoff' => $cutoff->toDateString(), 'tap' => $tap, 'q' => $search, 'summary' => 1]) }}">Download Rekap</a>
    </div>
    <div data-report-summary-url="{{ $summaryUrl }}">
        <p class="ngrs-note" role="status">Memuat rekap TAP…</p>
        <button class="ngrs-button" type="button" data-report-summary-retry hidden>Muat ulang rekap</button>
        <noscript><a href="{{ $summaryUrl }}">Buka rekap TAP</a></noscript>
    </div>

    <div class="ngrs-report-heading"><h2>Detail per outlet</h2></div>
    <section class="ngrs-card" aria-label="Transaksi CVM per outlet">
        <div class="ngrs-scroll ngrs-detail-scroll" tabindex="0">
            <table class="ngrs-detail cvm-transactions-table" data-transaction-table="detail" data-report-end-month="{{ $cutoff->month }}" data-report-default-range="12">
                @include('surya.partials.outlet-cvm-head', ['labels' => ['row_number' => 'No'] + $identity, 'filterable' => true])
                <tbody>
                    @forelse($outlets as $outlet)
                        <tr>
                            <td>{{ $outlets->firstItem() + $loop->index }}</td>
                            @foreach($identity as $column => $label)
                                <td @class([$frozenColumns[$column] ?? ''])>{{ $outlet->{$column} ?: ($column === 'kategori_outlet' ? 'Reguler' : '—') }}</td>
                            @endforeach
                            @include('surya.partials.outlet-cvm-values', ['series' => $details->get($outlet->id_outlet, [])])
                        </tr>
                    @empty
                        <tr><td colspan="{{ 1 + count($identity) + count($metrics) * count($columns) }}" class="ngrs-empty">Tidak ada outlet sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="ngrs-footer">
            <span>{{ number_format($outlets->total()) }} outlet · — berarti belum ada data pada periode ini</span>
            <div>
                @if($outlets->previousPageUrl())
                    <a href="{{ $outlets->previousPageUrl() }}">‹ Sebelumnya</a>
                @endif
                <span>{{ $outlets->currentPage() }} / {{ $outlets->lastPage() }}</span>
                @if($outlets->nextPageUrl())
                    <a href="{{ $outlets->nextPageUrl() }}">Berikutnya ›</a>
                @endif
            </div>
        </footer>
    </section>
    @include('surya.partials.outlet-column-menu', [
        'columnSearch' => $search,
        'columnValuesUrl' => route('surya.outlet-cvm.values'),
        'columnResetLabel' => 'Urutan nama outlet',
    ])
@endsection
