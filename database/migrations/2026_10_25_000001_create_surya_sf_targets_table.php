<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('surya_sf_targets', function (Blueprint $table) {
            $table->id();
            $table->string('idsf', 25);
            $table->string('validity', 80);
            $table->enum('product_family', ['PV', 'SA']);
            $table->unsignedInteger('target_qty')->default(0);
            $table->timestamps();
            $table->unique(['idsf', 'product_family', 'validity']);
            $table->index(['product_family', 'validity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surya_sf_targets');
    }
};
