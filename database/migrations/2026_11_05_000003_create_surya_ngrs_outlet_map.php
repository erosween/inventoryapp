<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {Schema::create('surya_ngrs_outlet_map',function(Blueprint $t){$t->string('rs',20)->primary();$t->unsignedInteger('candidates');$t->string('id_outlet')->nullable();$t->string('nama_outlet')->nullable();$t->string('master_tap',100)->nullable();$t->index('master_tap');});}
 public function down(): void {Schema::dropIfExists('surya_ngrs_outlet_map');}
};
