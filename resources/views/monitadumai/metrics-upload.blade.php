<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Transaksi SURYA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root{font-family:Inter,system-ui,sans-serif;color:#162033;background:#eef3f8}*{box-sizing:border-box}body{margin:0}.top{height:76px;background:#fff;border-bottom:1px solid #e5eaf0;display:flex;align-items:center;justify-content:space-between;padding:0 5%;}.brand{display:flex;align-items:center;gap:12px;font-weight:900;font-size:20px}.brand img{width:42px;height:42px;border-radius:12px}.back{color:#d10b20;text-decoration:none;font-weight:800}.wrap{max-width:860px;margin:48px auto;padding:0 22px}.card{background:#fff;border-radius:22px;padding:30px;box-shadow:0 14px 40px #182b4212}.eyebrow{color:#d10b20;letter-spacing:.12em;font-size:12px;font-weight:900}.title{font-size:32px;margin:8px 0}.muted{color:#667085}.drop{margin-top:26px;border:2px dashed #d8dee8;border-radius:18px;padding:26px;background:#fbfcfe}.drop input{display:block;margin-top:14px;width:100%;padding:13px;border:1px solid #d8dee8;border-radius:10px}.btn{margin-top:20px;border:0;border-radius:12px;padding:14px 20px;background:#d10b20;color:#fff;font-weight:900;cursor:pointer}.alert{margin-bottom:18px;padding:14px;border-radius:12px;background:#ecfdf3;color:#087443;font-weight:700}.info{margin-top:20px;padding:16px;border-radius:14px;background:#f5f8fc;color:#475467;font-size:14px;line-height:1.55}
    </style>
</head>
<body>
<header class="top"><div class="brand"><img src="/assets/img/surya-logo-mobile.webp" alt="SURYA">SURYA</div><a class="back" href="{{ route('surya.dashboard') }}"><i class="fa fa-arrow-left"></i> Kembali ke dashboard</a></header>
<main class="wrap"><section class="card">
    @if(session('success'))<div class="alert"><i class="fa fa-check-circle"></i> {{ session('success') }}</div>@endif
    <div class="eyebrow">SURYA DATA MANAGEMENT</div><h1 class="title">Upload Transaksi Outlet</h1><p class="muted">Upload file Excel manual untuk memperbarui transaksi CVM, Super Seru, Hot Promo, Comsak, Insak, dan Digital.</p>
    <form class="drop" method="POST" action="{{ route('surya.transaction-upload.store') }}" enctype="multipart/form-data">@csrf
        <strong><i class="fa fa-file-excel"></i> Pilih file upload.xlsx</strong>
        <input type="file" name="metrics_file" accept=".xlsx,.xls,.csv" required>
        @error('metrics_file')<div style="color:#b42318;margin-top:8px">{{ $message }}</div>@enderror
        <button class="btn" type="submit"><i class="fa fa-upload"></i> Upload dan Simpan</button>
    </form>
    <div class="info"><strong>Header wajib:</strong> <code>id_outlet</code>, kolom KPI dengan pola <code>_fm1</code>, <code>_m1</code>, <code>_m</code>, dan kolom <code>update</code>. Hanya user <strong>admin_super</strong> yang dapat membuka halaman ini.</div>
</section></main>
</body></html>
