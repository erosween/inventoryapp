@extends('layout.mobile_layout')

@section('title', 'Edit Penjualan')

@section('content')
@php
    $outletName = $sales->first()->nama_outlet ?? $id_outlet;
    $totalQty = $sales->sum('qty');
@endphp

<div class="reveal">
    <form action="{{ route('mobile.update', [$id_outlet, $tgl]) }}" method="POST" id="edit-sales-form">
        @csrf
        @method('PUT')

        <!-- Client & Visit Details -->
        <div class="glass-card mb-4 p-0 border-0 shadow-sm overflow-hidden" style="background: white; border-radius: 24px;">
            <div class="d-flex align-items-stretch">
                <div style="width: 6px; background: var(--primary); opacity: 0.8;"></div>
                <div class="p-4 w-100">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <h6 class="fw-800 mb-0 text-dark" style="font-size: 0.75rem; letter-spacing: 1px;">CLIENT & VISIT DETAILS</h6>
                        <div class="text-muted fw-bold" style="font-size: 0.65rem;">{{ \Carbon\Carbon::parse($tgl)->format('d M Y') }}</div>
                    </div>

                    <div class="row align-items-center mb-2">
                        <div class="col-4">
                            <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Client ID</label>
                        </div>
                        <div class="col-8">
                            <div class="bg-light rounded-3 px-3 py-2 fw-bold text-dark" style="font-size: 0.85rem;">{{ $id_outlet }}</div>
                        </div>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-4">
                            <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Nama Outlet</label>
                        </div>
                        <div class="col-8">
                            <div class="bg-light rounded-3 px-3 py-2 fw-bold text-dark" style="font-size: 0.85rem;">{{ $outletName }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Activity Section -->
        <div class="d-flex justify-content-between align-items-center mb-2 px-2">
            <h6 class="fw-800 mb-0 text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">SALES ACTIVITY</h6>
            <button type="button" class="btn btn-sm rounded-pill fw-800 px-3 add-product-trigger" style="background: rgba(16, 185, 129, 0.1); color: #10b981; font-size: 0.65rem;">
                <i class="fas fa-plus me-1"></i> ADD ITEM
            </button>
        </div>

        <div id="product-container">
            @foreach($sales as $index => $sale)
                <div class="glass-card mb-3 p-0 border-0 shadow-sm overflow-hidden product-item" style="background: white; border-radius: 24px;">
                    <div class="d-flex align-items-stretch">
                        <div style="width: 6px; background: #10b981; opacity: 0.8;"></div>
                        <div class="p-4 w-100">
                            @if($index > 0)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-800 mb-0 text-muted" style="font-size: 0.65rem; letter-spacing: 1px;">ITEM #{{ $index + 1 }}</h6>
                                <button type="button" class="btn btn-link text-danger p-0 remove-item-btn"><i class="fas fa-times-circle fs-5"></i></button>
                            </div>
                            @endif

                            <div class="row align-items-center mb-3">
                                <div class="col-4">
                                    <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Product</label>
                                </div>
                                <div class="col-8">
                                    <div class="product-trigger border-0 bg-light rounded-3 px-3 py-2 d-flex justify-content-between align-items-center" style="cursor: pointer;">
                                        <span class="product-placeholder fw-bold text-dark" style="font-size: 0.8rem;">{{ $sale->denom }}</span>
                                        <input type="hidden" name="products[{{ $index }}][id]" value="{{ $sale->id }}">
                                        <input type="hidden" name="products[{{ $index }}][iddenom]" value="{{ $sale->iddenom }}" class="iddenom-input">
                                        <i class="fas fa-search text-muted" style="font-size: 0.7rem;"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="row align-items-center">
                                <div class="col-4">
                                    <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Quantity</label>
                                </div>
                                <div class="col-8">
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="products[{{ $index }}][qty]" class="form-control form-control-sm border-0 bg-light rounded-start-3 px-3 py-2 fw-bold text-dark edit-qty" value="{{ $sale->qty }}" min="1" required style="font-size: 0.9rem;">
                                        <span class="input-group-text border-0 bg-light text-muted fw-bold rounded-end-3" style="font-size: 0.65rem;">PCS</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Notes Section -->
        <div class="glass-card mb-4 p-0 border-0 shadow-sm overflow-hidden" style="background: white; border-radius: 24px;">
            <div class="d-flex align-items-stretch">
                <div style="width: 6px; background: #f59e0b; opacity: 0.8;"></div>
                <div class="p-4 w-100">
                    <h6 class="fw-800 mb-3 text-dark" style="font-size: 0.75rem; letter-spacing: 1px;">NOTES & FOLLOW-UP</h6>
                    <textarea name="keterangan" class="form-control border-0 bg-light rounded-3 px-3 py-3 fw-bold text-dark" rows="3" placeholder="Activity summary..." style="font-size: 0.85rem; resize: none;">{{ $sales->first()->keterangan ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <div id="edit-setoran-summary" class="glass-card mb-4 p-0 border-0 shadow-sm overflow-hidden" style="background: white; border-radius: 24px;">
            <div class="d-flex align-items-stretch">
                <div style="width: 6px; background: var(--primary); opacity: 0.8;"></div>
                <div class="p-4 w-100">
                    <div>
                        <h6 class="fw-800 mb-1 text-dark" style="font-size: 0.75rem; letter-spacing: 1px;">RINGKASAN UPDATE</h6>
                        <div class="text-muted fw-bold" style="font-size: 0.65rem;"><span id="edit-total-items">{{ $sales->count() }}</span> item • <span id="edit-total-pcs">{{ number_format($totalQty) }}</span> pcs</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky-bottom pb-4 pt-2 px-1" style="background: var(--bg-body); z-index: 10;">
            <div class="d-flex gap-2">
                <a href="{{ route('mobile.history') }}" class="btn btn-light rounded-pill fw-800 py-3 px-4" style="border: 1px solid #e2e8f0; font-size: 0.85rem;">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary flex-grow-1 rounded-pill py-3 fw-800 shadow" style="font-size: 0.9rem; background: var(--primary); border: none;">
                    Update Data <i class="fas fa-save ms-1"></i>
                </button>
            </div>
        </div>
    </form>
</div>

@push('modals')
<!-- Modal Product Picker -->
<div class="modal fade" id="productPickerModal" tabindex="-1" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-dialog-scrollable mx-auto" style="max-width: 440px; position: absolute; bottom: 0; left: 0; right: 0; margin: 0;">
        <div class="modal-content border-0" style="border-radius: 30px 30px 0 0; height: 80vh;">
            <div class="d-flex justify-content-center pt-3">
                <div style="width: 45px; height: 6px; background: #e2e8f0; border-radius: 10px;"></div>
            </div>
            <div class="modal-header border-0 p-4 pb-2">
                <h5 class="fw-800 mb-0">Pilih Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="sticky-top bg-white px-4 pt-2 pb-3 shadow-sm" style="z-index: 10;">
                    <div class="position-relative">
                        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        <input type="text" id="product-search-modal" class="form-control ps-5 border-0 bg-light rounded-pill" placeholder="Ketik nama produk..." style="height: 45px;">
                    </div>
                </div>
                <div class="list-group list-group-flush px-4 pt-3 pb-5">
                    @foreach($denoms as $d)
                        <button type="button" class="list-group-item list-group-item-action border-0 py-3 px-0 d-flex align-items-center select-product-btn" 
                                data-iddenom="{{ $d->iddenom }}" 
                                data-denom="{{ $d->denom }}"
                                data-stock="{{ $d->stock_qty }}">
                            <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3 text-primary">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-800 text-dark" style="font-size: 0.85rem; line-height: 1.2;">{{ $d->denom }}</div>
                                <div class="small text-muted fw-bold mt-1" style="font-size: 0.65rem;">Stok: {{ number_format($d->stock_qty) }}</div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endpush
@endsection

@push('scripts')
<script>
    let productIndex = {{ $sales->count() }};
    let activeTrigger = null;
    const stockMap = {!! json_encode($denoms->pluck('stock_qty', 'iddenom')) !!};

    function recalcEditSummary() {
        let totalPcs = 0;
        let itemCount = 0;

        $('.product-item').each(function() {
            const qty = parseInt($(this).find('.edit-qty').val()) || 0;
            totalPcs += qty;
            itemCount++;
        });

        $('#edit-total-items').text(itemCount);
        $('#edit-total-pcs').text(totalPcs.toLocaleString('id-ID'));
    }

    // Open Picker
    $(document).on('click', '.product-trigger', function() {
        activeTrigger = $(this);
        $('#productPickerModal').modal('show');
        $('#product-search-modal').val('').trigger('input');
    });

    // Search in Modal
    $('#product-search-modal').on('input', function() {
        let val = $(this).val().toLowerCase();
        $('.select-product-btn').each(function() {
            let name = $(this).data('denom').toLowerCase();
            $(this).toggle(name.includes(val));
        });
    });

    // Select Product
    $(document).on('click', '.select-product-btn', function() {
        const iddenom = $(this).data('iddenom');
        const denom = $(this).data('denom');

        activeTrigger.find('.product-placeholder').text(denom);
        activeTrigger.find('.iddenom-input').val(iddenom);

        $('#productPickerModal').modal('hide');
        recalcEditSummary();
    });

    // Add Item
    $('.add-product-trigger').on('click', function() {
        const html = `
            <div class="glass-card mb-3 p-0 border-0 shadow-sm overflow-hidden product-item animate__animated animate__fadeInUp" style="background: white; border-radius: 24px;">
                <div class="d-flex align-items-stretch">
                    <div style="width: 6px; background: #10b981; opacity: 0.8;"></div>
                    <div class="p-4 w-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-800 mb-0 text-muted" style="font-size: 0.65rem; letter-spacing: 1px;">ITEM BARU</h6>
                            <button type="button" class="btn btn-link text-danger p-0 remove-item-btn"><i class="fas fa-times-circle fs-5"></i></button>
                        </div>

                        <div class="row align-items-center mb-3">
                            <div class="col-4">
                                <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Product</label>
                            </div>
                            <div class="col-8">
                                <div class="product-trigger border-0 bg-light rounded-3 px-3 py-2 d-flex justify-content-between align-items-center" style="cursor: pointer;">
                                    <span class="product-placeholder fw-bold text-muted" style="font-size: 0.8rem;">Select item...</span>
                                    <input type="hidden" name="products[${productIndex}][iddenom]" class="iddenom-input" required>
                                    <i class="fas fa-search text-muted" style="font-size: 0.7rem;"></i>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-center">
                            <div class="col-4">
                                <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Quantity</label>
                            </div>
                            <div class="col-8">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="products[${productIndex}][qty]" class="form-control form-control-sm border-0 bg-light rounded-start-3 px-3 py-2 fw-bold text-dark edit-qty" value="1" min="1" required style="font-size: 0.9rem;">
                                    <span class="input-group-text border-0 bg-light text-muted fw-bold rounded-end-3" style="font-size: 0.65rem;">PCS</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#product-container').append(html);
        productIndex++;
        recalcEditSummary();
    });

    $(document).on('click', '.remove-item-btn', function() {
        $(this).closest('.product-item').remove();
        recalcEditSummary();
    });

    $(document).on('input', '.edit-qty', recalcEditSummary);

    $('#edit-sales-form').on('submit', function(event) {
        const requested = {};
        $('.product-item').each(function() {
            const id = $(this).find('.iddenom-input').val();
            const qty = parseInt($(this).find('.edit-qty').val()) || 0;
            if (id) requested[id] = (requested[id] || 0) + qty;
        });

        const invalid = Object.entries(requested).find(([id, qty]) => qty > Number(stockMap[id] || 0));
        if (invalid) {
            event.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Stok Tidak Cukup',
                text: `Total qty ${invalid[1]} melebihi stok tersedia ${Number(stockMap[invalid[0]] || 0)}.`,
                confirmButtonColor: 'var(--primary)'
            });
        }
    });
</script>
@endpush
