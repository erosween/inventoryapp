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
<section class="upload-card" style="margin-top:24px"><div class="eyebrow">TASKFORCE · PUSH SO</div><h3>Upload Daftar Outlet Taskforce</h3><p class="muted">Tentukan outlet yang menjadi tugas asmen. PUSH SO hanya menampilkan outlet dalam daftar upload bulan yang dipilih, sesuai wilayah user.</p>
<form class="drop" id="taskforce-upload-form" method="POST" action="{{ route('surya.taskforce.upload') }}" enctype="multipart/form-data">
@csrf
<label for="taskforce-month">Bulan taskforce</label><input class="input" id="taskforce-month" name="taskforce_month" type="month" value="{{ old('taskforce_month',now('Asia/Jakarta')->format('Y-m')) }}" required>
@error('taskforce_month')<div class="field-error">{{ $message }}</div>@enderror
<label class="file-label" for="taskforce-file"><i class="fa fa-file-excel"></i> File khusus taskforce</label><input class="file-input" id="taskforce-file" type="file" name="taskforce_file" accept=".xlsx,.xls,.csv" required>
@error('taskforce_file')<div class="field-error">{{ $message }}</div>@enderror
<div class="metrics-import-actions"><button class="btn" type="submit">Upload Daftar Taskforce</button><a class="btn metrics-import-resume" href="{{ route('surya.taskforce.template') }}">Download Template CSV</a></div><p role="status" data-taskforce-status hidden></p>
</form><div class="info"><strong>Format:</strong> kolom A berjudul <code>ID Outlet</code>, satu outlet per baris. Maksimal 10.000 outlet / 5 MB; gunakan sheet pertama. Nama, TAP, dan SF diambil dari master outlet.<br><strong>Upload ulang mengganti daftar aktif untuk bulan tersebut.</strong> Daftar lama, status visit, dan catatan tetap disimpan. Jika ada ID yang tidak terdaftar, seluruh file ditolak agar tidak ada tugas yang hilang diam-diam.<br>Angka transaksi tetap mengikuti Detail Outlet. File taskforce hanya menentukan daftar outlet yang ditugaskan.</div></section>
</main></div></div>

<script src="{{ asset('js/surya-metrics-upload.js') }}?v={{ filemtime(public_path('js/surya-metrics-upload.js')) }}" defer></script>
<script>document.getElementById('taskforce-upload-form').addEventListener('submit',function(){const b=this.querySelector('button[type="submit"]'),s=this.querySelector('[data-taskforce-status]');b.disabled=true;b.textContent='Memproses daftar…';s.hidden=false;s.textContent='Tunggu sampai proses selesai. Daftar akan diganti setelah seluruh file valid.';this.setAttribute('aria-busy','true');});window.addEventListener('pageshow',function(){const f=document.getElementById('taskforce-upload-form');f.querySelector('button').disabled=false;f.querySelector('button').textContent='Upload Daftar Taskforce';f.querySelector('[data-taskforce-status]').hidden=true;f.removeAttribute('aria-busy');});</script>
</body></html>
