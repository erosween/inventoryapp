<?php

namespace App\Http\Controllers;

use App\Models\MobileAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MobileAttendanceController extends Controller
{
    private array $types = [
        'hadir' => 'Kehadiran',
        'cuti' => 'Cuti',
        'terlambat' => 'Izin Terlambat Masuk',
        'cepat_pulang' => 'Izin Cepat Pulang',
        'sakit' => 'Sakit',
    ];

    public function index()
    {
        $idsf = session('mobile_sf_id');
        $today = now()->toDateString();

        $todayAttendance = MobileAttendance::where('idsf', $idsf)
            ->whereDate('attendance_date', $today)
            ->latest()
            ->first();

        $monthRows = MobileAttendance::where('idsf', $idsf)
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

        return view('mobile.attendance', [
            'todayAttendance' => $todayAttendance,
            'history' => $monthRows->take(14),
            'summary' => $summary,
            'types' => $this->types,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'attendance_type' => ['required', Rule::in(array_keys($this->types))],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'face_image' => ['nullable', 'string'],
            'face_confidence' => ['nullable', 'integer', 'min:0', 'max:100'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $type = $validated['attendance_type'];
        $now = now();
        $idsf = session('mobile_sf_id');

        if (in_array($type, ['cuti', 'terlambat', 'cepat_pulang', 'sakit'], true) && blank($request->reason)) {
            return back()->withInput()->with('error', 'Keterangan wajib diisi untuk cuti, izin, atau sakit.');
        }

        $photoPath = $this->storeFaceImage($request->input('face_image'), $idsf);

        $attendance = MobileAttendance::firstOrNew([
            'idsf' => $idsf,
            'attendance_date' => $now->toDateString(),
        ]);

        $attendance->fill([
            'idtap' => session('idtap'),
            'attendance_type' => $type,
            'status' => $type === 'hadir' ? 'verified' : 'submitted',
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'face_confidence' => (int) $request->input('face_confidence', $photoPath ? 85 : 0),
            'reason' => $request->input('reason'),
        ]);

        if ($photoPath) {
            $attendance->face_photo_path = $photoPath;
        }

        if ($type === 'hadir') {
            $attendance->check_in_at ??= $now;
        }

        if ($type === 'cepat_pulang' && empty($attendance->check_out_at)) {
            $attendance->check_out_at = $now;
        }

        $attendance->save();

        return redirect()->route('mobile.attendance')->with('success', 'Presensi berhasil disimpan.');
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'face_image' => ['nullable', 'string'],
            'face_confidence' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $idsf = session('mobile_sf_id');
        $attendance = MobileAttendance::where('idsf', $idsf)
            ->whereDate('attendance_date', now()->toDateString())
            ->first();

        if (!$attendance || !$attendance->check_in_at) {
            return back()->with('error', 'Clock in dulu sebelum clock out.');
        }

        $photoPath = $this->storeFaceImage($request->input('face_image'), $idsf);

        $attendance->update([
            'check_out_at' => now(),
            'latitude' => $request->input('latitude', $attendance->latitude),
            'longitude' => $request->input('longitude', $attendance->longitude),
            'face_photo_path' => $photoPath ?: $attendance->face_photo_path,
            'face_confidence' => (int) $request->input('face_confidence', $attendance->face_confidence),
            'status' => 'completed',
        ]);

        return redirect()->route('mobile.attendance')->with('success', 'Clock out berhasil.');
    }

    private function storeFaceImage(?string $image, string $idsf): ?string
    {
        if (!$image || !str_starts_with($image, 'data:image/')) {
            return null;
        }

        [$meta, $payload] = explode(',', $image, 2);
        $extension = str_contains($meta, 'image/png') ? 'png' : 'jpg';
        $binary = base64_decode($payload, true);

        if ($binary === false) {
            return null;
        }

        $safeIdsf = preg_replace('/[^A-Za-z0-9_-]/', '_', $idsf);
        $path = 'attendance/face/' . $safeIdsf . '-' . now()->format('YmdHis') . '.' . $extension;
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
