<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('login', function (Blueprint $table) {
            $table->id();
            $table->string('idtap');
            $table->string('username')->nullable();
            $table->string('password')->unique();
            $table->enum('level', ['1','2']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login');
    }
};
