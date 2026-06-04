<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileAttendance extends Model
{
    use HasFactory;

    protected $table = 'mobile_attendances';

    protected $fillable = [
        'attendance_date',
        'idtap',
        'idsf',
        'attendance_type',
        'status',
        'check_in_at',
        'check_out_at',
        'latitude',
        'longitude',
        'face_photo_path',
        'face_confidence',
        'reason',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];
}
