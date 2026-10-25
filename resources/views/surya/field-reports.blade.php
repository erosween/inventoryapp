@extends('surya.layouts.workspace')
@section('title', 'Laporan Lapangan')
@section('active-page', 'field-reports')
@section('description', 'Pantau konsinyasi SA, survei kompetitor, dan kondisi jaringan outlet.')
@section('content')
<section class="surya-card">
    <form class="surya-report-filters" method="get">
        <label for="report-type">Jenis laporan</label>
        <select id="report-type" name="type" onchange="this.form.submit()">
            <option value="consignment" @selected($type === 'consignment')>Konsinyasi SA</option>
            <option value="survey" @selected($type === 'survey')>Survei Kompetitor</option>
            <option value="network" @selected($type === 'network')>Jaringan</option>
        </select>
    </form>
    <div class="surya-report-scroll"><table class="surya-report-table">
        <thead><tr><th>Waktu</th><th>Outlet</th><th>SF</th><th>Detail</th><th>Catatan</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
        <tr>
            <td>{{ \Carbon\Carbon::parse($row->reported_at)->format('d M Y H:i') }}</td>
            <td>{{ $row->nama_outlet ?? $row->id_outlet }}</td><td>{{ $row->namasf ?? $row->idsf }}</td>
            <td>@if($type === 'consignment'){{ $row->serial_number }} · {{ $row->status }}@elseif($type === 'survey'){{ $row->operator }} · {{ $row->product ?: '—' }} · Rp {{ number_format($row->price ?? 0, 0, ',', '.') }}@else{{ $row->operator }} · Sinyal: {{ $row->signal_status }} · Data: {{ $row->data_status ?: '—' }}@endif</td>
            <td>{{ $row->notes ?: '—' }}</td>
        </tr>
        @empty<tr><td colspan="5" class="surya-table-empty">Belum ada data.</td></tr>@endforelse
        </tbody>
    </table></div>
    @include('surya.partials.pagination', ['paginator' => $rows])
</section>
@endsection
