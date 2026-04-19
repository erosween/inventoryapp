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
        Schema::table('stockawaltap', function (Blueprint $table) {
            $table->dropColumn(['cluster', 'denom']);
        });

        Schema::table('stockawalfeb', function (Blueprint $table) {
            $table->dropColumn(['cluster', 'denom']);
        });

        Schema::table('stockawalsf', function (Blueprint $table) {
            $table->dropColumn(['idtap', 'namasf']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stockawaltap', function (Blueprint $table) {
            $table->string('cluster', 100)->nullable();
            $table->string('denom', 25)->nullable();
        });

        Schema::table('stockawalfeb', function (Blueprint $table) {
            $table->string('cluster', 100)->nullable();
            $table->string('denom', 25)->nullable();
        });

        Schema::table('stockawalsf', function (Blueprint $table) {
            $table->string('idtap', 24)->nullable();
            $table->string('namasf', 100)->nullable();
        });
    }
};
