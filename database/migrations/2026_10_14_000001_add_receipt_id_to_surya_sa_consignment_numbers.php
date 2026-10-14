<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('surya_sa_consignment_numbers', 'receipt_id')) {
            Schema::table('surya_sa_consignment_numbers', function (Blueprint $table) {
                $table->unsignedBigInteger('receipt_id')->nullable()->after('id');
                $table->index('receipt_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('surya_sa_consignment_numbers', 'receipt_id')) {
            Schema::table('surya_sa_consignment_numbers', function (Blueprint $table) {
                $table->dropIndex(['receipt_id']);
                $table->dropColumn('receipt_id');
            });
        }
    }
};
