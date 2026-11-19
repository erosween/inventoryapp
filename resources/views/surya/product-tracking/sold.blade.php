@extends('surya.layouts.workspace')

@section('title', 'Validasi ST')
@section('active-page', 'product-tracking-sold')
@section('description', 'Validasi kecocokan SN dan qty antara nota Mobile SURYA dan ST Digipos.')
@section('content-class', 'pt-page')

@push('styles')<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">@endpush

@section('content')
    <section class="pt-card pt-sold-intro">
        <div class="pt-sold-intro-copy">
            <span class="pt-sold-icon"><i class="fas fa-code-compare"></i></span>
            <div><h2>Ringkasan Validasi ST · {{ $activeTab === 'progress' ? 'SN Progress' : 'SN Approve' }}</h2><p class="pt-subtitle mb-0">{{ $activeTab === 'progress' ? 'Memantau SN dari transaksi Mobile SURYA yang menunggu approval.' : 'Mencocokkan SN transaksi Mobile SURYA yang disetujui dengan hasil upload ST Digipos berdasarkan outlet dan rentang tanggal.' }}</p></div>
        </div>
        <div class="pt-sold-stats" aria-label="Ringkasan validasi">
            <div><strong>{{ number_format($summary->matched_outlets) }}</strong><span>outlet sesuai</span></div>
            <div><strong>{{ number_format($summary->mismatch_outlets) }}</strong><span>outlet perlu dicek</span></div>
            <div><strong>{{ number_format($summary->matched_qty) }}</strong><span>qty sesuai</span></div>
            <div><strong>{{ number_format($summary->mismatch_qty) }}</strong><span>qty belum cocok</span></div>
        </div>
    </section>

    <nav class="pt-sold-tabs" aria-label="Kategori SN">
        <a class="{{ $activeTab === 'approve' ? 'active' : '' }}" href="{{ route('surya.product-tracking.sold', array_merge(request()->query(), ['tab' => 'approve'])) }}"><i class="fas fa-circle-check"></i> SN Approve</a>
        <a class="{{ $activeTab === 'progress' ? 'active' : '' }}" href="{{ route('surya.product-tracking.sold', array_merge(request()->query(), ['tab' => 'progress'])) }}"><i class="fas fa-hourglass-half"></i> SN Progress</a>
    </nav>

    <form method="get" action="{{ route('surya.product-tracking.sold') }}" class="pt-card pt-grid pt-sold-filter">
        <div class="pt-field"><label for="sold-date-from">Dari tanggal ST</label><input id="sold-date-from" type="date" name="date_from" value="{{ $filters['date_from'] }}" max="{{ now('Asia/Jakarta')->toDateString() }}"></div>
        <div class="pt-field"><label for="sold-date-to">Sampai tanggal ST</label><input id="sold-date-to" type="date" name="date_to" value="{{ $filters['date_to'] }}" max="{{ now('Asia/Jakarta')->toDateString() }}"></div>
        <div class="pt-field"><label for="sold-tap">TAP</label><select id="sold-tap" name="tap"><option value="">Semua TAP</option>@foreach($taps as $tap)<option value="{{ $tap }}" @selected($filters['tap']===$tap)>{{ $tap }}</option>@endforeach</select></div>
        <div class="pt-field"><label for="sold-sf">Sales Force</label><select id="sold-sf" name="sf"><option value="">Semua Sales Force</option>@foreach($sfs as $sf)<option value="{{ $sf->idsf }}" @selected($filters['sf']===$sf->idsf)>{{ $sf->namasf ?: $sf->idsf }}</option>@endforeach</select></div>
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <div class="pt-sold-actions"><button class="pt-button" type="submit"><i class="fas fa-filter"></i> Terapkan filter</button><button class="pt-button primary" type="submit" formaction="{{ route('surya.product-tracking.sold.download') }}" @disabled($summary->serial_count === 0)><i class="fas fa-download"></i> Download detail CSV</button></div>
    </form>
    @error('download')<div class="pt-alert error">{{ $message }}</div>@enderror
    @if(!$hasScanUploads)<div class="pt-alert error">Belum ada upload ST Digipos pada rentang tanggal ini. Qty ditampilkan sebagai belum cocok sampai file scan diunggah.</div>@endif

    @if($summary->serial_count > 0)
        @foreach([['Ringkasan per TAP', $tapSummaries], ['Ringkasan per Sales Force', $sfSummaries]] as [$heading, $groups])
            <section class="pt-card">
                <h2>{{ $heading }}</h2>
                <div class="pt-table-wrap"><table class="pt-table">
                    <thead><tr><th>{{ str_contains($heading, 'TAP') ? 'TAP' : 'Sales Force' }}</th><th>Total outlet</th><th>Outlet sesuai</th><th>Outlet perlu dicek</th><th>Qty nota</th><th>Qty sesuai</th><th>Qty belum cocok</th></tr></thead>
                    <tbody>
                    @foreach($groups as $group)
                        <tr><td>{{ $group->label }}</td><td>{{ number_format($group->outlet_count) }}</td><td><span class="pt-chip good">{{ number_format($group->matched_outlets) }}</span></td><td><span class="pt-chip {{ $group->mismatch_outlets ? 'bad' : 'good' }}">{{ number_format($group->mismatch_outlets) }}</span></td><td>{{ number_format($group->qty) }}</td><td>{{ number_format($group->matched_qty) }}</td><td>{{ number_format($group->mismatch_qty) }}</td></tr>
                    @endforeach
                    </tbody>
                </table></div>
            </section>
        @endforeach

        <section class="pt-card">
            <div class="pt-result-head"><div><h2>Rincian per outlet</h2><span class="pt-muted">{{ number_format($summary->outlet_count) }} outlet · bandingkan qty nota dengan SN Digipos yang cocok di outlet yang sama.</span></div></div>
            <div class="pt-table-wrap"><table class="pt-table">
                <thead><tr><th>Outlet</th><th>ID Outlet</th><th>TAP</th><th>Sales Force</th><th>Qty nota</th><th>Qty sesuai</th><th>Qty belum cocok</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($outlets as $outlet)
                    <tr><td>{{ $outlet->nama_outlet ?: 'Nama outlet tidak tersedia' }}</td><td>{{ $outlet->id_outlet }}</td><td>{{ $outlet->tap }}</td><td>{{ $outlet->namasf ?: ($outlet->idsf ?: '—') }}</td><td>{{ number_format($outlet->qty) }}</td><td>{{ number_format($outlet->matched_qty) }}</td><td>{{ number_format($outlet->mismatch_qty) }}</td><td>@if($outlet->mismatch_qty === 0)<span class="pt-chip good">Sesuai</span>@else<span class="pt-chip bad">Perlu dicek</span>@endif</td></tr>
                @endforeach
                </tbody>
            </table></div>
        </section>
    @else
        <section class="pt-card"><p class="pt-empty mb-0">Belum ada SN {{ $activeTab === 'progress' ? 'menunggu approval' : 'dari transaksi yang disetujui' }} pada filter ini.</p></section>
    @endif
@endsection
