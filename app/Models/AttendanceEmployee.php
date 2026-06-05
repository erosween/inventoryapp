<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_code',
        'name',
        'department',
        'position',
        'work_location',
        'role',
        'employee_level',
        'supervisor_id',
        'attendance_location_mode',
        'attendance_location_label',
        'attendance_latitude',
        'attendance_longitude',
        'attendance_radius_meters',
        'work_start_time',
        'work_end_time',
        'late_tolerance_minutes',
        'password',
        'phone',
        'profile_photo_path',
        'status',
        'face_enrolled_at',
        'pwa_prompted_at',
    ];

    protected $casts = [
        'employee_level' => 'integer',
        'supervisor_id' => 'integer',
        'attendance_latitude' => 'float',
        'attendance_longitude' => 'float',
        'attendance_radius_meters' => 'integer',
        'late_tolerance_minutes' => 'integer',
        'face_enrolled_at' => 'datetime',
        'pwa_prompted_at' => 'datetime',
    ];

    public function canAttendAnywhere(): bool
    {
        return ($this->employee_level ?? 1) >= 2 || $this->attendance_location_mode === 'anywhere';
    }

    public function levelLabel(): string
    {
        return match ((int) ($this->employee_level ?? 1)) {
            1 => 'Admin / Sales',
            2 => 'SPV / Manager',
            3 => 'GM',
            default => 'Karyawan',
        };
    }

    public function hasAttendanceLocationTarget(): bool
    {
        return $this->attendance_latitude !== null && $this->attendance_longitude !== null;
    }

    public function attendanceLocationPolicy(): array
    {
        if ($this->canAttendAnywhere()) {
            return [
                'mode' => 'anywhere',
                'locked' => false,
                'configured' => false,
                'badge' => 'Bebas Lokasi',
                'badge_class' => 'info',
                'label' => 'Semua lokasi',
                'description' => 'Karyawan level ' . ($this->employee_level ?? 1) . ' bisa melakukan presensi dari mana saja.',
                'latitude' => null,
                'longitude' => null,
                'radius' => null,
            ];
        }

        $configured = $this->hasAttendanceLocationTarget();
        $label = $this->attendance_location_label ?: $this->work_location ?: 'Lokasi kantor';

        return [
            'mode' => 'locked',
            'locked' => true,
            'configured' => $configured,
            'badge' => $configured ? 'Terkunci' : 'Set Admin',
            'badge_class' => $configured ? 'success' : 'warning',
            'label' => $label,
            'description' => $configured
                ? 'Presensi harus berada dalam radius ' . ($this->attendance_radius_meters ?: 150) . ' meter dari ' . $label . '.'
                : 'Lock lokasi aktif untuk level 1, titik kantor belum ditentukan admin.',
            'latitude' => $configured ? $this->attendance_latitude : null,
            'longitude' => $configured ? $this->attendance_longitude : null,
            'radius' => $this->attendance_radius_meters ?: 150,
        ];
    }

    public function distanceToAttendanceLocation(float $latitude, float $longitude): ?float
    {
        if (!$this->hasAttendanceLocationTarget()) {
            return null;
        }

        $earthRadius = 6371000;
        $latFrom = deg2rad($latitude);
        $lonFrom = deg2rad($longitude);
        $latTo = deg2rad($this->attendance_latitude);
        $lonTo = deg2rad($this->attendance_longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            sin($latDelta / 2) ** 2 +
            cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2
        ));

        return $earthRadius * $angle;
    }

    public function scheduleLabel(): string
    {
        $start = $this->work_start_time ? substr((string) $this->work_start_time, 0, 5) : '08:30';
        $end = $this->work_end_time ? substr((string) $this->work_end_time, 0, 5) : '17:00';

        return $start . ' - ' . $end;
    }

    public function faceEnrollments()
    {
        return $this->hasMany(EmployeeFaceEnrollment::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(self::class, 'supervisor_id');
    }

    public function latestAttendance()
    {
        return $this->hasOne(EmployeeAttendance::class)->latestOfMany();
    }

    public function activeFaceEnrollment()
    {
        return $this->hasOne(EmployeeFaceEnrollment::class)->where('status', 'active')->latestOfMany();
    }

    public function attendances()
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function presenceRequests()
    {
        return $this->hasMany(EmployeePresenceRequest::class);
    }
}
