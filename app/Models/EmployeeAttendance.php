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
}
