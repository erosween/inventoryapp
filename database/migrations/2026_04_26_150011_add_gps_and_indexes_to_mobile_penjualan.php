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
        Schema::table('mobile_penjualan', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('qty');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            
            // Performance Indexes
            $table->index('idsf');
            $table->index('tgl');
            $table->index('id_outlet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobile_penjualan', function (Blueprint $table) {
            $table->dropIndex(['idsf']);
            $table->dropIndex(['tgl']);
            $table->dropIndex(['id_outlet']);
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
