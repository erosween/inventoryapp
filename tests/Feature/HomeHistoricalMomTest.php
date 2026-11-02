<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HomeHistoricalMomTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);
        DB::purge('sqlite');
        DB::connection()->getPdo()->sqliteCreateFunction('MONTH',fn($date)=>(int)substr($date,5,2));
        DB::connection()->getPdo()->sqliteCreateFunction('YEAR',fn($date)=>(int)substr($date,0,4));
        $schema=DB::getSchemaBuilder();
        $schema->create('keluar',function($t){$t->integer('status');$t->string('penerima');});
        $schema->create('idsf',function($t){$t->string('idtap');$t->string('idsf');$t->string('namasf');});
        $schema->create('denom',function($t){foreach(['iddenom','denom','product','product_variant','group_name','kategori_inject','stock_form'] as$c)$t->string($c);});
        foreach(['keluarsf','mobile_penjualan','injectvf','masuksf'] as$table)$schema->create($table,function($t){
            $t->id();foreach(['idtap','idsf','owner_idsf','iddenom','tgl','status','tambahanket'] as$c)$t->string($c)->nullable();$t->integer('qty')->default(0);
        });
        DB::table('idsf')->insert(['idtap'=>'DUMAI','idsf'=>'SF1','namasf'=>'SALES TEST']);
        DB::table('denom')->insert(['iddenom'=>'V1','denom'=>'PV TEST','product'=>'PV','product_variant'=>'REGULER','group_name'=>'1 HARI','kategori_inject'=>'REGULER','stock_form'=>'INJECT']);
        foreach(['2026-09-24'=>10,'2026-10-24'=>20,'2026-10-31'=>30,'2026-11-02'=>5] as$date=>$qty)DB::table('keluarsf')->insert(['tgl'=>$date.' 12:00:00','idtap'=>'DUMAI','idsf'=>'SF1','iddenom'=>'V1','qty'=>$qty]);
        $this->mock(\App\Http\Controllers\sisaStockController::class,function($mock){
            $mock->shouldReceive('data')->andReturnUsing(function($request){
                $this->stockDates[]=$request->input('date');
                return ['denoms'=>DB::table('denom')->get(),'rows'=>collect([['idtap'=>'DUMAI','V1'=>240]]),'sf_rows'=>collect([['idtap'=>'DUMAI','idsf'=>'SF1','V1'=>100]])];
            });
        });
    }
    private array $stockDates=[];
    private function admin(): void
    {
        $this->actingAs(new User(['id'=>1,'username'=>'admin_super']))->withSession(['idtap'=>'SBP_DUMAI']);
    }
    public function test_historical_mom_uses_selected_month_and_historical_stock_date(): void
    {
        $this->admin();
        $r=$this->withHeaders(['X-Surya-Card'=>'mom'])->get('/home?mom_date=2026-10-24&mom_product=pv_all')->assertOk();
        $this->assertSame('2026-10-24',$r->viewData('momSelectedDate')->toDateString());
        $this->assertSame('2026-09-24',$r->viewData('momEndPrevMonthPartial')->toDateString());
        $r->assertSee('MTD (24 OKT)')->assertSee('M-1 (24 SEP)')->assertSee('per 24 Okt 2026');
        $this->assertContains('2026-10-24',$this->stockDates);
        $product=$r->viewData('momProductRows')->first();
        $this->assertSame(20,(int)$product->curr_qty);
        $this->assertSame(10,(int)$product->prev_partial_qty);
        $sf=$r->viewData('momProductSfByTap')->get('DUMAI')->first();
        $this->assertEqualsWithDelta(20/24,$sf->daily_st,.001);
    }
    public function test_month_end_uses_full_previous_month(): void
    {
        $this->admin();
        $r=$this->withHeaders(['X-Surya-Card'=>'mom'])->get('/home?mom_date=2026-10-31')->assertOk();
        $this->assertSame('2026-09-30',$r->viewData('momEndPrevMonthPartial')->toDateString());
    }
    public function test_full_reload_keeps_current_kpis_but_renders_historical_mom_card(): void
    {
        $this->admin();
        $r=$this->get('/home?mom_date=2026-10-24')->assertOk();
        $this->assertSame($r->viewData('fairCutoffThisMonth')->toDateString(),$r->viewData('momSelectedDate')->toDateString());
        $this->assertSame('2026-10-24',$r->viewData('momCardData')['momSelectedDate']->toDateString());
        $r->assertSee('MTD (24 OKT)');
    }
    public function test_malformed_date_is_validation_error(): void
    {
        $this->admin();
        $this->getJson('/home?mom_date=not-a-date')->assertUnprocessable();
        $this->withHeaders(['X-Surya-Card'=>'mom'])->getJson('/home?mom_date=2099-01-01')->assertUnprocessable();
    }
}
