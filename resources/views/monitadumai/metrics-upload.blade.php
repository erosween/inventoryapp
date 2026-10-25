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
<form class="drop" method="POST" action="{{ route('surya.transaction-upload.store') }}" enctype="multipart/form-data">@csrf<label class="file-label" for="metrics-file"><i class="fa fa-file-excel"></i> Pilih file upload.xlsx</label><input class="file-input" id="metrics-file" type="file" name="metrics_file" accept=".xlsx,.xls,.csv" required>@error('metrics_file')<div class="field-error">{{ $message }}</div>@enderror<button class="btn" type="submit"><i class="fa fa-upload"></i>Upload dan Simpan</button></form>
<div class="info"><strong>Upload transaksi:</strong> gunakan header <code>id_outlet</code>, kolom KPI berpola <code>_fm1</code>, <code>_m1</code>, <code>_m</code>, dan <code>update</code>.<br><strong>Upload historis SA/PV:</strong> empat kolom berurutan <code>Tanggal Trx</code> | <code>ID Outlet</code> | <code>SA</code> | <code>PV</code>. Sistem mengenali file dari tanggal pada kolom pertama.<br><strong>Upload biometrik:</strong> empat kolom berurutan <code>ID Outlet</code> | <code>Bio FM1</code> | <code>Bio M1</code> | <code>Bio MTD</code>. Nama header bebas; sistem membaca berdasarkan urutan kolom. Hanya user <strong>admin_super</strong> yang dapat membuka halaman ini.</div></section>
</main></div></div>

</body></html>
