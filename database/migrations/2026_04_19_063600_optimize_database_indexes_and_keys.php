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
        // 1. Tambah Primary Key ke tabel master & stok
        Schema::table('idsf', function (Blueprint $table) {
            $table->primary('idsf');
        });

        Schema::table('stockawaltap', function (Blueprint $table) {
            $table->primary(['idtap', 'iddenom']);
        });

        Schema::table('stockawalsf', function (Blueprint $table) {
            $table->primary(['idsf', 'iddenom']);
        });

        // 2. Tambah Index pencarian ke tabel transaksi
        Schema::table('masuk', function (Blueprint $table) {
            $table->index('tgl');
            $table->index('iddenom');
        });

        Schema::table('keluar', function (Blueprint $table) {
            $table->index('tgl');
            $table->index('iddenom');
        });

        Schema::table('masuksf', function (Blueprint $table) {
            $table->index('tgl');
            $table->index('iddenom');
        });

        Schema::table('keluarsf', function (Blueprint $table) {
            $table->index('tgl');
            $table->index('iddenom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('idsf', function (Blueprint $table) {
            $table->dropPrimary(['idsf']);
        });

        Schema::table('stockawaltap', function (Blueprint $table) {
            $table->dropPrimary(['idtap', 'iddenom']);
        });

        Schema::table('stockawalsf', function (Blueprint $table) {
            $table->dropPrimary(['idsf', 'iddenom']);
        });

        Schema::table('masuk', function (Blueprint $table) {
            $table->dropIndex(['tgl']);
            $table->dropIndex(['iddenom']);
        });

        Schema::table('keluar', function (Blueprint $table) {
            $table->dropIndex(['tgl']);
            $table->dropIndex(['iddenom']);
        });

        Schema::table('masuksf', function (Blueprint $table) {
            $table->dropIndex(['tgl']);
            $table->dropIndex(['iddenom']);
        });

        Schema::table('keluarsf', function (Blueprint $table) {
            $table->dropIndex(['tgl']);
            $table->dropIndex(['iddenom']);
        });
    }
};
