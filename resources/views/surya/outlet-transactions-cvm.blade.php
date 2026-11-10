@extends('surya.layouts.workspace')

@section('title', 'Transaksi CVM')
@section('active-page', 'transactions-cvm')
@section('description', 'Total CVM dan Sub CVM per outlet untuk periode yang dipilih.')
@section('content-class', 'ngrs-page transaction-report')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/surya-ngrs.css') }}?v={{ filemtime(public_path('css/surya-ngrs.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/surya-outlet-transactions.css') }}?v={{ filemtime(public_path('css/surya-outlet-transactions.css')) }}">
@endpush

@section('content')
    <form class="ngrs-toolbar outlet-transactions-toolbar cvm-transactions-toolbar" method="get" action="{{ route('surya.outlet-cvm') }}" aria-label="Filter transaksi CVM">
        <label>Dari tanggal
            <input name="from" type="date" value="{{ $from }}" max="{{ $today }}" required>
        </label>
        <label>Sampai tanggal
            <input name="to" type="date" value="{{ $to }}" max="{{ $today }}" required>
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
        <input type="hidden" name="per_page" value="{{ $pageSize }}">
        <button class="ngrs-button" type="submit">Tampilkan</button>
    </form>

    @if($latest === null)
        <p class="ngrs-note">Belum ada data CVM atau Sub CVM yang diupload.</p>
    @endif

    <section class="ngrs-card" aria-label="Transaksi CVM per outlet">
        <div class="ngrs-scroll ngrs-detail-scroll" tabindex="0">
            <table class="ngrs-detail cvm-transactions-table">
                <thead>
                    <tr>
                        <th scope="colgroup" colspan="{{ count($identity) }}">Profil Outlet</th>
                        <th scope="colgroup" colspan="{{ count($metrics) }}">Total Transaksi</th>
                    </tr>
                    <tr>
                        @foreach($identity + $metrics as $label)
                            <th scope="col">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($outlets as $outlet)
                        <tr>
                            @foreach($identity as $column => $label)
                                <td>{{ $outlet->{$column} ?: ($column === 'kategori_outlet' ? 'Reguler' : '—') }}</td>
                            @endforeach
                            @php($values = $totals->get($outlet->id_outlet))
                            @foreach($metrics as $column => $label)
                                @php($value = $values?->{$column})
                                <td>{{ $value === null ? '—' : number_format($value) }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($identity) + count($metrics) }}" class="ngrs-empty">Tidak ada outlet sesuai filter.</td></tr>
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
