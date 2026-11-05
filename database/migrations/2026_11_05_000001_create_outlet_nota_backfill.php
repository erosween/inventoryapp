<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('surya_outlet_nota_backfill', function (Blueprint $t) {
            $t->date('transaction_date');
            $t->string('id_outlet', 40);
            $t->unsignedInteger('sa');
            $t->unsignedInteger('pv');
            $t->timestamps();
            $t->primary(['transaction_date', 'id_outlet']);
            $t->index(['id_outlet', 'transaction_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('surya_outlet_nota_backfill'); }
};
