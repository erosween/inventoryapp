@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Retur BO TAP</h4>
                        <div class="d-flex gap-2 ">
                            <div class="position-relative mr-1">
                                <input type="text" id="daterange" class="form-control form-control-sm pe-4"
                                    style="min-width:260px" placeholder="Pilih tanggal" autocomplete="off">

                                <i class="fas fa-calendar-alt position-absolute text-muted" id="calendarIcon"
                                    style="right:10px; top:50%; transform:translateY(-50%); cursor:pointer">
                                </i>
                            </div>


                            <a href="#" id="btnExport" class="btn btn-success btn-sm mr-1">
                                <i class="fas fa-file-export"></i>
                            </a>

                            <a href="{{ url('form/formkeluarbo') }}" class="btn btn-primary btn-sm">
                                + Tambah
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="bo-table" class="table table-sm table-striped table-hover w-100">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
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
                alwaysShowCalendars: true,
                showCustomRangeLabel: false,
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

            /* ==========================
               CONFIRM DELETE
            ========================== */
            $(document).on('submit', '.form-delete', function(e) {
                e.preventDefault();
                let form = this;

                Swal.fire({
                    title: 'Hapus data?',
                    text: 'Stok akan dikembalikan ke BO',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonText: 'Batal',
                    confirmButtonText: 'Ya, hapus'
                }).then(res => {
                    if (res.isConfirmed) form.submit();
                });
            });

        });
    </script>
@endpush
