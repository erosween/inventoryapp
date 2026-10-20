@extends('layout.mobile_layout')

@section('title', 'Input Penjualan')

@section('content')
<div class="reveal">
    <form action="{{ route('mobile.store') }}" method="POST" id="sales-form">
        @csrf
        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">
        
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
                <textarea name="consignment_numbers" id="consignment_numbers" class="form-control consignment-textarea" rows="5" inputmode="text" enterkeyhint="enter" autocomplete="off" placeholder="081234567890&#10;081234567891">{{ old('consignment_numbers') }}</textarea>
                <div id="consignment-feedback" class="consignment-feedback mt-2"></div>
                @error('consignment_numbers')
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

        <div class="sticky-bottom pb-4 pt-2 px-1" style="background: var(--bg-body); z-index: 10;">
            <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-800 shadow" style="font-size: 0.95rem; background: var(--primary); border: none;">
                Lihat Detail & Konfirmasi <i class="fas fa-arrow-right ms-1"></i>
            </button>
        </div>
    </form>
</div>

@push('styles')
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
        let outletSearchTimer = null;

        function selectOutlet(outlet) {
            $('#id_outlet').val(String(outlet.id_outlet || '').toUpperCase());
            $('#outlet-search-inline').val(String(outlet.id_outlet || '').toUpperCase());
            $('#selected-outlet-name').text(outlet.nama_outlet || 'Outlet belum ditemukan');
            $('#outlet-inline-results').addClass('d-none').empty();
            saveFormState();
        }

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
                consignment_numbers: $('#consignment_numbers').val(),
                products: []
            };
            $('#product-container .glass-card').each(function() {
                const id = $(this).find('.iddenom-input').val();
                const qty = $(this).find('.qty-input').val();
                const name = $(this).find('.product-placeholder').text();
                if (id || qty) formData.products.push({ id, name, qty });
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
            $('#consignment-toggle').attr('aria-expanded', open ? 'true' : 'false');
            $('#consignment-toggle .consignment-toggle-action i').toggleClass('fa-plus', !open).toggleClass('fa-chevron-up', open);
            if (open) updateConsignmentSummary();
        }

        $('#consignment-toggle').on('click', function() {
            setConsignmentOpen($('#consignment_enabled').val() !== '1');
            saveFormState();
        });
        $('#consignment_numbers').on('input', function() {
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
        $('#consignment_numbers').on('keydown', function(event) {
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
            $('#consignment_numbers').val('');
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
                $('#consignment_numbers').val(data.consignment_numbers || '');
                setConsignmentOpen(Boolean(data.consignment_enabled));
                if (data.products && data.products.length > 0) {
                    $('#product-container').empty();
                    const restoredDenoms = new Set();
                    data.products
                        .filter(p => !p.id || Number(stockMap[p.id] || 0) > 0)
                        .filter(p => {
                            if (!p.id) return true;
                            if (restoredDenoms.has(String(p.id))) return false;
                            restoredDenoms.add(String(p.id));
                            return true;
                        })
                        .forEach(p => addProductRow(p.id, p.name, p.qty));
                    recalcTotal();
                }
            } catch (e) { console.error(e); }
        }

        function addProductRow(id = '', name = 'Select item...', qty = '') {
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
                                           placeholder="0" value="${qty}" min="1" style="font-size: 0.85rem;" inputmode="numeric">
                                    <div class="stock-warning mt-2 d-none rounded-3 px-3 py-2 fw-bold" style="font-size: 0.68rem; background: rgba(239, 68, 68, 0.08); color: #dc2626;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            $('#product-container').append(html);
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

        function selectedDenomIdsExceptActiveRow() {
            const selected = new Set();
            const activeCard = activeRow?.closest('.glass-card')[0];
            $('.iddenom-input').each(function() {
                if ($(this).closest('.glass-card')[0] === activeCard) return;
                const id = String($(this).val() || '');
                if (id) selected.add(id);
            });
            return selected;
        }

        $(document).on('click', '.product-trigger', function() { activeRow = $(this); $('#productPickerModal').modal('show'); $('#product-search-modal').val('').trigger('input').focus(); });
        $('#product-search-modal').on('input', function() {
            const val = $(this).val().toLowerCase().trim();
            const selectedDenoms = selectedDenomIdsExceptActiveRow();
            let matches = 0;
            $('.select-product-btn').each(function() {
                const name = $(this).data('name').toString().toLowerCase();
                const id = $(this).data('id').toString().toLowerCase();
                const stock = parseInt($(this).data('stock')) || 0;
                const alreadySelected = selectedDenoms.has(String($(this).data('id')));
                
                if (val === '') {
                    if (stock > 0 && !alreadySelected) {
                        $(this).attr('style', (i,s) => s.replace(/display:[^;]+;?/g, '') + 'display: flex !important;'); 
                        matches++; 
                    }
                    else { $(this).attr('style', (i,s) => s.replace(/display:[^;]+;?/g, '') + 'display: none !important;'); }
                } else {
                    if (stock > 0 && !alreadySelected && (name.includes(val) || id.includes(val))) { $(this).attr('style', (i,s) => s.replace(/display:[^;]+;?/g, '') + 'display: flex !important;'); matches++; }
                    else { $(this).attr('style', (i,s) => s.replace(/display:[^;]+;?/g, '') + 'display: none !important;'); }
                }
            });
            $('#no-results').remove();
            if (matches === 0) $('#modal-product-list').append('<div id="no-results" class="text-center py-5 opacity-50"><i class="fas fa-search fs-1 mb-3"></i><p>Produk tidak ditemukan</p></div>');
        });

        $(document).on('click', '.select-product-btn', function() {
            const id = $(this).data('id'), name = $(this).data('name'), stock = Number(stockMap[id] || 0);
            if (selectedDenomIdsExceptActiveRow().has(String(id))) return;
            activeRow.find('.product-placeholder').text(name).removeClass('text-muted').addClass('text-dark');
            activeRow.find('.iddenom-input').prop('disabled', false).val(id);
            activeRow.closest('.glass-card').find('.qty-input').prop('disabled', false);
            activeRow.closest('.p-4').find('.stock-info').text(stock);
            $('#productPickerModal').modal('hide');
            recalcTotal(); validateStock(activeRow.closest('.glass-card')); saveFormState();
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
                if (!consignment.numbers.length || consignment.invalid.length || consignment.duplicates.length) {
                    e.preventDefault();
                    renderConsignmentValidation(consignment, false);
                    $('#consignment_numbers').trigger('focus');
                    return;
                }
                const key = consignment.numbers.join('|');
                const availabilityReady = checkedConsignmentKey === key && consignment.numbers.every(number => consignmentAvailability.has(number));
                if (!availabilityReady) {
                    e.preventDefault();
                    checkConsignmentAvailability((available) => {
                        if (available) $('#sales-form').trigger('submit');
                        else $('#consignment_numbers').trigger('focus');
                    });
                    return;
                }
                if (consignment.numbers.some(number => !consignmentAvailability.get(number)?.available)) {
                    e.preventDefault();
                    renderConsignmentValidation(consignment, false);
                    $('#consignment_numbers').trigger('focus');
                    return;
                }
                $('#consignment_numbers').val(consignment.numbers.join('\n'));
            }
            const requested = {};
            let duplicateDenom = false;
            let hasProduct = false;
            let incompleteProduct = false;
            $('.iddenom-input').each(function() {
                const id = $(this).val();
                const qty = parseInt($(this).closest('.glass-card').find('.qty-input').val()) || 0;
                if (id || qty) {
                    hasProduct = true;
                    if (!id || qty < 1) incompleteProduct = true;
                }
                if (id && Object.prototype.hasOwnProperty.call(requested, id)) duplicateDenom = true;
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
            if (duplicateDenom) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Produk Duplikat', text: 'Satu denom hanya boleh dimasukkan satu kali.', confirmButtonColor: 'var(--primary)' });
                return;
            }
            let valid = true;
            $('#product-container .glass-card').each(function() {
                const id = $(this).find('.iddenom-input').val();
                if (!id) {
                    $(this).find('input').prop('disabled', true);
                    return;
                }
                if (!validateStock($(this), requested[id] || 0)) valid = false;
            });
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
