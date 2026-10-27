<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('taskforce_push_so_actions', function (Blueprint $table) {
            $table->id();
            $table->string('id_outlet', 50);
            $table->date('period_month');
            $table->boolean('visited');
            $table->date('visited_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('user_id', 50);
            $table->string('username', 150);
            $table->timestamps();
            $table->index(['period_month', 'id_outlet', 'id'], 'push_so_outlet_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskforce_push_so_actions');
    }
};
