@extends('presensi.admin.layout')

@section('title', 'Dashboard Admin Presensi')

@section('content')
@php
    $attendanceTypeLabels = [
        'hadir' => 'Kehadiran',
        'cuti' => 'Cuti',
        'terlambat' => 'Izin Terlambat Masuk',
        'cepat_pulang' => 'Izin Cepat Pulang',
        'sakit' => 'Sakit',
    ];
    $statusLabels = [
        'submitted' => 'Menunggu',
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'verified' => 'Terverifikasi',
        'completed' => 'Selesai',
        'rejected' => 'Ditolak',
    ];
    $statusClasses = [
        'submitted' => 'warning',
        'pending' => 'warning',
        'approved' => 'success',
        'verified' => 'success',
        'completed' => 'success',
        'rejected' => 'danger',
    ];
    $serviceRequestLabels = [
        'cuti' => 'Pengajuan Cuti',
        'ubah-kehadiran' => 'Ubah Kehadiran',
        'lembur' => 'Lembur',
        'reimbursement' => 'Reimbursement',
        'pengeluaran' => 'Pengeluaran',
    ];
    $activeAdminSection = $activeAdminSection ?? 'dashboard';
    $showStats = in_array($activeAdminSection, ['dashboard', 'presensi'], true);
    $showMonitoring = in_array($activeAdminSection, ['dashboard', 'presensi'], true);
    $showAttendanceRequests = in_array($activeAdminSection, ['dashboard', 'izin-cuti'], true);
    $showServiceRequests = in_array($activeAdminSection, ['dashboard', 'izin-cuti', 'lembur'], true);
    $showApproval = $showAttendanceRequests || $showServiceRequests;
    $showSettingsHero = in_array($activeAdminSection, ['karyawan', 'lokasi', 'pengaturan', 'role-akses'], true);
    $showUploadStructure = in_array($activeAdminSection, ['karyawan', 'pengaturan', 'role-akses'], true);
    $showEmployeeList = in_array($activeAdminSection, ['karyawan', 'lokasi', 'role-akses'], true);
    $showHistory = in_array($activeAdminSection, ['rekap', 'aktivitas'], true);
    $showAnnouncement = $activeAdminSection === 'pengumuman';
    $visiblePresenceRequests = $activeAdminSection === 'lembur'
        ? $presenceRequests->where('request_type', 'lembur')->values()
        : $presenceRequests;
