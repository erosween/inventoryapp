<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('surya_outlet_branding_overrides', function (Blueprint $table) {
            $table->string('id_outlet', 20)->primary();
            $table->string('branding_dominance', 24);
            $table->string('branding_quality', 24)->nullable();
            $table->unsignedBigInteger('updated_by');
            $table->timestamp('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surya_outlet_branding_overrides');
    }
};
