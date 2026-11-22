<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_tracking_digipos', function (Blueprint $table) {
            $table->index(['id_outlet', 'kind', 'serial_start', 'serial_end'], 'pt_digipos_redeem_overlap_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_tracking_digipos', function (Blueprint $table) {
            $table->dropIndex('pt_digipos_redeem_overlap_idx');
        });
    }
};
