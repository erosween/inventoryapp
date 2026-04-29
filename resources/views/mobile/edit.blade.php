@extends('layout.mobile_layout')

@section('title', 'Edit Penjualan')

@section('content')
@php
    $outletName = $sales->first()->nama_outlet ?? $id_outlet;
    $totalQty = $sales->sum('qty');
    $grandTotal = $sales->sum(function($sale) {
        return $sale->qty * ($sale->harga_jual ?? 0);
    });
@endphp

<div class="reveal">
    <form action="{{ route('mobile.update', [$id_outlet, $tgl]) }}" method="POST" id="edit-sales-form">
        @csrf
        @method('PUT')

        <div class="glass-card mb-4 p-0 border-0 shadow-sm overflow-hidden" style="background: white; border-radius: 24px;">
            <div class="d-flex align-items-stretch">
                <div style="width: 6px; background: var(--primary); opacity: 0.8;"></div>
                <div class="p-4 w-100">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <h6 class="fw-800 mb-1 text-dark" style="font-size: 0.75rem; letter-spacing: 1px;">DETAIL KUNJUNGAN</h6>
                            <div class="text-muted fw-bold" style="font-size: 0.65rem;">{{ \Carbon\Carbon::parse($tgl)->format('d M Y') }}</div>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill fw-800 px-3 py-2" style="font-size: 0.6rem;">EDIT</span>
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

        <div class="d-flex justify-content-between align-items-center mb-2 px-2">
            <h6 class="fw-800 mb-0 text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">ITEM PENJUALAN</h6>
            <span class="badge bg-light text-muted rounded-pill fw-800 px-3 py-2" style="font-size: 0.6rem;">{{ $sales->count() }} PRODUK</span>
        </div>

        <div class="product-edit-list">
            @foreach($sales as $index => $sale)
                @php $subtotal = $sale->qty * ($sale->harga_jual ?? 0); @endphp
                <div class="glass-card mb-3 p-0 border-0 shadow-sm overflow-hidden" style="background: white; border-radius: 24px;">
                    <div class="d-flex align-items-stretch">
                        <div style="width: 6px; background: #10b981; opacity: 0.8;"></div>
                        <div class="p-4 w-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success bg-opacity-10 p-2 rounded-4 me-3 text-success d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                        <i class="fas fa-box-open" style="font-size: 0.95rem;"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-800 mb-1 text-dark" style="font-size: 0.9rem; line-height: 1.2;">{{ $sale->denom }}</h6>
                                        <div class="text-muted fw-bold" style="font-size: 0.6rem;">ITEM #{{ $index + 1 }}</div>
                                    </div>
                                </div>
                                @if($sale->harga_jual > 0)
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill" style="font-size: 0.55rem; padding: 5px 8px;">Rp {{ number_format($sale->harga_jual, 0, ',', '.') }}/pcs</span>
                                @endif
                            </div>

                            <input type="hidden" name="products[{{ $index }}][id]" value="{{ $sale->id }}">

                            <div class="row align-items-center">
                                <div class="col-4">
                                    <label class="mb-0 fw-bold text-muted" style="font-size: 0.7rem;">Quantity</label>
                                </div>
                                <div class="col-8">
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="products[{{ $index }}][qty]" class="form-control form-control-sm border-0 bg-light rounded-start-3 px-3 py-2 fw-bold text-dark edit-qty" value="{{ $sale->qty }}" min="1" required style="font-size: 0.9rem;" data-harga="{{ $sale->harga_jual ?? 0 }}">
                                        <span class="input-group-text border-0 bg-light text-muted fw-bold rounded-end-3" style="font-size: 0.65rem;">PCS</span>
                                    </div>
                                </div>
                            </div>

                            @if($sale->harga_jual > 0)
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-light">
                                    <span class="text-muted fw-bold" style="font-size: 0.65rem;">Subtotal</span>
                                    <span class="fw-800 text-success line-subtotal" style="font-size: 0.9rem;">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div id="edit-setoran-summary" class="glass-card mb-4 p-0 border-0 shadow-sm overflow-hidden" style="background: white; border-radius: 24px; {{ $grandTotal > 0 ? '' : 'display: none;' }}">
            <div class="d-flex align-items-stretch">
                <div style="width: 6px; background: var(--primary); opacity: 0.8;"></div>
                <div class="p-4 w-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-800 mb-1 text-dark" style="font-size: 0.75rem; letter-spacing: 1px;">RINGKASAN UPDATE</h6>
                            <div class="text-muted fw-bold" style="font-size: 0.65rem;"><span id="edit-total-items">{{ $sales->count() }}</span> item • <span id="edit-total-pcs">{{ number_format($totalQty) }}</span> pcs</div>
                        </div>
                        <div class="text-end">
                            <h4 class="fw-800 text-success mb-0" id="edit-total-setoran" style="font-size: 1.25rem;">Rp {{ number_format($grandTotal, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky-bottom pb-4 pt-2 px-1" style="background: linear-gradient(to top, var(--bg-body) 70%, transparent); z-index: 10;">
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
@endsection

@push('scripts')
<script>
    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

    function recalcEditSummary() {
        let total = 0;
        let totalPcs = 0;

        $('.edit-qty').each(function() {
            const qty = parseInt($(this).val()) || 0;
            const harga = parseInt($(this).data('harga')) || 0;
            const subtotal = qty * harga;

            total += subtotal;
            totalPcs += qty;
            $(this).closest('.glass-card').find('.line-subtotal').text(formatRupiah(subtotal));
        });

        $('#edit-total-pcs').text(totalPcs.toLocaleString('id-ID'));

        if (total > 0) {
            $('#edit-setoran-summary').show();
            $('#edit-total-setoran').text(formatRupiah(total));
        } else {
            $('#edit-setoran-summary').hide();
        }
    }

    $(document).on('input', '.edit-qty', recalcEditSummary);
</script>
@endpush
