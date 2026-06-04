@extends('presensi.layout')

@section('title', 'Notifikasi')

@section('content')
@php
    $statusIcon = [
        'pending' => ['fa-bell', 'warning', 'Pengajuan menunggu'],
        'approved' => ['fa-check', 'success', 'Pengajuan disetujui'],
        'rejected' => ['fa-xmark', 'danger', 'Pengajuan ditolak'],
    ];
@endphp

<main class="app-content">
    <div class="app-topbar">
        <a href="{{ route('presensi.menu') }}" class="icon-btn plain" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <h1 class="app-title">Notifikasi</h1>
        <a href="{{ route('presensi.index') }}" class="icon-btn plain" aria-label="Beranda"><i class="fas fa-house"></i></a>
    </div>

    <div class="section-row mb-3">
        <h2>Aktivitas Terbaru</h2>
        <span>Tandai semua dibaca</span>
    </div>

    <section class="pb-3">
        @forelse($requests as $request)
            @php($meta = $statusIcon[$request->status] ?? ['fa-circle-info', 'info', 'Informasi'])
            <div class="notification-card">
                <div class="notification-icon {{ $meta[1] }}">
                    <i class="fas {{ $meta[0] }}"></i>
                </div>
                <div class="min-w-0 flex-grow-1">
                    <strong>{{ $meta[2] }}</strong>
                    <span>{{ $request->category }} pada {{ $request->created_at->format('d M Y H:i') }}</span>
                    <em>{{ strtoupper(str_replace('-', ' ', $request->request_type)) }}</em>
                </div>
            </div>
        @empty
            <div class="empty-panel">
                <i class="fas fa-bell-slash"></i>
                <div>Belum ada notifikasi.</div>
            </div>
        @endforelse
    </section>
</main>
@endsection

@push('styles')
<style>
    .notification-card,
    .empty-panel {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 18px;
        padding: 14px;
        margin-bottom: 10px;
        box-shadow: 0 9px 24px rgba(31,35,85,0.05);
    }

    .notification-card {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .notification-icon {
        width: 42px;
        height: 42px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        flex: 0 0 auto;
    }

    .notification-icon.success { background: #18b87a; }
    .notification-icon.warning { background: #f59e0b; }
    .notification-icon.danger { background: #ef4444; }
    .notification-icon.info { background: #5b37e5; }

    .notification-card strong,
    .notification-card span,
    .notification-card em {
        display: block;
    }

    .notification-card strong {
        color: var(--ink);
        font-size: 0.84rem;
        font-weight: 900;
    }

    .notification-card span {
        color: var(--muted);
        font-size: 0.68rem;
        font-weight: 800;
        line-height: 1.45;
        margin-top: 4px;
    }

    .notification-card em {
        color: var(--muted);
        font-size: 0.62rem;
        font-style: normal;
        font-weight: 800;
        margin-top: 8px;
    }

    .empty-panel {
        text-align: center;
        color: #9aa1bb;
        font-size: 0.8rem;
        font-weight: 900;
        padding: 44px 12px;
    }

    .empty-panel i {
        display: block;
        font-size: 1.9rem;
        margin-bottom: 10px;
    }
</style>
@endpush
