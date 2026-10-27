<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('denom_duplicate_archive')) {
            Schema::create('denom_duplicate_archive', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('source_idbarang')->unique();
                $table->string('iddenom', 25);
                $table->text('original_row');
                $table->timestamp('archived_at');
            });
        }

        DB::transaction(function () {
            foreach (['SP6', 'SP7'] as $code) {
                $rows = DB::table('denom')->where('iddenom', $code)->orderBy('idbarang')->lockForUpdate()->get();
                if ($rows->isEmpty()) continue;
                if ($rows->pluck('harga_jual')->unique()->count() > 1) {
                    throw new RuntimeException('Conflicting prices for '.$code.'; manual reconciliation required.');
                }
                foreach ($rows as $row) {
                    DB::table('denom_duplicate_archive')->insertOrIgnore([
                        'source_idbarang' => $row->idbarang, 'iddenom' => $code,
                        'original_row' => json_encode($row, JSON_THROW_ON_ERROR), 'archived_at' => now(),
                    ]);
                }
                $keep = $rows->first()->idbarang;
                DB::table('denom')->where('iddenom', $code)->where('idbarang', '<>', $keep)->delete();
                DB::table('denom')->where('idbarang', $keep)->update([
                    'group_name' => 'SEGEL', 'kategori_inject' => 'SA', 'product_group' => 'SP',
                ]);
            }
            $duplicates = DB::table('denom')->select('iddenom')->groupBy('iddenom')->havingRaw('COUNT(*) > 1')->exists();
            if ($duplicates) throw new RuntimeException('Other duplicate denomination codes require reconciliation.');
        });

        Schema::table('denom', function (Blueprint $table) {
            $table->unique('iddenom', 'denom_iddenom_unique');
        });
    }

    public function down(): void
    {
        Schema::table('denom', fn (Blueprint $table) => $table->dropUnique('denom_iddenom_unique'));
        // Keep reconciled rows and the recovery archive. Rollback must not
        // silently reintroduce duplicated join results into stock reports.
    }
};
