<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('keluarsf', 'id_outlet')) {
            Schema::table('keluarsf', function (Blueprint $table) {
                $table->string('id_outlet', 100)->nullable()->after('idsf')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('keluarsf', 'id_outlet')) {
            Schema::table('keluarsf', fn (Blueprint $table) => $table->dropColumn('id_outlet'));
        }
    }
};
