<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Upload Transaksi · SURYA</title>

<link rel="stylesheet" href="{{ asset('css/surya-upload.css') }}?v={{ filemtime(public_path('css/surya-upload.css')) }}">
    @include('surya.partials.assets')
</head>
<body><div class="shell" id="app-shell" data-surya-workspace>
@include('surya.partials.sidebar', ['activePage' => 'transaction-upload'])

<div class="main surya-workspace-main">@include('surya.partials.header')<main class="content surya-content">
@include('surya.partials.page-heading', ['title' => 'Upload Transaksi', 'description' => 'Kelola pembaruan transaksi outlet dari satu workspace SURYA.'])
@if(session('success'))<div class="alert"><i class="fa fa-check-circle"></i> {{ session('success') }}</div>@endif
<section class="upload-card"><div class="eyebrow">SURYA DATA MANAGEMENT</div><h3>Upload Transaksi Outlet</h3><p class="muted">Upload file Excel manual untuk memperbarui transaksi CVM, Super Seru, Hot Promo, Comsak, Insak, dan Digital.</p>
<form class="drop" id="metrics-upload-form" method="POST" action="{{ route('surya.transaction-upload.store') }}" data-process-url="{{ route('surya.transaction-upload.process', ['upload' => '__UPLOAD__']) }}" data-owner="{{ auth()->id() }}" enctype="multipart/form-data">
@csrf
<label class="file-label" for="metrics-file"><i class="fa fa-file-excel"></i> Pilih file Excel atau CSV</label>
<input class="file-input" id="metrics-file" type="file" name="metrics_file" accept=".xlsx,.xls,.csv" required>
@error('metrics_file')<div class="field-error">{{ $message }}</div>@enderror
<div class="metrics-import-progress" hidden>
    <div class="metrics-import-heading"><strong data-import-label>Menyiapkan upload</strong><span data-import-percent>0%</span></div>
    <progress max="100" value="0" aria-label="Progres upload transaksi"></progress>
    <p data-import-status role="status" aria-live="polite"></p>
</div>
<p class="field-error" data-import-error role="alert" hidden></p>
<div class="metrics-import-actions">
    <button class="btn" type="submit"><i class="fa fa-upload"></i><span data-import-button>Upload dan Simpan</span></button>
    <button class="btn metrics-import-resume" type="button" data-import-resume hidden>Lanjutkan Upload</button>
    <button class="btn metrics-import-resume" type="button" data-import-change hidden>Pilih File Lain</button>
</div>
<p class="metrics-import-hint">File diproses bertahap. Tunggu hingga selesai; jika koneksi terputus, lanjutkan upload dari progres terakhir.</p>
</form>
<div class="info"><strong>Upload transaksi:</strong> gunakan header <code>id_outlet</code>, kolom KPI berpola <code>_fm1</code>, <code>_m1</code>, <code>_m</code>, dan <code>update</code>.<br><strong>Upload historis SA/PV:</strong> empat kolom berurutan <code>Tanggal Trx</code> | <code>ID Outlet</code> | <code>SA</code> | <code>PV</code>. Sistem mengenali file dari tanggal pada kolom pertama.<br><strong>Upload biometrik:</strong> empat kolom berurutan <code>ID Outlet</code> | <code>Bio FM1</code> | <code>Bio M1</code> | <code>Bio MTD</code>. Nama header bebas; sistem membaca berdasarkan urutan kolom. Hanya user <strong>admin_super</strong> yang dapat membuka halaman ini.</div></section>
</main></div></div>

<script src="{{ asset('js/surya-metrics-upload.js') }}?v={{ filemtime(public_path('js/surya-metrics-upload.js')) }}" defer></script>
</body></html>
