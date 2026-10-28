<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appsdumais', function (Blueprint $table) {
            $table->decimal('latitude_checkin', 10, 8)->nullable()->after('pjp');
            $table->decimal('longitude_checkin', 11, 8)->nullable()->after('latitude_checkin');
        });
        if (!Schema::hasTable('surya_visits')) return;
        // Preserve the latest accepted check-in before any new Digipos import.
        app(\App\Services\SuryaOutletCoordinates::class)->latestVisits()
            ->orderBy('coordinate_visit_id')->chunk(250, function ($visits) {
                foreach ($visits as $visit) DB::table('appsdumais')
                    ->whereRaw('CAST(id_outlet AS CHAR) = ?', [(string)$visit->coordinate_outlet_id])
                    ->whereNull('latitude_checkin')->whereNull('longitude_checkin')->update([
                        'latitude_checkin' => $visit->visit_latitude,
                        'longitude_checkin' => $visit->visit_longitude,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('appsdumais', fn (Blueprint $table) => $table->dropColumn(['latitude_checkin', 'longitude_checkin']));
    }
};
