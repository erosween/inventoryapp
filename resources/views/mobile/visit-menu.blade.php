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
    @if($visit?->gps_status === 'rejected')
        <div class="alert alert-danger rounded-4 small"><i class="fas fa-circle-xmark me-2"></i>Foto visit ditolak admin. Ambil ulang foto tampak depan dan foto etalase agar aktivitas dapat dilanjutkan.</div>
    @endif
    @if($needsStockOwner ?? false)
        <form method="POST" action="{{ route('mobile.visit.stock-owner') }}" class="visit-stock-owner-panel">
            @csrf
            <input type="hidden" name="id_outlet" value="{{ $outlet->id_outlet }}">
            <label for="visit-stock-owner"><i class="fas fa-boxes-stacked me-2"></i>SF Pemilik Stok</label>
            <select id="visit-stock-owner" name="stock_owner_idsf" required>
                <option value="">Pilih SF yang stoknya akan dipotong</option>
                @foreach($stockOwners as $stockOwner)
                    <option value="{{ $stockOwner->idsf }}">{{ $stockOwner->namasf }} ({{ $stockOwner->idsf }})</option>
                @endforeach
            </select>
            <small>Wajib dipilih sebelum input penjualan. Hanya SF dari TAP {{ session('idtap') }} yang tersedia.</small>
            <button type="submit"><i class="fas fa-check me-1"></i>Gunakan Stok SF Ini</button>
        </form>
    @endif
    @if($photoDone && $visit->gps_status === 'pending_admin' && $visit->visit_type !== 'non_digipos')
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
            <span class="visit-action-icon"><i class="fas fa-check"></i></span><span><strong>2 Foto Visit Sudah Diambil</strong><small>{{ $visit->gps_status === 'pending_admin' && $visit->visit_type !== 'non_digipos' ? 'Lokasi berbeda, menunggu persetujuan admin' : 'Foto visit lengkap dan dapat lanjut input penjualan' }}</small></span>
        </div>
    @else
        <a href="{{ route('mobile.visit', $outlet ? ['id_outlet' => $outlet->id_outlet] : []) }}" class="visit-action visit-action-camera text-decoration-none">
            <span class="visit-action-icon"><i class="fas fa-camera"></i></span><span><strong>{{ $visit?->gps_status === 'rejected' ? 'Ambil Ulang 2 Foto Visit' : 'Input 2 Foto Visit' }}</strong><small>Foto depan, foto dalam, dan verifikasi lokasi GPS</small></span><i class="fas fa-chevron-right visit-action-chevron"></i>
        </a>
    @endif
    @if($canSell)
        <a href="{{ route('mobile.form', $outlet ? ['id_outlet' => $outlet->id_outlet] : []) }}" class="visit-action visit-action-sale text-decoration-none"><span class="visit-action-icon"><i class="fas fa-cart-plus"></i></span><span><strong>Input Penjualan</strong><small>Catat penjualan produk TSEL/SA</small></span><i class="fas fa-chevron-right visit-action-chevron"></i></a>
    @else
        <div class="visit-action visit-action-locked"><span class="visit-action-icon"><i class="fas fa-lock"></i></span><span><strong>Input Penjualan Terkunci</strong><small>{{ $photoDone ? 'Menunggu persetujuan admin' : 'Lengkapi foto depan dan foto dalam terlebih dahulu' }}</small></span></div>
    @endif
    @if($photoDone)
        <details class="visit-checkout-panel">
            <summary><i class="fas fa-door-open"></i><span><strong>Check-out Tanpa Penjualan</strong><small>Outlet tutup, pemilik tidak ada, atau tidak bisa dilayani</small></span><i class="fas fa-chevron-down"></i></summary>
            <form method="POST" action="{{ route('mobile.visit.checkout') }}" class="visit-checkout-form" data-checkout-form>
                @csrf
                <input type="hidden" name="id_outlet" value="{{ $outlet->id_outlet }}">
                <label for="checkout-reason">Alasan check-out</label>
                <select id="checkout-reason" name="checkout_reason" required>
                    <option value="">Pilih alasan...</option>
                    <option value="outlet_tutup">Outlet tutup</option>
                    <option value="pemilik_tidak_ada">Pemilik / penjaga tidak ada</option>
                    <option value="menolak_visit">Outlet menolak visit</option>
                    <option value="lainnya">Lainnya</option>
                </select>
                <label for="checkout-notes">Catatan (opsional)</label>
                <textarea id="checkout-notes" name="checkout_notes" rows="2" maxlength="500" placeholder="Tambahkan keterangan singkat..."></textarea>
                <button type="submit"><i class="fas fa-check"></i> Konfirmasi Check-out</button>
            </form>
        </details>
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
    .visit-stock-owner-panel { margin-bottom: 12px; padding: 14px; border: 1px solid #fde68a; border-radius: 16px; background: #fffbeb; }
    .visit-stock-owner-panel label, .visit-stock-owner-panel small { display: block; }
    .visit-stock-owner-panel label { margin-bottom: 8px; color: #92400e; font-size: .72rem; font-weight: 900; }
    .visit-stock-owner-panel select { width: 100%; min-height: 44px; padding: 9px 11px; border: 1px solid #fcd34d; border-radius: 12px; background: #fff; font-size: .72rem; font-weight: 700; }
    .visit-stock-owner-panel small { margin-top: 7px; color: #78716c; font-size: .6rem; font-weight: 700; line-height: 1.45; }
    .visit-stock-owner-panel button { width: 100%; margin-top: 11px; padding: 10px 12px; border: 0; border-radius: 999px; background: #b45309; color: #fff; font-size: .68rem; font-weight: 900; }
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
    .visit-checkout-panel { margin-top: 4px; border: 1px solid #fee2e2; border-radius: 17px; background: #fffafa; overflow: hidden; }
    .visit-checkout-panel summary { display: grid; grid-template-columns: 38px minmax(0,1fr) auto; align-items: center; gap: 12px; padding: 13px 14px; color: #991b1b; cursor: pointer; list-style: none; }
    .visit-checkout-panel summary::-webkit-details-marker { display: none; }
    .visit-checkout-panel summary > i:first-child { display: grid; width: 38px; height: 38px; place-items: center; border-radius: 13px; background: #fff; }
    .visit-checkout-panel summary strong, .visit-checkout-panel summary small { display: block; }
    .visit-checkout-panel summary strong { font-size: .8rem; }
    .visit-checkout-panel summary small { margin-top: 2px; color: #7f1d1d; font-size: .58rem; line-height: 1.35; }
    .visit-checkout-form { padding: 0 14px 14px; }
    .visit-checkout-form label { display: block; margin: 9px 0 5px; color: #475569; font-size: .62rem; font-weight: 800; }
    .visit-checkout-form select, .visit-checkout-form textarea { width: 100%; border: 1px solid #e2e8f0; border-radius: 11px; background: #fff; color: #1f2937; font: inherit; font-size: .72rem; }
    .visit-checkout-form select { min-height: 42px; padding: 0 10px; }
    .visit-checkout-form textarea { padding: 9px 10px; resize: vertical; }
    .visit-checkout-form button { width: 100%; min-height: 42px; margin-top: 11px; border: 0; border-radius: 12px; background: #b91c1c; color: #fff; font-size: .72rem; font-weight: 900; }
</style>
@endpush

@if($photoDone)
@push('scripts')
<script>
document.querySelector('[data-checkout-form]')?.addEventListener('submit', function (event) {
    event.preventDefault();
    const form = this;
    Swal.fire({
        icon: 'warning',
        title: 'Check-out tanpa penjualan?',
        text: 'Visit tetap dihitung sebagai Actual Call dan outlet berikutnya akan dibuka.',
        showCancelButton: true,
        confirmButtonText: 'Ya, Check-out',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#b91c1c'
    }).then(result => {
        if (result.isConfirmed) form.submit();
    });
});
</script>
@endpush
@endif

@if($photoDone && $visit->gps_status === 'pending_admin' && $visit->visit_type !== 'non_digipos')
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
        if (result.gps_status === 'rejected') {
            message.textContent = result.message;
            Swal.fire({
                icon: 'error',
                title: 'Foto Ditolak',
                text: result.message,
                confirmButtonText: 'Ambil Ulang Foto',
                confirmButtonColor: 'var(--primary)'
            }).then(() => window.location.reload());
            return;
        }
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
