<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEmployee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeePresenceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeePresenceMenuController extends Controller
{
    private array $modules = [
        'cuti' => [
            'title' => 'Pengajuan Cuti',
            'subtitle' => 'Ajukan periode cuti dan delegasi tugas.',
            'icon' => 'fa-calendar-check',
            'accent' => '#6366f1',
            'categories' => ['Tahunan', 'Melahirkan', 'Menikah', 'Keluarga', 'Lainnya'],
        ],
        'ubah-kehadiran' => [
            'title' => 'Ubah Kehadiran',
            'subtitle' => 'Koreksi jam masuk, jam keluar, atau tanggal absen.',
            'icon' => 'fa-pen-to-square',
            'accent' => '#f59e0b',
            'categories' => ['Lupa absen masuk', 'Lupa absen keluar', 'Salah jam', 'Kendala aplikasi'],
        ],
        'lembur' => [
            'title' => 'Pengajuan Lembur',
            'subtitle' => 'Input tanggal, durasi, dan kompensasi lembur.',
            'icon' => 'fa-business-time',
            'accent' => '#0f766e',
            'categories' => ['Dibayar', 'Pengganti cuti', 'Project khusus'],
        ],
        'reimbursement' => [
            'title' => 'Reimbursement',
            'subtitle' => 'Ajukan klaim biaya dengan lampiran opsional.',
            'icon' => 'fa-receipt',
            'accent' => '#2563eb',
            'categories' => ['Transport', 'Makan', 'Pulsa/Data', 'Operasional', 'Lainnya'],
        ],
        'pengeluaran' => [
            'title' => 'Pengeluaran',
            'subtitle' => 'Catat pengeluaran operasional untuk approval.',
            'icon' => 'fa-wallet',
            'accent' => '#db2777',
            'categories' => ['Operasional', 'Promosi', 'Transport', 'Maintenance', 'Lainnya'],
        ],
    ];

    public function dashboard()
    {
        $employee = $this->employee();
        $recentRequests = EmployeePresenceRequest::where('attendance_employee_id', $employee->id)
            ->latest()
            ->take(5)
            ->get();
        $leaderPendingCount = EmployeePresenceRequest::whereHas('employee', fn ($query) => $query->where('supervisor_id', $employee->id))
            ->where('status', 'pending')
            ->count();
        $leaderAttendancePendingCount = EmployeeAttendance::whereHas('employee', fn ($query) => $query->where('supervisor_id', $employee->id))
            ->whereIn('attendance_type', ['cuti', 'sakit', 'terlambat', 'cepat_pulang'])
            ->whereIn('status', ['submitted', 'pending'])
            ->count();

        return view('presensi.menu', [
            'employee' => $employee,
            'modules' => $this->modules,
            'recentRequests' => $recentRequests,
            'pendingCount' => $recentRequests->where('status', 'pending')->count() + $leaderPendingCount + $leaderAttendancePendingCount,
        ]);
    }

    public function create(string $type)
    {
        abort_unless(isset($this->modules[$type]), 404);

        $employee = $this->employee();
        $requests = EmployeePresenceRequest::where('attendance_employee_id', $employee->id)
            ->where('request_type', $type)
            ->latest()
            ->take(8)
            ->get();

        return view('presensi.request-form', [
            'employee' => $employee,
            'type' => $type,
            'module' => $this->modules[$type],
            'requests' => $requests,
        ]);
    }

    public function store(Request $request, string $type)
    {
        abort_unless(isset($this->modules[$type]), 404);

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'category' => ['required', 'string', Rule::in($this->modules[$type]['categories'])],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'delegation_to' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:1500'],
            'attachment' => ['nullable', 'file', 'max:4096', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        $employee = $this->employee();
        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $safeCode = preg_replace('/[^A-Za-z0-9_-]/', '_', $employee->employee_code);
            $attachmentPath = $request->file('attachment')->store("attendance/requests/{$safeCode}", 'public');
        }

        EmployeePresenceRequest::create([
            'attendance_employee_id' => $employee->id,
            'request_type' => $type,
            'status' => 'pending',
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? $validated['start_date'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'category' => $validated['category'],
            'amount' => $validated['amount'] ?? null,
            'delegation_to' => $validated['delegation_to'] ?? null,
            'description' => $validated['description'],
            'attachment_path' => $attachmentPath,
        ]);

        return redirect()->route('presensi.menu')->with('success', 'Pengajuan berhasil dikirim.');
    }

    public function inbox()
    {
        $employee = $this->employee();
        $requests = EmployeePresenceRequest::where('attendance_employee_id', $employee->id)
            ->latest()
            ->take(20)
            ->get();
        $leaderRequests = EmployeePresenceRequest::with('employee')
            ->whereHas('employee', fn ($query) => $query->where('supervisor_id', $employee->id))
            ->latest()
            ->take(30)
            ->get();
        $leaderAttendanceRequests = EmployeeAttendance::with('employee')
            ->whereHas('employee', fn ($query) => $query->where('supervisor_id', $employee->id))
            ->whereIn('attendance_type', ['cuti', 'sakit', 'terlambat', 'cepat_pulang'])
            ->latest()
            ->take(30)
            ->get();

        return view('presensi.inbox', compact('employee', 'requests', 'leaderRequests', 'leaderAttendanceRequests'));
    }

    public function updateLeaderRequest(Request $request, EmployeePresenceRequest $presenceRequest)
    {
        $employee = $this->employee();

        abort_unless((int) $presenceRequest->employee?->supervisor_id === (int) $employee->id, 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        $presenceRequest->update([
            'status' => $validated['status'],
            'approved_at' => $validated['status'] === 'approved' ? now() : null,
        ]);

        return back()->with('success', $validated['status'] === 'approved' ? 'Pengajuan tim disetujui.' : 'Pengajuan tim ditolak.');
    }

    public function updateLeaderAttendance(Request $request, EmployeeAttendance $attendance)
    {
        $employee = $this->employee();

        abort_unless((int) $attendance->employee?->supervisor_id === (int) $employee->id, 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        $attendance->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', $validated['status'] === 'approved' ? 'Pengajuan presensi tim disetujui.' : 'Pengajuan presensi tim ditolak.');
    }

    public function payslip()
    {
        $employee = $this->employee();

        return view('presensi.payslip', compact('employee'));
    }

    public function account()
    {
        $employee = $this->employee();

        return view('presensi.account', compact('employee'));
    }

    private function employee(): AttendanceEmployee
    {
        return AttendanceEmployee::findOrFail(session('attendance_employee_id'));
    }
}
