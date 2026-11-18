@extends('surya.layouts.workspace')

@section('title', $kind==='warehouse'?'Warehouse Digipos':'Upload ST Digipos')
@section('active-page', $kind==='warehouse'?'product-tracking-warehouse':'product-tracking-scan')
@section('description', $kind==='warehouse'?'Impor snapshot warehouse untuk pencocokan serial.':'Unggah ST Digipos untuk pencocokan SN dan rekonsiliasi.')
@section('content-class', 'pt-page')

@push('styles')<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">@endpush

@section('content')
    <section class="pt-card">
        <h2>{{ $kind==='warehouse'?'Upload Warehouse Digipos':'Upload ST Digipos' }}</h2>
        <p class="pt-subtitle">Pilih file CSV. Setelah header terbaca, petakan kolom SN, ID DigiPOS, user login SF, tanggal, dan denom sebelum impor.</p>
        @if(!auth()->user()?->isSuperAdmin())<div class="pt-muted">Upload hanya tersedia untuk Admin.</div>@else
        <form method="post" action="{{ route('surya.product-tracking.imports.stage') }}" enctype="multipart/form-data" class="pt-grid pt-upload-form">
            @csrf<input type="hidden" name="kind" value="{{ $kind }}">
            <div class="pt-field pt-upload-file-field"><label for="pt-file">{{ $kind==='scan'?'File ST Digipos':'File Digipos' }}</label><div class="pt-file-control"><input class="pt-file-input" id="pt-file" type="file" name="file" accept=".csv,.txt" required><label class="pt-file-action" for="pt-file">Pilih file</label><span class="pt-file-name pt-muted" id="pt-file-name">Belum ada file dipilih</span></div><small class="pt-file-hint">CSV · Maksimal 100 MB · Header pada baris pertama</small></div>
            <div class="pt-field pt-upload-submit"><button class="pt-button primary" type="submit"><i class="fas fa-upload"></i> Baca Header</button></div>
        </form>@endif
    </section>

    @if($pending->isNotEmpty())<section class="pt-card"><h2>Upload menunggu pemetaan</h2><div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>File</th><th>Tanggal</th><th>Diunggah</th><th>Aksi</th></tr></thead><tbody>@foreach($pending as $item)<tr><td>{{ $item->filename }}</td><td>{{ $item->scan_date?:'—' }}</td><td>{{ $item->created_at }}</td><td><a class="pt-button" href="{{ route('surya.product-tracking.mapping',$item->id) }}">Petakan Kolom</a></td></tr>@endforeach</tbody></table></div></section>@endif

    <section class="pt-card"><div class="pt-result-head"><div><h2>Riwayat Upload</h2><span class="pt-muted">Warehouse terbaru yang selesai diimpor menjadi sumber pencocokan aktif.</span></div></div>
        <div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>Waktu Upload</th><th>File</th><th>Tanggal Scan</th><th>Baris</th><th>Status</th></tr></thead><tbody>
        @forelse($uploads as $item)<tr><td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}</td><td>{{ $item->filename }}</td><td>{{ $item->scan_date?:'—' }}</td><td>{{ number_format($item->row_count) }}</td><td>@if($item->status==='complete')<span class="pt-chip good">Selesai</span>@else<span class="pt-chip bad">Duplikat · tidak dihitung</span>@endif</td></tr>@empty<tr><td colspan="5" class="pt-empty">Belum ada file {{ $kind==='warehouse'?'warehouse':'ST' }} Digipos.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    @if(auth()->user()?->isSuperAdmin())
        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const input = document.getElementById('pt-file');
                const name = document.getElementById('pt-file-name');
                input?.addEventListener('change', () => {
                    const file = input.files?.[0];
                    name.textContent = file ? `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB` : 'Belum ada file dipilih';
                });
            });
        </script>
        @endpush
    @endif
@endsection
