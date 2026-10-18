<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('mobile_penjualan', 'owner_idsf')) {
            Schema::table('mobile_penjualan', function (Blueprint $table) {
                $table->string('owner_idsf', 25)->nullable()->after('idsf')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mobile_penjualan', 'owner_idsf')) {
            Schema::table('mobile_penjualan', fn (Blueprint $table) => $table->dropColumn('owner_idsf'));
        }
    }
};
