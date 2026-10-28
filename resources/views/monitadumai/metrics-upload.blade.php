<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Upload TRX · SURYA</title>
@include('surya.partials.assets')
<link rel="stylesheet" href="{{ asset('css/surya-upload.css') }}?v={{ filemtime(public_path('css/surya-upload.css')) }}">
</head>
<body class="surya-upload-page"><div class="shell" id="app-shell" data-surya-workspace>
@include('surya.partials.sidebar', ['activePage' => 'transaction-upload'])
<div class="main surya-workspace-main">@include('surya.partials.header')<main class="content surya-content">
@include('surya.partials.page-heading', ['title' => 'Upload TRX'])
@if(session('success'))<div class="upload-alert" role="status"><i class="fa fa-check-circle" aria-hidden="true"></i> {{ session('success') }}</div>@endif
<div class="upload-grid">
<section class="upload-card" aria-labelledby="trx-title">
<header class="upload-card-header"><span class="upload-icon"><i class="fa fa-chart-column" aria-hidden="true"></i></span><h2 id="trx-title">TRX CVM / Biometrik</h2><span class="upload-format">CSV</span></header>
<form class="drop" id="metrics-upload-form" method="POST" action="{{ route('surya.transaction-upload.store') }}" data-process-url="{{ route('surya.transaction-upload.process', ['upload' => '__UPLOAD__']) }}" data-owner="{{ auth()->id() }}" enctype="multipart/form-data">
@csrf
<label class="file-label" for="metrics-file">File transaksi</label>
<input class="file-input" id="metrics-file" type="file" name="metrics_file" accept=".csv,text/csv" aria-describedby="trx-file-hint" required>
<p class="upload-hint" id="trx-file-hint">Nama file: cvm.csv atau biometrik.csv · CSV UTF-8 · Maks. 20 MB</p>
@error('metrics_file')<div class="field-error" role="alert">{{ $message }}</div>@enderror
<div class="metrics-import-progress" hidden>
<div class="metrics-import-heading"><strong data-import-label>Menyiapkan upload</strong><span data-import-percent>0%</span></div>
<progress max="100" value="0" aria-label="Progres upload transaksi"></progress>
<p data-import-status role="status" aria-live="polite"></p>
</div>
<p class="field-error" data-import-error role="alert" hidden></p>
<div class="metrics-import-actions">
<button class="btn" type="submit"><i class="fa fa-upload" aria-hidden="true"></i><span data-import-button>Upload</span></button>
<button class="btn metrics-import-resume" type="button" data-import-resume hidden>Lanjutkan Upload</button>
<button class="btn metrics-import-resume" type="button" data-import-change hidden>Pilih File Lain</button>
</div>
</form></section>
<section class="upload-card" aria-labelledby="taskforce-title">
<header class="upload-card-header"><span class="upload-icon"><i class="fa fa-bullseye" aria-hidden="true"></i></span><h2 id="taskforce-title">Taskforce</h2><span class="upload-format">Excel / CSV</span></header>
<form class="drop" id="taskforce-upload-form" data-simple-upload method="POST" action="{{ route('surya.taskforce.upload') }}" enctype="multipart/form-data">
@csrf
<div class="upload-fields">
<div><label class="file-label" for="taskforce-month">Bulan</label><input class="upload-input" id="taskforce-month" name="taskforce_month" type="month" value="{{ old('taskforce_month',now('Asia/Jakarta')->format('Y-m')) }}" required>
@error('taskforce_month')<div class="field-error" role="alert">{{ $message }}</div>@enderror</div>
<div><label class="file-label" for="taskforce-file">File taskforce</label><input class="file-input" id="taskforce-file" type="file" name="taskforce_file" accept=".xlsx,.xls,.csv" required>
@error('taskforce_file')<div class="field-error" role="alert">{{ $message }}</div>@enderror</div>
</div>
<p class="upload-hint">Excel / CSV · Maks. 5 MB</p>
<div class="metrics-import-actions"><button class="btn" type="submit"><i class="fa fa-upload" aria-hidden="true"></i> Upload</button><a class="btn metrics-import-resume" href="{{ route('surya.taskforce.template') }}"><i class="fa fa-download" aria-hidden="true"></i> Template</a></div>
<p class="upload-hint" role="status" data-simple-status hidden></p>
</form></section>
<section class="upload-card upload-card-wide" aria-labelledby="outletlist-title">
<header class="upload-card-header"><span class="upload-icon"><i class="fa fa-store" aria-hidden="true"></i></span><h2 id="outletlist-title">Outletlist</h2><span class="upload-format">CSV</span></header>
<form class="drop" id="outletlist-upload-form" data-simple-upload method="POST" action="{{ route('surya.outletlist.upload') }}" enctype="multipart/form-data">
@csrf
<label class="file-label" for="outletlist-file">File outletlist</label><input class="file-input" id="outletlist-file" type="file" name="outletlist_file" accept=".csv,text/csv" aria-describedby="outletlist-hint" required>
<p class="upload-hint" id="outletlist-hint">Header sesuai template · CSV UTF-8 · Maks. 20 MB · Koordinat check-in tetap tersimpan.</p>
@error('outletlist_file')<div class="field-error" role="alert">{{ $message }}</div>@enderror
<div class="metrics-import-actions"><button class="btn" type="submit"><i class="fa fa-upload" aria-hidden="true"></i> Upload Outletlist</button><a class="btn metrics-import-resume" href="{{ route('surya.outletlist.template') }}"><i class="fa fa-download" aria-hidden="true"></i> Template</a></div>
<p class="upload-hint" role="status" data-simple-status hidden></p>
</form></section>
</div></main></div></div>
<script src="{{ asset('js/surya-metrics-upload.js') }}?v={{ filemtime(public_path('js/surya-metrics-upload.js')) }}" defer></script>
<script src="{{ asset('js/surya-simple-upload.js') }}?v={{ filemtime(public_path('js/surya-simple-upload.js')) }}" defer></script>
</body></html>
