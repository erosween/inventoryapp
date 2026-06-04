<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            $table->time('work_start_time')->nullable()->after('attendance_radius_meters');
            $table->time('work_end_time')->nullable()->after('work_start_time');
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(15)->after('work_end_time');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            $table->dropColumn([
                'work_start_time',
                'work_end_time',
                'late_tolerance_minutes',
            ]);
        });
    }
};
