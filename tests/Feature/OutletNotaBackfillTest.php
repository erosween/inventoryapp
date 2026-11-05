<?php
namespace Tests\Feature;

use App\Http\Controllers\MonitaDumaiController;
use App\Services\OutletColumnFilters;
use App\Services\OutletNotaImport;
use App\Services\OutletNotaSales;
use App\Services\OutletSalesInsight;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OutletNotaBackfillTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']); DB::purge('sqlite');
        Schema::create('appsdumais',function(Blueprint $t){foreach(OutletColumnFilters::TEXT as $c)$t->string($c)->nullable();});
        DB::table('appsdumais')->insert(['id_outlet'=>'1201034972','nama_outlet'=>'Bastian','tap'=>'BAGAN BATU','kecamatan'=>'Bagan Sinembah','pjp'=>'Sabtu']);
        Schema::create('denom',function(Blueprint $t){foreach(['iddenom','product_group','product','product_variant','group_name']as$c)$t->string($c);});
        DB::table('denom')->insert([
            ['iddenom'=>'V1','product_group'=>'PV','product'=>'PV','product_variant'=>'REGULER','group_name'=>'1 HARI'],
            ['iddenom'=>'SP1','product_group'=>'SA','product'=>'SA','product_variant'=>'SIMPATI','group_name'=>'SA'],
        ]);
        foreach(['keluarsf','mobile_penjualan']as$table)Schema::create($table,function(Blueprint $t){$t->id();foreach(['id_outlet','iddenom','tgl','status','tambahanket']as$c)$t->string($c)->nullable();$t->integer('qty');});
        $migration=require database_path('migrations/2026_11_05_000001_create_outlet_nota_backfill.php');$migration->up();
        $history=require database_path('migrations/2026_10_22_000003_create_surya_outlet_sales_history.php');$history->up();
        foreach(['surya_trx_cvm'=>['ss','cvm','hot_promo','comsak'],'surya_biometrik'=>['bio']]as$table=>$metrics)Schema::create($table,function(Blueprint $t)use($metrics){$t->string('id_outlet');$t->string('uploaded_metrics')->nullable();foreach($metrics as$m)foreach(['fm1','m1','m']as$p)$t->integer($m.'_'.$p)->default(0);});
        Schema::create('surya_daily_metrics',function(Blueprint $t){$t->id();$t->string('id_outlet');$t->date('metric_date');});
        foreach([['2026-10-10','V1',999],['2026-10-16','V1',30],['2026-10-31','V1',40],['2026-10-16','SP1',4],['2026-11-01','V1',7]]as[$date,$denom,$qty])DB::table('keluarsf')->insert(['id_outlet'=>'1201034972','iddenom'=>$denom,'tgl'=>$date,'qty'=>$qty]);
        DB::table('mobile_penjualan')->insert(['id'=>10,'id_outlet'=>'1201034972','iddenom'=>'V1','tgl'=>'2026-10-16','qty'=>5,'status'=>'approved']);
        DB::table('keluarsf')->insert(['id_outlet'=>'1201034972','iddenom'=>'V1','tgl'=>'2026-10-16','qty'=>5,'tambahanket'=>'Mobile Approval ID: 10']);
    }
    private function upload(string $body): int
    {
        return app(OutletNotaImport::class)->import(UploadedFile::fake()->createWithContent('nota.csv',"tanggal,id_outlet,st_sa,st_pv\n".$body));
    }
    private function seedUpload(): void { $this->upload("2026-10-01,1201034972,2,100\n2026-10-15,1201034972,3,200\n"); }
    private function hydrate(string $cutoff)
    {
        $method=new \ReflectionMethod(MonitaDumaiController::class,'hydrateOutletDetailPage');$method->setAccessible(true);
        return $method->invoke(app(MonitaDumaiController::class),DB::table('appsdumais')->get(),Carbon::parse($cutoff))->first();
    }
    public function test_october_and_november_periods_and_detail_export_reconcile(): void
    {
        $this->seedUpload();
        $oct=$this->hydrate('2026-10-31');$this->assertSame(375,$oct->st_pv_mtd);$this->assertSame(9,$oct->st_sa_mtd);
        $nov=$this->hydrate('2026-11-10');$this->assertSame(375,$nov->st_pv_fm1);$this->assertSame(100,$nov->st_pv_m1);$this->assertSame(7,$nov->st_pv_mtd);
        $this->assertSame(9,$nov->st_sa_fm1);$this->assertSame(2,$nov->st_sa_m1);
        $this->assertSame(300,$this->hydrate('2026-10-15')->st_pv_mtd);
        $this->assertSame(100,$this->hydrate('2026-10-01')->st_pv_mtd);
        $export=new \App\Exports\SuryaOutletDetailExport(collect([$nov]));
        $mapped=array_combine($export->headings(),$export->map($nov));$this->assertSame(375,$mapped['ST PV FM1']);$this->assertSame(100,$mapped['ST PV M1']);
        $this->assertSame(999,(int)DB::table('keluarsf')->where('tgl','2026-10-10')->value('qty'));
    }
    public function test_filters_sort_and_summary_use_the_same_special_period(): void
    {
        $this->seedUpload();$s=app(OutletColumnFilters::class);
        $q=$s->query(DB::table('appsdumais as outlet'),Carbon::parse('2026-11-10'),['columns'=>['st_pv_fm1'=>['375']],'sort'=>'st_pv_m1']);
        $this->assertSame(100,(int)$s->order($q,['sort'=>'st_pv_m1'])->first()->st_pv_m1);
        $outlets=DB::table('appsdumais')->get();$insight=app(OutletSalesInsight::class);
        $oct=$insight->data($outlets,Carbon::parse('2026-10-31'),'pv_all');
        $this->assertSame(375,$oct['grand']['denominator']['current']);$this->assertSame(300,$oct['grand']['values']['HISTORI TANPA RINCIAN']['current']);
        $nov=$insight->data($outlets,Carbon::parse('2026-11-10'),'pv_all');$this->assertSame(100,$nov['grand']['denominator']['previous']);
        $variant=$insight->data($outlets,Carbon::parse('2026-10-31'),'pv_reg');$this->assertArrayNotHasKey('HISTORI TANPA RINCIAN',$variant['grand']['values']);
        $sa=$insight->data($outlets,Carbon::parse('2026-10-31'),'sa_all');$this->assertSame(9,$sa['grand']['denominator']['current']);
    }
    public function test_reupload_replaces_and_invalid_files_preserve_previous_snapshot(): void
    {
        $this->seedUpload();$this->upload("2026-10-02,1201034972,0,50\n");$this->assertSame(1,DB::table('surya_outlet_nota_backfill')->count());
        foreach([
            '',"2026-10-16,1201034972,0,9\n","2026-10-01,99999999,0,9\n",
            "2026-10-01,1201034972,-1,9\n","2026-10-01,1201034972,0,9\n2026-10-01,1201034972,0,9\n",
            "2026-10-01,1201034972,0,9\n2026-11-01,1201034972,0,9\n",
        ]as$body){try{$this->upload($body);$this->fail('Invalid snapshot accepted');}catch(ValidationException $e){$this->assertArrayHasKey('nota_file',$e->errors());}
            $this->assertSame(50,(int)DB::table('surya_outlet_nota_backfill')->sum('pv'));
        }
    }

    public function test_upload_and_template_are_admin_only_and_use_real_route(): void
    {
        $u=new \App\Models\User();$u->username='asmen_dumai';$this->actingAs($u)->withSession(['idtap'=>'DUMAI']);
        $this->getJson(route('surya.outletlist.nota-template'))->assertForbidden();
        $this->postJson(route('surya.outletlist.nota'))->assertForbidden();
        $u->username='admin_super';$this->actingAs($u);
        $template=$this->get(route('surya.outletlist.nota-template'))->assertOk()->streamedContent();
        $this->assertStringContainsString('tanggal,id_outlet,st_sa,st_pv',$template);
        $file=UploadedFile::fake()->createWithContent('nota.csv',"tanggal,id_outlet,st_sa,st_pv\n2026-10-01,1201034972,0,15\n");
        $this->post(route('surya.outletlist.nota'),['nota_file'=>$file])->assertRedirect(route('surya.transaction-upload'))->assertSessionHasNoErrors();
        $this->assertSame(15,$this->hydrate('2026-10-01')->st_pv_mtd);
    }
}
