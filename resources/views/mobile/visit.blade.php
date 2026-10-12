@extends('layout.mobile_layout')

@section('title', 'Visit PJP')

@section('content')
@php($selectedOutlet = request('id_outlet') ? $outlets->firstWhere('id_outlet', request('id_outlet')) : null)
<div class="glass-card p-4">
    <div class="d-flex align-items-center gap-3 mb-3">
        <a href="{{ $selectedOutlet ? route('mobile.visit.menu', ['id_outlet' => $selectedOutlet->id_outlet]) : route('mobile.index') }}" class="visit-back-button" aria-label="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="rounded-4 p-3 text-white" style="background: var(--primary);"><i class="fas fa-location-dot"></i></div>
        <div><h5 class="fw-bold mb-1">Visit PJP</h5><small class="text-muted">Foto wajib di lokasi outlet.</small></div>
    </div>
    <form method="POST" action="{{ route('mobile.visit.store') }}" enctype="multipart/form-data" id="visit-form">
        @csrf
        <label class="form-label fw-bold">Outlet</label>
        @if($selectedOutlet)
            <input type="hidden" name="id_outlet" id="visit-outlet" value="{{ $selectedOutlet->id_outlet }}">
            <div class="form-control mb-3 fw-bold bg-light">{{ $selectedOutlet->nama_outlet }} ({{ $selectedOutlet->id_outlet }})</div>
        @else
            <input type="search" id="outlet-search" class="form-control mb-2" placeholder="Cari ID outlet atau nama outlet...">
            <select name="id_outlet" id="visit-outlet" class="form-select mb-3" required>
                <option value="">Pilih outlet</option>
                @foreach($outlets as $outlet)
                    <option value="{{ $outlet->id_outlet }}" data-lat="{{ $outlet->latitude }}" data-lng="{{ $outlet->longitude }}">{{ $outlet->nama_outlet }} ({{ $outlet->id_outlet }})</option>
                @endforeach
            </select>
        @endif
        <input type="hidden" name="latitude" id="visit-latitude"><input type="hidden" name="longitude" id="visit-longitude"><input type="hidden" name="accuracy" id="visit-accuracy">

        <div class="visit-photo-card mb-3" data-photo-kind="front">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="visit-photo-number">1</span>
                <div>
                    <div class="fw-bold">Foto outlet tampak depan</div>
                    <small class="text-muted">Ambil seluruh bagian depan dan papan nama outlet.</small>
                </div>
            </div>
            <input type="file" name="photo_front" id="visit-photo-front" class="d-none visit-photo-input" accept="image/jpeg" required>
            <div class="camera-stage rounded-4 overflow-hidden bg-dark mb-3 d-none"><video class="visit-camera" autoplay playsinline muted></video></div>
            <canvas class="visit-canvas d-none"></canvas>
            <img class="visit-preview rounded-4 mb-3 d-none" alt="Foto outlet tampak depan">
            <button type="button" class="open-camera btn btn-outline-danger w-100 rounded-pill py-3 fw-bold mb-2"><i class="fas fa-camera me-2"></i>Buka Kamera Depan</button>
            <button type="button" class="capture-photo btn btn-danger w-100 rounded-pill py-3 fw-bold mb-2 d-none"><i class="fas fa-circle-dot me-2"></i>Ambil Foto Depan</button>
            <div class="visit-photo-status small text-muted"><i class="fas fa-circle me-1"></i> Foto tampak depan belum diambil</div>
        </div>

        <div class="visit-photo-card mb-3" data-photo-kind="inside">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="visit-photo-number">2</span>
                <div>
                    <div class="fw-bold">Foto outlet tampak dalam</div>
                    <small class="text-muted">Ambil kondisi bagian dalam, display, dan branding outlet.</small>
                </div>
            </div>
            <input type="file" name="photo_inside" id="visit-photo-inside" class="d-none visit-photo-input" accept="image/jpeg" required>
            <div class="camera-stage rounded-4 overflow-hidden bg-dark mb-3 d-none"><video class="visit-camera" autoplay playsinline muted></video></div>
            <canvas class="visit-canvas d-none"></canvas>
            <img class="visit-preview rounded-4 mb-3 d-none" alt="Foto outlet tampak dalam">
            <button type="button" class="open-camera btn btn-outline-danger w-100 rounded-pill py-3 fw-bold mb-2"><i class="fas fa-camera me-2"></i>Buka Kamera Dalam</button>
            <button type="button" class="capture-photo btn btn-danger w-100 rounded-pill py-3 fw-bold mb-2 d-none"><i class="fas fa-circle-dot me-2"></i>Ambil Foto Dalam</button>
            <div class="visit-photo-status small text-muted"><i class="fas fa-circle me-1"></i> Foto tampak dalam belum diambil</div>
        </div>

        <textarea name="notes" class="form-control mb-3" rows="3" placeholder="Catatan visit (opsional)"></textarea>
        <div id="visit-gps-status" class="alert alert-warning small">Mengambil lokasi GPS...</div>
        <button class="btn btn-danger w-100 rounded-pill py-3 fw-bold" type="submit"><i class="fas fa-camera me-2"></i>Simpan 2 Foto Visit</button>
    </form>
