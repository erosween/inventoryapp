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
                <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-3">
                    <div class="d-flex align-items-center">
                        <div class="theme-icon-box p-2 rounded-3 me-3">
                            <i class="fas fa-calendar-alt" style="font-size: 1rem;"></i>
                        </div>
                        <div>
                            <h6 class="fw-800 mb-0 text-dark" style="font-size: 0.9rem;">Filter Tanggal</h6>
                        </div>
                    </div>
                    <div style="width: 100%;">
                        <div class="position-relative">
                            <input type="text" id="filter_date" name="filter_date" class="form-control form-control-sm border-0 bg-light rounded-pill px-4 py-2 fw-bold text-dark text-center shadow-sm" 
                                   value="{{ $filter_date ?? '' }}" 
                                   placeholder="Pilih Tanggal..."
                                   style="font-size: 0.75rem; width: min(100%, 220px); cursor: pointer;">
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
        <!-- TAB PENJUALAN: summary per date, detail per outlet on demand -->
        <div class="tab-pane fade show active" id="pills-penjualan" role="tabpanel">
            <div class="mb-4">
                @forelse($history as $day)
                    @php
                        $dateKey = \Carbon\Carbon::parse($day->tgl)->toDateString();
                        $dayDenoms = $historyDenoms->get($dateKey, collect());
                        $pvTotal = $dayDenoms->where('category', 'PV')->sum('total_qty');
                        $saTotal = $dayDenoms->where('category', 'SA')->sum('total_qty');
                    @endphp
                    <button type="button"
                            class="glass-card history-date-card w-100 p-0 mb-3 reveal shadow-sm border-0 text-start"
                            data-tgl="{{ $dateKey }}"
                            aria-label="Lihat detail penjualan {{ \Carbon\Carbon::parse($day->tgl)->format('d M Y') }}">
                        <div class="history-date-head">
                            <div class="d-flex align-items-center gap-3">
                                <div class="theme-icon-box history-date-icon"><i class="fas fa-calendar-day"></i></div>
                                <div>
                                    <div class="history-date-label">{{ \Carbon\Carbon::parse($day->tgl)->locale('id')->translatedFormat('l') }}</div>
                                    <h6 class="fw-800 mb-0 text-dark">{{ \Carbon\Carbon::parse($day->tgl)->format('d M Y') }}</h6>
                                    <small class="text-muted fw-bold">{{ number_format($day->outlet_count) }} outlet</small>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right history-date-chevron"></i>
                        </div>

                        <div class="history-totals">
                            <div><span>PV</span><strong>{{ number_format($pvTotal) }}</strong><small>PCS</small></div>
                            <div><span>SA</span><strong>{{ number_format($saTotal) }}</strong><small>PCS</small></div>
                        </div>

                        <div class="history-denom-list">
                            @forelse($dayDenoms as $denom)
                                <div class="history-denom-row">
                                    <span class="history-denom-category {{ strtolower($denom->category) }}">{{ $denom->category }}</span>
                                    <span class="history-denom-name">{{ $denom->denom }}</span>
                                    <strong>{{ number_format($denom->total_qty) }} <small>pcs</small></strong>
                                </div>
                            @empty
                                <div class="history-empty-summary">Belum ada transaksi selesai pada tanggal ini.</div>
                            @endforelse
                        </div>

                        <div class="history-date-foot">
                            <span>
                                @if($day->draft_count > 0)
                                    <i class="fas fa-circle-info text-warning me-1"></i>Ada penjualan yang belum disubmit
                                @elseif($day->pending_count > 0)
                                    <i class="fas fa-hourglass-half text-warning me-1"></i>Menunggu validasi
                                @else
                                    <i class="fas fa-check-circle text-success me-1"></i>Transaksi diproses
                                @endif
                            </span>
                            <strong>Detail outlet</strong>
                        </div>
                    </button>
                @empty
                    <div class="glass-card text-center py-5 opacity-50 border-0" style="background: transparent; box-shadow: none;">
                        <i class="fas fa-history fs-1 mb-3"></i>
                        <p class="small fw-bold">Belum ada riwayat penjualan.</p>
                    </div>
                @endforelse

                @foreach($keluarsf_history->groupBy(fn ($row) => \Carbon\Carbon::parse($row->tgl)->toDateString()) as $webDate => $webRows)
                    <div class="glass-card history-web-card p-3 mb-3 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="history-date-label">INPUT ADMIN</span>
                                <div class="fw-800">{{ \Carbon\Carbon::parse($webDate)->format('d M Y') }}</div>
                            </div>
                            <i class="fas fa-desktop text-info"></i>
                        </div>
                        @foreach($webRows->groupBy('denom') as $denomName => $rows)
                            <div class="history-denom-row px-0">
                                <span class="history-denom-name">{{ $denomName }}</span>
                                <strong>{{ number_format($rows->sum('qty')) }} <small>pcs</small></strong>
                            </div>
                        @endforeach
                    </div>
                @endforeach
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
    .history-date-card {
        overflow: hidden;
        background: #fff;
        border-radius: 20px;
        color: var(--text-main);
    }
    .history-date-card:active {
        transform: scale(.99);
    }
    .history-date-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px;
    }
    .history-date-icon {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        flex: 0 0 42px;
    }
    .history-date-label {
        color: var(--primary);
        font-size: .58rem;
        font-weight: 900;
        letter-spacing: .1em;
    }
    .history-date-chevron {
        color: #94a3b8;
        font-size: .82rem;
    }
    .history-totals {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1px;
        margin: 0 16px 12px;
        overflow: hidden;
        border: 1px solid #eef2f7;
        border-radius: 14px;
        background: #eef2f7;
    }
    .history-totals > div {
        display: flex;
        align-items: baseline;
        gap: 5px;
        padding: 10px 12px;
        background: #f8fafc;
    }
    .history-totals span {
        color: var(--text-muted);
        font-size: .58rem;
        font-weight: 900;
    }
    .history-totals strong {
        color: var(--primary);
        font-size: 1rem;
    }
    .history-totals small,
    .history-denom-row small {
        color: #94a3b8;
        font-size: .54rem;
        font-weight: 800;
    }
    .history-denom-list {
        padding: 0 16px 10px;
    }
    .history-denom-row {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 8px;
        min-height: 34px;
        padding: 6px 0;
        border-top: 1px solid #f1f5f9;
        font-size: .72rem;
    }
    .history-denom-row:first-child {
        border-top: 0;
    }
    .history-denom-category {
        min-width: 28px;
        padding: 3px 5px;
        border-radius: 7px;
        color: #fff;
        font-size: .5rem;
        font-weight: 900;
        text-align: center;
    }
    .history-denom-category.pv { background: var(--primary); }
    .history-denom-category.sa { background: #2563eb; }
    .history-denom-name {
        overflow: hidden;
        color: #334155;
        font-weight: 750;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .history-empty-summary {
        padding: 8px 0 12px;
        color: #94a3b8;
        font-size: .68rem;
        font-weight: 700;
    }
    .history-date-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 16px;
        background: #f8fafc;
        color: #64748b;
        font-size: .6rem;
    }
    .history-date-foot strong {
        color: var(--primary);
        white-space: nowrap;
    }
    .history-web-card {
        border-radius: 18px;
        background: #fff;
    }
    .history-outlet-card {
        padding: 14px;
        border: 1px solid #eef2f7;
        border-radius: 16px;
        background: #fff;
    }
    .history-outlet-card + .history-outlet-card {
        margin-top: 12px;
    }
    .history-outlet-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        padding: 8px 0;
        border-top: 1px solid #f1f5f9;
        font-size: .72rem;
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

@push('modals')
<!-- Detail Visit Modal -->
<div class="modal fade" id="historyDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable mx-auto" style="max-width: 440px; position: absolute; bottom: 0; left: 0; right: 0; margin: 0;">
        <div class="modal-content border-0" style="border-radius: 30px 30px 0 0; max-height: 85vh;">
            <div class="d-flex justify-content-center pt-3">
                <div style="width: 45px; height: 6px; background: #e2e8f0; border-radius: 10px;"></div>
            </div>
            <div class="modal-header border-0 p-4 pb-2">
                <div>
                    <h5 class="fw-800 mb-0">Detail Penjualan</h5>
                    <p class="small text-muted mb-0" id="detail-date">-</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <div id="detail-items-container">
                    <!-- Loaded via AJAX -->
                    <div class="text-center py-5">
                        <i class="fas fa-circle-notch fa-spin fs-2 text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
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
            disableMobile: "true",
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2 || selectedDates.length === 0) {
                    document.getElementById('filterForm').submit();
                }
            }
        });

        const escapeHtml = (value) => $('<div>').text(value ?? '').html();
        const statusMeta = {
            approved: ['success', 'DISETUJUI'],
            rejected: ['danger', 'DITOLAK'],
            pending: ['warning', 'MENUNGGU'],
            draft_wa: ['info', 'BELUM DISUBMIT']
        };

        $('.history-date-card').on('click', function() {
            const tgl = String($(this).data('tgl'));
            $('#detail-items-container').html('<div class="text-center py-5"><i class="fas fa-circle-notch fa-spin fs-2 text-primary opacity-50"></i></div>');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('historyDetailModal')).show();

            $.get("{{ route('mobile.history.details') }}", { tgl: tgl }, function(res) {
                if (res.success) {
                    $('#detail-date').text(res.date);
                    const html = res.outlets.map(outlet => {
                        const items = outlet.items.map(item => {
                            const [color, label] = statusMeta[item.status] || ['secondary', String(item.status).toUpperCase()];
                            return `
                                <div class="history-outlet-item">
                                    <div><strong>${escapeHtml(item.produk)}</strong><div><span class="badge bg-${color} bg-opacity-10 text-${color} rounded-pill mt-1">${escapeHtml(label)}</span></div></div>
                                    <strong class="text-primary">${Number(item.qty).toLocaleString('id-ID')} <small>PCS</small></strong>
                                </div>`;
                        }).join('');
                        const receiptButton = outlet.receipt_url
                            ? `<a href="${escapeHtml(outlet.receipt_url)}" class="btn btn-danger btn-sm rounded-pill w-100 mt-2 fw-bold"><i class="fas fa-check-circle me-1"></i>Lanjutkan Konfirmasi</a>`
                            : '';
                        const consignmentNumbers = (outlet.consignment_numbers || []).map(number =>
                            `<span class="badge rounded-pill text-bg-light border text-dark me-1 mb-1">${escapeHtml(number)}</span>`
                        ).join('');
                        const consignmentBlock = Number(outlet.consignment_qty || 0) > 0
                            ? `<div class="history-outlet-item align-items-start">
                                    <div><strong>Konsinyasi SA</strong><div class="mt-2">${consignmentNumbers}</div></div>
                                    <strong class="text-danger">${Number(outlet.consignment_qty).toLocaleString('id-ID')} <small>NOMOR</small></strong>
                               </div>`
                            : '';

                        return `
                            <section class="history-outlet-card">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div><div class="fw-800 text-dark">${escapeHtml(outlet.nama_outlet)}</div><small class="text-muted fw-bold">ID: ${escapeHtml(outlet.id_outlet)}</small></div>
                                    <div class="text-end"><strong class="text-primary">${Number(outlet.total_qty).toLocaleString('id-ID')}</strong><small class="d-block text-muted">PCS</small></div>
                                </div>
                                ${items}
                                ${consignmentBlock}
                                ${receiptButton}
                            </section>`;
                    }).join('');
                    $('#detail-items-container').html(html);
                } else {
                    $('#detail-items-container').html('<div class="alert alert-danger">Gagal mengambil data.</div>');
                }
            }).fail(function() {
                $('#detail-items-container').html('<div class="alert alert-danger">Detail penjualan tidak dapat dimuat. Silakan coba lagi.</div>');
            });
        });
    });
</script>
@endpush
