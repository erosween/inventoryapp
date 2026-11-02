<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('idsf', function (Blueprint $table) {
            $table->string('digipos_id', 50)->nullable()->unique();
        });
    }
    public function down(): void
    {
        Schema::table('idsf', fn (Blueprint $table) => $table->dropColumn('digipos_id'));
    }
};
