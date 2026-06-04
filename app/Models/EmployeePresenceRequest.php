<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePresenceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_employee_id',
        'request_type',
        'status',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'category',
        'amount',
        'delegation_to',
        'description',
        'attachment_path',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(AttendanceEmployee::class, 'attendance_employee_id');
    }
}
