<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('surya_sa_consignment_numbers', 'active_serial')) {
            return;
        }

        Schema::table('surya_sa_consignment_numbers', function (Blueprint $table) {
            $table->string('active_serial', 30)->nullable()->storedAs(
                "CASE WHEN status IN ('draft', 'consigned', 'approved') THEN serial_number ELSE NULL END"
            );
            $table->unique('active_serial', 'surya_consignment_active_serial_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('surya_sa_consignment_numbers', 'active_serial')) {
            return;
        }

        Schema::table('surya_sa_consignment_numbers', function (Blueprint $table) {
            $table->dropUnique('surya_consignment_active_serial_unique');
            $table->dropColumn('active_serial');
        });
    }
};
