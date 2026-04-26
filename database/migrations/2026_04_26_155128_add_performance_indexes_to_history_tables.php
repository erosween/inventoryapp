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
        Schema::table('appsdumais', function (Blueprint $table) {
            $table->index('id_outlet');
        });

        Schema::table('masuksf', function (Blueprint $table) {
            $table->index('idsf');
            $table->index('tgl');
        });

        Schema::table('keluarsf', function (Blueprint $table) {
            $table->index('idsf');
            $table->index('tgl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appsdumais', function (Blueprint $table) {
            $table->dropIndex(['id_outlet']);
        });

        Schema::table('masuksf', function (Blueprint $table) {
            $table->dropIndex(['idsf']);
            $table->dropIndex(['tgl']);
        });

        Schema::table('keluarsf', function (Blueprint $table) {
            $table->dropIndex(['idsf']);
            $table->dropIndex(['tgl']);
        });
    }
};
