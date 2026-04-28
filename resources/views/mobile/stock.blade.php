@extends('layout.mobile_layout')

@section('title', 'Stok Saya')

@section('content')
<!-- Red Header Section -->
<div class="px-4 pt-4 pb-5" style="background: linear-gradient(135deg, var(--primary) 0%, #B00B11 100%); margin: 0 -16px; position: relative;">
    <div class="d-flex align-items-center mb-2">
        <a href="{{ route('mobile.index') }}" class="text-white me-3 text-decoration-none">
            <i class="fas fa-arrow-left fs-5"></i>
        </a>
        <h5 class="text-white fw-800 mb-0">Stok Saya</h5>
    </div>
    <p class="text-white text-opacity-75 mb-0 fw-bold ms-4 ps-2" style="font-size: 0.7rem;">Sisa inventory yang tersedia</p>
</div>

<div class="reveal position-relative" style="margin-top: -40px; z-index: 10;">
    <!-- Summary Card -->
    <div class="glass-card mb-4 p-4 text-center border-0 shadow-sm" style="background: white; border-radius: 24px;">
        <div class="row">
            <div class="col-6 border-end">
                <div class="text-muted fw-bold mb-1" style="font-size: 0.65rem;"><i class="fas fa-boxes-stacked me-1" style="color: var(--primary);"></i> TOTAL ITEM</div>
                <h3 class="fw-800 mb-0" style="color: var(--text-main);">{{ $stocks->where('stock', '>', 0)->count() }}</h3>
                <div class="small fw-bold text-muted" style="font-size: 0.6rem;">PRODUK TERSEDIA</div>
            </div>
            <div class="col-6">
                <div class="text-muted fw-bold mb-1" style="font-size: 0.65rem;"><i class="fas fa-wallet me-1 text-success"></i> TOTAL NILAI</div>
                <h4 class="fw-800 mb-0 text-success" style="font-size: 1.1rem;">Rp {{ number_format($total_value, 0, ',', '.') }}</h4>
                <div class="small fw-bold text-muted" style="font-size: 0.6rem;">ESTIMASI NILAI STOK</div>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="mb-3">
        <div class="position-relative">
            <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="font-size: 0.8rem;"></i>
            <input type="text" id="stock-search" class="form-control border-0 bg-white rounded-pill ps-5 py-2 fw-bold shadow-sm" placeholder="Cari produk..." style="font-size: 0.85rem;">
        </div>
    </div>

    <!-- Stock List -->
    @php
        $grouped = $stocks->groupBy('group_name');
    @endphp

    @foreach($grouped as $group => $items)
    <div class="stock-group mb-3" data-group="{{ $group }}">
        <div class="d-flex align-items-center mb-2 px-1">
            <h6 class="fw-800 text-muted mb-0" style="font-size: 0.65rem; letter-spacing: 1px;">{{ $group }}</h6>
            <span class="ms-2 badge bg-light text-muted rounded-pill fw-bold" style="font-size: 0.55rem;">{{ $items->count() }}</span>
        </div>

        @foreach($items as $item)
        <div class="stock-item glass-card mb-2 p-0 border-0 overflow-hidden shadow-sm" style="background: white; border-radius: 20px;" data-name="{{ strtolower($item->denom ?? '') }}">
            <div class="d-flex align-items-stretch">
                <!-- Status Strip -->
                <div style="width: 5px; background: {{ $item->stock > 10 ? '#10b981' : ($item->stock > 0 ? '#f59e0b' : '#ef4444') }};"></div>
                
                <div class="p-3 w-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="p-2 rounded-3 me-3 d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px; background: {{ $item->stock > 10 ? 'rgba(16,185,129,0.1)' : ($item->stock > 0 ? 'rgba(245,158,11,0.1)' : 'rgba(239,68,68,0.1)') }}; color: {{ $item->stock > 10 ? '#10b981' : ($item->stock > 0 ? '#f59e0b' : '#ef4444') }};">
                                <i class="fas fa-box" style="font-size: 0.85rem;"></i>
                            </div>
                            <div>
                                <h6 class="fw-800 mb-0 text-dark" style="font-size: 0.85rem;">{{ $item->denom }}</h6>
                                <div class="d-flex align-items-center mt-1 gap-2">
                                    @if($item->stock > 10)
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill" style="font-size: 0.5rem; padding: 3px 8px;">READY</span>
                                    @elseif($item->stock > 0)
                                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill" style="font-size: 0.5rem; padding: 3px 8px;">LOW</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill" style="font-size: 0.5rem; padding: 3px 8px;">EMPTY</span>
                                    @endif
                                    @if($item->harga_jual > 0)
                                        <span class="text-muted fw-bold" style="font-size: 0.55rem;">Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <div class="fw-800 {{ $item->stock > 0 ? 'text-dark' : 'text-danger' }}" style="font-size: 1.2rem;">{{ number_format($item->stock) }}</div>
                            <div class="text-muted fw-bold" style="font-size: 0.5rem;">PCS</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endforeach

    @if($stocks->isEmpty())
    <div class="text-center py-5 opacity-50">
        <i class="fas fa-box-open fs-1 mb-3"></i>
        <p class="small fw-bold">Belum ada data stok.</p>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Search filter
    document.getElementById('stock-search').addEventListener('input', function() {
        const val = this.value.toLowerCase().trim();
        document.querySelectorAll('.stock-item').forEach(function(item) {
            const name = item.getAttribute('data-name') || '';
            item.style.display = name.includes(val) ? '' : 'none';
        });
        // Hide group headers if all items hidden
        document.querySelectorAll('.stock-group').forEach(function(group) {
            const visibleItems = group.querySelectorAll('.stock-item[style=""], .stock-item:not([style])');
            group.style.display = visibleItems.length > 0 ? '' : 'none';
        });
    });
</script>
@endpush
