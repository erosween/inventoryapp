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
                                    <i class="fas fa-file-upload mr-2"></i>Barang Keluar TAP
                                </h4>
                                <div class="text-muted small">Monitoring distribusi barang dari TAP ke gudang lain / outlet</div>
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
                                    <a href="{{ url('form/formkeluartap') }}" class="btn btn-primary btn-sm border-0 font-weight-bold">
                                        <i class="fas fa-plus-circle mr-1"></i> Tambah
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- BODY --}}
                    <div class="card-body px-0 py-0">
                        <div class="table-responsive">
                            <table id="keluar-table" class="table table-indigo table-hover w-100 mb-0">
                                <thead>
                                    <tr>
                                        <th class="sticky-col">Tanggal</th>
                                        <th>Denom</th>
                                        <th class="text-end">Quantity</th>
                                        <th>Pengirim</th>
                                        <th>Penerima</th>
                                        <th>SN</th>
                                        <th>Keterangan</th>
                                        <th width="210">Status</th>
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
            let table = $('#keluar-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ route('keluar.data') }}",
                    data: function(d) {
                        d.daterange = $daterange.val();
                    }
                },
                columns: [{
                        data: 'tgl',
                        name: 'k.tgl',
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
                        data: 'status_label',
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

            /* ==========================
               APPLY ONLY
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
                    '/exporttap?daterange=' + encodeURIComponent(range)
                );
            });

            /* ==========================
               EXPORT DEFAULT
            ========================== */
            $('#btnExport').attr(
                'href',
                '/exporttap?daterange=' + encodeURIComponent($daterange.val())
            );

            $('#keluar-table').on('submit', '.cancel-transfer-form', function(event) {
                event.preventDefault();
                const form = this;
                const escapeAlertText = value => $('<div>').text(value).html();
                const denom = escapeAlertText(form.dataset.denom || '-');
                const qty = escapeAlertText(form.dataset.qty || '0');
                const penerima = escapeAlertText(form.dataset.penerima || '-');

                Swal.fire({
                    icon: 'warning',
                    title: 'Batalkan pengiriman?',
                    html: `
                        <div class="text-left mx-auto" style="max-width:310px">
                            <div class="text-muted mb-3">Data pending ini akan langsung dihapus.</div>
                            <div class="p-3 rounded" style="background:#f8f9fc;border:1px solid #edf0f5">
                                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Denom</span><strong>${denom}</strong></div>
                                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Quantity</span><strong>${qty}</strong></div>
                                <div class="d-flex justify-content-between"><span class="text-muted">Penerima</span><strong>${penerima}</strong></div>
                            </div>
                        </div>`,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-times-circle mr-1"></i> Ya, batalkan',
                    cancelButtonText: 'Kembali',
                    confirmButtonColor: '#ef5350',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    focusCancel: true
                }).then(result => {
                    if (!result.isConfirmed) return;
                    Swal.fire({
                        title: 'Membatalkan pengiriman...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => Swal.showLoading()
                    });
                    form.submit();
                });
            });

            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Pengiriman dibatalkan',
                    text: @json(session('success')),
                    confirmButtonText: 'Oke',
                    confirmButtonColor: '#1e88e5',
                    timer: 3500,
                    timerProgressBar: true
                });
            @endif

        });
    </script>
@endpush
