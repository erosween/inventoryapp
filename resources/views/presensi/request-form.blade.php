@extends('presensi.layout')

@section('title', $module['title'])

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
        <a href="{{ route('presensi.menu') }}" class="icon-btn plain" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <h1 class="app-title">Ajukan Pengajuan</h1>
        <span></span>
    </div>

    <section class="presence-card p-3 mb-3 request-heading" style="--accent: {{ $module['accent'] }};">
        <i class="fas {{ $module['icon'] }}"></i>
        <div class="min-w-0">
            <div class="eyebrow mb-1">Jenis Pengajuan</div>
            <h2>{{ $module['title'] }}</h2>
            <p>{{ $module['subtitle'] }}</p>
        </div>
    </section>

    <form action="{{ route('presensi.request.store', $type) }}" method="POST" enctype="multipart/form-data" class="presence-card p-3 mb-4">
        @csrf
        <label class="field-label">Jenis Pengajuan</label>
        <select name="category" class="presence-input mb-3" required>
            <option value="">Pilih jenis pengajuan</option>
            @foreach($module['categories'] as $category)
                <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
            @endforeach
        </select>

        <div class="date-grid">
            <div>
                <label class="field-label">Tanggal Mulai</label>
                <input type="date" name="start_date" class="presence-input mb-3" value="{{ old('start_date', now()->toDateString()) }}" required>
            </div>
            <div>
                <label class="field-label">Tanggal Selesai</label>
                <input type="date" name="end_date" class="presence-input mb-3" value="{{ old('end_date') }}">
            </div>
        </div>

        @if(in_array($type, ['lembur', 'ubah-kehadiran'], true))
            <div class="date-grid">
                <div>
                    <label class="field-label">Jam Mulai</label>
                    <input type="time" name="start_time" class="presence-input mb-3" value="{{ old('start_time') }}">
                </div>
                <div>
                    <label class="field-label">Jam Selesai</label>
                    <input type="time" name="end_time" class="presence-input mb-3" value="{{ old('end_time') }}">
                </div>
            </div>
        @endif

        @if(in_array($type, ['reimbursement', 'pengeluaran'], true))
            <label class="field-label">Nominal</label>
            <input type="number" name="amount" class="presence-input mb-3" value="{{ old('amount') }}" placeholder="0" inputmode="numeric">
        @endif

        @if($type === 'cuti')
            <label class="field-label">Delegasi Tugas</label>
            <input type="text" name="delegation_to" class="presence-input mb-3" value="{{ old('delegation_to') }}" placeholder="Nama rekan pengganti">
        @endif

        <label class="field-label">Keterangan</label>
        <textarea name="description" rows="5" class="presence-input textarea mb-2" placeholder="Tulis keterangan pengajuan..." maxlength="1500" required>{{ old('description') }}</textarea>
        <div class="text-end text-muted fw-800 mb-3" style="font-size: 0.62rem;">Maks. 1500 karakter</div>

        <label class="field-label">Lampiran Opsional</label>
        <label class="upload-box mb-4">
            <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf">
            <i class="fas fa-upload"></i>
            <span>Tambah Lampiran</span>
            <em>Format JPG, PNG, PDF. Maks 4MB</em>
        </label>

        <button type="submit" class="primary-btn w-100">Kirim Pengajuan</button>
    </form>

    <section class="pb-3">
        <div class="section-row">
            <h2>Riwayat {{ $module['title'] }}</h2>
            <span>{{ $requests->count() }} item</span>
        </div>

        @forelse($requests as $request)
            <div class="request-row">
                <div class="min-w-0">
                    <strong>{{ $request->category }}</strong>
                    <span>{{ $request->start_date?->format('d M Y') }} @if($request->end_date && !$request->end_date->equalTo($request->start_date)) - {{ $request->end_date->format('d M Y') }} @endif</span>
                </div>
                <em class="{{ $statusClass[$request->status] ?? 'warning' }}">{{ strtoupper($request->status) }}</em>
            </div>
        @empty
            <div class="empty-panel">Belum ada riwayat pengajuan.</div>
        @endforelse
    </section>
</main>
@endsection

@push('styles')
<style>
    .request-heading {
        display: flex;
        align-items: center;
        gap: 13px;
    }

    .request-heading > i {
        width: 50px;
        height: 50px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--accent);
        background: color-mix(in srgb, var(--accent) 12%, white);
        flex: 0 0 auto;
    }

    .request-heading h2 {
        margin: 0;
        color: var(--ink);
        font-size: 0.98rem;
        font-weight: 900;
    }

    .request-heading p {
        margin: 3px 0 0;
        color: var(--muted);
        font-size: 0.68rem;
        font-weight: 800;
        line-height: 1.38;
    }

    .textarea {
        min-height: 128px;
        padding-top: 14px;
        resize: none;
    }

    .date-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .date-grid .presence-input {
        margin-bottom: 0 !important;
    }

    .upload-box {
        min-height: 58px;
        border: 1px dashed rgba(91,55,229,0.45);
        border-radius: 14px;
        background: #fbfaff;
        color: var(--primary);
        display: grid;
        place-items: center;
        text-align: center;
        padding: 12px;
        cursor: pointer;
    }

    .upload-box input {
        display: none;
    }

    .upload-box span,
    .upload-box em {
        display: block;
    }

    .upload-box span {
        margin-top: 4px;
        font-size: 0.76rem;
        font-weight: 900;
    }

    .upload-box em {
        color: var(--muted);
        font-size: 0.6rem;
        font-style: normal;
        font-weight: 800;
        margin-top: 2px;
    }

    .request-row,
    .empty-panel {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 18px;
        padding: 14px;
        margin-bottom: 10px;
        box-shadow: 0 8px 22px rgba(31,35,85,0.05);
    }

    .request-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .request-row strong,
    .request-row span {
        display: block;
    }

    .request-row strong {
        color: var(--ink);
        font-size: 0.82rem;
        font-weight: 900;
    }

    .request-row span {
        color: var(--muted);
        font-size: 0.66rem;
        font-weight: 800;
        margin-top: 3px;
    }

    .request-row em {
        flex: 0 0 auto;
        border-radius: 999px;
        padding: 6px 9px;
        font-size: 0.55rem;
        font-style: normal;
        font-weight: 900;
    }

    .request-row em.warning { color: #b45309; background: #fef3c7; }
    .request-row em.success { color: #047857; background: #dcfce7; }
    .request-row em.danger { color: #c62828; background: #fee2e2; }

    .empty-panel {
        color: #9aa1bb;
        font-size: 0.8rem;
        font-weight: 900;
        text-align: center;
    }
</style>
@endpush
