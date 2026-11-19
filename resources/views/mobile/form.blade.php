{{--
    Input penjualan mobile. Pilihan outlet, stok, dan submit mengikuti MobileSalesController.
--}}@extends('layout.mobile_layout')

@section('title', 'Input Penjualan')

@section('content')
<div class="reveal">
    <form action="{{ route('mobile.store') }}" method="POST" id="sales-form" class="sales-input-form">
        @csrf
        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">

        @if($errors->any())
            <div class="alert alert-danger rounded-4 mb-3" role="alert" aria-live="assertive">
                <strong class="d-block mb-1">Penjualan belum tersimpan</strong>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <!-- Outlet Details - Blue Bento Card -->
        <div class="glass-card outlet-details-card mb-4 p-0 border-0 shadow-sm" style="background: white; border-radius: 24px;">
            <div class="d-flex align-items-stretch">
                <div style="width: 6px; background: var(--primary); opacity: 0.8;"></div>
                <div class="p-4 w-100">
                    <h6 class="fw-800 mb-3 text-dark" style="font-size: 0.75rem; letter-spacing: 1px;">CLIENT & VISIT DETAILS</h6>
                    
                    <div class="row align-items-center">
                        <div class="col-4">
                            <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Client ID</label>
                        </div>
                        <div class="col-8">
                            @if($pre_id_outlet)
                                <input type="hidden" name="id_outlet" id="id_outlet" value="{{ strtoupper($pre_id_outlet) }}">
                                <input type="text" class="form-control form-control-sm border-0 bg-light rounded-3 px-3 py-2 fw-bold text-dark" value="{{ strtoupper($pre_id_outlet) }}" disabled>
                            @else
                                <input type="hidden" name="id_outlet" id="id_outlet" required>
                                <div class="position-relative">
                                    <input type="search" id="outlet-search-inline" class="form-control form-control-sm border-0 bg-light rounded-3 px-3 py-2 fw-bold text-dark" placeholder="Cari ID / nama outlet..." autocomplete="off">
                                    <div id="outlet-inline-results" class="outlet-inline-results d-none"></div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row align-items-center mt-2">
                        <div class="col-4">
                            <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Nama Outlet</label>
                        </div>
                        <div class="col-8">
                            <div id="selected-outlet-name" class="bg-light rounded-3 px-3 py-2 fw-bold text-dark" style="font-size: 0.85rem; min-height: 35px;">
                                {{ $selected_outlet->nama_outlet ?? 'Outlet belum ditemukan' }}
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="button" id="outlet-performance-toggle" class="outlet-performance-toggle" aria-expanded="false" {{ $selected_outlet ? '' : 'disabled' }}>
                            <i class="fas fa-chart-line me-1"></i><span>Lihat performansi</span><i class="fas fa-chevron-down ms-1 performance-chevron"></i>
                        </button>
                    </div>
                    <div id="outlet-performance-panel" class="outlet-performance-panel d-none" aria-live="polite"></div>
                </div>
            </div>
        </div>

        <!-- Product Section - Green Bento Card -->
        <div class="d-flex justify-content-between align-items-center mb-2 px-2">
            <h6 class="fw-800 mb-0 text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">SALES ACTIVITY</h6>
            <button type="button" id="add-item-btn" class="btn btn-sm rounded-pill fw-800 px-3" style="background: rgba(16, 185, 129, 0.1); color: #10b981; font-size: 0.65rem;">
                <i class="fas fa-plus me-1"></i> ADD ITEM
            </button>
        </div>

        <div id="product-container">
            <!-- Product Rows added via JS -->
        </div>
        <div id="sales-activity-feedback" class="consignment-server-error d-none mb-3 px-2"></div>
        @error('products')
            <div class="consignment-server-error mb-3 px-2"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
        @enderror

        @php
            $consignmentOpen = old('consignment_enabled') === '1';
        @endphp
        <div class="consignment-shell mb-4">
            <button type="button" id="consignment-toggle" class="consignment-toggle" aria-expanded="{{ $consignmentOpen ? 'true' : 'false' }}" aria-controls="consignment-panel">
                        <span><i class="fas fa-sim-card me-2"></i>Konsinyasi (SA / Voucher Fisik)</span>
                <span class="consignment-toggle-action"><i class="fas fa-plus me-1"></i> Isi nomor perdana</span>
            </button>
            <input type="hidden" name="consignment_enabled" id="consignment_enabled" value="{{ $consignmentOpen ? '1' : '0' }}">
            <div id="consignment-panel" class="consignment-panel {{ $consignmentOpen ? '' : 'd-none' }}">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="fw-800 text-dark" style="font-size: .78rem;">Nomor perdana wajib diisi</div>
                        <div class="text-muted fw-bold" style="font-size: .62rem;">Satu nomor per baris. Qty dihitung otomatis oleh sistem.</div>
                    </div>
                    <span id="consignment-count" class="consignment-count">0 nomor</span>
                </div>
                <div id="consignment-rows"></div>
                <button type="button" id="consignment-add-denom" class="consignment-add-denom"><i class="fas fa-plus me-1"></i> Tambah denom</button>
                <textarea id="consignment_numbers" class="d-none" tabindex="-1" aria-hidden="true"></textarea>
                <div id="consignment-feedback" class="consignment-feedback mt-2"></div>
                @error('consignments')
                    <div class="consignment-server-error mt-2"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
                <button type="button" id="consignment-clear" class="btn btn-link text-danger text-decoration-none fw-bold p-0 mt-3" style="font-size: .66rem;">
                    Hapus konsinyasi dari transaksi
                </button>
            </div>
        </div>

        <!-- Notes Section - Orange Bento Card -->
        <div class="glass-card mb-4 p-0 border-0 shadow-sm overflow-hidden" style="background: white; border-radius: 24px;">
            <div class="d-flex align-items-stretch">
                <div style="width: 6px; background: #f59e0b; opacity: 0.8;"></div>
                <div class="p-4 w-100">
                    <h6 class="fw-800 mb-3 text-dark" style="font-size: 0.75rem; letter-spacing: 1px;">NOTES & FOLLOW-UP</h6>
                    <textarea name="keterangan" id="keterangan" class="form-control border-0 bg-light rounded-3 px-3 py-3 fw-bold text-dark" rows="3" placeholder="Activity summary..." style="font-size: 0.85rem; resize: none;"></textarea>
                </div>
            </div>
        </div>

        <!-- Live Quantity Summary -->
        <div id="setoran-summary" class="glass-card mb-4 p-0 border-0 shadow-sm overflow-hidden" style="background: white; border-radius: 24px; display: none;">
            <div class="d-flex align-items-stretch">
                <div style="width: 6px; background: #10b981; opacity: 0.8;"></div>
                <div class="p-4 w-100">
                    <div>
                        <h6 class="fw-800 mb-1 text-dark" style="font-size: 0.75rem; letter-spacing: 1px;">RINGKASAN QTY</h6>
                        <div class="text-muted fw-bold" style="font-size: 0.65rem;"><span id="total-items">0</span> item • <span id="total-pcs">0</span> pcs</div>
                    </div>
                    <div id="setoran-detail" class="mt-3 pt-3 border-top"></div>
                </div>
            </div>
        </div>

    </form>
</div>

@section('mobile_action')
    <div class="sales-submit-bar">
        <button type="submit" form="sales-form" class="btn btn-primary w-100 rounded-pill py-3 fw-800 shadow sales-submit-button">
            Lihat Detail & Konfirmasi <i class="fas fa-arrow-right ms-1"></i>
        </button>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
