<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('surya_transaction_uploads',function(Blueprint $t){$t->id();$t->string('kind',20);$t->string('status',20)->default('processing');$t->timestamps();});
  Schema::create('surya_transaction_staging',function(Blueprint $t){$t->id();$t->unsignedBigInteger('upload_id')->index();$t->string('id_outlet');$t->longText('data');$t->unique(['upload_id','id_outlet']);});
  Schema::create('surya_transaction_current',function(Blueprint $t){$t->string('kind',20)->primary();$t->unsignedBigInteger('upload_id')->default(0);});
  foreach(['cvm'=>'surya_trx_cvm','biometrik'=>'surya_biometrik'] as $kind=>$table){
   DB::table('surya_transaction_current')->insert(['kind'=>$kind,'upload_id'=>0]);
   // Retain the latest dated row for every outlet before enforcing one-row-per-outlet.
   $latest = DB::table($table)->select('id_outlet')->selectRaw('MAX(metric_date) AS latest_date')->groupBy('id_outlet')->pluck('latest_date','id_outlet');
   DB::table($table)->orderBy('id')->chunkById(500,function($rows)use($table,$latest){
    $ids=$rows->filter(fn($r)=>$r->metric_date < $latest[$r->id_outlet])->pluck('id');
    if($ids->isNotEmpty()) DB::table($table)->whereIn('id',$ids)->delete();
   });
   Schema::table($table,fn(Blueprint $t)=>$t->unique('id_outlet'));
  }
 }
 public function down():void {throw new RuntimeException('Snapshot rollback requires explicit data recovery.');}
};
