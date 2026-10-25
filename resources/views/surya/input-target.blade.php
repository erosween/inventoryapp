@extends('surya.layouts.workspace')
@section('title', 'Input Target')
@section('active-page', 'input-target')
@section('description', 'Kelola target PV dan SA per sales force.')
@section('content-class', 'surya-stock-page')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/surya-stock.css') }}?v={{ filemtime(public_path('css/surya-stock.css')) }}">
@endpush
@section('content')
<form method="POST" action="{{ route('surya.input-target.upload') }}" enctype="multipart/form-data" class="upload-form">
    @csrf
    <label for="target-file">Upload Excel target</label>
    <input id="target-file" type="file" name="target_file" accept=".xlsx,.xls,.csv" required>
    <button type="submit"><i class="fas fa-upload" aria-hidden="true"></i> Upload</button>
    <small>Header: SF Code | Nama SF | TAP | PV 1D | PV 2D | PV 3D | PV 5D | PV 7D | PV 28D | SA 3GB | SA 5GB | SA 7GB | SA 10GB.</small>
</form>
<section class="surya-card">
    <div class="target-toolbar"><strong>Target per Sales Force</strong><label class="search-box"><i class="fas fa-search" aria-hidden="true"></i><input id="target-search" type="search" aria-label="Cari sales force" placeholder="Cari SF code, nama, atau TAP..."></label></div>
@php
    $defaultColumns = collect(['1 HARI','2 HARI','3 HARI','5 HARI','7 HARI','14 HARI','28 HARI','30 HARI'])->map(fn($v) => 'PV|'.$v)
        ->merge(collect(['3GB','5GB','7GB','10GB'])->map(fn($v) => 'SA|'.$v));
    $columns = $defaultColumns->merge($targets->map(fn($t) => $t->product_family.'|'.$t->validity))->unique()->values();
    $bySf = $targets->groupBy('idsf');
@endphp
<div class="table-wrap"><table class="target-table"><thead><tr><th>SF Code</th><th>Nama SF</th><th>TAP</th>@foreach($columns as $column) @php([$family, $validity] = explode('|', $column, 2)) @php($shortValidity = $family === 'PV' ? preg_replace('/\s*HARI$/i', 'D', $validity) : $validity)<th>{{ $family }} {{ $shortValidity }}</th>@endforeach</tr></thead><tbody>
@forelse($sfs as $sf)<tr class="target-row" data-search="{{ strtolower($sf->idsf.' '.$sf->namasf.' '.$sf->idtap) }}"><td><strong>{{ $sf->idsf }}</strong></td><td>{{ $sf->namasf }}</td><td>{{ $sf->idtap }}</td>@foreach($columns as $column) @php($target = $bySf->get($sf->idsf, collect())->first(fn($t) => $t->product_family.'|'.$t->validity === $column))<td>@if($target)<form method="POST" action="{{ route('surya.input-target.update', $target->id) }}" class="target-inline-form">@csrf @method('PATCH')<input type="number" name="target_qty" aria-label="Target {{ $sf->namasf }} {{ str_replace('|', ' ', $column) }}" min="0" value="{{ $target->target_qty }}"><button title="Simpan target"><i class="fas fa-save"></i></button></form>@else<span class="text-muted">0</span>@endif</td>@endforeach</tr>@empty<tr><td colspan="{{ 3 + $columns->count() }}">Belum ada SF.</td></tr>@endforelse</tbody></table></div>
<div id="target-empty" class="empty-state" hidden>SF tidak ditemukan.</div>
</section>
@endsection
@push('scripts')
<script>
const targetSearch = document.getElementById('target-search');
const targetRows = [...document.querySelectorAll('.target-row')];
targetSearch.addEventListener('input', () => {
    const query = targetSearch.value.toLocaleLowerCase('id-ID').trim();
    let found = 0;
    targetRows.forEach(row => {
        row.hidden = !!query && !row.dataset.search.includes(query);
        if (!row.hidden) found++;
    });
    document.getElementById('target-empty').hidden = targetRows.length === 0 || found > 0;
});
</script>
@endpush
