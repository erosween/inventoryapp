<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surya_visits', function (Blueprint $table) {
            $table->string('branding_dominance', 30)->nullable()->after('photo_front_path');
            $table->string('branding_quality', 30)->nullable()->after('branding_dominance');
        });
    }

    public function down(): void
    {
        Schema::table('surya_visits', function (Blueprint $table) {
            $table->dropColumn(['branding_dominance', 'branding_quality']);
        });
    }
};
