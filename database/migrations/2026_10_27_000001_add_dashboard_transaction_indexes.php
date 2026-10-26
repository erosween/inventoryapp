<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keluarsf', function (Blueprint $table) {
            $table->index(['tgl', 'idtap', 'iddenom'], 'keluarsf_dashboard_tgl_tap_denom_idx');
        });

        Schema::table('mobile_penjualan', function (Blueprint $table) {
            $table->index(['status', 'tgl', 'idtap', 'iddenom'], 'mobile_dashboard_status_tgl_tap_denom_idx');
        });

        Schema::table('denom', function (Blueprint $table) {
            $table->index(['kategori_inject', 'iddenom'], 'denom_dashboard_category_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('keluarsf', fn (Blueprint $table) => $table->dropIndex('keluarsf_dashboard_tgl_tap_denom_idx'));
        Schema::table('mobile_penjualan', fn (Blueprint $table) => $table->dropIndex('mobile_dashboard_status_tgl_tap_denom_idx'));
        Schema::table('denom', fn (Blueprint $table) => $table->dropIndex('denom_dashboard_category_id_idx'));
    }
};
