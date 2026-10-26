<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('surya_visits', 'surya_visits_outlet_gps_time_idx')) {
            Schema::table('surya_visits', function (Blueprint $table) {
                $table->index(['id_outlet', 'gps_status', 'visited_at'], 'surya_visits_outlet_gps_time_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('surya_visits', 'surya_visits_outlet_gps_time_idx')) {
            Schema::table('surya_visits', fn (Blueprint $table) => $table->dropIndex('surya_visits_outlet_gps_time_idx'));
        }
    }
};
