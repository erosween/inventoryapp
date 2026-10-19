<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('keluarsf', 'id_outlet') && Schema::hasColumn('keluarsf', 'tambahanket')) {
            DB::statement("UPDATE keluarsf k JOIN appsdumais o ON CAST(o.id_outlet AS CHAR) = TRIM(SUBSTRING_INDEX(k.tambahanket, ',', 1)) SET k.id_outlet = o.id_outlet WHERE k.id_outlet IS NULL");
        }
    }

    public function down(): void
    {
        // Keep the migrated outlet IDs; the source notes remain unchanged.
    }
};
