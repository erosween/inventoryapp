<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\OutletColumnFilters;
use Illuminate\Http\Request;
use Carbon\Carbon;
class OutletColumnFiltersTest extends TestCase {
 protected function setUp():void{
  parent::setUp();config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
  Schema::create('appsdumais',function(Blueprint$t){foreach(OutletColumnFilters::TEXT as$c)$t->string($c)->nullable();});
  Schema::create('denom',function(Blueprint$t){$t->string('iddenom');$t->string('product_group');});
  foreach(['keluarsf','mobile_penjualan']as$table)Schema::create($table,function(Blueprint$t){$t->string('id_outlet');$t->string('iddenom');$t->date('tgl');$t->integer('qty');$t->string('status')->nullable();});
  Schema::create('surya_outlet_sales_history',function(Blueprint$t){$t->string('id_outlet');$t->date('transaction_date');$t->integer('sa');$t->integer('pv');});
  foreach(['surya_trx_cvm'=>['ss','cvm','hot_promo','comsak'],'surya_biometrik'=>['bio']]as$table=>$metrics)Schema::create($table,function(Blueprint$t)use($metrics){$t->string('id_outlet')->unique();$t->string('uploaded_metrics')->nullable();foreach($metrics as$m)foreach(['fm1','m1','m']as$p)$t->integer($m.'_'.$p)->default(0);});
  foreach([['A','DUMAI'],['B','DUMAI'],['C','DURI']]as[$id,$tap])DB::table('appsdumais')->insert(['id_outlet'=>$id,'nama_outlet'=>'Outlet '.$id,'tap'=>$tap,'sf'=>'SF '.$tap,'pjp'=>'Senin']);
  DB::table('denom')->insert(['iddenom'=>'V1','product_group'=>'PV']);
  DB::table('keluarsf')->insert(['id_outlet'=>'A','iddenom'=>'V1','tgl'=>'2026-10-23','qty'=>10]);
  foreach([['approved',5],['pending',999]]as[$status,$qty])DB::table('mobile_penjualan')->insert(['id_outlet'=>'A','iddenom'=>'V1','tgl'=>'2026-10-24','qty'=>$qty,'status'=>$status]);
  DB::table('surya_outlet_sales_history')->insert([['id_outlet'=>'A','transaction_date'=>'2026-09-24','sa'=>1,'pv'=>20],['id_outlet'=>'A','transaction_date'=>'2026-09-29','sa'=>1,'pv'=>10]]);
  DB::table('surya_trx_cvm')->insert([['id_outlet'=>'A','cvm_fm1'=>8,'cvm_m1'=>4,'cvm_m'=>2],['id_outlet'=>'B','cvm_fm1'=>8,'cvm_m1'=>4,'cvm_m'=>10],['id_outlet'=>'C','cvm_fm1'=>8,'cvm_m1'=>4,'cvm_m'=>999]]);
 }
 private function query(array$o,?string$facet=null){$s=app(OutletColumnFilters::class);return $s->query(DB::table('appsdumais as outlet')->where('outlet.tap','DUMAI'),Carbon::parse('2026-10-26'),$o,$facet);}
 public function test_numeric_sort_filters_before_pagination_and_enforces_scope():void{
  $s=app(OutletColumnFilters::class);$o=['sort'=>'cvm_mtd','direction'=>'desc'];$q=$s->order($this->query($o),$o);$this->assertSame('B',$q->paginate(1)->first()->id_outlet);
  $rows=$this->query(['columns'=>['cvm_gap'=>['-2']]])->get();$this->assertSame(['A'],$rows->pluck('id_outlet')->all());
  $a=$this->query(['sort'=>'st_pv_mtd'])->where('id_outlet','A')->first();$this->assertSame(15,(int)$a->st_pv_mtd);$this->assertSame(20,(int)$a->st_pv_m1);$this->assertSame(30,(int)$a->st_pv_fm1);$this->assertSame(-25.0,(float)$a->st_pv_mom_pct);
  $this->assertSame(['B'],$this->query(['exclude'=>['cvm_mtd'=>['2']]])->get()->pluck('id_outlet')->all());
 }
 public function test_text_blank_and_combined_filters():void{
  $this->assertSame(2,$this->query(['columns'=>['kecamatan'=>['']]])->count());
  $this->assertSame(['A'],$this->query(['columns'=>['id_outlet'=>['A'],'cvm_mtd'=>['2']]])->get()->pluck('id_outlet')->all());
  $this->assertSame(0,$this->query(['columns'=>['id_outlet'=>['C']]])->count());
 }
 public function test_invalid_headers_and_direction_are_rejected():void{
  $this->expectException(\Illuminate\Validation\ValidationException::class);
  app(OutletColumnFilters::class)->options(Request::create('/','GET',['sort'=>'id_outlet; DROP TABLE appsdumais']));
 }
}
