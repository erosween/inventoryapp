<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_sales_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 32)->unique();
            $table->date('tgl');
            $table->string('id_outlet')->index();
            $table->string('idtap', 25)->index();
            $table->string('idsf', 25)->index();
            $table->string('owner_whatsapp', 20)->nullable();
            $table->unsignedInteger('total_qty')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->timestamp('whatsapp_opened_at')->nullable();
            $table->timestamp('whatsapp_shared_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('mobile_penjualan', function (Blueprint $table) {
            $table->unsignedBigInteger('receipt_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('mobile_penjualan', function (Blueprint $table) {
            $table->dropColumn('receipt_id');
        });

        Schema::dropIfExists('mobile_sales_receipts');
    }
};
