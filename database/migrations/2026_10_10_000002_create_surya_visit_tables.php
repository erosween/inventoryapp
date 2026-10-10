<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('appsdumais', 'latitude')) {
            Schema::table('appsdumais', function (Blueprint $table) {
                $table->decimal('latitude', 10, 8)->nullable()->after('id_outlet');
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            });
        }

        if (! Schema::hasTable('surya_visits')) {
            Schema::create('surya_visits', function (Blueprint $table) {
                $table->id();
                $table->dateTime('visited_at');
                $table->string('id_outlet', 100);
                $table->string('idtap', 25);
                $table->string('idsf', 25);
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->decimal('accuracy', 8, 2)->nullable();
                $table->decimal('distance_meters', 10, 2)->nullable();
                $table->string('photo_path')->nullable();
                $table->enum('gps_status', ['valid', 'invalid'])->default('valid');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['idsf', 'id_outlet', 'visited_at']);
                $table->index(['idtap', 'visited_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('surya_visits');
        if (Schema::hasColumn('appsdumais', 'latitude')) {
            Schema::table('appsdumais', function (Blueprint $table) {
                $table->dropColumn(['latitude', 'longitude']);
            });
        }
    }
};