</div>
@endsection

@push('styles')
<style>
    .visit-photo-card {
        padding: 16px;
        border: 1px solid #e2e8f0;
        border-radius: 22px;
        background: #f8fafc;
    }

    .visit-back-button {
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        color: #111827;
        background: #f8fafc;
        text-decoration: none;
        font-size: 1rem;
    }

    .visit-photo-number {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(236, 32, 40, 0.1);
        color: var(--primary);
        font-weight: 900;
    }

    .visit-camera,
    .visit-preview {
        width: 100%;
        max-height: 320px;
        object-fit: cover;
        display: block;
    }
</style>
@endpush

@push('scripts')
<script>
function refreshVisitLocation(message = 'GPS aktif. Lokasi akan dibandingkan dengan koordinat outlet.') {
    return new Promise(function(resolve, reject) {
        if (!navigator.geolocation) {
            $('#visit-gps-status').removeClass('alert-warning alert-success').addClass('alert-danger').text('Perangkat ini tidak mendukung GPS.');
            reject(new Error('GPS tidak tersedia'));
            return;
        }

        navigator.geolocation.getCurrentPosition(function(position) {
            $('#visit-latitude').val(position.coords.latitude);
            $('#visit-longitude').val(position.coords.longitude);
            $('#visit-accuracy').val(position.coords.accuracy);
            $('#visit-gps-status').removeClass('alert-warning alert-danger').addClass('alert-success').text(message);
            resolve(position);
        }, function(error) {
            $('#visit-gps-status').removeClass('alert-warning alert-success').addClass('alert-danger').text('GPS wajib diaktifkan untuk melakukan visit.');
            reject(error);
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    });
}

refreshVisitLocation().catch(function() {});

let visitCameraStream = null;
let activePhotoCard = null;
let visitSubmitting = false;

function stopVisitCamera() {
    if (visitCameraStream) {
        visitCameraStream.getTracks().forEach(track => track.stop());
        visitCameraStream = null;
    }
}

$('.open-camera').on('click', async function() {
    const card = $(this).closest('.visit-photo-card');

    try {
        stopVisitCamera();
        $('.camera-stage, .capture-photo').addClass('d-none');
        $('.open-camera').removeClass('d-none');

        visitCameraStream = await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}},audio:false});
        activePhotoCard = card;
        card.find('.visit-camera').get(0).srcObject = visitCameraStream;
        card.find('.camera-stage, .capture-photo').removeClass('d-none');
        card.find('.visit-preview').addClass('d-none');
        $(this).addClass('d-none');
    } catch (error) {
        card.find('.visit-photo-status').removeClass('text-muted').addClass('text-danger fw-bold').html('<i class="fas fa-circle-exclamation me-1"></i>Kamera wajib diizinkan untuk mengambil foto visit');
    }
});

