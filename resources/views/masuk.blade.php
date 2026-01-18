@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                {{-- ALERT --}}
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->has('error'))
                    <div class="alert alert-danger">{{ $errors->first('error') }}</div>
                @endif

                @if ($unapprovedCount > 0)
                    <div class="alert alert-danger">
                        Terdapat {{ $unapprovedCount }} item yang belum disetujui.
                    </div>
                @endif

                <div class="card shadow-sm">

                    {{-- HEADER --}}
                    <div class="card-header py-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                            <h4 class="card-title mb-0">Barang Masuk TAP</h4>

                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                {{-- DATE RANGE --}}
                                <div class="position-relative">
                                    <input type="text" id="daterange" class="form-control form-control-sm pe-4"
                                        style="min-width:260px" placeholder="Pilih tanggal" autocomplete="off">
                                    <i class="fas fa-calendar-alt position-absolute"
                                        style="right:10px; top:50%; transform:translateY(-50%); color:#6c757d"></i>
                                </div>

                                {{-- EXPORT --}}
                                <a href="#" id="btnExport" class="btn btn-success btn-sm ml-1">
                                    <i class="fas fa-file-export"></i> Export
                                </a>

                                {{-- VIEW SUMMARY --}}
                                <button class="btn btn-primary btn-sm ml-1" data-toggle="modal" data-target="#show">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- BODY --}}
                    <div class="card-body">

                        <div class="table-responsive">
                            <table id="masuk-table" class="table table-sm table-striped table-hover align-middle w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Denom</th>
                                        <th class="text-end">Quantity</th>
                                        <th>Pengirim</th>
                                        <th>Penerima</th>
                                        <th>SN</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                    </div>
                </div>

                {{-- MODAL VIEW TOTAL --}}
                <div class="modal fade" id="show" role="dialog">
                    <div class="modal-dialog modal-md">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Ringkasan Barang Masuk</h5>
                            </div>
                            <div class="modal-body">
                                <table id="summary-table" class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Denom</th>
                                            <th class="text-end">Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Grand Total</th>
                                            <th class="text-end" id="grandTotal">0</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                            </div>
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
               DATATABLE
            ========================== */
            let table = $('#masuk-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ route('masuk.data') }}",
                    data: function(d) {
                        d.daterange = $daterange.val();
                    }
                },
                columns: [{
                        data: 'tgl',
                        render: d => moment(d).format('DD-MM-YYYY')
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
                        data: 'penerima'
                    },
                    {
                        data: 'sn'
                    },
                    {
                        data: 'status',
                        render: s => s == 0 ?
                            '<span class="badge badge-success">Approved</span>' :
                            '<span class="badge badge-warning">Wait for Approval</span>'
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

                table.ajax.reload();

                $('#btnExport').attr(
                    'href',
                    '/exportexcelmasuk?daterange=' + encodeURIComponent(range)
                );

                loadSummary(range);
            });

            /* ==========================
               EXPORT DEFAULT
            ========================== */
            $('#btnExport').attr(
                'href',
                '/exportexcelmasuk?daterange=' + encodeURIComponent($daterange.val())
            );

            /* ==========================
               SUMMARY MODAL
            ========================== */
            function loadSummary(range) {
                $.get('/masuk/summary', {
                    daterange: range
                }, function(res) {
                    let html = '';
                    let total = 0;

                    res.forEach(r => {
                        html += `<tr>
                    <td>${r.denom}</td>
                    <td class="text-end">${Number(r.qty).toLocaleString()}</td>
                </tr>`;
                        total += parseInt(r.qty);
                    });

                    $('#summary-table tbody').html(html);
                    $('#grandTotal').text(total.toLocaleString());
                });
            }

            loadSummary($daterange.val());

        });
    </script>
@endpush
