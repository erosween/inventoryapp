@extends('layout.mobile_layout')

@section('title', 'e-Bon Penjualan')

@section('content')
<div class="receipt-page reveal">
    <div class="d-flex align-items-center gap-3 mb-3 px-1">
        <a href="{{ $receipt->whatsapp_shared_at ? route('mobile.history') : route('mobile.visit.menu', ['id_outlet' => $receipt->id_outlet]) }}" class="receipt-back" aria-label="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h5 class="fw-800 mb-0">e-Bon Penjualan</h5>
            <small class="text-muted">Rincian transaksi outlet</small>
        </div>
    </div>

    <section class="thermal-preview-card mb-4">
        <div class="thermal-preview-heading">
            <div>
                <div class="receipt-kicker">PREVIEW NOTA JPEG</div>
                <strong>{{ $outlet->nama_outlet ?? $receipt->id_outlet }}</strong>
            </div>
            <span>{{ number_format($receipt->total_qty, 0, ',', '.') }} PCS</span>
        </div>
        <div class="thermal-image-wrap">
            <img src="{{ route('mobile.receipt.image', $receipt) }}" alt="Nota penjualan {{ $receipt->receipt_number }}" class="thermal-receipt-image" loading="eager">
            <div class="visually-hidden">
                Outlet {{ $outlet->nama_outlet ?? $receipt->id_outlet }}.
                @foreach($items as $item)
                    {{ $item->denom }} {{ number_format($item->qty, 0, ',', '.') }} pcs.
                @endforeach
                Total {{ number_format($receipt->total_qty, 0, ',', '.') }} pcs.
            </div>
        </div>
        <a href="{{ route('mobile.receipt.image', $receipt) }}" download="{{ $receipt->receipt_number }}.jpg" class="thermal-download">
            <i class="fas fa-download me-2"></i>Unduh Nota JPEG
        </a>
    </section>

    @if($receipt->whatsapp_shared_at)
        <section class="glass-card p-4 text-center border-0">
            <div class="receipt-success-icon"><i class="fas fa-check"></i></div>
            <h5 class="fw-800 mt-3">e-Bon Sudah Dikirim</h5>
            <p class="text-muted small mb-3">Transaksi sudah masuk ke admin <strong>{{ $receipt->idtap }}</strong> untuk validasi uang.</p>
            <div class="bg-light rounded-4 p-3 mb-3 small">
                WhatsApp owner: <strong>+{{ $receipt->owner_whatsapp }}</strong><br>
                Dikirim: {{ $receipt->whatsapp_shared_at->format('d/m/Y H:i') }}
            </div>
            <a href="{{ route('mobile.history') }}" class="btn btn-danger rounded-pill w-100 py-3 fw-bold">Lihat Riwayat Transaksi</a>
        </section>
    @else
        <section class="glass-card p-4 border-0">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="wa-icon"><i class="fab fa-whatsapp"></i></div>
                <div>
                    <h6 class="fw-800 mb-1">Kirim ke Owner Outlet</h6>
                    <small class="text-muted">Masukkan nomor owner, lalu pilih WhatsApp saat menu berbagi terbuka.</small>
                </div>
            </div>

            <label for="owner-whatsapp" class="form-label fw-bold small">Nomor WhatsApp Owner</label>
            <div class="input-group mb-2">
                <span class="input-group-text border-0 bg-light fw-bold">WA</span>
                <input type="tel" id="owner-whatsapp" class="form-control border-0 bg-light py-3" inputmode="tel" autocomplete="tel" placeholder="Contoh: 0812 3456 7890" value="{{ $receipt->owner_whatsapp ? '+' . $receipt->owner_whatsapp : '' }}">
            </div>
            <div id="wa-error" class="small text-danger fw-bold mb-3 d-none"></div>

            <button type="button" id="open-whatsapp" class="btn btn-success rounded-pill w-100 py-3 fw-bold">
                <i class="fab fa-whatsapp me-2"></i>Kirim Nota JPEG ke WhatsApp
            </button>

            <div id="confirm-wa-section" class="{{ $receipt->whatsapp_opened_at ? '' : 'd-none' }} mt-4 pt-4 border-top">
                <div class="alert alert-warning rounded-4 small mb-3">
                    <i class="fas fa-circle-info me-2"></i>Setelah nota JPEG terkirim di WhatsApp, kembali ke halaman ini lalu konfirmasi.
                </div>
                <form method="POST" action="{{ route('mobile.receipt.confirm', $receipt) }}">
                    @csrf
                    <button type="submit" class="btn btn-danger rounded-pill w-100 py-3 fw-bold">
                        <i class="fas fa-check-circle me-2"></i>Saya Sudah Mengirim e-Bon
                    </button>
                </form>
            </div>
        </section>
    @endif
