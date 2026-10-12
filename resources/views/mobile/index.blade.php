@extends('layout.mobile_layout')

@section('title', 'Dashboard')

@section('content')
@php
    $progress = $pjp_list->count() > 0 ? ($pjp_list->where('is_visited', true)->count() / $pjp_list->count()) * 100 : 0;
@endphp

<div class="px-4 pt-4 pb-5 mobile-home-hero" style="margin: 0 -16px; position: relative;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center min-w-0">
            <img src="/assets/img/surya-logo.png" alt="SURYA" class="me-3" style="width: 46px; height: 46px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.55); box-shadow: 0 12px 28px rgba(0,0,0,0.18); background: #fff; object-fit: cover;">
            <div class="min-w-0">
                <p class="text-white-50 mb-1 fw-800" style="font-size: 0.66rem; letter-spacing: .08em;">SURYA · SALES REPORTING</p>
                <h6 class="text-white fw-900 mb-0 text-truncate" style="font-size: 1rem;">{{ session('mobile_sf_name') }}</h6>
            </div>
        </div>
        <div class="text-end">
            <div class="text-white-50 fw-bold" style="font-size: 0.62rem;">{{ date('d M Y') }}</div>
            <div class="text-white fw-900" style="font-size: 0.95rem;">{{ session('idtap') }}</div>
        </div>
    </div>

    <div class="hero-command">
        <div>
            <div class="text-white-50 fw-900 mb-2" style="font-size: 0.66rem;">TARGET KUNJUNGAN HARI INI</div>
            <div class="text-white fw-900" style="font-size: 2.15rem; line-height: 1;">{{ round($progress) }}%</div>
            <div class="text-white-50 fw-bold mt-1" style="font-size: 0.72rem;">{{ $pjp_list->where('is_visited', true)->count() }} dari {{ $pjp_list->count() }} outlet PJP selesai</div>
        </div>
        <a href="{{ $activeVisit ? route('mobile.visit.menu', ['id_outlet' => $activeVisit->id_outlet]) : route('mobile.form') }}" class="hero-action" aria-label="{{ $activeVisit ? 'Lanjutkan visit' : 'Input penjualan' }}">
            <i class="fas fa-plus"></i>
        </a>
    </div>
</div>

