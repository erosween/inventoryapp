<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('surya_visits', 'surya_visits_outlet_time_id_idx')) {
            Schema::table('surya_visits', fn (Blueprint $table) => $table->index(['id_outlet', 'visited_at', 'id'], 'surya_visits_outlet_time_id_idx'));
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('surya_visits', 'surya_visits_outlet_time_id_idx')) {
            Schema::table('surya_visits', fn (Blueprint $table) => $table->dropIndex('surya_visits_outlet_time_id_idx'));
        }
    }
};