</div>
@endsection

@push('styles')
<style>
    .receipt-back {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 40px;
        border-radius: 14px;
        background: #fff;
        color: #111827;
        box-shadow: 0 8px 25px rgba(15, 23, 42, .08);
        text-decoration: none;
    }
    .thermal-preview-card {
        overflow: hidden;
        border-radius: 26px;
        background: #fff;
        box-shadow: 0 16px 45px rgba(15, 23, 42, .09);
    }
    .thermal-preview-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        color: #fff;
        background: linear-gradient(135deg, #72000d, #ed1b2f);
    }
    .thermal-preview-heading strong { display: block; font-size: .84rem; }
    .thermal-preview-heading > span { font-size: .72rem; font-weight: 900; white-space: nowrap; }
    .receipt-kicker { margin-bottom: 6px; font-size: .62rem; font-weight: 800; letter-spacing: .13em; opacity: .8; }
    .thermal-image-wrap { padding: 20px; background: #eef2f7; text-align: center; }
    .thermal-receipt-image {
        display: block;
        width: min(100%, 360px);
        height: auto;
        margin: 0 auto;
        border: 1px solid #d9dde5;
        box-shadow: 0 12px 30px rgba(15, 23, 42, .12);
    }
    .thermal-download {
        display: block;
        padding: 14px 20px;
        color: #475569;
        font-size: .72rem;
        font-weight: 800;
        text-align: center;
        text-decoration: none;
        border-top: 1px solid #eef2f7;
    }
    .wa-icon, .receipt-success-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
    }
    .wa-icon { width: 48px; height: 48px; flex: 0 0 48px; color: #128c4e; background: #e7f8ef; font-size: 1.4rem; }
    .receipt-success-icon { width: 64px; height: 64px; color: #fff; background: #16a066; font-size: 1.5rem; }
</style>
@endpush

@if(!$receipt->whatsapp_shared_at)
@push('scripts')
<script>
document.getElementById('open-whatsapp').addEventListener('click', async function() {
    const button = this;
    const error = document.getElementById('wa-error');
    const number = document.getElementById('owner-whatsapp').value.trim();

    error.classList.add('d-none');
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i>Menyiapkan e-Bon...';

    try {
        const response = await fetch(@json(route('mobile.receipt.whatsapp', $receipt)), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token()),
            },
            body: JSON.stringify({ owner_whatsapp: number }),
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Nomor WhatsApp tidak dapat diproses.');
        }

        const imageResponse = await fetch(result.image_url, {credentials: 'same-origin', cache: 'no-store'});
        if (!imageResponse.ok) throw new Error('Nota JPEG tidak dapat dibuat.');

        const imageBlob = await imageResponse.blob();
        const imageFile = typeof File === 'function'
            ? new File([imageBlob], result.image_filename, {type: 'image/jpeg'})
            : null;
        const shareData = imageFile
            ? {files: [imageFile], title: 'Nota Penjualan SURYA', text: result.share_text}
            : null;

        if (shareData && navigator.share && (!navigator.canShare || navigator.canShare(shareData))) {
            await navigator.share(shareData);
        } else {
            const downloadUrl = URL.createObjectURL(imageBlob);
            const downloadLink = document.createElement('a');
            downloadLink.href = downloadUrl;
            downloadLink.download = result.image_filename;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            downloadLink.remove();
            setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
            window.location.href = result.whatsapp_url;
        }

        document.getElementById('confirm-wa-section').classList.remove('d-none');
        button.disabled = false;
        button.innerHTML = '<i class="fab fa-whatsapp me-2"></i>Kirim Nota JPEG Lagi';
    } catch (exception) {
        error.textContent = exception.name === 'AbortError' ? 'Pengiriman dibatalkan. Nota belum dikonfirmasi terkirim.' : exception.message;
        error.classList.remove('d-none');
        button.disabled = false;
        button.innerHTML = '<i class="fab fa-whatsapp me-2"></i>Kirim Nota JPEG ke WhatsApp';
    }
});
</script>
@endpush
@endif
