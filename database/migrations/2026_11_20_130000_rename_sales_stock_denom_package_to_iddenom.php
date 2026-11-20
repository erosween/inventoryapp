<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_tracking_sales_stock_ranges', 'denom_paket')) return;

        Schema::table('product_tracking_sales_stock_ranges', function (Blueprint $table) {
            $table->dropIndex('pt_sales_stock_upload_sf_denom_idx');
            $table->renameColumn('denom_paket', 'iddenom');
        });
        Schema::table('product_tracking_sales_stock_ranges', function (Blueprint $table) {
            $table->index(['upload_id', 'sfcode', 'iddenom'], 'pt_sales_stock_upload_sf_iddenom_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('product_tracking_sales_stock_ranges', 'iddenom')) return;

        Schema::table('product_tracking_sales_stock_ranges', function (Blueprint $table) {
            $table->dropIndex('pt_sales_stock_upload_sf_iddenom_idx');
            $table->renameColumn('iddenom', 'denom_paket');
        });
        Schema::table('product_tracking_sales_stock_ranges', function (Blueprint $table) {
            $table->index(['upload_id', 'sfcode', 'denom_paket'], 'pt_sales_stock_upload_sf_denom_idx');
        });
    }
};
