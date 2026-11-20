@extends('surya.layouts.workspace')

@section('title', 'Upload Stok SN Sales')
@section('active-page', 'product-tracking-sales-stock')
@section('description', 'Kumpulkan serial number yang sedang dipegang Sales Force.')
@section('content-class', 'pt-page')

@push('styles')<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">@endpush

@section('content')
    <section class="pt-card">
        <div class="pt-result-head">
            <div>
                <h2>Upload Stok SN Sales</h2>
                <p class="pt-subtitle">Unggah daftar serial yang saat ini dipegang Sales Force. Rentang SN disimpan per baris untuk persiapan pemilihan stok berikutnya.</p>
            </div>
            <a class="pt-button" href="{{ route('surya.product-tracking.sales-stock.template') }}"><i class="fas fa-download"></i> Unduh Template CSV</a>
        </div>

        <div class="pt-table-wrap pt-sales-stock-template">
            <table class="pt-table"><thead><tr><th>SFCODE</th><th>TAP</th><th>CLUSTER</th><th>DENOM PAKET</th><th>SN AWAL</th><th>SN AKHIR</th><th>QTY</th></tr></thead>
                <tbody><tr><td>SF001</td><td>DURI</td><td>DUMAI BENGKALIS</td><td>7GB/2hari PV</td><td>902000000001</td><td>902000000003</td><td>3</td></tr></tbody>
            </table>
        </div>
        <p class="pt-muted"><strong>DENOM PAKET</strong> adalah satu kolom. Satu nomor saja diisi dengan SN Awal = SN Akhir dan QTY = 1. SN PV harus 12 digit; SN SA harus 16 digit dan diawali 0. Simpan kolom SN sebagai teks agar nol di depan tidak hilang.</p>

        @if(auth()->user()?->isSuperAdmin())
            <form method="post" action="{{ route('surya.product-tracking.sales-stock.upload') }}" enctype="multipart/form-data" class="pt-grid pt-upload-form" data-loading-message="Memvalidasi data dan menyimpan rentang SN…" data-loading-label="Mengunggah…">
                @csrf
                <div class="pt-field pt-upload-file-field"><label for="sales-stock-file">File stok SN Sales</label><div class="pt-file-control"><input class="pt-file-input" id="sales-stock-file" type="file" name="file" accept=".csv,.txt,.xlsx,.xls" required><label class="pt-file-action" for="sales-stock-file">Pilih file</label><span class="pt-file-name pt-muted" id="sales-stock-file-name">Belum ada file dipilih</span></div><small class="pt-file-hint">CSV atau Excel · Maksimal 100 MB · Header wajib sesuai template</small></div>
                <div class="pt-field pt-upload-submit"><button class="pt-button primary" type="submit"><i class="fas fa-upload"></i><span>Upload Stok</span></button><span class="pt-loading-status" role="status" aria-live="polite" hidden></span></div>
            </form>
        @else
            <div class="pt-muted">Upload hanya tersedia untuk Admin.</div>
        @endif
    </section>

    <section class="pt-card">
        <div class="pt-result-head"><div><h2>Riwayat Upload</h2><span class="pt-muted">Menampilkan maksimal 30 upload terbaru.</span></div></div>
        <div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>Waktu Upload</th><th>File</th><th>Jumlah Baris</th><th>Diunggah Oleh</th></tr></thead><tbody>
            @forelse($uploads as $upload)
                <tr><td>{{ \Carbon\Carbon::parse($upload->created_at)->format('d M Y H:i') }}</td><td>{{ $upload->filename }}</td><td>{{ number_format($upload->row_count) }}</td><td>{{ $upload->username }}</td></tr>
            @empty
                <tr><td colspan="4" class="pt-empty">Belum ada stok SN Sales yang diunggah.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
    @if(auth()->user()?->isSuperAdmin())
        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const input = document.getElementById('sales-stock-file');
                const name = document.getElementById('sales-stock-file-name');
                input?.addEventListener('change', () => {
                    const file = input.files?.[0];
                    name.textContent = file ? `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB` : 'Belum ada file dipilih';
                });
                document.querySelectorAll('form.pt-upload-form[data-loading-message]').forEach((form) => {
                    form.addEventListener('submit', () => {
                        if (form.dataset.submitting === 'true') return;
                        form.dataset.submitting = 'true';
                        form.setAttribute('aria-busy', 'true');
                        const button = form.querySelector('button[type="submit"]');
                        const status = form.querySelector('.pt-loading-status');
                        if (button) { button.disabled = true; button.innerHTML = `<span class="pt-spinner" aria-hidden="true"></span><span>${form.dataset.loadingLabel}</span>`; }
                        if (status) { status.textContent = form.dataset.loadingMessage; status.hidden = false; }
                    });
                });
            });
        </script>
        @endpush
    @endif
@endsection
