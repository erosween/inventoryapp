@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                <div class="card">
                    <div class="card-header py-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                            <h4 class="card-title mb-0">Barang Masuk SF</h4>

                            <div class="d-flex align-items-center gap-2 flex-wrap">

                                {{-- Date Range --}}
                                <div class="position-relative  mr-1">
                                    <input type="text" id="daterange" class="form-control form-control-sm pe-4"
                                        style="min-width: 260px" placeholder="Pilih tanggal" autocomplete="off">
                                    <i class="fas fa-calendar-alt position-absolute"
                                        style="right:10px; top:50%; transform:translateY(-50%); color:#6c757d"></i>
                                </div>

                                {{-- Export --}}
                                <a href="#" id="btnExport" class="btn btn-success btn-sm  mr-1">
                                    <i class="fas fa-file-export"></i>
                                </a>

                                {{-- Tambah --}}
                                <a href="{{ url('form/form-sfmasuk') }}" class="btn btn-primary btn-sm">
                                    + Tambah
                                </a>

                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="sfmasuk-table" class="table table-sm table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Denom</th>
                                        <th>Qty</th>
                                        <th>TAP</th>
                                        <th>SF</th>
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
        $(function() {

            /* ==========================
               DEFAULT RANGE: BULAN INI
            ========================== */
            let start = moment().startOf('month');
            let end = moment().endOf('month');

            $('#daterange').val(
                start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD')
            );

            /* ==========================
               DATATABLE SERVER SIDE
            ========================== */
            let table = $('#sfmasuk-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ route('sf-masuk.data') }}",
                    data: function(d) {
                        d.daterange = $('#daterange').val();
                    }
                },
                columns: [{
                        data: 'tgl',
                        name: 'f.tgl'
                    },
                    {
                        data: 'denom',
                        name: 'd.denom'
                    },
                    {
                        data: 'qty',
                        name: 'f.qty',
                        className: 'text-end'
                    },
                    {
                        data: 'idtap',
                        name: 'f.idtap'
                    },
                    {
                        data: 'namasf',
                        name: 'i.namasf'
                    },
                    {
                        data: 'sn',
                        name: 'f.sn'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },

                ]
            });

            /* ==========================
               DATE RANGE PICKER
            ========================== */
            $('#daterange').daterangepicker({
                startDate: start,
                endDate: end,
                autoUpdateInput: true,
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' - ',
                    applyLabel: 'Pilih',
                    cancelLabel: 'Batal'
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
            }, function(start, end) {

                let range = start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD');
                $('#daterange').val(range);

                table.ajax.reload();

                $('#btnExport').attr(
                    'href',
                    '/exportsfmasuk?daterange=' + encodeURIComponent(range)
                );
            });

            /* ==========================
               EXPORT DEFAULT
            ========================== */
            $('#btnExport').attr(
                'href',
                '/exportsfmasuk?daterange=' + encodeURIComponent($('#daterange').val())
            );

        });
    </script>
@endpush
