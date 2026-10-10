<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surya_sa_consignment_numbers', function (Blueprint $table) {
            $table->id();
            $table->dateTime('reported_at');
            $table->string('id_outlet', 100);
            $table->string('idtap', 25);
            $table->string('idsf', 25);
            $table->string('serial_number', 30);
            $table->string('status', 20)->default('consigned');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['serial_number', 'status']);
            $table->index(['id_outlet', 'reported_at']);
        });

        Schema::create('surya_competitor_surveys', function (Blueprint $table) {
            $table->id();
            $table->dateTime('reported_at');
            $table->string('id_outlet', 100);
            $table->string('idtap', 25);
            $table->string('idsf', 25);
            $table->string('operator', 60);
            $table->string('product')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->integer('estimated_stock')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['id_outlet', 'reported_at']);
        });

        Schema::create('surya_network_reports', function (Blueprint $table) {
            $table->id();
            $table->dateTime('reported_at');
            $table->string('id_outlet', 100);
            $table->string('idtap', 25);
            $table->string('idsf', 25);
            $table->string('operator', 30)->default('TELKOMSEL');
            $table->string('signal_status', 30);
            $table->string('data_status', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['id_outlet', 'reported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surya_network_reports');
        Schema::dropIfExists('surya_competitor_surveys');
        Schema::dropIfExists('surya_sa_consignment_numbers');
    }
};
