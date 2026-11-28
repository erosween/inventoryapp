<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('appsdumais', 'freq')) {
            Schema::table('appsdumais', function (Blueprint $table) {
                $table->string('freq', 30)->default('UNMAPPING')->after('kategori_outlet');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('appsdumais', 'freq')) {
            Schema::table('appsdumais', fn (Blueprint $table) => $table->dropColumn('freq'));
        }
    }
};
