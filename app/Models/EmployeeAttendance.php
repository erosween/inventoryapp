<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_employee_id',
        'attendance_date',
        'attendance_type',
        'status',
        'check_in_at',
        'check_out_at',
        'latitude',
        'longitude',
        'face_photo_path',
        'face_thumbnail_path',
        'face_match_score',
        'face_signature_hash',
        'reason',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(AttendanceEmployee::class, 'attendance_employee_id');
    }

    public function scheduledStartAt()
    {
        $employee = $this->employee;
        if (!$employee || !$this->attendance_date) {
            return null;
        }

        $time = $employee->work_start_time ? substr((string) $employee->work_start_time, 0, 5) : '08:30';

        return $this->attendance_date->copy()->setTimeFromTimeString($time);
    }

    public function toleranceLimitAt()
    {
        $start = $this->scheduledStartAt();
        if (!$start) {
            return null;
        }

        return $start->copy()->addMinutes((int) ($this->employee?->late_tolerance_minutes ?? 15));
    }

    public function lateMinutes(): int
    {
        $limit = $this->toleranceLimitAt();
        if (!$limit || !$this->check_in_at || $this->check_in_at->lessThanOrEqualTo($limit)) {
            return 0;
        }

        return (int) round($limit->diffInMinutes($this->check_in_at));
    }

    public function arrivalStatus(): array
    {
        if (in_array($this->attendance_type, ['cuti', 'sakit'], true)) {
            return [
                'label' => $this->attendance_type === 'cuti' ? 'Cuti' : 'Sakit',
                'class' => 'info',
                'minutes' => 0,
            ];
        }

        if (!$this->check_in_at) {
            return [
                'label' => 'Belum Check In',
                'class' => 'warning',
                'minutes' => 0,
            ];
        }

        $lateMinutes = $this->lateMinutes();
        if ($lateMinutes > 0 || $this->attendance_type === 'terlambat') {
            return [
                'label' => $lateMinutes > 0 ? 'Telat ' . $lateMinutes . ' menit' : 'Izin terlambat',
                'class' => 'warning',
                'minutes' => $lateMinutes,
            ];
        }

        return [
            'label' => 'Tepat waktu',
            'class' => 'success',
            'minutes' => 0,
        ];
    }
}
