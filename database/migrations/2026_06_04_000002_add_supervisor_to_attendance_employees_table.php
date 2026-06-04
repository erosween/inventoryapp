<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            $table->foreignId('supervisor_id')
                ->nullable()
                ->after('employee_level')
                ->constrained('attendance_employees')
                ->nullOnDelete();

            $table->index(['employee_level', 'supervisor_id'], 'att_emp_level_supervisor_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropIndex('att_emp_level_supervisor_idx');
            $table->dropColumn('supervisor_id');
        });
    }
};
