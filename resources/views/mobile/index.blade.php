@extends('layout.mobile_layout')

@section('title', 'Dashboard')

@section('content')
<!-- Red Header Section (Telkomsel Style) -->
<div class="px-4 pt-4 pb-5" style="background: linear-gradient(135deg, var(--primary) 0%, #B00B11 100%); margin: 0 -16px; position: relative;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center">
            <img src="/assets/img/MSP5.png" alt="Avatar" style="width: 42px; height: 42px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.2);" class="me-3">
            <div>
                <p class="text-white text-opacity-75 mb-0 fw-bold" style="font-size: 0.65rem;">Hai, Sales Force</p>
                <h6 class="text-white fw-800 mb-0" style="font-size: 1rem;">{{ session('mobile_sf_name') }}</h6>
            </div>
        </div>
        <div class="text-end">
            <i class="fas fa-bell text-white fs-5 position-relative">
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle"></span>
            </i>
        </div>
    </div>
    <div class="text-white pb-3">
        <p class="mb-0 fw-bold" style="font-size: 0.65rem; opacity: 0.8;">ID TAP</p>
        <h5 class="fw-800 mb-0">{{ session('idtap') }}</h5>
    </div>
</div>

<div class="reveal position-relative" style="margin-top: -45px; z-index: 10;">
    <!-- Quick Performance Overlapping Card -->
    <div class="glass-card mb-4 p-4 border-0 shadow-sm" style="background: white; border-radius: 24px;">
        <div class="row g-3 text-center">
            <div class="col-6">
                <div class="p-3 rounded-4" style="background: linear-gradient(135deg, rgba(236,32,40,0.05), rgba(236,32,40,0.12));">
                    <div class="text-muted fw-bold mb-1" style="font-size: 0.6rem;"><i class="fas fa-calendar-day me-1" style="color: var(--primary);"></i> SALES HARI INI</div>
                    <h3 class="fw-800 mb-0" style="color: var(--primary);">{{ number_format($today_sales) }}</h3>
                    <div class="small fw-bold text-muted" style="font-size: 0.55rem;">PCS TERJUAL</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded-4" style="background: linear-gradient(135deg, rgba(16,185,129,0.05), rgba(16,185,129,0.12));">
                    <div class="text-muted fw-bold mb-1" style="font-size: 0.6rem;"><i class="fas fa-wallet me-1 text-success"></i> SETORAN HARI INI</div>
                    <h4 class="fw-800 mb-0 text-success" style="font-size: 1.05rem;">Rp {{ number_format($today_setoran, 0, ',', '.') }}</h4>
                    <div class="small fw-bold text-muted" style="font-size: 0.55rem;">ESTIMASI</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded-4" style="background: rgba(0,0,0,0.02);">
                    <div class="text-muted fw-bold mb-1" style="font-size: 0.6rem;"><i class="fas fa-calendar-alt me-1" style="color: var(--accent);"></i> SALES BULAN INI</div>
                    <h3 class="fw-800 mb-0" style="color: var(--text-main);">{{ number_format($month_sales) }}</h3>
                    <div class="small fw-bold text-muted" style="font-size: 0.55rem;">PCS TERJUAL</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded-4" style="background: rgba(0,0,0,0.02);">
                    <div class="text-muted fw-bold mb-1" style="font-size: 0.6rem;"><i class="fas fa-money-bill-wave me-1 text-success"></i> SETORAN BULAN INI</div>
                    <h4 class="fw-800 mb-0" style="color: var(--text-main); font-size: 1.05rem;">Rp {{ number_format($month_setoran, 0, ',', '.') }}</h4>
                    <div class="small fw-bold text-muted" style="font-size: 0.55rem;">ESTIMASI</div>
                </div>
            </div>
        </div>
    </div>

    <!-- PJP Section Header -->
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h5 class="fw-800 mb-0 text-dark" style="letter-spacing: -0.5px;">Journey Plan</h5>
            <p class="small text-muted mb-0 fw-bold" style="font-size: 0.65rem;">{{ date('d M Y') }}</p>
        </div>
        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fw-bold me-3" 
                    style="font-size: 0.65rem;"
                    data-bs-toggle="modal" data-bs-target="#searchOutletModal">
                <i class="fas fa-plus me-1"></i> TAMBAH
            </button>
            @php
                $progress = $pjp_list->count() > 0 ? ($pjp_list->where('is_visited', true)->count() / $pjp_list->count()) * 100 : 0;
            @endphp
            <div class="text-end">
                <span class="text-primary fw-800" style="font-size: 0.9rem;">{{ round($progress) }}%</span>
            </div>
        </div>
    </div>
    <!-- Modern Progress Bar -->
    <div class="progress rounded-pill mb-3" style="height: 6px; background: #ffe4e6;">
        <div class="progress-bar rounded-pill" role="progressbar" 
             style="width: {{ $progress }}%; background: var(--primary);" 
             aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>

    <div class="pjp-list mb-4">
        @forelse($pjp_list as $pjp)
            <div class="glass-card mb-2 p-0 border-0 overflow-hidden reveal shadow-sm" style="background: white; border-radius: 24px;">
                <div class="d-flex align-items-stretch">
                    <!-- Vertical Status Strip -->
                    <div style="width: 5px; background: {{ $pjp->is_visited ? '#10b981' : '#e2e8f0' }}; transition: all 0.4s;"></div>
                    
                    <div class="p-3 w-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-2 rounded-4 me-3 text-muted d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-store" style="font-size: 0.9rem;"></i>
                                </div>
                                <div>
                                    <h6 class="fw-800 mb-0 text-dark" style="font-size: 0.85rem;">{{ $pjp->nama_outlet }}</h6>
                                    <div class="d-flex align-items-center mt-1">
                                        <span class="text-muted fw-bold" style="font-size: 0.6rem;">ID: {{ $pjp->id_outlet }}</span>
                                        @if($pjp->is_visited)
                                            <span class="ms-2 badge bg-success bg-opacity-10 text-success rounded-pill" style="font-size: 0.5rem; padding: 3px 8px;">VERIFIED</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            @if($pjp->is_visited)
                                <div class="text-success text-center">
                                    <i class="fas fa-check-circle fs-4 mb-1"></i>
                                    <div class="fw-800" style="font-size: 0.5rem;">DONE</div>
                                </div>
                            @else
                                <a href="{{ route('mobile.form', ['id_outlet' => $pjp->id_outlet]) }}" class="btn btn-sm rounded-pill px-4 fw-800" 
                                   style="font-size: 0.65rem; background: #f8fafc; color: var(--primary); border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: all 0.2s;">
                                    VISIT <i class="fas fa-chevron-right ms-1" style="font-size: 0.5rem;"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="glass-card text-center py-5 opacity-50 border-0" style="background: transparent; box-shadow: none;">
                <i class="fas fa-clipboard-list fs-1 mb-3"></i>
                <p class="small fw-bold">Tidak ada jadwal kunjungan hari ini.</p>
            </div>
        @endforelse
    </div>

    <!-- Non-PJP Section -->
    @if(isset($non_pjp_list) && $non_pjp_list->count() > 0)
    <div class="d-flex justify-content-between align-items-end mb-3 mt-2">
        <div>
            <h6 class="fw-800 mb-0 text-dark" style="letter-spacing: -0.5px;">Kunjungan Luar PJP</h6>
            <p class="small text-muted mb-0 fw-bold" style="font-size: 0.65rem;">Ekstra Visit Hari Ini</p>
        </div>
        <div class="text-end">
            <span class="badge bg-warning text-dark fw-bold rounded-pill" style="font-size: 0.6rem;">+{{ $non_pjp_list->count() }} OUTLET</span>
        </div>
    </div>

    <div class="non-pjp-list mb-4">
        @foreach($non_pjp_list as $np)
            <div class="glass-card mb-2 p-0 border-0 overflow-hidden reveal shadow-sm" style="background: white; border-radius: 24px;">
                <div class="d-flex align-items-stretch">
                    <!-- Vertical Status Strip (Orange for extra visit) -->
                    <div style="width: 5px; background: #f59e0b;"></div>
                    
                    <div class="p-3 w-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 p-2 rounded-4 me-3 text-warning d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-store" style="font-size: 0.9rem;"></i>
                                </div>
                                <div>
                                    <h6 class="fw-800 mb-0 text-dark" style="font-size: 0.85rem;">{{ $np->nama_outlet }}</h6>
                                    <div class="d-flex align-items-center mt-1">
                                        <span class="text-muted fw-bold" style="font-size: 0.6rem;">ID: {{ $np->id_outlet }}</span>
                                        <span class="ms-2 badge bg-warning bg-opacity-10 text-warning rounded-pill" style="font-size: 0.5rem; padding: 3px 8px;">LUAR PJP</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-warning text-center">
                                <i class="fas fa-check-circle fs-4 mb-1"></i>
                                <div class="fw-800" style="font-size: 0.5rem;">DONE</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Activity Section -->
    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
        <h6 class="fw-bold mb-0" style="color: var(--text-main); letter-spacing: 0.5px;">AKTIVITAS TERAKHIR</h6>
        <a href="{{ route('mobile.history') }}" class="small text-primary fw-bold text-decoration-none">LIHAT SEMUA</a>
    </div>

    <!-- Activity List - Clean Style -->
    @forelse($latest as $item)
                            <div class="activity-item d-flex align-items-center mb-3">
                                <div class="activity-icon bg-primary bg-opacity-10 text-primary me-3">
                                    <i class="fas fa-shopping-cart small"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $item->denom }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Outlet: {{ $item->id_outlet }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-800 text-primary" style="font-size: 0.9rem;">{{ number_format($item->qty) }} pcs</div>
                                    @if($item->harga_jual > 0)
                                        <div class="text-success fw-bold" style="font-size: 0.6rem;">Rp {{ number_format($item->qty * $item->harga_jual, 0, ',', '.') }}</div>
                                    @else
                                        <div class="text-muted" style="font-size: 0.65rem;">{{ $item->created_at->diffForHumans() }}</div>
                                    @endif
                                </div>
                            </div>
                        @empty
    <div class="glass-card text-center py-5 opacity-50">
        <p class="small mb-0">Belum ada aktivitas.</p>
    </div>
    @endforelse
</div>
</div>
@endsection

@push('modals')
<!-- Modal Cari Outlet -->
<div class="modal fade" id="searchOutletModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width: 440px;">
        <div class="modal-content border-0" style="border-radius: 30px; overflow: hidden; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);">
            <div class="modal-header border-0 p-4 pb-2">
                <h5 class="fw-800 mb-0">Cari Outlet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <p class="small text-muted fw-bold mb-3">Input data jualan di luar jadwal PJP hari ini.</p>
                <div class="glass-input-group mb-4">
                    <input type="text" id="outletSearchInput" class="glass-input" placeholder="Ketik nama atau ID outlet..." autocomplete="off">
                </div>
                
                <div id="outletSearchResults" class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                    <div class="text-center py-4 opacity-50">
                        <i class="fas fa-search fs-2 mb-2"></i>
                        <p class="small fw-bold">Cari untuk memunculkan hasil</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let searchTimeout = null;

    $('#outletSearchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        let q = $(this).val();
        
        if (q.length < 3) {
            $('#outletSearchResults').html('<div class="text-center py-4 opacity-50"><i class="fas fa-search fs-2 mb-2"></i><p class="small fw-bold">Ketik minimal 3 karakter</p></div>');
            return;
        }

        searchTimeout = setTimeout(function() {
            $('#outletSearchResults').html('<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>');
            
            $.ajax({
                url: "{{ route('mobile.search-outlet') }}",
                data: { q: q },
                success: function(data) {
                    let html = '';
                    if (data.length > 0) {
                        data.forEach(function(outlet) {
                            html += `
                                <a href="{{ route('mobile.form') }}?id_outlet=${outlet.id_outlet}" class="list-group-item list-group-item-action border-0 py-3 px-0 d-flex align-items-center">
                                    <div class="bg-light p-2 rounded-3 me-3 text-muted">
                                        <i class="fas fa-store"></i>
                                    </div>
                                    <div>
                                        <div class="fw-800 text-dark" style="font-size: 0.85rem;">${outlet.nama_outlet}</div>
                                        <div class="small text-muted fw-bold" style="font-size: 0.65rem;">ID: ${outlet.id_outlet}</div>
                                    </div>
                                    <i class="fas fa-chevron-right ms-auto text-muted opacity-50" style="font-size: 0.7rem;"></i>
                                </a>
                            `;
                        });
                    } else {
                        html = '<div class="text-center py-4 opacity-50"><p class="small fw-bold">Outlet tidak ditemukan</p></div>';
                    }
                    $('#outletSearchResults').html(html);
                }
            });
        }, 500);
    });

    // Reset search when modal closed
    $('#searchOutletModal').on('hidden.bs.modal', function () {
        $('#outletSearchInput').val('');
        $('#outletSearchResults').html('<div class="text-center py-4 opacity-50"><i class="fas fa-search fs-2 mb-2"></i><p class="small fw-bold">Cari untuk memunculkan hasil</p></div>');
    });
});
</script>
@endpush