<style>
    .outlet-inline-results {
        position: absolute;
        z-index: 1085;
        top: calc(100% + 6px);
        right: 0;
        left: 0;
        max-height: min(280px, 46vh);
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 16px 34px rgba(15, 23, 42, .16);
    }
    .outlet-details-card {
        position: relative;
        z-index: 30;
        overflow: visible !important;
    }
    .outlet-details-card > .d-flex {
        overflow: visible;
    }
    .outlet-inline-option {
        width: 100%;
        padding: 10px 12px;
        border: 0;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
        color: #111827;
        text-align: left;
    }
    .outlet-inline-option:last-child { border-bottom: 0; }
    .outlet-inline-option strong,
    .outlet-inline-option small { display: block; }
    .outlet-inline-option strong { font-size: .72rem; }
    .outlet-inline-option small { color: #64748b; font-size: .6rem; font-weight: 700; }
    .outlet-performance-toggle { display:inline-flex;align-items:center;min-height:30px;padding:6px 10px;border:0;border-radius:999px;background:rgba(220,38,38,.08);color:var(--primary);font-size:.61rem;font-weight:800; }
    .outlet-performance-toggle:disabled { opacity:.45; }
    .outlet-performance-toggle[aria-expanded="true"] .performance-chevron { transform:rotate(180deg); }
    .performance-chevron { transition:transform .2s ease; }
    .outlet-performance-panel { margin-top:10px;padding:12px;border:1px solid #e5e7eb;border-radius:15px;background:#f8fafc; }
    .performance-head { display:flex;justify-content:space-between;gap:10px;margin-bottom:9px;color:#64748b;font-size:.58rem;font-weight:800; }
    .performance-grid { display:grid;grid-template-columns:1.35fr repeat(4,.75fr);align-items:center; }
    .performance-cell { padding:7px 4px;border-top:1px solid #e8edf3;text-align:right;color:#334155;font-size:.6rem;font-weight:750;font-variant-numeric:tabular-nums; }
    .performance-cell.label { text-align:left;color:#111827;font-weight:850; }
    .performance-cell.heading { padding-top:2px;border-top:0;color:#94a3b8;font-size:.52rem;text-transform:uppercase; }
    .performance-cell.positive { color:#059669; }.performance-cell.negative { color:#dc2626; }
    .performance-loading,.performance-error { padding:12px;text-align:center;color:#64748b;font-size:.62rem;font-weight:750; }
    .performance-error { color:#dc2626; }
    .consignment-shell {
        border-radius: 18px;
        background: #fff;
        border: 1px solid #e5e7eb;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        overflow: hidden;
    }
    .consignment-toggle {
        width: 100%;
        min-height: 48px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 14px;
        border: 0;
        background: #fff;
        color: #111827;
        font-size: .72rem;
        font-weight: 800;
        text-align: left;
    }
    .consignment-toggle-action {
        flex: 0 0 auto;
        border-radius: 999px;
        padding: 7px 10px;
        background: rgba(220, 38, 38, .08);
        color: var(--primary);
        font-size: .6rem;
    }
    .consignment-panel {
        padding: 14px;
        border-top: 1px solid #f1f5f9;
        background: #f8fafc;
    }
    .consignment-count {
        flex: 0 0 auto;
        border-radius: 999px;
        padding: 6px 9px;
        background: rgba(16, 185, 129, .12);
        color: #047857;
        font-size: .62rem;
        font-weight: 800;
        white-space: nowrap;
    }
    .consignment-textarea {
        border: 1px solid #dbe3ee !important;
        border-radius: 14px !important;
        background: #fff !important;
        font-size: .78rem;
        font-weight: 700;
        line-height: 1.65;
        resize: vertical;
    }
    .consignment-denom-row { position:relative;margin-bottom:10px;padding:12px;border:1px solid #e2e8f0;border-radius:15px;background:#fff; }
    .consignment-denom-row .select2-container { width:100%!important;margin-bottom:9px; }
    .consignment-denom-row .select2-selection--single { height:42px!important;border:1px solid #dbe3ee!important;border-radius:12px!important;background:#f8fafc!important; }
    .consignment-denom-row .select2-selection__rendered { padding:6px 36px 6px 12px!important;color:#1f2937!important;font-size:.72rem;font-weight:800;line-height:28px!important; }
    .consignment-denom-row .select2-selection__arrow { top:7px!important;right:7px!important; }
    .select2-container--open .select2-dropdown { z-index:1085;border-color:#dbe3ee;border-radius:12px;overflow:hidden;box-shadow:0 14px 32px rgba(15,23,42,.18); }
    .select2-container--open .select2-search__field { min-height:38px;border:1px solid #dbe3ee!important;border-radius:8px;padding:7px 10px;outline:none; }
    .select2-results__option { padding:10px 12px;font-size:.76rem; }
    .consignment-denom-row select { min-height:42px;margin-bottom:9px;font-size:.7rem;font-weight:800!important; }
    .consignment-denom-row .consignment-textarea { min-height:96px; }
    .consignment-row-count { color:#059669;font-size:.58rem;font-weight:850; }
    .consignment-row-head { display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px; }
    .consignment-row-title { color:#64748b;font-size:.65rem;font-weight:850; }
    .remove-consignment-row { flex:0 0 auto;width:30px;height:30px;border:1px solid #fecaca;border-radius:10px;background:#fff1f2;color:#dc2626; }
    .serial-input-area { padding:14px;border:1px solid #e2e8f0;border-radius:16px;background:linear-gradient(180deg,#fff 0%,#f8fafc 100%); }
    .serial-section-heading { display:flex;align-items:flex-start;gap:10px;margin-bottom:14px; }
    .serial-section-icon { display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;border-radius:11px;background:#fff1f2;color:#c8102e; }
    .serial-section-heading strong { display:block;color:#172033;font-size:.78rem;font-weight:850; }
    .serial-section-heading small { display:block;margin-top:2px;color:#64748b;font-size:.64rem;line-height:1.45; }
    .serial-field-label { display:block;margin-bottom:7px;color:#475569;font-size:.65rem;font-weight:850; }
    .serial-segmented { display:flex;gap:3px;width:100%;padding:4px;border:1px solid #e2e8f0;border-radius:12px;background:#f1f5f9; }
    .serial-segment { display:inline-flex;align-items:center;justify-content:center;gap:7px;flex:1;min-width:0;min-height:38px;padding:7px 9px;border:1px solid transparent;border-radius:9px;background:transparent;color:#64748b;font-size:.68rem;font-weight:800;line-height:1.2;white-space:nowrap;transition:background-color .15s ease,color .15s ease,border-color .15s ease,box-shadow .15s ease; }
    .serial-segment i { font-size:.68rem; }
    .serial-segment.active { border-color:#fecdd3;background:#fff;color:#be123c;box-shadow:0 1px 3px rgba(15,23,42,.08); }
    .serial-segment:focus-visible { outline:2px solid #e11d48;outline-offset:2px; }
    .serial-segment:disabled { opacity:.5;cursor:not-allowed; }
    .serial-hidden-select { position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important; }
    .serial-single-wrap,.serial-range-wrap { margin-top:14px; }
    .serial-input-area .form-control { min-height:44px;border:1px solid #dbe3ee;border-radius:12px;background:#fff;font-size:.78rem;font-weight:750; }
    .serial-range-fields { display:grid;grid-template-columns:minmax(0,1fr);gap:10px; }
    .serial-range-field { min-width:0; }
    .serial-range-field label { display:block;margin:0 0 5px;color:#64748b;font-size:.59rem;font-weight:800; }
    .serial-range-separator { display:none; }
    .serial-qty-hint { display:block;margin-top:8px;color:#64748b!important;font-size:.61rem;font-weight:700;line-height:1.45; }
    .serial-qty-hint.text-danger { color:#dc2626!important; }
    .serial-qty-hint.text-success { color:#059669!important; }
    .serial-range-hint { padding:8px 10px;border-radius:10px;background:#f1f5f9; }
    @media(max-width:360px){.serial-segment{gap:5px;padding-inline:6px;font-size:.62rem}.serial-segment i{font-size:.62rem}}
    .sales-input-form { padding-bottom:185px; }
    .sales-submit-bar { position:fixed;z-index:1050;bottom:calc(84px + env(safe-area-inset-bottom, 0px));left:50%;width:min(calc(100% - 24px),456px);padding:10px 12px;transform:translateX(-50%);border:1px solid rgba(226,232,240,.9);border-radius:20px;background:rgba(255,255,255,.96);box-shadow:0 12px 30px rgba(15,23,42,.14);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);pointer-events:none; }
    .sales-submit-button { min-height:56px;font-size:.95rem!important;background:linear-gradient(110deg,#c1121f,#ef233c)!important;border:0!important;box-shadow:0 8px 18px rgba(193,18,31,.2)!important;pointer-events:auto;position:relative;z-index:1; }
    @media (max-width:360px) { .sales-submit-bar { width:calc(100% - 16px);padding:8px; } .sales-submit-button { min-height:52px;font-size:.85rem!important; } }
    .consignment-add-denom { width:100%;min-height:36px;border:1px dashed #fca5a5;border-radius:12px;background:#fff7f7;color:var(--primary);font-size:.64rem;font-weight:850; }
    .consignment-feedback,
    .consignment-server-error {
        color: #dc2626;
        font-size: .64rem;
        font-weight: 700;
    }
    .consignment-validation-list { display: grid; gap: 7px; }
    .consignment-validation-row { display: flex; align-items: flex-start; gap: 8px; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
    .consignment-validation-row i { margin-top: 2px; }
    .consignment-validation-row strong, .consignment-validation-row small { display: block; overflow-wrap: anywhere; }
    .consignment-validation-row strong { color: #334155; font-size: .68rem; }
    .consignment-validation-row small { margin-top: 2px; color: #64748b; font-size: .59rem; }
    .consignment-validation-row.valid { border-color: #bbf7d0; background: #f0fdf4; }
    .consignment-validation-row.valid i { color: #16a34a; }
    .consignment-validation-row.invalid { border-color: #fecaca; background: #fff1f2; }
    .consignment-validation-row.invalid i { color: #dc2626; }
    .consignment-validation-row.checking i { color: #64748b; }
</style>
@endpush

@push('modals')
<div class="modal fade" id="productPickerModal" tabindex="-1" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-dialog-scrollable" style="margin: 0 auto; max-width: 480px; position: absolute; bottom: 0; left: 0; right: 0; width: 100%;">
        <div class="modal-content border-0" style="border-radius: 30px 30px 0 0; height: 85vh; width: 100%; box-shadow: 0 -10px 50px rgba(0,0,0,0.2);">
            <div class="d-flex justify-content-center pt-3">
                <div style="width: 45px; height: 6px; background: #e2e8f0; border-radius: 10px;"></div>
            </div>
            
            <div class="modal-header border-0 px-4 pt-3 pb-2">
                <div class="flex-grow-1">
                    <h5 class="modal-title fw-800" style="color: var(--text-main); font-size: 1.4rem;">Pilih Produk</h5>
                    <p class="small text-muted mb-0">Ketuk produk untuk memasukkan ke form</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-0">
                <div class="sticky-top bg-white px-4 pt-2 pb-3 shadow-sm" style="z-index: 10;">
                    <div class="position-relative">
                        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        <input type="text" id="product-search-modal" class="glass-input ps-5" placeholder="Ketik nama produk..." style="background: #f1f5f9; font-size: 1rem; border-radius: 20px; border: 1px solid #e2e8f0;">
                    </div>
                </div>
                
                <div class="product-list-wrapper px-4 pt-3 pb-5">
                    <div class="list-group list-group-flush" id="modal-product-list">
                        @php
                            // Separate special items to ensure they are at the TOP
                            $specialItems = $denoms->filter(function($d) {
                                return strtoupper((string) ($d->product_group ?? 'PV')) === 'SP';
                            });
                            $otherItems = $denoms->reject(function($d) {
                                return strtoupper((string) ($d->product_group ?? 'PV')) === 'SP';
                            });
                        @endphp

                        {{-- Render Special Items First --}}
                        @foreach($specialItems as $denom)
                        <button type="button" class="list-group-item list-group-item-action border-0 mb-3 rounded-4 py-3 px-4 d-flex align-items-center select-product-btn" 
                                style="background: #ffffff; border: 1px solid #f1f5f9 !important; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: all 0.2s; display: flex !important;"
                                data-id="{{ $denom->iddenom }}" 
                                data-name="{{ $denom->denom }}"
                                data-stock="{{ $denom->stock_qty ?? 0 }}">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-4 me-3 text-primary">
                                <i class="fas fa-star fs-5"></i>
                            </div>
                            <div class="flex-grow-1 text-start">
                                <div class="fw-800 text-dark" style="font-size: 1.05rem; line-height: 1.2;">{{ $denom->denom }}</div>
                                <div class="small text-muted fw-bold mt-1 d-flex align-items-center" style="font-size: 0.7rem;">
                                    <span class="badge bg-primary bg-opacity-10 text-primary me-2 px-2 py-1 rounded-pill">VIRTUAL</span>
                                    <span class="text-primary">STOK: {{ number_format($denom->stock_qty) }}</span>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-muted opacity-25"></i>
                        </button>
                        @endforeach

                        {{-- Render Other Items --}}
                        @foreach($otherItems as $denom)
                        <button type="button" class="list-group-item list-group-item-action border-0 mb-3 rounded-4 py-3 px-4 d-flex align-items-center select-product-btn" 
                                style="background: #ffffff; border: 1px solid #f1f5f9 !important; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: all 0.2s; display: flex !important;"
                                data-id="{{ $denom->iddenom }}" 
                                data-name="{{ $denom->denom }}"
                                data-stock="{{ $denom->stock_qty ?? 0 }}">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-4 me-3 text-primary">
                                <i class="fas fa-box-open fs-5"></i>
                            </div>
                            <div class="flex-grow-1 text-start">
                                <div class="fw-800 text-dark" style="font-size: 1.05rem; line-height: 1.2;">{{ $denom->denom }}</div>
                                <div class="small text-muted fw-bold mt-1 d-flex align-items-center" style="font-size: 0.7rem;">
                                    <span class="badge bg-success bg-opacity-10 text-success me-2 px-2 py-1 rounded-pill">READY</span>
                                    <span class="text-primary">STOK: {{ number_format($denom->stock_qty) }}</span>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-muted opacity-25"></i>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #productPickerModal {
        background: rgba(0,0,0,0.4);
    }
    .select-product-btn:active {
        transform: scale(0.96);
        background: #f8fafc !important;
    }
    /* Hide spin buttons */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
</style>
@endpush

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Prevent scroll changing number values
        $(document).on('wheel', 'input[type=number]', function(e) {
            $(this).blur();
        });
        
        // Also for mobile touch scroll issues
        $(document).on('focus', 'input[type=number]', function(e) {
            $(this).on('wheel.disableScroll', function(e) {
                e.preventDefault();
            });
        });
        $(document).on('blur', 'input[type=number]', function(e) {
            $(this).off('wheel.disableScroll');
        });
        let activeRow = null;
        const stockMap = {!! json_encode($denoms->pluck('stock_qty', 'iddenom')) !!};
        const starterPackMap = @json($starterPackByDenom);
        const consignmentDenoms = @json($consignmentDenomOptions);
        const oldConsignments = @json(old('consignments', []));
        let consignmentRowIndex = 0;
        let outletSearchTimer = null;

        function consignmentOptions(selected = '') {
            return '<option value="">Pilih denom konsinyasi...</option>' + consignmentDenoms.map(denom =>
                `<option value="${escapeHtml(denom.id)}" ${String(denom.id) === String(selected) ? 'selected' : ''}>${escapeHtml(denom.name)}</option>`
            ).join('');
        }
        function addConsignmentRow(iddenom = '', numbers = '') {
            const index = consignmentRowIndex++;
            const disabled = $('#consignment_enabled').val() === '1' ? '' : 'disabled';
            $('#consignment-rows').append(`<div class="consignment-denom-row">
                <div class="consignment-row-head"><span class="consignment-row-title">Denom konsinyasi</span><button type="button" class="remove-consignment-row" aria-label="Hapus denom"><i class="fas fa-times"></i></button></div>
                <select name="consignments[${index}][iddenom]" class="form-select consignment-denom-select" required ${disabled}>${consignmentOptions(iddenom)}</select>
                <div class="d-flex justify-content-between mb-1"><small class="fw-bold text-muted">Nomor perdana — satu nomor per baris</small><span class="consignment-row-count">0 nomor</span></div>
                <textarea name="consignments[${index}][numbers]" class="form-control consignment-textarea consignment-row-numbers" rows="4" inputmode="text" enterkeyhint="enter" autocomplete="off" required ${disabled} placeholder="081234567890&#10;081234567891">${escapeHtml(numbers)}</textarea>
            </div>`);
            const select = $('#consignment-rows .consignment-denom-row').last().find('.consignment-denom-select');
            if ($.fn.select2) {
                select.select2({
                    width: '100%',
                    placeholder: 'Cari atau pilih denom konsinyasi...',
                    minimumResultsForSearch: 0,
                    dropdownParent: $('body'),
                    language: {
                        noResults: () => 'Denom tidak ditemukan',
                        searching: () => 'Mencari denom...'
                    }
                });
            }
            syncConsignmentRows();
        }
        function syncConsignmentRows() {
            const values = [];
            let total = 0;
            $('.consignment-row-numbers').each(function() {
                const result = parseConsignmentNumbers($(this).val());
                $(this).closest('.consignment-denom-row').find('.consignment-row-count').text(`${result.numbers.length} nomor`);
                values.push($(this).val());
                total += result.numbers.length;
            });
            $('#consignment_numbers').val(values.join('\n'));
            $('#consignment-count').text(`${total.toLocaleString('id-ID')} nomor`);
        }

        function selectOutlet(outlet) {
            $('#id_outlet').val(String(outlet.id_outlet || '').toUpperCase());
            $('#outlet-search-inline').val(String(outlet.id_outlet || '').toUpperCase());
            $('#selected-outlet-name').text(outlet.nama_outlet || 'Outlet belum ditemukan');
            $('#outlet-inline-results').addClass('d-none').empty();
            resetOutletPerformance();
            saveFormState();
        }

        let performanceRequest = null;
        function resetOutletPerformance() {
            if (performanceRequest) performanceRequest.abort();
            performanceRequest = null;
            $('#outlet-performance-panel').addClass('d-none').empty();
            $('#outlet-performance-toggle').prop('disabled', !$('#id_outlet').val()).attr('aria-expanded', 'false');
            $('#outlet-performance-toggle span').text('Lihat performansi');
        }
        function metricValue(value) { return Number(value || 0).toLocaleString('id-ID'); }
        function renderOutletPerformance(response) {
            const rows = (response.metrics || []).map(metric => {
                const mom = metric.mom === null ? '–' : `${metric.mom > 0 ? '+' : ''}${Number(metric.mom).toLocaleString('id-ID', {maximumFractionDigits:1})}%`;
                const tone = metric.mom === null ? '' : (metric.mom >= 0 ? 'positive' : 'negative');
                return `<div class="performance-cell label">${escapeHtml(metric.label)}</div><div class="performance-cell">${metricValue(metric.fm1)}</div><div class="performance-cell">${metricValue(metric.m1)}</div><div class="performance-cell">${metricValue(metric.mtd)}</div><div class="performance-cell ${tone}">${mom}</div>`;
            }).join('');
            return `<div class="performance-head"><span>PERFORMANSI OUTLET</span><span>Cutoff ${escapeHtml(response.cutoff || '–')}</span></div><div class="performance-grid"><div class="performance-cell heading label">Parameter</div><div class="performance-cell heading">FM-1</div><div class="performance-cell heading">M-1</div><div class="performance-cell heading">MTD</div><div class="performance-cell heading">MoM</div>${rows}</div>`;
        }
        $('#outlet-performance-toggle').on('click', function() {
            const panel = $('#outlet-performance-panel');
            if ($(this).attr('aria-expanded') === 'true') {
                $(this).attr('aria-expanded', 'false').find('span').text('Lihat performansi');
                panel.addClass('d-none');
                return;
            }
            const outletId = $('#id_outlet').val();
            if (!outletId) return;
            $(this).attr('aria-expanded', 'true').find('span').text('Sembunyikan');
            panel.removeClass('d-none').html('<div class="performance-loading"><i class="fas fa-circle-notch fa-spin me-1"></i> Memuat performansi...</div>');
            performanceRequest = $.get("{{ route('mobile.outlet-performance', ['id_outlet' => '__OUTLET__']) }}".replace('__OUTLET__', encodeURIComponent(outletId)))
                .done(response => panel.html(renderOutletPerformance(response)))
                .fail((xhr, status) => { if (status !== 'abort') panel.html('<div class="performance-error">Performansi belum dapat dimuat. Coba lagi.</div>'); })
                .always(() => { performanceRequest = null; });
        });

        function loadOutletOptions(query) {
            clearTimeout(outletSearchTimer);
            const value = String(query || '').trim();
            if (value.length < 2) {
                $('#outlet-inline-results').addClass('d-none').empty();
                return;
            }

            outletSearchTimer = setTimeout(function() {
                $.get("{{ route('mobile.search-outlet') }}", { q: value })
                    .done(function(outlets) {
                        const html = outlets.map(outlet => `
                            <button type="button" class="outlet-inline-option" data-id="${escapeHtml(outlet.id_outlet)}" data-name="${escapeHtml(outlet.nama_outlet)}">
                                <strong>${escapeHtml(outlet.nama_outlet)}</strong>
                                <small>ID: ${escapeHtml(outlet.id_outlet)}</small>
                            </button>`).join('');
                        $('#outlet-inline-results')
                            .html(html || '<div class="p-3 text-center text-muted fw-bold" style="font-size:.65rem">Outlet SF tidak ditemukan</div>')
                            .removeClass('d-none');
                    })
                    .fail(function() {
                        $('#outlet-inline-results').html('<div class="p-3 text-center text-danger fw-bold" style="font-size:.65rem">Gagal memuat outlet</div>').removeClass('d-none');
                    });
            }, 220);
        }

        $('#outlet-search-inline').on('input', function() {
            $('#id_outlet').val('');
            $('#selected-outlet-name').text('Pilih outlet dari hasil pencarian');
            resetOutletPerformance();
            loadOutletOptions($(this).val());
        });

        $(document).on('click', '.outlet-inline-option', function() {
            selectOutlet({ id_outlet: $(this).data('id'), nama_outlet: $(this).data('name') });
        });

        $(document).on('click', function(event) {
            if (! $(event.target).closest('#outlet-search-inline, #outlet-inline-results').length) {
                $('#outlet-inline-results').addClass('d-none');
            }
        });

        // GPS
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(position => {
                $('#latitude').val(position.coords.latitude);
                $('#longitude').val(position.coords.longitude);
            }, null, { enableHighAccuracy: true });
        }

        // Persistence
        const STORAGE_KEY = 'sf_sales_form_data';
        function saveFormState() {
            const formData = {
                id_outlet: $('#id_outlet').val(),
                keterangan: $('#keterangan').val(),
                consignment_enabled: $('#consignment_enabled').val() === '1',
                consignments: $('.consignment-denom-row').map(function() { return {
                    iddenom: $(this).find('.consignment-denom-select').val(),
                    numbers: $(this).find('.consignment-row-numbers').val()
                }; }).get(),
                products: []
            };
            $('#product-container .glass-card').each(function() {
                const id = $(this).find('.iddenom-input').val();
                const qty = $(this).find('.qty-input').val();
                const name = $(this).find('.product-placeholder').text();
                const serialRow = $(this);
                if (id || qty || serialRow.find('.serial-value-input, .serial-start-input, .serial-end-input').filter(function() { return $(this).val(); }).length) {
                    formData.products.push({ id, name, qty, serial_mode: serialRow.find('.serial-mode-select').val(), identifier_type: 'sn', serial_value: serialRow.find('.serial-value-input').val(), serial_start: serialRow.find('.serial-start-input').val(), serial_end: serialRow.find('.serial-end-input').val() });
                }
            });
            localStorage.setItem(STORAGE_KEY, JSON.stringify(formData));
        }

        window.addEventListener('mobile:before-refresh', saveFormState);
        window.addEventListener('mobile:draft-restored', function() {
            const query = String($('#outlet-search-inline').val() || '').trim();
            if (!$('#id_outlet').val() && query.length >= 2) loadOutletOptions(query);
        });
        $('#keterangan').on('input', saveFormState);

        function parseConsignmentNumbers(value) {
            const numbers = [];
            const invalid = [];
            const duplicates = [];
            const entries = [];

            String(value || '').split(/[\r\n,;]+/).forEach(rawValue => {
                const raw = rawValue.trim();
                if (!raw) return;
                const compact = raw.replace(/[\s().-]+/g, '');
                if (!/^\+?\d+$/.test(compact)) {
                    invalid.push(raw);
                    entries.push({ raw, number: null, state: 'invalid', message: 'Format nomor tidak valid' });
                    return;
                }
                let number = compact.replace(/^\+/, '');
                if (number.startsWith('0062')) number = number.slice(2);
                else if (number.startsWith('0')) number = `62${number.slice(1)}`;
                if (!/^\d{8,20}$/.test(number)) {
                    invalid.push(raw);
                    entries.push({ raw, number, state: 'invalid', message: 'Nomor harus berisi 8–20 digit' });
                    return;
                }
                if (numbers.includes(number)) {
                    duplicates.push(number);
                    entries.push({ raw, number, state: 'duplicate', message: 'Nomor duplikat dalam isian ini' });
                    return;
                }
                numbers.push(number);
                entries.push({ raw, number, state: 'pending', message: 'Memeriksa nomor…' });
            });

            return { numbers, invalid: [...new Set(invalid)], duplicates: [...new Set(duplicates)], entries };
        }

        let consignmentCheckTimer = null;
        let checkedConsignmentKey = '';
        let consignmentAvailability = new Map();
        let consignmentCheckRequest = null;

        function renderConsignmentValidation(result, checking = false) {
            const rows = result.entries.map(entry => {
                let state = entry.state;
                let message = entry.message;
                let icon = 'fa-times-circle';
                if (entry.number && state === 'pending') {
                    const remote = consignmentAvailability.get(entry.number);
                    if (checking || !remote) {
                        state = 'checking'; message = 'Memeriksa ketersediaan…'; icon = 'fa-circle-notch fa-spin';
                    } else if (remote.available) {
                        state = 'valid';
                        message = remote.will_move ? `Akan dipindahkan dari ${remote.nama_outlet || remote.id_outlet} ke outlet ini` : 'Nomor tersedia';
                        icon = remote.will_move ? 'fa-right-left' : 'fa-check-circle';
                    } else {
                        state = 'invalid';
                        const outlet = remote.nama_outlet || remote.id_outlet || 'outlet lain';
                        const outletId = remote.id_outlet && remote.nama_outlet ? ` (${remote.id_outlet})` : '';
                        message = `Sudah tercatat di ${outlet}${outletId}`;
                    }
                }
                return `<div class="consignment-validation-row ${state}"><i class="fas ${icon}"></i><div><strong>${escapeHtml(entry.number || entry.raw)}</strong><small>${escapeHtml(message)}</small></div></div>`;
            }).join('');
            $('#consignment-feedback').html(rows ? `<div class="consignment-validation-list">${rows}</div>` : '');
        }

        function updateConsignmentSummary() {
            syncConsignmentRows();
            const result = parseConsignmentNumbers($('#consignment_numbers').val());
            $('#consignment-count').text(`${result.numbers.length.toLocaleString('id-ID')} nomor`);
            renderConsignmentValidation(result, false);
            return result;
        }

        function checkConsignmentAvailability(onComplete = null) {
            const result = parseConsignmentNumbers($('#consignment_numbers').val());
            const key = result.numbers.join('|');
            if (!result.numbers.length || result.invalid.length || result.duplicates.length) {
                checkedConsignmentKey = '';
                consignmentAvailability = new Map();
                renderConsignmentValidation(result, false);
                if (onComplete) onComplete(false, result);
                return;
            }
            if (checkedConsignmentKey === key && result.numbers.every(number => consignmentAvailability.has(number))) {
                renderConsignmentValidation(result, false);
                if (onComplete) onComplete(result.numbers.every(number => consignmentAvailability.get(number)?.available), result);
                return;
            }
            if (consignmentCheckRequest) consignmentCheckRequest.abort();
            renderConsignmentValidation(result, true);
            consignmentCheckRequest = $.ajax({
                url: "{{ route('mobile.consignment.check') }}",
                method: 'POST',
                data: { _token: "{{ csrf_token() }}", numbers: result.numbers, id_outlet: $('#id_outlet').val() }
            }).done(response => {
                consignmentAvailability = new Map((response.results || []).map(item => [String(item.number), item]));
                checkedConsignmentKey = key;
                renderConsignmentValidation(result, false);
                if (onComplete) onComplete(result.numbers.every(number => consignmentAvailability.get(number)?.available), result);
            }).fail((xhr, status) => {
                if (status === 'abort') return;
                checkedConsignmentKey = '';
                consignmentAvailability = new Map();
                let message = 'Server belum dapat memvalidasi nomor. Coba beberapa saat lagi.';
                if (xhr.status === 0) message = 'Koneksi ke server terputus. Periksa jaringan lalu coba lagi.';
                else if (xhr.status === 419) message = 'Sesi login telah berakhir. Muat ulang halaman lalu login kembali.';
                else if (xhr.status === 422) message = xhr.responseJSON?.message || 'Format nomor belum dapat divalidasi.';
                $('#consignment-feedback').html(`<div class="consignment-validation-row invalid"><i class="fas fa-times-circle"></i><div><strong>Validasi belum berhasil</strong><small>${escapeHtml(message)}</small></div></div>`);
                if (onComplete) onComplete(false, result);
            }).always(() => { consignmentCheckRequest = null; });
        }

        function setConsignmentOpen(open) {
            $('#consignment_enabled').val(open ? '1' : '0');
            $('#consignment-panel').toggleClass('d-none', !open);
            $('#consignment-rows').find('.consignment-denom-select, .consignment-row-numbers').prop('disabled', !open);
            $('#consignment-toggle').attr('aria-expanded', open ? 'true' : 'false');
            $('#consignment-toggle .consignment-toggle-action i').toggleClass('fa-plus', !open).toggleClass('fa-chevron-up', open);
            if (open) updateConsignmentSummary();
        }

        $('#consignment-toggle').on('click', function() {
            setConsignmentOpen($('#consignment_enabled').val() !== '1');
            saveFormState();
        });
        $('#consignment-rows').on('input change', '.consignment-row-numbers, .consignment-denom-select', function() {
            checkedConsignmentKey = '';
            consignmentAvailability = new Map();
            const result = updateConsignmentSummary();
            clearTimeout(consignmentCheckTimer);
            if (result.numbers.length && !result.invalid.length && !result.duplicates.length) {
                consignmentCheckTimer = setTimeout(() => checkConsignmentAvailability(), 350);
            }
            saveFormState();
        });

        // iOS Safari can treat Enter in a form textarea as the form's next
        // action instead of inserting a line break. Force the expected
        // one-number-per-line behaviour while preserving the caret position.
        $('#consignment-rows').on('keydown', '.consignment-row-numbers', function(event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const start = this.selectionStart;
            const end = this.selectionEnd;
            const value = this.value;
            this.value = value.slice(0, start) + '\n' + value.slice(end);
            this.selectionStart = this.selectionEnd = start + 1;
            $(this).trigger('input');
        });
        $('#consignment-clear').on('click', function() {
            $('#consignment-rows').empty();
            addConsignmentRow();
            setConsignmentOpen(false);
            updateConsignmentSummary();
            saveFormState();
        });

        function loadFormState() {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (!saved) return;
            try {
                const data = JSON.parse(saved);
                const currentOutlet = $('#id_outlet').val();
                if (currentOutlet && data.id_outlet && currentOutlet !== data.id_outlet) return;
                if (!currentOutlet && data.id_outlet) {
                    $.get("{{ route('mobile.search-outlet') }}", { q: data.id_outlet }).done(function(outlets) {
                        const match = outlets.find(outlet => String(outlet.id_outlet).toUpperCase() === String(data.id_outlet).toUpperCase());
                        if (match) selectOutlet(match);
                    });
                }
                $('#keterangan').val(data.keterangan || '');
                $('#consignment-rows').empty();
                (data.consignments?.length ? data.consignments : [{ iddenom: '', numbers: '' }])
                    .forEach(row => addConsignmentRow(row.iddenom || '', row.numbers || ''));
                setConsignmentOpen(Boolean(data.consignment_enabled));
                if (data.products && data.products.length > 0) {
                    $('#product-container').empty();
                    data.products
                        .filter(p => !p.id || Number(stockMap[p.id] || 0) > 0)
                        .forEach(p => addProductRow(p.id, p.name, p.qty, p));
                    recalcTotal();
                }
            } catch (e) { console.error(e); }
        }

        $('#consignment-add-denom').on('click', function() { addConsignmentRow(); saveFormState(); });
        $('#consignment-rows').on('click', '.remove-consignment-row', function() {
            $(this).closest('.consignment-denom-row').remove();
            if (!$('#consignment-rows .consignment-denom-row').length) addConsignmentRow();
            updateConsignmentSummary();
            saveFormState();
        });
        (oldConsignments.length ? oldConsignments : [{ iddenom: '', numbers: '' }])
            .forEach(row => addConsignmentRow(row.iddenom || '', row.numbers || ''));

        function addProductRow(id = '', name = 'Select item...', qty = '', serial = {}) {
            const rowId = Date.now() + Math.random().toString(36).substr(2, 5);
            const stock = id ? Number(stockMap[id] || 0) : 0;
            const safeName = escapeHtml(name);
            const html = `
                <div class="glass-card mb-3 p-0 border-0 shadow-sm overflow-hidden reveal" style="background: white; border-radius: 24px;">
                    <div class="d-flex align-items-stretch">
                        <div style="width: 6px; background: #10b981; opacity: 0.5;"></div>
                        <div class="p-4 w-100 position-relative">
                            <button type="button" class="btn btn-link text-danger p-0 text-decoration-none position-absolute top-0 end-0 m-3 remove-row">
                                <i class="fas fa-times-circle fs-5"></i>
                            </button>
                            <div class="row align-items-center mb-3">
                                <div class="col-4"><label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Product</label></div>
                                <div class="col-8">
                                    <div class="product-trigger border-0 bg-light rounded-3 px-3 py-2 d-flex justify-content-between align-items-center" style="cursor: pointer;">
                                        <span class="product-placeholder fw-bold ${id ? 'text-dark' : 'text-muted'}" style="font-size: 0.8rem;">${safeName}</span>
                                        <input type="hidden" name="products[${rowId}][iddenom]" class="iddenom-input" value="${id}">
                                        <i class="fas fa-search text-muted" style="font-size: 0.7rem;"></i>
                                    </div>
                                    <div class="mt-1"><span class="small text-muted fw-bold" style="font-size: 0.6rem;">STOK: <span class="stock-info">${stock}</span></span></div>
                                </div>
                            </div>
                            <div class="row align-items-center">
                                <div class="col-4"><label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Quantity</label></div>
                                <div class="col-8">
                                    <input type="number" name="products[${rowId}][qty]" class="form-control form-control-sm border-0 bg-light rounded-3 px-3 py-2 fw-bold text-dark qty-input" 
                                           placeholder="Otomatis dari SN" value="${qty}" min="1" style="font-size: 0.85rem;" inputmode="numeric" readonly>
                                    <div class="stock-warning mt-2 d-none rounded-3 px-3 py-2 fw-bold" style="font-size: 0.68rem; background: rgba(239, 68, 68, 0.08); color: #dc2626;"></div>
                                </div>
                            </div>
                            <div class="serial-input-area mt-3 ${id ? '' : 'd-none'}">
                                <div class="serial-section-heading"><span class="serial-section-icon"><i class="fas fa-barcode"></i></span><div><strong>Serial Number</strong><small>Masukkan SN untuk menghitung jumlah otomatis.</small></div></div>
                                <div class="mb-3"><label class="serial-field-label">Cara input</label><select name="products[${rowId}][serial_mode]" class="serial-hidden-select serial-mode-select"><option value="single" ${serial.serial_mode !== 'range' ? 'selected' : ''}>Satuan</option><option value="range" ${serial.serial_mode === 'range' ? 'selected' : ''}>Massal</option></select><div class="serial-segmented serial-mode-choices" role="group" aria-label="Cara input SN"><button type="button" class="serial-segment ${serial.serial_mode !== 'range' ? 'active' : ''}" data-serial-mode="single" aria-pressed="${serial.serial_mode !== 'range'}"><i class="fas fa-barcode" aria-hidden="true"></i><span>Satuan</span></button><button type="button" class="serial-segment ${serial.serial_mode === 'range' ? 'active' : ''}" data-serial-mode="range" aria-pressed="${serial.serial_mode === 'range'}"><i class="fas fa-layer-group" aria-hidden="true"></i><span>Massal</span></button></div></div>
                                <input type="hidden" name="products[${rowId}][identifier_type]" class="identifier-type-select" value="sn">
                                <div class="serial-single-wrap ${serial.serial_mode === 'range' ? 'd-none' : ''}"><label class="serial-field-label">SN satuan</label><textarea name="products[${rowId}][serial_value]" class="form-control form-control-sm serial-value-input" inputmode="numeric" autocomplete="off" rows="3" placeholder="Masukkan beberapa SN, satu per baris">${escapeHtml(serial.serial_value || '')}</textarea><small class="serial-qty-hint serial-single-hint text-muted"></small></div>
                                <div class="serial-range-wrap ${serial.serial_mode === 'range' ? '' : 'd-none'}"><label class="serial-field-label">Rentang SN</label><div class="serial-range-fields"><div class="serial-range-field"><label>SN awal</label><input name="products[${rowId}][serial_start]" class="form-control form-control-sm serial-start-input" inputmode="numeric" autocomplete="off" value="${escapeHtml(serial.serial_start || '')}" placeholder="SN awal"></div><span class="serial-range-separator">SAMPAI</span><div class="serial-range-field"><label>SN akhir</label><input name="products[${rowId}][serial_end]" class="form-control form-control-sm serial-end-input" inputmode="numeric" autocomplete="off" value="${escapeHtml(serial.serial_end || '')}" placeholder="SN akhir"></div></div><small class="serial-qty-hint serial-range-hint text-muted">Masukkan SN awal dan SN akhir; Qty dihitung otomatis.</small></div>
                            </div>
                        </div>
                    </div>
                </div>`;
            $('#product-container').append(html);
            const row = $('#product-container .glass-card').last();
            syncSerialFields(row);
            updateSerialQuantity(row);
        }

        function syncSerialFields(row) {
            const id = row.find('.iddenom-input').val();
            const isStarterPack = Boolean(starterPackMap[id]);
            const mode = row.find('.serial-mode-select').val() || 'single';
            let identifierType = row.find('.identifier-type-select').val() || 'sn';
            row.find('.serial-input-area').toggleClass('d-none', !id);
            row.find('.identifier-type-wrap').toggleClass('d-none', mode === 'range');
            row.find('.serial-single-wrap').toggleClass('d-none', mode !== 'single');
            row.find('.serial-range-wrap').toggleClass('d-none', mode !== 'range');
            const numberOption = row.find('.identifier-type-select option[value="number"]');
            numberOption.prop('disabled', !isStarterPack);
            if (!isStarterPack || mode === 'range') {
                row.find('.identifier-type-select').val('sn');
                identifierType = 'sn';
            }
            row.find('.serial-mode-choices .serial-segment').each(function() {
                const active = String($(this).data('serial-mode')) === mode;
                $(this).toggleClass('active', active).attr('aria-pressed', active).prop('disabled', !id);
            });
            row.find('.identifier-type-choices .serial-segment').each(function() {
                const type = String($(this).data('identifier-type'));
                const active = type === identifierType;
                $(this).toggleClass('active', active).attr('aria-pressed', active).prop('disabled', !id || (type === 'number' && !isStarterPack));
            });
            row.find('.serial-value-input').prop('disabled', !id || mode !== 'single');
            row.find('.serial-start-input, .serial-end-input').prop('disabled', !id || mode !== 'range');
            row.find('.serial-mode-select, .identifier-type-select').prop('disabled', !id);
            row.find('.serial-single-hint').text(isStarterPack
                ? 'Bisa masukkan beberapa SN sekaligus, satu SN per baris. Format: 16 digit diawali 0.'
                : 'Bisa masukkan beberapa SN sekaligus, satu SN per baris. Format: 12 digit.');
            const snLength = isStarterPack ? 16 : 12;
            row.find('.serial-start-input, .serial-end-input').attr('maxlength', snLength);
            row.find('.serial-value-input').attr('maxlength', 50000);
        }

        function updateSerialQuantity(row) {
            const mode = row.find('.serial-mode-select').val();
            const id = row.find('.iddenom-input').val();
            const isStarterPack = Boolean(starterPackMap[id]);
            const identifierType = row.find('.identifier-type-select').val() || 'sn';
            let qty = 0;
            if (mode === 'single') {
                const value = row.find('.serial-value-input').val() || '';
                const serials = value.trim() ? value.trim().split(/[\s,;]+/).filter(Boolean) : [];
                const pattern = identifierType === 'number' ? /^08\d{7,10}$/ : (isStarterPack ? /^0\d{15}$/ : /^\d{12}$/);
                const invalidCount = serials.filter(serial => !pattern.test(serial)).length;
                const valid = serials.length > 0 && invalidCount === 0;
                if (valid) qty = serials.length;
                const hint = serials.length === 0
                    ? 'Bisa masukkan beberapa SN sekaligus, satu SN per baris.'
                    : (invalidCount > 0 ? `${invalidCount} SN belum sesuai format ${isStarterPack ? '16 digit diawali 0' : '12 digit'}.` : `${serials.length} SN satuan terhitung.`);
                row.find('.serial-single-hint').text(hint).toggleClass('text-danger', serials.length > 0 && !valid).toggleClass('text-success', valid);
            } else {
                const start = row.find('.serial-start-input').val() || '';
                const end = row.find('.serial-end-input').val() || '';
                const validFormat = isStarterPack
                    ? /^0\d{15}$/.test(start) && /^0\d{15}$/.test(end)
                    : /^\d{12}$/.test(start) && /^\d{12}$/.test(end);
                if (validFormat) {
                    const first = start.replace(/^0+(?=\d)/, '');
                    const last = end.replace(/^0+(?=\d)/, '');
                    if (last.length > first.length || (last.length === first.length && last >= first)) {
                        let borrow = 0;
                        let difference = '';
                        for (let i = 0; i < last.length; i++) {
                            const endDigit = Number(last[last.length - 1 - i]);
                            const startDigit = Number(first[first.length - 1 - i] || 0) + borrow;
                            let digit = endDigit - startDigit;
                            borrow = digit < 0 ? 1 : 0;
                            if (borrow) digit += 10;
                            difference = String(digit) + difference;
                        }
                        difference = difference.replace(/^0+(?=\d)/, '');
                        let carry = 1;
                        let inclusiveCount = '';
                        for (let i = difference.length - 1; i >= 0; i--) {
                            const digit = Number(difference[i]) + carry;
                            inclusiveCount = String(digit % 10) + inclusiveCount;
                            carry = digit >= 10 ? 1 : 0;
                        }
                        if (carry) inclusiveCount = '1' + inclusiveCount;
                        if (inclusiveCount.length < 7 || (inclusiveCount.length === 7 && inclusiveCount <= '2000000')) {
                            qty = Number(inclusiveCount);
                        }
                    }
                }
            }
            row.find('.qty-input').val(qty || '');
            if (mode === 'range') {
                const start = row.find('.serial-start-input').val() || '';
                const end = row.find('.serial-end-input').val() || '';
                const hasInput = Boolean(start || end);
                row.find('.serial-range-hint').text(qty ? `${qty.toLocaleString('id-ID')} SN terhitung dari rentang.` : (hasInput ? `Format wajib: ${isStarterPack ? '16 digit diawali 0' : '12 digit'}; rentang maksimal 2.000.000 SN.` : `SN harus ${isStarterPack ? '16 digit diawali 0' : '12 digit'}.`));
                row.find('.serial-range-hint').toggleClass('text-danger', hasInput && !qty).toggleClass('text-success', Boolean(qty));
            }
            recalcTotal();
            validateStock(row, qty);
        }

        function recalcTotal() {
            let pcs = 0, items = 0;
            const details = [];
            $('.iddenom-input').each(function() {
                const row = $(this).closest('.p-4');
                const id = $(this).val(), qty = parseInt(row.find('.qty-input').val()) || 0;
                if (id && qty > 0) {
                    items++;
                    pcs += qty;
                    details.push({ name: row.find('.product-placeholder').text(), qty });
                }
            });
            if (items > 0) {
                $('#setoran-summary').slideDown(200);
                $('#total-items').text(items);
                $('#total-pcs').text(pcs.toLocaleString('id-ID'));
                const detailHtml = details.map(item => `
                    <div class="d-flex justify-content-between gap-2 mb-2 small">
                        <strong>${escapeHtml(item.name)}</strong>
                        <strong class="text-success text-nowrap">${item.qty.toLocaleString('id-ID')} pcs</strong>
                    </div>`).join('');
                $('#setoran-detail').html(detailHtml);
            }
            else $('#setoran-summary').slideUp(200);
        }

        function escapeHtml(value) {
            return $('<div>').text(value).html();
        }

        function validateStock(row, requestedQty = null) {
            const id = row.find('.iddenom-input').val();
            const qty = requestedQty ?? (parseInt(row.find('.qty-input').val()) || 0);
            const stock = Number(stockMap[id] || 0);
            const warning = row.find('.stock-warning');
            if (id && qty > stock) {
                warning.text(`⚠️ Stok tidak cukup (Sisa: ${stock})`).removeClass('d-none');
                return false;
            }
            warning.addClass('d-none');
            return true;
        }

        $(document).on('click', '.product-trigger', function() { activeRow = $(this); $('#productPickerModal').modal('show'); $('#product-search-modal').val('').trigger('input').focus(); });
        $('#product-search-modal').on('input', function() {
            const val = $(this).val().toLowerCase().trim();
            let matches = 0;
            $('.select-product-btn').each(function() {
                const name = $(this).data('name').toString().toLowerCase();
                const id = $(this).data('id').toString().toLowerCase();
                const stock = parseInt($(this).data('stock')) || 0;
                
                if (val === '') {
                    if (stock > 0) {
                        $(this).attr('style', (i,s) => s.replace(/display:[^;]+;?/g, '') + 'display: flex !important;'); 
                        matches++; 
                    }
                    else { $(this).attr('style', (i,s) => s.replace(/display:[^;]+;?/g, '') + 'display: none !important;'); }
                } else {
                    if (stock > 0 && (name.includes(val) || id.includes(val))) { $(this).attr('style', (i,s) => s.replace(/display:[^;]+;?/g, '') + 'display: flex !important;'); matches++; }
                    else { $(this).attr('style', (i,s) => s.replace(/display:[^;]+;?/g, '') + 'display: none !important;'); }
                }
            });
            $('#no-results').remove();
            if (matches === 0) $('#modal-product-list').append('<div id="no-results" class="text-center py-5 opacity-50"><i class="fas fa-search fs-1 mb-3"></i><p>Produk tidak ditemukan</p></div>');
        });

        $(document).on('click', '.select-product-btn', function() {
            const id = $(this).data('id'), name = $(this).data('name'), stock = Number(stockMap[id] || 0);
            activeRow.find('.product-placeholder').text(name).removeClass('text-muted').addClass('text-dark');
            activeRow.find('.iddenom-input').prop('disabled', false).val(id);
            activeRow.closest('.glass-card').find('.qty-input').prop('disabled', false);
            activeRow.closest('.p-4').find('.stock-info').text(stock);
            const productRow = activeRow.closest('.glass-card');
            productRow.find('.serial-mode-select').val('single');
            productRow.find('.identifier-type-select').val('sn');
            productRow.find('.serial-value-input, .serial-start-input, .serial-end-input').val('');
            syncSerialFields(productRow);
            updateSerialQuantity(productRow);
            $('#productPickerModal').modal('hide');
            recalcTotal(); validateStock(activeRow.closest('.glass-card')); saveFormState();
        });

        $(document).on('input change', '.serial-mode-select, .identifier-type-select, .serial-value-input, .serial-start-input, .serial-end-input', function() {
            const row = $(this).closest('.glass-card');
            syncSerialFields(row);
            updateSerialQuantity(row);
            saveFormState();
        });
        $(document).on('click', '.serial-mode-choices .serial-segment', function() {
            const row = $(this).closest('.glass-card');
            row.find('.serial-mode-select').val(String($(this).data('serial-mode'))).trigger('change');
        });
        $(document).on('click', '.identifier-type-choices .serial-segment:not(:disabled)', function() {
            const row = $(this).closest('.glass-card');
            row.find('.identifier-type-select').val(String($(this).data('identifier-type'))).trigger('change');
        });
        $(document).on('input', '.qty-input', function() { recalcTotal(); validateStock($(this).closest('.glass-card')); saveFormState(); });
        $('#add-item-btn').click(() => { addProductRow(); saveFormState(); });
        $(document).on('click', '.remove-row', function() { $(this).closest('.glass-card').fadeOut(200, function() { $(this).remove(); recalcTotal(); saveFormState(); }); });
        
        $('#sales-form').submit(function(e) {
            if (!$('#id_outlet').val()) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Pilih Outlet', text: 'Cari lalu pilih outlet yang terdaftar untuk Sales Force ini.', confirmButtonColor: 'var(--primary)' });
                return;
            }
            if ($('#consignment_enabled').val() === '1') {
                const consignment = updateConsignmentSummary();
                let consignmentRowsValid = true;
                const selectedConsignmentDenoms = new Set();
                $('.consignment-denom-row').each(function() {
                    const denom = $(this).find('.consignment-denom-select').val();
                    const rowNumbers = parseConsignmentNumbers($(this).find('.consignment-row-numbers').val());
                    if (!denom || !rowNumbers.numbers.length || selectedConsignmentDenoms.has(denom)) consignmentRowsValid = false;
                    if (denom) selectedConsignmentDenoms.add(denom);
                });
                if (!consignmentRowsValid) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Lengkapi Konsinyasi', text: 'Pilih denom yang berbeda pada setiap baris dan isi minimal satu nomor.', confirmButtonColor: 'var(--primary)' });
                    return;
                }
                if (!consignment.numbers.length || consignment.invalid.length || consignment.duplicates.length) {
                    e.preventDefault();
                    renderConsignmentValidation(consignment, false);
                    $('.consignment-row-numbers').first().trigger('focus');
                    return;
                }
                const key = consignment.numbers.join('|');
                const availabilityReady = checkedConsignmentKey === key && consignment.numbers.every(number => consignmentAvailability.has(number));
                if (!availabilityReady) {
                    e.preventDefault();
                    checkConsignmentAvailability((available) => {
                        if (available) $('#sales-form').trigger('submit');
                        else $('.consignment-row-numbers').first().trigger('focus');
                    });
                    return;
                }
                if (consignment.numbers.some(number => !consignmentAvailability.get(number)?.available)) {
                    e.preventDefault();
                    renderConsignmentValidation(consignment, false);
                    $('.consignment-row-numbers').first().trigger('focus');
                    return;
                }
            }
            const requested = {};
            let hasProduct = false;
            let incompleteProduct = false;
            $('.iddenom-input').each(function() {
                const id = $(this).val();
                const qty = parseInt($(this).closest('.glass-card').find('.qty-input').val()) || 0;
                if (id || qty) {
                    hasProduct = true;
                    if (!id || qty < 1) incompleteProduct = true;
                }
                if (id) requested[id] = (requested[id] || 0) + qty;
            });
            const hasConsignment = $('#consignment_enabled').val() === '1' && parseConsignmentNumbers($('#consignment_numbers').val()).numbers.length > 0;
            if (!hasProduct && !hasConsignment) {
                e.preventDefault();
                $('#sales-activity-feedback').removeClass('d-none').html('<i class="fas fa-exclamation-circle me-1"></i>Pilih produk atau isi nomor konsinyasi SA.');
                return;
            }
            if (incompleteProduct) {
                e.preventDefault();
                $('#sales-activity-feedback').removeClass('d-none').html('<i class="fas fa-exclamation-circle me-1"></i>Lengkapi produk dan quantity, atau hapus baris produk yang kosong.');
                return;
            }
            $('#sales-activity-feedback').addClass('d-none').empty();
            let valid = true;
            let serialEntriesValid = true;
            $('#product-container .glass-card').each(function() {
                const id = $(this).find('.iddenom-input').val();
                if (!id) {
                    $(this).find('input').prop('disabled', true);
                    return;
                }
                const row = $(this);
                if (row.find('.serial-mode-select').val() === 'single') {
                    const value = String(row.find('.serial-value-input').val() || '').trim();
                    const serials = value ? value.split(/[\s,;]+/).filter(Boolean) : [];
                    const isStarterPack = Boolean(starterPackMap[id]);
                    const pattern = isStarterPack ? /^0\d{15}$/ : /^\d{12}$/;
                    const seen = new Set();
                    const duplicates = serials.some(serial => seen.has(serial) || !seen.add(serial));
                    if (!serials.length || serials.some(serial => !pattern.test(serial)) || duplicates) {
                        serialEntriesValid = false;
                        row.find('.serial-value-input').trigger('focus');
                    }
                }
                if (!validateStock($(this), requested[id] || 0)) valid = false;
            });
            if (!serialEntriesValid) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Periksa SN', text: 'Pastikan semua SN satuan sesuai format dan tidak ada nomor yang berulang.', confirmButtonColor: 'var(--primary)' });
                return;
            }
            if (!valid) { e.preventDefault(); Swal.fire({ icon: 'warning', title: 'Stok Tidak Cukup', text: 'Periksa kembali qty yang melebihi stok.', confirmButtonColor: 'var(--primary)' }); }
        });

        loadFormState();
        setConsignmentOpen($('#consignment_enabled').val() === '1');
        updateConsignmentSummary();
        if ($('#consignment_enabled').val() === '1' && $('#consignment_numbers').val().trim()) checkConsignmentAvailability();
        if ($('#product-container').children().length === 0) addProductRow();
    });
</script>
@endpush
