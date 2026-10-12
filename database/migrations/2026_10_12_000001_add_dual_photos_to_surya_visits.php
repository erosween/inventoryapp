<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surya_visits', function (Blueprint $table) {
            $table->string('photo_front_path')->nullable()->after('photo_path');
            $table->string('photo_inside_path')->nullable()->after('photo_front_path');
        });
    }

    public function down(): void
    {
        Schema::table('surya_visits', function (Blueprint $table) {
            $table->dropColumn(['photo_front_path', 'photo_inside_path']);
        });
    }
};
