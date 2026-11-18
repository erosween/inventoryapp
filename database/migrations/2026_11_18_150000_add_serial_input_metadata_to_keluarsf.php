<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('keluarsf', 'serial_mode')) {
            Schema::table('keluarsf', fn (Blueprint $table) => $table->string('serial_mode', 12)->nullable());
        }

        if (! Schema::hasColumn('keluarsf', 'identifier_type')) {
            Schema::table('keluarsf', fn (Blueprint $table) => $table->string('identifier_type', 12)->nullable());
        }
    }

    public function down(): void
    {
        $columns = array_values(array_filter(
            ['serial_mode', 'identifier_type'],
            fn ($column) => Schema::hasColumn('keluarsf', $column)
        ));

        if ($columns) {
            Schema::table('keluarsf', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
