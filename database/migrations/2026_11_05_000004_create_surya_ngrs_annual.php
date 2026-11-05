<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('surya_ngrs_annual',function(Blueprint $t){$t->id();$t->unsignedSmallInteger('year');$t->string('rs',20);$t->string('tap',40);$t->string('cluster',40);foreach(['trx','rech']as$metric){foreach(range(1,12)as$m)$t->unsignedBigInteger($metric.'_m'.$m);$t->unsignedBigInteger($metric.'_total');}$t->unique(['year','rs','tap','cluster'],'ngrs_annual_unique');$t->index(['year','tap']);});}
 public function down():void {Schema::dropIfExists('surya_ngrs_annual');}
};
