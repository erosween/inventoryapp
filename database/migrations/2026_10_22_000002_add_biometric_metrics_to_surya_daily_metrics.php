<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surya_daily_metrics', function (Blueprint $table) {
            $table->unsignedInteger('bio_fm1')->default(0)->after('digital_m');
            $table->unsignedInteger('bio_m1')->default(0)->after('bio_fm1');
            $table->unsignedInteger('bio_m')->default(0)->after('bio_m1');
        });
    }

    public function down(): void
    {
        Schema::table('surya_daily_metrics', function (Blueprint $table) {
            $table->dropColumn(['bio_fm1', 'bio_m1', 'bio_m']);
        });
    }
};
