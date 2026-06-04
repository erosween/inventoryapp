<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_presence_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_employee_id')->constrained('attendance_employees')->cascadeOnDelete();
            $table->string('request_type', 40);
            $table->string('status', 30)->default('pending');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('category')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('delegation_to')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['attendance_employee_id', 'request_type'], 'emp_req_employee_type_idx');
            $table->index(['status', 'created_at'], 'emp_req_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_presence_requests');
    }
};
