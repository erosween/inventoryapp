<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('keluarsf', 'sn_awal')) {
            Schema::table('keluarsf', fn (Blueprint $table) => $table->string('sn_awal', 24)->nullable());
        }

        if (! Schema::hasColumn('keluarsf', 'sn_akhir')) {
            Schema::table('keluarsf', fn (Blueprint $table) => $table->string('sn_akhir', 24)->nullable());
        }
    }

    public function down(): void
    {
        $columns = array_values(array_filter(['sn_awal', 'sn_akhir'], fn ($column) => Schema::hasColumn('keluarsf', $column)));
        if ($columns) {
            Schema::table('keluarsf', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
