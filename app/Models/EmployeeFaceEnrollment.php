<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeFaceEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_employee_id',
        'image_path',
        'signature_json',
        'signature_hash',
        'status',
        'enrolled_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(AttendanceEmployee::class, 'attendance_employee_id');
    }

    public function signature(): array
    {
        $signature = json_decode($this->signature_json, true);

        return is_array($signature) ? $signature : [];
    }
}
