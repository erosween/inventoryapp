@extends('layout.mobile_layout')

@section('title', 'Konfirmasi Penjualan')

@section('content')
<div class="receipt-page reveal">
    <div class="d-flex align-items-center gap-3 mb-4 px-1">
        <a href="{{ $receipt->whatsapp_shared_at ? route('mobile.history') : route('mobile.visit.menu', ['id_outlet' => $receipt->id_outlet]) }}" class="receipt-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div><h5 class="fw-800 mb-0">Konfirmasi Penjualan & Konsinyasi</h5><small class="text-muted">Periksa detail sebelum disubmit</small></div>
    </div>

    <section class="confirmation-card mb-4">
        <div class="confirmation-head">
            <div><div class="confirmation-kicker">OUTLET</div><strong>{{ $outlet->nama_outlet ?? $receipt->id_outlet }}</strong><small>ID: {{ $receipt->id_outlet }}</small></div>
            <span>
                @if($items->isNotEmpty())
                    {{ number_format($receipt->total_qty, 0, ',', '.') }} PCS
                @endif
                @if($consignments->isNotEmpty())
                    {{ $consignments->count() }} KONSINYASI
                @endif
            </span>
        </div>
        <div class="confirmation-items">
            @foreach($items as $item)
                <div class="confirmation-item">
                    <div><strong>{{ $item->denom }}</strong><small>Produk / denom penjualan</small></div>
                    <div class="confirmation-qty">{{ number_format($item->qty, 0, ',', '.') }} <small>PCS</small></div>
                </div>
            @endforeach
            @if($consignments->isNotEmpty())
                <div class="confirmation-item consignment-confirmation-item">
                    <div><strong>Konsinyasi SA</strong><small>{{ $consignments->count() }} nomor perdana tercatat</small></div>
                    <div class="confirmation-qty">{{ $consignments->count() }} <small>NOMOR</small></div>
                </div>
                <div class="consignment-number-list">
                    @foreach($consignments as $number)<span><i class="fas fa-check-circle"></i>{{ $number }}</span>@endforeach
                </div>
            @endif
        </div>
        <div class="confirmation-total">
            <span>Ringkasan</span>
            <strong>
                {{ number_format($receipt->total_qty, 0, ',', '.') }} PCS
                @if($consignments->isNotEmpty())
                    · {{ $consignments->count() }} Konsinyasi
                @endif
            </strong>
        </div>
    </section>

    @if($receipt->whatsapp_shared_at)
        <section class="glass-card p-4 text-center border-0">
            <div class="receipt-success-icon"><i class="fas fa-check"></i></div>
            <h5 class="fw-800 mt-3">{{ $items->isNotEmpty() ? 'Penjualan Sudah Disubmit' : 'Konsinyasi Sudah Dicatat' }}</h5>
            <p class="text-muted small mb-3">{{ $items->isNotEmpty() ? 'Transaksi sudah masuk ke admin '.$receipt->idtap.' untuk validasi.' : 'Nomor perdana sudah terhubung ke outlet ini dan dapat dilacak dari sistem.' }}</p>
            <a href="{{ route('mobile.history') }}" class="btn btn-danger rounded-pill w-100 py-3 fw-bold">Lihat Riwayat Penjualan</a>
        </section>
    @else
        <div class="alert alert-warning rounded-4 small mb-3"><i class="fas fa-circle-info me-2"></i>{{ $items->isNotEmpty() ? 'Setelah dikonfirmasi, stok akan diamankan sementara dan transaksi dikirim ke admin untuk validasi.' : 'Setelah dikonfirmasi, nomor konsinyasi langsung dicatat ke outlet ini tanpa memotong stok.' }}</div>
        <form method="POST" action="{{ route('mobile.receipt.confirm', $receipt) }}" id="confirm-sales-form" enctype="multipart/form-data">
            @csrf
            <div class="glass-card p-3 mb-3 border-0 shadow-sm"><label for="receipt_photo" class="fw-800 small mb-2"><i class="fas fa-camera me-1 text-danger"></i>Foto Nota Bon (wajib)</label><input type="file" name="receipt_photo" id="receipt_photo" class="form-control" accept="image/jpeg,image/png,image/webp" capture="environment" required><small class="text-muted d-block mt-2">Foto nota disimpan bersama transaksi untuk pemeriksaan admin.</small></div>
            <button type="submit" class="btn btn-danger rounded-pill w-100 py-3 fw-bold shadow-sm"><i class="fas fa-check-circle me-2"></i>Konfirmasi & Submit Penjualan</button>
        </form>
    @endif
</div>
@endsection

