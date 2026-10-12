<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surya_visits', function (Blueprint $table) {
            $table->string('gps_status', 30)->default('valid')->change();
        });
    }

    public function down(): void
    {
        Schema::table('surya_visits', function (Blueprint $table) {
            $table->enum('gps_status', ['valid', 'invalid'])->default('valid')->change();
        });
    }
};