<div class="reveal position-relative" style="margin-top: -62px; z-index: 10;">
    <div class="mobile-kpi-grid mb-3">
        <div class="mobile-kpi-card accent-red">
            <span>Sales Hari Ini</span>
            <strong>{{ number_format($today_sales) }}</strong>
            <small>PCS terjual</small>
        </div>
        <div class="mobile-kpi-card accent-green">
            <span>Setoran Hari Ini</span>
            <strong>Rp {{ number_format($today_setoran, 0, ',', '.') }}</strong>
            <small>Estimasi</small>
        </div>
        <div class="mobile-kpi-card">
            <span>Sales Bulan Ini</span>
            <strong>{{ number_format($month_sales) }}</strong>
            <small>PCS terjual</small>
        </div>
        <div class="mobile-kpi-card">
            <span>Setoran Bulan Ini</span>
            <strong>Rp {{ number_format($month_setoran, 0, ',', '.') }}</strong>
            <small>Estimasi</small>
        </div>
    </div>

    @if($activeVisit)
        <a href="{{ route('mobile.visit.menu', ['id_outlet' => $activeVisit->id_outlet]) }}" class="active-visit-banner d-flex align-items-center gap-3 mb-3 text-decoration-none">
            <span class="active-visit-icon"><i class="fas fa-location-dot"></i></span>
            <span class="flex-grow-1 min-w-0">
                <small class="d-block fw-900 text-uppercase">Sedang Berlangsung</small>
                <strong class="d-block text-truncate">{{ $activeVisit->outlet_name }}</strong>
                <span class="d-block">ID: {{ $activeVisit->id_outlet }} · Selesaikan input penjualan</span>
            </span>
            <i class="fas fa-chevron-right"></i>
        </a>
    @endif

    <!-- PJP Section Header -->
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h5 class="fw-800 mb-0 text-dark" style="letter-spacing: -0.5px;">Journey Plan</h5>
            <p class="small text-muted mb-0 fw-bold" style="font-size: 0.65rem;">{{ date('d M Y') }}</p>
        </div>
        <div class="d-flex align-items-center">
            @if($activeVisit)
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fw-bold me-3" style="font-size: 0.65rem;" disabled>
                    <i class="fas fa-lock me-1"></i> TERKUNCI
                </button>
            @else
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fw-bold me-3"
                        style="font-size: 0.65rem;"
                        data-bs-toggle="modal" data-bs-target="#searchOutletModal">
                    <i class="fas fa-plus me-1"></i> TAMBAH
                </button>
            @endif
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
            @php($isActiveOutlet = $activeVisit && strtoupper($activeVisit->id_outlet) === strtoupper($pjp->id_outlet))
            <div class="glass-card mb-2 p-0 border-0 overflow-hidden reveal shadow-sm" style="background: white; border-radius: 24px;">
                <div class="d-flex align-items-stretch">
                    <!-- Vertical Status Strip -->
                    <div style="width: 5px; background: {{ $pjp->is_visited ? '#10b981' : ($isActiveOutlet ? '#f59e0b' : '#e2e8f0') }}; transition: all 0.4s;"></div>
                    
                    <div class="p-3 w-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center" data-lat="{{ $pjp->latitude }}" data-lng="{{ $pjp->longitude }}">
                                <div class="p-2 rounded-4 me-3 d-flex align-items-center justify-content-center"
                                     style="width: 40px; height: 40px; background: {{ $pjp->is_visited ? 'rgba(236,32,40,0.10)' : ($isActiveOutlet ? 'rgba(245,158,11,0.12)' : '#f8fafc') }}; color: {{ $pjp->is_visited ? 'var(--primary)' : ($isActiveOutlet ? '#d97706' : 'var(--text-muted)') }};">
                                    <i class="fas fa-store" style="font-size: 0.9rem;"></i>
                                </div>
                                <div>
                                    <h6 class="fw-800 mb-0 text-dark" style="font-size: 0.85rem;">{{ $pjp->nama_outlet }}</h6>
                                    <div class="d-flex align-items-center mt-1">
                                        <span class="text-muted fw-bold" style="font-size: 0.6rem;">ID: {{ $pjp->id_outlet }}</span>
                                        <span class="distance-label ms-2 text-muted fw-bold" style="font-size: 0.58rem;">Mengukur jarak...</span>
                                        @if($pjp->is_visited)
                                            <span class="ms-2 badge bg-success bg-opacity-10 text-success rounded-pill" style="font-size: 0.5rem; padding: 3px 8px;">VERIFIED</span>
                                        @elseif($isActiveOutlet)
                                            <span class="ms-2 badge bg-warning bg-opacity-10 text-warning rounded-pill" style="font-size: 0.5rem; padding: 3px 8px;">SEDANG BERLANGSUNG</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            @if($pjp->is_visited)
                                <div class="text-success text-center">
                                    <i class="fas fa-check-circle fs-4 mb-1"></i>
                                    <div class="fw-800" style="font-size: 0.5rem;">DONE</div>
                                </div>
                            @elseif($isActiveOutlet)
                                <a href="{{ route('mobile.visit.menu', ['id_outlet' => $pjp->id_outlet]) }}" class="btn btn-sm rounded-pill px-3 fw-800"
                                   style="font-size: 0.62rem; background: #fff7ed; color: #d97706; border: 1px solid #fed7aa;">
                                    LANJUTKAN <i class="fas fa-chevron-right ms-1" style="font-size: 0.5rem;"></i>
                                </a>
                            @elseif($activeVisit)
                                <button type="button" class="btn btn-sm rounded-pill px-3 fw-800" disabled
                                        style="font-size: 0.62rem; background: #f8fafc; color: #94a3b8; border: 1px solid #e2e8f0; opacity: 1;">
                                    <i class="fas fa-lock me-1"></i> TERKUNCI
                                </button>
                            @else
                                <a href="#" data-outlet="{{ $pjp->id_outlet }}" class="open-visit-menu btn btn-sm rounded-pill px-4 fw-800"
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

    <script>
    document.querySelectorAll('.open-visit-menu').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const target = this;
            const go = function(distance) {
                let url = '{{ route('mobile.visit.menu') }}?id_outlet=' + encodeURIComponent(target.dataset.outlet);
                if (distance !== null) url += '&distance=' + Math.round(distance);
                window.location.href = url;
            };
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    const card = target.closest('.glass-card'), point = card ? card.querySelector('[data-lat][data-lng]') : null;
                    go(point && point.dataset.lat && point.dataset.lng ? distanceMeters(pos.coords.latitude, pos.coords.longitude, parseFloat(point.dataset.lat), parseFloat(point.dataset.lng)) : null);
                }, function() { go(null); }, {enableHighAccuracy:true, timeout:10000, maximumAge:0});
            } else go(null);
        });
    });
    </script>

    <script>
    function distanceMeters(a,b,c,d){const r=6371000,x=(c-a)*Math.PI/180,y=(d-b)*Math.PI/180,z=Math.sin(x/2)**2+Math.cos(a*Math.PI/180)*Math.cos(c*Math.PI/180)*Math.sin(y/2)**2;return 2*r*Math.asin(Math.min(1,Math.sqrt(z)));}
    if (navigator.geolocation) navigator.geolocation.getCurrentPosition(function(pos){
        document.querySelectorAll('[data-lat][data-lng]').forEach(function(card){const lat=parseFloat(card.dataset.lat),lng=parseFloat(card.dataset.lng);if(!lat||!lng)return;const dist=Math.round(distanceMeters(pos.coords.latitude,pos.coords.longitude,lat,lng));const el=card.querySelector('.distance-label');if(el){el.textContent=dist.toLocaleString('id-ID')+' m dari posisi';el.classList.remove('text-muted','text-danger','text-success');el.classList.add(dist>10?'text-danger':'text-success');}});
    }, function(){document.querySelectorAll('.distance-label').forEach(e=>e.textContent='GPS tidak aktif');},{enableHighAccuracy:true,timeout:10000,maximumAge:0});
    </script>

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

