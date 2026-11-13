<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masuk', function (Blueprint $table) {
            // Existing DOs still use the destination implied by their original product code.
            $table->boolean('do_received_at_bo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('masuk', function (Blueprint $table) {
            $table->dropColumn('do_received_at_bo');
        });
    }
};
