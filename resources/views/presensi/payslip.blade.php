@extends('presensi.layout')

@section('title', 'Slip Gaji')

@section('content')
<main class="app-content">
    <div class="app-topbar">
        <a href="{{ route('presensi.menu') }}" class="icon-btn plain" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <h1 class="app-title">Slip Gaji</h1>
        <span></span>
    </div>

    <section class="presence-card p-4 text-center payroll-empty">
        <i class="fas fa-file-invoice"></i>
        <h2>Belum ada slip gaji</h2>
        <p>Halaman ini siap dihubungkan ke modul payroll untuk menampilkan daftar slip gaji dan download PDF.</p>
        <a href="{{ route('presensi.menu') }}" class="outline-btn w-100 mt-3">Kembali ke Pengajuan</a>
    </section>
</main>
@endsection

@push('styles')
<style>
    .payroll-empty i {
        width: 68px;
        height: 68px;
        border-radius: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        background: var(--primary-soft);
        font-size: 1.55rem;
        margin-bottom: 16px;
    }

    .payroll-empty h2 {
        margin: 0 0 8px;
        color: var(--ink);
        font-size: 1.08rem;
        font-weight: 900;
    }

    .payroll-empty p {
        margin: 0;
        color: var(--muted);
        font-size: 0.76rem;
        font-weight: 800;
        line-height: 1.6;
    }
</style>
@endpush
