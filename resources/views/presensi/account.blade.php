@extends('presensi.layout')

@section('title', 'Profil')

@section('content')
@php
    $initials = collect(explode(' ', trim($employee->name)))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
    $locationPolicy = $employee->attendanceLocationPolicy();
@endphp

<main class="app-content">
    <div class="app-topbar">
        <a href="{{ route('presensi.index') }}" class="icon-btn plain" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <h1 class="app-title">Profil</h1>
        <span class="icon-btn plain"><i class="fas fa-gear"></i></span>
    </div>

    <section class="profile-hero presence-card p-4 mb-4">
        <div class="avatar-xl">{{ $initials ?: 'MS' }}</div>
        <h2>{{ $employee->name }}</h2>
        <p>{{ $employee->position ?? 'Karyawan' }}</p>
        <span class="status-badge success">{{ strtoupper($employee->status ?? 'Aktif') }}</span>
    </section>

    <section class="profile-menu presence-card p-2 mb-3">
        <div class="profile-item">
            <i class="fas fa-user"></i>
            <div>
                <strong>Data Pribadi</strong>
                <span>{{ $employee->employee_code }} - {{ $employee->phone ?? 'No. HP belum diisi' }}</span>
            </div>
            <i class="fas fa-chevron-right"></i>
        </div>
        <div class="profile-item">
            <i class="fas fa-briefcase"></i>
            <div>
                <strong>Data Pekerjaan</strong>
                <span>Level {{ $employee->employee_level ?? 1 }} - {{ $employee->department ?? '-' }} - {{ $employee->work_location ?? '-' }}</span>
            </div>
            <i class="fas fa-chevron-right"></i>
        </div>
        <div class="profile-item">
            <i class="fas fa-location-dot"></i>
            <div>
                <strong>Aturan Lokasi</strong>
                <span>{{ $locationPolicy['badge'] }} - {{ $locationPolicy['label'] }}</span>
            </div>
            <i class="fas fa-chevron-right"></i>
        </div>
        <div class="profile-item">
            <i class="fas fa-shield-halved"></i>
            <div>
                <strong>Keamanan</strong>
                <span>Enrollment wajah {{ $employee->face_enrolled_at ? $employee->face_enrolled_at->format('d M Y H:i') : 'belum aktif' }}</span>
            </div>
            <i class="fas fa-chevron-right"></i>
        </div>
        <a href="{{ route('mobile.login') }}" class="profile-item">
            <i class="fas fa-store"></i>
            <div>
                <strong>Aplikasi Jualan</strong>
                <span>Buka modul sales force.</span>
            </div>
            <i class="fas fa-chevron-right"></i>
        </a>
        <div class="profile-item">
            <i class="fas fa-circle-question"></i>
            <div>
                <strong>Bantuan</strong>
                <span>Hubungi HR jika data presensi bermasalah.</span>
            </div>
            <i class="fas fa-chevron-right"></i>
        </div>
        <div class="profile-item">
            <i class="fas fa-circle-info"></i>
            <div>
                <strong>Tentang Aplikasi</strong>
                <span>PresensiKu MSP 2026</span>
            </div>
            <i class="fas fa-chevron-right"></i>
        </div>
    </section>

    <a href="{{ route('presensi.logout') }}" class="danger-btn w-100 mb-3">
        <i class="fas fa-arrow-right-from-bracket"></i> Keluar
    </a>
</main>
@endsection

@push('styles')
<style>
    .profile-hero {
        text-align: center;
    }

    .avatar-xl {
        width: 84px;
        height: 84px;
        border-radius: 30px;
        margin: 0 auto 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: var(--gradient);
        box-shadow: var(--shadow-strong);
        font-size: 1.25rem;
        font-weight: 900;
    }

    .profile-hero h2 {
        margin: 0;
        color: var(--ink);
        font-size: 1.05rem;
        font-weight: 900;
    }

    .profile-hero p {
        margin: 4px 0 10px;
        color: var(--muted);
        font-size: 0.74rem;
        font-weight: 800;
    }

    .profile-item {
        min-height: 62px;
        border-radius: 16px;
        padding: 11px 10px;
        display: grid;
        grid-template-columns: 38px 1fr 22px;
        align-items: center;
        gap: 10px;
        color: var(--ink);
        text-decoration: none;
    }

    .profile-item + .profile-item {
        border-top: 1px solid #f0f2fa;
    }

    .profile-item > i:first-child {
        width: 36px;
        height: 36px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        background: var(--primary-soft);
    }

    .profile-item > i:last-child {
        color: #9aa1bb;
        font-size: 0.72rem;
    }

    .profile-item strong,
    .profile-item span {
        display: block;
    }

    .profile-item strong {
        color: var(--ink);
        font-size: 0.82rem;
        font-weight: 900;
    }

    .profile-item span {
        color: var(--muted);
        font-size: 0.64rem;
        font-weight: 800;
        line-height: 1.35;
        margin-top: 2px;
    }
</style>
@endpush
