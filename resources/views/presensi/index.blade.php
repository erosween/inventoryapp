@extends('presensi.layout')

@section('title', 'Dashboard Presensi')

@section('content')
@php
    $currentType = $todayAttendance->attendance_type ?? null;
    $hasClockIn = !empty($todayAttendance?->check_in_at);
    $hasClockOut = !empty($todayAttendance?->check_out_at);
    $firstName = explode(' ', trim($employee->name))[0] ?? $employee->name;
    $initials = collect(explode(' ', trim($employee->name)))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
    $todayLabel = now()->locale('id')->translatedFormat('l, d M Y');
    $typeColors = [
        'hadir' => '#18b87a',
        'cuti' => '#5b37e5',
        'terlambat' => '#f59e0b',
        'cepat_pulang' => '#ec4899',
        'sakit' => '#ef4444',
    ];
    $typeIcons = [
        'hadir' => 'fa-fingerprint',
        'cuti' => 'fa-calendar-check',
        'terlambat' => 'fa-clock',
        'cepat_pulang' => 'fa-person-walking-arrow-right',
        'sakit' => 'fa-briefcase-medical',
    ];
@endphp

<main class="app-content">
    <div class="app-topbar">
        <a href="{{ route('presensi.menu') }}" class="icon-btn" aria-label="Menu"><i class="fas fa-bars"></i></a>
        <h1 class="app-title">Dashboard</h1>
        <a href="{{ route('presensi.inbox') }}" class="icon-btn plain" aria-label="Notifikasi">
            <i class="fas fa-bell"></i>
            <span class="icon-dot"></span>
        </a>
    </div>

    <section class="mb-3">
        <div class="brand-lockup mb-3">
            <div class="avatar-badge">{{ $initials ?: 'MS' }}</div>
            <div class="min-w-0">
                <h2 class="screen-heading text-truncate">Halo, {{ $firstName }}</h2>
                <p class="screen-subtitle text-truncate">{{ $employee->employee_code }} - {{ $employee->department ?? 'Karyawan' }}</p>
            </div>
        </div>

        <div class="gradient-card p-3">
            <div class="d-flex justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-white-50 fw-900 mb-1" style="font-size: 0.66rem;">Kehadiran Hari Ini</div>
                    <div class="fw-800" style="font-size: 0.68rem;">{{ $todayLabel }}</div>
                </div>
                <span class="status-badge {{ $hasClockIn ? 'success' : 'warning' }}">
                    {{ $hasClockIn ? 'Terekam' : 'Belum Check In' }}
                </span>
            </div>
            <div class="d-flex align-items-end justify-content-between gap-3">
                <div>
                    <div class="fw-900" id="liveClock" style="font-size: 2.2rem; line-height: 1;">{{ now()->format('H:i') }}</div>
                    <div class="text-white-50 fw-800 mt-2" style="font-size: 0.72rem;">
                        {{ $todayAttendance ? ($types[$currentType] ?? 'Terkirim') : 'Siap melakukan presensi' }}
                    </div>
                </div>
                <div class="attendance-times">
                    <div>
                        <span>Check In</span>
                        <strong>{{ $hasClockIn ? $todayAttendance->check_in_at->format('H:i') : '--:--' }}</strong>
                    </div>
                    <div>
                        <span>Check Out</span>
                        <strong>{{ $hasClockOut ? $todayAttendance->check_out_at->format('H:i') : '--:--' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="late-notice-card {{ $lateNotice['class'] }} mb-3">
        <i class="fas {{ $lateNotice['icon'] }}"></i>
        <div class="min-w-0">
            <strong>{{ $lateNotice['title'] }}</strong>
            <span>{{ $lateNotice['description'] }}</span>
        </div>
    </section>

    <section class="quick-grid mb-4">
        <a href="#scan" class="quick-action purple">
            <i class="fas fa-fingerprint"></i>
            <span>Presensi</span>
        </a>
        <a href="{{ route('presensi.history') }}" class="quick-action blue">
            <i class="fas fa-calendar-days"></i>
            <span>Riwayat</span>
        </a>
        <a href="{{ route('presensi.menu') }}" class="quick-action orange">
            <i class="fas fa-clipboard-list"></i>
            <span>Pengajuan</span>
        </a>
        <a href="{{ route('presensi.inbox') }}" class="quick-action red">
            <i class="fas fa-bell"></i>
            <span>Notifikasi</span>
        </a>
    </section>

    <section class="presence-card p-3 mb-4">
        <div class="section-row">
            <h2>Ringkasan Bulan Ini</h2>
            <a href="{{ route('presensi.history') }}">{{ now()->locale('id')->translatedFormat('M Y') }} <i class="fas fa-chevron-right ms-1"></i></a>
        </div>
        <div class="summary-strip">
            <div class="summary-box purple"><span>Hadir</span><strong>{{ $summary['hadir'] }}</strong><em>Hari</em></div>
            <div class="summary-box green"><span>Izin</span><strong>{{ $summary['izin'] }}</strong><em>Hari</em></div>
            <div class="summary-box orange"><span>Cuti</span><strong>{{ $summary['cuti'] }}</strong><em>Hari</em></div>
            <div class="summary-box red"><span>Sakit</span><strong>{{ $summary['sakit'] }}</strong><em>Hari</em></div>
        </div>
    </section>

    <section class="presence-card p-3 mb-4 insight-card">
        <div class="section-row">
            <h2>Insight Kehadiran</h2>
            <span>{{ now()->locale('id')->translatedFormat('M Y') }}</span>
        </div>
        <div class="insight-kpis mb-3">
            <div>
                <span>Tepat Waktu</span>
                <strong>{{ $dashboardInsights['on_time'] }}</strong>
            </div>
            <div>
                <span>Terlambat</span>
                <strong>{{ $dashboardInsights['late'] }}</strong>
            </div>
            <div>
                <span>Checkout</span>
                <strong>{{ $dashboardInsights['completion_rate'] }}%</strong>
            </div>
        </div>
        <div class="trend-chart" aria-label="Grafik kehadiran tujuh hari terakhir">
            @foreach($dashboardInsights['trend'] as $bar)
                <div class="trend-item">
                    <div class="trend-bar-wrap">
                        <div class="trend-bar {{ $bar['class'] }}" style="--bar-height: {{ $bar['height'] }}%;"></div>
                    </div>
                    <strong>{{ $bar['day'] }}</strong>
                    <span>{{ $bar['date'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="trend-legend mt-3">
            <span><i class="success"></i>Tepat</span>
            <span><i class="warning"></i>Telat</span>
            <span><i class="info"></i>Izin</span>
            <span><i class="empty"></i>Kosong</span>
        </div>
    </section>

    <section id="scan" class="pt-1 scan-section">
        <div class="app-topbar compact">
            <a href="#top" class="icon-btn plain" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
            <h2 class="app-title">Presensi</h2>
            <span></span>
        </div>

        <div class="presence-card p-3 mb-3">
            <div class="location-card">
                <div>
                    <div class="eyebrow mb-1">Lokasi Anda</div>
                    <strong><i class="fas fa-location-dot me-2"></i>{{ $locationPolicy['label'] }}</strong>
                    <p id="geoStatus">{{ $locationPolicy['description'] }}</p>
                </div>
                <span class="status-badge {{ $locationPolicy['badge_class'] }}" id="locationPolicyBadge">{{ $locationPolicy['badge'] }}</span>
            </div>
            <div class="map-preview mt-3">
                <div class="map-pin"><i class="fas fa-location-dot"></i></div>
            </div>
        </div>

        <div class="presence-card p-4 text-center mb-3">
            <div class="screen-subtitle mb-2">{{ $todayLabel }}</div>
            <div class="fw-900" id="liveClockExact" style="font-size: 2rem; line-height: 1;">{{ now()->format('H:i:s') }}</div>
            <div class="screen-subtitle mt-2">{{ $hasClockIn ? 'Anda sudah check in hari ini' : 'Anda belum check in' }}</div>
            <div class="work-schedule-pill mt-3">
                <i class="fas fa-business-time"></i>
                <span>Jam kerja {{ $employee->scheduleLabel() }} - toleransi {{ $employee->late_tolerance_minutes ?? 15 }} menit</span>
            </div>
        </div>

        <div class="presence-card p-3 mb-3">
            <div class="camera-stage">
                <video id="cameraPreview" autoplay playsinline muted></video>
                <canvas id="faceCanvas" class="d-none"></canvas>
                <div class="scan-frame"><span></span><span></span><span></span><span></span></div>
                <div class="face-lock-overlay" id="faceLockOverlay">
                    <div class="face-lock-ring" id="faceLockRing">
                        <div class="face-lock-core">
                            <i class="fas fa-face-smile"></i>
                            <strong id="faceLockPercent">0%</strong>
                        </div>
                    </div>
                    <div class="face-lock-caption" id="faceLockCaption">Mencari wajah</div>
                </div>
                <div class="camera-placeholder" id="cameraPlaceholder">
                    <i class="fas fa-camera"></i>
                    <div>Aktifkan kamera</div>
                </div>
            </div>

            <button type="button" class="primary-btn w-100 mt-3" id="faceScanBtn">
                <i class="fas fa-face-smile"></i>
                Mulai Face ID
            </button>

            <div class="face-flow-note mt-3">
                <i class="fas fa-circle-info"></i>
                <span>Kamera akan auto-capture saat wajah stabil. Setelah siap, tombol presensi aktif.</span>
            </div>

            <div class="verification-row mt-3">
                <div>
                    <div class="fw-900 text-dark" style="font-size: 0.84rem;">Status Face ID</div>
                    <div class="text-muted fw-bold" id="faceStatus" style="font-size: 0.68rem;">Tekan Mulai Face ID</div>
                </div>
                <div class="confidence-pill" id="confidencePill">0%</div>
            </div>
        </div>

        @if(!$activeEnrollment)
            <form action="{{ route('presensi.enroll') }}" method="POST" id="enrollForm" class="mb-3">
                @csrf
                <input type="hidden" name="face_image" class="face-image-input">
                <div class="presence-card p-4 mb-3 enrollment-card">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <i class="fas fa-user-check"></i>
                        <div>
                            <h3 class="fw-900 mb-1" style="font-size: 0.98rem;">Daftarkan Wajah Awal</h3>
                            <div class="screen-subtitle">Capture ini menjadi referensi validasi presensi berikutnya.</div>
                        </div>
                    </div>
                    <button type="submit" class="primary-btn w-100 presence-submit-btn" id="enrollSubmitBtn" disabled data-default-label="Simpan Enrollment">
                        <i class="fas fa-lock"></i>
                        Face ID dulu
                    </button>
                </div>
            </form>
        @else
            <form action="{{ route('presensi.attendance.store') }}" method="POST" id="attendanceForm" class="mb-3">
                @csrf
                <input type="hidden" name="latitude" class="latitude-input">
                <input type="hidden" name="longitude" class="longitude-input">
                <input type="hidden" name="face_image" class="face-image-input">

                <div class="presence-card p-3 mb-3">
                    <label class="field-label">Pilih Status</label>
                    <div class="type-grid">
                        @foreach($types as $key => $label)
                            <label class="type-option {{ $key === 'hadir' ? 'active' : '' }}" style="--type-color: {{ $typeColors[$key] ?? '#5b37e5' }};">
                                <input type="radio" name="attendance_type" value="{{ $key }}" {{ $key === 'hadir' ? 'checked' : '' }}>
                                <i class="fas {{ $typeIcons[$key] ?? 'fa-circle-check' }}"></i>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <textarea name="reason" class="reason-box mt-3" rows="3" placeholder="Keterangan izin, cuti, sakit, atau cepat pulang...">{{ old('reason') }}</textarea>
                    <div class="threshold-note mt-3">
                        Angka capture di kamera hanya indikator kualitas foto. Validasi identitas tetap dihitung server dengan minimal match {{ $faceThreshold }}%.
                    </div>
                </div>

                <button type="submit" class="primary-btn w-100 presence-submit-btn" id="attendanceSubmitBtn" {{ $hasClockIn && $currentType === 'hadir' ? 'disabled' : 'disabled' }} data-default-label="{{ $hasClockIn && $currentType === 'hadir' ? 'Sudah Check In' : 'Check In Sekarang' }}">
                    <i class="fas fa-fingerprint"></i>
                    {{ $hasClockIn && $currentType === 'hadir' ? 'Sudah Check In' : 'Face ID dulu' }}
                </button>
            </form>

            @if($hasClockIn && !$hasClockOut)
                <form action="{{ route('presensi.attendance.checkout') }}" method="POST" id="checkoutForm" class="mb-4">
                    @csrf
                    <input type="hidden" name="latitude" class="latitude-input">
                    <input type="hidden" name="longitude" class="longitude-input">
                    <input type="hidden" name="face_image" class="face-image-input">
                    <button type="submit" class="outline-btn w-100 presence-submit-btn" id="checkoutSubmitBtn" disabled data-default-label="Check Out Sekarang">
                        <i class="fas fa-right-from-bracket"></i> Face ID dulu untuk Check Out
                    </button>
                </form>
            @endif
        @endif
    </section>

    <section class="pb-3">
        <div class="section-row">
            <h2>Riwayat Terbaru</h2>
            <a href="{{ route('presensi.history') }}">Lihat Rekap Lengkap <i class="fas fa-chevron-right ms-1"></i></a>
        </div>
        <div class="history-list">
            @forelse($history->take(4) as $item)
                @php($arrival = $item->arrivalStatus())
                <div class="history-row">
                    <div class="history-icon" style="--accent: {{ $typeColors[$item->attendance_type] ?? '#94a3b8' }};">
                        <i class="fas {{ $typeIcons[$item->attendance_type] ?? 'fa-calendar-check' }}"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-900 text-dark text-truncate" style="font-size: 0.86rem;">{{ $types[$item->attendance_type] ?? ucfirst($item->attendance_type) }}</div>
                        <div class="text-muted fw-bold" style="font-size: 0.68rem;">
                            {{ $item->attendance_date->format('d M Y') }}
                            @if($item->check_in_at) - IN {{ $item->check_in_at->format('H:i') }} @endif
                            @if($item->check_out_at) - OUT {{ $item->check_out_at->format('H:i') }} @endif
                        </div>
                    </div>
                    <span class="status-badge {{ $arrival['class'] }}">{{ $arrival['label'] }}</span>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fas fa-calendar-days"></i>
                    <div>Belum ada riwayat presensi.</div>
                </div>
            @endforelse
        </div>
    </section>
</main>
@endsection

@push('styles')
<style>
    html {
        scroll-behavior: smooth;
    }

    .app-topbar.compact {
        margin: 8px 0 14px;
    }

    .scan-section {
        margin-top: 44px;
        scroll-margin-top: 16px;
    }

    .attendance-times {
        min-width: 122px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .attendance-times span,
    .attendance-times strong {
        display: block;
        text-align: right;
    }

    .attendance-times span {
        color: rgba(255,255,255,0.72);
        font-size: 0.58rem;
        font-weight: 800;
    }

    .attendance-times strong {
        color: #fff;
        font-size: 0.78rem;
        font-weight: 900;
    }

    .quick-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }

    .quick-action {
        min-height: 78px;
        border-radius: 18px;
        color: var(--ink);
        background: #fff;
        border: 1px solid var(--line);
        box-shadow: 0 10px 24px rgba(31,35,85,0.06);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
        font-size: 0.62rem;
        font-weight: 900;
        text-align: center;
    }

    .quick-action i {
        width: 36px;
        height: 36px;
        border-radius: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.92rem;
    }

    .quick-action.purple i { color: #5b37e5; background: #eee9ff; }
    .quick-action.blue i { color: #2f80ed; background: #e7efff; }
    .quick-action.orange i { color: #f59e0b; background: #fff4df; }
    .quick-action.red i { color: #ef4444; background: #ffe8ec; }

    .summary-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 8px;
    }

    .summary-box {
        min-width: 0;
        border-radius: 16px;
        padding: 14px 8px;
        text-align: center;
        background: #f8f9ff;
    }

    .summary-box span,
    .summary-box em {
        display: block;
        font-size: 0.58rem;
        font-style: normal;
        font-weight: 900;
    }

    .summary-box strong {
        display: inline-block;
        margin: 5px 0 2px;
        font-size: 1.45rem;
        line-height: 1;
        font-weight: 900;
    }

    .summary-box.purple { color: #5b37e5; background: #f1edff; }
    .summary-box.green { color: #059669; background: #e9fbf3; }
    .summary-box.orange { color: #f59e0b; background: #fff6e6; }
    .summary-box.red { color: #ef4444; background: #fff0f0; }

    .late-notice-card {
        min-height: 74px;
        border-radius: 20px;
        padding: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid var(--line);
        background: #fff;
        box-shadow: var(--shadow);
    }

    .late-notice-card > i {
        width: 42px;
        height: 42px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .late-notice-card strong,
    .late-notice-card span {
        display: block;
    }

    .late-notice-card strong {
        color: var(--ink);
        font-size: 0.86rem;
        font-weight: 900;
    }

    .late-notice-card span {
        margin-top: 3px;
        color: var(--muted);
        font-size: 0.68rem;
        font-weight: 800;
        line-height: 1.45;
    }

    .late-notice-card.success > i { color: #047857; background: #dcfce7; }
    .late-notice-card.warning > i { color: #b45309; background: #fef3c7; }
    .late-notice-card.danger > i { color: #c62828; background: #fee2e2; }
    .late-notice-card.info > i { color: #235ecf; background: #e7efff; }

    .insight-kpis {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .insight-kpis div {
        min-height: 70px;
        border-radius: 16px;
        padding: 12px 10px;
        background: #f8f9ff;
        border: 1px solid #edf0f8;
    }

    .insight-kpis span,
    .insight-kpis strong {
        display: block;
        text-align: center;
    }

    .insight-kpis span {
        color: var(--muted);
        font-size: 0.58rem;
        font-weight: 900;
    }

    .insight-kpis strong {
        margin-top: 6px;
        color: var(--ink);
        font-size: 1.22rem;
        line-height: 1;
        font-weight: 900;
    }

    .trend-chart {
        min-height: 142px;
        border-radius: 18px;
        padding: 14px 10px 10px;
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
        background: linear-gradient(180deg, #fbfcff 0%, #f2f5ff 100%);
        border: 1px solid #edf0f8;
    }

    .trend-item {
        min-width: 0;
        display: grid;
        grid-template-rows: 84px auto auto;
        align-items: end;
        justify-items: center;
        gap: 4px;
    }

    .trend-bar-wrap {
        width: 100%;
        height: 84px;
        border-radius: 999px;
        display: flex;
        align-items: end;
        justify-content: center;
        background: rgba(232,234,246,0.62);
        overflow: hidden;
    }

    .trend-bar {
        width: 100%;
        min-height: 10px;
        height: var(--bar-height);
        border-radius: 999px 999px 0 0;
        background: #d5daeb;
    }

    .trend-bar.success { background: linear-gradient(180deg, #34d399, #059669); }
    .trend-bar.warning { background: linear-gradient(180deg, #fbbf24, #f59e0b); }
    .trend-bar.info { background: linear-gradient(180deg, #60a5fa, #2563eb); }
    .trend-bar.empty { background: #d5daeb; }

    .trend-item strong {
        color: var(--ink);
        font-size: 0.58rem;
        font-weight: 900;
    }

    .trend-item span {
        color: var(--muted);
        font-size: 0.52rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .trend-legend {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .trend-legend span {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: var(--muted);
        font-size: 0.62rem;
        font-weight: 900;
    }

    .trend-legend i {
        width: 9px;
        height: 9px;
        border-radius: 99px;
        display: inline-block;
    }

    .trend-legend i.success { background: #059669; }
    .trend-legend i.warning { background: #f59e0b; }
    .trend-legend i.info { background: #2563eb; }
    .trend-legend i.empty { background: #d5daeb; }

    .location-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .location-card strong {
        display: block;
        color: var(--ink);
        font-size: 0.8rem;
        font-weight: 900;
    }

    .location-card p {
        max-width: 250px;
        margin: 5px 0 0;
        color: var(--muted);
        font-size: 0.64rem;
        font-weight: 800;
        line-height: 1.42;
    }

    .map-preview {
        height: 128px;
        border-radius: 18px;
        overflow: hidden;
        position: relative;
        background:
            linear-gradient(32deg, transparent 46%, rgba(91,55,229,0.1) 47%, rgba(91,55,229,0.1) 53%, transparent 54%),
            linear-gradient(122deg, transparent 45%, rgba(31,35,85,0.06) 46%, rgba(31,35,85,0.06) 53%, transparent 54%),
            linear-gradient(0deg, rgba(31,35,85,0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(31,35,85,0.04) 1px, transparent 1px),
            #f0f2f8;
        background-size: 100% 100%, 100% 100%, 34px 34px, 34px 34px, 100% 100%;
    }

    .map-pin {
        position: absolute;
        left: 50%;
        top: 50%;
        width: 72px;
        height: 72px;
        transform: translate(-50%, -50%);
        border-radius: 999px;
        display: grid;
        place-items: center;
        color: #fff;
        background: rgba(91,55,229,0.14);
    }

    .map-pin i {
        width: 34px;
        height: 34px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--gradient);
        box-shadow: var(--shadow-strong);
    }

    .work-schedule-pill {
        min-height: 38px;
        border-radius: 999px;
        padding: 8px 12px;
        color: var(--primary);
        background: var(--primary-soft);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 0.66rem;
        font-weight: 900;
    }

    .camera-stage {
        position: relative;
        aspect-ratio: 4 / 5;
        border-radius: 20px;
        overflow: hidden;
        background: #12172d;
    }

    #cameraPreview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
        display: none;
    }

    .face-lock-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    .camera-stage.scanning .face-lock-overlay {
        opacity: 1;
    }

    .face-lock-ring {
        --lock-score: 0;
        width: min(58%, 250px);
        aspect-ratio: 1;
        border-radius: 999px;
        display: grid;
        place-items: center;
        background:
            conic-gradient(#7b4dff calc(var(--lock-score) * 1%), rgba(255,255,255,0.16) 0),
            rgba(91,55,229,0.08);
        box-shadow:
            0 0 0 1px rgba(255,255,255,0.12),
            0 0 42px rgba(91,55,229,0.28);
        position: relative;
    }

    .face-lock-ring::before {
        content: "";
        position: absolute;
        inset: 9px;
        border-radius: inherit;
        border: 1px solid rgba(255,255,255,0.26);
    }

    .face-lock-ring::after {
        content: "";
        position: absolute;
        left: 18%;
        right: 18%;
        height: 2px;
        border-radius: 99px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.92), transparent);
        box-shadow: 0 0 18px rgba(255,255,255,0.72);
        animation: faceScanLine 1.6s ease-in-out infinite;
    }

    .face-lock-core {
        width: calc(100% - 22px);
        height: calc(100% - 22px);
        border-radius: inherit;
        display: grid;
        place-items: center;
        align-content: center;
        gap: 8px;
        color: #fff;
        background: rgba(18,23,45,0.72);
        backdrop-filter: blur(12px);
    }

    .face-lock-core i {
        font-size: 1.5rem;
        color: rgba(255,255,255,0.86);
    }

    .face-lock-core strong {
        font-size: 1.45rem;
        line-height: 1;
        font-weight: 900;
    }

    .face-lock-caption {
        border-radius: 999px;
        padding: 8px 12px;
        color: #fff;
        background: rgba(18,23,45,0.62);
        backdrop-filter: blur(10px);
        font-size: 0.7rem;
        font-weight: 900;
    }

    @keyframes faceScanLine {
        0%, 100% { top: 22%; opacity: 0.35; }
        50% { top: 76%; opacity: 1; }
    }

    .camera-placeholder {
        position: absolute;
        inset: 0;
        display: grid;
        place-content: center;
        text-align: center;
        gap: 10px;
        color: rgba(255,255,255,0.78);
        font-weight: 900;
    }

    .camera-placeholder i {
        font-size: 2rem;
    }

    .scan-frame {
        position: absolute;
        inset: 14%;
        border: 1px solid rgba(255,255,255,0.18);
        border-radius: 999px;
        pointer-events: none;
    }

    .scan-frame span {
        position: absolute;
        width: 28px;
        height: 28px;
        border-color: #fff;
        border-style: solid;
    }

    .scan-frame span:nth-child(1) { top: -1px; left: -1px; border-width: 3px 0 0 3px; border-radius: 18px 0 0 0; }
    .scan-frame span:nth-child(2) { top: -1px; right: -1px; border-width: 3px 3px 0 0; border-radius: 0 18px 0 0; }
    .scan-frame span:nth-child(3) { bottom: -1px; left: -1px; border-width: 0 0 3px 3px; border-radius: 0 0 0 18px; }
    .scan-frame span:nth-child(4) { bottom: -1px; right: -1px; border-width: 0 3px 3px 0; border-radius: 0 0 18px 0; }

    .face-flow-note {
        min-height: 44px;
        border-radius: 14px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 9px;
        color: var(--primary);
        background: var(--primary-soft);
        font-size: 0.68rem;
        font-weight: 900;
        line-height: 1.35;
    }

    .face-flow-note i {
        flex: 0 0 auto;
    }

    .camera-stage.face-ready .scan-frame {
        border-color: rgba(24,184,122,0.42);
    }

    .camera-stage.face-ready .scan-frame span {
        border-color: #55f0a7;
    }

    .camera-stage.face-ready .face-lock-ring {
        background:
            conic-gradient(#18b87a calc(var(--lock-score) * 1%), rgba(255,255,255,0.16) 0),
            rgba(24,184,122,0.08);
        box-shadow:
            0 0 0 1px rgba(255,255,255,0.12),
            0 0 42px rgba(24,184,122,0.32);
    }

    .verification-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .confidence-pill {
        flex: 0 0 auto;
        border-radius: 999px;
        background: #ffe8ec;
        color: #ef4444;
        padding: 8px 12px;
        font-size: 0.72rem;
        font-weight: 900;
    }

    .enrollment-card i {
        width: 44px;
        height: 44px;
        flex: 0 0 auto;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--primary-soft);
        color: var(--primary);
    }

    .type-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
    }

    .type-option {
        position: relative;
        min-height: 74px;
        border-radius: 16px;
        padding: 12px;
        background: #f8f9ff;
        border: 1px solid #e6e9f5;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        color: #4b526d;
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
        background: #f1edff;
        border-color: rgba(91,55,229,0.25);
        color: var(--ink);
    }

    .reason-box {
        width: 100%;
        border: 1px solid #e6e9f5;
        border-radius: 16px;
        background: #fff;
        padding: 13px 14px;
        resize: none;
        font-size: 0.82rem;
        font-weight: 800;
        outline: none;
    }

    .threshold-note {
        color: var(--muted);
        background: #f8f9ff;
        border: 1px solid #e6e9f5;
        border-radius: 14px;
        padding: 10px 12px;
        font-size: 0.7rem;
        font-weight: 900;
        line-height: 1.45;
    }

    .history-row {
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 18px;
        padding: 13px;
        margin-bottom: 10px;
        background: #fff;
        border: 1px solid var(--line);
        box-shadow: 0 8px 22px rgba(31,35,85,0.05);
    }

    .history-icon {
        width: 42px;
        height: 42px;
        border-radius: 15px;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--accent);
        background: color-mix(in srgb, var(--accent) 12%, white);
    }

    .empty-state {
        text-align: center;
        color: #9aa1bb;
        font-weight: 900;
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
    const cameraStage = document.querySelector('.camera-stage');
    const placeholder = document.getElementById('cameraPlaceholder');
    const faceStatus = document.getElementById('faceStatus');
    const confidencePill = document.getElementById('confidencePill');
    const faceLockRing = document.getElementById('faceLockRing');
    const faceLockPercent = document.getElementById('faceLockPercent');
    const faceLockCaption = document.getElementById('faceLockCaption');
    const faceScanBtn = document.getElementById('faceScanBtn');
    const enrollSubmitBtn = document.getElementById('enrollSubmitBtn');
    const attendanceSubmitBtn = document.getElementById('attendanceSubmitBtn');
    const checkoutSubmitBtn = document.getElementById('checkoutSubmitBtn');
    const geoStatus = document.getElementById('geoStatus');
    const locationPolicyBadge = document.getElementById('locationPolicyBadge');
    const attendanceLocationPolicy = @json($locationPolicy);
    const attendanceAlreadyLocked = @json($hasClockIn && $currentType === 'hadir');
    let cameraStream = null;
    let liveScanTimer = null;
    let liveCaptureScore = 0;
    let faceReady = false;
    let stableScanCount = 0;
    let autoCaptureBusy = false;

    function setConfidence(score, text) {
        liveCaptureScore = score;
        confidencePill.textContent = score + '%';
        confidencePill.style.background = score >= 80 ? '#dcfce7' : '#ffe8ec';
        confidencePill.style.color = score >= 80 ? '#047857' : '#ef4444';
        faceStatus.textContent = text;
        if (faceLockRing) faceLockRing.style.setProperty('--lock-score', score);
        if (faceLockPercent) faceLockPercent.textContent = score + '%';
        if (faceLockCaption) faceLockCaption.textContent = text;
    }

    function setFaceScanButton(icon, text, disabled = false) {
        if (!faceScanBtn) return;

        faceScanBtn.disabled = disabled;
        faceScanBtn.innerHTML = '<i class="fas ' + icon + '"></i>' + text;
    }

    function faceRequiredForForm(form) {
        if (!form) return false;
        if (form.id === 'enrollForm' || form.id === 'checkoutForm') return true;

        const type = form.querySelector('input[name="attendance_type"]:checked')?.value;
        return ['hadir', 'terlambat', 'cepat_pulang'].includes(type);
    }

    function updateSubmitStates() {
        if (enrollSubmitBtn) {
            enrollSubmitBtn.disabled = !faceReady;
            enrollSubmitBtn.innerHTML = faceReady
                ? '<i class="fas fa-user-check"></i>Simpan Enrollment'
                : '<i class="fas fa-lock"></i>Face ID dulu';
        }

        if (attendanceSubmitBtn) {
            const form = document.getElementById('attendanceForm');
            const type = form?.querySelector('input[name="attendance_type"]:checked')?.value;
            const requiresFace = faceRequiredForForm(form);

            if (attendanceAlreadyLocked) {
                attendanceSubmitBtn.disabled = true;
                attendanceSubmitBtn.innerHTML = '<i class="fas fa-fingerprint"></i>Sudah Check In';
            } else if (!requiresFace) {
                attendanceSubmitBtn.disabled = false;
                attendanceSubmitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>Kirim Pengajuan';
            } else {
                attendanceSubmitBtn.disabled = !faceReady;
                attendanceSubmitBtn.innerHTML = faceReady
                    ? '<i class="fas fa-fingerprint"></i>' + (attendanceSubmitBtn.dataset.defaultLabel || 'Check In Sekarang')
                    : '<i class="fas fa-lock"></i>Face ID dulu';
            }

            if (type === 'cepat_pulang' && faceReady) {
                attendanceSubmitBtn.innerHTML = '<i class="fas fa-person-walking-arrow-right"></i>Ajukan Cepat Pulang';
            }
        }

        if (checkoutSubmitBtn) {
            checkoutSubmitBtn.disabled = !faceReady;
            checkoutSubmitBtn.innerHTML = faceReady
                ? '<i class="fas fa-right-from-bracket"></i>Check Out Sekarang'
                : '<i class="fas fa-lock"></i>Face ID dulu untuk Check Out';
        }
    }

    function estimateFrameQuality() {
        if (!video.videoWidth) return 35;

        const sample = document.createElement('canvas');
        sample.width = 72;
        sample.height = 96;
        const ctx = sample.getContext('2d', { willReadFrequently: true });
        ctx.drawImage(video, 0, 0, sample.width, sample.height);

        const data = ctx.getImageData(0, 0, sample.width, sample.height).data;
        let total = 0;
        let totalSq = 0;
        let centerTotal = 0;
        let centerCount = 0;

        for (let y = 0; y < sample.height; y++) {
            for (let x = 0; x < sample.width; x++) {
                const idx = (y * sample.width + x) * 4;
                const light = (data[idx] + data[idx + 1] + data[idx + 2]) / 3;
                total += light;
                totalSq += light * light;

                if (x > sample.width * 0.25 && x < sample.width * 0.75 && y > sample.height * 0.18 && y < sample.height * 0.82) {
                    centerTotal += light;
                    centerCount++;
                }
            }
        }

        const pixels = sample.width * sample.height;
        const mean = total / pixels;
        const variance = Math.max(0, totalSq / pixels - mean * mean);
        const contrast = Math.sqrt(variance);
        const centerMean = centerTotal / Math.max(1, centerCount);

        const brightnessScore = Math.max(0, 38 - Math.abs(centerMean - 132) * 0.32);
        const contrastScore = Math.min(34, contrast * 0.9);
        const readyScore = video.readyState >= 2 ? 18 : 6;
        const framingScore = centerMean > 45 && centerMean < 220 ? 10 : 0;

        return Math.max(25, Math.min(96, Math.round(brightnessScore + contrastScore + readyScore + framingScore)));
    }

    function startLiveFaceScan() {
        if (liveScanTimer) clearInterval(liveScanTimer);

        cameraStage?.classList.add('scanning');
        cameraStage?.classList.remove('face-ready');
        setConfidence(45, 'Mendeteksi wajah...');
        stableScanCount = 0;

        liveScanTimer = setInterval(function() {
            const score = estimateFrameQuality();
            const text = score >= 86
                ? 'Wajah stabil, auto-capture...'
                : (score >= 70 ? 'Wajah terdeteksi, tahan posisi' : 'Dekatkan wajah ke frame');

            setConfidence(score, text);

            if (score >= 86) {
                stableScanCount++;
            } else {
                stableScanCount = 0;
            }

            if (stableScanCount >= 2 && !autoCaptureBusy && !faceReady) {
                captureFace(true);
            }
        }, 700);
    }

    async function startCamera() {
        try {
            faceReady = false;
            updateSubmitStates();
            setFaceScanButton('fa-spinner fa-spin', 'Membuka kamera...', true);
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
            }
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 900 }, height: { ideal: 1200 } },
                audio: false
            });
            video.srcObject = cameraStream;
            video.style.display = 'block';
            placeholder.style.display = 'none';
            startLiveFaceScan();
            setFaceScanButton('fa-wand-magic-sparkles', 'Scanning wajah...', true);
        } catch (error) {
            setFaceScanButton('fa-face-smile', 'Mulai Face ID', false);
            Swal.fire({ icon: 'error', title: 'Kamera tidak aktif', text: 'Izinkan akses kamera dari browser.', confirmButtonColor: '#5b37e5' });
        }
    }

    function canvasToDataUrl(sourceCanvas, mimeType = 'image/jpeg', quality = 0.78) {
        return new Promise(resolve => {
            sourceCanvas.toBlob(blob => {
                if (!blob) {
                    resolve(sourceCanvas.toDataURL('image/jpeg', quality));
                    return;
                }

                const reader = new FileReader();
                reader.onloadend = () => resolve(reader.result);
                reader.readAsDataURL(blob);
            }, mimeType, quality);
        });
    }

    async function compressedFaceImage() {
        const maxSide = 960;
        const sourceWidth = video.videoWidth;
        const sourceHeight = video.videoHeight;
        const scale = Math.min(1, maxSide / Math.max(sourceWidth, sourceHeight));

        canvas.width = Math.max(1, Math.round(sourceWidth * scale));
        canvas.height = Math.max(1, Math.round(sourceHeight * scale));

        const ctx = canvas.getContext('2d');
        ctx.save();
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        ctx.restore();

        return canvasToDataUrl(canvas, 'image/jpeg', 0.78);
    }

    async function captureFace(isAuto = false) {
        if (autoCaptureBusy) return;
        autoCaptureBusy = true;

        if (!video.srcObject) {
            await startCamera();
        }

        if (!video.videoWidth) {
            setConfidence(25, 'Kamera masih menyiapkan preview');
            autoCaptureBusy = false;
            return;
        }

        const image = await compressedFaceImage();
        document.querySelectorAll('.face-image-input').forEach(input => input.value = image);

        let score = liveCaptureScore || 88;
        if ('FaceDetector' in window) {
            try {
                const detector = new FaceDetector({ fastMode: true, maxDetectedFaces: 1 });
                const faces = await detector.detect(canvas);
                score = faces.length ? Math.max(score, 96) : Math.min(score, 58);
            } catch (error) {
                score = liveCaptureScore || 88;
            }
        }

        if (score >= 80) {
            faceReady = true;
            if (liveScanTimer) clearInterval(liveScanTimer);
            cameraStage?.classList.add('face-ready');
            setConfidence(score, 'Face ID siap, lanjut tekan presensi');
            setFaceScanButton('fa-rotate-right', 'Ulangi Face ID', false);
            updateSubmitStates();

            const targetButton = checkoutSubmitBtn && !checkoutSubmitBtn.disabled ? checkoutSubmitBtn : (attendanceSubmitBtn || enrollSubmitBtn);
            targetButton?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            faceReady = false;
            updateSubmitStates();
            setConfidence(score, isAuto ? 'Cahaya kurang, tahan wajah di frame' : 'Foto belum jelas, coba ulangi Face ID');
            setFaceScanButton('fa-rotate-right', 'Ulangi Face ID', false);
        }

        autoCaptureBusy = false;
    }

    function distanceMeters(fromLat, fromLng, toLat, toLng) {
        const earthRadius = 6371000;
        const toRad = value => value * Math.PI / 180;
        const latFrom = toRad(fromLat);
        const lonFrom = toRad(fromLng);
        const latTo = toRad(toLat);
        const lonTo = toRad(toLng);
        const latDelta = latTo - latFrom;
        const lonDelta = lonTo - lonFrom;
        const angle = 2 * Math.asin(Math.sqrt(
            Math.sin(latDelta / 2) ** 2 +
            Math.cos(latFrom) * Math.cos(latTo) * Math.sin(lonDelta / 2) ** 2
        ));

        return earthRadius * angle;
    }

    function setLocationBadge(text, type) {
        if (!locationPolicyBadge) return;

        locationPolicyBadge.textContent = text;
        locationPolicyBadge.className = 'status-badge ' + type;
    }

    function applyLocationStatus(coords) {
        if (!geoStatus) return;

        if (!attendanceLocationPolicy.locked) {
            geoStatus.textContent = attendanceLocationPolicy.description;
            setLocationBadge('Bebas Lokasi', 'info');
            return;
        }

        if (!attendanceLocationPolicy.configured) {
            geoStatus.textContent = attendanceLocationPolicy.description;
            setLocationBadge('Set Admin', 'warning');
            return;
        }

        const distance = distanceMeters(
            coords.latitude,
            coords.longitude,
            attendanceLocationPolicy.latitude,
            attendanceLocationPolicy.longitude
        );
        const radius = attendanceLocationPolicy.radius || 150;

        if (distance <= radius) {
            geoStatus.textContent = 'GPS terbaca. Jarak ' + Math.round(distance) + 'm dari titik presensi.';
            setLocationBadge('Dalam Radius', 'success');
        } else {
            geoStatus.textContent = 'GPS terbaca, tapi jarak ' + Math.round(distance) + 'm. Maksimal ' + radius + 'm dari titik presensi.';
            setLocationBadge('Di Luar Radius', 'danger');
        }
    }

    function updatePosition(position) {
        document.querySelectorAll('.latitude-input').forEach(input => input.value = position.coords.latitude);
        document.querySelectorAll('.longitude-input').forEach(input => input.value = position.coords.longitude);
        applyLocationStatus(position.coords);
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(updatePosition, function() {
            if (attendanceLocationPolicy.locked && attendanceLocationPolicy.configured) {
                geoStatus.textContent = 'GPS belum terbaca. Izinkan akses lokasi untuk presensi di area kantor.';
                setLocationBadge('Butuh GPS', 'warning');
            }
        }, { enableHighAccuracy: true, timeout: 8000 });
    }

    faceScanBtn?.addEventListener('click', startCamera);

    document.querySelectorAll('.type-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.type-option').forEach(item => item.classList.remove('active'));
            this.classList.add('active');
            updateSubmitStates();
        });
    });

    document.querySelectorAll('#enrollForm, #attendanceForm, #checkoutForm').forEach(form => {
        form.addEventListener('submit', function(e) {
            const type = this.querySelector('input[name="attendance_type"]:checked')?.value;
            const reason = this.querySelector('textarea[name="reason"]')?.value.trim();
            const face = this.querySelector('.face-image-input')?.value;

            if (this.id === 'attendanceForm' && ['cuti', 'sakit', 'terlambat', 'cepat_pulang'].includes(type) && !reason) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Keterangan wajib', text: 'Isi alasan untuk cuti, sakit, atau izin.', confirmButtonColor: '#5b37e5' });
                return;
            }

            if (faceRequiredForForm(this) && !face) {
                e.preventDefault();
                startCamera();
                Swal.fire({ icon: 'warning', title: 'Face ID dulu', text: 'Kamera akan auto-capture saat wajah sudah pas.', confirmButtonColor: '#5b37e5' });
            }
        });
    });

    updateSubmitStates();

    setInterval(function() {
        const now = new Date();
        const shortTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        const longTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const liveClock = document.getElementById('liveClock');
        const liveClockExact = document.getElementById('liveClockExact');

        if (liveClock) liveClock.textContent = shortTime;
        if (liveClockExact) liveClockExact.textContent = longTime;
    }, 1000);
</script>
@endpush
