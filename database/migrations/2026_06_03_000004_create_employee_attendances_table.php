<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_employee_id')->constrained('attendance_employees')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('attendance_type', 40)->default('hadir');
            $table->string('status', 30)->default('submitted');
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('face_photo_path')->nullable();
            $table->unsignedTinyInteger('face_match_score')->default(0);
            $table->string('face_signature_hash', 80)->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['attendance_employee_id', 'attendance_date'], 'emp_att_unique_day');
            $table->index(['attendance_date', 'attendance_type'], 'emp_att_date_type_idx');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attendances');
    }
};
