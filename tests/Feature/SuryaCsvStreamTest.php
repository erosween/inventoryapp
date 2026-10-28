<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Services\SuryaCsvReader;
use App\Services\SuryaMetricsImport;
class SuryaCsvStreamTest extends TestCase {
 protected function setUp():void {
  parent::setUp();config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');Storage::fake('local');
  foreach(['2026_10_17_000004_create_surya_daily_metrics_table.php','2026_10_22_000002_add_biometric_metrics_to_surya_daily_metrics.php']as$m)(require database_path('migrations/'.$m))->up();
  Schema::table('surya_daily_metrics',fn($t)=>$t->string('uploaded_metrics')->nullable());
  foreach(['2026_10_28_140000_split_surya_trx_cvm_and_biometrik.php','2026_10_28_150000_use_latest_surya_transaction_snapshots.php']as$m)(require database_path('migrations/'.$m))->up();
 }
 private function path(string $contents):string{$p=tempnam(sys_get_temp_dir(),'csv-stream-');file_put_contents($p,$contents);$this->beforeApplicationDestroyed(fn()=>@unlink($p));return $p;}
 public function test_cursor_retry_and_atomic_replacement_across_batches():void {
  DB::table('surya_trx_cvm')->insert(['id_outlet'=>'OLD','metric_date'=>'2026-10-27','cvm_m'=>99]);DB::table('surya_biometrik')->insert(['id_outlet'=>'BIO','metric_date'=>'2026-10-27','bio_m'=>42]);
  $csv="id_outlet,cvm_m,update\n";for($i=0;$i<1005;$i++)$csv.="CSV-$i,$i,2026-10-28\n";$csv.="CSV-0,2000,2026-10-28\n";
  $s=app(SuryaMetricsImport::class);$state=$s->start(new UploadedFile($this->path($csv),'trx_cvm.csv',null,null,true),42);
  $state=$s->advance($state['id'],42,2);$this->assertSame(1002,$state['next_row']);$this->assertSame(99,(int)DB::table('surya_trx_cvm')->value('cvm_m'));
  $retry=$s->advance($state['id'],42,2);$this->assertSame($state,$retry);
  $state=$s->advance($state['id'],42,$state['next_row']);$this->assertSame('complete',$state['status']);$this->assertSame(1005,DB::table('surya_trx_cvm')->count());$this->assertSame(2000,(int)DB::table('surya_trx_cvm')->where('id_outlet','CSV-0')->value('cvm_m'));$this->assertSame(42,(int)DB::table('surya_biometrik')->value('bio_m'));
 }
 public function test_bom_semicolon_quotes_and_grouped_quantities():void {
  $p=$this->path("\xEF\xBB\xBFid_outlet;cvm_m;update;catatan\r\n00123;\"1.234\";27/10/2026;\"baris pertama\nbaris kedua\"\r\n");
  $r=app(SuryaCsvReader::class);$m=$r->inspect($p,'cvm');$this->assertSame(';',$m['delimiter']);$this->assertSame(2,$m['total_rows']);[$rows]=$r->chunk($p,$m,2,2);$this->assertSame('00123',$rows[2]['A']);$this->assertSame('1234',$rows[2]['B']);$this->assertSame('2026-10-27',$rows[2]['C']);$this->assertStringContainsString("\n",$rows[2]['D']);
 }
 public function test_invalid_csv_never_replaces_active_data():void {
  DB::table('surya_trx_cvm')->insert(['id_outlet'=>'OLD','metric_date'=>'2026-10-27','cvm_m'=>99]);
  $s=app(SuryaMetricsImport::class);$state=$s->start(new UploadedFile($this->path("id_outlet,cvm_m,update\nA,abc,2026-10-28\n"),'cvm.csv',null,null,true),42);
  try{$s->advance($state['id'],42,2);$this->fail('Invalid qty accepted');}catch(\Illuminate\Validation\ValidationException$e){$this->assertSame(99,(int)DB::table('surya_trx_cvm')->value('cvm_m'));}
 }
 public function test_excel_extension_is_rejected_before_parsing():void {
  $this->expectException(\Illuminate\Validation\ValidationException::class);app(SuryaMetricsImport::class)->inspect($this->path('PK not CSV'),'cvm.xlsx');
 }
}
