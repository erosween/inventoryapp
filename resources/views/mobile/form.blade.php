@extends('layout.mobile_layout')

@section('title', 'Input Penjualan')

@section('content')
<div class="reveal">
    <form action="{{ route('mobile.store') }}" method="POST" id="sales-form">
        @csrf
        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">
        
        <!-- Outlet Details - Blue Bento Card -->
        <div class="glass-card mb-4 p-0 border-0 shadow-sm overflow-hidden" style="background: white; border-radius: 24px;">
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
                                <input type="text" name="id_outlet" id="id_outlet" class="form-control form-control-sm border-0 bg-light rounded-3 px-3 py-2 fw-bold text-dark" placeholder="Search or scan..." required>
                            @endif
                        </div>
                    </div>

                    <div class="row align-items-center mt-2">
                        <div class="col-4">
                            <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Nama Outlet</label>
                        </div>
                        <div class="col-8">
                            <div class="bg-light rounded-3 px-3 py-2 fw-bold text-dark" style="font-size: 0.85rem; min-height: 35px;">
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

        <!-- Live Total Setoran Summary -->
        <div id="setoran-summary" class="glass-card mb-4 p-0 border-0 shadow-sm overflow-hidden" style="background: white; border-radius: 24px; display: none;">
            <div class="d-flex align-items-stretch">
                <div style="width: 6px; background: #10b981; opacity: 0.8;"></div>
                <div class="p-4 w-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-800 mb-1 text-dark" style="font-size: 0.75rem; letter-spacing: 1px;">ESTIMASI SETORAN</h6>
                            <div class="text-muted fw-bold" style="font-size: 0.65rem;"><span id="total-items">0</span> item • <span id="total-pcs">0</span> pcs</div>
                        </div>
                        <div class="text-end">
                            <h4 class="fw-800 text-success mb-0" id="total-setoran" style="font-size: 1.3rem;">Rp 0</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky-bottom pb-4 pt-2 px-1" style="background: linear-gradient(to top, var(--bg-body) 70%, transparent); z-index: 10;">
            <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-800 shadow" style="font-size: 0.95rem; background: var(--primary); border: none;">
                Log Activity <i class="fas fa-paper-plane ms-1"></i>
            </button>
        </div>
    </form>
