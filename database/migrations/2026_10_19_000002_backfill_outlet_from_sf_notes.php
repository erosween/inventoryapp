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
            $backfill = function (string $where, string $join, array $bindings = []) {
                $lastId = 0;
                do {
                    $rows = DB::select("SELECT k.idkeluar, o.id_outlet AS outlet_id FROM keluarsf k {$join} WHERE k.idkeluar > ? AND k.id_outlet IS NULL {$where} ORDER BY k.idkeluar LIMIT 500", [$lastId, ...$bindings]);
                    if (! $rows) break;

                    $ids = array_map(fn ($row) => (int) $row->idkeluar, $rows);
                    $case = implode(' ', array_map(fn ($row) => 'WHEN ? THEN ?', $rows));
                    $params = [];
                    foreach ($rows as $row) {
                        $params[] = (int) $row->idkeluar;
                        $params[] = $row->outlet_id;
                    }
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    DB::update("UPDATE keluarsf SET id_outlet = CASE idkeluar {$case} END WHERE idkeluar IN ({$placeholders}) AND id_outlet IS NULL", [...$params, ...$ids]);
                    $lastId = max($ids);
                } while (count($rows) === 500);
            };

            $backfill("AND REPLACE(TRIM(SUBSTRING_INDEX(k.tambahanket, ',', 1)), ' ', '') REGEXP '^(12|60)[0-9]+' AND CAST(o.id_outlet AS CHAR) = REPLACE(TRIM(SUBSTRING_INDEX(k.tambahanket, ',', 1)), ' ', '')", 'JOIN appsdumais o ON 1=1');
            $backfill('AND m.id_outlet IS NOT NULL AND TRIM(k.tambahanket) = CONCAT(\'Mobile Approval ID: \', m.id)', 'JOIN mobile_penjualan m ON 1=1');
        }
    }

    public function down(): void
    {
        // Keep the migrated outlet IDs; the source notes remain unchanged.
    }
};
