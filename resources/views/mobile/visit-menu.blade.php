@extends('layout.mobile_layout')

@section('title', 'Aktivitas Visit')

@section('content')
<div class="glass-card p-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('mobile.index') }}" class="text-dark"><i class="fas fa-arrow-left fs-5"></i></a>
        <div><h5 class="fw-800 mb-1">Aktivitas Visit</h5><small class="text-muted">Pilih aktivitas untuk outlet ini</small></div>
    </div>
    @if($outlet)
        <div class="rounded-4 p-3 mb-3 bg-light d-flex align-items-center justify-content-between gap-2">
            <div><div class="fw-800">{{ $outlet->nama_outlet }}</div><small class="text-muted">ID: {{ $outlet->id_outlet }}</small></div>
            @if($photoDone)
                <span class="badge bg-warning text-dark rounded-pill px-3 py-2">SEDANG BERLANGSUNG</span>
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
        <div class="d-flex align-items-center gap-3 p-4 mb-3 rounded-4 bg-success bg-opacity-10 text-dark" role="status">
            <i class="fas fa-check-circle text-success fs-2"></i><span><strong class="d-block fs-5">2 Foto Visit Sudah Diambil</strong><small class="text-muted">{{ $visit->gps_status === 'pending_admin' ? 'Lokasi berbeda, menunggu persetujuan admin' : 'Foto visit sudah lengkap dan tidak dapat diubah' }}</small></span>
        </div>
    @else
        <a href="{{ route('mobile.visit', $outlet ? ['id_outlet' => $outlet->id_outlet] : []) }}" class="d-flex align-items-center gap-3 p-4 mb-3 rounded-4 text-decoration-none bg-danger bg-opacity-10 text-dark">
            <i class="fas fa-camera text-danger fs-2"></i><span><strong class="d-block fs-5">Input 2 Foto Visit</strong><small class="text-muted">Foto depan, foto dalam, dan verifikasi lokasi GPS</small></span>
        </a>
    @endif
    @if($canSell)
        <a href="{{ route('mobile.form', $outlet ? ['id_outlet' => $outlet->id_outlet] : []) }}" class="d-flex align-items-center gap-3 p-4 rounded-4 text-decoration-none bg-light text-dark"><i class="fas fa-cart-plus text-danger fs-2"></i><span><strong class="d-block fs-5">Input Penjualan</strong><small class="text-muted">Catat penjualan produk TSEL/SA</small></span></a>
    @else
        <div class="d-flex align-items-center gap-3 p-4 rounded-4 bg-light text-muted opacity-75"><i class="fas fa-lock fs-2"></i><span><strong class="d-block fs-5">Input Penjualan Terkunci</strong><small>{{ $photoDone ? 'Menunggu persetujuan admin' : 'Lengkapi foto depan dan foto dalam terlebih dahulu' }}</small></span></div>
    @endif
</div>
@endsection

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
