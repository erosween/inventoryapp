@extends('layout.mobile_layout')

@section('title', 'Visit PJP')

@section('content')
<div class="glass-card p-4">
    <div class="d-flex align-items-center gap-3 mb-3">
        <div class="rounded-4 p-3 text-white" style="background: var(--primary);"><i class="fas fa-location-dot"></i></div>
        <div><h5 class="fw-bold mb-1">Visit PJP</h5><small class="text-muted">Foto wajib di lokasi outlet.</small></div>
    </div>
    <form method="POST" action="{{ route('mobile.visit.store') }}" enctype="multipart/form-data" id="visit-form">
        @csrf
        <label class="form-label fw-bold">Outlet</label>
        <select name="id_outlet" id="visit-outlet" class="form-select mb-3" required>
            <option value="">Pilih outlet</option>
            @foreach($outlets as $outlet)
                <option value="{{ $outlet->id_outlet }}" data-lat="{{ $outlet->latitude }}" data-lng="{{ $outlet->longitude }}">{{ $outlet->nama_outlet }} ({{ $outlet->id_outlet }})</option>
            @endforeach
        </select>
        <input type="hidden" name="latitude" id="visit-latitude"><input type="hidden" name="longitude" id="visit-longitude"><input type="hidden" name="accuracy" id="visit-accuracy">
        <label class="form-label fw-bold">Foto outlet / branding terbaru</label>
        <input type="file" name="photo" class="form-control mb-3" accept="image/*" capture="environment" required>
        <textarea name="notes" class="form-control mb-3" rows="3" placeholder="Catatan visit (opsional)"></textarea>
        <div id="visit-gps-status" class="alert alert-warning small">Mengambil lokasi GPS...</div>
        <button class="btn btn-danger w-100 rounded-pill py-3 fw-bold" type="submit"><i class="fas fa-camera me-2"></i>Simpan Visit</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
navigator.geolocation?.getCurrentPosition(function(position) {
    $('#visit-latitude').val(position.coords.latitude);
    $('#visit-longitude').val(position.coords.longitude);
    $('#visit-accuracy').val(position.coords.accuracy);
    $('#visit-gps-status').removeClass('alert-warning').addClass('alert-success').text('GPS aktif. Pilih outlet dan ambil foto di lokasi.');
}, function() {
    $('#visit-gps-status').removeClass('alert-warning').addClass('alert-danger').text('GPS wajib diaktifkan untuk melakukan visit.');
}, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
</script>
@endpush
