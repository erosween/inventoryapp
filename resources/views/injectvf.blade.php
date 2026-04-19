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
                                <a href="#" id="btnExport" class="btn btn-success btn-sm mr-1">
                                    <i class="fas fa-file-export"></i>
                                </a>

                                <div class="dropdown">
                                    <button class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown">
                                        + INJECT SEGEL
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="form/forminject">SEGEL</a>
                                        {{-- <a class="dropdown-item" href="form/forminjectroamax">SEGEL ROAMAX</a> --}}
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

            let start = moment().startOf('month');
            let end = moment().endOf('month');

            $('#daterange').val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));

            let table = $('#inject-table').DataTable({
                processing: true,
                serverSide: true,
                order: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ route('inject.data') }}",
                    data: function (d) {
                        d.daterange = $('#daterange').val();
                    }
                },
                columns: [{
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
                ]
            });

            $('#daterange').daterangepicker({
                startDate: start,
                endDate: end,
                autoUpdateInput: true,
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
            }, function (start, end) {

                let range = start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD');
                $('#daterange').val(range);

                table.ajax.reload();

                $('#btnExport').attr(
                    'href',
                    '/exportinject?daterange=' + encodeURIComponent(range)
                );
            });


            $('#btnExport').attr(
                'href', '/exportinject?daterange=' + encodeURIComponent($('#daterange').val())
            );

        });
    </script>
@endpush