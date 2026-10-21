<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surya_outlet_sales_history', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->string('id_outlet', 40);
            $table->unsignedInteger('sa')->default(0);
            $table->unsignedInteger('pv')->default(0);
            $table->timestamps();

            $table->unique(['transaction_date', 'id_outlet'], 'surya_outlet_sales_history_unique');
            $table->index(['id_outlet', 'transaction_date'], 'surya_outlet_sales_history_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surya_outlet_sales_history');
    }
};
