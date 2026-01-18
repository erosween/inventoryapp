@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                <div class="card shadow-sm">
                    {{-- HEADER --}}
                    <div class="card-header py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h4 class="card-title mb-0">Voucher Rusak</h4>

                            <div class="d-flex gap-2">
                                <input type="text" id="daterange" class="form-control form-control-sm"
                                    style="min-width:260px" placeholder="Pilih tanggal">

                                <a href="#" id="btnExport" class="btn btn-success btn-sm ml-1">
                                    <i class="fas fa-file-export"></i>
                                </a>

                                <a href="{{ url('form/form-vrusak') }}" class="btn btn-primary btn-sm ml-1">
                                    + Tambah
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- BODY --}}
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="vrusak-table" class="table table-sm table-striped table-hover w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Denom</th>
                                        <th class="text-end">Qty</th>
                                        <th>TAP</th>
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
               DEFAULT RANGE (BULAN INI)
            ========================== */
            let start = moment().startOf('month');
            let end = moment().endOf('month');

            const $daterange = $('#daterange');

            // ⬅️ PENTING: set value langsung
            $daterange.val(
                start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD')
            );

            /* ==========================
               DATATABLE
            ========================== */
            let table = $('#vrusak-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ route('vrusak.data') }}",
                    data: function(d) {
                        d.daterange = $daterange.val();
                    }
                },
                columns: [{
                        data: 'tgl',
                        name: 'r.tgl',
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
                        className: 'text-end'
                    },
                    {
                        data: 'idtap',
                        name: 'r.idtap'
                    },
                    {
                        data: 'sn'
                    },
                    {
                        data: 'ketvf'
                    },
                    {
                        data: 'ketlain'
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
               APPLY RANGE
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
                    '/exportrusak?daterange=' + encodeURIComponent(range)
                );
            });

            /* ==========================
               EXPORT DEFAULT
            ========================== */
            $('#btnExport').attr(
                'href',
                '/exportrusak?daterange=' + encodeURIComponent($daterange.val())
            );

        });
    </script>
@endpush
