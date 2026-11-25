<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        'mobile_penjualan' => [
            'mobile_penjualan_updated_at_idx' => ['updated_at'],
        ],
        'logs' => [
            'logs_module_created_at_idx' => ['module', 'created_at'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->pluck('Key_name')
                ->unique()
                ->all();

            foreach ($indexes as $name => $columns) {
                if (in_array($name, $existing, true)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->pluck('Key_name')
                ->unique()
                ->all();

            foreach (array_keys($indexes) as $name) {
                if (in_array($name, $existing, true)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }
};
