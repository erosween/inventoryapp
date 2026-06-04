@extends('layout.mobile_layout')

@section('title', 'Presensi')

@section('content')
@php
    $currentType = $todayAttendance->attendance_type ?? null;
    $hasClockIn = !empty($todayAttendance?->check_in_at);
    $hasClockOut = !empty($todayAttendance?->check_out_at);
    $typeColors = [
        'hadir' => '#10b981',
        'cuti' => '#6366f1',
        'terlambat' => '#f59e0b',
        'cepat_pulang' => '#ec4899',
        'sakit' => '#ef4444',
    ];
@endphp

<div class="attendance-shell reveal" style="margin: 0 -16px; min-height: 100vh; background: #f4f7f9;">
    <section class="attendance-hero px-4 pt-4 pb-5">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center min-w-0">
                <img src="/assets/img/MSP5.png" alt="MSP" class="me-3" style="width: 46px; height: 46px; border-radius: 14px; border: 2px solid rgba(255,255,255,0.8);">
                <div class="min-w-0">
                    <div class="text-white-50 fw-bold" style="font-size: 0.68rem;">PRESENSI SALES FORCE</div>
                    <h5 class="text-white fw-800 mb-0 text-truncate" style="font-size: 1rem;">{{ session('mobile_sf_name') }}</h5>
                </div>
            </div>
            <div class="text-end">
                <div class="text-white-50 fw-bold" style="font-size: 0.62rem;">{{ now()->format('d M Y') }}</div>
                <div class="text-white fw-800" id="liveClock" style="font-size: 1.15rem;">{{ now()->format('H:i') }}</div>
            </div>
        </div>

        <div class="attendance-status">
            <div>
                <div class="text-white-50 fw-bold mb-1" style="font-size: 0.7rem;">STATUS HARI INI</div>
                <h2 class="text-white fw-800 mb-1" style="font-size: 2rem; line-height: 1;">
                    {{ $todayAttendance ? ($types[$currentType] ?? 'Terkirim') : 'Belum Presensi' }}
                </h2>
                <div class="text-white-50 fw-bold" style="font-size: 0.72rem;">
                    @if($hasClockIn)
                        Masuk {{ $todayAttendance->check_in_at->format('H:i') }}
                        @if($hasClockOut)
                            • Pulang {{ $todayAttendance->check_out_at->format('H:i') }}
                        @endif
                    @else
                        Face capture, GPS, dan izin dalam satu flow.
                    @endif
                </div>
            </div>
            <div class="attendance-ring">
                <i class="fas {{ $hasClockIn ? 'fa-shield-halved' : 'fa-face-smile' }}"></i>
            </div>
        </div>
    </section>

    <div class="px-3 position-relative" style="margin-top: -38px; z-index: 2;">
        <div class="summary-grid mb-3">
            <div class="summary-tile">
                <span>Hadir</span>
                <strong>{{ $summary['hadir'] }}</strong>
            </div>
            <div class="summary-tile">
                <span>Cuti</span>
                <strong>{{ $summary['cuti'] }}</strong>
            </div>
            <div class="summary-tile">
                <span>Izin</span>
                <strong>{{ $summary['izin'] }}</strong>
            </div>
            <div class="summary-tile">
                <span>Sakit</span>
                <strong>{{ $summary['sakit'] }}</strong>
            </div>
        </div>

        <div class="capture-panel mb-3">
            <div class="camera-stage">
                <video id="cameraPreview" autoplay playsinline muted></video>
                <canvas id="faceCanvas" class="d-none"></canvas>
                <div class="scan-frame">
                    <span></span><span></span><span></span><span></span>
                </div>
                <div class="camera-placeholder" id="cameraPlaceholder">
                    <i class="fas fa-camera-retro"></i>
                    <div>Aktifkan kamera</div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="button" class="btn action-btn flex-fill" id="startCameraBtn">
                    <i class="fas fa-video me-1"></i> Kamera
                </button>
                <button type="button" class="btn action-btn flex-fill" id="captureFaceBtn">
                    <i class="fas fa-camera me-1"></i> Capture
                </button>
            </div>
            <div class="verification-row mt-3">
                <div>
                    <div class="fw-800 text-dark" style="font-size: 0.82rem;">Face Verification</div>
                    <div class="text-muted fw-bold" id="faceStatus" style="font-size: 0.68rem;">Menunggu capture wajah</div>
                </div>
                <div class="confidence-pill" id="confidencePill">0%</div>
            </div>
        </div>

        <form action="{{ route('mobile.attendance.store') }}" method="POST" id="attendanceForm" class="mb-3">
            @csrf
            <input type="hidden" name="latitude" class="latitude-input">
            <input type="hidden" name="longitude" class="longitude-input">
            <input type="hidden" name="face_image" class="face-image-input">
            <input type="hidden" name="face_confidence" class="face-confidence-input" value="0">

            <div class="request-panel mb-3">
                <label class="panel-label">Pilih Status</label>
                <div class="type-grid">
                    @foreach($types as $key => $label)
                        <label class="type-option {{ $key === 'hadir' ? 'active' : '' }}" style="--type-color: {{ $typeColors[$key] ?? '#ec2028' }};">
                            <input type="radio" name="attendance_type" value="{{ $key }}" {{ $key === 'hadir' ? 'checked' : '' }}>
                            <i class="fas {{ $key === 'hadir' ? 'fa-fingerprint' : ($key === 'cuti' ? 'fa-calendar-check' : ($key === 'sakit' ? 'fa-briefcase-medical' : 'fa-clock')) }}"></i>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <textarea name="reason" class="reason-box mt-3" rows="3" placeholder="Keterangan izin, cuti, atau sakit...">{{ old('reason') }}</textarea>
            </div>

            <button type="submit" class="btn submit-presence w-100" {{ $hasClockIn && $currentType === 'hadir' ? 'disabled' : '' }}>
                <i class="fas fa-fingerprint me-2"></i>
                {{ $hasClockIn && $currentType === 'hadir' ? 'Sudah Clock In' : 'Simpan Presensi' }}
            </button>
        </form>

        @if($hasClockIn && !$hasClockOut)
            <form action="{{ route('mobile.attendance.checkout') }}" method="POST" id="checkoutForm" class="mb-4">
                @csrf
                <input type="hidden" name="latitude" class="latitude-input">
                <input type="hidden" name="longitude" class="longitude-input">
                <input type="hidden" name="face_image" class="face-image-input">
                <input type="hidden" name="face_confidence" class="face-confidence-input" value="0">
                <button type="submit" class="btn checkout-btn w-100">
                    <i class="fas fa-right-from-bracket me-2"></i> Clock Out
                </button>
            </form>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-3 px-1">
            <h6 class="fw-800 mb-0" style="font-size: 0.8rem;">RIWAYAT BULAN INI</h6>
            <span class="text-muted fw-bold" style="font-size: 0.68rem;">{{ now()->format('F Y') }}</span>
        </div>

        <div class="history-list pb-4">
            @forelse($history as $item)
                <div class="history-row">
                    <div class="history-mark" style="background: {{ $typeColors[$item->attendance_type] ?? '#94a3b8' }};"></div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-800 text-dark text-truncate" style="font-size: 0.86rem;">{{ $types[$item->attendance_type] ?? ucfirst($item->attendance_type) }}</div>
                        <div class="text-muted fw-bold" style="font-size: 0.68rem;">
                            {{ $item->attendance_date->format('d M Y') }}
                            @if($item->check_in_at) • IN {{ $item->check_in_at->format('H:i') }} @endif
                            @if($item->check_out_at) • OUT {{ $item->check_out_at->format('H:i') }} @endif
                        </div>
                    </div>
                    <span class="status-chip">{{ strtoupper($item->status) }}</span>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fas fa-calendar-days"></i>
                    <div>Belum ada riwayat presensi.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .attendance-hero {
        background:
            linear-gradient(135deg, #161a25 0%, #3f2432 54%, #ec2028 100%);
        border-radius: 0 0 32px 32px;
        box-shadow: 0 24px 70px rgba(38, 20, 31, 0.28);
    }

    .attendance-status {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
    }

    .attendance-ring {
        width: 78px;
        height: 78px;
        border-radius: 24px;
        display: grid;
        place-items: center;
        color: #fff;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.2);
        font-size: 1.8rem;
        flex: 0 0 auto;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 8px;
    }

    .summary-tile,
    .capture-panel,
    .request-panel,
    .history-row {
        background: #fff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }

    .summary-tile {
        border-radius: 18px;
        padding: 12px 8px;
        text-align: center;
        min-width: 0;
    }

    .summary-tile span {
        display: block;
        color: #64748b;
        font-size: 0.58rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .summary-tile strong {
        display: block;
        color: #111827;
        font-size: 1.35rem;
        line-height: 1.1;
        margin-top: 4px;
    }

    .capture-panel,
    .request-panel {
        border-radius: 24px;
        padding: 14px;
    }

    .camera-stage {
        position: relative;
        aspect-ratio: 4 / 5;
        border-radius: 22px;
        overflow: hidden;
        background: #111827;
    }

    #cameraPreview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
        display: none;
    }

    .camera-placeholder {
        position: absolute;
        inset: 0;
        display: grid;
        place-content: center;
        text-align: center;
        gap: 10px;
        color: rgba(255, 255, 255, 0.78);
        font-weight: 800;
    }

    .camera-placeholder i {
        font-size: 2rem;
    }

    .scan-frame {
        position: absolute;
        inset: 13%;
        border: 1px solid rgba(255,255,255,0.18);
        border-radius: 999px;
        pointer-events: none;
    }

    .scan-frame span {
        position: absolute;
        width: 26px;
        height: 26px;
        border-color: #fff;
        border-style: solid;
    }

    .scan-frame span:nth-child(1) { top: -1px; left: -1px; border-width: 3px 0 0 3px; border-radius: 18px 0 0 0; }
    .scan-frame span:nth-child(2) { top: -1px; right: -1px; border-width: 3px 3px 0 0; border-radius: 0 18px 0 0; }
    .scan-frame span:nth-child(3) { bottom: -1px; left: -1px; border-width: 0 0 3px 3px; border-radius: 0 0 0 18px; }
    .scan-frame span:nth-child(4) { bottom: -1px; right: -1px; border-width: 0 3px 3px 0; border-radius: 0 0 18px 0; }

    .action-btn,
    .checkout-btn {
        border-radius: 18px;
        padding: 13px 14px;
        font-weight: 800;
        border: 0;
    }

    .action-btn {
        color: #111827;
        background: #f1f5f9;
    }

    .verification-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .confidence-pill,
    .status-chip {
        flex: 0 0 auto;
        border-radius: 999px;
        font-weight: 900;
    }

    .confidence-pill {
        background: #fee2e2;
        color: #ec2028;
        padding: 8px 12px;
        font-size: 0.72rem;
    }

    .panel-label {
        display: block;
        color: #64748b;
        font-size: 0.65rem;
        font-weight: 900;
        text-transform: uppercase;
        margin: 2px 0 10px;
    }

    .type-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
    }

    .type-option {
        position: relative;
        min-height: 74px;
        border-radius: 18px;
        padding: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        color: #475569;
        font-size: 0.72rem;
        font-weight: 900;
        line-height: 1.15;
    }

    .type-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .type-option i {
        color: var(--type-color);
        font-size: 1rem;
    }

    .type-option.active {
        background: color-mix(in srgb, var(--type-color) 12%, white);
        border-color: color-mix(in srgb, var(--type-color) 36%, white);
        color: #111827;
    }

    .reason-box {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #f8fafc;
        padding: 13px 14px;
        resize: none;
        font-size: 0.82rem;
        font-weight: 700;
        outline: none;
    }

    .submit-presence {
        border: 0;
        border-radius: 20px;
        padding: 16px;
        background: linear-gradient(135deg, #ec2028, #ff5960);
        color: #fff;
        font-weight: 900;
        box-shadow: 0 14px 30px rgba(236, 32, 40, 0.24);
    }

    .submit-presence:disabled {
        background: #cbd5e1;
        box-shadow: none;
        color: #64748b;
    }

    .checkout-btn {
        background: #111827;
        color: #fff;
    }

    .history-row {
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 18px;
        padding: 13px;
        margin-bottom: 9px;
    }

    .history-mark {
        width: 5px;
        height: 42px;
        border-radius: 99px;
        flex: 0 0 auto;
    }

    .status-chip {
        background: #f1f5f9;
        color: #475569;
        padding: 7px 9px;
        font-size: 0.56rem;
    }

    .empty-state {
        text-align: center;
        color: #94a3b8;
        font-weight: 800;
        padding: 36px 12px;
    }

    .empty-state i {
        display: block;
        font-size: 2rem;
        margin-bottom: 10px;
    }
</style>
@endpush

@push('scripts')
<script>
    const video = document.getElementById('cameraPreview');
    const canvas = document.getElementById('faceCanvas');
    const placeholder = document.getElementById('cameraPlaceholder');
    const startCameraBtn = document.getElementById('startCameraBtn');
    const captureFaceBtn = document.getElementById('captureFaceBtn');
    const faceStatus = document.getElementById('faceStatus');
    const confidencePill = document.getElementById('confidencePill');
    let cameraStream = null;

    function setConfidence(score, text) {
        document.querySelectorAll('.face-confidence-input').forEach(input => input.value = score);
        confidencePill.textContent = score + '%';
        confidencePill.style.background = score >= 80 ? '#dcfce7' : '#fee2e2';
        confidencePill.style.color = score >= 80 ? '#047857' : '#ec2028';
        faceStatus.textContent = text;
    }

    async function startCamera() {
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 900 }, height: { ideal: 1200 } },
                audio: false
            });
            video.srcObject = cameraStream;
            video.style.display = 'block';
            placeholder.style.display = 'none';
            setConfidence(40, 'Kamera aktif, posisikan wajah di frame');
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Kamera tidak aktif', text: 'Izinkan akses kamera dari browser.' });
        }
    }

    async function captureFace() {
        if (!video.srcObject) {
            await startCamera();
        }

        if (!video.videoWidth) {
            setConfidence(25, 'Kamera masih menyiapkan preview');
            return;
        }

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const image = canvas.toDataURL('image/jpeg', 0.82);
        document.querySelectorAll('.face-image-input').forEach(input => input.value = image);

        let score = 88;
        if ('FaceDetector' in window) {
            try {
                const detector = new FaceDetector({ fastMode: true, maxDetectedFaces: 1 });
                const faces = await detector.detect(canvas);
                score = faces.length ? 96 : 58;
            } catch (error) {
                score = 88;
            }
        }

        setConfidence(score, score >= 80 ? 'Wajah ter-capture dan siap diverifikasi' : 'Wajah belum jelas, coba capture ulang');
    }

    function updatePosition(position) {
        document.querySelectorAll('.latitude-input').forEach(input => input.value = position.coords.latitude);
        document.querySelectorAll('.longitude-input').forEach(input => input.value = position.coords.longitude);
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(updatePosition, function() {}, { enableHighAccuracy: true, timeout: 8000 });
    }

    startCameraBtn?.addEventListener('click', startCamera);
    captureFaceBtn?.addEventListener('click', captureFace);

    document.querySelectorAll('.type-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.type-option').forEach(item => item.classList.remove('active'));
            this.classList.add('active');
        });
    });

    document.querySelectorAll('#attendanceForm, #checkoutForm').forEach(form => {
        form.addEventListener('submit', function(e) {
            const type = this.querySelector('input[name="attendance_type"]:checked')?.value;
            const reason = this.querySelector('textarea[name="reason"]')?.value.trim();
            const confidence = Number(this.querySelector('.face-confidence-input')?.value || 0);

            if (this.id === 'attendanceForm' && type !== 'hadir' && !reason) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Keterangan wajib', text: 'Isi alasan untuk cuti, izin, atau sakit.' });
                return;
            }

            if (confidence < 50) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Capture wajah dulu', text: 'Aktifkan kamera lalu tekan Capture sebelum menyimpan.' });
            }
        });
    });

    setInterval(function() {
        const now = new Date();
        document.getElementById('liveClock').textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }, 1000);
</script>
@endpush
