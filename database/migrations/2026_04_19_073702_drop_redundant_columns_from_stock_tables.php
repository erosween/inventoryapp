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
            if (Schema::hasColumn('stockawaltap', 'cluster')) {
                $table->dropColumn('cluster');
            }
            if (Schema::hasColumn('stockawaltap', 'denom')) {
                $table->dropColumn('denom');
            }
        });

        Schema::table('stockawalfeb', function (Blueprint $table) {
            if (Schema::hasColumn('stockawalfeb', 'cluster')) {
                $table->dropColumn('cluster');
            }
            if (Schema::hasColumn('stockawalfeb', 'denom')) {
                $table->dropColumn('denom');
            }
        });

        Schema::table('stockawalsf', function (Blueprint $table) {
            if (Schema::hasColumn('stockawalsf', 'idtap')) {
                $table->dropColumn('idtap');
            }
            if (Schema::hasColumn('stockawalsf', 'namasf')) {
                $table->dropColumn('namasf');
            }
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
