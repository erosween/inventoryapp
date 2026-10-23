@extends('layout.mobile_layout')

@section('title', 'Visit PJP')

@section('content')
@php($selectedOutlet = request('id_outlet') ? $outlets->firstWhere('id_outlet', request('id_outlet')) : null)
@php($mode = $mode ?? request('mode', request()->filled('id_outlet') ? 'pjp' : 'extra_call'))
@php($isHelper = str_starts_with(strtoupper((string) session('mobile_sf_id')), 'HELPER'))
@php($modeTitle = match($mode) { 'pjp' => 'Visit PJP', 'non_digipos' => 'Outlet Non Digipos', 'digipos_unmapped' => 'Outlet Digipos Unmapping', default => 'Extra Call (Outlet PJP)' })
<div class="glass-card p-4">
    <div class="d-flex align-items-center gap-3 mb-3">
        <a href="{{ $selectedOutlet ? route('mobile.visit.menu', ['id_outlet' => $selectedOutlet->id_outlet]) : route('mobile.index') }}" class="visit-back-button" aria-label="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="rounded-4 p-3 text-white" style="background: var(--primary);"><i class="fas fa-location-dot"></i></div>
        <div><h5 class="fw-bold mb-1">{{ $modeTitle }}</h5><small class="text-muted">Foto wajib di lokasi outlet.</small></div>
    </div>
    <form method="POST" action="{{ route('mobile.visit.store') }}" enctype="multipart/form-data" id="visit-form">
        @csrf
        <input type="hidden" name="visit_type" value="{{ $mode }}">
        <label class="form-label fw-bold">{{ $mode === 'non_digipos' ? 'Data Outlet Non Digipos' : $modeTitle }}</label>
        @if($mode === 'non_digipos')
            <input type="hidden" name="id_outlet" value="NON-DIGIPOS">
            <input name="outlet_name" class="form-control mb-2" required placeholder="Nama toko">
            <input name="owner_name" class="form-control mb-2" required placeholder="Nama owner">
            <input name="phone_number" class="form-control mb-2" required inputmode="tel" placeholder="Nomor hape">
            <textarea name="outlet_address" class="form-control mb-3" required rows="2" placeholder="Alamat toko"></textarea>
        @elseif($selectedOutlet)
            <input type="hidden" name="id_outlet" id="visit-outlet" value="{{ $selectedOutlet->id_outlet }}">
            <div class="form-control mb-3 fw-bold bg-light">{{ $selectedOutlet->nama_outlet }} ({{ $selectedOutlet->id_outlet }})</div>
        @else
            <input type="hidden" name="id_outlet" id="visit-outlet" required>
            <div class="outlet-picker mb-3" id="outlet-picker">
                <button type="button" class="outlet-picker-button" id="outlet-picker-button"><span><i class="fas fa-store me-2"></i>Pilih outlet Extra Call</span><i class="fas fa-chevron-down"></i></button>
                <div class="outlet-picker-menu d-none" id="outlet-picker-menu">
                    <div class="outlet-picker-search"><i class="fas fa-search"></i><input type="search" id="outlet-search" placeholder="Cari nama atau ID outlet..." autocomplete="off"></div>
                    <div class="outlet-picker-options" id="outlet-picker-options">
                @foreach($outlets as $outlet)
                    <button type="button" class="outlet-picker-option" data-value="{{ $outlet->id_outlet }}" data-label="{{ $outlet->nama_outlet }}" data-search="{{ strtolower($outlet->nama_outlet.' '.$outlet->id_outlet) }}"><span><strong>{{ $outlet->nama_outlet }}</strong><small>{{ $outlet->id_outlet }}</small></span><i class="fas fa-check"></i></button>
                @endforeach
                    </div>
                </div>
            </div>
        @endif
        @if(in_array($mode, ['digipos_unmapped', 'non_digipos'], true) && $isHelper)
            <label class="form-label fw-bold">SF Pemilik Stok</label>
            <select name="stock_owner_idsf" class="form-select mb-3" required>
                <option value="">Pilih SF yang stoknya akan dipotong</option>
                @foreach($stockOwners as $stockOwner)
                    <option value="{{ $stockOwner->idsf }}" @selected(old('stock_owner_idsf') === $stockOwner->idsf)>{{ $stockOwner->namasf }} ({{ $stockOwner->idsf }})</option>
                @endforeach
            </select>
            <div class="small text-muted mb-3">Stok penjualan akan menggunakan dan memotong stok SF yang dipilih.</div>
        @endif
        <input type="hidden" name="latitude" id="visit-latitude"><input type="hidden" name="longitude" id="visit-longitude"><input type="hidden" name="accuracy" id="visit-accuracy">

        <label class="form-label fw-bold">Catatan Visit <span class="text-muted fw-normal">(opsional)</span></label>
        <textarea name="notes" class="form-control mb-3" rows="3" placeholder="Tulis catatan jika ada..."></textarea>

        <div class="visit-photo-card mb-3" data-photo-kind="front">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="visit-photo-number">1</span>
                <div>
                    <div class="fw-bold">Foto outlet tampak depan</div>
                    <small class="text-muted">Ambil seluruh bagian depan dan papan nama outlet.</small>
                </div>
            </div>
            <input type="file" name="photo_front" id="visit-photo-front" class="d-none visit-photo-input" accept="image/jpeg,image/webp" required>
            <div class="camera-stage position-relative rounded-4 overflow-hidden bg-dark mb-3 d-none">
                <video class="visit-camera" autoplay playsinline muted></video>
                <button type="button" class="switch-camera btn btn-light btn-sm rounded-pill" aria-label="Ganti kamera depan atau belakang">
                    <i class="fas fa-camera-rotate me-1"></i><span>Ganti Kamera</span>
                </button>
            </div>
            <canvas class="visit-canvas d-none"></canvas>
            <img class="visit-preview rounded-4 mb-3 d-none" alt="Foto outlet tampak depan">
            <div class="visit-camera-actions mb-2">
                <button type="button" class="open-camera camera-action btn btn-outline-danger rounded-pill fw-bold"><i class="fas fa-camera me-2"></i>Buka Kamera</button>
                <button type="button" class="capture-photo camera-action btn btn-danger rounded-pill fw-bold d-none"><i class="fas fa-circle-dot me-2"></i>Ambil Foto</button>
            </div>
            <div class="visit-photo-status small text-muted"><i class="fas fa-circle me-1"></i> Foto tampak depan belum diambil</div>
        </div>

        <div class="visit-photo-card mb-3" data-photo-kind="inside">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="visit-photo-number">2</span>
                <div>
                    <div class="fw-bold">Foto etalase outlet</div>
                    <small class="text-muted">Ambil foto etalase, display, dan branding outlet.</small>
                </div>
            </div>
            <input type="file" name="photo_inside" id="visit-photo-inside" class="d-none visit-photo-input" accept="image/jpeg,image/webp" required>
            <div class="camera-stage position-relative rounded-4 overflow-hidden bg-dark mb-3 d-none">
                <video class="visit-camera" autoplay playsinline muted></video>
                <button type="button" class="switch-camera btn btn-light btn-sm rounded-pill" aria-label="Ganti kamera depan atau belakang">
                    <i class="fas fa-camera-rotate me-1"></i><span>Ganti Kamera</span>
                </button>
            </div>
            <canvas class="visit-canvas d-none"></canvas>
            <img class="visit-preview rounded-4 mb-3 d-none" alt="Foto etalase outlet">
            <div class="visit-camera-actions mb-2">
                <button type="button" class="open-camera camera-action btn btn-outline-danger rounded-pill fw-bold"><i class="fas fa-camera me-2"></i>Buka Kamera</button>
                <button type="button" class="capture-photo camera-action btn btn-danger rounded-pill fw-bold d-none"><i class="fas fa-circle-dot me-2"></i>Ambil Foto</button>
            </div>
            <div class="visit-photo-status small text-muted"><i class="fas fa-circle me-1"></i> Foto etalase belum diambil</div>
        </div>

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
    .outlet-picker { position: relative; z-index: 20; }
    .outlet-picker-button { width: 100%; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; color: #334155; padding: 13px 14px; font: inherit; font-size: .78rem; font-weight: 800; text-align: left; }
    .outlet-picker-button.is-selected { border-color: #fecaca; color: #991b1b; background: #fffafa; }
    .outlet-picker-menu { position: absolute; left: 0; right: 0; top: calc(100% + 7px); overflow: hidden; border: 1px solid #e2e8f0; border-radius: 16px; background: #fff; box-shadow: 0 16px 35px rgba(15,23,42,.16); }
    .outlet-picker-search { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-bottom: 1px solid #f1f5f9; color: #94a3b8; }
    .outlet-picker-search input { width: 100%; border: 0; outline: 0; font: inherit; font-size: .75rem; }
    .outlet-picker-options { max-height: 260px; overflow-y: auto; padding: 5px; }
    .outlet-picker-option { width: 100%; display: flex; justify-content: space-between; align-items: center; gap: 8px; border: 0; border-radius: 11px; background: transparent; padding: 10px; text-align: left; }
    .outlet-picker-option:hover, .outlet-picker-option:focus { background: #fff1f2; outline: 0; }
    .outlet-picker-option strong, .outlet-picker-option small { display: block; }
    .outlet-picker-option strong { color: #1e293b; font-size: .72rem; }
    .outlet-picker-option small { margin-top: 2px; color: #94a3b8; font-size: .62rem; }
    .outlet-picker-option > i { color: #dc2626; opacity: 0; }
    .outlet-picker-option.selected > i { opacity: 1; }

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

    .visit-camera-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .camera-action {
        width: auto !important;
        min-height: 40px;
        padding: 8px 16px !important;
        font-size: 0.78rem;
    }

    .switch-camera {
        position: absolute;
        right: 10px;
        bottom: 10px;
        z-index: 2;
        min-height: 36px;
        padding: 7px 12px;
        font-size: 0.7rem;
        font-weight: 800;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.24);
    }

    .visit-camera.is-front-camera {
        transform: scaleX(-1);
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
let visitFacingMode = 'environment';

function stopVisitCamera() {
    if (visitCameraStream) {
        visitCameraStream.getTracks().forEach(track => track.stop());
        visitCameraStream = null;
    }
}

async function startVisitCamera(card, facingMode) {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        throw new Error('Kamera tidak didukung perangkat ini.');
    }

    stopVisitCamera();
    visitCameraStream = await navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: {ideal: facingMode},
            width: {ideal: 1920},
            height: {ideal: 1080}
        },
        audio: false
    });

    visitFacingMode = facingMode;
    activePhotoCard = card;
    const video = card.find('.visit-camera').get(0);
    video.srcObject = visitCameraStream;
    video.classList.toggle('is-front-camera', facingMode === 'user');
    card.find('.camera-stage, .capture-photo').removeClass('d-none');
    card.find('.visit-preview').addClass('d-none');
    card.find('.switch-camera span').text(facingMode === 'user' ? 'Pakai Kamera Belakang' : 'Pakai Kamera Depan');
}

$('.open-camera').on('click', async function() {
    const card = $(this).closest('.visit-photo-card');

    try {
        stopVisitCamera();
        $('.camera-stage, .capture-photo').addClass('d-none');
        $('.open-camera:not(:disabled)').removeClass('d-none');

        await startVisitCamera(card, 'environment');
        $(this).addClass('d-none');
    } catch (error) {
        card.find('.visit-photo-status').removeClass('text-muted').addClass('text-danger fw-bold').html('<i class="fas fa-circle-exclamation me-1"></i>Kamera wajib diizinkan untuk mengambil foto visit');
    }
});

$('.switch-camera').on('click', async function() {
    const card = $(this).closest('.visit-photo-card');
    const previousFacingMode = visitFacingMode;
    const nextFacingMode = visitFacingMode === 'environment' ? 'user' : 'environment';
    const button = $(this);

    button.prop('disabled', true).find('span').text('Mengganti...');
    try {
        await startVisitCamera(card, nextFacingMode);
    } catch (error) {
        try {
            await startVisitCamera(card, previousFacingMode);
        } catch (restoreError) {
            card.find('.camera-stage, .capture-photo').addClass('d-none');
            card.find('.open-camera:not(:disabled)').removeClass('d-none');
        }
        card.find('.visit-photo-status').removeClass('text-muted').addClass('text-danger fw-bold').html('<i class="fas fa-circle-exclamation me-1"></i>Kamera lain tidak tersedia di perangkat ini');
    } finally {
        button.prop('disabled', false);
    }
});

$('.capture-photo').on('click', async function() {
    const card = $(this).closest('.visit-photo-card');
    const video = card.find('.visit-camera').get(0);
    const canvas = card.find('.visit-canvas').get(0);
    const kind = card.data('photo-kind');
    const label = kind === 'front' ? 'tampak depan' : 'etalase';

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

    // Some mobile browsers expose the camera dimensions before the first
    // decoded frame is actually available. Waiting for a real frame prevents
    // an otherwise valid-looking capture from becoming a black image.
    if (video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
        await new Promise(resolve => setTimeout(resolve, 250));
    }
    if (typeof video.play === 'function') {
        try { await video.play(); } catch (e) {}
    }
    await new Promise(resolve => setTimeout(resolve, 180));

    const maxEdge = 1600;
    const scale = Math.min(1, maxEdge / Math.max(video.videoWidth, video.videoHeight));
    canvas.width = Math.max(1, Math.round(video.videoWidth * scale));
    canvas.height = Math.max(1, Math.round(video.videoHeight * scale));
    const context = canvas.getContext('2d');
    if (visitFacingMode === 'user') {
        context.translate(canvas.width, 0);
        context.scale(-1, 1);
    }
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    const sample = context.getImageData(0, 0, canvas.width, canvas.height).data;
    let darkPixels = 0;
    const sampleStep = Math.max(4, Math.floor(sample.length / 2400));
    let sampleCount = 0;
    for (let index = 0; index < sample.length; index += sampleStep * 4) {
        const luma = (0.299 * sample[index]) + (0.587 * sample[index + 1]) + (0.114 * sample[index + 2]);
        if (luma < 10) darkPixels++;
        sampleCount++;
    }
    if (sampleCount && darkPixels / sampleCount >= 0.92) {
        card.find('.visit-photo-status').removeClass('text-muted').addClass('text-danger fw-bold').html('<i class="fas fa-circle-exclamation me-1"></i>Foto hitam atau terlalu gelap. Silakan ambil ulang.');
        stopVisitCamera();
        return;
    }
    const usePhotoBlob = function(blob) {
        if (!blob) {
            card.find('.visit-photo-status').removeClass('text-muted').addClass('text-danger fw-bold').html('<i class="fas fa-circle-exclamation me-1"></i>Foto gagal dikompresi. Silakan buka kamera kembali.');
            return;
        }

        const isWebp = blob.type === 'image/webp';
        const file = new File([blob], 'visit-' + kind + '-' + Date.now() + (isWebp ? '.webp' : '.jpg'), {type: isWebp ? 'image/webp' : 'image/jpeg'});
        const transfer = new DataTransfer(); transfer.items.add(file);
        card.find('.visit-photo-input').get(0).files = transfer.files;
        card.find('.visit-preview').attr('src', URL.createObjectURL(blob)).removeClass('d-none');
        card.find('.camera-stage, .capture-photo').addClass('d-none');
        card.find('.open-camera').addClass('d-none').prop('disabled', true);
        card.find('.visit-photo-status').removeClass('text-muted text-danger').addClass('text-success fw-bold').html('<i class="fas fa-check-circle me-1"></i> Foto ' + label + ' sudah diambil dan dikompresi');
        stopVisitCamera();
        activePhotoCard = null;
    };

    // Use JPEG for the upload. WebP encoding is inconsistent on mobile
    // browsers; the server already normalizes the stored image to WebP.
    canvas.toBlob(usePhotoBlob, 'image/jpeg', .86);
});

$('#visit-form').on('submit', function(event) {
    if (visitSubmitting) return;

    event.preventDefault();
    const frontReady = document.getElementById('visit-photo-front').files.length > 0;
    const insideReady = document.getElementById('visit-photo-inside').files.length > 0;

    if (!frontReady || !insideReady) {
        const missing = !frontReady && !insideReady
            ? 'foto tampak depan dan foto etalase'
            : (!frontReady ? 'foto tampak depan' : 'foto etalase');
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

$('#outlet-picker-button').on('click', function() {
    $('#outlet-picker-menu').toggleClass('d-none');
    if (!$('#outlet-picker-menu').hasClass('d-none')) $('#outlet-search').trigger('focus');
});
$('#outlet-search').on('input', function() {
    const keyword = $(this).val().toLowerCase().trim();
    $('.outlet-picker-option').each(function() {
        $(this).toggle(!keyword || String($(this).data('search')).includes(keyword));
    });
});
$('.outlet-picker-option').on('click', function() {
    const value = $(this).data('value');
    const label = $(this).data('label');
    $('#visit-outlet').val(value);
    $('#outlet-picker-button').addClass('is-selected').find('span').html('<i class="fas fa-store me-2"></i>' + $('<div>').text(label).html() + '<small class="d-block text-muted mt-1">' + value + '</small>');
    $('.outlet-picker-option').removeClass('selected');
    $(this).addClass('selected');
    $('#outlet-picker-menu').addClass('d-none');
});
$(document).on('click', function(event) {
    if (!$(event.target).closest('#outlet-picker').length) $('#outlet-picker-menu').addClass('d-none');
});

</script>
@endpush
