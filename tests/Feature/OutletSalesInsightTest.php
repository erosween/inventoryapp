<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Services\OutletSalesInsight;
use Carbon\Carbon;
class OutletSalesInsightTest extends TestCase {
 protected function setUp():void{
 parent::setUp();config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
 Schema::create('keluar',fn(Blueprint$t)=>[$t->string('penerima'),$t->integer('status')]);
 Schema::create('denom',function(Blueprint$t){foreach(['iddenom','product','product_variant','group_name']as$c)$t->string($c);});
 foreach(['keluarsf','mobile_penjualan']as$table)Schema::create($table,function(Blueprint$t){$t->id();foreach(['id_outlet','idtap','iddenom','tgl','status','tambahanket']as$c)$t->string($c)->nullable();$t->integer('qty');});
 DB::table('denom')->insert([['iddenom'=>'V1','product'=>'PV','product_variant'=>'REGULER','group_name'=>'1 HARI'],['iddenom'=>'V2','product'=>'PV','product_variant'=>'BYU','group_name'=>'2 HARI'],['iddenom'=>'SP1','product'=>'SA','product_variant'=>'SIMPATI','group_name'=>'SA']]);
 foreach([['A','V1',10,'2026-09-26 12:00:00'],['A','V1',999,'2026-09-27 12:00:00'],['A','V1',30,'2026-10-26 12:00:00'],['B','V2',5,'2026-09-26 12:00:00'],['B','V2',10,'2026-10-26 12:00:00'],['C','V1',500,'2026-10-26 12:00:00'],['A','SP1',8,'2026-10-26 12:00:00']]as[$id,$denom,$qty,$date])DB::table('keluarsf')->insert(['id_outlet'=>$id,'idtap'=>$id==='C'?'DURI':'DUMAI','iddenom'=>$denom,'qty'=>$qty,'tgl'=>$date]);
 DB::table('mobile_penjualan')->insert([['id'=>1,'id_outlet'=>'A','idtap'=>'DUMAI','iddenom'=>'V1','qty'=>5,'tgl'=>'2026-10-26 12:00:00','status'=>'approved'],['id'=>2,'id_outlet'=>'A','idtap'=>'DUMAI','iddenom'=>'V1','qty'=>999,'tgl'=>'2026-10-26 12:00:00','status'=>'pending']]);
 DB::table('keluarsf')->insert(['id_outlet'=>'A','idtap'=>'DUMAI','iddenom'=>'V1','qty'=>5,'tgl'=>'2026-10-26 12:00:00','tambahanket'=>'Mobile Approval ID: 1']);
 }
 private function rows(bool$all=false){return collect(array_map(fn($r)=>(object)$r,array_merge([['id_outlet'=>'A','tap'=>'DUMAI','kecamatan'=>'Kota'],['id_outlet'=>'B','tap'=>'DUMAI','kecamatan'=>'Barat']],$all?[['id_outlet'=>'C','tap'=>'DURI','kecamatan'=>'Kota']]:[])));}
 private function report(string$p='pv_all',bool$all=false){return app(OutletSalesInsight::class)->data($this->rows($all),Carbon::parse('2026-10-26'),$p);}
 public function test_cutoff_sources_scope_and_region_contribution():void{
 $d=$this->report();$this->assertSame(45,$d['grand']['denominator']['current']);$this->assertSame(15,$d['grand']['denominator']['previous']);$this->assertSame(35,$d['grand']['values']['1D']['current']);$this->assertSame(10,$d['grand']['values']['2D']['current']);$this->assertSame(2,count($d['districtRows']));
 $this->assertSame(45,$d['tapRows'][0]['denominator']['current']);$this->assertSame(35,$d['districtRows'][1]['denominator']['current']);
 $html=view('surya.partials.outlet-sales-insight',['insight'=>$d])->render();$this->assertStringContainsString('77,8%',$html);$this->assertStringContainsString('+250,0%',$html);
 }
 public function test_variant_share_uses_all_family_not_only_selected_variant():void{
 $d=$this->report('pv_reg');$this->assertSame(45,$d['grand']['denominator']['current']);$this->assertSame(['1D'],$d['columns']);$this->assertSame(35,$d['grand']['values']['1D']['current']);
 $sa=$this->report('sa_all');$this->assertSame(8,$sa['grand']['denominator']['current']);$this->assertSame(['SA'],$sa['columns']);
 }
 public function test_same_district_name_in_different_taps_is_kept_separate_and_totals_reconcile():void{
 $d=$this->report('pv_all',true);$this->assertSame(3,count($d['districtRows']));$this->assertSame(545,$d['grand']['denominator']['current']);$this->assertSame(545,array_sum(array_map(fn($r)=>$r['denominator']['current'],$d['districtRows'])));$this->assertSame(545,array_sum(array_map(fn($r)=>$r['denominator']['current'],$d['tapRows'])));
 }
}
