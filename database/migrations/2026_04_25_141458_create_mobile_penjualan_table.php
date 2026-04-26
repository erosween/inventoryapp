<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mobile_penjualan', function (Blueprint $table) {
            $table->id();
            $table->date('tgl');
            $table->string('idtap', 25);
            $table->string('idsf', 25);
            $table->string('iddenom', 25);
            $table->integer('qty');
            $table->text('keterangan')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_penjualan');
    }
};
