<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'surya_visits_type_outlet_id_idx';

    public function up(): void
    {
        if (! Schema::hasIndex('surya_visits', self::INDEX)) {
            Schema::table('surya_visits', function (Blueprint $table) {
                $table->index(['visit_type', 'id_outlet', 'id'], self::INDEX);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('surya_visits', self::INDEX)) {
            Schema::table('surya_visits', function (Blueprint $table) {
                $table->dropIndex(self::INDEX);
            });
        }
    }
};
