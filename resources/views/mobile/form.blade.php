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
                    
                    <div class="row align-items-center mb-3">
                        <div class="col-4">
                            <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Visit Date</label>
                        </div>
                        <div class="col-8">
                            <input type="date" name="tgl" class="form-control form-control-sm border-0 bg-light rounded-3 px-3 py-2 fw-bold text-dark" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-4">
                            <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Client ID</label>
                        </div>
                        <div class="col-8">
                            <input type="text" name="id_outlet" class="form-control form-control-sm border-0 bg-light rounded-3 px-3 py-2 fw-bold text-dark" placeholder="Search or scan..." value="{{ $pre_id_outlet ?? '' }}" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Section - Green Bento Card -->
        <div class="d-flex justify-content-between align-items-center mb-2 px-2">
            <h6 class="fw-800 mb-0 text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">SALES ACTIVITY</h6>
            <button type="button" id="add-product" class="btn btn-sm rounded-pill fw-800 px-3" style="background: rgba(16, 185, 129, 0.1); color: #10b981; font-size: 0.65rem;">
                <i class="fas fa-plus me-1"></i> ADD ITEM
            </button>
        </div>

        <div id="product-container">
            <!-- Product Row Template -->
            <div class="glass-card mb-3 p-0 border-0 shadow-sm overflow-hidden" style="background: white; border-radius: 24px;">
                <div class="d-flex align-items-stretch">
                    <div style="width: 6px; background: #10b981; opacity: 0.8;"></div>
                    <div class="p-4 w-100">
                        <div class="row align-items-center mb-3">
                            <div class="col-4">
                                <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Product</label>
                            </div>
                            <div class="col-8">
                                <div class="product-trigger border-0 bg-light rounded-3 px-3 py-2 d-flex justify-content-between align-items-center" data-row="0" style="cursor: pointer;">
                                    <span class="product-placeholder fw-bold text-muted" style="font-size: 0.8rem;">Select item...</span>
                                    <input type="hidden" name="products[0][iddenom]" class="iddenom-input" required>
                                    <i class="fas fa-search text-muted" style="font-size: 0.7rem;"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row align-items-center">
                            <div class="col-4">
                                <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Quantity</label>
                            </div>
                            <div class="col-8">
                                <input type="number" name="products[0][qty]" class="form-control form-control-sm border-0 bg-light rounded-3 px-3 py-2 fw-bold text-dark" placeholder="0" min="1" required style="font-size: 0.85rem;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes Section - Orange Bento Card -->
        <div class="glass-card mb-4 p-0 border-0 shadow-sm overflow-hidden" style="background: white; border-radius: 24px;">
            <div class="d-flex align-items-stretch">
                <div style="width: 6px; background: #f59e0b; opacity: 0.8;"></div>
                <div class="p-4 w-100">
                    <h6 class="fw-800 mb-3 text-dark" style="font-size: 0.75rem; letter-spacing: 1px;">NOTES & FOLLOW-UP</h6>
                    <textarea name="keterangan" class="form-control border-0 bg-light rounded-3 px-3 py-3 fw-bold text-dark" rows="3" placeholder="Activity summary..." style="font-size: 0.85rem; resize: none;"></textarea>
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
<!-- Premium Product Picker Modal (Bottom Sheet Style) -->
<div class="modal fade" id="productPickerModal" tabindex="-1" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-dialog-scrollable" style="margin: 0 auto; max-width: 480px; position: absolute; bottom: 0; left: 0; right: 0; width: 100%;">
        <div class="modal-content border-0" style="border-radius: 30px 30px 0 0; height: 85vh; width: 100%; box-shadow: 0 -10px 50px rgba(0,0,0,0.2);">
            <!-- Handle bar for better UX -->
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
                <!-- Search Bar Sticky - Fixed with solid background -->
                <div class="sticky-top bg-white px-4 pt-2 pb-3 shadow-sm" style="z-index: 10;">
                    <div class="position-relative">
                        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        <input type="text" id="product-search-modal" class="glass-input ps-5" placeholder="Ketik nama produk..." style="background: #f1f5f9; font-size: 1rem; border-radius: 20px; border: 1px solid #e2e8f0;">
                    </div>
                </div>
                
                <div class="product-list-wrapper px-4 pt-3 pb-5">
                    <div class="list-group list-group-flush" id="modal-product-list">
                        @foreach($denoms as $denom)
                        <button type="button" class="list-group-item list-group-item-action border-0 mb-3 rounded-4 py-3 px-4 d-flex align-items-center select-product-btn" 
                                style="background: #ffffff; border: 1px solid #f1f5f9 !important; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: all 0.2s;"
                                data-id="{{ $denom->iddenom }}" 
                                data-name="{{ $denom->denom }}">
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
</style>
@endpush

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Ambil Koordinat GPS
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                $('#latitude').val(position.coords.latitude);
                $('#longitude').val(position.coords.longitude);
            }, function(error) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Akses Lokasi Diperlukan',
                    text: 'Mohon izinkan akses lokasi (GPS) pada browser Anda agar kunjungan divalidasi oleh sistem.',
                    confirmButtonColor: 'var(--primary)'
                });
            }, {
                enableHighAccuracy: true
            });
        }

        let productCount = 1;
        let activeRow = null;

        // Open Picker
        $(document).on('click', '.product-trigger', function() {
            activeRow = $(this);
            $('#productPickerModal').modal('show');
            $('#product-search-modal').val('').trigger('keyup').focus();
        });

        // Search in Modal - Enhanced for Mobile
        $('#product-search-modal').on('input', function() {
            let val = $(this).val().toLowerCase().trim();
            let matches = 0;
            
            $('.select-product-btn').each(function() {
                let name = $(this).data('name') ? $(this).data('name').toString().toLowerCase() : $(this).text().toLowerCase();
                
                if (name.includes(val)) {
                    $(this).attr('style', 'display: flex !important; background: #ffffff; border: 1px solid #f1f5f9 !important; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: all 0.2s;');
                    matches++;
                } else {
                    $(this).attr('style', 'display: none !important;');
                }
            });

            // Show/Hide no results message
            if (matches === 0) {
                if ($('#no-results').length === 0) {
                    $('#modal-product-list').append('<div id="no-results" class="text-center py-5 opacity-50"><i class="fas fa-search fs-1 mb-3"></i><p>Produk tidak ditemukan</p></div>');
                }
            } else {
                $('#no-results').remove();
            }
        });

        // Select Product
        $(document).on('click', '.select-product-btn', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');
            
            activeRow.find('.product-placeholder').text(name).css('color', 'var(--text-main)');
            activeRow.find('.iddenom-input').val(id);
            
            $('#productPickerModal').modal('hide');
        });

        $('#add-product').click(function() {
            let newRow = `
                <div class="glass-card mb-3 p-0 border-0 shadow-sm overflow-hidden reveal" id="row-${productCount}" style="background: white; border-radius: 24px;">
                    <div class="d-flex align-items-stretch">
                        <div style="width: 6px; background: #10b981; opacity: 0.5;"></div>
                        <div class="p-4 w-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-800 mb-0 text-muted" style="font-size: 0.65rem; letter-spacing: 1px;">ITEM #${productCount + 1}</h6>
                                <button type="button" class="btn btn-link text-danger p-0 text-decoration-none small fw-bold remove-row" data-id="${productCount}">
                                    <i class="fas fa-times-circle fs-5"></i>
                                </button>
                            </div>
                            
                            <div class="row align-items-center mb-3">
                                <div class="col-4">
                                    <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Product</label>
                                </div>
                                <div class="col-8">
                                    <div class="product-trigger border-0 bg-light rounded-3 px-3 py-2 d-flex justify-content-between align-items-center" data-row="${productCount}" style="cursor: pointer;">
                                        <span class="product-placeholder fw-bold text-muted" style="font-size: 0.8rem;">Select item...</span>
                                        <input type="hidden" name="products[${productCount}][iddenom]" class="iddenom-input" required>
                                        <i class="fas fa-search text-muted" style="font-size: 0.7rem;"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row align-items-center">
                                <div class="col-4">
                                    <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Quantity</label>
                                </div>
                                <div class="col-8">
                                    <input type="number" name="products[${productCount}][qty]" class="form-control form-control-sm border-0 bg-light rounded-3 px-3 py-2 fw-bold text-dark" placeholder="0" min="1" required style="font-size: 0.85rem;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('#product-container').append(newRow);
            productCount++;
        });

        $(document).on('click', '.remove-row', function() {
            let rowId = $(this).data('id');
            $(`#row-${rowId}`).remove();
        });
    });
</script>
@endpush
