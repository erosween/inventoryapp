<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_tracking_digipos', function (Blueprint $table) {
            $table->date('scan_date')->nullable()->after('kind');
            $table->string('serial_start', 24)->nullable()->after('scan_date');
            $table->string('serial_end', 24)->nullable()->after('serial_start');
            $table->unsignedInteger('serial_count')->default(1)->after('serial_end');
            $table->index(['kind', 'serial_start', 'serial_end'], 'pt_digipos_kind_range_idx');
        });

    }

    public function down(): void
    {
        Schema::table('product_tracking_digipos', function (Blueprint $table) {
            $table->dropIndex('pt_digipos_kind_range_idx');
            $table->dropColumn(['scan_date', 'serial_start', 'serial_end', 'serial_count']);
        });
    }
};
