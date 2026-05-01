@extends('layout.layout')

@section('content')
<div class="main-panel">
    <div class="content">
        <div class="page-inner">
            <div class="page-header">
                <h4 class="page-title text-indigo fw-bold">Validasi Penjualan SF</h4>
                <ul class="breadcrumbs">
                    <li class="nav-home"><a href="{{ url('home') }}"><i class="flaticon-home text-indigo"></i></a></li>
                    <li class="separator"><i class="flaticon-right-arrow"></i></li>
                    <li class="nav-item">Sales Force</li>
                    <li class="separator"><i class="flaticon-right-arrow"></i></li>
                    <li class="nav-item">Approval Mobile</li>
                </ul>
            </div>

            <!-- Global Stats Banner -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-round shadow-sm border-0 mb-4 text-white overflow-hidden" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);">
                        <div class="card-body py-4 position-relative">
                            <div class="row align-items-center">
                                <div class="col-md-12">
                                    <h6 class="text-white-50 fw-bold mb-1">TOTAL PENDING HARI INI</h6>
                                    <h1 class="fw-900 mb-0" style="font-size: 2.5rem;">Rp {{ number_format($total_pending_amount, 0, ',', '.') }}</h1>
                                    <div class="mt-2 text-white-50 small">
                                        <i class="fas fa-chart-line me-1"></i> Rekapitulasi setoran tertunda yang menunggu verifikasi
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card premium-card card-round border-0 shadow-sm">
                        <div class="card-header border-bottom py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light-indigo p-2 rounded-3 me-3 text-indigo">
                                        <i class="fas fa-users-cog"></i>
                                    </div>
                                    <h5 class="fw-bold mb-0 me-4">Summary Per Sales Force</h5>
                                    <!-- Status Tabs -->
                                    <ul class="nav nav-pills nav-indigo nav-pills-no-bd" id="status-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="tab-pending" data-status="pending" href="#">Menunggu</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="tab-approved" data-status="approved" href="#">Disetujui</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="tab-rejected" data-status="rejected" href="#">Ditolak</a>
                                        </li>
                                    </ul>
                                </div>
                                <div>
                                    <i class="fas fa-sync-alt fa-spin me-1 d-none text-muted" id="table-loader"></i> 
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="approval-table" class="table table-hover table-indigo w-100">
                                    <thead>
                                        <tr>
                                            <th>NAMA SALES FORCE</th>
                                            <th>TAP</th>
                                            <th>JENIS PAKET</th>
                                            <th class="text-right">TOTAL QTY</th>
                                            <th class="text-right">TOTAL RUPIAH</th>
                                            <th class="text-center">STATUS</th>
                                            <th class="text-center" style="width: 150px">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal (Detail Breakdown) -->
