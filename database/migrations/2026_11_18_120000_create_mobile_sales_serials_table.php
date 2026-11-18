<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_sales_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobile_penjualan_id')->constrained('mobile_penjualan')->cascadeOnDelete();
            $table->foreignId('receipt_id')->nullable()->constrained('mobile_sales_receipts')->nullOnDelete();
            $table->string('iddenom', 25)->index();
            $table->string('identifier_type', 10); // sn | number
            $table->string('input_mode', 10); // single | range
            $table->string('serial_start', 24);
            $table->string('serial_end', 24);
            $table->unsignedInteger('qty');
            $table->timestamps();

            $table->index(['identifier_type', 'serial_start', 'serial_end'], 'mobile_sales_serial_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_sales_serials');
    }
};