</div>

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
                                $n = strtoupper($d->denom);
                                return (str_contains($n, 'SA SIMPATI 3GB') || str_contains($n, 'SA BYU 3GB'));
                            });
                            $otherItems = $denoms->reject(function($d) {
                                $n = strtoupper($d->denom);
                                return (str_contains($n, 'SA SIMPATI 3GB') || str_contains($n, 'SA BYU 3GB'));
                            });
                        @endphp

                        {{-- Render Special Items First --}}
                        @foreach($specialItems as $denom)
                        <button type="button" class="list-group-item list-group-item-action border-0 mb-3 rounded-4 py-3 px-4 d-flex align-items-center select-product-btn" 
                                style="background: #ffffff; border: 1px solid #f1f5f9 !important; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: all 0.2s; display: flex !important;"
                                data-id="{{ $denom->iddenom }}" 
                                data-name="{{ $denom->denom }}"
                                data-harga="{{ $denom->harga_jual ?? 0 }}"
                                data-stock="{{ $denom->stock_qty ?? 0 }}">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-4 me-3 text-primary">
                                <i class="fas fa-star fs-5"></i>
                            </div>
                            <div class="flex-grow-1 text-start">
                                <div class="fw-800 text-dark" style="font-size: 1.05rem; line-height: 1.2;">{{ $denom->denom }}</div>
                                <div class="small text-muted fw-bold mt-1 d-flex align-items-center" style="font-size: 0.7rem;">
                                    <span class="badge bg-primary bg-opacity-10 text-primary me-2 px-2 py-1 rounded-pill">VIRTUAL</span>
                                    <span class="text-primary">READY</span>
                                    @if($denom->harga_jual > 0)
                                        <span class="ms-2 text-success fw-bold">• Rp {{ number_format($denom->harga_jual, 0, ',', '.') }}</span>
                                    @endif
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-muted opacity-25"></i>
                        </button>
                        @endforeach

                        {{-- Render Other Items --}}
                        @foreach($otherItems as $denom)
                        <button type="button" class="list-group-item list-group-item-action border-0 mb-3 rounded-4 py-3 px-4 d-flex align-items-center select-product-btn" 
                                style="background: #ffffff; border: 1px solid #f1f5f9 !important; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: all 0.2s; display: {{ $denom->stock_qty > 0 ? 'flex' : 'none' }} !important;"
                                data-id="{{ $denom->iddenom }}" 
                                data-name="{{ $denom->denom }}"
                                data-harga="{{ $denom->harga_jual ?? 0 }}"
                                data-stock="{{ $denom->stock_qty ?? 0 }}">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-4 me-3 text-primary">
                                <i class="fas fa-box-open fs-5"></i>
                            </div>
                            <div class="flex-grow-1 text-start">
                                <div class="fw-800 text-dark" style="font-size: 1.05rem; line-height: 1.2;">{{ $denom->denom }}</div>
                                <div class="small text-muted fw-bold mt-1 d-flex align-items-center" style="font-size: 0.7rem;">
                                    @if($denom->stock_qty > 0)
                                        <span class="badge bg-success bg-opacity-10 text-success me-2 px-2 py-1 rounded-pill">READY</span>
                                        <span class="text-primary">STOK: {{ number_format($denom->stock_qty) }}</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger me-2 px-2 py-1 rounded-pill">EMPTY</span>
                                        <span class="text-danger">STOK HABIS</span>
                                    @endif
                                    @if($denom->harga_jual > 0)
                                        <span class="ms-2 text-success fw-bold">• Rp {{ number_format($denom->harga_jual, 0, ',', '.') }}</span>
                                    @endif
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
        backdrop-filter: blur(4px);
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
        const priceMap = {!! json_encode($denoms->pluck('harga_jual', 'iddenom')) !!};
        const stockMap = {!! json_encode($denoms->pluck('stock_qty', 'iddenom')) !!};

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
                products: []
            };
            $('#product-container .glass-card').each(function() {
                const id = $(this).find('.iddenom-input').val();
                const qty = $(this).find('.qty-input').val();
                const name = $(this).find('.product-placeholder').text();
                const stock = $(this).find('.stock-info').text();
                if (id || qty) formData.products.push({ id, name, stock, qty });
            });
            localStorage.setItem(STORAGE_KEY, JSON.stringify(formData));
        }

        function loadFormState() {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (!saved) return;
            try {
                const data = JSON.parse(saved);
                const currentOutlet = $('#id_outlet').val();
                if (currentOutlet && data.id_outlet && currentOutlet !== data.id_outlet) return;
                if (!currentOutlet && data.id_outlet) $('#id_outlet').val(data.id_outlet);
                $('#keterangan').val(data.keterangan || '');
                if (data.products && data.products.length > 0) {
                    $('#product-container').empty();
                    data.products.forEach(p => addProductRow(p.id, p.name, p.stock, p.qty));
                    recalcTotal();
                }
            } catch (e) { console.error(e); }
        }

        function addProductRow(id = '', name = 'Select item...', stock = 0, qty = '') {
            const rowId = Date.now() + Math.random().toString(36).substr(2, 5);
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
                                        <span class="product-placeholder fw-bold ${id ? 'text-dark' : 'text-muted'}" style="font-size: 0.8rem;">${name}</span>
                                        <input type="hidden" name="products[${rowId}][iddenom]" class="iddenom-input" value="${id}" required>
                                        <i class="fas fa-search text-muted" style="font-size: 0.7rem;"></i>
                                    </div>
                                    <div class="mt-1"><span class="small text-muted fw-bold" style="font-size: 0.6rem;">STOK: <span class="stock-info">${stock}</span></span></div>
                                </div>
                            </div>
                            <div class="row align-items-center">
                                <div class="col-4"><label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Quantity</label></div>
                                <div class="col-8">
                                    <input type="number" name="products[${rowId}][qty]" class="form-control form-control-sm border-0 bg-light rounded-3 px-3 py-2 fw-bold text-dark qty-input" 
                                           placeholder="0" value="${qty}" min="1" required style="font-size: 0.85rem;" inputmode="numeric">
                                    <div class="stock-warning mt-2 d-none rounded-3 px-3 py-2 fw-bold" style="font-size: 0.68rem; background: rgba(239, 68, 68, 0.08); color: #dc2626;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            $('#product-container').append(html);
            if (id) updatePriceLabel($('#product-container').children().last(), id);
        }

        function updatePriceLabel(row, id) {
            const harga = priceMap[id] || 0;
            let label = row.find('.price-label');
            if (harga > 0) {
                if (label.length === 0) row.find('.product-trigger').after(`<div class="price-label mt-1"><span class="badge bg-success bg-opacity-10 text-success rounded-pill" style="font-size: 0.6rem;">Rp ${harga.toLocaleString('id-ID')}/pcs</span></div>`);
                else label.find('span').text(`Rp ${harga.toLocaleString('id-ID')}/pcs`);
            } else label.remove();
        }

        function recalcTotal() {
            let setoran = 0, pcs = 0, items = 0;
            $('.iddenom-input').each(function() {
                const id = $(this).val(), qty = parseInt($(this).closest('.p-4').find('.qty-input').val()) || 0;
                if (id && qty > 0) { items++; pcs += qty; setoran += qty * (priceMap[id] || 0); }
            });
            if (items > 0) { $('#setoran-summary').slideDown(200); $('#total-items').text(items); $('#total-pcs').text(pcs.toLocaleString('id-ID')); $('#total-setoran').text('Rp ' + setoran.toLocaleString('id-ID')); }
            else $('#setoran-summary').slideUp(200);
        }

        function validateStock(row) {
            const id = row.find('.iddenom-input').val(), qty = parseInt(row.find('.qty-input').val()) || 0, stock = parseInt(row.find('.stock-info').text()) || 0;
            const warning = row.find('.stock-warning');
            if (id && !id.startsWith('SA') && !id.startsWith('SEGEL') && qty > stock) {
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
                    if (stock > 0 || name.includes('sa simpati 3gb') || name.includes('sa byu 3gb')) { 
                        $(this).attr('style', (i,s) => s.replace(/display:[^;]+;?/g, '') + 'display: flex !important;'); 
                        matches++; 
                    }
                    else { $(this).attr('style', (i,s) => s.replace(/display:[^;]+;?/g, '') + 'display: none !important;'); }
                } else {
                    if (name.includes(val) || id.includes(val)) { $(this).attr('style', (i,s) => s.replace(/display:[^;]+;?/g, '') + 'display: flex !important;'); matches++; }
                    else { $(this).attr('style', (i,s) => s.replace(/display:[^;]+;?/g, '') + 'display: none !important;'); }
                }
            });
            $('#no-results').remove();
            if (matches === 0) $('#modal-product-list').append('<div id="no-results" class="text-center py-5 opacity-50"><i class="fas fa-search fs-1 mb-3"></i><p>Produk tidak ditemukan</p></div>');
        });

        $(document).on('click', '.select-product-btn', function() {
            const id = $(this).data('id'), name = $(this).data('name'), stock = $(this).data('stock') || 0;
            activeRow.find('.product-placeholder').text(name).removeClass('text-muted').addClass('text-dark');
            activeRow.find('.iddenom-input').val(id);
            activeRow.closest('.p-4').find('.stock-info').text(stock);
            updatePriceLabel(activeRow.closest('.p-4'), id);
            $('#productPickerModal').modal('hide');
            recalcTotal(); validateStock(activeRow.closest('.glass-card')); saveFormState();
        });

        $(document).on('input', '.qty-input', function() { recalcTotal(); validateStock($(this).closest('.glass-card')); saveFormState(); });
        $('#add-item-btn').click(() => { addProductRow(); saveFormState(); });
        $(document).on('click', '.remove-row', function() { $(this).closest('.glass-card').fadeOut(200, function() { $(this).remove(); recalcTotal(); saveFormState(); }); });
        
        $('#sales-form').submit(function(e) {
            let valid = true; $('.glass-card').each(function() { if (!validateStock($(this))) valid = false; });
            if (!valid) { e.preventDefault(); Swal.fire({ icon: 'warning', title: 'Stok Tidak Cukup', text: 'Periksa kembali qty yang melebihi stok.', confirmButtonColor: 'var(--primary)' }); }
            else localStorage.removeItem(STORAGE_KEY);
        });

        loadFormState();
        if ($('#product-container').children().length === 0) addProductRow();
    });
</script>
@endpush
