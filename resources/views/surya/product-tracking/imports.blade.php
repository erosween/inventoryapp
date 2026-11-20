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
        <form method="post" action="{{ route('surya.product-tracking.imports.stage') }}" enctype="multipart/form-data" class="pt-grid pt-upload-form" data-loading-message="Membaca header dan menyiapkan pratinjau file…" data-loading-label="Membaca…">
            @csrf<input type="hidden" name="kind" value="{{ $kind }}">
            <div class="pt-field pt-upload-file-field"><label for="pt-file">{{ $kind==='scan'?'File ST Digipos':'File Digipos' }}</label><div class="pt-file-control"><input class="pt-file-input" id="pt-file" type="file" name="file" accept=".csv,.txt" required><label class="pt-file-action" for="pt-file">Pilih file</label><span class="pt-file-name pt-muted" id="pt-file-name">Belum ada file dipilih</span></div><small class="pt-file-hint">CSV · Maksimal 100 MB · Header pada baris pertama</small></div>
            <div class="pt-field pt-upload-submit"><button class="pt-button primary" type="submit"><i class="fas fa-upload"></i><span>Baca Header</span></button><span class="pt-loading-status" role="status" aria-live="polite" hidden></span></div>
        </form>@endif
    </section>

    @if($kind==='scan')
        <section class="pt-card">
            <h2>Total SN berhasil diupload per tanggal</h2>
            <p class="pt-subtitle">Kategori mengikuti awalan nama file, misalnya pv_dumai_20261120 atau sa_dumai_20261120. File berawalan pv_ atau sa_ masuk total produk; file tanpa prefix wilayah hanya masuk Total All.</p>
            <div class="pt-table-wrap">
                <table class="pt-table">
                    <thead><tr><th>Tanggal Scan</th><th>Dumai Bengkalis · PV</th><th>Dumai Bengkalis · SA</th><th>Rokan Hilir · PV</th><th>Rokan Hilir · SA</th><th>Total PV</th><th>Total SA</th><th>Total All</th></tr></thead>
                    <tbody>
                        @forelse($dailyTotals as $total)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($total->scan_date)->format('j M Y') }}</td>
                                <td>{{ number_format($total->dumai_bengkalis_pv_qty) }}</td>
                                <td>{{ number_format($total->dumai_bengkalis_sa_qty) }}</td>
                                <td>{{ number_format($total->rokan_hilir_pv_qty) }}</td>
                                <td>{{ number_format($total->rokan_hilir_sa_qty) }}</td>
                                <td>{{ number_format($total->total_pv_qty) }}</td>
                                <td>{{ number_format($total->total_sa_qty) }}</td>
                                <td><strong>{{ number_format($total->total_qty) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="pt-empty">Belum ada SN yang berhasil diupload.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($dailyTotals->hasPages())
                <nav class="pt-pagination d-flex align-items-center justify-content-between" aria-label="Navigasi halaman total SN">
                    <span class="pt-muted">10 tanggal per halaman · Halaman {{ $dailyTotals->currentPage() }}</span>
                    <div class="d-flex align-items-center" style="gap:8px">
                        @if($dailyTotals->onFirstPage())<span class="pt-button" aria-disabled="true">Sebelumnya</span>@else<a class="pt-button" href="{{ $dailyTotals->previousPageUrl() }}">Sebelumnya</a>@endif
                        <span class="pt-muted">Halaman {{ $dailyTotals->currentPage() }}</span>
                        @if($dailyTotals->hasMorePages())<a class="pt-button" href="{{ $dailyTotals->nextPageUrl() }}">Berikutnya</a>@else<span class="pt-button" aria-disabled="true">Berikutnya</span>@endif
                    </div>
                </nav>
            @endif
        </section>
    @endif
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
                document.querySelectorAll('form.pt-upload-form[data-loading-message]').forEach((form) => {
                    form.addEventListener('submit', () => {
                        if (form.dataset.submitting === 'true') return;
                        if (form.querySelector('[name="kind"]')?.value !== 'scan') return;
                        form.dataset.submitting = 'true';
                        form.setAttribute('aria-busy', 'true');
                        const button = form.querySelector('button[type="submit"]');
                        const status = form.querySelector('.pt-loading-status');
                        if (button) {
                            button.disabled = true;
                            button.innerHTML = `<span class="pt-spinner" aria-hidden="true"></span><span>${form.dataset.loadingLabel}</span>`;
                        }
                        if (status) {
                            status.textContent = form.dataset.loadingMessage;
                            status.hidden = false;
                        }
                    });
                });
            });
        </script>
        @endpush
    @endif
@endsection
