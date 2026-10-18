<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('surya_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('id_outlet')->index();
            $table->date('metric_date')->index();
            $table->unsignedInteger('ss_fm1')->default(0);
            $table->unsignedInteger('ss_m1')->default(0);
            $table->unsignedInteger('ss_m')->default(0);
            $table->unsignedInteger('hot_promo_fm1')->default(0);
            $table->unsignedInteger('hot_promo_m1')->default(0);
            $table->unsignedInteger('hot_promo_m')->default(0);
            $table->unsignedInteger('cvm_fm1')->default(0);
            $table->unsignedInteger('cvm_m1')->default(0);
            $table->unsignedInteger('cvm_m')->default(0);
            $table->unsignedInteger('insak_fm1')->default(0);
            $table->unsignedInteger('insak_m1')->default(0);
            $table->unsignedInteger('insak_m')->default(0);
            $table->unsignedInteger('comsak_fm1')->default(0);
            $table->unsignedInteger('comsak_m1')->default(0);
            $table->unsignedInteger('comsak_m')->default(0);
            $table->unsignedInteger('digital_fm1')->default(0);
            $table->unsignedInteger('digital_m1')->default(0);
            $table->unsignedInteger('digital_m')->default(0);
            $table->date('source_updated_at')->nullable();
            $table->timestamps();
            $table->unique(['id_outlet', 'metric_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('surya_daily_metrics'); }
};
