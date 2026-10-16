<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('mobile_sales_receipts', 'receipt_photo_path')) {
            Schema::table('mobile_sales_receipts', function (Blueprint $table) {
                $table->string('receipt_photo_path')->nullable()->after('total_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mobile_sales_receipts', 'receipt_photo_path')) {
            Schema::table('mobile_sales_receipts', fn (Blueprint $table) => $table->dropColumn('receipt_photo_path'));
        }
    }
};
