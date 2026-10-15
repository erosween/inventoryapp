<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('surya_visits', 'checkout_at')) {
            Schema::table('surya_visits', function (Blueprint $table) {
                $table->timestamp('checkout_at')->nullable()->after('visited_at');
            });
        }
        if (! Schema::hasColumn('surya_visits', 'checkout_reason')) {
            Schema::table('surya_visits', function (Blueprint $table) {
                $table->string('checkout_reason', 80)->nullable()->after('checkout_at');
            });
        }
        if (! Schema::hasColumn('surya_visits', 'checkout_notes')) {
            Schema::table('surya_visits', function (Blueprint $table) {
                $table->string('checkout_notes', 500)->nullable()->after('checkout_reason');
            });
        }
    }

    public function down(): void
    {
        // Kolom ini mungkin sudah ada dari instalasi sebelumnya. Jangan hapus
        // data operasional ketika migration di-rollback.
    }
};
