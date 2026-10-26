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
            <div class="branding-survey mt-3">
                <div class="branding-survey-title"><i class="fas fa-tags"></i><span>Survey Branding <small>Wajib diisi</small></span></div>
                <div class="branding-question">
                    <div class="branding-label">Dominasi Branding</div>
                    <div class="branding-options branding-dominance-options">
                        @foreach(['Tsel', 'Indosat-Tri', 'XL-Smart', 'No Branding'] as $branding)
                            <label class="branding-option">
                                <input type="radio" name="branding_dominance" value="{{ $branding }}" @checked(old('branding_dominance') === $branding) required>
                                <span>{{ $branding }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="branding-question branding-quality-question" id="branding-quality-question">
                    <div class="branding-label">Kelayakan Branding</div>
                    <div class="branding-options">
                        @foreach(['Bagus', 'Kurang Bagus', 'Tidak Bagus'] as $quality)
                            <label class="branding-option">
                                <input type="radio" name="branding_quality" value="{{ $quality }}" @checked(old('branding_quality') === $quality) required>
                                <span>{{ $quality }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
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
        <div id="visit-upload-status" class="alert alert-light border small d-none" role="status" aria-live="polite">
            <div id="visit-upload-message" class="mb-2"></div>
            <progress id="visit-upload-progress" class="w-100" max="100" value="0" aria-label="Progres upload foto"></progress>
            <a id="visit-upload-check" class="d-none d-block mt-2 fw-bold text-danger" href="{{ route('mobile.visit.menu') }}" data-menu-url="{{ route('mobile.visit.menu') }}">Cek status visit</a>
        </div>
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
    #visit-upload-progress { display: block; height: 8px; accent-color: var(--primary); }
    .branding-survey { padding: 15px; border: 1px solid #fecaca; border-radius: 18px; background: #fff7f7; }
    .branding-survey-title { display: flex; align-items: center; gap: 9px; color: #991b1b; font-size: .78rem; font-weight: 900; }
    .branding-survey-title i { width: 29px; height: 29px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; color: #fff; background: #ec2028; }
    .branding-survey-title small { display: block; margin-top: 2px; color: #b45309; font-size: .62rem; font-weight: 800; }
    .branding-question { margin-top: 14px; }
    .branding-label { margin-bottom: 7px; color: #334155; font-size: .7rem; font-weight: 900; }
    .branding-options { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 7px; }
    .branding-option { position: relative; cursor: pointer; }
    .branding-option input { position: absolute; opacity: 0; pointer-events: none; }
    .branding-option span { min-height: 43px; display: flex; align-items: center; justify-content: center; padding: 7px 5px; border: 1px solid #e2e8f0; border-radius: 12px; color: #64748b; background: #fff; font-size: .66rem; font-weight: 800; line-height: 1.15; text-align: center; transition: .16s ease; }
    .branding-option input:checked + span { border-color: #ec2028; color: #991b1b; background: #fff1f2; box-shadow: 0 0 0 2px rgba(236,32,40,.12); }
    .branding-option input:focus-visible + span { outline: 3px solid rgba(59,130,246,.25); outline-offset: 2px; }
    .branding-dominance-options .branding-option:last-child span { color:#64748b; background:#f1f5f9; }
    .branding-dominance-options .branding-option:last-child input:checked + span { border-color:#64748b; color:#334155; background:#e2e8f0; box-shadow:0 0 0 2px rgba(100,116,139,.14); }
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
<script src="{{ asset('js/surya-visit-upload.js') }}?v={{ filemtime(public_path('js/surya-visit-upload.js')) }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const qualityQuestion = document.getElementById('branding-quality-question');
        const qualityInputs = qualityQuestion?.querySelectorAll('input[name="branding_quality"]') || [];
        const dominanceInputs = document.querySelectorAll('input[name="branding_dominance"]');
        const syncBrandingQuality = () => {
            const noBranding = document.querySelector('input[name="branding_dominance"]:checked')?.value === 'No Branding';
            if (!qualityQuestion) return;
            qualityQuestion.classList.toggle('d-none', noBranding);
            qualityInputs.forEach(input => {
                input.required = !noBranding;
                if (noBranding) input.checked = false;
            });
        };
        dominanceInputs.forEach(input => input.addEventListener('change', syncBrandingQuality));
        syncBrandingQuality();
    });
</script>
<script>
let visitGpsPromise = null;
let visitGpsWatch = null;
let visitGpsWatchVersion = 0;
let visitLastPosition = null;
let visitGpsDenied = false;
let visitPermissionVersion = 0;
const visitPhotoLocations = {};
function rememberVisitPhotoLocation(kind, position) {
    visitPhotoLocations[kind] = {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy: position.coords.accuracy,
    };
}
function applyVisitPhotoLocation() {
    const location = visitPhotoLocations.front;
    if (!location) return false;
    $('#visit-latitude').val(location.latitude);
    $('#visit-longitude').val(location.longitude);
    $('#visit-accuracy').val(location.accuracy);
    return true;
}
function isFreshVisitPosition(position, maxAge = 30000) {
    const lat = position?.coords?.latitude, lng = position?.coords?.longitude;
    return Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180
        && (lat !== 0 || lng !== 0) && Number.isFinite(position.timestamp)
        && Date.now() - position.timestamp <= maxAge && position.timestamp - Date.now() <= 5000;
}
function visitGpsProblem(error) {
    if (error?.code === 'unsupported') return {
        title: 'Lokasi Tidak Didukung',
        message: 'Browser ini tidak menyediakan akses lokasi. Buka SURYA melalui HTTPS di browser yang mendukung lokasi.',
    };
    if (error?.code === 1) return {
        title: 'Izin Lokasi Diblokir',
        message: 'Izinkan lokasi untuk SURYA di pengaturan browser, aktifkan lokasi presisi, lalu coba ambil foto lagi.',
    };
    if (error?.code === 3) return {
        title: 'Lokasi Belum Terbaca',
        message: 'Browser terlalu lama memperoleh lokasi. GPS belum tentu mati. Tetap di outlet, coba mendekat ke area terbuka lalu tekan Ambil Foto lagi.',
    };
    if (error?.code === 'stale') return {
        title: 'Lokasi Belum Diperbarui',
        message: 'Browser memberikan lokasi lama atau tidak valid. Tetap di outlet dan coba Ambil Foto lagi dengan lokasi presisi aktif.',
    };
    return {
        title: 'Lokasi Belum Tersedia',
        message: 'Browser belum bisa membaca lokasi. Periksa layanan lokasi dan lokasi presisi, lalu coba Ambil Foto lagi. Foto yang sudah diambil tetap ada.',
    };
}
function showVisitPosition(position, message) {
    // A slower one-shot callback must not replace a newer camera GPS fix.
    if (!isFreshVisitPosition(visitLastPosition) || position.timestamp >= visitLastPosition.timestamp) {
        visitLastPosition = position;
    }
    visitGpsDenied = false;
    const latest = visitLastPosition;
    $('#visit-latitude').val(latest.coords.latitude);
    $('#visit-longitude').val(latest.coords.longitude);
    $('#visit-accuracy').val(latest.coords.accuracy);
    const accuracy = Number.isFinite(latest.coords.accuracy) ? ' · akurasi ±' + Math.round(latest.coords.accuracy) + ' m' : '';
    $('#visit-gps-status').removeClass('alert-warning alert-danger').addClass('alert-success')
        .text(message + ' GPS: ' + latest.coords.latitude.toFixed(6) + ', ' + latest.coords.longitude.toFixed(6) + accuracy);
    return latest;
}
function stopVisitGpsWatch() {
    visitGpsWatchVersion++;
    if (visitGpsWatch !== null) navigator.geolocation?.clearWatch(visitGpsWatch);
    visitGpsWatch = null;
}
function handleVisitPermissionDenied() {
    visitPermissionVersion++;
    visitGpsDenied = true;
    visitLastPosition = null;
    stopVisitGpsWatch();
    $('#visit-latitude, #visit-longitude, #visit-accuracy').val('');
}
function startVisitGpsWatch() {
    if (!navigator.geolocation?.watchPosition || visitGpsWatch !== null) return;
    const version = ++visitGpsWatchVersion;
    visitGpsWatch = navigator.geolocation.watchPosition(function(position) {
        if (version !== visitGpsWatchVersion || !isFreshVisitPosition(position)) return;
        showVisitPosition(position, 'Lokasi kamera siap.');
    }, function(error) {
        if (version !== visitGpsWatchVersion) return;
        if (error.code === 1) {
            handleVisitPermissionDenied();
            $('#visit-gps-status').removeClass('alert-success alert-warning').addClass('alert-danger').text(visitGpsProblem(error).message);
        }
        // Temporary watch timeouts do not invalidate a photo or claim GPS is off.
    }, {enableHighAccuracy: true, timeout: 15000, maximumAge: 0});
}
function refreshVisitLocation(message = 'GPS aktif. Lokasi akan dibandingkan dengan koordinat outlet.') {
    // Share an in-flight reading instead of making photo capture cancel the
    // page's initial GPS request (or another tap on Ambil Foto).
    if (visitGpsPromise) return visitGpsPromise;
    $('#visit-latitude, #visit-longitude, #visit-accuracy').val('');
    const pending = new Promise(function(resolve, reject) {
        if (!navigator.geolocation) {
            const error = Object.assign(new Error('Perangkat ini tidak mendukung lokasi browser.'), {code: 'unsupported'});
            $('#visit-gps-status').removeClass('alert-warning alert-success').addClass('alert-danger').text(visitGpsProblem(error).message);
            reject(error);
            return;
        }
        let attempts = 0;
        function failed(error) {
            if (error.code === 1) handleVisitPermissionDenied();
            if (error.code !== 1 && ++attempts < 2) {
                $('#visit-gps-status').removeClass('alert-success alert-danger').addClass('alert-warning').text('Lokasi belum terbaca. Mencoba GPS sekali lagi…');
                read();
                return;
            }
            $('#visit-gps-status').removeClass('alert-warning alert-success').addClass('alert-danger').text(visitGpsProblem(error).message);
            reject(error);
        }
        function read() {
            const permissionVersion = visitPermissionVersion;
            navigator.geolocation.getCurrentPosition(function(position) {
                if (permissionVersion !== visitPermissionVersion) {
                    failed({code: 1});
                    return;
                }
                if (!isFreshVisitPosition(position)) {
                    failed(Object.assign(new Error('Pembacaan GPS tidak valid atau sudah kedaluwarsa.'), {code: 'stale'}));
                    return;
                }
                resolve(showVisitPosition(position, message));
            }, failed, {enableHighAccuracy: true, timeout: attempts ? 10000 : 12000, maximumAge: 0});
        }
        $('#visit-gps-status').removeClass('alert-success alert-danger').addClass('alert-warning').text('Membaca lokasi terbaru…');
        read();
    });
    visitGpsPromise = pending.finally(function() { visitGpsPromise = null; });
    return visitGpsPromise;
}
async function getVisitPhotoLocation(message) {
    // Reuse only a recent measured camera fix, never outlet master coordinates.
    if (!visitGpsDenied && isFreshVisitPosition(visitLastPosition, 10000)) {
        return showVisitPosition(visitLastPosition, message);
    }
    try {
        return await refreshVisitLocation(message);
    } catch (error) {
        // A camera watch may have obtained a fresh fix while the one-shot read
        // timed out. Permission denial always prevents this fallback.
        if (error.code !== 1 && !visitGpsDenied && isFreshVisitPosition(visitLastPosition, 10000)) {
            return showVisitPosition(visitLastPosition, message);
        }
        throw error;
    }
}

refreshVisitLocation().catch(function() {});

let visitCameraStream = null;
let activePhotoCard = null;
let visitSubmitting = false;
let visitCapturing = false;
let visitFacingMode = 'environment';

function stopVisitCamera() {
    stopVisitGpsWatch();
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
    startVisitGpsWatch();
}

$('.open-camera').on('click', async function() {
    if (visitCapturing) return;
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
    if (visitCapturing) return;
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

async function captureVisitPhoto(card) {
    const video = card.find('.visit-camera').get(0);
    const canvas = card.find('.visit-canvas').get(0);
    const kind = card.data('photo-kind');
    const label = kind === 'front' ? 'tampak depan' : 'etalase';
    let photoPosition;

    try {
        photoPosition = await getVisitPhotoLocation('Lokasi foto ' + label + ' tercatat. Lokasi akan diverifikasi saat disimpan.');
    } catch (error) {
        const problem = visitGpsProblem(error);
        Swal.fire({
            icon: error.code === 1 ? 'error' : 'warning',
            title: problem.title,
            text: problem.message,
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
        rememberVisitPhotoLocation(kind, photoPosition);
        card.find('.visit-preview').attr('src', URL.createObjectURL(blob)).removeClass('d-none');
        card.find('.camera-stage, .capture-photo').addClass('d-none');
        card.find('.open-camera').addClass('d-none').prop('disabled', true);
        card.find('.visit-photo-status').removeClass('text-muted text-danger').addClass('text-success fw-bold').html('<i class="fas fa-check-circle me-1"></i> Foto ' + label + ' sudah diambil dan dikompresi');
        stopVisitCamera();
        activePhotoCard = null;
    };

    // Use JPEG for the upload. WebP encoding is inconsistent on mobile
    // browsers; the server already normalizes the stored image to WebP.
    try {
        usePhotoBlob(await SuryaVisitUpload.compress(canvas));
    } catch (error) {
        card.find('.visit-photo-status').removeClass('text-muted').addClass('text-danger fw-bold').text(error.message);
    }
}

$('.capture-photo').on('click', async function() {
    if (this.disabled || visitCapturing) return;
    visitCapturing = true;
    const captureButton = $(this);
    captureButton.prop('disabled', true);
    const originalLabel = captureButton.html();
    captureButton.html('<i class="fas fa-spinner fa-spin me-2"></i>Membaca Lokasi…');
    const card = $(this).closest('.visit-photo-card');
    card.find('.switch-camera').prop('disabled', true);
    try {
        await captureVisitPhoto(card);
    } finally {
        visitCapturing = false;
        captureButton.prop('disabled', false).html(originalLabel);
        card.find('.switch-camera').prop('disabled', false);
    }
});

$('#visit-form').on('submit', async function(event) {
    event.preventDefault();
    if (visitSubmitting) return;
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

    // Use the measurement belonging to the actual front photo/check-in.
    // Re-reading GPS here could relocate the outlet after the SF walks away.
    if (!applyVisitPhotoLocation()) {
        Swal.fire({
            icon: 'error',
            title: 'GPS Foto Belum Tercatat',
            text: 'Ambil ulang foto tampak depan dengan GPS aktif agar lokasi check-in ikut tersimpan.',
            confirmButtonColor: 'var(--primary)'
        });
        return;
    }
    visitSubmitting = true;
    const button = $(this).find('button[type="submit"]');
    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Mengirim Foto...');
    const status = document.getElementById('visit-upload-status');
    const progress = document.getElementById('visit-upload-progress');
    const message = document.getElementById('visit-upload-message');
    const check = document.getElementById('visit-upload-check');
    const outlet = new FormData(this).get('id_outlet');
    const menuUrl = new URL(check.dataset.menuUrl);
    menuUrl.searchParams.set('id_outlet', outlet);
    check.href = menuUrl.href;
    check.textContent = 'Cek status visit';
    check.classList.add('d-none');
    status.classList.remove('d-none', 'alert-danger');
    status.classList.add('alert-light');
    this.setAttribute('aria-busy', 'true');
    try {
        const result = await SuryaVisitUpload.send(this, percent => {
            progress.value = percent;
            message.textContent = percent < 100 ? 'Mengirim 2 foto · ' + percent + '%' : 'Upload selesai. Server sedang menyimpan foto…';
            if (percent === 100) button.html('<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan Foto...');
        });
        message.textContent = 'Foto tersimpan. Membuka menu visit…';
        window.location.assign(result.redirect_url);
    } catch (error) {
        // Never automatically resend an upload whose outcome is unknown.
        // The menu also lets the SF confirm a save after a lost response.
        status.classList.add('alert-danger');
        status.classList.remove('alert-light');
        message.textContent = error.message;
        check.classList.remove('d-none');
        if (error.errors?.photo_front || error.errors?.photo_inside) {
            $('.open-camera').prop('disabled', false).removeClass('d-none').text('Ambil Ulang Foto');
        }
        if (error.redirect) { check.href = error.redirect; check.textContent = 'Login kembali'; }
        visitSubmitting = false;
        button.prop('disabled', false).html('<i class="fas fa-camera me-2"></i>Coba Simpan Foto Lagi');
    } finally {
        this.removeAttribute('aria-busy');
    }
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
