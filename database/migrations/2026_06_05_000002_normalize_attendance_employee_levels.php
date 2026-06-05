<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('attendance_employees')
            ->where('role', 'gm')
            ->update([
                'employee_level' => 3,
                'attendance_location_mode' => 'anywhere',
                'supervisor_id' => null,
            ]);

        DB::table('attendance_employees')
            ->where('role', 'spv_manager')
            ->update([
                'employee_level' => 2,
                'attendance_location_mode' => 'anywhere',
                'supervisor_id' => null,
            ]);

        DB::table('attendance_employees')
            ->where('role', 'staff')
            ->update([
                'employee_level' => 1,
                'attendance_location_mode' => 'locked',
            ]);
    }

    public function down(): void
    {
        DB::table('attendance_employees')
            ->where('role', 'gm')
            ->update(['employee_level' => 1]);

        DB::table('attendance_employees')
            ->where('role', 'staff')
            ->update(['employee_level' => 3]);
    }
};
