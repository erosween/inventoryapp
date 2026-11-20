<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_tracking_sales_stock_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('filename', 255);
            $table->unsignedInteger('row_count')->default(0);
            $table->string('username', 150);
            $table->timestamps();
        });

        Schema::create('product_tracking_sales_stock_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('product_tracking_sales_stock_uploads')->cascadeOnDelete();
            $table->string('sfcode', 100);
            $table->string('tap', 100);
            $table->string('cluster', 100);
            $table->string('denom_paket', 200);
            $table->string('serial_start', 16);
            $table->string('serial_end', 16);
            $table->unsignedInteger('qty');
            $table->timestamps();

            $table->index(['sfcode', 'serial_start'], 'pt_sales_stock_sf_start_idx');
            $table->index(['upload_id', 'sfcode', 'denom_paket'], 'pt_sales_stock_upload_sf_denom_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tracking_sales_stock_ranges');
        Schema::dropIfExists('product_tracking_sales_stock_uploads');
    }
};