@push('styles')
<style>
    .receipt-back{width:40px;height:40px;display:inline-flex;align-items:center;justify-content:center;flex:0 0 40px;border-radius:14px;background:#fff;color:#111827;box-shadow:0 8px 25px rgba(15,23,42,.08);text-decoration:none}
    .confirmation-card{overflow:hidden;border-radius:24px;background:#fff;box-shadow:0 16px 45px rgba(15,23,42,.09)}
    .confirmation-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px;color:#fff;background:linear-gradient(135deg,#72000d,#ed1b2f)}
    .confirmation-head strong,.confirmation-head small{display:block}.confirmation-head strong{font-size:.92rem}.confirmation-head small{margin-top:4px;font-size:.65rem;opacity:.78}.confirmation-head>span{font-size:.78rem;font-weight:900;white-space:nowrap}
    .confirmation-kicker{margin-bottom:6px;font-size:.6rem;font-weight:800;letter-spacing:.13em;opacity:.75}
    .confirmation-items{padding:6px 20px}.confirmation-item{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:16px 0;border-bottom:1px solid #edf1f5}.confirmation-item:last-child{border-bottom:0}.confirmation-item strong,.confirmation-item small{display:block}.confirmation-item strong{font-size:.82rem;color:#111827}.confirmation-item small{margin-top:4px;color:#94a3b8;font-size:.61rem;font-weight:700}.confirmation-qty{color:#d10b20;font-size:.9rem;font-weight:900;white-space:nowrap}.confirmation-qty small{display:inline;color:#64748b;font-size:.58rem}
    .confirmation-total{display:flex;align-items:center;justify-content:space-between;padding:17px 20px;background:#f8fafc;border-top:1px solid #e2e8f0}.confirmation-total span{color:#64748b;font-size:.7rem;font-weight:800}.confirmation-total strong{color:#111827;font-size:.9rem}
    .receipt-success-icon{width:64px;height:64px;margin:auto;display:grid;place-items:center;border-radius:50%;color:#fff;background:#16a066;font-size:1.5rem}
    .consignment-number-list{display:grid;gap:7px;padding:0 0 16px}.consignment-number-list span{display:flex;align-items:center;gap:7px;padding:8px 10px;border-radius:10px;background:#f0fdf4;color:#166534;font-size:.7rem;font-weight:800}.consignment-number-list i{color:#16a34a}
</style>
@endpush

@push('scripts')
<script>
localStorage.removeItem('sf_sales_form_data');
document.getElementById('confirm-sales-form')?.addEventListener('submit', function () {
    const button = this.querySelector('button[type="submit"]');
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i>Menyimpan Penjualan...';
});

document.getElementById('confirm-sales-form')?.addEventListener('submit', async function (event) {
    const input = document.getElementById('receipt_photo');
    if (!input?.files?.[0] || input.dataset.compressed === '1') return;
    event.preventDefault();
    const file = input.files[0];
    const image = new Image();
    image.onload = () => {
        const maxSide = 1600;
        const scale = Math.min(1, maxSide / Math.max(image.naturalWidth, image.naturalHeight));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
        const context = canvas.getContext('2d', { willReadFrequently: true });
        context.drawImage(image, 0, 0, canvas.width, canvas.height);

        // Nota sering difoto di dalam ruangan. Naikkan exposure hanya bila
        // memang gelap, supaya nota normal tidak menjadi terlalu putih.
        const sample = context.getImageData(0, 0, canvas.width, canvas.height);
        let luminance = 0;
        const stride = Math.max(4, Math.floor(sample.data.length / 12000) * 4);
        let count = 0;
        for (let index = 0; index < sample.data.length; index += stride) {
            luminance += (0.299 * sample.data[index]) + (0.587 * sample.data[index + 1]) + (0.114 * sample.data[index + 2]);
            count++;
        }

        if (count && (luminance / count) < 125) {
            const pixels = sample.data;
            const brightness = (luminance / count) < 80 ? 1.42 : 1.24;
            for (let index = 0; index < pixels.length; index += 4) {
                pixels[index] = Math.min(255, pixels[index] * brightness);
                pixels[index + 1] = Math.min(255, pixels[index + 1] * brightness);
                pixels[index + 2] = Math.min(255, pixels[index + 2] * brightness);
            }
            context.putImageData(sample, 0, 0);
        }

        canvas.toBlob((blob) => {
            if (!blob) return this.submit();
            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'nota-bon.jpg', { type: 'image/jpeg' }));
            input.files = transfer.files;
            input.dataset.compressed = '1';
            this.requestSubmit();
        }, 'image/jpeg', 0.84);
    };
    image.onerror = () => this.submit();
    image.src = URL.createObjectURL(file);
});
</script>
@endpush
