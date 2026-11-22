<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('keluarsf', 'sn_awal')) {
            Schema::table('keluarsf', function (Blueprint $table) {
                $table->index('sn_awal', 'keluarsf_sn_awal_stock_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('keluarsf', 'sn_awal')) {
            Schema::table('keluarsf', function (Blueprint $table) {
                $table->dropIndex('keluarsf_sn_awal_stock_idx');
            });
        }
    }
};
