<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('surya_visits', 'stock_owner_idsf')) {
            Schema::table('surya_visits', function (Blueprint $table) {
                $table->string('stock_owner_idsf', 25)->nullable()->after('visit_type')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('surya_visits', 'stock_owner_idsf')) {
            Schema::table('surya_visits', fn (Blueprint $table) => $table->dropColumn('stock_owner_idsf'));
        }
    }
};
