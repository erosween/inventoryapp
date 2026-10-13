@extends('layout.mobile_layout')

@section('title', 'Aktivitas Visit')

@section('content')
<div class="glass-card visit-menu-shell">
    <div class="visit-menu-head">
        <a href="{{ route('mobile.index') }}" class="visit-back-button" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div><h5 class="fw-800 mb-1">Aktivitas Visit</h5><small class="text-muted">Pilih aktivitas untuk outlet ini</small></div>
    </div>
    @if($outlet)
        <div class="visit-outlet-summary">
            <div><div class="fw-800">{{ $outlet->nama_outlet }}</div><small class="text-muted">ID: {{ $outlet->id_outlet }}</small></div>
            @if($photoDone)
                <span class="visit-progress-badge">SEDANG BERLANGSUNG</span>
            @endif
        </div>
    @endif
    @if(request('distance') && (float) request('distance') > 10)
        <div class="alert alert-danger rounded-4 small"><i class="fas fa-location-dot me-2"></i>Lokasi tidak sesuai outlet ({{ number_format((float) request('distance'), 0) }} m). Foto visit akan menunggu konfirmasi admin.</div>
    @endif
    @if($photoDone && $visit->gps_status === 'pending_admin')
        <div class="alert alert-warning rounded-4 small" id="visit-approval-alert">
            <div class="d-flex align-items-start gap-2">
                <i class="fas fa-hourglass-half mt-1"></i>
                <span id="visit-approval-message">Visit sedang berlangsung dan menunggu persetujuan admin. Outlet lain tetap terkunci.</span>
            </div>
            <button type="button" class="btn btn-sm btn-warning rounded-pill fw-bold mt-3" id="refresh-visit-status" data-status-url="{{ route('mobile.visit.status', ['id_outlet' => $outlet->id_outlet]) }}">
                <i class="fas fa-rotate me-1"></i> Refresh Status
            </button>
        </div>
    @elseif($canSell)
        <div class="alert alert-info rounded-4 small"><i class="fas fa-circle-info me-2"></i>Visit sedang berlangsung. Selesaikan input penjualan untuk membuka outlet berikutnya.</div>
    @endif
    @if($photoDone)
        <div class="visit-action visit-action-success" role="status">
            <span class="visit-action-icon"><i class="fas fa-check"></i></span><span><strong>2 Foto Visit Sudah Diambil</strong><small>{{ $visit->gps_status === 'pending_admin' ? 'Lokasi berbeda, menunggu persetujuan admin' : 'Foto visit lengkap dan tidak dapat diubah' }}</small></span>
        </div>
    @else
        <a href="{{ route('mobile.visit', $outlet ? ['id_outlet' => $outlet->id_outlet] : []) }}" class="visit-action visit-action-camera text-decoration-none">
            <span class="visit-action-icon"><i class="fas fa-camera"></i></span><span><strong>Input 2 Foto Visit</strong><small>Foto depan, foto dalam, dan verifikasi lokasi GPS</small></span><i class="fas fa-chevron-right visit-action-chevron"></i>
        </a>
    @endif
    @if($canSell)
        <a href="{{ route('mobile.form', $outlet ? ['id_outlet' => $outlet->id_outlet] : []) }}" class="visit-action visit-action-sale text-decoration-none"><span class="visit-action-icon"><i class="fas fa-cart-plus"></i></span><span><strong>Input Penjualan</strong><small>Catat penjualan produk TSEL/SA</small></span><i class="fas fa-chevron-right visit-action-chevron"></i></a>
    @else
        <div class="visit-action visit-action-locked"><span class="visit-action-icon"><i class="fas fa-lock"></i></span><span><strong>Input Penjualan Terkunci</strong><small>{{ $photoDone ? 'Menunggu persetujuan admin' : 'Lengkapi foto depan dan foto dalam terlebih dahulu' }}</small></span></div>
    @endif
</div>
@endsection

@push('styles')
<style>
    .visit-menu-shell {
        padding: 18px;
        border-radius: 22px;
    }
    .visit-menu-head {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }
    .visit-menu-head h5 { font-size: 1.05rem; }
    .visit-menu-head small { font-size: .68rem; }
    .visit-back-button {
        display: grid;
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        place-items: center;
        border-radius: 12px;
        background: #f8fafc;
        color: #1f2937;
        text-decoration: none;
    }
    .visit-outlet-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
        padding: 13px 14px;
        border-radius: 16px;
        background: #f8fafc;
        font-size: .86rem;
    }
    .visit-outlet-summary small { font-size: .64rem; }
    .visit-progress-badge {
        flex: 0 0 auto;
        padding: 6px 10px;
        border-radius: 999px;
        background: #fbbf24;
        color: #422006;
        font-size: .54rem;
        font-weight: 900;
    }
    .visit-menu-shell .alert {
        margin-bottom: 12px;
        padding: 11px 13px;
        border-radius: 15px !important;
        font-size: .68rem;
        line-height: 1.5;
    }
    .visit-action {
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr) auto;
        align-items: center;
        gap: 12px;
        min-height: 72px;
        margin-bottom: 10px;
        padding: 12px 14px;
        border: 1px solid #edf2f7;
        border-radius: 17px;
        color: #1f2937;
        background: #f8fafc;
    }
    .visit-action:last-child { margin-bottom: 0; }
    .visit-action-success { background: #ecf8f2; }
    .visit-action-camera { background: #fff4f5; }
    .visit-action-locked { color: #94a3b8; background: #f8fafc; }
    .visit-action-icon {
        display: grid;
        width: 38px;
        height: 38px;
        place-items: center;
        border-radius: 13px;
        background: #fff;
        color: var(--primary);
        font-size: .95rem;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
    }
    .visit-action-success .visit-action-icon { color: #059669; }
    .visit-action-locked .visit-action-icon { color: #94a3b8; }
    .visit-action strong {
        display: block;
        font-size: .88rem;
        line-height: 1.25;
    }
    .visit-action small {
        display: block;
        margin-top: 3px;
        color: #64748b;
        font-size: .63rem;
        line-height: 1.4;
    }
    .visit-action-chevron { color: #cbd5e1; font-size: .7rem; }
</style>
@endpush

@if($photoDone && $visit->gps_status === 'pending_admin')
@push('scripts')
<script>
document.getElementById('refresh-visit-status')?.addEventListener('click', async function () {
    const button = this;
    const message = document.getElementById('visit-approval-message');
    const original = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-rotate fa-spin me-1"></i> Mengecek...';

    try {
        const response = await fetch(button.dataset.statusUrl, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
        });
        if (!response.ok) throw new Error('Status visit tidak dapat dimuat.');

        const result = await response.json();
        if (result.can_sell) {
            button.innerHTML = '<i class="fas fa-check me-1"></i> Disetujui';
            message.textContent = result.message;
            window.location.reload();
            return;
        }

        message.textContent = result.message;
        Swal.fire({
            icon: 'info',
            title: 'Masih Menunggu',
            text: result.message,
            timer: 1800,
            showConfirmButton: false,
            toast: true,
            position: 'top'
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Gagal Mengecek',
            text: error.message,
            confirmButtonColor: 'var(--primary)'
        });
    } finally {
        if (!button.innerHTML.includes('Disetujui')) {
            button.disabled = false;
            button.innerHTML = original;
        }
    }
});
</script>
@endpush
@endif
