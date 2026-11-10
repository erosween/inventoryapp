@extends('surya.layouts.workspace')

@section('title', 'Transaksi CVM')
@section('active-page', 'transactions-cvm')
@section('description', 'Tren bulanan CVM dan Sub CVM per TAP dan outlet.')
@section('content-class', 'ngrs-page transaction-report')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/surya-ngrs.css') }}?v={{ filemtime(public_path('css/surya-ngrs.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/surya-outlet-transactions.css') }}?v={{ filemtime(public_path('css/surya-outlet-transactions.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/surya-transaction-tables.css') }}?v={{ filemtime(public_path('css/surya-transaction-tables.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/surya-transaction-tables.js') }}?v={{ filemtime(public_path('js/surya-transaction-tables.js')) }}" defer></script>
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
        <button class="ngrs-button" type="submit">Tampilkan</button>
    </form>

    @if($latest === null)
        <p class="ngrs-note">Belum ada data CVM atau Sub CVM yang diupload.</p>
    @endif

    @php
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        $columns = [];
        foreach (range(1, $cutoff->month) as $month) {
            $columns['m'.$month] = $months[$month - 1];
            if ($comparison && $month === $cutoff->month - 1) {
                $columns['compare'] = $months[$month - 1].'-MoM';
            }
        }
        $columns['total'] = 'Total '.$cutoff->year;
        for ($month = 2; $month <= $cutoff->month; $month++) {
            $columns['gap_m'.$month] = 'GAP '.$months[$month - 1];
            $columns['mom_pct_m'.$month] = 'MoM % '.$months[$month - 1];
        }
    @endphp

    <div class="ngrs-report-heading">
        <div>
            <h2>Rekap per TAP</h2>
            <p>Cutoff {{ $cutoff->translatedFormat('d M Y') }}@if($comparison)
                · Pembanding MTD 1–{{ $previousEnd->translatedFormat('d M Y') }}
            @endif</p>
        </div>
    </div>
    <section class="ngrs-card" aria-label="Rekap CVM per TAP">
        <div class="ngrs-scroll" tabindex="0">
            <table class="ngrs-summary cvm-transactions-table" data-transaction-table="summary" data-report-end-month="{{ $cutoff->month }}" data-report-default-range="12">
                @include('surya.partials.outlet-cvm-head', ['labels' => ['tap' => 'TAP']])
                <tbody>
                    @foreach($summary as $name => $series)
                        <tr><th scope="row">{{ $name ?: 'Tanpa TAP' }}</th>@include('surya.partials.outlet-cvm-values')</tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @foreach($clusters as $name => $series)
                        <tr class="ngrs-cluster"><th scope="row"><small>Cluster</small>{{ $name }}</th>@include('surya.partials.outlet-cvm-values')</tr>
                    @endforeach
                    <tr class="ngrs-grand">
                        <th scope="row">Grand Total</th>
                        @include('surya.partials.outlet-cvm-values', ['series' => $grand])
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <div class="ngrs-report-heading"><h2>Detail per outlet</h2></div>
    <section class="ngrs-card" aria-label="Transaksi CVM per outlet">
        <div class="ngrs-scroll ngrs-detail-scroll" tabindex="0">
            <table class="ngrs-detail cvm-transactions-table" data-transaction-table="detail" data-report-end-month="{{ $cutoff->month }}" data-report-default-range="12">
                @include('surya.partials.outlet-cvm-head', ['labels' => ['row_number' => 'No'] + $identity])
                <tbody>
                    @forelse($outlets as $outlet)
                        <tr>
                            <td>{{ $outlets->firstItem() + $loop->index }}</td>
                            @foreach($identity as $column => $label)
                                <td>{{ $outlet->{$column} ?: ($column === 'kategori_outlet' ? 'Reguler' : '—') }}</td>
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
@endsection
