<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_tracking_digipos', function (Blueprint $table) {
            $table->index(['scan_date', 'serial_start', 'serial_end'], 'pt_digipos_scan_overlap_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_tracking_digipos', function (Blueprint $table) {
            $table->dropIndex('pt_digipos_scan_overlap_idx');
        });
    }
};
