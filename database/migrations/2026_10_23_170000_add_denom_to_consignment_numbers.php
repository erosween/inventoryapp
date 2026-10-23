<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('surya_sa_consignment_numbers', 'iddenom')) {
            Schema::table('surya_sa_consignment_numbers', function (Blueprint $table) {
                $table->string('iddenom', 25)->nullable()->after('idsf')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('surya_sa_consignment_numbers', 'iddenom')) {
            Schema::table('surya_sa_consignment_numbers', function (Blueprint $table) {
                $table->dropIndex(['iddenom']);
                $table->dropColumn('iddenom');
            });
        }
    }
};
