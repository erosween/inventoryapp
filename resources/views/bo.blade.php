@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                <div class="card premium-card">
                    {{-- HEADER --}}
                    <div class="card-header py-3 px-4 bg-white border-bottom shadow-sm">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <h4 class="card-title mb-0 font-weight-bold text-indigo">
                                    <i class="fas fa-box-open mr-2"></i>Retur BO TAP
                                </h4>
                                <div class="text-muted small">Monitoring pengembalian barang (Bad Stock / BO) ke gudang pusat</div>
                            </div>

                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                {{-- Date Range --}}
                                <div class="position-relative">
                                    <input type="text" id="daterange" class="form-control form-control-sm pe-4 shadow-none border"
                                        style="min-width: 250px; background: #f8f9fa; border-radius: 20px;" placeholder="Pilih tanggal" autocomplete="off">
                                    <i class="fas fa-calendar-alt position-absolute"
                                        style="right:12px; top:50%; transform:translateY(-50%); color:var(--premium-indigo)"></i>
                                </div>

                                <div class="btn-group shadow-sm" style="border-radius: 20px; overflow: hidden;">
                                    {{-- Export --}}
                                    <a href="#" id="btnExport" class="btn btn-success btn-sm border-0" title="Export Excel">
                                        <i class="fas fa-file-export"></i>
                                    </a>

                                    {{-- Tambah --}}
                                    <a href="{{ url('form/formkeluarbo') }}" class="btn btn-primary btn-sm border-0 font-weight-bold">
                                        <i class="fas fa-plus-circle mr-1"></i> Tambah
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- BODY --}}
                    <div class="card-body px-0 py-0">
                        <div class="table-responsive">
                            <table id="bo-table" class="table table-indigo table-hover w-100 mb-0">
                                <thead>
                                    <tr>
                                        <th class="sticky-col">Tanggal</th>
                                        <th>Denom</th>
                                        <th class="text-end">Qty</th>
                                        <th>Pengirim</th>
                                        <th>Penerima</th>
                                        <th>SN</th>
                                        <th>Keterangan</th>
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
               DEFAULT RANGE : BULAN INI
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
            let table = $('#bo-table').DataTable({
                processing: true,
                serverSide: true,
                order: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ route('bo.data') }}",
                    data: function(d) {
                        d.daterange = $daterange.val();
                    }
                },
                columns: [{
                        data: 'tgl',
                        name: 'k.tgl'
                    },
                    {
                        data: 'denom',
                        name: 'd.denom'
                    },
                    {
                        data: 'qty',
                        name: 'k.qty',
                        className: 'text-end'
                    },
                    {
                        data: 'pengirim',
                        name: 'k.pengirim'
                    },
                    {
                        data: 'penerima',
                        name: 'k.penerima'
                    },
                    {
                        data: 'sn',
                        name: 'k.sn'
                    },
                    {
                        data: 'tambahanket',
                        name: 'k.tambahanket'
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
                autoUpdateInput: true,
                alwaysShowCalendars: false,
                showCustomRangeLabel: false,
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
            }, function(start, end) {

                let range = start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD');
                $('#daterange').val(range);

                table.ajax.reload();

                $('#btnExport').attr(
                    'href',
                    '/exportexcelbo?daterange=' + encodeURIComponent(range)
                );
            });

            /* ==========================
               FORCE OPEN CALENDAR
            ========================== */
            // klik input
            $daterange.on('click', function() {
                $(this).data('daterangepicker').show();
            });

            // klik icon kalender (kalau ada)
            $('#calendarIcon').on('click', function() {
                $daterange.data('daterangepicker').show();
            });

            /* ==========================
               EXPORT DEFAULT (BULAN INI)
            ========================== */
            $('#btnExport').attr(
                'href',
                '/exportexcelbo?daterange=' +
                encodeURIComponent($daterange.val())
            );

        });
    </script>
@endpush
