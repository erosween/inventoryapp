@extends('layout.mobile_layout')

@section('title', 'Riwayat Input')

@section('content')
<!-- Red Header Section (Telkomsel Style) -->
<div class="px-4 pt-4 pb-5" style="background: linear-gradient(135deg, var(--primary) 0%, #B00B11 100%); margin: 0 -16px; position: relative;">
    <div class="d-flex align-items-center mb-2">
        <a href="{{ route('mobile.index') }}" class="text-white me-3 text-decoration-none">
            <i class="fas fa-arrow-left fs-5"></i>
        </a>
        <h5 class="text-white fw-800 mb-0">Riwayat Kunjungan</h5>
    </div>
    <p class="text-white text-opacity-75 mb-3 fw-bold ms-4 ps-2" style="font-size: 0.7rem;">Laporan aktivitas harian Sales Force</p>
</div>

<div class="reveal position-relative" style="margin-top: -40px; z-index: 10;">
    <!-- Date Filter Overlapping Card -->
    <div class="mb-4">
        <form action="{{ route('mobile.history') }}" method="GET" id="filterForm">
            <div class="glass-card p-4 border-0 shadow-sm" style="background: white; border-radius: 20px;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="theme-icon-box p-2 rounded-3 me-3">
                            <i class="fas fa-calendar-alt" style="font-size: 1rem;"></i>
                        </div>
                        <div>
                            <h6 class="fw-800 mb-0 text-dark" style="font-size: 0.9rem;">Filter Tanggal</h6>
                        </div>
                    </div>
                    <div>
                        <div class="position-relative">
                            <input type="text" id="filter_date" name="filter_date" class="form-control form-control-sm border-0 bg-light rounded-pill px-4 py-2 fw-bold text-dark text-center shadow-sm" 
                                   value="{{ $filter_date ?? '' }}" 
                                   placeholder="Pilih Tanggal..."
                                   style="font-size: 0.75rem; width: 200px; cursor: pointer;">
                            <i class="fas fa-chevron-down position-absolute text-muted" style="right: 12px; top: 50%; transform: translateY(-50%); font-size: 0.6rem; pointer-events: none;"></i>
                        </div>
                    </div>
                </div>
                @if(isset($filter_date) && $filter_date != '')
                <div class="mt-3 text-end border-top pt-2">
                    <a href="{{ route('mobile.history') }}" class="text-danger small fw-bold text-decoration-none" style="font-size: 0.7rem;">
                        <i class="fas fa-times-circle me-1"></i> Bersihkan Filter
                    </a>
                </div>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-4 justify-content-center custom-pills" id="history-pills" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-penjualan-tab" data-bs-toggle="pill" data-bs-target="#pills-penjualan" type="button" role="tab">Riwayat Penjualan</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-terima-tab" data-bs-toggle="pill" data-bs-target="#pills-terima" type="button" role="tab">Terima Stok</button>
        </li>
    </ul>

    <div class="tab-content" id="history-pills-content">
        <!-- TAB PENJUALAN (Mobile + Web) -->
        <div class="tab-pane fade show active" id="pills-penjualan" role="tabpanel">
            <div class="mb-4">
                {{-- Penjualan Mobile --}}
                @foreach($history as $item)
                <div class="glass-card p-4 mb-3 reveal shadow-sm border-0" style="background: white; border-radius: 24px;">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center">
                            <div class="theme-icon-box p-3 rounded-4 me-3">
                                <i class="fas fa-store"></i>
                            </div>
                            <div>
                                <h6 class="fw-800 mb-1 text-dark" style="font-size: 0.95rem;">{{ $item->nama_outlet ?? 'ID: '.$item->id_outlet }}</h6>
                                <p class="small text-muted mb-0 fw-bold" style="font-size: 0.65rem;">
                                    <i class="far fa-calendar-alt me-1"></i> {{ \Carbon\Carbon::parse($item->tgl)->format('d M Y') }}
                                </p>
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('mobile.edit', [$item->id_outlet, $item->tgl]) }}" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-800" style="font-size: 0.6rem; padding: 5px 12px;">
                                <i class="fas fa-edit me-1"></i> EDIT
                            </a>
                        </div>
                    </div>
                    
                    <div class="row g-0 mt-3 pt-3 border-top border-light">
                        <div class="col-4">
                            <div class="small text-muted fw-bold" style="font-size: 0.6rem;">ITEMS</div>
                            <div class="fw-800 text-dark">{{ $item->item_count }} Produk</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="small text-muted fw-bold" style="font-size: 0.6rem;">TOTAL SALES</div>
                            <div class="fw-800" style="color: var(--primary);">{{ number_format($item->total_qty) }} <span class="small fw-normal text-muted" style="font-size: 0.65rem;">PCS</span></div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="small text-muted fw-bold" style="font-size: 0.6rem;">SETORAN</div>
                            @if($item->total_setoran > 0)
                                <div class="fw-800 text-success" style="font-size: 0.85rem;">Rp {{ number_format($item->total_setoran, 0, ',', '.') }}</div>
                            @else
                                <div class="fw-bold text-muted" style="font-size: 0.8rem;">-</div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- Penjualan Web (Keluarsf) --}}
                @foreach($keluarsf_history as $k)
                <div class="glass-card p-4 mb-3 reveal shadow-sm border-0" style="background: white; border-radius: 24px;">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 p-3 rounded-4 me-3 text-info">
                                <i class="fas fa-laptop"></i>
                            </div>
                            <div>
                                <h6 class="fw-800 mb-1 text-dark" style="font-size: 0.95rem;">Penjualan via Web Admin</h6>
                                <p class="small text-muted mb-0 fw-bold" style="font-size: 0.65rem;">
                                    <i class="far fa-calendar-alt me-1"></i> {{ \Carbon\Carbon::parse($k->tgl)->format('d M Y') }}
                                </p>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-info bg-opacity-10 text-info fw-bold rounded-pill px-3 py-2" style="font-size: 0.6rem;">Sales Web</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted fw-bold" style="font-size: 0.6rem;">DENOM / PRODUK</div>
                            <div class="fw-800 text-dark">{{ $k->denom }}</div>
                            @if($k->tambahanket)
                                <div class="small text-muted mt-1" style="font-size: 0.65rem;"><i class="fas fa-quote-left text-muted opacity-50 me-1"></i> {{ $k->tambahanket }}</div>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="small text-muted fw-bold" style="font-size: 0.6rem;">TERJUAL</div>
                            <div class="fw-800 text-info fs-5">{{ number_format($k->qty) }} <span class="small fw-normal text-muted" style="font-size: 0.65rem;">PCS</span></div>
                        </div>
                    </div>
                </div>
                @endforeach

                @if(count($history) == 0 && count($keluarsf_history) == 0)
                    <div class="glass-card text-center py-5 opacity-50 border-0" style="background: transparent; box-shadow: none;">
                        <i class="fas fa-history fs-1 mb-3"></i>
                        <p class="small fw-bold">Belum ada riwayat penjualan.</p>
                    </div>
                @endif
            </div>

            <!-- Pagination (Mobile Penjualan only) -->
            <div class="d-flex justify-content-center mb-5 mt-4">
                {{ $history->links() }}
            </div>
        </div>

        <!-- TAB TERIMA STOK (Masuksf) -->
        <div class="tab-pane fade" id="pills-terima" role="tabpanel">
            <div class="mb-5">
                @forelse($masuksf_history as $m)
                <div class="glass-card p-4 mb-3 reveal shadow-sm border-0" style="background: white; border-radius: 24px;">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 p-3 rounded-4 me-3 text-success">
                                <i class="fas fa-box-open"></i>
                            </div>
                            <div>
                                <h6 class="fw-800 mb-1 text-dark" style="font-size: 0.95rem;">Distribusi Stok</h6>
                                <p class="small text-muted mb-0 fw-bold" style="font-size: 0.65rem;">
                                    <i class="far fa-calendar-alt me-1"></i> {{ \Carbon\Carbon::parse($m->tgl)->format('d M Y') }}
                                </p>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success bg-opacity-10 text-success fw-bold rounded-pill px-3 py-2" style="font-size: 0.6rem;">Masuk</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted fw-bold" style="font-size: 0.6rem;">DENOM / PRODUK</div>
                            <div class="fw-800 text-dark">{{ $m->denom }}</div>
                            @if(isset($m->sn) && $m->sn)
                                <div class="small text-muted mt-1" style="font-size: 0.65rem;"><i class="fas fa-barcode text-muted opacity-50 me-1"></i> {{ $m->sn }}</div>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="small text-muted fw-bold" style="font-size: 0.6rem;">QTY DITERIMA</div>
                            <div class="fw-800 text-success fs-5">+{{ number_format($m->qty) }}</div>
                        </div>
                    </div>
                </div>
                @empty
                    <div class="glass-card text-center py-5 opacity-50 border-0" style="background: transparent; box-shadow: none;">
                        <i class="fas fa-box fs-1 mb-3"></i>
                        <p class="small fw-bold">Belum ada riwayat terima stok.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Flatpickr Custom Theme (Telkomsel Red) */
    .flatpickr-calendar.arrowTop:before, .flatpickr-calendar.arrowTop:after {
        border-bottom-color: var(--primary) !important;
    }
    .flatpickr-calendar.arrowBottom:before, .flatpickr-calendar.arrowBottom:after {
        border-top-color: var(--primary) !important;
    }
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay {
        background: var(--primary) !important;
        border-color: var(--primary) !important;
    }
    .flatpickr-months .flatpickr-month, .flatpickr-current-month .flatpickr-monthDropdown-months {
        background: var(--primary) !important;
        color: white !important;
        fill: white !important;
    }
    .flatpickr-current-month .numInputWrapper span.arrowUp:after {
        border-bottom-color: white !important;
    }
    .flatpickr-current-month .numInputWrapper span.arrowDown:after {
        border-top-color: white !important;
    }
    .flatpickr-months .flatpickr-prev-month, .flatpickr-months .flatpickr-next-month {
        color: white !important;
        fill: white !important;
    }
    .flatpickr-weekdays {
        background: var(--primary) !important;
    }
    span.flatpickr-weekday {
        color: rgba(255,255,255,0.8) !important;
    }

    .custom-pills .nav-link {
        border-radius: 20px;
        color: var(--text-muted);
        font-weight: 700;
        font-size: 0.75rem;
        padding: 8px 20px;
        margin: 0 4px;
        background: rgba(0,0,0,0.04);
        transition: all 0.3s ease;
    }
    .custom-pills .nav-link.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 4px 12px rgba(236, 32, 40, 0.3);
    }
    .theme-icon-box {
        background-color: rgba(236, 32, 40, 0.1) !important;
        color: var(--primary) !important;
    }
    .pagination {
        gap: 5px;
    }
    .page-link {
        border-radius: 8px;
        color: var(--primary);
        border: none;
        box-shadow: var(--shadow);
    }
    .page-item.active .page-link {
        background-color: var(--primary);
        border-color: var(--primary);
    }
</style>
@endpush

@push('scripts')
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        flatpickr("#filter_date", {
            mode: "range",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y",
            disableMobile: "true", /* Force custom UI on mobile for consistent premium look */
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2 || selectedDates.length === 0) {
                    document.getElementById('filterForm').submit();
                }
            }
        });
    });
</script>
@endpush
