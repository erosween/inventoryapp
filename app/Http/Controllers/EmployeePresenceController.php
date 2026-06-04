<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEmployee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeFaceEnrollment;
use App\Services\FaceSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeePresenceController extends Controller
{
    private const FACE_MATCH_THRESHOLD = 72;

    private array $types = [
        'hadir' => 'Kehadiran',
        'cuti' => 'Cuti',
        'terlambat' => 'Izin Terlambat Masuk',
        'cepat_pulang' => 'Izin Cepat Pulang',
        'sakit' => 'Sakit',
    ];

    public function __construct(private FaceSignatureService $faces)
    {
    }

    public function index()
    {
        $employee = $this->employee();

        $todayAttendance = EmployeeAttendance::where('attendance_employee_id', $employee->id)
            ->whereDate('attendance_date', now()->toDateString())
            ->latest()
            ->first();

        $monthRows = EmployeeAttendance::where('attendance_employee_id', $employee->id)
            ->whereMonth('attendance_date', now()->month)
            ->whereYear('attendance_date', now()->year)
            ->orderByDesc('attendance_date')
            ->orderByDesc('created_at')
            ->get();

        $summary = [
            'hadir' => $monthRows->where('attendance_type', 'hadir')->count(),
            'cuti' => $monthRows->where('attendance_type', 'cuti')->count(),
            'izin' => $monthRows->whereIn('attendance_type', ['terlambat', 'cepat_pulang'])->count(),
            'sakit' => $monthRows->where('attendance_type', 'sakit')->count(),
        ];
        $monthRows->each(fn ($row) => $row->setRelation('employee', $employee));
        if ($todayAttendance) {
            $todayAttendance->setRelation('employee', $employee);
        }
        $dashboardInsights = $this->dashboardInsights($employee, $monthRows);
        $lateNotice = $this->lateNotice($employee, $todayAttendance);

        return view('presensi.index', [
            'employee' => $employee,
            'activeEnrollment' => $employee->activeFaceEnrollment,
            'todayAttendance' => $todayAttendance,
            'history' => $monthRows->take(14),
            'summary' => $summary,
            'dashboardInsights' => $dashboardInsights,
            'lateNotice' => $lateNotice,
            'types' => $this->types,
            'faceThreshold' => self::FACE_MATCH_THRESHOLD,
            'locationPolicy' => $employee->attendanceLocationPolicy(),
        ]);
    }

    public function history()
    {
        $employee = $this->employee();

        $monthRows = EmployeeAttendance::where('attendance_employee_id', $employee->id)
            ->whereMonth('attendance_date', now()->month)
            ->whereYear('attendance_date', now()->year)
            ->orderByDesc('attendance_date')
            ->orderByDesc('created_at')
            ->get();

        $summary = [
            'hadir' => $monthRows->where('attendance_type', 'hadir')->count(),
            'cuti' => $monthRows->where('attendance_type', 'cuti')->count(),
            'izin' => $monthRows->whereIn('attendance_type', ['terlambat', 'cepat_pulang'])->count(),
            'sakit' => $monthRows->where('attendance_type', 'sakit')->count(),
        ];

        return view('presensi.history', [
            'employee' => $employee,
            'history' => $monthRows,
            'summary' => $summary,
            'types' => $this->types,
        ]);
    }

    public function enroll(Request $request)
    {
        $request->validate([
            'face_image' => ['required', 'string'],
        ]);

        $employee = $this->employee();
        $signature = $this->faces->signatureFromDataUrl($request->face_image);

        if (!$signature) {
            return back()->with('error', 'Foto wajah belum valid. Coba capture ulang dengan cahaya lebih terang.');
        }

        $path = $this->faces->storeImage($request->face_image, 'attendance/employees/enrollments', $employee->employee_code);

        if (!$path) {
            return back()->with('error', 'Foto enrollment gagal disimpan.');
        }

        DB::transaction(function () use ($employee, $signature, $path) {
            EmployeeFaceEnrollment::where('attendance_employee_id', $employee->id)
                ->where('status', 'active')
                ->update(['status' => 'retired']);

            EmployeeFaceEnrollment::create([
                'attendance_employee_id' => $employee->id,
                'image_path' => $path,
                'signature_json' => json_encode($signature),
                'signature_hash' => $this->faces->hash($signature),
                'status' => 'active',
                'enrolled_at' => now(),
            ]);

            $employee->update(['face_enrolled_at' => now()]);
        });

        return redirect()->route('presensi.index')->with('success', 'Wajah awal berhasil didaftarkan.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'attendance_type' => ['required', Rule::in(array_keys($this->types))],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'face_image' => ['nullable', 'string'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = $this->employee();
        $type = $request->attendance_type;

        if (in_array($type, ['cuti', 'sakit', 'terlambat', 'cepat_pulang'], true) && blank($request->reason)) {
            return back()->withInput()->with('error', 'Keterangan wajib diisi untuk cuti, sakit, atau izin.');
        }

        $faceResult = null;
        $locationResult = null;
        if (in_array($type, ['hadir', 'terlambat', 'cepat_pulang'], true)) {
            $faceResult = $this->verifyFace($employee, $request->face_image);
            if (!$faceResult['passed']) {
                return back()->withInput()->with('error', $faceResult['message']);
            }

            $locationResult = $this->verifyLocation($employee, $request->latitude, $request->longitude);
            if (!$locationResult['passed']) {
                return back()->withInput()->with('error', $locationResult['message']);
            }
        }

        $attendance = EmployeeAttendance::firstOrNew([
            'attendance_employee_id' => $employee->id,
            'attendance_date' => now()->toDateString(),
        ]);

        $attendance->fill([
            'attendance_type' => $type,
            'status' => in_array($type, ['hadir', 'terlambat', 'cepat_pulang'], true) ? 'verified' : 'submitted',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'reason' => $request->reason,
        ]);

        if ($faceResult) {
            $attendance->face_photo_path = $faceResult['path'];
            $attendance->face_thumbnail_path = $faceResult['thumbnail_path'];
            $attendance->face_match_score = $faceResult['score'];
            $attendance->face_signature_hash = $faceResult['hash'];
        }

        if (in_array($type, ['hadir', 'terlambat'], true)) {
            $attendance->check_in_at ??= now();
        }

        if ($type === 'cepat_pulang') {
            $attendance->check_out_at ??= now();
        }

        $attendance->save();

        $matchMessage = $faceResult ? ' Match wajah server: ' . $faceResult['score'] . '%.' : '';
        $locationMessage = $locationResult && isset($locationResult['distance'])
            ? ' Lokasi valid: ' . round($locationResult['distance']) . 'm.'
            : '';

        return redirect()->route('presensi.index')->with('success', 'Presensi berhasil disimpan.' . $matchMessage . $locationMessage);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'face_image' => ['required', 'string'],
        ]);

        $employee = $this->employee();
        $attendance = EmployeeAttendance::where('attendance_employee_id', $employee->id)
            ->whereDate('attendance_date', now()->toDateString())
            ->first();

        if (!$attendance || !$attendance->check_in_at) {
            return back()->with('error', 'Clock in dulu sebelum clock out.');
        }

        $faceResult = $this->verifyFace($employee, $request->face_image);
        if (!$faceResult['passed']) {
            return back()->with('error', $faceResult['message']);
        }

        $locationResult = $this->verifyLocation($employee, $request->latitude, $request->longitude);
        if (!$locationResult['passed']) {
            return back()->with('error', $locationResult['message']);
        }

        $attendance->update([
            'check_out_at' => now(),
            'latitude' => $request->input('latitude', $attendance->latitude),
            'longitude' => $request->input('longitude', $attendance->longitude),
            'face_photo_path' => $faceResult['path'],
            'face_thumbnail_path' => $faceResult['thumbnail_path'],
            'face_match_score' => $faceResult['score'],
            'face_signature_hash' => $faceResult['hash'],
            'status' => 'completed',
        ]);

        $locationMessage = isset($locationResult['distance'])
            ? ' Lokasi valid: ' . round($locationResult['distance']) . 'm.'
            : '';

        return redirect()->route('presensi.index')->with('success', 'Clock out berhasil. Match wajah server: ' . $faceResult['score'] . '%.' . $locationMessage);
    }

    private function employee(): AttendanceEmployee
    {
        return AttendanceEmployee::with('activeFaceEnrollment')->findOrFail(session('attendance_employee_id'));
    }

    private function verifyFace(AttendanceEmployee $employee, ?string $image): array
    {
        $enrollment = $employee->activeFaceEnrollment;
        if (!$enrollment) {
            return [
                'passed' => false,
                'message' => 'Wajah awal belum didaftarkan. Lakukan enrollment wajah terlebih dahulu.',
            ];
        }

        if (!$image) {
            return [
                'passed' => false,
                'message' => 'Capture wajah wajib dilakukan sebelum presensi.',
            ];
        }

        $signature = $this->faces->signatureFromDataUrl($image);
        if (!$signature) {
            return [
                'passed' => false,
                'message' => 'Foto wajah belum valid. Coba capture ulang.',
            ];
        }

        $score = $this->faces->similarity($enrollment->signature(), $signature);
        if ($score < self::FACE_MATCH_THRESHOLD) {
            return [
                'passed' => false,
                'message' => "Wajah tidak cocok dengan enrollment awal. Skor match {$score}%, minimal " . self::FACE_MATCH_THRESHOLD . '%.',
            ];
        }

        $stored = $this->faces->storeCompressedImage($image, 'attendance/employees/checks', $employee->employee_code, 960, 82, 240);
        if (!$stored) {
            return [
                'passed' => false,
                'message' => 'Foto wajah gagal disimpan. Coba capture ulang.',
            ];
        }

        return [
            'passed' => true,
            'score' => $score,
            'hash' => $this->faces->hash($signature),
            'path' => $stored['path'],
            'thumbnail_path' => $stored['thumbnail_path'],
        ];
    }

    private function dashboardInsights(AttendanceEmployee $employee, $monthRows): array
    {
        $workedRows = $monthRows->filter(fn ($row) => !empty($row->check_in_at));
        $lateRows = $workedRows->filter(fn ($row) => $row->lateMinutes() > 0 || $row->attendance_type === 'terlambat');
        $completedRows = $workedRows->filter(fn ($row) => !empty($row->check_out_at));
        $requestRows = $monthRows->whereIn('attendance_type', ['cuti', 'sakit', 'cepat_pulang']);
        $onTimeCount = max(0, $workedRows->count() - $lateRows->count());

        $trend = collect(range(6, 0))->map(function (int $daysAgo) use ($monthRows) {
            $date = now()->copy()->subDays($daysAgo);
            $row = $monthRows->first(fn ($item) => $item->attendance_date?->isSameDay($date));

            if (!$row) {
                return [
                    'day' => $date->locale('id')->translatedFormat('D'),
                    'date' => $date->format('d M'),
                    'label' => '-',
                    'class' => 'empty',
                    'height' => 18,
                ];
            }

            $arrival = $row->arrivalStatus();
            $isRequest = in_array($row->attendance_type, ['cuti', 'sakit'], true);
            $class = $isRequest ? 'info' : $arrival['class'];

            return [
                'day' => $date->locale('id')->translatedFormat('D'),
                'date' => $date->format('d M'),
                'label' => $arrival['label'],
                'class' => $class,
                'height' => match ($class) {
                    'success' => 92,
                    'warning' => 66,
                    'info' => 46,
                    default => 24,
                },
            ];
        });

        return [
            'on_time' => $onTimeCount,
            'late' => $lateRows->count(),
            'requests' => $requestRows->count(),
            'completion_rate' => $workedRows->count() > 0 ? (int) round(($completedRows->count() / $workedRows->count()) * 100) : 0,
            'trend' => $trend,
            'schedule' => $employee->scheduleLabel(),
        ];
    }

    private function lateNotice(AttendanceEmployee $employee, ?EmployeeAttendance $todayAttendance): array
    {
        $start = now()->copy()->setTimeFromTimeString($employee->work_start_time ? substr((string) $employee->work_start_time, 0, 5) : '08:30');
        $limit = $start->copy()->addMinutes((int) ($employee->late_tolerance_minutes ?? 15));

        if ($todayAttendance?->check_in_at) {
            $lateMinutes = $todayAttendance->lateMinutes();
            if ($lateMinutes > 0) {
                return [
                    'class' => 'warning',
                    'icon' => 'fa-clock',
                    'title' => 'Terlambat ' . $lateMinutes . ' menit',
                    'description' => 'Check in pukul ' . $todayAttendance->check_in_at->format('H:i') . ', melewati batas toleransi ' . $limit->format('H:i') . '.',
                ];
            }

            return [
                'class' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Check in tepat waktu',
                'description' => 'Masuk pukul ' . $todayAttendance->check_in_at->format('H:i') . ', jadwal kerja ' . $employee->scheduleLabel() . '.',
            ];
        }

        if (now()->greaterThan($limit)) {
            return [
                'class' => 'danger',
                'icon' => 'fa-triangle-exclamation',
                'title' => 'Sudah melewati toleransi',
                'description' => 'Batas toleransi ' . $limit->format('H:i') . '. Keterlambatan berjalan sekitar ' . (int) round($limit->diffInMinutes(now())) . ' menit.',
            ];
        }

        if (now()->greaterThan($start)) {
            return [
                'class' => 'warning',
                'icon' => 'fa-hourglass-half',
                'title' => 'Masuk masa toleransi',
                'description' => 'Jadwal masuk ' . $start->format('H:i') . '. Toleransi sampai ' . $limit->format('H:i') . '.',
            ];
        }

        return [
            'class' => 'info',
            'icon' => 'fa-business-time',
            'title' => 'Siap check in',
            'description' => 'Jadwal masuk hari ini ' . $start->format('H:i') . ', toleransi ' . ($employee->late_tolerance_minutes ?? 15) . ' menit.',
        ];
    }

    private function verifyLocation(AttendanceEmployee $employee, mixed $latitude, mixed $longitude): array
    {
        if ($employee->canAttendAnywhere()) {
            return [
                'passed' => true,
                'message' => 'Karyawan bebas lokasi.',
            ];
        }

        if (!$employee->hasAttendanceLocationTarget()) {
            return [
                'passed' => true,
                'message' => 'Lock lokasi belum diset admin.',
            ];
        }

        if ($latitude === null || $latitude === '' || $longitude === null || $longitude === '') {
            return [
                'passed' => false,
                'message' => 'Lokasi browser belum terbaca. Izinkan akses lokasi lalu coba presensi lagi.',
            ];
        }

        $distance = $employee->distanceToAttendanceLocation((float) $latitude, (float) $longitude);
        $radius = $employee->attendance_radius_meters ?: 150;

        if ($distance !== null && $distance > $radius) {
            return [
                'passed' => false,
                'message' => 'Lokasi di luar area presensi. Jarak ' . round($distance) . 'm, maksimal ' . $radius . 'm dari titik kantor.',
                'distance' => $distance,
            ];
        }

        return [
            'passed' => true,
            'message' => 'Lokasi valid.',
            'distance' => $distance,
        ];
    }
}
