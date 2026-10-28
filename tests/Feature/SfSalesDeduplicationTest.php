<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Support\SalesSource;
use App\Models\User;
class SfSalesDeduplicationTest extends TestCase {
 protected function setUp():void{
  parent::setUp();config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
  Schema::create('keluarsf',function(Blueprint$t){$t->id();foreach(['tgl','idtap','idsf','iddenom','tambahanket']as$c)$t->string($c)->nullable();$t->integer('qty');});
  Schema::create('mobile_penjualan',function(Blueprint$t){$t->id();foreach(['tgl','idtap','idsf','owner_idsf','iddenom','status']as$c)$t->string($c)->nullable();$t->integer('qty');});
  Schema::create('denom',function(Blueprint$t){foreach(['iddenom','denom','product','product_variant']as$c)$t->string($c);});
  Schema::create('idsf',function(Blueprint$t){$t->string('idsf');$t->string('namasf');});
  DB::table('denom')->insert(['iddenom'=>'V1','denom'=>'1GB','product'=>'PV','product_variant'=>'REGULER']);
  DB::table('idsf')->insert([['idsf'=>'SF1','namasf'=>'Sales Owner'],['idsf'=>'HELPER','namasf'=>'Helper']]);
  DB::table('mobile_penjualan')->insert(['id'=>1,'tgl'=>'2026-10-26 00:00:00','idtap'=>'DUMAI','idsf'=>'HELPER','owner_idsf'=>'SF1','iddenom'=>'V1','status'=>'approved','qty'=>10]);
  foreach([null,'Mobile Approval ID: 1','Mobile Approval ID: 999']as$note)DB::table('keluarsf')->insert(['tgl'=>'2026-10-26 00:00:00','idtap'=>'DUMAI','idsf'=>'SF1','iddenom'=>'V1','qty'=>10,'tambahanket'=>$note]);
 }
 public function test_posted_journal_is_kept_and_mobile_is_suppressed_by_identity():void{
  $this->assertSame(30,(int)SalesSource::legacy()->sum('qty'));
  $this->assertSame(0,(int)SalesSource::mobile()->sum('qty'));
  DB::table('mobile_penjualan')->where('id',1)->update(['status'=>'rejected']);
  $this->assertSame(30,(int)SalesSource::legacy()->sum('qty'));
 }
 public function test_export_has_one_mobile_copy_and_attributes_helper_to_owner():void{
  $u=new User();$u->username='admin_super';$this->actingAs($u)->withSession(['idtap'=>'SBP_DUMAI']);Excel::fake();
  app(\App\Http\Controllers\SfkeluarController::class)->exportexcel(Request::create('/','GET',['daterange'=>'2026-10-26 - 2026-10-26']));
  Excel::assertDownloaded('PENJUALAN_SF_2026-10-26_sd_2026-10-26.xlsx',function($export){$rows=$export->collection();$this->assertSame(30,(int)$rows->sum('qty'));$this->assertSame(['Sales Owner'],$rows->pluck('namasf')->unique()->values()->all());return true;});
 }
 public function test_export_uses_posted_qty_even_if_mobile_qty_date_product_or_tap_changes():void {
  foreach ([['qty'=>40],['tgl'=>'2026-10-17 00:00:00'],['iddenom'=>'OTHER'],['idtap'=>'OTHER']] as $change) {
   DB::table('mobile_penjualan')->where('id',1)->update($change);
   $this->assertSame(30,(int)SalesSource::legacy()->sum('qty')+(int)SalesSource::mobile()->sum('qty'));
  }
  $u=new User();$u->username='admin_super';$this->actingAs($u)->withSession(['idtap'=>'SBP_DUMAI']);Excel::fake();
  app(\App\Http\Controllers\SfkeluarController::class)->exportexcel(Request::create('/','GET',['daterange'=>'2026-10-26 - 2026-10-26']));
  Excel::assertDownloaded('PENJUALAN_SF_2026-10-26_sd_2026-10-26.xlsx',function($export){$this->assertSame(30,(int)$export->collection()->sum('qty'));return true;});
 }
 public function test_approved_mobile_without_posted_journal_is_still_included():void {
  DB::table('mobile_penjualan')->insert(['id'=>2,'tgl'=>'2026-10-26','idtap'=>'DUMAI','idsf'=>'SF1','iddenom'=>'V1','qty'=>7,'status'=>'approved']);
  $this->assertSame(7,(int)SalesSource::mobile()->sum('qty'));
 }
 private function prepareNewApproval():void {
  Schema::table('keluarsf',fn(Blueprint $t)=>$t->string('id_outlet')->nullable());
  Schema::table('mobile_penjualan',function(Blueprint $t){$t->string('id_outlet')->nullable();$t->timestamp('stock_reserved_at')->nullable();$t->unsignedInteger('receipt_id')->nullable();$t->timestamp('updated_at')->nullable();});
  Schema::create('stockawalsf',function(Blueprint $t){$t->string('idsf');$t->string('iddenom');$t->integer('stock');});
  DB::table('stockawalsf')->insert(['idsf'=>'SF1','iddenom'=>'V1','stock'=>100]);
  DB::table('mobile_penjualan')->insert(['id'=>2,'tgl'=>now()->startOfDay()->toDateTimeString(),'idtap'=>'DUMAI','idsf'=>'HELPER','owner_idsf'=>'SF1','id_outlet'=>'OUTLET-A','iddenom'=>'V1','status'=>'pending','qty'=>7]);
  $u=new User();$u->username='admin_super';$this->actingAs($u)->withSession(['idtap'=>'SBP_DUMAI']);
 }
 public function test_new_approval_and_retry_count_sales_and_deduct_stock_only_once():void {
  $this->prepareNewApproval();$controller=app(\App\Http\Controllers\MobileApprovalController::class);
  $first=$controller->approve(new Request(['id'=>2]))->getData(true);$this->assertTrue($first['success']);
  $second=$controller->approve(new Request(['id'=>2]))->getData(true);$this->assertFalse($second['success']);
  $this->assertSame(1,DB::table('keluarsf')->where('tambahanket','Mobile Approval ID: 2')->count());
  $this->assertSame(93,(int)DB::table('stockawalsf')->value('stock'));
  $this->assertSame(37,(int)SalesSource::legacy()->sum('qty')+(int)SalesSource::mobile()->sum('qty'));
 }
 public function test_bulk_approval_with_repeated_id_is_safe():void {
  $this->prepareNewApproval();$r=app(\App\Http\Controllers\MobileApprovalController::class)->bulkApprove(new Request(['ids'=>[2,2]]))->getData(true);
  $this->assertSame(1,$r['details']['success']);$this->assertSame(1,$r['details']['failed']);$this->assertSame(93,(int)DB::table('stockawalsf')->value('stock'));
  $this->assertSame(1,DB::table('keluarsf')->where('tambahanket','Mobile Approval ID: 2')->count());
 }
 public function test_duplicate_guard_is_not_limited_to_october_26_27_or_28():void {
  DB::table('keluarsf')->where('tambahanket','Mobile Approval ID: 1')->update(['tgl'=>'2031-01-15 00:00:00']);
  DB::table('mobile_penjualan')->where('id',1)->update(['tgl'=>'2031-01-15 00:00:00']);
  $this->assertSame(10,(int)SalesSource::legacy()->whereDate('tgl','2031-01-15')->sum('qty'));
  $this->assertSame(0,(int)SalesSource::mobile()->whereDate('tgl','2031-01-15')->sum('qty'));
 }

}
