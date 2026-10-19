<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('keluarsf', 'id_outlet') && Schema::hasColumn('keluarsf', 'tambahanket')) {
            // Process in small batches so a legacy backfill cannot lock the
            // entire keluarsf table while normal users are saving data.
            do {
                $affected = DB::affectingStatement("UPDATE keluarsf k JOIN appsdumais o ON CAST(o.id_outlet AS CHAR) = REPLACE(TRIM(SUBSTRING_INDEX(k.tambahanket, ',', 1)), ' ', '') SET k.id_outlet = o.id_outlet WHERE k.id_outlet IS NULL AND REPLACE(TRIM(SUBSTRING_INDEX(k.tambahanket, ',', 1)), ' ', '') REGEXP '^(12|60)[0-9]+' ORDER BY k.idkeluar LIMIT 500");
            } while ($affected > 0);

            do {
                $affected = DB::affectingStatement("UPDATE keluarsf k JOIN mobile_penjualan m ON TRIM(k.tambahanket) = CONCAT('Mobile Approval ID: ', m.id) SET k.id_outlet = m.id_outlet WHERE k.id_outlet IS NULL AND m.id_outlet IS NOT NULL ORDER BY k.idkeluar LIMIT 500");
            } while ($affected > 0);
        }
    }

    public function down(): void
    {
        // Keep the migrated outlet IDs; the source notes remain unchanged.
    }
};
