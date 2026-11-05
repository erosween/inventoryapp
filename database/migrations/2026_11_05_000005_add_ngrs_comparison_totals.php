<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('surya_ngrs_annual',function(Blueprint $t){$t->unsignedBigInteger('compare_trx')->default(0);$t->unsignedBigInteger('compare_rech')->default(0);});}
 public function down():void {Schema::table('surya_ngrs_annual',fn(Blueprint $t)=>$t->dropColumn(['compare_trx','compare_rech']));}
};
