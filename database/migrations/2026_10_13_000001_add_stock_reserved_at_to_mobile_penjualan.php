<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mobile_penjualan', 'stock_reserved_at')) {
            Schema::table('mobile_penjualan', function (Blueprint $table) {
                $table->timestamp('stock_reserved_at')->nullable()->after('status')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mobile_penjualan', 'stock_reserved_at')) {
            Schema::table('mobile_penjualan', function (Blueprint $table) {
                $table->dropColumn('stock_reserved_at');
            });
        }
    }
};
