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

    <section class="receipt-card mb-4">
        <div class="receipt-brand">
            <div>
                <div class="receipt-kicker">SURYA DIGITAL RECEIPT</div>
                <h4 class="mb-1 fw-800">{{ $outlet->nama_outlet ?? $receipt->id_outlet }}</h4>
                <small>{{ $receipt->id_outlet }}</small>
            </div>
            <div class="receipt-check"><i class="fas fa-receipt"></i></div>
        </div>

        <div class="receipt-meta">
            <div><span>No. Bon</span><strong>{{ $receipt->receipt_number }}</strong></div>
            <div><span>Tanggal</span><strong>{{ $receipt->tgl->format('d/m/Y') }}</strong></div>
            <div><span>Sales</span><strong>{{ session('mobile_sf_name') }}</strong></div>
            <div><span>TAP</span><strong>{{ $receipt->idtap }}</strong></div>
        </div>

        <div class="receipt-items">
            @foreach($items as $item)
                @php($subtotal = (int) $item->qty * (int) $item->harga_jual)
                <div class="receipt-item">
                    <div class="flex-grow-1 pe-2">
                        <strong>{{ $item->denom }}</strong>
                        <small>{{ number_format($item->qty, 0, ',', '.') }} pcs × Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</small>
                    </div>
                    <strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
                </div>
            @endforeach
        </div>

        <div class="receipt-total-row">
            <div><span>Total Produk</span><strong>{{ number_format($receipt->total_qty, 0, ',', '.') }} pcs</strong></div>
            <div class="grand-total"><span>Grand Total</span><strong>Rp {{ number_format($receipt->total_amount, 0, ',', '.') }}</strong></div>
        </div>
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
                    <small class="text-muted">Wajib dikirim sebelum transaksi masuk ke admin.</small>
                </div>
            </div>

            <label for="owner-whatsapp" class="form-label fw-bold small">Nomor WhatsApp Owner</label>
            <div class="input-group mb-2">
                <span class="input-group-text border-0 bg-light fw-bold">WA</span>
                <input type="tel" id="owner-whatsapp" class="form-control border-0 bg-light py-3" inputmode="tel" autocomplete="tel" placeholder="Contoh: 0812 3456 7890" value="{{ $receipt->owner_whatsapp ? '+' . $receipt->owner_whatsapp : '' }}">
            </div>
            <div id="wa-error" class="small text-danger fw-bold mb-3 d-none"></div>

            <button type="button" id="open-whatsapp" class="btn btn-success rounded-pill w-100 py-3 fw-bold">
                <i class="fab fa-whatsapp me-2"></i>Buka WhatsApp &amp; Kirim e-Bon
            </button>

            <div id="confirm-wa-section" class="{{ $receipt->whatsapp_opened_at ? '' : 'd-none' }} mt-4 pt-4 border-top">
                <div class="alert alert-warning rounded-4 small mb-3">
                    <i class="fas fa-circle-info me-2"></i>Setelah pesan terkirim di WhatsApp, kembali ke halaman ini lalu konfirmasi.
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
    .receipt-card {
        overflow: hidden;
        border-radius: 26px;
        background: #fff;
        box-shadow: 0 16px 45px rgba(15, 23, 42, .09);
    }
    .receipt-brand {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 24px;
        color: #fff;
        background: linear-gradient(135deg, #72000d, #ed1b2f);
    }
    .receipt-brand small { color: rgba(255,255,255,.78); }
    .receipt-kicker { margin-bottom: 6px; font-size: .62rem; font-weight: 800; letter-spacing: .13em; opacity: .8; }
    .receipt-check, .wa-icon, .receipt-success-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
    }
    .receipt-check { width: 52px; height: 52px; flex: 0 0 52px; background: rgba(255,255,255,.15); font-size: 1.25rem; }
    .receipt-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; padding: 20px 24px; background: #fff7f7; }
    .receipt-meta div, .receipt-total-row div { display: flex; flex-direction: column; gap: 3px; }
    .receipt-meta span, .receipt-total-row span { color: #64748b; font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; }
    .receipt-meta strong { font-size: .78rem; overflow-wrap: anywhere; }
    .receipt-items { padding: 8px 24px; }
    .receipt-item { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 16px 0; border-bottom: 1px dashed #e2e8f0; }
    .receipt-item strong { font-size: .82rem; }
    .receipt-item small { display: block; margin-top: 4px; color: #64748b; font-size: .68rem; font-weight: 700; }
    .receipt-total-row { display: grid; grid-template-columns: 1fr 1.35fr; gap: 12px; padding: 20px 24px 24px; }
    .receipt-total-row > div { padding: 14px; border-radius: 16px; background: #f8fafc; }
    .receipt-total-row .grand-total { background: #eaf8f1; color: #138a57; }
    .receipt-total-row strong { font-size: .9rem; }
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

        document.getElementById('confirm-wa-section').classList.remove('d-none');
        button.disabled = false;
        button.innerHTML = '<i class="fab fa-whatsapp me-2"></i>Buka WhatsApp Lagi';
        window.location.href = result.whatsapp_url;
    } catch (exception) {
        error.textContent = exception.message;
        error.classList.remove('d-none');
        button.disabled = false;
        button.innerHTML = '<i class="fab fa-whatsapp me-2"></i>Buka WhatsApp & Kirim e-Bon';
    }
});
</script>
@endpush
@endif
