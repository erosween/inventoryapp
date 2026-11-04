<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appsdumais', function (Blueprint $table) {
            $table->string('kategori_outlet', 30)->default('Reguler');
        });
    }

    public function down(): void
    {
        Schema::table('appsdumais', fn (Blueprint $table) => $table->dropColumn('kategori_outlet'));
    }
};
