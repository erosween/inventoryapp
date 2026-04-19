<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah Primary Key ke tabel master & stok (IDEMPOTENT)
        $this->addPrimaryKeyIfMissing('idsf', 'idsf');
        $this->addCompositePrimaryKeyIfMissing('stockawaltap', ['idtap', 'iddenom']);
        $this->addCompositePrimaryKeyIfMissing('stockawalsf', ['idsf', 'iddenom']);

        // 2. Tambah Index pencarian ke tabel transaksi (IDEMPOTENT)
        $this->addIndexIfMissing('masuk', 'tgl');
        $this->addIndexIfMissing('masuk', 'iddenom');
        $this->addIndexIfMissing('keluar', 'tgl');
        $this->addIndexIfMissing('keluar', 'iddenom');
        $this->addIndexIfMissing('masuksf', 'tgl');
        $this->addIndexIfMissing('masuksf', 'iddenom');
        $this->addIndexIfMissing('keluarsf', 'tgl');
        $this->addIndexIfMissing('keluarsf', 'iddenom');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop what we can safely drop
    }

    private function addPrimaryKeyIfMissing(string $table, string $column): void
    {
        $keys = DB::select("SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'");
        if (empty($keys)) {
            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->primary($column);
            });
        }
    }

    private function addCompositePrimaryKeyIfMissing(string $table, array $columns): void
    {
        $keys = DB::select("SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'");
        if (empty($keys)) {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                $t->primary($columns);
            });
        }
    }

    private function addIndexIfMissing(string $table, string $column): void
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Column_name = '{$column}' AND Key_name != 'PRIMARY'");
        if (empty($indexes)) {
            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->index($column);
            });
        }
    }
};
