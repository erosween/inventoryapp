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
        $this->addIndexIfMissing('appsdumais', 'id_outlet');
        $this->addIndexIfMissing('masuksf', 'idsf');
        $this->addIndexIfMissing('masuksf', 'tgl');
        $this->addIndexIfMissing('keluarsf', 'idsf');
        $this->addIndexIfMissing('keluarsf', 'tgl');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('appsdumais', 'id_outlet');
        $this->dropIndexIfExists('masuksf', 'idsf');
        $this->dropIndexIfExists('masuksf', 'tgl');
        $this->dropIndexIfExists('keluarsf', 'idsf');
        $this->dropIndexIfExists('keluarsf', 'tgl');
    }

    private function addIndexIfMissing($table, $column)
    {
        $indexName = "{$table}_{$column}_index";
        $exists = collect(DB::select("SHOW INDEX FROM {$table}"))->where('Key_name', $indexName)->count() > 0;
        
        if (!$exists) {
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->index($column);
            });
        }
    }

    private function dropIndexIfExists($table, $column)
    {
        $indexName = "{$table}_{$column}_index";
        $exists = collect(DB::select("SHOW INDEX FROM {$table}"))->where('Key_name', $indexName)->count() > 0;
        
        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->dropIndex([$column]);
            });
        }
    }
};
