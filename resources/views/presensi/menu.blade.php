@extends('presensi.layout')

@section('title', 'Pengajuan')

@section('content')
@php
    $statusClass = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
    ];
@endphp

<main class="app-content">
    <div class="app-topbar">
        <a href="{{ route('presensi.index') }}" class="icon-btn plain" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <h1 class="app-title">Pengajuan</h1>
        <a href="{{ route('presensi.inbox') }}" class="icon-btn plain" aria-label="Notifikasi">
            <i class="fas fa-bell"></i>
            @if($pendingCount > 0)<span class="icon-dot"></span>@endif
        </a>
    </div>

    <div class="tab-row mb-3">
        <button class="active" type="button">Semua</button>
        <button type="button">Menunggu</button>
        <button type="button">Disetujui</button>
        <button type="button">Ditolak</button>
    </div>

    <section class="mb-4">
        @forelse($recentRequests as $request)
            <div class="request-list-card">
                <div class="request-icon" style="--accent: {{ $modules[$request->request_type]['accent'] ?? '#5b37e5' }};">
                    <i class="fas {{ $modules[$request->request_type]['icon'] ?? 'fa-file-lines' }}"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <strong>{{ $modules[$request->request_type]['title'] ?? strtoupper(str_replace('-', ' ', $request->request_type)) }}</strong>
                    <span>{{ $request->category }} - {{ $request->created_at->format('d M Y') }}</span>
                </div>
                <em class="{{ $statusClass[$request->status] ?? 'warning' }}">{{ strtoupper($request->status) }}</em>
            </div>
        @empty
            <div class="empty-panel">Belum ada pengajuan.</div>
        @endforelse
    </section>

    <section class="presence-card p-3 mb-4">
        <div class="section-row">
            <h2>Layanan Karyawan</h2>
            <span>{{ count($modules) + 3 }} menu</span>
        </div>
        <div class="feature-grid">
            @foreach($modules as $type => $module)
                <a href="{{ route('presensi.request.create', $type) }}" class="feature-card" style="--accent: {{ $module['accent'] }};">
                    <i class="fas {{ $module['icon'] }}"></i>
                    <div>
                        <strong>{{ $module['title'] }}</strong>
                        <span>{{ $module['subtitle'] }}</span>
                    </div>
                </a>
            @endforeach
            <a href="{{ route('presensi.payslip') }}" class="feature-card" style="--accent: #5b37e5;">
                <i class="fas fa-file-invoice"></i>
                <div>
                    <strong>Slip Gaji</strong>
                    <span>Lihat slip gaji saat payroll sudah generate.</span>
                </div>
            </a>
            <a href="{{ route('presensi.inbox') }}" class="feature-card" style="--accent: #2f80ed;">
                <i class="fas fa-inbox"></i>
                <div>
                    <strong>Kotak Masuk</strong>
                    <span>Status approval, pengingat, dan informasi HR.</span>
                </div>
            </a>
            <a href="{{ route('presensi.account') }}" class="feature-card" style="--accent: #737997;">
                <i class="fas fa-user-gear"></i>
                <div>
                    <strong>Akun</strong>
                    <span>Profil, keamanan, dan akses aplikasi jualan.</span>
                </div>
            </a>
        </div>
    </section>

    <a href="{{ route('presensi.request.create', 'cuti') }}" class="primary-btn w-100 mb-3">
        <i class="fas fa-plus"></i> Ajukan Pengajuan
    </a>
</main>
@endsection

@push('styles')
<style>
    .tab-row {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        border-bottom: 1px solid var(--line);
    }

    .tab-row button {
        min-height: 42px;
        border: 0;
        color: var(--muted);
        background: transparent;
        font-size: 0.66rem;
        font-weight: 900;
        position: relative;
    }

    .tab-row button.active {
        color: var(--primary);
    }

    .tab-row button.active::after {
        content: "";
        position: absolute;
        left: 10px;
        right: 10px;
        bottom: -1px;
        height: 3px;
        border-radius: 99px 99px 0 0;
        background: var(--primary);
    }

    .request-list-card,
    .empty-panel {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 18px;
        padding: 13px;
        margin-bottom: 10px;
        box-shadow: 0 9px 24px rgba(31,35,85,0.05);
    }

    .request-list-card {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .request-icon,
    .feature-card i {
        width: 42px;
        height: 42px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--accent);
        background: color-mix(in srgb, var(--accent) 12%, white);
        flex: 0 0 auto;
    }

    .request-list-card strong,
    .request-list-card span {
        display: block;
    }

    .request-list-card strong {
        color: var(--ink);
        font-size: 0.82rem;
        font-weight: 900;
    }

    .request-list-card span {
        color: var(--muted);
        font-size: 0.66rem;
        font-weight: 800;
        margin-top: 3px;
    }

    .request-list-card em {
        flex: 0 0 auto;
        border-radius: 999px;
        padding: 6px 9px;
        font-size: 0.55rem;
        font-style: normal;
        font-weight: 900;
    }

    .request-list-card em.warning { color: #b45309; background: #fef3c7; }
    .request-list-card em.success { color: #047857; background: #dcfce7; }
    .request-list-card em.danger { color: #c62828; background: #fee2e2; }

    .feature-grid {
        display: grid;
        gap: 10px;
    }

    .feature-card {
        min-height: 76px;
        border-radius: 17px;
        padding: 12px;
        color: var(--ink);
        background: #fbfcff;
        border: 1px solid var(--line);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .feature-card strong,
    .feature-card span {
        display: block;
    }

    .feature-card strong {
        color: var(--ink);
        font-size: 0.84rem;
        font-weight: 900;
    }

    .feature-card span {
        color: var(--muted);
        font-size: 0.66rem;
        font-weight: 800;
        line-height: 1.38;
        margin-top: 3px;
    }

    .empty-panel {
        color: #9aa1bb;
        font-size: 0.8rem;
        font-weight: 900;
        text-align: center;
    }
</style>
@endpush
