<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('keluarsf', 'id_outlet') || ! Schema::hasColumn('keluarsf', 'tambahanket')) {
            return;
        }

        // Legacy notes may contain an outlet ID followed by a comma or text.
        // Normalize spaces and compare as strings to avoid numeric truncation.
        DB::statement("UPDATE keluarsf k JOIN appsdumais o ON CAST(o.id_outlet AS CHAR) = REPLACE(TRIM(SUBSTRING_INDEX(k.tambahanket, ',', 1)), ' ', '') SET k.id_outlet = o.id_outlet WHERE k.id_outlet IS NULL AND REPLACE(TRIM(SUBSTRING_INDEX(k.tambahanket, ',', 1)), ' ', '') REGEXP '^(12|60)[0-9]+'");
    }

    public function down(): void {}
};
