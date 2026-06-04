@extends('presensi.layout')

@section('title', 'Riwayat Kehadiran')

@section('content')
@php
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
        <a href="{{ route('presensi.index') }}" class="icon-btn plain" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <h1 class="app-title">Riwayat Kehadiran</h1>
        <span></span>
    </div>

    <div class="tab-row mb-3">
        <button class="active" type="button">Harian</button>
        <button type="button">Mingguan</button>
        <button type="button">Bulanan</button>
    </div>

    <section class="month-row mb-3">
        <button type="button" aria-label="Bulan sebelumnya"><i class="fas fa-chevron-left"></i></button>
        <strong>{{ now()->locale('id')->translatedFormat('F Y') }}</strong>
        <button type="button" aria-label="Bulan berikutnya"><i class="fas fa-chevron-right"></i></button>
    </section>

    <section class="presence-card p-3 mb-4">
        <div class="summary-strip">
            <div class="summary-box purple"><span>Hadir</span><strong>{{ $summary['hadir'] }}</strong><em>Hari</em></div>
            <div class="summary-box green"><span>Izin</span><strong>{{ $summary['izin'] }}</strong><em>Hari</em></div>
            <div class="summary-box orange"><span>Cuti</span><strong>{{ $summary['cuti'] }}</strong><em>Hari</em></div>
            <div class="summary-box red"><span>Sakit</span><strong>{{ $summary['sakit'] }}</strong><em>Hari</em></div>
        </div>
    </section>

    <section class="pb-3">
        @forelse($history as $item)
            <div class="history-day">
                <div class="history-date">{{ $item->attendance_date->locale('id')->translatedFormat('l, d M Y') }}</div>
                <div class="presence-card history-card">
                    <div class="history-main">
                        <div class="history-icon" style="--accent: {{ $typeColors[$item->attendance_type] ?? '#94a3b8' }};">
                            <i class="fas {{ $typeIcons[$item->attendance_type] ?? 'fa-calendar-check' }}"></i>
                        </div>
                        <div class="min-w-0">
                            <strong>{{ $types[$item->attendance_type] ?? ucfirst($item->attendance_type) }}</strong>
                            <span>{{ strtoupper($item->status) }}</span>
                        </div>
                    </div>
                    <div class="time-pair">
                        <div>
                            <strong>{{ $item->check_in_at ? $item->check_in_at->format('H:i') : '-' }}</strong>
                            <span>Check In</span>
                        </div>
                        <div>
                            <strong>{{ $item->check_out_at ? $item->check_out_at->format('H:i') : '-' }}</strong>
                            <span>Check Out</span>
                        </div>
                    </div>
                    @if($item->face_match_score)
                        <span class="status-badge success">Match {{ $item->face_match_score }}%</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty-state">
                <i class="fas fa-calendar-days"></i>
                <div>Belum ada riwayat kehadiran bulan ini.</div>
            </div>
        @endforelse
    </section>
</main>
@endsection

@push('styles')
<style>
    .tab-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        border-bottom: 1px solid var(--line);
    }

    .tab-row button {
        min-height: 42px;
        border: 0;
        color: var(--muted);
        background: transparent;
        font-size: 0.7rem;
        font-weight: 900;
        position: relative;
    }

    .tab-row button.active {
        color: var(--primary);
    }

    .tab-row button.active::after {
        content: "";
        position: absolute;
        left: 16px;
        right: 16px;
        bottom: -1px;
        height: 3px;
        border-radius: 99px 99px 0 0;
        background: var(--primary);
    }

    .month-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .month-row button {
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 14px;
        color: var(--ink);
        background: #fff;
        box-shadow: 0 8px 18px rgba(31,35,85,0.05);
    }

    .month-row strong {
        color: var(--ink);
        font-size: 0.95rem;
        font-weight: 900;
    }

    .summary-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 8px;
    }

    .summary-box {
        border-radius: 16px;
        padding: 12px 8px;
        text-align: center;
    }

    .summary-box span,
    .summary-box em {
        display: block;
        font-size: 0.56rem;
        font-style: normal;
        font-weight: 900;
    }

    .summary-box strong {
        display: inline-block;
        margin: 5px 0 2px;
        font-size: 1.35rem;
        line-height: 1;
        font-weight: 900;
    }

    .summary-box.purple { color: #5b37e5; background: #f1edff; }
    .summary-box.green { color: #059669; background: #e9fbf3; }
    .summary-box.orange { color: #f59e0b; background: #fff6e6; }
    .summary-box.red { color: #ef4444; background: #fff0f0; }

    .history-date {
        margin: 16px 2px 9px;
        color: var(--muted);
        font-size: 0.68rem;
        font-weight: 900;
    }

    .history-card {
        padding: 14px;
        margin-bottom: 10px;
        display: grid;
        gap: 13px;
    }

    .history-main {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .history-main strong,
    .history-main span {
        display: block;
    }

    .history-main strong {
        color: var(--ink);
        font-size: 0.88rem;
        font-weight: 900;
    }

    .history-main span {
        color: var(--muted);
        font-size: 0.65rem;
        font-weight: 900;
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

    .time-pair {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        background: #f9faff;
        border-radius: 16px;
        overflow: hidden;
    }

    .time-pair div {
        padding: 12px;
    }

    .time-pair div + div {
        border-left: 1px solid var(--line);
    }

    .time-pair strong,
    .time-pair span {
        display: block;
    }

    .time-pair strong {
        color: var(--ink);
        font-size: 1.05rem;
        font-weight: 900;
    }

    .time-pair span {
        color: var(--muted);
        font-size: 0.62rem;
        font-weight: 800;
        margin-top: 4px;
    }

    .empty-state {
        text-align: center;
        color: #9aa1bb;
        font-weight: 900;
        padding: 48px 12px;
    }

    .empty-state i {
        display: block;
        font-size: 2rem;
        margin-bottom: 10px;
    }
</style>
@endpush
