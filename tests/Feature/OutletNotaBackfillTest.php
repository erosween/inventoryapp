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
        foreach(['surya_trx_cvm'=>['ss','cvm','hot_promo','comsak','insak','digital'],'surya_biometrik'=>['bio']]as$table=>$metrics)Schema::create($table,function(Blueprint $t)use($metrics){$t->id();$t->string('id_outlet');$t->date('metric_date');$t->date('source_updated_at')->nullable();$t->timestamps();$t->string('uploaded_metrics')->nullable();foreach($metrics as$m)foreach(['fm1','m1','m']as$p)$t->integer($m.'_'.$p)->default(0);});
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

    public function test_january_to_september_uploads_fill_all_periods_and_preserve_other_months(): void
    {
        $this->seedUpload();
        $this->upload("2026-01-01,1201034972,1,10\n2026-01-31,1201034972,2,20\n2026-02-01,1201034972,3,30\n2026-02-10,1201034972,4,40\n2026-09-01,1201034972,5,50\n2026-09-30,1201034972,6,60\n");
        $this->assertSame(30,$this->hydrate('2026-01-31')->st_pv_mtd);
        $feb=$this->hydrate('2026-02-10');
        $this->assertSame(70,$feb->st_pv_mtd);$this->assertSame(30,$feb->st_pv_fm1);$this->assertSame(10,$feb->st_pv_m1);
        $this->assertSame(7,$feb->st_sa_mtd);$this->assertSame(3,$feb->st_sa_fm1);$this->assertSame(1,$feb->st_sa_m1);
        $oct=$this->hydrate('2026-10-31');$this->assertSame(110,$oct->st_pv_fm1);$this->assertSame(110,$oct->st_pv_m1);$this->assertSame(375,$oct->st_pv_mtd);
        $this->assertSame(50,$this->hydrate('2026-10-01')->st_pv_m1);
        $s=app(OutletColumnFilters::class);
        $row=$s->query(DB::table('appsdumais as outlet'),Carbon::parse('2026-02-10'),['columns'=>['st_pv_fm1'=>['30']]])->first();
        $this->assertSame(70,(int)$row->st_pv_mtd);
        $d=app(OutletSalesInsight::class)->data(DB::table('appsdumais')->get(),Carbon::parse('2026-02-10'),'pv_all');
        $this->assertSame(70,$d['grand']['denominator']['current']);$this->assertSame(10,$d['grand']['denominator']['previous']);
        $this->upload("2026-01-02,1201034972,0,9\n");
        $this->assertSame(9,$this->hydrate('2026-01-31')->st_pv_mtd);
        $this->assertSame(70,$this->hydrate('2026-02-28')->st_pv_mtd);
        $this->assertSame(110,$this->hydrate('2026-09-30')->st_pv_mtd);
        $this->assertSame(375,$this->hydrate('2026-10-31')->st_pv_mtd);
        $this->assertSame(375,$this->hydrate('2026-11-10')->st_pv_fm1);
    }

    public function test_new_month_snapshot_replaces_old_history_without_overlay_or_invalid_dates(): void
    {
        DB::table('surya_outlet_sales_history')->insert([
            ['id_outlet'=>'1201034972','transaction_date'=>'2026-09-01','sa'=>1,'pv'=>12],
            ['id_outlet'=>'1201034972','transaction_date'=>'2026-09-30','sa'=>1,'pv'=>18],
        ]);
        $this->assertSame(30,$this->hydrate('2026-09-30')->st_pv_mtd);
        $this->assertSame(30,$this->hydrate('2026-10-10')->st_pv_fm1);
        $this->upload("2026-09-01,1201034972,0,5\n");
        $this->assertSame(5,$this->hydrate('2026-09-30')->st_pv_mtd);
        $this->assertSame(5,$this->hydrate('2026-10-10')->st_pv_fm1);
        $this->assertSame(5,$this->hydrate('2026-10-10')->st_pv_m1);
        foreach(['2025-12-31','2026-02-29','2026-04-31','2026-10-16']as$date){
            try{$this->upload("$date,1201034972,0,9\n");$this->fail('Invalid date accepted');}
            catch(ValidationException $e){$this->assertArrayHasKey('nota_file',$e->errors());}
            $this->assertSame(5,(int)DB::table('surya_outlet_nota_backfill')->sum('pv'));
        }
    }

    public function test_metrics_never_leak_between_months_and_missing_is_not_zero(): void
    {
        DB::table('surya_trx_cvm')->insert(['id_outlet'=>'1201034972','metric_date'=>'2026-11-04','uploaded_metrics'=>'cvm,ss,comsak,hot_promo','cvm_fm1'=>8,'cvm_m1'=>4,'cvm_m'=>0,'ss_m'=>15,'comsak_m'=>25,'hot_promo_m'=>35]);
        DB::table('surya_biometrik')->insert(['id_outlet'=>'1201034972','metric_date'=>'2026-10-28','uploaded_metrics'=>'bio','bio_fm1'=>10,'bio_m1'=>20,'bio_m'=>50]);
        $feb=$this->hydrate('2026-02-28');
        foreach(['cvm','super_seru','comsak','hot_promo','biometrik']as$group)foreach(['fm1','m1','mtd','gap','mom_pct']as$period)$this->assertNull($feb->{$group.'_'.$period});
        $nov=$this->hydrate('2026-11-10');$this->assertSame(0,$nov->cvm_mtd);$this->assertSame(-4,$nov->cvm_gap);$this->assertSame(15,$nov->super_seru_mtd);$this->assertNull($nov->biometrik_mtd);
        $oct=$this->hydrate('2026-10-31');$this->assertSame(50,$oct->biometrik_mtd);$this->assertNull($oct->cvm_mtd);
        $export=new \App\Exports\SuryaOutletDetailExport(collect([$feb]));$cells=array_combine($export->headings(),$export->map($feb));
        $this->assertNull($cells['CVM MTD']);$this->assertNull($cells['Biometrik MTD']);
        $s=app(OutletColumnFilters::class);$base=fn()=>DB::table('appsdumais as outlet');
        $this->assertSame(0,$s->query($base(),Carbon::parse('2026-02-28'),['columns'=>['cvm_mtd'=>['0']]])->count());
        $this->assertSame(1,$s->query($base(),Carbon::parse('2026-02-28'),['columns'=>['cvm_mtd'=>['']]])->count());
        $this->assertSame(1,$s->query($base(),Carbon::parse('2026-11-10'),['columns'=>['cvm_mtd'=>['0']]])->count());
        $this->assertSame(1,$s->query($base(),Carbon::parse('2026-10-31'),['columns'=>['biometrik_mtd'=>['50']]])->count());
    }

    public function test_closed_month_end_compares_entire_previous_month_in_all_reports(): void
    {
        $this->upload("2026-08-01,1201034972,800,100\n2026-08-31,1201034972,1300,200\n2026-09-30,1201034972,700,60\n");
        $closing=$this->hydrate('2026-09-30');
        $this->assertSame(2100,$closing->st_sa_fm1);$this->assertSame(2100,$closing->st_sa_m1);
        $this->assertSame(700,$closing->st_sa_mtd);$this->assertSame(-1400,$closing->st_sa_gap);
        $this->assertEqualsWithDelta(-66.6667,$closing->st_sa_mom_pct,0.001);
        $this->assertSame(300,$closing->st_pv_fm1);$this->assertSame(300,$closing->st_pv_m1);
        $mid=$this->hydrate('2026-09-29');$this->assertSame(2100,$mid->st_sa_fm1);$this->assertSame(800,$mid->st_sa_m1);
        $s=app(OutletColumnFilters::class);
        $q=$s->query(DB::table('appsdumais as outlet'),Carbon::parse('2026-09-30'),['columns'=>['st_sa_m1'=>['2100']],'sort'=>'st_sa_m1']);
        $filtered=$s->order($q,['sort'=>'st_sa_m1'])->first();$this->assertSame(2100,(int)$filtered->st_sa_m1);$this->assertSame(-1400,(int)$filtered->st_sa_gap);
        $export=new \App\Exports\SuryaOutletDetailExport(collect([$closing]));$cells=array_combine($export->headings(),$export->map($closing));$this->assertSame($cells['ST SA FM1'],$cells['ST SA M1']);
        $d=app(OutletSalesInsight::class)->data(DB::table('appsdumais')->get(),Carbon::parse('2026-09-30'),'sa_all');
        $this->assertSame('2026-08-31',$d['prevEnd']->toDateString());$this->assertSame(2100,$d['grand']['denominator']['previous']);$this->assertSame(700,$d['grand']['denominator']['current']);
        $this->upload("2026-01-31,1201034972,25,40\n2026-02-28,1201034972,30,50\n");
        $feb=$this->hydrate('2026-02-28');$this->assertSame(25,$feb->st_sa_fm1);$this->assertSame(25,$feb->st_sa_m1);
    }
}
