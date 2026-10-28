<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Services\SuryaMetricsImport;
class SuryaTransactionSnapshotTest extends TestCase {
 protected function setUp():void{
  parent::setUp();config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
  foreach(['surya_trx_cvm','surya_biometrik']as$table)Schema::create($table,function(Blueprint$t){$t->id();$t->string('id_outlet');$t->date('metric_date');$t->integer('value')->default(0);$t->unique(['id_outlet','metric_date']);});
  DB::table('surya_trx_cvm')->insert([['id_outlet'=>'A','metric_date'=>'2026-10-25','value'=>1],['id_outlet'=>'A','metric_date'=>'2026-10-26','value'=>2],['id_outlet'=>'B','metric_date'=>'2026-10-26','value'=>3]]);
  (require database_path('migrations/2026_10_28_150000_use_latest_surya_transaction_snapshots.php'))->up();
 }
 private function stage(int$id,string$outlet,int$value):void{DB::table('surya_transaction_staging')->updateOrInsert(['upload_id'=>$id,'id_outlet'=>$outlet],['data'=>json_encode(['id_outlet'=>$outlet,'metric_date'=>'2026-10-28','value'=>$value])]);}
 public function test_replaces_only_completed_type_deduplicates_and_older_upload_cannot_overwrite_newer():void{
  $this->assertSame(2,DB::table('surya_trx_cvm')->count());$this->assertSame(2,(int)DB::table('surya_trx_cvm')->where('id_outlet','A')->value('value'));
  DB::table('surya_biometrik')->insert(['id_outlet'=>'Z','metric_date'=>'2026-10-25','value'=>8]);
  $this->stage(2,'A',10);$this->stage(2,'A',20);$this->stage(2,'C',30);
  $this->assertSame(2,(int)DB::table('surya_trx_cvm')->where('id_outlet','A')->value('value'));
  $s=app(SuryaMetricsImport::class);$s->publishSnapshot(['snapshot_id'=>2,'type'=>'metrics']);
  $this->assertSame(['A','C'],DB::table('surya_trx_cvm')->orderBy('id_outlet')->pluck('id_outlet')->all());
  $this->assertSame(20,(int)DB::table('surya_trx_cvm')->where('id_outlet','A')->value('value'));
  $this->assertSame(8,(int)DB::table('surya_biometrik')->value('value'));
  $s->publishSnapshot(['snapshot_id'=>2,'type'=>'metrics']);
  $this->stage(1,'OLD',99);$s->publishSnapshot(['snapshot_id'=>1,'type'=>'metrics']);
  $this->assertSame(0,DB::table('surya_trx_cvm')->where('id_outlet','OLD')->count());
 }
 public function test_empty_upload_does_not_delete_current_data():void{
  try{app(SuryaMetricsImport::class)->publishSnapshot(['snapshot_id'=>3,'type'=>'metrics']);$this->fail('Empty accepted');}catch(\Illuminate\Validation\ValidationException$e){$this->assertSame(2,DB::table('surya_trx_cvm')->count());}
 }
}
