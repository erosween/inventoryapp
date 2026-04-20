@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                <div class="card shadow-sm">

                    {{-- HEADER --}}
                    {{-- HEADER --}}
                    <div class="card-header py-3 px-4 bg-white border-bottom shadow-sm">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <h4 class="card-title mb-0 font-weight-bold text-indigo">Barang Retur SF (Masuk TAP)</h4>
                                <div class="text-muted small">Monitoring pengembalian barang dari Sales Force ke TAP</div>
                            </div>

                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                {{-- Date Range --}}
                                <div class="position-relative">
                                    <input type="text" id="daterange" class="form-control form-control-sm pe-4 shadow-none border"
                                        style="min-width: 250px; background: #f8f9fa;" placeholder="Pilih tanggal" autocomplete="off">
                                    <i class="fas fa-calendar-alt position-absolute"
                                        style="right:10px; top:50%; transform:translateY(-50%); color:#6c757d"></i>
                                </div>

                                {{-- Bulk Delete --}}
                                @if (auth()->user()->username === 'admin_cluster')
                                    <button id="btnBulkDelete" class="btn btn-danger btn-sm d-none shadow-sm">
                                        <i class="fas fa-trash-alt"></i> Hapus Terpilih
                                    </button>
                                @endif

                                <div class="btn-group shadow-sm">
                                    <a href="#" id="btnExport" class="btn btn-success btn-sm border-0" title="Export Excel">
                                        <i class="fas fa-file-export"></i>
                                    </a>
                                    <a href="{{ url('form/form-retursf') }}" class="btn btn-primary btn-sm border-0 font-weight-bold">
                                        <i class="fas fa-plus-circle mr-1"></i> Tambah
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="retursf-table" class="table table-sm table-striped table-hover w-100">
                                <thead>
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
                                        <th>SF</th>
                                        <th>SN</th>
                                        <th>Ket VF</th>
                                        <th>Ket Lain</th>
                                        <th width="80">Action</th>
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
        $(function() {
            let selectedIds = [];

            /* ==========================
               DEFAULT RANGE = BULAN INI
            ========================== */
            let start = moment().startOf('month');
            let end = moment().endOf('month');

            const $daterange = $('#daterange');

            $daterange.val(
                start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD')
            );

            /* ==========================
               DATATABLE
            ========================== */
            let table = $('#retursf-table').DataTable({
                processing: true,
                serverSide: true,
                order: [
                    [{{ auth()->user()->username === 'admin_cluster' ? 1 : 0 }}, 'desc']
                ],
                ajax: {
                    url: "{{ route('retursf.data') }}",
                    data: function(d) {
                        d.daterange = $daterange.val();
                    }
                },
                columns: [
                    @if (auth()->user()->username === 'admin_cluster')
                        {
                            data: 'idretur',
                            name: 'idretur',
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
                        data: 'namasf'
                    },
                    {
                        data: 'sn'
                    },
                    {
                        data: 'ketvf'
                    },
                    {
                        data: 'tambahket'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            /* ==========================
               DATE RANGE PICKER (FULL)
            ========================== */
            $daterange.daterangepicker({
                startDate: start,
                endDate: end,
                autoUpdateInput: false,
                alwaysShowCalendars: false,
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
                let range =
                    picker.startDate.format('YYYY-MM-DD') +
                    ' - ' +
                    picker.endDate.format('YYYY-MM-DD');

                $(this).val(range);
                table.ajax.reload(null, false);

                $('#btnExport').attr(
                    'href',
                    '/exportretursf?daterange=' + encodeURIComponent(range)
                );
            });

            /* EXPORT DEFAULT */
            $('#btnExport').attr(
                'href',
                '/exportretursf?daterange=' + encodeURIComponent($daterange.val())
            );

            @if (auth()->user()->username === 'admin_cluster')
                /* ==========================
                GLOBAL SELECTION LOGIC
                ========================== */
                table.on('draw', function() {
                    updateCheckAllState();
                });

                $(document).on('click', '#checkAll', function() {
                    $('.row-checkbox').each(function() {
                        let id = $(this).val().toString();
                        if ($('#checkAll').is(':checked')) {
                            if (!selectedIds.includes(id)) selectedIds.push(id);
                            $(this).prop('checked', true);
                        } else {
                            selectedIds = selectedIds.filter(item => item !== id);
                            $(this).prop('checked', false);
                        }
                    });
                    toggleBulkBtn();
                });

                $(document).on('click', '.row-checkbox', function() {
                    let id = $(this).val().toString();
                    if ($(this).is(':checked')) {
                        if (!selectedIds.includes(id)) selectedIds.push(id);
                    } else {
                        selectedIds = selectedIds.filter(item => item !== id);
                    }
                    updateCheckAllState();
                    toggleBulkBtn();
                });

                function updateCheckAllState() {
                    let allCheckedOnPage = true;
                    let checkboxes = $('.row-checkbox');
                    if (checkboxes.length === 0) {
                        allCheckedOnPage = false;
                    } else {
                        checkboxes.each(function() {
                            if (!$(this).is(':checked')) allCheckedOnPage = false;
                        });
                    }
                    $('#checkAll').prop('checked', allCheckedOnPage);
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
                        text: `Anda akan menghapus ${selectedIds.length} data terpilih (mencakup data lintas halaman/tanggal). Stok akan dikembalikan otomatis!`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Ya, Hapus Semua!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ route('retursf.bulk-delete') }}",
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
