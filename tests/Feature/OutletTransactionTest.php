<?php
namespace Tests\Feature;

use App\Http\Controllers\MonitaDumaiController;
use App\Models\User;
use App\Services\OutletNotaSales;
use App\Services\OutletTransactionColumns;
use App\Services\OutletTransactionReport;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OutletTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','session.driver'=>'array']);DB::purge('sqlite');
        Carbon::setTestNow(Carbon::parse('2026-11-06','Asia/Jakarta'));
        Schema::create('keluar',function(Blueprint $t){$t->string('penerima')->nullable();$t->integer('status');});
        Schema::create('appsdumais',function(Blueprint $t){foreach(['id_outlet','nama_outlet','nomor_rs','tap','cluster','kecamatan','sf','pjp','kategori_outlet']as$c)$t->string($c)->nullable();});
        foreach([['A','DUMAI','Senin'],['B','DURI','Selasa'],['C','BAGAN BATU','Rabu'],['X','DUMAI','UNMAPPING']]as[$id,$tap,$pjp])DB::table('appsdumais')->insert(['id_outlet'=>$id,'nama_outlet'=>'Outlet '.$id,'tap'=>$tap,'pjp'=>$pjp,'sf'=>'SF '.$id,'cluster'=>$tap==='BAGAN BATU'?'ROKAN HILIR':'DUMAI BENGKALIS']);
        Schema::create('denom',fn(Blueprint $t)=>[$t->string('iddenom'),$t->string('product_group')]);
        DB::table('denom')->insert([['iddenom'=>'V1','product_group'=>'PV'],['iddenom'=>'SP1','product_group'=>'SA']]);
        foreach(['keluarsf','mobile_penjualan']as$table)Schema::create($table,function(Blueprint $t){$t->id();foreach(['id_outlet','iddenom','tgl','status','tambahanket','idtap']as$c)$t->string($c)->nullable();$t->integer('qty');});
        (require database_path('migrations/2026_11_05_000001_create_outlet_nota_backfill.php'))->up();
        (require database_path('migrations/2026_10_22_000003_create_surya_outlet_sales_history.php'))->up();
        DB::table('surya_outlet_nota_backfill')->insert([
            ['transaction_date'=>'2026-01-01','id_outlet'=>'A','sa'=>3,'pv'=>20],
            ['transaction_date'=>'2026-10-01','id_outlet'=>'A','sa'=>10,'pv'=>100],
            ['transaction_date'=>'2026-10-10','id_outlet'=>'A','sa'=>5,'pv'=>50],
        ]);
        foreach([['A','V1','2026-10-02',999],['A','V1','2026-10-16',30],['A','SP1','2026-11-02',4],['A','V1','2026-11-02',20],['B','V1','2026-11-03',100],['C','V1','2026-11-03',12]]as[$id,$denom,$date,$qty])DB::table('keluarsf')->insert(['id_outlet'=>$id,'iddenom'=>$denom,'tgl'=>$date,'qty'=>$qty]);
        DB::table('mobile_penjualan')->insert([
            ['id'=>10,'id_outlet'=>'A','iddenom'=>'V1','tgl'=>'2026-11-02','qty'=>7,'status'=>'approved'],
            ['id'=>11,'id_outlet'=>'A','iddenom'=>'V1','tgl'=>'2026-11-02','qty'=>8,'status'=>'approved'],
            ['id'=>12,'id_outlet'=>'A','iddenom'=>'V1','tgl'=>'2026-11-02','qty'=>500,'status'=>'pending'],
        ]);
        DB::table('keluarsf')->insert(['id_outlet'=>'A','iddenom'=>'V1','tgl'=>'2026-11-02','qty'=>7,'tambahanket'=>'Mobile Approval ID: 10']);
        foreach(['keluarsf','mobile_penjualan']as$table)DB::table($table)->update(['idtap'=>'DUMAI']);
    }
    protected function tearDown():void {Carbon::setTestNow();parent::tearDown();}
    private function report(string $family='pv',string $date='2026-11-04'):array{return app(OutletTransactionReport::class)->data($family,Carbon::parse($date));}

    public function test_monthly_totals_reconcile_with_detail_outlet_source_and_mobile_is_not_counted_twice():void
    {
        $data=$this->report();$a=$data['rows']->keyBy('id_outlet')->get('A');
        $this->assertSame(20,$a['m1']);$this->assertSame(180,$a['m10']);$this->assertSame(35,$a['m11']);
        $this->assertSame(100,$a['compare']);$this->assertSame(-65,$a['gap_m11']);$this->assertSame(-65.0,$a['mom_pct_m11']);
        $this->assertNull($a['m12']);$this->assertNull($a['gap_m12']);
        $this->assertSame(147,$data['grand']['m11']);$this->assertSame(135,$data['clusters'][0]['m11']);
        $this->assertCount(3,$data['rows']);
        foreach(['pv','sa']as$family){$rows=$this->report($family)['rows']->keyBy('id_outlet');
            $nota=app(OutletNotaSales::class)->totals(Carbon::parse('2026-11-01'),Carbon::parse('2026-11-04'))->get()->keyBy('id_outlet');
            foreach($rows as$id=>$row)$this->assertSame((int)($nota[$id]->{$family}??0),$row['m11']);}
        $this->assertSame(4,$this->report('sa')['grand']['m11']);
    }
    public function test_closed_month_uses_full_previous_month():void
    {
        $data=$this->report('pv','2026-10-31');$this->assertFalse($data['comparison']);
        $this->assertSame(180,$data['grand']['m10']);$this->assertSame(180,$data['grand']['gap_m10']);$this->assertNull($data['grand']['m11']);
    }
    public function test_user_scope_applies_to_master_summary_filters_and_downloads():void
    {
        $user=new User;$user->username='asmen_dumai';$this->actingAs($user);
        $data=$this->report();$this->assertSame(['A'],$data['rows']->pluck('id_outlet')->all());$this->assertSame(35,$data['grand']['m11']);
        $this->get('/surya/transaksi-outlet/pv?cutoff=2026-11-04&tap=DURI')->assertOk()->assertViewHas('rows',fn($r)=>$r->isEmpty());
        $csv=$this->get('/surya/transaksi-outlet/pv/export?cutoff=2026-11-04')->assertOk();$this->assertStringContainsString('Outlet A',$csv->streamedContent());$this->assertStringNotContainsString('Outlet B',$csv->streamedContent());
    }
    public function test_header_sort_filters_facets_and_csv_retain_numeric_negative_values():void
    {
        $user=new User;$user->username='admin_super';$this->actingAs($user);
        $this->get('/surya/transaksi-outlet/pv?cutoff=2026-11-04&sort=m11&direction=desc')->assertOk()->assertViewHas('outlets',fn($p)=>$p->first()['id_outlet']==='B');
        $this->get('/surya/transaksi-outlet/sa?cutoff=2026-11-04&columns[tap][]=DUMAI')->assertOk()->assertViewHas('outlets',fn($p)=>$p->total()===1);
        $this->get('/surya/transaksi-outlet/pv/filter-values?cutoff=2026-11-04&column=tap&columns[tap][]=DUMAI')->assertOk()->assertJsonCount(3,'values');
        $csv=$this->get('/surya/transaksi-outlet/pv/export?cutoff=2026-11-04&columns[id_outlet][]=A');$csv->assertOk();$content=$csv->streamedContent();
        $this->assertStringContainsString(',-65,-65,',$content);$this->assertStringNotContainsString("'-65",$content);
        $this->get('/surya/transaksi-outlet/pv?sort=bad')->assertRedirect();
    }
    public function test_navigation_and_cvm_placeholder_and_invalid_dates():void
    {
        $user=new User;$user->username='admin_super';$this->actingAs($user);
        $this->get('/surya/transaksi-outlet/pv?cutoff=2026-11-04')->assertOk()->assertSee('TRANSAKSI OUTLET')->assertSee('Rekap per TAP')->assertSee('Dumai Bengkalis')->assertSee('Rokan Hilir')->assertSee('ngrs-frozen-name',false);
        $this->get('/surya/transaksi-outlet/cvm')->assertOk()->assertSee('Laporan CVM belum tersedia.');
        $this->get('/surya/transaksi-outlet/pv?cutoff=2027-01-01')->assertRedirect();
        $this->get('/surya/transaksi-outlet/other')->assertNotFound();
    }
    public function test_second_family_reuses_monthly_source_cache():void
    {
        $this->report('pv');DB::enableQueryLog();$this->report('sa');
        $queries=collect(DB::getQueryLog())->pluck('query')->implode(' ');DB::disableQueryLog();
        $this->assertStringNotContainsString('mobile_penjualan',$queries);$this->assertStringNotContainsString('surya_outlet_nota_backfill',$queries);
    }
    public function test_sa_pv_outlet_category_uses_master_even_when_sales_are_cached():void
    {
        $user=new User;$user->username='admin_super';$this->actingAs($user);
        $this->report();
        DB::table('appsdumais')->where('id_outlet','A')->update(['kategori_outlet'=>'Top 20 TAP']);
        foreach(['sa','pv']as$family){
            $data=$this->report($family);$this->assertSame('Top 20 TAP',$data['rows']->keyBy('id_outlet')['A']['kategori_outlet']);
            $this->assertSame('Reguler',$data['rows']->keyBy('id_outlet')['B']['kategori_outlet']);
            $this->get('/surya/transaksi-outlet/'.$family.'?cutoff=2026-11-04&columns[kategori_outlet][]=Top%2020%20TAP&sort=kategori_outlet')
                ->assertOk()->assertViewHas('outlets',fn($p)=>$p->total()===1)->assertSee('Kategori Outlet');
            $response=$this->get('/surya/transaksi-outlet/'.$family.'/export?cutoff=2026-11-04&columns[kategori_outlet][]=Top%2020%20TAP')->assertOk();
            $this->assertStringContainsString('Kategori Outlet',$response->streamedContent());
            $this->assertStringContainsString('Top 20 TAP',$response->streamedContent());
        }
        $this->get('/surya/transaksi-outlet/cvm')->assertOk()->assertSee('Kategori Outlet');
    }

    public function test_default_cutoff_follows_approved_sales_and_is_shared_with_detail_outlet():void
    {
        $user=new User;$user->username='admin_super';$this->actingAs($user);
        foreach(['DUMAI','DURI']as$tap)DB::table('keluarsf')->insert(['id_outlet'=>'A','iddenom'=>'V1','tgl'=>'2026-11-05','qty'=>10,'idtap'=>$tap]);
        $this->get('/surya/transaksi-outlet/pv')->assertOk()->assertViewHas('filters',fn($f)=>$f['cutoff']==='2026-11-05');
        $this->get('/surya/transaksi-outlet/sa')->assertOk()->assertViewHas('filters',fn($f)=>$f['cutoff']==='2026-11-05');
        $method=new \ReflectionMethod(MonitaDumaiController::class,'outletSalesCutoff');$method->setAccessible(true);
        $this->assertSame('2026-11-05',$method->invoke(app(MonitaDumaiController::class),\Illuminate\Http\Request::create('/'))->toDateString());
        DB::table('mobile_penjualan')->insert(['id_outlet'=>'A','iddenom'=>'V1','tgl'=>'2026-11-06','qty'=>99,'idtap'=>'DUMAI','status'=>'pending']);
        $this->assertSame('2026-11-05',\App\Support\OutletSalesCutoff::latest()->toDateString());
        DB::table('keluarsf')->where('idtap','DURI')->where('tgl','2026-11-05')->update(['tgl'=>'2026-11-04']);
        $this->assertSame('2026-11-04',\App\Support\OutletSalesCutoff::latest()->toDateString());
        $user->username='asmen_dumai';$this->actingAs($user);
        $this->assertSame('2026-11-04',\App\Support\OutletSalesCutoff::latest()->toDateString());
        $this->get('/surya/transaksi-outlet/pv?cutoff=2026-10-31')->assertOk()->assertViewHas('filters',fn($f)=>$f['cutoff']==='2026-10-31');
    }

}
