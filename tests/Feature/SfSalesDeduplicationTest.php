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
 public function test_only_verified_approval_mirror_is_removed_not_real_identical_transactions():void{
  $this->assertSame(20,(int)SalesSource::legacy()->sum('qty'));
  DB::table('mobile_penjualan')->where('id',1)->update(['status'=>'rejected']);
  $this->assertSame(30,(int)SalesSource::legacy()->sum('qty'));
 }
 public function test_export_has_one_mobile_copy_and_attributes_helper_to_owner():void{
  $u=new User();$u->username='admin_super';$this->actingAs($u)->withSession(['idtap'=>'SBP_DUMAI']);Excel::fake();
  app(\App\Http\Controllers\SfkeluarController::class)->exportexcel(Request::create('/','GET',['daterange'=>'2026-10-26 - 2026-10-26']));
  Excel::assertDownloaded('PENJUALAN_SF_2026-10-26_sd_2026-10-26.xlsx',function($export){$rows=$export->collection();$this->assertSame(30,(int)$rows->sum('qty'));$this->assertSame(['Sales Owner'],$rows->pluck('namasf')->unique()->values()->all());return true;});
 }
}