@push('styles')
<style>
    .mobile-home-hero {
        min-height: 258px;
    }

    .hero-command {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 18px;
        position: relative;
        z-index: 1;
    }

    .hero-action {
        width: 62px;
        height: 62px;
        border-radius: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: #111827;
        background: #fff;
        text-decoration: none;
        box-shadow: 0 16px 34px rgba(0, 0, 0, 0.18);
    }

    .hero-action i {
        font-size: 1.25rem;
    }

    .mobile-kpi-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .mobile-kpi-card {
        min-height: 98px;
        border-radius: 24px;
        padding: 12px 15px;
        background: rgba(255, 255, 255, 0.97);
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.07);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        overflow: hidden;
    }

    .mobile-kpi-card span,
    .mobile-kpi-card small {
        color: #64748b;
        font-size: 0.61rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .mobile-kpi-card strong {
        color: #111827;
        font-size: clamp(1rem, 4.5vw, 1.58rem);
        line-height: 1.05;
        font-weight: 900;
        overflow-wrap: anywhere;
    }

    .mobile-kpi-card.accent-red strong {
        color: var(--primary);
    }

    .mobile-kpi-card.accent-green strong {
        color: #059669;
    }

    .distance-label.text-danger { color: #dc2626 !important; }
    .distance-label.text-success { color: #059669 !important; }

    .active-visit-banner {
        padding: 14px 16px;
        border: 1px solid #fed7aa;
        border-radius: 20px;
        background: linear-gradient(135deg, #fff7ed, #fffbeb);
        color: #9a3412;
        box-shadow: 0 12px 24px rgba(245, 158, 11, 0.10);
    }

    .active-visit-banner small {
        color: #d97706;
        font-size: 0.58rem;
        letter-spacing: 0.08em;
    }

    .active-visit-banner strong {
        color: #111827;
        font-size: 0.88rem;
    }

    .active-visit-banner span span {
        color: #78716c;
        font-size: 0.62rem;
        font-weight: 700;
    }

    .active-visit-icon {
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f59e0b;
        color: #fff;
    }

</style>
@endpush

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
