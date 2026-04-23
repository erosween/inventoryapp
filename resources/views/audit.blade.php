@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                <div class="card premium-card">
                    <div class="card-header py-3 px-4 bg-white border-bottom shadow-sm">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h4 class="card-title mb-0 font-weight-bold text-indigo">
                                    <i class="fas fa-history mr-2"></i>Audit Trail (Log Aktifitas)
                                </h4>
                                <div class="text-muted small">Rekam jejak setiap perubahan data di dalam sistem</div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <div class="position-relative">
                                    <input type="text" id="daterange" class="form-control form-control-sm pe-4 shadow-none border"
                                        style="min-width: 250px; background: #f8f9fa; border-radius: 20px;" placeholder="Pilih tanggal" autocomplete="off">
                                    <i class="fas fa-calendar-alt position-absolute"
                                        style="right:12px; top:50%; transform:translateY(-50%); color:var(--premium-indigo)"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body px-0 py-0">
                        <div class="table-responsive">
                            <table id="audit-table" class="table table-indigo table-hover w-100 mb-0" style="font-size: 0.85rem;">
                                <thead>
                                    <tr>
                                        <th width="150">Waktu</th>
                                        <th width="100">User</th>
                                        <th width="80">Aksi</th>
                                        <th width="120">Modul</th>
                                        <th width="50">ID</th>
                                        <th>Data Lama</th>
                                        <th>Data Baru</th>
                                        <th width="100">IP Address</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <style>
        .table-indigo pre {
            background: #f1f3f9;
            padding: 5px;
            border-radius: 4px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.75rem;
        }
    </style>
@endsection

@push('scripts')
    <script>
        $(function() {
            let start = moment().subtract(7, 'days');
            let end = moment();

            const $daterange = $('#daterange');
            $daterange.val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));

            let table = $('#audit-table').DataTable({
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                ajax: {
                    url: "{{ route('audit.data') }}",
                    data: function(d) {
                        d.daterange = $daterange.val();
                    }
                },
                columns: [
                    { data: 'created_at', name: 'created_at' },
                    { data: 'username', name: 'username' },
                    { 
                        data: 'action', 
                        name: 'action',
                        render: function(data) {
                            let badgeClass = 'badge-secondary';
                            if (data === 'INSERT') badgeClass = 'badge-success';
                            if (data === 'UPDATE') badgeClass = 'badge-warning';
                            if (data === 'DELETE') badgeClass = 'badge-danger';
                            if (data === 'APPROVE') badgeClass = 'badge-info';
                            return `<span class="badge ${badgeClass}">${data}</span>`;
                        }
                    },
                    { data: 'module', name: 'module' },
                    { data: 'record_id', name: 'record_id' },
                    { data: 'old_values', name: 'old_values', orderable: false, searchable: false },
                    { data: 'new_values', name: 'new_values', orderable: false, searchable: false },
                    { data: 'ip_address', name: 'ip_address' },
                ]
            });

            $daterange.daterangepicker({
                startDate: start,
                endDate: end,
                autoUpdateInput: false,
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' - '
                }
            });

            $daterange.on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                table.ajax.reload();
            });
        });
    </script>
@endpush
