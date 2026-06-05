<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_employees', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('phone');
            }

            if (!Schema::hasColumn('attendance_employees', 'pwa_prompted_at')) {
                $table->timestamp('pwa_prompted_at')->nullable()->after('face_enrolled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_employees', 'pwa_prompted_at')) {
                $table->dropColumn('pwa_prompted_at');
            }

            if (Schema::hasColumn('attendance_employees', 'profile_photo_path')) {
                $table->dropColumn('profile_photo_path');
            }
        });
    }
};
