<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'masuk' => [
            'masuk_pengirim_tgl_idx' => ['pengirim', 'tgl'],
            'masuk_pengirim_tap_tgl_idx' => ['pengirim', 'idtappenerima', 'tgl'],
            'masuk_idtap_tgl_idx' => ['idtap', 'tgl'],
        ],
        'keluar' => [
            'keluar_penerima_tgl_idx' => ['penerima', 'tgl'],
            'keluar_idtap_tgl_idx' => ['idtap', 'tgl'],
        ],
        'masuksf' => [
            'masuksf_idtap_tgl_idx' => ['idtap', 'tgl'],
        ],
        'keluarsf' => [
            'keluarsf_idtap_tgl_idx' => ['idtap', 'tgl'],
        ],
        'injectvf' => [
            'injectvf_idtap_tgl_idx' => ['idtap', 'tgl'],
        ],
        'returvfrusak' => [
            'returvfrusak_idtap_tgl_idx' => ['idtap', 'tgl'],
        ],
        'retursf' => [
            'retursf_idtap_tgl_idx' => ['idtap', 'tgl'],
        ],
        'logs' => [
            'logs_created_at_idx' => ['created_at'],
            'logs_username_action_created_idx' => ['username', 'action', 'created_at'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (!Schema::hasTable($table)) continue;

            $existing = collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->pluck('Key_name')
                ->all();

            foreach ($indexes as $name => $columns) {
                if (in_array($name, $existing, true)) continue;
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (!Schema::hasTable($table)) continue;

            $existing = collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->pluck('Key_name')
                ->all();

            foreach (array_keys($indexes) as $name) {
                if (!in_array($name, $existing, true)) continue;
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
            }
        }
    }
};
