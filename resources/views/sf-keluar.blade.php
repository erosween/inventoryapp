@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                <div class="card shadow-sm">
                    {{-- HEADER --}}
                    <div class="card-header py-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                            <h4 class="card-title mb-0">Barang Keluar SF</h4>

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
                                <a href="{{ url('form/form-sfkeluar') }}" class="btn btn-primary btn-sm">
                                    + Tambah
                                </a>

                            </div>
                        </div>
                    </div>

                    {{-- BODY --}}
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table id="sfkeluar-table" class="table table-sm table-striped table-hover align-middle w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Denom</th>
                                        <th class="text-end">Quantity</th>
                                        <th>TAP</th>
                                        <th>SF</th>
                                        <th>Keterangan</th>
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
               DEFAULT RANGE
            ========================== */
            let start = moment().startOf('month');
            let end = moment().endOf('month');

            const $daterange = $('#daterange');

            $daterange.val(
                start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD')
            );

            /* ==========================
               DATATABLE SERVER SIDE
            ========================== */
            let table = $('#sfkeluar-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ route('sf-keluar.data') }}",
                    data: function(d) {
                        d.daterange = $daterange.val();
                    }
                },
                columns: [{
                        data: 'tgl',
                        name: 'f.tgl',
                        render: function(data, type) {
                            if (type === 'display') {
                                return moment(data).format('DD-MM-YYYY');
                            }
                            return data;
                        }
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
                        data: 'tambahanket',
                        name: 'f.tambahanket'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            /* ==========================
               DATE RANGE PICKER
            ========================== */
            $daterange.daterangepicker({
                startDate: start,
                endDate: end,
                autoUpdateInput: false,
                showDropdowns: false,
                alwaysShowCalendars: true,
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
            });

            /* ==========================
               APPLY ONLY (AMAN)
            ========================== */
            $daterange.on('apply.daterangepicker', function(ev, picker) {

                let range =
                    picker.startDate.format('YYYY-MM-DD') +
                    ' - ' +
                    picker.endDate.format('YYYY-MM-DD');

                $(this).val(range);

                table.ajax.reload(null, false);

                $('#btnExport').attr(
                    'href',
                    '/exportsfkeluar?daterange=' + encodeURIComponent(range)
                );
            });

            /* ==========================
               EXPORT DEFAULT
            ========================== */
            $('#btnExport').attr(
                'href',
                '/exportsfkeluar?daterange=' + encodeURIComponent($daterange.val())
            );

        });
    </script>
@endpush
