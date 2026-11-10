<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('surya_outlet_cvm', function (Blueprint $table) {
            $table->date('transaction_date');
            $table->string('id_outlet', 40);
            foreach (['cvm', 'comsak', 'insak', 'hot_promo', 'super_seru', 'byu', 'digital'] as $column) {
                $table->unsignedInteger($column)->nullable();
            }
            $table->primary(['transaction_date', 'id_outlet']);
            $table->index(['id_outlet', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surya_outlet_cvm');
    }
};
