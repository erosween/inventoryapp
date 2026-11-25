<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'pt_digipos_kind_end_range_idx';

    public function up(): void
    {
        if (! Schema::hasTable('product_tracking_digipos')) {
            return;
        }

        $exists = collect(DB::select('SHOW INDEX FROM `product_tracking_digipos`'))
            ->contains(fn ($index) => $index->Key_name === self::INDEX);
        if ($exists) {
            return;
        }

        Schema::table('product_tracking_digipos', function (Blueprint $table) {
            $table->index(['kind', 'serial_end', 'serial_start'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_tracking_digipos')) {
            return;
        }

        $exists = collect(DB::select('SHOW INDEX FROM `product_tracking_digipos`'))
            ->contains(fn ($index) => $index->Key_name === self::INDEX);
        if ($exists) {
            Schema::table('product_tracking_digipos', fn (Blueprint $table) => $table->dropIndex(self::INDEX));
        }
    }
};
