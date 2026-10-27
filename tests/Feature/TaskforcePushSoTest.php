<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaskforcePushSoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        DB::connection()->getPdo()->sqliteCreateFunction('FIND_IN_SET',fn($needle,$list)=>in_array($needle,explode(',',(string)$list),true)?1:0);
        Carbon::setTestNow(Carbon::parse('2026-10-27 10:00:00'));
        Schema::create('keluar',function(Blueprint $t) { $t->integer('status');$t->string('penerima'); });
        Schema::create('appsdumais',function(Blueprint $t) {
            foreach(['id_outlet','nama_outlet','kecamatan','sf','tap','pjp'] as $c) $t->string($c);
        });
        Schema::create('denom',function(Blueprint $t) { $t->string('iddenom');$t->string('product_group'); });
        foreach(['keluarsf','mobile_penjualan'] as $table) Schema::create($table,function(Blueprint $t) {
            $t->id(); $t->string('id_outlet');$t->string('iddenom');$t->date('tgl');$t->integer('qty');$t->string('status')->nullable();
        });
        Schema::create('surya_outlet_sales_history',function(Blueprint $t) {
            $t->id();$t->string('id_outlet');$t->date('transaction_date');$t->integer('sa');$t->integer('pv');
        });
        Schema::create('surya_daily_metrics',function(Blueprint $t) {
            $t->id();$t->string('id_outlet');$t->date('metric_date');$t->string('uploaded_metrics')->nullable();
            foreach(['bio','cvm','ss','comsak','hot_promo'] as $metric) foreach(['fm1','m1','m'] as $period) $t->integer($metric.'_'.$period)->default(0);
        });
        (require database_path('migrations/2026_10_27_200000_create_taskforce_push_so_actions.php'))->up();
        foreach([['A','DUMAI'],['B','DUMAI'],['C','DURI']] as [$id,$tap]) DB::table('appsdumais')->insert([
            'id_outlet'=>$id,'nama_outlet'=>'Outlet '.$id,'kecamatan'=>'Kota','sf'=>'SF '.$tap,'tap'=>$tap,'pjp'=>'Senin',
        ]);
        DB::table('denom')->insert([['iddenom'=>'SP1','product_group'=>'SP'],['iddenom'=>'V1','product_group'=>'PV']]);
        DB::table('keluarsf')->insert([
            ['id_outlet'=>'A','iddenom'=>'SP1','tgl'=>'2026-10-20','qty'=>2],
            ['id_outlet'=>'A','iddenom'=>'V1','tgl'=>'2026-10-20','qty'=>10],
            ['id_outlet'=>'A','iddenom'=>'V1','tgl'=>'2026-10-27','qty'=>999],
        ]);
        DB::table('mobile_penjualan')->insert([
            ['id_outlet'=>'A','iddenom'=>'SP1','tgl'=>'2026-10-22','qty'=>1,'status'=>'approved'],
            ['id_outlet'=>'B','iddenom'=>'SP1','tgl'=>'2026-10-22','qty'=>99,'status'=>'pending'],
        ]);
        DB::table('surya_outlet_sales_history')->insert([
            ['id_outlet'=>'A','transaction_date'=>'2026-09-05','sa'=>4,'pv'=>8],
            ['id_outlet'=>'A','transaction_date'=>'2026-09-29','sa'=>16,'pv'=>22],
        ]);
        DB::table('surya_daily_metrics')->insert([
            ['id_outlet'=>'A','metric_date'=>'2026-10-25','uploaded_metrics'=>'bio','bio_fm1'=>8,'bio_m1'=>4,'bio_m'=>2],
            ['id_outlet'=>'A','metric_date'=>'2026-10-26','uploaded_metrics'=>'cvm','bio_fm1'=>999,'bio_m1'=>999,'bio_m'=>999],
        ]);
        $this->asUser('asmen_dumai','DUMAI');
    }

    protected function tearDown(): void { Carbon::setTestNow();parent::tearDown(); }

    private function asUser(string $username,string $tap): void
    {
        $user=new User(['id'=>1,'username'=>$username,'idtap'=>$tap,'level'=>'TAP']);
        $this->actingAs($user)->withSession(['idtap'=>$tap]);
    }

    private function url(array $query=[]): string
    {
        return route('surya.taskforce.push-so', ['sales_cutoff'=>'2026-10-26']+$query);
    }

    public function test_page_reuses_detail_metrics_and_respects_tap_scope(): void
    {
        $response=$this->get($this->url())->assertOk()->assertSee('PUSH SO')->assertSee('TASKFORCE');
        $rows=$response->viewData('outlets');
        self::assertSame(2,$rows->total());
        self::assertSame(['A','B'],$rows->pluck('id_outlet')->all());
        $a=$rows->first();
        self::assertSame(3,$a->st_sa_mtd);
        self::assertSame(10,$a->st_pv_mtd);
        self::assertSame(20,$a->st_sa_fm1);
        self::assertSame(4,$a->st_sa_m1);
        self::assertSame(2,$a->biometrik_mtd);
        self::assertSame(['SF DUMAI'],$response->viewData('sfs')->all());
    }

    public function test_multiple_numeric_filters_apply_before_pagination_and_match_visible_numbers(): void
    {
        $r=$this->get($this->url(['sa_min'=>3,'sa_max'=>3,'pv_min'=>10,'pv_max'=>10,'bio_min'=>2,'bio_max'=>2]))->assertOk();
        self::assertSame(['A'],$r->viewData('outlets')->pluck('id_outlet')->all());
        $r=$this->get($this->url(['sa_max'=>0,'bio_max'=>0]))->assertOk();
        self::assertSame(['B'],$r->viewData('outlets')->pluck('id_outlet')->all());
        $r=$this->get($this->url(['period'=>'fm1','sa_min'=>20,'sa_max'=>20]))->assertOk();
        self::assertSame(['A'],$r->viewData('outlets')->pluck('id_outlet')->all());
        $r=$this->get($this->url(['period'=>'m1','sa_min'=>4,'sa_max'=>4,'bio_min'=>4]))->assertOk();
        self::assertSame(['A'],$r->viewData('outlets')->pluck('id_outlet')->all());
    }

    public function test_asmen_can_save_visit_with_audit_trail_and_month_isolation(): void
    {
        $url=route('surya.taskforce.push-so.store',['idOutlet'=>'A']);
        $this->postJson($url,['sales_cutoff'=>'2026-10-26','visited'=>1,'visited_at'=>'2026-10-26','notes'=>'Sudah cek stok'])->assertOk();
        $this->postJson($url,['sales_cutoff'=>'2026-10-26','visited'=>0,'notes'=>'Jadwalkan ulang'])->assertOk();
        self::assertSame(2,DB::table('taskforce_push_so_actions')->count());
        self::assertSame('asmen_dumai',DB::table('taskforce_push_so_actions')->value('username'));
        $r=$this->get($this->url(['visit'=>'pending']))->assertOk();
        self::assertSame(2,$r->viewData('outlets')->total());
        self::assertSame('Jadwalkan ulang',$r->viewData('outlets')->first()->notes);
        self::assertNull($r->viewData('outlets')->first()->visited_at);
        $r=$this->get(route('surya.taskforce.push-so',['sales_cutoff'=>'2026-09-26']))->assertOk();
        self::assertNull($r->viewData('outlets')->first()->notes);
    }

    public function test_readers_cannot_write_and_asmen_cannot_access_other_tap(): void
    {
        $payload=['sales_cutoff'=>'2026-10-26','visited'=>0,'notes'=>'test'];
        $this->postJson(route('surya.taskforce.push-so.store',['idOutlet'=>'C']),$payload)->assertNotFound();
        $this->asUser('admin_super','SBP_DUMAI');
        $r=$this->get($this->url())->assertOk();
        self::assertSame(3,$r->viewData('outlets')->total());
        self::assertFalse($r->viewData('canAct'));
        $this->postJson(route('surya.taskforce.push-so.store',['idOutlet'=>'A']),$payload)->assertForbidden();
        self::assertSame(0,DB::table('taskforce_push_so_actions')->count());
    }

    public function test_validation_rejects_inverted_ranges_and_visit_without_date(): void
    {
        $this->getJson($this->url(['sa_min'=>5,'sa_max'=>1]))->assertUnprocessable()->assertJsonValidationErrors('sa_max');
        $this->postJson(route('surya.taskforce.push-so.store',['idOutlet'=>'A']),['sales_cutoff'=>'2026-10-26','visited'=>1])->assertUnprocessable()->assertJsonValidationErrors('visited_at');
        self::assertSame(0,DB::table('taskforce_push_so_actions')->count());
    }
}
