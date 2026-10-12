@extends('layout.mobile_layout')
@section('title', $title)
@section('content')
<div class="glass-card p-4"><h5 class="fw-bold mb-1">{{ $title }}</h5><p class="text-muted small mb-4">Dicatat otomatis atas nama {{ session('mobile_sf_name') }}.</p>
<form method="POST" action="{{ route('mobile.surya.store', $type) }}">@csrf
<label class="form-label fw-bold">Outlet</label><select name="id_outlet" class="form-select mb-3" required><option value="">Pilih outlet</option>@foreach($outlets as $outlet)<option value="{{ $outlet->id_outlet }}">{{ $outlet->nama_outlet }} ({{ $outlet->id_outlet }})</option>@endforeach</select>
@if($type === 'consignment')<label class="form-label fw-bold">Nomor perdana SA</label><input name="serial_number" class="form-control mb-3" maxlength="30" required placeholder="Masukkan nomor SA">@endif
@if($type === 'survey')<label class="form-label fw-bold">Operator</label><input name="operator" class="form-control mb-3" required><label class="form-label fw-bold">Produk / paket</label><input name="product" class="form-control mb-3"><label class="form-label fw-bold">Estimasi stok</label><input name="estimated_stock" type="number" class="form-control mb-3" min="0">@endif
@if($type === 'network')<label class="form-label fw-bold">Operator</label><select name="operator" class="form-select mb-3" required><option>TELKOMSEL</option><option>INDOSAT</option><option>XL</option><option>TRI</option><option>SMARTFREN</option></select><label class="form-label fw-bold">Status sinyal</label><select name="signal_status" class="form-select mb-3" required><option>BAIK</option><option>SEDANG</option><option>BURUK</option><option>TIDAK ADA</option></select><label class="form-label fw-bold">Status data</label><input name="data_status" class="form-control mb-3">@endif
<label class="form-label fw-bold">Catatan</label><textarea name="notes" class="form-control mb-4" rows="3"></textarea><button class="btn btn-danger rounded-pill w-100 py-3 fw-bold">Simpan Data</button></form></div>
@endsection
