@extends('layout.mobile_layout')

@section('title', 'Edit Penjualan')

@section('content')
<div class="reveal">
    <div class="glass-card mb-3 p-3 border-0 shadow-sm" style="background: white; border-radius: 20px;">
        <div class="d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3 text-primary">
                <i class="fas fa-store" style="font-size: 0.8rem;"></i>
            </div>
            <div>
                <h6 class="fw-800 mb-0 text-dark" style="font-size: 0.9rem;">{{ $sales->first()->nama_outlet ?? $id_outlet }}</h6>
                <p class="small text-muted mb-0 fw-bold" style="font-size: 0.65rem;">{{ \Carbon\Carbon::parse($tgl)->format('d M Y') }} • {{ $sales->count() }} Produk</p>
            </div>
        </div>
    </div>

    <form action="{{ route('mobile.update', [$id_outlet, $tgl]) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="product-edit-list">
            @foreach($sales as $index => $sale)
            <div class="glass-card p-3 mb-2 border-0 shadow-sm" style="background: white; border-radius: 20px;">
                <div class="row align-items-center g-2">
                    <div class="col-7">
                        <div class="fw-800 text-dark" style="font-size: 0.8rem; line-height: 1.2;">{{ $sale->denom }}</div>
                        <div class="text-muted fw-bold mt-1" style="font-size: 0.55rem;">ITEM #{{ $index + 1 }}</div>
                    </div>
                    <div class="col-5">
                        <input type="hidden" name="products[{{ $index }}][id]" value="{{ $sale->id }}">
                        <div class="input-group input-group-sm">
                            <input type="number" name="products[{{ $index }}][qty]" class="glass-input py-2 px-3" value="{{ $sale->qty }}" min="1" required style="border-radius: 12px 0 0 12px; font-size: 0.85rem;">
                            <span class="input-group-text border-0 bg-light text-muted fw-bold" style="border-radius: 0 12px 12px 0; font-size: 0.65rem;">PCS</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="stats-row mt-4 mb-5" style="gap: 10px;">
            <a href="{{ route('mobile.history') }}" class="btn btn-light rounded-4 fw-800 py-3" style="border: 1px solid #e2e8f0; flex: 1; font-size: 0.85rem;">
                BATAL
            </a>
            <button type="submit" class="btn-premium" style="flex: 2; font-size: 0.85rem; padding: 12px;">
                UPDATE DATA <i class="fas fa-save ms-1"></i>
            </button>
        </div>
    </form>
</div>
@endsection
