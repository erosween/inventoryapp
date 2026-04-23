@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                {{-- ALERT --}}
                @if (session('success'))
                    <script>
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: '{{ session('success') }}'
                        })
                    </script>
                @endif

                @if (session('error'))
                    <script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: '{{ session('error') }}'
                        })
                    </script>
                @endif

                <div class="card premium-card">

                    {{-- HEADER --}}
                    <div class="card-header py-3 px-4 bg-white border-bottom shadow-sm">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h4 class="card-title mb-0 font-weight-bold text-indigo">
                                    <i class="fas fa-syringe mr-2"></i>Inject Voucher Fisik
                                </h4>
                                <div class="text-muted small">Monitoring data aktivasi/inject voucher fisik segel</div>
                            </div>

                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                {{-- Date Range --}}
                                <div class="position-relative">
                                    <input type="text" id="daterange" class="form-control form-control-sm pe-4 shadow-none border"
                                        style="min-width: 250px; background: #f8f9fa; border-radius: 20px;" placeholder="Pilih tanggal" autocomplete="off">
                                    <i class="fas fa-calendar-alt position-absolute"
                                        style="right:12px; top:50%; transform:translateY(-50%); color:var(--premium-indigo)"></i>
                                </div>

                                {{-- Bulk Delete --}}
                                @if (auth()->user()->username === 'admin_cluster')
                                    <button id="btnBulkDelete" class="btn btn-danger btn-sm d-none shadow-sm" style="border-radius: 20px;">
                                        <i class="fas fa-trash-alt"></i> Hapus Terpilih
                                    </button>
                                @endif

                                <div class="btn-group shadow-sm" style="border-radius: 20px;">
                                    <a href="#" id="btnExport" class="btn btn-success btn-sm border-0" title="Export Excel" style="border-radius: 20px 0 0 20px;">
                                        <i class="fas fa-file-export"></i>
                                    </a>
                                    
                                    <div class="btn-group">
                                        <button class="btn btn-primary btn-sm border-0 font-weight-bold dropdown-toggle" data-toggle="dropdown" style="border-radius: 0 20px 20px 0;">
                                            <i class="fas fa-plus-circle mr-1"></i> Tambah
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right shadow border-0">
                                            <a class="dropdown-item py-2" href="form/forminject">
                                                <i class="fas fa-barcode mr-2 text-primary"></i> SEGEL
                                            </a>
                                            <a class="dropdown-item py-2" href="form/forminjectbyu">
                                                <i class="fas fa-mobile-alt mr-2 text-info"></i> SEGEL BYU
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- BODY --}}
                    <div class="card-body px-0 py-0">
                        <div class="table-responsive">
                            <table id="inject-table" class="table table-indigo table-hover w-100 mb-0">
                                <thead>
                                    <tr>
                                        @if (auth()->user()->username === 'admin_cluster')
                                            <th width="30" class="text-center no-export sticky-col">
                                                <input type="checkbox" id="checkAll" class="cursor-pointer">
                                            </th>
                                        @endif
                                        <th class="{{ auth()->user()->username === 'admin_cluster' ? 'sticky-col-2' : 'sticky-col' }}">Tanggal</th>
                                        <th>Denom</th>
                                        <th class="text-end">Qty</th>
                                        <th>TAP</th>
                                        <th>SN</th>
                                        <th width="90">Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            let selectedIds = [];

            let start = moment().startOf('month');
            let end = moment().endOf('month');

            const $daterange = $('#daterange');

            $daterange.val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));

            let table = $('#inject-table').DataTable({
                processing: true,
                serverSide: true,
                order: [
                    [{{ auth()->user()->username === 'admin_cluster' ? 1 : 0 }}, 'desc']
                ],
                ajax: {
                    url: "{{ route('inject.data') }}",
                    data: function (d) {
                        d.daterange = $daterange.val();
                    }
                },
                columns: [
                    @if (auth()->user()->username === 'admin_cluster')
                    {
                        data: 'idinject',
                        name: 'idinject',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data) {
                            let checked = selectedIds.includes(data.toString()) ? "checked" : "";
                            return `<input type="checkbox" class="row-checkbox cursor-pointer" value="${data}" ${checked}>`;
                        }
                    },
                    @endif
                    {
                        data: 'tgl'
                    },
                    {
                        data: 'denom'
                    },
                    {
                        data: 'qty',
                        className: 'text-end'
                    },
                    {
                        data: 'idtap'
                    },
                    {
                        data: 'sn'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                drawCallback: function(settings) {
                    updateCheckAllState();
                }
            });

            $daterange.daterangepicker({
                startDate: start,
                endDate: end,
                autoUpdateInput: false,
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' - ',
                    applyLabel: 'Pilih',
                    cancelLabel: 'Batal',
                    customRangeLabel: 'Custom Range'
                },
                ranges: {
                    'Hari Ini': [moment(), moment()],
                    'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
                    '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
                    'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
                    'Bulan Lalu': [
                        moment().subtract(1, 'month').startOf('month'),
                        moment().subtract(1, 'month').endOf('month')
                    ]
                }
            });

            $daterange.on('apply.daterangepicker', function(ev, picker) {
                let range = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
                $(this).val(range);
                table.ajax.reload(null, false);

                $('#btnExport').attr(
                    'href',
                    '/exportinject?daterange=' + encodeURIComponent(range)
                );
            });


            $('#btnExport').attr(
                'href', '/exportinject?daterange=' + encodeURIComponent($daterange.val())
            );

            @if (auth()->user()->username === 'admin_cluster')
                /* ==========================
                   LOGIK SELECTION
                ========================== */
                $('#checkAll').on('click', function() {
                    const isChecked = this.checked;
                    $('.row-checkbox').each(function() {
                        const id = $(this).val();
                        $(this).prop('checked', isChecked);
                        updateSelectedIds(id, isChecked);
                    });
                    toggleBulkBtn();
                });

                $(document).on('click', '.row-checkbox', function() {
                    const id = $(this).val();
                    const isChecked = this.checked;
                    updateSelectedIds(id, isChecked);
                    updateCheckAllState();
                    toggleBulkBtn();
                });

                function updateSelectedIds(id, isChecked) {
                    id = id.toString();
                    if (isChecked) {
                        if (!selectedIds.includes(id)) selectedIds.push(id);
                    } else {
                        selectedIds = selectedIds.filter(item => item !== id);
                    }
                }

                function updateCheckAllState() {
                    const totalRows = $('.row-checkbox').length;
                    const checkedRows = $('.row-checkbox:checked').length;
                    $('#checkAll').prop('checked', totalRows > 0 && totalRows === checkedRows);
                }

                function toggleBulkBtn() {
                    const count = selectedIds.length;
                    if (count > 0) {
                        $('#btnBulkDelete').removeClass('d-none').html(
                            `<i class="fas fa-trash-alt"></i> Hapus (${count})`
                        );
                    } else {
                        $('#btnBulkDelete').addClass('d-none');
                    }
                }

                /* ==========================
                   BULK DELETE AJAX
                ========================== */
                $('#btnBulkDelete').on('click', function() {
                    if (selectedIds.length === 0) return;

                    Swal.fire({
                        title: 'Hapus Massal?',
                        text: `Anda akan menghapus ${selectedIds.length} data terpilih. Stok akan dikembalikan otomatis!`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, Hapus Semua!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ route('inject.bulk-delete') }}",
                                type: "POST",
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    ids: selectedIds
                                },
                                success: function(res) {
                                    if (res.success) {
                                        Swal.fire('Berhasil!', res.message, 'success');
                                        selectedIds = [];
                                        $('#checkAll').prop('checked', false);
                                        table.ajax.reload(null, false);
                                        toggleBulkBtn();
                                    } else {
                                        Swal.fire('Gagal!', res.message, 'error');
                                    }
                                },
                                error: function() {
                                    Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error');
                                }
                            });
                        }
                    });
                });
            @endif

        });
    </script>
@endpush