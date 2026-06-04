<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_attendances', function (Blueprint $table) {
            $table->id();
            $table->date('attendance_date');
            $table->string('idtap', 25);
            $table->string('idsf', 25);
            $table->string('attendance_type', 40)->default('hadir');
            $table->string('status', 30)->default('submitted');
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('face_photo_path')->nullable();
            $table->unsignedTinyInteger('face_confidence')->default(0);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['idsf', 'attendance_date']);
            $table->index(['idtap', 'attendance_date']);
            $table->index('attendance_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_attendances');
    }
};
