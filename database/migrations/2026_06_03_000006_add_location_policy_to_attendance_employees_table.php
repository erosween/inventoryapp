<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            $table->unsignedTinyInteger('employee_level')->default(1)->after('role');
            $table->string('attendance_location_mode', 20)->default('locked')->after('employee_level');
            $table->string('attendance_location_label')->nullable()->after('attendance_location_mode');
            $table->decimal('attendance_latitude', 10, 7)->nullable()->after('attendance_location_label');
            $table->decimal('attendance_longitude', 10, 7)->nullable()->after('attendance_latitude');
            $table->unsignedInteger('attendance_radius_meters')->default(150)->after('attendance_longitude');

            $table->index(['employee_level', 'attendance_location_mode'], 'att_emp_level_location_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            $table->dropIndex('att_emp_level_location_idx');
            $table->dropColumn([
                'employee_level',
                'attendance_location_mode',
                'attendance_location_label',
                'attendance_latitude',
                'attendance_longitude',
                'attendance_radius_meters',
            ]);
        });
    }
};
