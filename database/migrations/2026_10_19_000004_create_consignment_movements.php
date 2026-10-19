<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('surya_sa_consignment_movements')) return;

        Schema::create('surya_sa_consignment_movements', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number', 30);
            $table->string('from_id_outlet', 80)->nullable();
            $table->string('to_id_outlet', 80);
            $table->foreignId('receipt_id')->nullable()->constrained('mobile_sales_receipts')->nullOnDelete();
            $table->string('idtap', 50)->nullable();
            $table->string('idsf', 50)->nullable();
            $table->timestamp('moved_at');
            $table->timestamps();
            $table->index(['serial_number', 'moved_at']);
            $table->index('from_id_outlet');
            $table->index('to_id_outlet');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surya_sa_consignment_movements');
    }
};
