<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\ValidityMom;
class ValidityMomTest extends TestCase {
 protected function setUp():void{parent::setUp();config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
 Schema::create('keluar',fn(Blueprint$t)=>[$t->integer('status'),$t->string('penerima')]);
 Schema::create('idsf',fn(Blueprint$t)=>$t->string('idtap'));
 Schema::create('denom',function(Blueprint$t){foreach(['iddenom','product','product_variant','group_name']as$c)$t->string($c);});
 foreach(['keluarsf','mobile_penjualan','injectvf']as$table)Schema::create($table,function(Blueprint$t){$t->string('idtap');$t->string('iddenom');$t->integer('qty');$t->dateTime('tgl');$t->string('status')->nullable();});
 foreach(['DUMAI','DURI']as$tap)DB::table('idsf')->insert(['idtap'=>$tap]);
 foreach([['V1','PV','REGULER','1 HARI'],['V2','PV','BYU','2 HARI'],['SP1','SA','SIMPATI','SA'],['SP5','SA','BYU','SA']]as[$id,$p,$v,$g]){DB::table('denom')->insert(['iddenom'=>$id,'product'=>$p,'product_variant'=>$v,'group_name'=>$g]);
 foreach(['keluarsf','injectvf']as$table)DB::table($table)->insert([['idtap'=>'DUMAI','iddenom'=>$id,'tgl'=>'2026-09-24 12:00:00','qty'=>10],['idtap'=>'DUMAI','iddenom'=>$id,'tgl'=>'2026-09-28 12:00:00','qty'=>999],['idtap'=>'DUMAI','iddenom'=>$id,'tgl'=>'2026-10-24 12:00:00','qty'=>20],['idtap'=>'DURI','iddenom'=>$id,'tgl'=>'2026-10-24 12:00:00','qty'=>999]]);
 foreach([['approved',5],['pending',999]]as[$status,$qty])DB::table('mobile_penjualan')->insert(['idtap'=>'DUMAI','iddenom'=>$id,'tgl'=>'2026-10-24 12:00:00','qty'=>$qty,'status'=>$status]);}
 }
 private function report(string$product='pv_all',string$tab='sales'):array{return app(ValidityMom::class)->data(Request::create('/','GET',['validity_product'=>$product,'validity_tab'=>$tab]),Carbon::parse('2026-10-26 23:59:59'),Carbon::parse('2026-09-26 23:59:59'),fn($q,$col)=>$q->where($col,'DUMAI'));}
 public function test_all_product_tabs_have_matching_cutoffs_approved_sales_and_tap_scope():void{
 foreach(['pv_all'=>2,'pv_reg'=>1,'pv_byu'=>1,'sa_all'=>2,'sa_simpati'=>1,'sa_byu'=>1]as$product=>$count)foreach(['sales','inject']as$tab){$d=$this->report($product,$tab);$this->assertSame(['DUMAI'],array_column($d['rows'],'tap'));$this->assertSame(($tab==='sales'?25:20)*$count,array_sum(array_column($d['total'],'current')));$this->assertSame(10*$count,array_sum(array_column($d['total'],'previous')));}
 $this->assertSame(['1D','2D'],$this->report()['columns']);
 }
 public function test_partial_renders_group_headers_controls_and_growth():void{
 $html=view('surya.partials.validity-mom',['validityMom'=>$this->report()])->render();
 foreach(['M-1','MTD','MoM','1D','2D','GRAND TOTAL','+150,0%','data-validity-product','data-validity-tab="inject"']as$text)$this->assertStringContainsString($text,$html);
 }
 public function test_invalid_product_is_rejected():void{$this->expectException(\Illuminate\Validation\ValidationException::class);$this->report('invalid');}
}
