<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEmployee;
use App\Models\EmployeeAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmployeePresenceAdminController extends Controller
{
    public function showLogin()
    {
        if (Session::has('presence_admin_id')) {
            return redirect()->route('admin-presensi.index');
        }

        return view('presensi.admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $admin = User::where('username', $request->username)->first();

        if (!$admin || $admin->username !== 'admin_cluster' || !Hash::check($request->password, $admin->password)) {
            return back()->withInput()->with('error', 'Username atau password admin presensi belum sesuai.');
        }

        $request->session()->regenerate();
        Session::put('presence_admin_id', $admin->id);
        Session::put('presence_admin_username', $admin->username);

        return redirect()->route('admin-presensi.index');
    }

    public function logout(Request $request)
    {
        Session::forget(['presence_admin_id', 'presence_admin_username']);
        $request->session()->regenerateToken();

        return redirect()->route('admin-presensi.login');
    }

    public function index()
    {
        if ($redirect = $this->redirectIfNotPresenceAdmin()) {
            return $redirect;
        }

        $employees = AttendanceEmployee::with(['supervisor', 'latestAttendance'])
            ->withCount('subordinates')
            ->orderBy('employee_level')
            ->orderBy('name')
            ->get();
        $supervisors = AttendanceEmployee::whereIn('employee_level', [1, 2])
            ->orderBy('employee_level')
            ->orderBy('name')
            ->get();
        $attendanceHistory = EmployeeAttendance::with('employee')
            ->orderByDesc('attendance_date')
            ->orderByDesc('created_at')
            ->limit(150)
            ->get();

        return view('presensi.admin.index', compact('employees', 'supervisors', 'attendanceHistory'));
    }

    public function store(Request $request)
    {
        if ($redirect = $this->redirectIfNotPresenceAdmin()) {
            return $redirect;
        }

        $request->merge([
            'employee_code' => strtoupper((string) $request->input('employee_code')),
        ]);

        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:40', Rule::unique('attendance_employees', 'employee_code')],
            'name' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:120'],
            'position' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:50'],
            'work_location' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'employee_level' => ['required', 'integer', Rule::in([1, 2, 3])],
            'supervisor_id' => ['nullable', 'integer', 'exists:attendance_employees,id'],
            'attendance_location_mode' => ['required', Rule::in(['locked', 'anywhere'])],
            'attendance_location_label' => ['nullable', 'string', 'max:120'],
            'attendance_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'attendance_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'attendance_radius_meters' => ['required', 'integer', 'min:25', 'max:5000'],
            'work_start_time' => ['nullable', 'date_format:H:i'],
            'work_end_time' => ['nullable', 'date_format:H:i'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'password' => ['nullable', 'string', 'min:3', 'max:80'],
        ]);

        $validated['employee_level'] = (int) $validated['employee_level'];

        if ($validated['employee_level'] >= 2) {
            $validated['attendance_location_mode'] = 'anywhere';
        }

        $validated['supervisor_id'] = $this->validatedSupervisorId(null, $validated['employee_level'], $validated['supervisor_id'] ?? null);
        $validated['role'] = $this->roleForLevel($validated['employee_level']);
        $plainPassword = blank($validated['password'] ?? null) ? '123456' : $validated['password'];
        $validated['password'] = Hash::make($plainPassword);

        $employee = AttendanceEmployee::create($validated);

        return back()->with('success', 'Karyawan ' . $employee->name . ' berhasil ditambahkan manual. Password awal: ' . $plainPassword . '.');
    }

    public function update(Request $request, AttendanceEmployee $employee)
    {
        if ($redirect = $this->redirectIfNotPresenceAdmin()) {
            return $redirect;
        }

        $validated = $request->validate([
            'employee_level' => ['required', 'integer', Rule::in([1, 2, 3])],
            'supervisor_id' => ['nullable', 'integer', 'exists:attendance_employees,id'],
            'attendance_location_mode' => ['required', Rule::in(['locked', 'anywhere'])],
            'attendance_location_label' => ['nullable', 'string', 'max:120'],
            'attendance_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'attendance_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'attendance_radius_meters' => ['required', 'integer', 'min:25', 'max:5000'],
            'work_start_time' => ['nullable', 'date_format:H:i'],
            'work_end_time' => ['nullable', 'date_format:H:i'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'new_password' => ['nullable', 'string', 'min:3', 'max:80'],
        ]);

        if ((int) $validated['employee_level'] >= 2) {
            $validated['attendance_location_mode'] = 'anywhere';
        }

        $validated['supervisor_id'] = $this->validatedSupervisorId($employee, (int) $validated['employee_level'], $validated['supervisor_id'] ?? null);
        $validated['role'] = $this->roleForLevel((int) $validated['employee_level']);

        if (!blank($validated['new_password'] ?? null)) {
            $validated['password'] = Hash::make($validated['new_password']);
        }

        unset($validated['new_password']);

        $employee->update($validated);

        return back()->with('success', 'Pengaturan presensi ' . $employee->name . ' berhasil disimpan.');
    }

    public function template()
    {
        if ($redirect = $this->redirectIfNotPresenceAdmin()) {
            return $redirect;
        }

        $headers = [
            'employee_code',
            'name',
            'department',
            'position',
            'phone',
            'level',
            'supervisor_code',
            'work_location',
            'location_mode',
            'location_label',
            'latitude',
            'longitude',
            'radius_meters',
            'work_start',
            'work_end',
            'late_tolerance',
            'password',
            'status',
        ];

        $rows = [
            ['EMP-GM01', 'Nama GM', 'OPERASIONAL', 'General Manager', '081200000001', '1', '', 'DUMAI', 'locked', 'Kantor Dumai', '-1.1234567', '101.1234567', '150', '08:00', '16:30', '15', '123456', 'active'],
            ['EMP-SPV01', 'Nama SPV', 'OPERASIONAL', 'Supervisor', '081200000002', '2', '', 'DUMAI', 'anywhere', '', '', '', '150', '08:00', '16:30', '15', '123456', 'active'],
            ['EMP-SLS01', 'Nama Sales', 'SALES', 'Sales', '081200000003', '3', 'EMP-SPV01', 'DUMAI', 'anywhere', '', '', '', '150', '08:00', '16:30', '15', '123456', 'active'],
        ];

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'template-upload-karyawan-presensi.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(Request $request)
    {
        if ($redirect = $this->redirectIfNotPresenceAdmin()) {
            return $redirect;
        }

        $request->validate([
            'employee_file' => ['required', 'file', 'max:8192', 'mimes:xlsx,xls,csv,txt'],
        ]);

        $result = $this->importEmployeesFromFile($request->file('employee_file')->getRealPath());

        return back()->with('success', "Upload selesai. Baru: {$result['created']}, update: {$result['updated']}, dilewati: {$result['skipped']}.");
    }

    public function attendancePhoto(EmployeeAttendance $attendance, string $variant = 'thumb')
    {
        if (!Session::has('presence_admin_id')) {
            abort(403);
        }

        $isFull = $variant === 'full';
        $path = $isFull
            ? $attendance->face_photo_path
            : ($attendance->face_thumbnail_path ?: $attendance->face_photo_path);

        if (!$path || !Storage::disk('public')->exists($path)) {
            $path = $attendance->face_photo_path;
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            return $this->photoPlaceholderResponse();
        }

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function redirectIfNotPresenceAdmin()
    {
        if (Session::has('presence_admin_id')) {
            return null;
        }

        return redirect()->route('admin-presensi.login')->with('error', 'Silakan login admin presensi terlebih dahulu.');
    }

    private function photoPlaceholderResponse()
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="240" height="180" viewBox="0 0 240 180">
  <rect width="240" height="180" rx="22" fill="#f1edff"/>
  <circle cx="120" cy="78" r="28" fill="#d9d2ff"/>
  <path d="M70 145c8-28 27-43 50-43s42 15 50 43" fill="#d9d2ff"/>
  <text x="120" y="164" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" font-weight="700" fill="#5b37e5">Foto belum tersedia</text>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function validatedSupervisorId(?AttendanceEmployee $employee, int $level, mixed $supervisorId): ?int
    {
        if ($level !== 3 || blank($supervisorId)) {
            return null;
        }

        if ($employee && (int) $supervisorId === (int) $employee->id) {
            throw ValidationException::withMessages([
                'supervisor_id' => 'Atasan tidak boleh diri sendiri.',
            ]);
        }

        $supervisor = AttendanceEmployee::find($supervisorId);
        if (!$supervisor || !in_array((int) $supervisor->employee_level, [1, 2], true)) {
            throw ValidationException::withMessages([
                'supervisor_id' => 'Atasan level 3 harus GM, SPV, atau Manager.',
            ]);
        }

        return (int) $supervisorId;
    }

    private function importEmployeesFromFile(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $headings = [];

        for ($column = 1; $column <= $highestColumn; $column++) {
            $heading = $this->normalizeHeading((string) $sheet->getCellByColumnAndRow($column, 1)->getValue());
            if ($heading !== '') {
                $headings[$heading] = $column;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $supervisorLinks = [];

        DB::transaction(function () use ($sheet, $highestRow, $headings, &$created, &$updated, &$skipped, &$supervisorLinks) {
            for ($row = 2; $row <= $highestRow; $row++) {
                $employeeCode = strtoupper($this->cellValue($sheet, $row, $headings, ['employee_code', 'kode_karyawan', 'nip', 'nik']));
                $name = $this->cellValue($sheet, $row, $headings, ['name', 'nama', 'nama_karyawan']);

                if ($employeeCode === '' && $name === '') {
                    continue;
                }

                if ($employeeCode === '' || $name === '') {
                    $skipped++;
                    continue;
                }

                $level = $this->parseLevel($this->cellValue($sheet, $row, $headings, ['level', 'employee_level', 'level_karyawan']));
                $locationMode = $this->cellValue($sheet, $row, $headings, ['location_mode', 'attendance_location_mode', 'mode_lokasi']);
                $password = $this->cellValue($sheet, $row, $headings, ['password', 'pass']);
                $employee = AttendanceEmployee::where('employee_code', $employeeCode)->first();
                $isNew = !$employee;

                $payload = [
                    'employee_code' => $employeeCode,
                    'name' => $name,
                    'department' => $this->nullableCell($sheet, $row, $headings, ['department', 'departemen', 'divisi']),
                    'position' => $this->nullableCell($sheet, $row, $headings, ['position', 'jabatan']),
                    'phone' => $this->nullableCell($sheet, $row, $headings, ['phone', 'hp', 'no_hp', 'telepon']),
                    'employee_level' => $level,
                    'work_location' => $this->nullableCell($sheet, $row, $headings, ['work_location', 'lokasi_kerja']),
                    'attendance_location_mode' => $level >= 2 ? 'anywhere' : $this->parseLocationMode($locationMode),
                    'attendance_location_label' => $this->nullableCell($sheet, $row, $headings, ['location_label', 'attendance_location_label', 'label_lokasi']),
                    'attendance_latitude' => $this->nullableNumericCell($sheet, $row, $headings, ['latitude', 'lat', 'attendance_latitude']),
                    'attendance_longitude' => $this->nullableNumericCell($sheet, $row, $headings, ['longitude', 'lng', 'long', 'attendance_longitude']),
                    'attendance_radius_meters' => $this->intCell($sheet, $row, $headings, ['radius_meters', 'radius', 'attendance_radius_meters'], 150),
                    'work_start_time' => $this->timeCell($sheet, $row, $headings, ['work_start', 'jam_masuk', 'work_start_time'], '08:00'),
                    'work_end_time' => $this->timeCell($sheet, $row, $headings, ['work_end', 'jam_pulang', 'work_end_time'], '16:30'),
                    'late_tolerance_minutes' => $this->intCell($sheet, $row, $headings, ['late_tolerance', 'toleransi_telat', 'late_tolerance_minutes'], 15),
                    'status' => $this->nullableCell($sheet, $row, $headings, ['status']) ?: 'active',
                    'role' => $this->roleForLevel($level),
                ];

                if ($password !== '') {
                    $payload['password'] = Hash::make($password);
                } elseif ($isNew) {
                    $payload['password'] = Hash::make('123456');
                }

                $employee = AttendanceEmployee::updateOrCreate(['employee_code' => $employeeCode], $payload);
                $supervisorLinks[$employeeCode] = strtoupper($this->cellValue($sheet, $row, $headings, ['supervisor_code', 'kode_atasan', 'atasan']));
                $isNew ? $created++ : $updated++;
            }

            foreach ($supervisorLinks as $employeeCode => $supervisorCode) {
                $employee = AttendanceEmployee::where('employee_code', $employeeCode)->first();
                if (!$employee) {
                    continue;
                }

                if ((int) $employee->employee_level !== 3 || $supervisorCode === '') {
                    $employee->update(['supervisor_id' => null]);
                    continue;
                }

                $supervisor = AttendanceEmployee::where('employee_code', $supervisorCode)
                    ->whereIn('employee_level', [1, 2])
                    ->first();

                if ($supervisor && $supervisor->id !== $employee->id) {
                    $employee->update(['supervisor_id' => $supervisor->id]);
                }
            }
        });

        return compact('created', 'updated', 'skipped');
    }

    private function cellValue($sheet, int $row, array $headings, array $aliases): string
    {
        foreach ($aliases as $alias) {
            $key = $this->normalizeHeading($alias);
            if (isset($headings[$key])) {
                return trim((string) $sheet->getCellByColumnAndRow($headings[$key], $row)->getFormattedValue());
            }
        }

        return '';
    }

    private function nullableCell($sheet, int $row, array $headings, array $aliases): ?string
    {
        $value = $this->cellValue($sheet, $row, $headings, $aliases);

        return $value === '' ? null : $value;
    }

    private function nullableNumericCell($sheet, int $row, array $headings, array $aliases): ?float
    {
        $value = str_replace(',', '.', $this->cellValue($sheet, $row, $headings, $aliases));

        return is_numeric($value) ? (float) $value : null;
    }

    private function intCell($sheet, int $row, array $headings, array $aliases, int $default): int
    {
        $value = $this->cellValue($sheet, $row, $headings, $aliases);

        return is_numeric($value) ? (int) $value : $default;
    }

    private function timeCell($sheet, int $row, array $headings, array $aliases, string $default): ?string
    {
        $value = $this->cellValue($sheet, $row, $headings, $aliases);
        if ($value === '') {
            return $default;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})/', $value, $matches)) {
            return str_pad($matches[1], 2, '0', STR_PAD_LEFT) . ':' . $matches[2];
        }

        return $default;
    }

    private function normalizeHeading(string $heading): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($heading)), '_');
    }

    private function parseLevel(string $value): int
    {
        $normalized = $this->normalizeHeading($value);

        return match (true) {
            $normalized === '2' || str_contains($normalized, 'spv') || str_contains($normalized, 'supervisor') || str_contains($normalized, 'manager') => 2,
            $normalized === '3' || str_contains($normalized, 'admin') || str_contains($normalized, 'sales') => 3,
            default => 1,
        };
    }

    private function parseLocationMode(string $value): string
    {
        $normalized = $this->normalizeHeading($value);

        return in_array($normalized, ['anywhere', 'bebas', 'semua_lokasi'], true) ? 'anywhere' : 'locked';
    }

    private function roleForLevel(int $level): string
    {
        return match ($level) {
            1 => 'gm',
            2 => 'spv_manager',
            3 => 'staff',
            default => 'employee',
        };
    }
}