$('.capture-photo').on('click', async function() {
    const card = $(this).closest('.visit-photo-card');
    const video = card.find('.visit-camera').get(0);
    const canvas = card.find('.visit-canvas').get(0);
    const kind = card.data('photo-kind');
    const label = kind === 'front' ? 'tampak depan' : 'tampak dalam';

    try {
        await refreshVisitLocation('GPS diperbarui sesuai lokasi foto ' + label + '. Lokasi akan diverifikasi saat disimpan.');
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'GPS Tidak Aktif',
            text: 'Aktifkan GPS agar lokasi foto dapat dicatat.',
            confirmButtonColor: 'var(--primary)'
        });
        return;
    }

    if (!video.videoWidth || !video.videoHeight) {
        card.find('.visit-photo-status').removeClass('text-muted').addClass('text-danger fw-bold').html('<i class="fas fa-circle-exclamation me-1"></i>Kamera belum siap. Coba kembali.');
        return;
    }

    canvas.width = video.videoWidth; canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    canvas.toBlob(function(blob) {
        const file = new File([blob], 'visit-' + kind + '-' + Date.now() + '.jpg', {type:'image/jpeg'});
        const transfer = new DataTransfer(); transfer.items.add(file);
        card.find('.visit-photo-input').get(0).files = transfer.files;
        card.find('.visit-preview').attr('src', URL.createObjectURL(blob)).removeClass('d-none');
        card.find('.camera-stage, .capture-photo').addClass('d-none');
        card.find('.open-camera').removeClass('d-none').html('<i class="fas fa-rotate me-2"></i>Ambil Ulang Foto');
        card.find('.visit-photo-status').removeClass('text-muted text-danger').addClass('text-success fw-bold').html('<i class="fas fa-check-circle me-1"></i> Foto ' + label + ' sudah diambil dari kamera');
        stopVisitCamera();
        activePhotoCard = null;
    }, 'image/jpeg', .86);
});

$('#visit-form').on('submit', function(event) {
    if (visitSubmitting) return;

    event.preventDefault();
    const frontReady = document.getElementById('visit-photo-front').files.length > 0;
    const insideReady = document.getElementById('visit-photo-inside').files.length > 0;

    if (!frontReady || !insideReady) {
        const missing = !frontReady && !insideReady
            ? 'foto tampak depan dan tampak dalam'
            : (!frontReady ? 'foto tampak depan' : 'foto tampak dalam');
        Swal.fire({
            icon: 'warning',
            title: 'Foto Belum Lengkap',
            text: 'Silakan ambil ' + missing + ' langsung dari kamera.',
            confirmButtonColor: 'var(--primary)'
        });
        return;
    }

    const form = this;
    const submitButton = $(form).find('button[type="submit"]');
    submitButton.prop('disabled', true).html('<i class="fas fa-location-crosshairs me-2"></i>Memverifikasi Lokasi...');

    refreshVisitLocation('GPS terbaru siap. Lokasi akan dibandingkan dengan koordinat outlet.')
        .then(function() {
            visitSubmitting = true;
            form.requestSubmit();
        })
        .catch(function() {
            submitButton.prop('disabled', false).html('<i class="fas fa-camera me-2"></i>Simpan 2 Foto Visit');
            Swal.fire({
                icon: 'error',
                title: 'Lokasi Belum Tersedia',
                text: 'Aktifkan GPS dan izinkan akses lokasi sebelum menyimpan visit.',
                confirmButtonColor: 'var(--primary)'
            });
        });
});

window.addEventListener('pagehide', stopVisitCamera);

$('#outlet-search').on('input', function() {
    const keyword = $(this).val().toLowerCase().trim();
    $('#visit-outlet option').each(function() {
        if (!this.value) return;
        const visible = !keyword || $(this).text().toLowerCase().includes(keyword) || this.value.toLowerCase().includes(keyword);
        $(this).toggle(visible);
    });
});

</script>
@endpush
