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

                    <div class="login-summary border-bottom bg-white px-4 py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:12px">
                            <div>
                                <h6 class="font-weight-bold text-dark mb-1">
                                    <i class="fas fa-sign-in-alt mr-2 text-indigo"></i>Summary Login per User
                                </h6>
                                <div class="text-muted small">Periode bulan berjalan · {{ $loginSummaryPeriod }}</div>
                            </div>
                            <div class="d-flex align-items-center flex-wrap" style="gap:8px">
                                <span class="login-summary-chip"><strong>{{ number_format($loginSummaryTotal) }}</strong> total login</span>
                                <span class="login-summary-chip"><strong>{{ number_format($loginSummaryActiveUsers) }}</strong> user aktif</span>
                            </div>
                        </div>

                        <div class="login-summary-toolbar d-flex justify-content-between align-items-center flex-wrap">
                            <div class="login-summary-search">
                                <span class="login-summary-search-icon" aria-hidden="true"><i class="fas fa-search"></i></span>
                                <input type="search" id="login-summary-search" class="form-control form-control-sm shadow-none"
                                    placeholder="Cari username atau nama TAP..." autocomplete="off" aria-label="Cari summary login">
                            </div>
                            <span id="login-summary-result" class="small text-muted">{{ number_format($loginSummary->count()) }} user ditampilkan</span>
                        </div>

                        <div id="login-summary-table-wrap" class="table-responsive login-summary-table-wrap">
                            <table class="table table-sm mb-0 login-summary-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>TAP</th>
                                        <th class="text-center">Jumlah Login</th>
                                        <th>Login Terakhir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($loginSummary as $summary)
                                        <tr class="login-summary-row {{ (int) $summary->login_count === 0 ? 'login-summary-inactive' : '' }}"
                                            data-search="{{ strtolower($summary->username.' '.$summary->idtap) }}">
                                            <td class="font-weight-bold">{{ $summary->username }}</td>
                                            <td>{{ $summary->idtap }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ (int) $summary->login_count > 0 ? 'badge-primary' : 'badge-light border' }} px-3 py-2">
                                                    {{ number_format($summary->login_count) }}x
                                                </span>
                                            </td>
                                            <td>{{ $summary->last_login_at ? \Carbon\Carbon::parse($summary->last_login_at)->format('d-m-Y H:i:s') : 'Belum login bulan ini' }}</td>
                                        </tr>
                                    @endforeach
                                    <tr id="login-summary-empty" class="d-none">
                                        <td colspan="4" class="text-center text-muted py-4">User atau TAP tidak ditemukan.</td>
                                    </tr>
                                </tbody>
                            </table>
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
        .login-summary-chip {
            padding: 7px 12px;
            border-radius: 999px;
            background: #eef2ff;
            color: #4f46e5;
            font-size: .78rem;
        }
        .login-summary-search input {
            flex: 1 1 auto;
            min-width: 0;
            height: 38px;
            padding: 0 12px 0 8px !important;
            border: 0;
            border-radius: 0 8px 8px 0;
            background: transparent;
        }
        .login-summary-search {
            display: flex;
            align-items: center;
            width: min(420px, 100%);
            min-height: 38px;
            overflow: hidden;
            border-radius: 8px;
            background: #fff;
        }
        .login-summary-search-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 38px;
            height: 38px;
            color: #8b94a5;
            font-size: .75rem;
            pointer-events: none;
        }
        .login-summary-toolbar {
            min-height: 48px;
            padding: 4px 12px;
            gap: 10px;
            background: #f8f9fc;
            border: 1px solid #edf0f5;
            border-bottom: 0;
            border-radius: 10px 10px 0 0;
        }
        .login-summary-table-wrap {
            max-height: 330px;
            border: 1px solid #edf0f5;
            border-radius: 0 0 10px 10px;
            transition: max-height .2s ease;
        }
        .login-summary-table-wrap.is-filtering {
            max-height: none;
            overflow: visible;
        }
        .login-summary-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f8f9fc;
            border-top: 0;
            color: #687386;
            font-size: .75rem;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .login-summary-table td {
            vertical-align: middle;
            font-size: .82rem;
            padding-top: .65rem;
            padding-bottom: .65rem;
        }
        .login-summary-inactive {
            color: #9aa1ad;
            background: #fbfcfd;
        }
    </style>
@endsection

@push('scripts')
    <script>
        $(function() {
            const summarySearch = document.getElementById('login-summary-search');
            summarySearch?.addEventListener('input', function() {
                const keyword = this.value.trim().toLowerCase();
                let visibleRows = 0;
                document.querySelectorAll('.login-summary-row').forEach(row => {
                    const visible = !keyword || (row.dataset.search || '').includes(keyword);
                    row.classList.toggle('d-none', !visible);
                    if (visible) visibleRows++;
                });
                document.getElementById('login-summary-empty')?.classList.toggle('d-none', visibleRows > 0);
                document.getElementById('login-summary-table-wrap')?.classList.toggle('is-filtering', keyword.length > 0);
                const resultLabel = document.getElementById('login-summary-result');
                if (resultLabel) resultLabel.textContent = `${visibleRows} user ditemukan`;
            });

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