<div class="modal fade" id="reviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content card-round border-0 shadow-lg">
            <div class="modal-header bg-indigo py-3">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-search me-2"></i> Tinjauan Detail Setoran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <!-- Modal Header Summary -->
                <div class="bg-light-indigo p-4 border-bottom position-relative">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h4 class="fw-900 text-indigo mb-0" id="modal-sf-name">-</h4>
                            <p class="text-muted small mb-0"><span id="modal-jenis-label" class="badge badge-info me-2"></span> <span id="modal-tap-label" class="badge badge-secondary"></span></p>
                        </div>
                        <div class="col-md-5 text-end">
                            <h2 class="fw-900 text-indigo mb-0" id="modal-total-amount">Rp 0</h2>
                            <span class="badge badge-indigo" id="modal-item-count">0 Item</span>
                            <div id="modal-status-badge" class="mt-2"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Items Table -->
                <div class="p-3">
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-sm table-hover" id="modal-items-table">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th style="width: 40px">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="check-all-items">
                                            <label class="custom-control-label" for="check-all-items"></label>
                                        </div>
                                    </th>
                                    <th>Outlet</th>
                                    <th>Produk</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center" style="width: 90px">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Comparison Helper (Only for Pending) -->
                <div id="modal-comparison-section" class="px-4 py-3 bg-light border-top">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold">INPUT TOTAL DARI BON FISIK</label>
                            <input type="number" id="real-bon-input" class="form-control" placeholder="Masukkan total di bon fisik...">
                        </div>
                        <div class="col-md-6 text-end">
                            <div id="selisih-wrapper" class="d-none">
                                <label class="text-muted small fw-bold">SELISIH DENGAN SISTEM</label>
                                <h3 id="selisih-val" class="fw-900 mb-0">Rp 0</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-3 d-none" id="modal-footer-actions">
                <div class="me-auto small text-muted fw-bold ps-3" id="selected-count-label">0 item dipilih</div>
                <button type="button" class="btn btn-outline-danger btn-round px-4 fw-bold me-2" id="modal-reject-btn">TOLAK TERPILIH</button>
                <button type="button" class="btn btn-success btn-round px-5 fw-bold" id="modal-approve-btn">APPROVE TERPILIH</button>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light-indigo { background-color: rgba(78, 115, 223, 0.05); }
    .text-indigo { color: #4e73df; }
    .bg-indigo { background-color: #4e73df; }
    
    .nav-pills.nav-indigo .nav-link.active {
        background-color: #4e73df;
        color: #fff;
        font-weight: 800;
        box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3);
    }

    /* Smooth Modal Animations */
    .modal.fade .modal-dialog {
        transform: translate(0, -30px);
        transition: transform 0.3s ease-out, opacity 0.3s ease-out;
    }
    .modal.show .modal-dialog {
        transform: translate(0, 0);
    }

    .card-round { border-radius: 15px !important; overflow: hidden; }
    .sticky-top { top: 0; z-index: 10; }
    .btn-indigo { background-color: #4e73df; color: #fff; }
    .btn-indigo:hover { background-color: #2e59d9; color: #fff; }

    /* Custom Checkbox Size */
    .custom-control-label::before, .custom-control-label::after { width: 1.25rem; height: 1.25rem; }

    /* Custom Table Loader Overlay */
    .table-responsive { position: relative; }
</style>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        var selectedIdsf = '';
        var selectedStatus = 'pending';
        var currentModalSf = '';
        var currentModalIdtap = '';
        var currentModalJenis = '';
        var currentModalTotal = 0;

        var table = $('#approval-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.mobile-approval.data') }}",
                data: function(d) {
                    d.idsf = selectedIdsf;
                    d.status = selectedStatus;
                },
                beforeSend: function() { $('#table-loader').removeClass('d-none'); },
                complete: function() { $('#table-loader').addClass('d-none'); }
            },
            columns: [
                { data: 'namasf', name: 's.namasf' },
                { data: 'idtap', name: 'm.idtap', className: 'text-center' },
                { data: 'jenis_paket', name: 'jenis_paket', className: 'text-center fw-bold' },
                { data: 'total_qty', name: 'total_qty', className: 'text-right fw-bold' },
                { data: 'total_rupiah', name: 'total_rupiah', className: 'text-right fw-bold text-success' },
                { data: 'status', name: 'm.status', className: 'text-center' },
                { data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false }
            ],
            order: [[0, 'asc']],
            pageLength: 25
        });

        // Status Tabs
        $('#status-tabs .nav-link').on('click', function(e) {
            e.preventDefault();
            $('#status-tabs .nav-link').removeClass('active');
            $(this).addClass('active');
            selectedStatus = $(this).data('status');
            table.ajax.reload();
        });

        // Review Modal Logic
        $(document).on('click', '.tinjau-btn', function(e) {
            e.preventDefault();
            var idsf = $(this).data('idsf');
            var status = $(this).data('status');
            var idtap = $(this).data('idtap');
            var jenis = $(this).data('jenis');
            var namasf = $(this).data('namasf') || $(this).text();
            
            currentModalSf = idsf;
            currentModalIdtap = idtap;
            currentModalJenis = jenis;
            
            // Open modal immediately with loading state
            $('#modal-sf-name').text(namasf.toUpperCase());
            $('#modal-jenis-label').text(jenis);
            $('#modal-tap-label').text(idtap);
            $('#modal-total-amount').text('...');
            $('#modal-item-count').text('Memuat...');
            $('#modal-status-badge').html('<i class="fas fa-circle-notch fa-spin text-muted"></i>');
            $('#modal-items-table tbody').html('<tr><td colspan="6" class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-indigo"></i><br><small class="text-muted">Mengambil data rincian...</small></td></tr>');
            $('#modal-comparison-section, #modal-footer-actions').addClass('d-none');
            $('#check-all-items').prop('checked', false);
            $('#reviewModal').modal('show');
            
            $.get("{{ route('admin.mobile-approval.sf-details') }}", { 
                idsf: idsf, 
                status: status,
                idtap: idtap,
                jenis: jenis
            }, function(res) {
                $('#modal-sf-name').text(res.sf_name.toUpperCase());
                $('#modal-total-amount').text(res.total_amount);
                $('#modal-item-count').text(res.count + ' Item');
                
                var badgeClass = res.status == 'pending' ? 'badge-warning' : (res.status == 'approved' ? 'badge-success' : 'badge-danger');
                $('#modal-status-badge').html(`<span class="badge ${badgeClass} fw-bold px-3 py-2 animate__animated animate__fadeIn">${res.status.toUpperCase()}</span>`);

                if (res.status == 'pending') {
                    $('#modal-comparison-section, #modal-footer-actions').removeClass('d-none').addClass('animate__animated animate__fadeIn');
                }

                currentModalTotal = parseInt(res.total_amount.replace(/[^0-9]/g, ''));
                $('#real-bon-input').val('');
                $('#selisih-wrapper').addClass('d-none');

                var html = '';
                res.items.forEach(function(item) {
                    var actionBtns = res.status == 'pending' ? `
                        <div class="d-flex gap-1 justify-content-center">
                            <button type="button" class="btn btn-icon btn-round btn-success btn-xs item-approve-btn" data-id="${item.id}" title="Approve"><i class="fas fa-check"></i></button>
                            <button type="button" class="btn btn-icon btn-round btn-danger btn-xs item-reject-btn" data-id="${item.id}" title="Tolak"><i class="fas fa-times"></i></button>
                        </div>
                    ` : '-';

                    var checkbox = res.status == 'pending' ? `
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input item-checkbox" id="check-${item.id}" data-id="${item.id}">
                            <label class="custom-control-label" for="check-${item.id}"></label>
                        </div>
                    ` : '-';

                    html += `<tr class="animate__animated animate__fadeIn">
                        <td>${checkbox}</td>
                        <td><small class="text-muted d-block">${item.tgl}</small>${item.outlet}</td>
                        <td><span class="fw-bold">${item.produk}</span></td>
                        <td class="text-end fw-bold">${item.qty}</td>
                        <td class="text-end text-indigo fw-bold">${item.total}</td>
                        <td class="text-center">${actionBtns}</td>
                    </tr>`;
                });
                $('#modal-items-table tbody').html(html);
                updateSelectedCount();
            });
        });

        // Check All Items
        $('#check-all-items').on('change', function() {
            $('.item-checkbox').prop('checked', $(this).is(':checked'));
            updateSelectedCount();
        });

        $(document).on('change', '.item-checkbox', function() {
            updateSelectedCount();
            $('#check-all-items').prop('checked', $('.item-checkbox:checked').length === $('.item-checkbox').length);
        });

        function updateSelectedCount() {
            var count = $('.item-checkbox:checked').length;
            $('#selected-count-label').text(count + ' item dipilih');
            $('#modal-approve-btn, #modal-reject-btn').prop('disabled', count === 0);
        }

        // Calculation Helper
        $('#real-bon-input').on('input', function() {
            var val = $(this).val();
            if (val) {
                var diff = val - currentModalTotal;
                $('#selisih-val').text('Rp ' + new Intl.NumberFormat('id-ID').format(diff));
                $('#selisih-val').toggleClass('text-danger', diff != 0).toggleClass('text-success', diff == 0);
                $('#selisih-wrapper').removeClass('d-none');
            } else {
                $('#selisih-wrapper').addClass('d-none');
            }
        });

        // Individual Item Actions
        $(document).on('click', '.item-approve-btn', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Setujui item ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Approve'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post("{{ route('admin.mobile-approval.bulk-approve') }}", { ids: [id] }, function(r) {
                        if (r.success) {
                            $(`.item-approve-btn[data-id="${id}"]`).closest('tr').fadeOut();
                            table.ajax.reload(null, false);
                        }
                    });
                }
            });
        });

        $(document).on('click', '.item-reject-btn', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Tolak item ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Ya, Tolak'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post("{{ route('admin.mobile-approval.bulk-reject') }}", { ids: [id] }, function(r) {
                        if (r.success) {
                            $(`.item-reject-btn[data-id="${id}"]`).closest('tr').fadeOut();
                            table.ajax.reload(null, false);
                        }
                    });
                }
            });
        });

        // Bulk Approve
        $('#modal-approve-btn').on('click', function() {
            var ids = $('.item-checkbox:checked').map(function() { return $(this).data('id'); }).get();
            Swal.fire({
                title: 'Setujui ' + ids.length + ' item terpilih?',
                text: 'Stok akan terpotong untuk semua item yang disetujui.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Ya, Approve!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post("{{ route('admin.mobile-approval.bulk-approve') }}", { ids: ids }, function(r) {
                        if (r.success) {
                            $('#reviewModal').modal('hide');
                            Swal.fire('Berhasil!', r.message, 'success').then(() => { table.ajax.reload(); });
                        }
                    });
                }
            });
        });

        // Bulk Reject
        $('#modal-reject-btn').on('click', function() {
            var ids = $('.item-checkbox:checked').map(function() { return $(this).data('id'); }).get();
            Swal.fire({
                title: 'Tolak ' + ids.length + ' item terpilih?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Ya, Tolak!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post("{{ route('admin.mobile-approval.bulk-reject') }}", { ids: ids }, function(r) {
                        if (r.success) {
                            $('#reviewModal').modal('hide');
                            Swal.fire('Ditolak!', r.message, 'info').then(() => { table.ajax.reload(); });
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
