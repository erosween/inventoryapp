@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                <div class="card shadow-sm">

                    {{-- HEADER --}}
                    <div class="card-header py-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <h4 class="card-title mb-0">Barang Retur SF (Masuk TAP)</h4>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                {{-- Date Range --}}
                                <div class="position-relative mr-1">
                                    <input type="text" id="daterange" class="form-control form-control-sm pe-4"
                                        style="min-width:260px" placeholder="Pilih tanggal" autocomplete="off">
                                    <i class="fas fa-calendar-alt position-absolute"
                                        style="right:10px; top:50%; transform:translateY(-50%); color:#6c757d"></i>
                                </div>

                                <a href="#" id="btnExport" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-export"></i>
                                </a>

                                <a href="{{ url('form/form-retursf') }}" class="btn btn-primary btn-sm ml-1">
                                    + Tambah
                                </a>

                            </div>
                        </div>
                    </div>



                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="retursf-table" class="table table-sm table-striped table-hover w-100">
                                <thead>
                                    <tr>
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
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ route('retursf.data') }}",
                    data: function(d) {
                        d.daterange = $daterange.val();
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

        });
    </script>
@endpush
