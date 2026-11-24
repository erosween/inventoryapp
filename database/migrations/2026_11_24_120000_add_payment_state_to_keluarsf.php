<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('keluarsf', 'payment_status')) {
            Schema::table('keluarsf', fn (Blueprint $table) => $table->string('payment_status', 12)->default('cash'));
        }
        if (! Schema::hasColumn('keluarsf', 'paid_at')) {
            Schema::table('keluarsf', fn (Blueprint $table) => $table->dateTime('paid_at')->nullable());
        }
    }

    public function down(): void
    {
        $columns = array_values(array_filter(
            ['payment_status', 'paid_at'],
            fn (string $column) => Schema::hasColumn('keluarsf', $column)
        ));

        if ($columns) {
            Schema::table('keluarsf', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
