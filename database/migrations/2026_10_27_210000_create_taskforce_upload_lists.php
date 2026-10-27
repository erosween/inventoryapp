<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('taskforce_uploads', function (Blueprint $table) {
            $table->id();
            $table->date('period_month');
            $table->string('filename');
            $table->unsignedInteger('outlet_count');
            $table->string('username',150);
            $table->timestamps();
            $table->index(['period_month','id']);
        });
        Schema::create('taskforce_upload_outlets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('upload_id');
            $table->string('id_outlet',50);
            $table->unique(['upload_id','id_outlet']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskforce_upload_outlets');
        Schema::dropIfExists('taskforce_uploads');
    }
};
