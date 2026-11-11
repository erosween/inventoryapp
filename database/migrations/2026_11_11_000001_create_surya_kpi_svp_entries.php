<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surya_kpi_svp_entries', function (Blueprint $table) {
            $table->date('period');
            $table->string('region', 12);
            $table->string('segment', 12);
            $table->unsignedTinyInteger('metric');
            $table->decimal('target', 20, 4)->nullable();
            $table->decimal('actual', 20, 4)->nullable();
            $table->date('actual_date')->nullable();
            $table->primary(['period', 'region', 'segment', 'metric'], 'surya_kpi_svp_entries_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surya_kpi_svp_entries');
    }
};
