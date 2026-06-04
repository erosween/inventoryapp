<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_face_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_employee_id')->constrained('attendance_employees')->cascadeOnDelete();
            $table->string('image_path');
            $table->text('signature_json');
            $table->string('signature_hash', 80);
            $table->string('status', 20)->default('active');
            $table->timestamp('enrolled_at');
            $table->timestamps();

            $table->index(['attendance_employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_face_enrollments');
    }
};
