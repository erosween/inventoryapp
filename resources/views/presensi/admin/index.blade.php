@extends('presensi.admin.layout')

@section('title', 'Dashboard Admin Presensi')

@section('content')
<div class="main-panel">
    <div class="content">
        <div class="page-inner">
            <div class="page-header">
                <h4 class="page-title">Pengaturan Presensi</h4>
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

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-round border-0 shadow-sm text-white mb-4" style="background: linear-gradient(135deg, #5b37e5 0%, #2f1aa8 100%);">
                        <div class="card-body py-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <div class="text-white-50 font-weight-bold text-uppercase small mb-1">Super Admin</div>
                                    <h2 class="font-weight-bold mb-1">Lokasi & Jam Kerja Presensi</h2>
                                    <p class="mb-0 text-white-50">Level 1 GM. Level 2 SPV/Manager. Level 3 Admin/Sales bisa dipilihkan atasan.</p>
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
                                        <span>GM</span>
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
                                        <span>Admin / Sales</span>
                                        <em>{{ $employees->where('employee_level', 3)->count() }} orang</em>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-light border mb-0 small font-weight-bold text-muted">
                                Atasan bisa dipilih untuk karyawan Level 3. Pilihan atasan berasal dari Level 1 dan Level 2.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
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
                                            <th>Foto</th>
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
                                            @php($thumbPath = $latestAttendance?->face_thumbnail_path ?: $photoPath)
                                            <tr>
                                                <td>
                                                    <div class="font-weight-bold">{{ $employee->name }}</div>
                                                    <div class="small text-muted">{{ $employee->employee_code }} - {{ $employee->department ?? '-' }}</div>
                                                </td>
                                                <td>
                                                    @if($photoPath)
                                                        <button type="button" class="attendance-photo-btn" data-toggle="modal" data-target="#attendancePhoto{{ $employee->id }}">
                                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($thumbPath) }}" alt="Foto presensi {{ $employee->name }}" loading="lazy">
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
                <form action="{{ route('admin-presensi.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="manual_form" value="1">
                    <div class="modal-body">
                        <p class="small text-muted">Isi data utama karyawan, pilih level, lalu tentukan atasan untuk Level 3 jika diperlukan.</p>

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
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Level Karyawan</label>
                                    <select class="form-control employee-level-select" name="employee_level" required>
                                        <option value="1" @selected((int) old('employee_level', 3) === 1)>Level 1 - GM</option>
                                        <option value="2" @selected((int) old('employee_level', 3) === 2)>Level 2 - SPV / Manager</option>
                                        <option value="3" @selected((int) old('employee_level', 3) === 3)>Level 3 - Admin / Sales</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group form-group-default">
                                    <label>Mode Lokasi</label>
                                    <select class="form-control location-mode-select" name="attendance_location_mode" required>
                                        <option value="locked" @selected(old('attendance_location_mode') === 'locked')>Lock Lokasi</option>
                                        <option value="anywhere" @selected(old('attendance_location_mode', 'anywhere') === 'anywhere')>Semua Lokasi</option>
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
                                    <label>Atasan Level 3</label>
                                    <select class="form-control supervisor-select" name="supervisor_id">
                                        <option value="">Pilih atasan...</option>
                                        @foreach($supervisors as $supervisor)
                                            <option value="{{ $supervisor->id }}" @selected((int) old('supervisor_id') === $supervisor->id)>
                                                Level {{ $supervisor->employee_level }} - {{ $supervisor->name }} ({{ $supervisor->employee_code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="small text-muted font-weight-bold mb-2">Untuk Level 3, atasan bisa GM, SPV, atau Manager.</div>
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
                            <form action="{{ route('admin-presensi.update', $employee) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p class="small text-muted">Level 1 GM. Level 2 SPV/Manager. Level 3 Admin/Sales bisa dipilihkan atasan dari level 1 atau 2.</p>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group form-group-default">
                                        <label>Level Karyawan</label>
                                        <select class="form-control employee-level-select" name="employee_level" required>
                                            <option value="1" @selected(($employee->employee_level ?? 1) === 1)>Level 1 - Lock/Admin</option>
                                            <option value="2" @selected(($employee->employee_level ?? 1) === 2)>Level 2 - SPV / Manager</option>
                                            <option value="3" @selected(($employee->employee_level ?? 1) === 3)>Level 3 - Admin / Sales</option>
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
                                        <label>Atasan Level 3</label>
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
                                    <div class="small text-muted font-weight-bold mb-2">Field ini aktif untuk Level 3. Level 1 dan 2 otomatis tidak memakai atasan di aplikasi ini.</div>
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
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($employee->latestAttendance->face_photo_path) }}" alt="Foto presensi {{ $employee->name }}" class="attendance-photo-full">
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
@endpush

@push('styles')
<style>
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
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        $('#presence-admin-table').DataTable({ pageLength: 10 });

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

            if (Number(this.value) === 3) {
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

        @if($errors->any() && old('manual_form'))
            $('#createEmployeeModal').modal('show');
        @endif
    });
</script>
@endpush
