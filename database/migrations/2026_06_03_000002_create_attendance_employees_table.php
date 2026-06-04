<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 40)->unique();
            $table->string('name');
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->string('work_location')->nullable();
            $table->string('role', 30)->default('employee');
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('face_enrolled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'role']);
            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_employees');
    }
};
