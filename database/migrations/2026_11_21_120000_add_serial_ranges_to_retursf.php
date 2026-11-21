<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('retursf', 'sn_awal')) {
            Schema::table('retursf', fn (Blueprint $table) => $table->string('sn_awal', 24)->nullable());
        }

        if (! Schema::hasColumn('retursf', 'sn_akhir')) {
            Schema::table('retursf', fn (Blueprint $table) => $table->string('sn_akhir', 24)->nullable());
        }

        if (Schema::hasColumn('retursf', 'sn')) {
            DB::table('retursf')->whereNull('sn_awal')
                ->whereRaw("TRIM(sn) REGEXP '^[0-9]{12,16}(-[0-9]{12,16})?$'")
                ->update([
                'sn_awal' => DB::raw("CASE WHEN LOCATE('-', sn) > 0 THEN SUBSTRING_INDEX(sn, '-', 1) ELSE sn END"),
                'sn_akhir' => DB::raw("CASE WHEN LOCATE('-', sn) > 0 THEN SUBSTRING_INDEX(sn, '-', -1) ELSE sn END"),
            ]);
        }
    }

    public function down(): void
    {
        $columns = array_values(array_filter(['sn_awal', 'sn_akhir'], fn ($column) => Schema::hasColumn('retursf', $column)));
        if ($columns) Schema::table('retursf', fn (Blueprint $table) => $table->dropColumn($columns));
    }
};
