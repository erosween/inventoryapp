<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('taskforce_uploads',function(Blueprint $table) { $table->longText('headers')->nullable(); });
        Schema::table('taskforce_upload_outlets',function(Blueprint $table) {
            $table->longText('cells')->nullable();
            $table->unsignedInteger('row_position')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('taskforce_upload_outlets',fn(Blueprint $table)=>$table->dropColumn(['cells','row_position']));
        Schema::table('taskforce_uploads',fn(Blueprint $table)=>$table->dropColumn('headers'));
    }
};