@endphp
<div class="main-panel">
    <div class="content">
        <div class="page-inner">
            <div class="page-header">
                <h4 class="page-title">Command Center Presensi</h4>
                <ul class="breadcrumbs">
                    <li class="nav-home">
                        <a href="{{ route('admin-presensi.index') }}"><i class="fas fa-house"></i></a>
                    </li>
                    <li class="separator"><i class="fas fa-chevron-right"></i></li>
                    <li class="nav-item">Admin</li>
                    <li class="separator"><i class="fas fa-chevron-right"></i></li>
                    <li class="nav-item">Presensi</li>
                </ul>
            </div>

            <nav class="admin-command-nav mb-4" aria-label="Menu admin presensi">
                <a href="#overview" class="active"><i class="fas fa-chart-pie"></i><span>Overview</span></a>
                <a href="#pengajuan"><i class="fas fa-inbox"></i><span>Pengajuan</span><em>{{ $dashboardStats['pending_total'] }}</em></a>
                <a href="#monitoring"><i class="fas fa-user-clock"></i><span>Monitoring</span></a>
                <a href="#karyawan"><i class="fas fa-users-gear"></i><span>Karyawan</span></a>
                <a href="#riwayat"><i class="fas fa-clock-rotate-left"></i><span>Riwayat</span></a>
                <a href="#pengaturan"><i class="fas fa-sliders"></i><span>Pengaturan</span></a>
            </nav>

            <section class="admin-ops-hero mb-4" id="overview">
                <div class="ops-copy">
                    <span class="ops-eyebrow">Super Admin Workspace</span>
                    <h1>Kontrol presensi, approval, lokasi, dan jam kerja dari satu layar.</h1>
                    <p>Dashboard ini memantau kehadiran hari ini, pengajuan cuti/izin, Face ID, struktur atasan, dan data karyawan.</p>
                    <div class="ops-actions">
                        <a href="#pengajuan" class="btn btn-light btn-round"><i class="fas fa-check-double mr-1"></i> Review Pengajuan</a>
                        <a href="{{ route('presensi.login') }}" class="btn btn-outline-light btn-round" target="_blank"><i class="fas fa-mobile-screen-button mr-1"></i> Buka App Karyawan</a>
                    </div>
                </div>
                <div class="ops-scoreboard">
                    <div>
                        <strong>{{ $dashboardStats['checked_in_today'] }}</strong>
                        <span>Check-in hari ini</span>
                    </div>
                    <div>
                        <strong>{{ $dashboardStats['pending_total'] }}</strong>
                        <span>Butuh approval</span>
                    </div>
                    <div>
                        <strong>{{ $dashboardStats['face_rate'] }}%</strong>
                        <span>Face ID aktif</span>
                    </div>
                </div>
            </section>

            @if($showStats)
            <section class="stats-grid mb-4">
                <div class="ops-stat-card">
                    <i class="fas fa-users"></i>
                    <span>Karyawan Aktif</span>
                    <strong>{{ $dashboardStats['active_employees'] }}</strong>
                    <em>{{ $dashboardStats['total_employees'] }} total data karyawan</em>
                </div>
                <div class="ops-stat-card success">
                    <i class="fas fa-circle-check"></i>
                    <span>Hadir Hari Ini</span>
                    <strong>{{ $dashboardStats['checked_in_today'] }}</strong>
                    <em>{{ $dashboardStats['completed_today'] }} sudah check-out</em>
                </div>
                <div class="ops-stat-card warning">
                    <i class="fas fa-clock"></i>
                    <span>Terlambat</span>
                    <strong>{{ $dashboardStats['late_today'] }}</strong>
                    <em>Berbasis jam kerja karyawan</em>
                </div>
                <div class="ops-stat-card danger">
                    <i class="fas fa-user-slash"></i>
                    <span>Belum Hadir</span>
                    <strong>{{ $dashboardStats['not_checked_in_today'] }}</strong>
                    <em>Karyawan aktif belum check-in</em>
                </div>
                <div class="ops-stat-card violet">
                    <i class="fas fa-inbox"></i>
                    <span>Izin / Cuti</span>
                    <strong>{{ $dashboardStats['pending_total'] }}</strong>
                    <em>{{ $dashboardStats['pending_attendance'] }} HR, {{ $dashboardStats['pending_services'] }} layanan</em>
                </div>
            </section>
            @endif

            @if($showMonitoring)
            <section class="row mb-4" id="monitoring">
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <div class="card premium-card h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <div class="d-flex align-items-center flex-wrap">
                                <h4 class="card-title text-indigo font-weight-bold mb-0">
                                    <i class="fas fa-chart-column mr-2"></i>Tren Kehadiran 7 Hari
                                </h4>
                                <span class="history-count-pill ml-auto">{{ $dashboardStats['completion_rate'] }}% checkout</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="admin-trend-chart">
                                @foreach($attendanceTrend as $trend)
                                    <div class="admin-trend-item">
                                        <div class="admin-trend-track">
                                            <span style="height: {{ $trend['height'] }}%;"></span>
                                        </div>
                                        <strong>{{ $trend['day'] }}</strong>
                                        <em>{{ $trend['present'] }} hadir</em>
                                    </div>
                                @endforeach
                            </div>
                            <div class="trend-legend mt-3">
                                <span><i class="legend-dot hadir"></i>Hadir</span>
                                <span><i class="legend-dot late"></i>Telat</span>
                                <span><i class="legend-dot request"></i>Pengajuan</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card premium-card h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h4 class="card-title text-indigo font-weight-bold mb-0">
                                <i class="fas fa-satellite-dish mr-2"></i>Live Monitoring Hari Ini
                            </h4>
                        </div>
                        <div class="card-body live-monitor-list">
                            @forelse($todayAttendances->take(6) as $attendance)
                                @php($employeeRow = $attendance->employee)
                                @php($arrival = $attendance->arrivalStatus())
                                <div class="live-monitor-item">
                                    <div class="live-avatar">{{ strtoupper(substr($employeeRow?->name ?? 'K', 0, 1)) }}</div>
                                    <div class="min-w-0 flex-grow-1">
                                        <strong>{{ $employeeRow?->name ?? '-' }}</strong>
                                        <span>{{ $attendanceTypeLabels[$attendance->attendance_type] ?? ucfirst($attendance->attendance_type) }} - {{ $attendance->check_in_at?->format('H:i') ?? 'Belum masuk' }}</span>
                                    </div>
                                    <em class="{{ $arrival['class'] }}">{{ $arrival['label'] }}</em>
                                </div>
                            @empty
                                <div class="empty-admin-panel">Belum ada check-in hari ini.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
            @endif

            @if($showApproval)
            <section class="row mb-4" id="pengajuan">
                @if($showAttendanceRequests)
                <div class="{{ $showServiceRequests ? 'col-xl-6 mb-4 mb-xl-0' : 'col-xl-12' }}">
                    <div class="card premium-card h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <div class="d-flex align-items-center flex-wrap">
                                <h4 class="card-title text-indigo font-weight-bold mb-0">
                                    <i class="fas fa-file-signature mr-2"></i>Approval Cuti / Izin / Sakit
                                </h4>
                                <span class="history-count-pill ml-auto">{{ $dashboardStats['pending_attendance'] }} pending</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="presence-request-table" class="table table-indigo table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Karyawan</th>
                                            <th>Pengajuan</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($attendanceRequests as $requestRow)
                                            @php($employeeRow = $requestRow->employee)
                                            @php($isPending = in_array($requestRow->status, ['submitted', 'pending'], true))
                                            <tr>
                                                <td data-order="{{ $requestRow->attendance_date?->format('Ymd') }}{{ $requestRow->created_at?->format('His') }}">
                                                    <div class="font-weight-bold">{{ $requestRow->attendance_date?->format('d M Y') }}</div>
                                                    <div class="small text-muted">{{ $requestRow->created_at?->format('H:i') }}</div>
                                                </td>
                                                <td>
                                                    <div class="font-weight-bold">{{ $employeeRow?->name ?? '-' }}</div>
                                                    <div class="small text-muted">{{ $employeeRow?->employee_code ?? '-' }}</div>
                                                </td>
                                                <td>
                                                    <div class="font-weight-bold">{{ $attendanceTypeLabels[$requestRow->attendance_type] ?? ucfirst($requestRow->attendance_type) }}</div>
                                                    <div class="request-note">{{ $requestRow->reason ?: 'Tidak ada keterangan.' }}</div>
                                                </td>
                                                <td>
                                                    <span class="approval-pill {{ $statusClasses[$requestRow->status] ?? 'warning' }}">
                                                        {{ $statusLabels[$requestRow->status] ?? strtoupper($requestRow->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($isPending)
                                                        <div class="approval-actions">
                                                            <form action="{{ route('admin-presensi.attendance-status', $requestRow) }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="status" value="approved">
                                                                <button type="submit" class="btn btn-success btn-sm btn-round"><i class="fas fa-check"></i></button>
                                                            </form>
                                                            <form action="{{ route('admin-presensi.attendance-status', $requestRow) }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="status" value="rejected">
                                                                <button type="submit" class="btn btn-outline-danger btn-sm btn-round"><i class="fas fa-xmark"></i></button>
                                                            </form>
                                                        </div>
                                                    @else
                                                        <span class="small text-muted font-weight-bold">Selesai</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @if($showServiceRequests)
                <div class="{{ $showAttendanceRequests ? 'col-xl-6' : 'col-xl-12' }}">
                    <div class="card premium-card h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <div class="d-flex align-items-center flex-wrap">
                                <h4 class="card-title text-indigo font-weight-bold mb-0">
                                    <i class="fas fa-briefcase mr-2"></i>Pengajuan Layanan Karyawan
                                </h4>
                                <span class="history-count-pill ml-auto">{{ $visiblePresenceRequests->where('status', 'pending')->count() }} pending</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="presence-service-request-table" class="table table-indigo table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th>Dibuat</th>
                                            <th>Karyawan</th>
                                            <th>Layanan</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($visiblePresenceRequests as $serviceRequest)
                                            @php($employeeRow = $serviceRequest->employee)
                                            @php($isPending = $serviceRequest->status === 'pending')
                                            <tr>
                                                <td data-order="{{ $serviceRequest->created_at?->format('YmdHis') }}">
                                                    <div class="font-weight-bold">{{ $serviceRequest->created_at?->format('d M Y') }}</div>
                                                    <div class="small text-muted">{{ $serviceRequest->created_at?->format('H:i') }}</div>
                                                </td>
                                                <td>
                                                    <div class="font-weight-bold">{{ $employeeRow?->name ?? '-' }}</div>
                                                    <div class="small text-muted">{{ $employeeRow?->employee_code ?? '-' }}</div>
                                                </td>
                                                <td>
                                                    <div class="font-weight-bold">{{ $serviceRequestLabels[$serviceRequest->request_type] ?? strtoupper(str_replace('-', ' ', $serviceRequest->request_type)) }}</div>
                                                    <div class="request-note">
                                                        {{ $serviceRequest->category }}
                                                        @if($serviceRequest->start_date)
                                                            - {{ $serviceRequest->start_date?->format('d M') }}{{ $serviceRequest->end_date && !$serviceRequest->end_date->equalTo($serviceRequest->start_date) ? ' - ' . $serviceRequest->end_date->format('d M') : '' }}
                                                        @endif
                                                        @if($serviceRequest->amount)
                                                            - Rp {{ number_format((float) $serviceRequest->amount, 0, ',', '.') }}
                                                        @endif
                                                    </div>
                                                    <div class="request-note">{{ $serviceRequest->description ?: '-' }}</div>
                                                    @if($serviceRequest->attachment_path)
                                                        <a href="{{ asset('storage/' . $serviceRequest->attachment_path) }}" target="_blank" class="attachment-link">
                                                            <i class="fas fa-paperclip mr-1"></i>Lampiran
                                                        </a>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="approval-pill {{ $statusClasses[$serviceRequest->status] ?? 'warning' }}">
                                                        {{ $statusLabels[$serviceRequest->status] ?? strtoupper($serviceRequest->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($isPending)
                                                        <div class="approval-actions">
                                                            <form action="{{ route('admin-presensi.request-status', $serviceRequest) }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="status" value="approved">
                                                                <button type="submit" class="btn btn-success btn-sm btn-round"><i class="fas fa-check"></i></button>
                                                            </form>
                                                            <form action="{{ route('admin-presensi.request-status', $serviceRequest) }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="status" value="rejected">
                                                                <button type="submit" class="btn btn-outline-danger btn-sm btn-round"><i class="fas fa-xmark"></i></button>
                                                            </form>
                                                        </div>
                                                    @else
                                                        <span class="small text-muted font-weight-bold">Selesai</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </section>
            @endif

            @if($showSettingsHero)
            <div class="row" id="pengaturan">
                <div class="col-md-12">
                    <div class="card card-round border-0 shadow-sm text-white mb-4" style="background: linear-gradient(135deg, #5b37e5 0%, #2f1aa8 100%);">
                        <div class="card-body py-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <div class="text-white-50 font-weight-bold text-uppercase small mb-1">Pengaturan Operasional</div>
                                    <h2 class="font-weight-bold mb-1">Lokasi, Jam Kerja, dan Struktur Karyawan</h2>
                                    <p class="mb-0 text-white-50">Upload karyawan, atur level, atasan, radius lokasi, jam masuk, jam pulang, dan toleransi keterlambatan.</p>
                                </div>
                                <div class="text-right mt-3 mt-md-0">
                                    <div class="h1 font-weight-bold mb-0">{{ $employees->count() }}</div>
                                    <div class="text-white-50 small font-weight-bold">Karyawan</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($showUploadStructure)
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card premium-card h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h4 class="card-title text-indigo font-weight-bold mb-0">
                                <i class="fas fa-file-arrow-up mr-2"></i>Upload Karyawan
                            </h4>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted font-weight-bold">Upload file `.xlsx`, `.xls`, atau `.csv`. Jika password kosong untuk karyawan baru, default-nya `123456`.</p>
                            <a href="{{ route('admin-presensi.template') }}" class="btn btn-outline-primary btn-round btn-block mb-3">
                                <i class="fas fa-download mr-1"></i>Download Template CSV
                            </a>
                            <form action="{{ route('admin-presensi.import') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group-default employee-file-field">
                                    <label for="employeeFile">File Karyawan</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="employeeFile" name="employee_file" accept=".xlsx,.xls,.csv,.txt" required>
                                        <label class="custom-file-label text-truncate" for="employeeFile">Pilih file Excel / CSV</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-round btn-block mt-3">
                                    <i class="fas fa-upload mr-1"></i>Upload Data
                                </button>
                            </form>
                            <div class="small text-muted mt-3">
                                Kolom penting: employee_code, name, level, supervisor_code, password.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 mb-4">
                    <div class="card premium-card h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h4 class="card-title text-indigo font-weight-bold mb-0">
                                <i class="fas fa-sitemap mr-2"></i>Struktur Level
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="level-card level-one">
                                        <strong>Level 1</strong>
                                        <span>Admin / Sales</span>
                                        <em>{{ $employees->where('employee_level', 1)->count() }} orang</em>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="level-card level-two">
                                        <strong>Level 2</strong>
                                        <span>SPV / Manager</span>
                                        <em>{{ $employees->where('employee_level', 2)->count() }} orang</em>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="level-card level-three">
                                        <strong>Level 3</strong>
                                        <span>GM</span>
                                        <em>{{ $employees->where('employee_level', 3)->count() }} orang</em>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-light border mb-0 small font-weight-bold text-muted">
                                Level 1 Admin/Sales bisa dikunci lokasi dan dipilihkan atasan. Level 2 SPV/Manager dan Level 3 GM otomatis bebas lokasi.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($showEmployeeList)
            <div class="row" id="karyawan">
                <div class="col-md-12">
                    <div class="card premium-card">
                        <div class="card-header bg-white border-bottom py-3">
                            <div class="d-flex align-items-center flex-wrap">
                                <h4 class="card-title text-indigo font-weight-bold mb-0">
                                    <i class="fas fa-location-crosshairs mr-2"></i>Daftar Karyawan Presensi
                                </h4>
                                <div class="ml-auto d-flex align-items-center flex-wrap presence-admin-actions">
                                    <button type="button" class="btn btn-primary btn-round mr-2" data-toggle="modal" data-target="#createEmployeeModal">
                                        <i class="fas fa-user-plus mr-1"></i>Tambah Manual
                                    </button>
                                    <a href="{{ route('presensi.login') }}" class="btn btn-outline-primary btn-round" target="_blank">
                                        <i class="fas fa-mobile-screen-button"></i> Buka App Presensi
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="presence-admin-table" class="table table-indigo table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th>Karyawan</th>
                                            <th>Face ID</th>
                                            <th>Level</th>
                                            <th>Atasan</th>
                                            <th>Aturan Lokasi</th>
                                            <th>Jam Kerja</th>
                                            <th>Radius</th>
                                            <th class="text-center" style="width: 120px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($employees as $employee)
                                            @php($policy = $employee->attendanceLocationPolicy())
                                            @php($latestAttendance = $employee->latestAttendance)
                                            @php($photoPath = $latestAttendance?->face_photo_path)
                                            @php($profilePhotoUrl = $employee->profile_photo_path ? asset('storage/' . $employee->profile_photo_path) : null)
                                            <tr>
                                                <td>
                                                    <div class="employee-inline">
                                                        @if($profilePhotoUrl)
                                                            <img src="{{ $profilePhotoUrl }}" alt="Foto {{ $employee->name }}" class="employee-avatar-img" loading="lazy">
                                                        @else
                                                            <span class="employee-avatar-fallback">{{ strtoupper(substr($employee->name ?? 'K', 0, 1)) }}</span>
                                                        @endif
                                                        <div class="min-w-0">
                                                            <div class="font-weight-bold text-truncate">{{ $employee->name }}</div>
                                                            <div class="small text-muted text-truncate">{{ $employee->employee_code }} - {{ $employee->department ?? '-' }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($photoPath)
                                                        <button type="button" class="attendance-photo-btn" data-toggle="modal" data-target="#attendancePhoto{{ $employee->id }}">
                                                            <img src="{{ route('admin-presensi.attendance-photo', [$latestAttendance, 'thumb'], false) }}" alt="Foto presensi {{ $employee->name }}" loading="lazy" data-photo-fallback="true">
                                                            <span>Match {{ $latestAttendance->face_match_score }}%</span>
                                                        </button>
                                                        <div class="small text-muted mt-1">{{ $latestAttendance->attendance_date?->format('d M Y') }}</div>
                                                    @else
                                                        <span class="text-muted small font-weight-bold">Belum ada</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ ($employee->employee_level ?? 1) >= 2 ? 'info' : 'primary' }}">
                                                        Level {{ $employee->employee_level ?? 1 }}
                                                    </span>
                                                    <div class="small text-muted mt-1">{{ $employee->levelLabel() }}</div>
                                                </td>
                                                <td>
                                                    @if($employee->supervisor)
                                                        <div class="font-weight-bold">{{ $employee->supervisor->name }}</div>
                                                        <div class="small text-muted">{{ $employee->supervisor->employee_code }}</div>
                                                    @else
                                                        <span class="text-muted small font-weight-bold">Belum diset</span>
                                                    @endif
                                                    @if($employee->subordinates_count > 0)
                                                        <div class="badge badge-light border mt-1">{{ $employee->subordinates_count }} bawahan</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $policy['badge_class'] === 'success' ? 'success' : ($policy['badge_class'] === 'info' ? 'info' : 'warning') }}">
                                                        {{ $policy['badge'] }}
                                                    </span>
                                                    <div class="small text-muted mt-1">{{ $policy['label'] }}</div>
                                                </td>
                                                <td>
                                                    <div class="font-weight-bold">{{ $employee->scheduleLabel() }}</div>
                                                    <div class="small text-muted">Toleransi {{ $employee->late_tolerance_minutes ?? 15 }} menit</div>
                                                </td>
                                                <td>{{ $employee->attendance_radius_meters ?? 150 }}m</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-link btn-primary btn-lg" data-toggle="modal" data-target="#editPresence{{ $employee->id }}">
                                                        <i class="fa fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($showHistory)
            <div class="row mt-4" id="riwayat">
                <div class="col-md-12">
                    <div class="card premium-card">
                        <div class="card-header bg-white border-bottom py-3">
                            <div class="d-flex align-items-center flex-wrap">
                                <h4 class="card-title text-indigo font-weight-bold mb-0">
                                    <i class="fas fa-clock-rotate-left mr-2"></i>Riwayat Absensi Karyawan
                                </h4>
                                <span class="history-count-pill ml-auto">{{ $attendanceHistory->count() }} data terakhir</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="presence-history-table" class="table table-indigo table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Karyawan</th>
                                            <th>Status</th>
                                            <th>Masuk</th>
                                            <th>Pulang</th>
                                            <th>Keterlambatan</th>
                                            <th>Foto</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($attendanceHistory as $attendance)
                                            @php($employeeRow = $attendance->employee)
                                            @php($arrival = $attendance->arrivalStatus())
                                            <tr>
                                                <td data-order="{{ $attendance->attendance_date?->format('Ymd') }}{{ $attendance->created_at?->format('His') }}">
                                                    <div class="font-weight-bold">{{ $attendance->attendance_date?->format('d M Y') }}</div>
                                                    <div class="small text-muted">{{ $attendance->created_at?->format('H:i') }}</div>
                                                </td>
                                                <td>
                                                    <div class="font-weight-bold">{{ $employeeRow?->name ?? '-' }}</div>
                                                    <div class="small text-muted">{{ $employeeRow?->employee_code ?? '-' }} - {{ $employeeRow?->department ?? '-' }}</div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $statusClasses[$attendance->status] ?? 'warning' }}">
                                                        {{ $statusLabels[$attendance->status] ?? strtoupper($attendance->status) }}
                                                    </span>
                                                    <div class="small text-muted mt-1">{{ $attendanceTypeLabels[$attendance->attendance_type] ?? ucfirst(str_replace('_', ' ', $attendance->attendance_type)) }}</div>
                                                </td>
                                                <td>{{ $attendance->check_in_at?->format('H:i') ?? '-' }}</td>
                                                <td>{{ $attendance->check_out_at?->format('H:i') ?? '-' }}</td>
                                                <td>
                                                    <span class="arrival-pill {{ $arrival['class'] }}">
                                                        {{ $arrival['label'] }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($attendance->face_photo_path)
                                                        <button type="button" class="attendance-photo-btn mini" data-toggle="modal" data-target="#attendanceHistoryPhoto{{ $attendance->id }}">
                                                            <img src="{{ route('admin-presensi.attendance-photo', [$attendance, 'thumb'], false) }}" alt="Foto presensi {{ $employeeRow?->name }}" loading="lazy" data-photo-fallback="true">
                                                            <span>{{ $attendance->face_match_score }}%</span>
                                                        </button>
                                                    @else
                                                        <span class="text-muted small font-weight-bold">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="history-reason">{{ $attendance->reason ?: '-' }}</div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($showAnnouncement)
            <section class="row">
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <div class="card premium-card h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h4 class="card-title text-indigo font-weight-bold mb-0">
                                <i class="fas fa-bullhorn mr-2"></i>Pengumuman HR
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="empty-admin-panel">Belum ada pengumuman aktif.</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card premium-card h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h4 class="card-title text-indigo font-weight-bold mb-0">
                                <i class="fas fa-calendar-day mr-2"></i>Info Hari Ini
                            </h4>
                        </div>
                        <div class="card-body live-monitor-list">
                            <div class="live-monitor-item">
                                <div class="live-avatar"><i class="fas fa-users"></i></div>
                                <div class="min-w-0 flex-grow-1">
                                    <strong>{{ $dashboardStats['active_employees'] }} karyawan aktif</strong>
                                    <span>{{ $dashboardStats['checked_in_today'] }} sudah check-in hari ini</span>
                                </div>
                                <em class="info">Live</em>
                            </div>
                            <div class="live-monitor-item">
                                <div class="live-avatar"><i class="fas fa-inbox"></i></div>
                                <div class="min-w-0 flex-grow-1">
                                    <strong>{{ $dashboardStats['pending_total'] }} approval pending</strong>
                                    <span>Cuti, izin, sakit, dan layanan karyawan</span>
                                </div>
                                <em class="warning">HR</em>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            @endif
        </div>
    </div>
</div>
@endsection

@push('modals')
    <div class="modal fade" id="createEmployeeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header no-bd">
                    <h5 class="modal-title">
                        <span class="fw-mediumbold">Tambah Manual</span>
                        <span class="fw-light">Karyawan Presensi</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin-presensi.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="manual_form" value="1">
                    <div class="modal-body">
                        <p class="small text-muted">Isi data utama karyawan, pilih level, lalu tentukan atasan untuk Level 1 jika diperlukan.</p>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Kode Karyawan</label>
                                    <input type="text" class="form-control" name="employee_code" value="{{ old('employee_code') }}" placeholder="EMP-SLS01" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group form-group-default">
                                    <label>Nama Karyawan</label>
                                    <input type="text" class="form-control" name="name" value="{{ old('name') }}" placeholder="Nama lengkap" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group form-group-default">
                                    <label>Status</label>
                                    <select class="form-control" name="status" required>
                                        <option value="active" @selected(old('status', 'active') === 'active')>Aktif</option>
                                        <option value="inactive" @selected(old('status') === 'inactive')>Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Departemen</label>
                                    <input type="text" class="form-control" name="department" value="{{ old('department') }}" placeholder="Sales">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Jabatan</label>
                                    <input type="text" class="form-control" name="position" value="{{ old('position') }}" placeholder="Sales">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>No. HP</label>
                                    <input type="text" class="form-control" name="phone" value="{{ old('phone') }}" placeholder="0812xxxx">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <label class="profile-upload-box">
                                    <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                    <span><i class="fas fa-camera"></i></span>
                                    <div>
                                        <strong>Foto Profil Karyawan</strong>
                                        <em>Opsional. JPG, PNG, atau WEBP maksimal 4MB.</em>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Level Karyawan</label>
                                    <select class="form-control employee-level-select" name="employee_level" required>
                                        <option value="1" @selected((int) old('employee_level', 1) === 1)>Level 1 - Admin / Sales</option>
                                        <option value="2" @selected((int) old('employee_level', 1) === 2)>Level 2 - SPV / Manager</option>
                                        <option value="3" @selected((int) old('employee_level', 1) === 3)>Level 3 - GM</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Mode Lokasi</label>
                                    <select class="form-control location-mode-select" name="attendance_location_mode" required>
                                        <option value="locked" @selected(old('attendance_location_mode', 'locked') === 'locked')>Lock Lokasi</option>
                                        <option value="anywhere" @selected(old('attendance_location_mode') === 'anywhere')>Semua Lokasi</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Radius Meter</label>
                                    <input type="number" class="form-control" name="attendance_radius_meters" value="{{ old('attendance_radius_meters', 150) }}" min="25" max="5000" required>
                                </div>
                            </div>
                        </div>

                        <div class="row supervisor-row">
                            <div class="col-md-12">
                                <div class="form-group form-group-default">
                                    <label>Atasan Level 1</label>
                                    <select class="form-control supervisor-select" name="supervisor_id">
                                        <option value="">Pilih atasan...</option>
                                        @foreach($supervisors as $supervisor)
                                            <option value="{{ $supervisor->id }}" @selected((int) old('supervisor_id') === $supervisor->id)>
                                                Level {{ $supervisor->employee_level }} - {{ $supervisor->name }} ({{ $supervisor->employee_code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="small text-muted font-weight-bold mb-2">Untuk Level 1, atasan bisa Level 2 SPV/Manager atau Level 3 GM.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Lokasi Kerja</label>
                                    <input type="text" class="form-control" name="work_location" value="{{ old('work_location') }}" placeholder="Dumai">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Label Lokasi</label>
                                    <input type="text" class="form-control" name="attendance_location_label" value="{{ old('attendance_location_label') }}" placeholder="Kantor Pusat">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Password Awal</label>
                                    <input type="text" class="form-control" name="password" value="{{ old('password') }}" placeholder="Kosongkan = 123456">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Latitude</label>
                                    <input type="number" step="0.0000001" class="form-control latitude-input" name="attendance_latitude" value="{{ old('attendance_latitude') }}" placeholder="-1.2345678">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Longitude</label>
                                    <input type="number" step="0.0000001" class="form-control longitude-input" name="attendance_longitude" value="{{ old('attendance_longitude') }}" placeholder="101.2345678">
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-center">
                                <button type="button" class="btn btn-outline-primary btn-round btn-sm use-current-location mt-2">
                                    <i class="fas fa-location-crosshairs"></i> Pakai Lokasi Browser Admin
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Jam Masuk</label>
                                    <input type="time" class="form-control" name="work_start_time" value="{{ old('work_start_time', '08:30') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Jam Pulang</label>
                                    <input type="time" class="form-control" name="work_end_time" value="{{ old('work_end_time', '17:00') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Toleransi Terlambat</label>
                                    <input type="number" class="form-control" name="late_tolerance_minutes" value="{{ old('late_tolerance_minutes', 15) }}" min="0" max="240" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer no-bd">
                        <button type="submit" class="btn btn-primary">Simpan Karyawan</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Tutup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach($employees as $employee)
        <div class="modal fade" id="editPresence{{ $employee->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header no-bd">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">Edit Presensi</span>
                            <span class="fw-light">{{ $employee->name }}</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                            <form action="{{ route('admin-presensi.update', $employee) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <p class="small text-muted">Level 1 Admin/Sales bisa dikunci lokasi dan dipilihkan atasan. Level 2 SPV/Manager dan Level 3 GM otomatis bebas lokasi.</p>

                            <div class="profile-edit-row mb-3">
                                @if($employee->profile_photo_path)
                                    <img src="{{ asset('storage/' . $employee->profile_photo_path) }}" alt="Foto {{ $employee->name }}" class="profile-edit-preview">
                                @else
                                    <span class="profile-edit-preview fallback">{{ strtoupper(substr($employee->name ?? 'K', 0, 1)) }}</span>
                                @endif
                                <label class="profile-upload-box flex-grow-1 mb-0">
                                    <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                    <span><i class="fas fa-camera"></i></span>
                                    <div>
                                        <strong>Ganti Foto Profil</strong>
                                        <em>Opsional. JPG, PNG, atau WEBP maksimal 4MB.</em>
                                    </div>
                                </label>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group form-group-default">
                                        <label>Level Karyawan</label>
                                        <select class="form-control employee-level-select" name="employee_level" required>
                                            <option value="1" @selected(($employee->employee_level ?? 1) === 1)>Level 1 - Admin / Sales</option>
                                            <option value="2" @selected(($employee->employee_level ?? 1) === 2)>Level 2 - SPV / Manager</option>
                                            <option value="3" @selected(($employee->employee_level ?? 1) === 3)>Level 3 - GM</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group form-group-default">
                                        <label>Mode Lokasi</label>
                                        <select class="form-control location-mode-select" name="attendance_location_mode" required>
                                            <option value="locked" @selected($employee->attendance_location_mode !== 'anywhere')>Lock Lokasi</option>
                                            <option value="anywhere" @selected($employee->attendance_location_mode === 'anywhere')>Semua Lokasi</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group form-group-default">
                                        <label>Radius Meter</label>
                                        <input type="number" class="form-control" name="attendance_radius_meters" value="{{ $employee->attendance_radius_meters ?? 150 }}" min="25" max="5000" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row supervisor-row">
                                <div class="col-md-12">
                                    <div class="form-group form-group-default">
                                        <label>Atasan Level 1</label>
                                        <select class="form-control supervisor-select" name="supervisor_id">
                                            <option value="">Pilih atasan...</option>
                                            @foreach($supervisors as $supervisor)
                                                @if($supervisor->id !== $employee->id)
                                                    <option value="{{ $supervisor->id }}" @selected($employee->supervisor_id === $supervisor->id)>
                                                        Level {{ $supervisor->employee_level }} - {{ $supervisor->name }} ({{ $supervisor->employee_code }})
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="small text-muted font-weight-bold mb-2">Field ini aktif untuk Level 1. Level 2 dan 3 otomatis tidak memakai atasan di aplikasi ini.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group form-group-default">
                                        <label>Label Lokasi</label>
                                        <input type="text" class="form-control" name="attendance_location_label" value="{{ $employee->attendance_location_label ?? $employee->work_location }}" placeholder="Kantor Pusat Dumai">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group form-group-default">
                                        <label>Latitude</label>
                                        <input type="number" step="0.0000001" class="form-control latitude-input" name="attendance_latitude" value="{{ $employee->attendance_latitude }}" placeholder="-1.2345678">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group form-group-default">
                                        <label>Longitude</label>
                                        <input type="number" step="0.0000001" class="form-control longitude-input" name="attendance_longitude" value="{{ $employee->attendance_longitude }}" placeholder="101.2345678">
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline-primary btn-round btn-sm use-current-location mb-3">
                                <i class="fas fa-location-crosshairs"></i> Pakai Lokasi Browser Admin
                            </button>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group form-group-default">
                                        <label>Jam Masuk</label>
                                        <input type="time" class="form-control" name="work_start_time" value="{{ $employee->work_start_time ? substr((string) $employee->work_start_time, 0, 5) : '08:30' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group form-group-default">
                                        <label>Jam Pulang</label>
                                        <input type="time" class="form-control" name="work_end_time" value="{{ $employee->work_end_time ? substr((string) $employee->work_end_time, 0, 5) : '17:00' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group form-group-default">
                                        <label>Toleransi Terlambat</label>
                                        <input type="number" class="form-control" name="late_tolerance_minutes" value="{{ $employee->late_tolerance_minutes ?? 15 }}" min="0" max="240" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group form-group-default">
                                        <label>Reset Password EMP</label>
                                        <input type="text" class="form-control" name="new_password" placeholder="Kosongkan jika tidak ingin ubah password">
                                    </div>
                                    <div class="small text-muted font-weight-bold mb-2">Password lama tidak bisa dilihat karena tersimpan hash. Isi field ini untuk reset password baru.</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer no-bd">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-danger" data-dismiss="modal">Tutup</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if($employee->latestAttendance?->face_photo_path)
            <div class="modal fade" id="attendancePhoto{{ $employee->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content attendance-photo-modal">
                        <div class="modal-header no-bd">
                            <h5 class="modal-title">
                                <span class="fw-mediumbold">Foto Presensi</span>
                                <span class="fw-light">{{ $employee->name }}</span>
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <img src="{{ route('admin-presensi.attendance-photo', [$employee->latestAttendance, 'full'], false) }}" alt="Foto presensi {{ $employee->name }}" class="attendance-photo-full" data-photo-fallback="true">
                            <div class="attendance-photo-meta">
                                <span><i class="fas fa-calendar-day mr-1"></i>{{ $employee->latestAttendance->attendance_date?->format('d M Y') }}</span>
                                <span><i class="fas fa-clock mr-1"></i>{{ $employee->latestAttendance->check_in_at?->format('H:i') ?? '-' }}</span>
                                <span><i class="fas fa-face-smile mr-1"></i>Match {{ $employee->latestAttendance->face_match_score }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    @foreach($attendanceHistory->whereNotNull('face_photo_path') as $attendance)
        @php($employeeRow = $attendance->employee)
        <div class="modal fade" id="attendanceHistoryPhoto{{ $attendance->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content attendance-photo-modal">
                    <div class="modal-header no-bd">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">Foto Riwayat</span>
                            <span class="fw-light">{{ $employeeRow?->name ?? 'Karyawan' }}</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <img src="{{ route('admin-presensi.attendance-photo', [$attendance, 'full'], false) }}" alt="Foto presensi {{ $employeeRow?->name }}" class="attendance-photo-full" data-photo-fallback="true">
                        <div class="attendance-photo-meta">
                            <span><i class="fas fa-calendar-day mr-1"></i>{{ $attendance->attendance_date?->format('d M Y') }}</span>
                            <span><i class="fas fa-clock mr-1"></i>{{ $attendance->check_in_at?->format('H:i') ?? '-' }}</span>
                            <span><i class="fas fa-face-smile mr-1"></i>Match {{ $attendance->face_match_score }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endpush

@push('styles')
<style>
    html {
        scroll-behavior: smooth;
    }

    .admin-command-nav {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 10px;
        padding: 10px;
        border: 1px solid rgba(232,234,246,0.96);
        border-radius: 22px;
        background: rgba(255,255,255,0.82);
        box-shadow: 0 18px 44px rgba(31,35,85,0.06);
        backdrop-filter: blur(14px);
        position: sticky;
        top: 10px;
        z-index: 20;
    }

    .admin-command-nav a {
        min-height: 52px;
        border-radius: 16px;
        padding: 8px 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: #737997;
        background: transparent;
        font-size: 0.74rem;
        font-weight: 900;
        text-decoration: none;
        position: relative;
    }

    .admin-command-nav a.active,
    .admin-command-nav a:hover {
        color: #5b37e5;
        background: #f1edff;
        text-decoration: none;
    }

    .admin-command-nav em {
        min-width: 22px;
        height: 22px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: #ef4444;
        font-size: 0.64rem;
        font-style: normal;
        font-weight: 900;
    }

    .admin-ops-hero {
        min-height: 280px;
        border-radius: 28px;
        padding: 28px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 330px;
        gap: 24px;
        color: #fff;
        background:
            linear-gradient(135deg, rgba(16,19,51,0.92) 0%, rgba(91,55,229,0.92) 58%, rgba(15,118,110,0.9) 100%),
            url("https://images.unsplash.com/photo-1551434678-e076c223a692?auto=format&fit=crop&w=1800&q=80");
        background-size: cover;
        background-position: center;
        box-shadow: 0 24px 60px rgba(31,35,85,0.18);
        overflow: hidden;
    }

    .ops-copy {
        max-width: 760px;
        align-self: center;
    }

    .ops-eyebrow {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        border-radius: 999px;
        padding: 7px 12px;
        color: #dff7ed;
        background: rgba(255,255,255,0.14);
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .ops-copy h1 {
        max-width: 760px;
        margin: 15px 0 10px;
        font-size: 2.22rem;
        line-height: 1.08;
        font-weight: 900;
    }

    .ops-copy p {
        max-width: 640px;
        margin: 0;
        color: rgba(255,255,255,0.78);
        font-size: 0.94rem;
        font-weight: 700;
        line-height: 1.6;
    }

    .ops-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 22px;
    }

    .ops-actions .btn {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .ops-scoreboard {
        align-self: stretch;
        display: grid;
        gap: 12px;
    }

    .ops-scoreboard > div {
        min-height: 76px;
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 20px;
        padding: 18px;
        display: grid;
        align-content: center;
        background: rgba(255,255,255,0.12);
        backdrop-filter: blur(12px);
    }

    .ops-scoreboard strong {
        font-size: 2rem;
        line-height: 1;
        font-weight: 900;
    }

    .ops-scoreboard span {
        margin-top: 5px;
        color: rgba(255,255,255,0.72);
        font-size: 0.74rem;
        font-weight: 900;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 14px;
    }

    .ops-stat-card {
        min-height: 148px;
        border: 1px solid rgba(232,234,246,0.96);
        border-radius: 22px;
        padding: 18px;
        display: grid;
        align-content: start;
        gap: 8px;
        background: #fff;
        box-shadow: 0 16px 42px rgba(31,35,85,0.055);
    }

    .ops-stat-card i {
        width: 38px;
        height: 38px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #5b37e5;
        background: #f1edff;
        font-size: 1rem;
    }

    .ops-stat-card span {
        color: #737997;
        font-size: 0.68rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .ops-stat-card strong {
        color: #101333;
        font-size: 1.82rem;
        line-height: 1;
        font-weight: 900;
    }

    .ops-stat-card em {
        color: #737997;
        font-size: 0.7rem;
        font-style: normal;
        font-weight: 800;
        line-height: 1.35;
    }

    .ops-stat-card.success i { color: #047857; background: #dcfce7; }
    .ops-stat-card.warning i { color: #b45309; background: #fef3c7; }
    .ops-stat-card.danger i { color: #dc2626; background: #fee2e2; }
    .ops-stat-card.info i { color: #2563eb; background: #e7efff; }
    .ops-stat-card.violet i { color: #6d28d9; background: #f3e8ff; }

    .admin-trend-chart {
        min-height: 230px;
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        align-items: end;
        gap: 12px;
    }

    .admin-trend-item {
        display: grid;
        gap: 8px;
        text-align: center;
    }

    .admin-trend-track {
        height: 160px;
        border-radius: 999px;
        padding: 6px;
        display: flex;
        align-items: end;
        background: #f5f6ff;
    }

    .admin-trend-track span {
        width: 100%;
        min-height: 18px;
        border-radius: 999px;
        background: linear-gradient(180deg, #5b37e5 0%, #0f766e 100%);
        box-shadow: 0 10px 20px rgba(91,55,229,0.18);
    }

    .admin-trend-item strong {
        color: #101333;
        font-size: 0.72rem;
        font-weight: 900;
    }

    .admin-trend-item em {
        color: #737997;
        font-size: 0.62rem;
        font-style: normal;
        font-weight: 800;
    }

    .trend-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .trend-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #737997;
        font-size: 0.7rem;
        font-weight: 900;
    }

    .legend-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        display: inline-block;
    }

    .legend-dot.hadir { background: #5b37e5; }
    .legend-dot.late { background: #f59e0b; }
    .legend-dot.request { background: #0f766e; }

    .live-monitor-list {
        display: grid;
        gap: 10px;
    }

    .live-monitor-item {
        min-height: 72px;
        border: 1px solid #e8eaf6;
        border-radius: 18px;
        padding: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        background: #fff;
    }

    .live-avatar {
        width: 42px;
        height: 42px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: linear-gradient(135deg, #5b37e5, #2563eb);
        flex: 0 0 auto;
        font-weight: 900;
    }

    .live-monitor-item strong,
    .live-monitor-item span {
        display: block;
    }

    .live-monitor-item strong {
        overflow: hidden;
        color: #101333;
        font-size: 0.82rem;
        font-weight: 900;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .live-monitor-item span {
        overflow: hidden;
        color: #737997;
        font-size: 0.68rem;
        font-weight: 800;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .live-monitor-item em,
    .approval-pill {
        border-radius: 999px;
        padding: 7px 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.64rem;
        font-style: normal;
        font-weight: 900;
        white-space: nowrap;
    }

    .live-monitor-item em.success,
    .approval-pill.success { color: #047857; background: #dcfce7; }
    .live-monitor-item em.warning,
    .approval-pill.warning { color: #b45309; background: #fef3c7; }
    .live-monitor-item em.info,
    .approval-pill.info { color: #2563eb; background: #e7efff; }
    .live-monitor-item em.danger,
    .approval-pill.danger { color: #dc2626; background: #fee2e2; }

    .request-note {
        max-width: 320px;
        overflow: hidden;
        color: #737997;
        font-size: 0.68rem;
        font-weight: 800;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .attachment-link {
        min-height: 26px;
        border-radius: 999px;
        padding: 5px 9px;
        display: inline-flex;
        align-items: center;
        margin-top: 5px;
        color: #2563eb;
        background: #e7efff;
        font-size: 0.64rem;
        font-weight: 900;
        text-decoration: none;
    }

    .attachment-link:hover {
        color: #1d4ed8;
        text-decoration: none;
    }

    .approval-actions {
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .approval-actions form {
        margin: 0;
    }

    .approval-actions .btn {
        width: 36px;
        height: 36px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .empty-admin-panel {
        min-height: 128px;
        border: 1px dashed #d8dcf0;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #737997;
        background: #fbfbff;
        font-size: 0.78rem;
        font-weight: 900;
        text-align: center;
    }

    .employee-inline {
        min-width: 210px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .employee-avatar-img,
    .employee-avatar-fallback,
    .profile-edit-preview {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        flex: 0 0 auto;
    }

    .employee-avatar-img,
    .profile-edit-preview {
        object-fit: cover;
        background: #eef1fb;
    }

    .employee-avatar-fallback,
    .profile-edit-preview.fallback {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: linear-gradient(135deg, #5b37e5, #0f766e);
        font-weight: 900;
    }

    .profile-upload-box {
        min-height: 76px;
        border: 1px dashed rgba(91,55,229,0.35);
        border-radius: 18px;
        padding: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #101333;
        background: #fbfaff;
        cursor: pointer;
    }

    .profile-upload-box input {
        display: none;
    }

    .profile-upload-box > span {
        width: 42px;
        height: 42px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #5b37e5;
        background: #f1edff;
        flex: 0 0 auto;
    }

    .profile-upload-box strong,
    .profile-upload-box em {
        display: block;
    }

    .profile-upload-box strong {
        color: #101333;
        font-size: 0.82rem;
        font-weight: 900;
    }

    .profile-upload-box em {
        color: #737997;
        font-size: 0.66rem;
        font-style: normal;
        font-weight: 800;
        margin-top: 2px;
    }

    .profile-edit-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .form-group-default input[type="date"].form-control,
    .form-group-default input[type="time"].form-control {
        min-height: 34px;
        line-height: 34px;
        padding-right: 4px;
    }

    .level-card {
        min-height: 120px;
        border-radius: 18px;
        padding: 18px;
        display: grid;
        align-content: center;
        gap: 5px;
        border: 1px solid rgba(232,234,246,0.96);
    }

    .level-card strong,
    .level-card span,
    .level-card em {
        display: block;
    }

    .level-card strong {
        color: #101333;
        font-size: 0.78rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .level-card span {
        font-size: 1.05rem;
        font-weight: 900;
    }

    .level-card em {
        color: #737997;
        font-size: 0.72rem;
        font-style: normal;
        font-weight: 800;
    }

    .level-one { background: #f1edff; color: #5b37e5; }
    .level-two { background: #e7efff; color: #2563eb; }
    .level-three { background: #e9fbf3; color: #059669; }

    .presence-admin-actions {
        gap: 8px;
    }

    .history-count-pill {
        border-radius: 999px;
        padding: 8px 12px;
        color: #5b37e5;
        background: #f1edff;
        font-size: 0.72rem;
        font-weight: 900;
    }

    .presence-admin-actions .btn,
    .premium-card .btn {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: normal;
    }

    .employee-file-field .custom-file,
    .employee-file-field .custom-file-input,
    .employee-file-field .custom-file-label {
        height: 42px;
    }

    .employee-file-field .custom-file-label {
        margin: 0;
        border: 0;
        border-radius: 12px;
        color: #101333;
        background: #f8f9ff;
        font-size: 0.82rem;
        font-weight: 800;
        line-height: 42px;
        padding-top: 0;
        padding-bottom: 0;
    }

    .employee-file-field .custom-file-label::after {
        height: 42px;
        border: 0;
        border-radius: 0 12px 12px 0;
        color: #fff;
        background: #5b37e5;
        line-height: 42px;
        font-weight: 900;
        content: "Browse";
    }

    .attendance-photo-btn {
        width: 96px;
        min-height: 76px;
        border: 1px solid rgba(232,234,246,0.96);
        border-radius: 14px;
        padding: 6px;
        display: grid;
        gap: 5px;
        color: #101333;
        background: #fff;
        box-shadow: 0 8px 18px rgba(31,35,85,0.06);
        cursor: pointer;
    }

    .attendance-photo-btn img {
        width: 100%;
        height: 46px;
        border-radius: 10px;
        object-fit: cover;
        background: #eef1fb;
    }

    .attendance-photo-btn span {
        overflow: hidden;
        color: #059669;
        font-size: 0.62rem;
        font-weight: 900;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .attendance-photo-btn.mini {
        width: 66px;
        min-height: 62px;
        padding: 5px;
    }

    .attendance-photo-btn.mini img {
        height: 34px;
    }

    .arrival-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 0.68rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .arrival-pill.success { color: #047857; background: #dcfce7; }
    .arrival-pill.warning { color: #b45309; background: #fef3c7; }
    .arrival-pill.info { color: #235ecf; background: #e7efff; }
    .arrival-pill.danger { color: #c62828; background: #fee2e2; }

    .history-reason {
        max-width: 260px;
        color: #737997;
        font-size: 0.72rem;
        font-weight: 800;
        line-height: 1.45;
    }

    .attendance-photo-modal .modal-body {
        padding-top: 0;
    }

    .attendance-photo-full {
        width: 100%;
        max-height: 72vh;
        border-radius: 18px;
        object-fit: contain;
        background: #101333;
    }

    .attendance-photo-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }

    .attendance-photo-meta span {
        border-radius: 999px;
        padding: 8px 11px;
        color: #5b37e5;
        background: #f1edff;
        font-size: 0.72rem;
        font-weight: 900;
    }

    div.dataTables_wrapper div.dataTables_filter input,
    div.dataTables_wrapper div.dataTables_length select {
        border-radius: 999px;
        border: 1px solid #e8eaf6;
    }

    .table-responsive {
        border-radius: 16px;
    }

    @media (max-width: 575.98px) {
        .admin-command-nav {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            position: static;
        }

        .admin-command-nav a {
            min-height: 48px;
            font-size: 0.68rem;
        }

        .admin-ops-hero {
            grid-template-columns: 1fr;
            padding: 22px;
        }

        .ops-copy h1 {
            font-size: 1.55rem;
        }

        .stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .ops-stat-card {
            min-height: 146px;
        }

        .admin-trend-chart {
            gap: 8px;
        }

        .presence-admin-actions {
            width: 100%;
            margin-left: 0 !important;
            margin-top: 12px;
        }

        .presence-admin-actions .btn {
            width: 100%;
            margin-right: 0 !important;
        }

        div.dataTables_wrapper div.dataTables_filter,
        div.dataTables_wrapper div.dataTables_length {
            text-align: left;
        }
    }

    @media (min-width: 576px) and (max-width: 991.98px) {
        .admin-command-nav {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .admin-ops-hero {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (min-width: 992px) and (max-width: 1399.98px) {
        .stats-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        const initDataTable = function(selector, options = {}) {
            if ($(selector).length) {
                $(selector).DataTable(Object.assign({
                    pageLength: 8,
                    autoWidth: false,
                    language: {
                        search: 'Cari:',
                        lengthMenu: 'Tampilkan _MENU_ data',
                        info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                        infoEmpty: 'Belum ada data',
                        zeroRecords: 'Data tidak ditemukan',
                        paginate: { previous: 'Prev', next: 'Next' },
                    },
                }, options));
            }
        };

        initDataTable('#presence-request-table', { order: [[0, 'desc']] });
        initDataTable('#presence-service-request-table', { order: [[0, 'desc']] });
        initDataTable('#presence-admin-table', { pageLength: 10 });
        initDataTable('#presence-history-table', { pageLength: 10, order: [[0, 'desc']] });

        $('.admin-command-nav a').on('click', function() {
            $('.admin-command-nav a').removeClass('active');
            $(this).addClass('active');
        });

        $('.approval-actions form').on('submit', function(event) {
            event.preventDefault();
            const form = this;
            const status = $(form).find('input[name="status"]').val();
            const isApprove = status === 'approved';

            Swal.fire({
                icon: isApprove ? 'question' : 'warning',
                title: isApprove ? 'Setujui pengajuan?' : 'Tolak pengajuan?',
                text: isApprove ? 'Status akan berubah menjadi disetujui.' : 'Status akan berubah menjadi ditolak.',
                showCancelButton: true,
                confirmButtonText: isApprove ? 'Ya, setujui' : 'Ya, tolak',
                cancelButtonText: 'Batal',
                confirmButtonColor: isApprove ? '#16a34a' : '#dc2626',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        $('.employee-level-select').on('change', function() {
            const form = $(this).closest('form');
            const mode = form.find('.location-mode-select');
            const supervisorRow = form.find('.supervisor-row');
            const supervisorSelect = form.find('.supervisor-select');

            if (Number(this.value) >= 2) {
                mode.val('anywhere').prop('disabled', true);
            } else {
                mode.prop('disabled', false);
            }

            if (Number(this.value) === 1) {
                supervisorRow.show();
                supervisorSelect.prop('disabled', false);
            } else {
                supervisorRow.hide();
                supervisorSelect.val('').prop('disabled', true);
            }
        }).trigger('change');

        $('form').on('submit', function() {
            $(this).find('.location-mode-select:disabled').prop('disabled', false);
            $(this).find('.supervisor-select:disabled').prop('disabled', false);
        });

        $('.use-current-location').on('click', function() {
            const button = $(this);
            const form = button.closest('form');

            if (!navigator.geolocation) {
                Swal.fire('GPS tidak tersedia', 'Browser tidak mendukung geolocation.', 'warning');
                return;
            }

            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Membaca lokasi...');

            navigator.geolocation.getCurrentPosition(function(position) {
                form.find('.latitude-input').val(position.coords.latitude.toFixed(7));
                form.find('.longitude-input').val(position.coords.longitude.toFixed(7));
                button.prop('disabled', false).html('<i class="fas fa-location-crosshairs"></i> Pakai Lokasi Browser Admin');
            }, function() {
                button.prop('disabled', false).html('<i class="fas fa-location-crosshairs"></i> Pakai Lokasi Browser Admin');
                Swal.fire('Lokasi gagal dibaca', 'Izinkan akses lokasi di browser admin.', 'warning');
            }, { enableHighAccuracy: true, timeout: 10000 });
        });

        $('.custom-file-input').on('change', function() {
            const fileName = this.files && this.files.length ? this.files[0].name : 'Pilih file Excel / CSV';
            $(this).next('.custom-file-label').text(fileName);
        });

        $('img[data-photo-fallback="true"]').on('error', function() {
            this.onerror = null;
            this.src = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg xmlns="http://www.w3.org/2000/svg" width="240" height="180" viewBox="0 0 240 180">
                    <rect width="240" height="180" rx="22" fill="#f1edff"/>
                    <circle cx="120" cy="78" r="28" fill="#d9d2ff"/>
                    <path d="M70 145c8-28 27-43 50-43s42 15 50 43" fill="#d9d2ff"/>
                    <text x="120" y="164" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" font-weight="700" fill="#5b37e5">Foto belum tersedia</text>
                </svg>
            `);
        });

        @if($errors->any() && old('manual_form'))
            $('#createEmployeeModal').modal('show');
        @endif
    });
</script>
@endpush
