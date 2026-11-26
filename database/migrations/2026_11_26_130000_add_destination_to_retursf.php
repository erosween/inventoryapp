<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retursf', function (Blueprint $table) {
            $table->string('return_type', 20)->default('warehouse')->after('idsf');
            $table->string('destination_idsf', 100)->nullable()->after('return_type');
        });
    }

    public function down(): void
    {
        Schema::table('retursf', function (Blueprint $table) {
            $table->dropColumn(['return_type', 'destination_idsf']);
        });
    }
};
