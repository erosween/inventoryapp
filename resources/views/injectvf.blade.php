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

                <div class="card shadow-sm">

                    {{-- HEADER --}}
                    <div class="card-header py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h4 class="card-title mb-0">Inject Voucher Fisik</h4>

                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="position-relative  mr-1">
                                    <input type="text" id="daterange" class="form-control form-control-sm pe-4"
                                        style="min-width: 260px" placeholder="Pilih tanggal" autocomplete="off">
                                    <i class="fas fa-calendar-alt position-absolute"
                                        style="right:10px; top:50%; transform:translateY(-50%); color:#6c757d"></i>
                                </div>

                                {{-- Bulk Delete --}}
                                @if (auth()->user()->username === 'admin_cluster')
                                    <button id="btnBulkDelete" class="btn btn-danger btn-sm d-none mr-1">
                                        <i class="fas fa-trash-alt"></i> Hapus Terpilih
                                    </button>
                                @endif

                                <a href="#" id="btnExport" class="btn btn-success btn-sm mr-1">
                                    <i class="fas fa-file-export"></i>
                                </a>

                                <div class="dropdown">
                                    <button class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">
                                        + INJECT SEGEL
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="form/forminject">SEGEL</a>
                                        <a class="dropdown-item" href="form/forminjectbyu">SEGEL BYU</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    {{-- BODY --}}
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="inject-table" class="table table-sm table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        @if (auth()->user()->username === 'admin_cluster')
                                            <th width="30" class="text-center no-export">
                                                <input type="checkbox" id="checkAll" class="cursor-pointer">
                                            </th>
                                        @endif
                                        <th>Tanggal</th>
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