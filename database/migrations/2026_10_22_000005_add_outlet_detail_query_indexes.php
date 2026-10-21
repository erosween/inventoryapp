<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('keluarsf', function (Blueprint $table) {
            $table->index(['id_outlet', 'tgl'], 'keluarsf_outlet_tgl_idx');
        });

        Schema::table('mobile_penjualan', function (Blueprint $table) {
            $table->index(['id_outlet', 'tgl', 'status'], 'mobile_sales_outlet_tgl_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('keluarsf', function (Blueprint $table) {
            $table->dropIndex('keluarsf_outlet_tgl_idx');
        });

        Schema::table('mobile_penjualan', function (Blueprint $table) {
            $table->dropIndex('mobile_sales_outlet_tgl_status_idx');
        });
    }
};
