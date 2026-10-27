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
        (require database_path('migrations/2026_10_27_210000_create_taskforce_upload_lists.php'))->up();
        (require database_path('migrations/2026_10_27_220000_store_taskforce_uploaded_cells.php'))->up();
        foreach([['A','DUMAI'],['B','DUMAI'],['C','DURI']] as [$id,$tap]) DB::table('appsdumais')->insert([
            'id_outlet'=>$id,'nama_outlet'=>'Outlet '.$id,'kecamatan'=>'Kota','sf'=>'SF '.$tap,'tap'=>$tap,'pjp'=>'Senin',
        ]);
        foreach(['2026-09-01','2026-10-01'] as $month) {
            $id=DB::table('taskforce_uploads')->insertGetId(['period_month'=>$month,'filename'=>'test.csv','username'=>'admin_super','outlet_count'=>3,'created_at'=>now(),'updated_at'=>now()]);
            foreach(['A','B','C'] as $outlet) DB::table('taskforce_upload_outlets')->insert(['upload_id'=>$id,'id_outlet'=>$outlet]);
        }
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

    public function test_page_respects_scope_and_shows_honest_legacy_upload_fallback(): void
    {
        $response=$this->get($this->url())->assertOk()->assertSee('PUSH SO')->assertSee('TASKFORCE')
            ->assertDontSee('id="push-so-filters"',false)->assertSee('Upload lama hanya menyimpan ID outlet.')
            ->assertSee('id="push-so-dialog"',false)->assertSee('Tanggal Visit');
        $rows=$response->viewData('outlets');
        self::assertSame(2,$rows->total());
        self::assertSame(['A','B'],$rows->pluck('id_outlet')->all());
        self::assertSame(['ID Outlet'],$response->viewData('headers'));
        self::assertSame(['A'],$rows->first()->uploaded_cells);
        self::assertFalse(property_exists($rows->first(),'st_sa_mtd'));
    }

    public function test_table_preserves_uploaded_headers_cell_values_and_row_order(): void
    {
        $this->asUser('admin_super','SBP_DUMAI');
        $file=\Illuminate\Http\UploadedFile::fake()->createWithContent('custom.csv',"Nama Pilihan,ID Outlet,SA M,Narasi\nNama File B,B,999,<script>alert(1)</script>\nNama File A,A,0,\n");
        $this->post(route('surya.taskforce.upload'),['taskforce_month'=>'2026-10','taskforce_file'=>$file])->assertRedirect()->assertSessionHasNoErrors();
        $this->asUser('asmen_dumai','DUMAI');
        $queries=[];
        DB::listen(function($event) use(&$queries) { $queries[]=$event->sql; });
        $r=$this->get($this->url(['sa_max'=>0,'tap'=>'DUMAI']))->assertOk();
        self::assertSame(['Nama Pilihan','ID Outlet','SA M','Narasi'],$r->viewData('headers'));
        self::assertSame(['B','A'],$r->viewData('outlets')->pluck('id_outlet')->all());
        self::assertSame(['Nama File B','B','999','<script>alert(1)</script>'],$r->viewData('outlets')->first()->uploaded_cells);
        self::assertSame(['Nama File A','A','0',''],$r->viewData('outlets')->last()->uploaded_cells);
        $r->assertSee('Nama File B')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);
        self::assertFalse(collect($queries)->contains(fn($sql)=>str_contains($sql,'keluarsf') || str_contains($sql,'mobile_penjualan') || str_contains($sql,'surya_daily_metrics')));
    }

    public function test_asmen_can_save_visit_with_audit_trail_and_month_isolation(): void
    {
        $url=route('surya.taskforce.push-so.store',['idOutlet'=>'A']);
        $this->postJson($url,['sales_cutoff'=>'2026-10-26','visited'=>1,'visited_at'=>'2026-10-26','notes'=>'Sudah cek stok'])->assertOk();
        $this->get($this->url())->assertOk()->assertSee('Sudah visit')->assertSee('26/10/2026');
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
        $this->getJson($this->url(['sa_min'=>5,'sa_max'=>1]))->assertOk();
        $this->postJson(route('surya.taskforce.push-so.store',['idOutlet'=>'A']),['sales_cutoff'=>'2026-10-26','visited'=>1])->assertUnprocessable()->assertJsonValidationErrors('visited_at');
        self::assertSame(0,DB::table('taskforce_push_so_actions')->count());
    }

    public function test_upload_replaces_only_selected_month_and_preserves_visit_notes(): void
    {
        $this->postJson(route('surya.taskforce.push-so.store',['idOutlet'=>'A']),['sales_cutoff'=>'2026-10-26','visited'=>0,'notes'=>'Tetap disimpan'])->assertOk();
        $this->asUser('admin_super','SBP_DUMAI');
        $file=\Illuminate\Http\UploadedFile::fake()->createWithContent('taskforce.csv',"ID Outlet\nB\nB\nC\n");
        $this->post(route('surya.taskforce.upload'),['taskforce_month'=>'2026-10','taskforce_file'=>$file])->assertRedirect(route('surya.transaction-upload'))->assertSessionHas('success');
        $r=$this->get($this->url())->assertOk();
        self::assertSame(['B','C'],$r->viewData('outlets')->pluck('id_outlet')->all());
        self::assertSame(2,$r->viewData('upload')->outlet_count);
        self::assertSame(3,$this->get(route('surya.taskforce.push-so',['sales_cutoff'=>'2026-09-26']))->viewData('outlets')->total());
        self::assertSame(1,DB::table('taskforce_push_so_actions')->count());
        $this->asUser('asmen_dumai','DUMAI');
        $r=$this->get($this->url())->assertOk();
        self::assertSame(['B'],$r->viewData('outlets')->pluck('id_outlet')->all());
        $this->postJson(route('surya.taskforce.push-so.store',['idOutlet'=>'A']),['sales_cutoff'=>'2026-10-26','visited'=>0])->assertNotFound();
        $this->asUser('admin_super','SBP_DUMAI');
        $file=\Illuminate\Http\UploadedFile::fake()->createWithContent('taskforce.csv',"ID Outlet\nA\n");
        $this->post(route('surya.taskforce.upload'),['taskforce_month'=>'2026-10','taskforce_file'=>$file])->assertRedirect()->assertSessionHasNoErrors();
        self::assertSame('Tetap disimpan',$this->get($this->url())->viewData('outlets')->first()->notes);
    }

    public function test_bad_upload_is_atomic_and_only_admin_can_upload(): void
    {
        $file=\Illuminate\Http\UploadedFile::fake()->createWithContent('taskforce.csv',"ID Outlet\nA\nUNKNOWN\n");
        $this->postJson(route('surya.taskforce.upload'),['taskforce_month'=>'2026-10','taskforce_file'=>$file])->assertForbidden();
        $this->asUser('admin_super','SBP_DUMAI');
        $this->postJson(route('surya.taskforce.upload'),['taskforce_month'=>'2026-10','taskforce_file'=>$file])->assertUnprocessable()->assertJsonValidationErrors('taskforce_file');
        self::assertSame(2,DB::table('taskforce_uploads')->count());
        self::assertSame(3,$this->get($this->url())->viewData('outlets')->total());
        $file=\Illuminate\Http\UploadedFile::fake()->createWithContent('empty.csv',"ID Outlet\n");
        $this->postJson(route('surya.taskforce.upload'),['taskforce_month'=>'2026-10','taskforce_file'=>$file])->assertUnprocessable();
        self::assertSame(2,DB::table('taskforce_uploads')->count());
    }

    public function test_no_upload_shows_no_outlets_and_excel_accepts_unmapped_outlet(): void
    {
        DB::table('taskforce_uploads')->delete();
        $this->get($this->url())->assertOk()->assertSee('Belum ada daftar taskforce');
        self::assertSame(0,$this->get($this->url())->viewData('outlets')->total());
        DB::table('appsdumais')->where('id_outlet','A')->update(['pjp'=>'UNMAPPING']);
        $book=new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $book->getActiveSheet()->fromArray([
            ['ID Outlet','Rencana Visit','Rasio','Stok Upload'],
            ['A',\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime('2026-10-26')),0.125,0],
        ],null,'A1',true);
        $book->getActiveSheet()->getStyle('B2')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $book->getActiveSheet()->getStyle('C2')->getNumberFormat()->setFormatCode('0.0%');
        $path=tempnam(sys_get_temp_dir(),'taskforce-xlsx-');
        try {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
            $this->asUser('admin_super','SBP_DUMAI');
            $file=new \Illuminate\Http\UploadedFile($path,'taskforce.xlsx',null,null,true);
            $this->post(route('surya.taskforce.upload'),['taskforce_month'=>'2026-10','taskforce_file'=>$file])->assertRedirect();
            $this->get(route('surya.transaction-upload'))->assertOk()->assertSee('Upload Daftar Outlet Taskforce');
            $this->get(route('surya.taskforce.template'))->assertOk()->assertDownload('template-taskforce.csv');
            $this->asUser('asmen_dumai','DUMAI');
            $response=$this->get($this->url())->assertOk();
            self::assertSame(['A'],$response->viewData('outlets')->pluck('id_outlet')->all());
            self::assertSame(['ID Outlet','Rencana Visit','Rasio','Stok Upload'],$response->viewData('headers'));
            self::assertSame(['A','26/10/2026','12.5%','0'],$response->viewData('outlets')->first()->uploaded_cells);
            $this->postJson(route('surya.taskforce.push-so.store',['idOutlet'=>'A']),['sales_cutoff'=>'2026-10-26','visited'=>0])->assertOk();
        } finally {
            @unlink($path);
            $book->disconnectWorksheets();
        }
    }

    public function test_tap_sf_filters_and_download_follow_the_same_scope(): void
    {
        DB::table('appsdumais')->where('id_outlet','B')->update(['sf'=>'SF B']);
        $r=$this->get($this->url(['tap'=>'DUMAI','sf'=>'SF B']))->assertOk()
            ->assertSee('<h1>TASKFORCE</h1>',false)->assertSee('Download Outlet')->assertSee('tf-visit-select')
            ->assertDontSee('Pantau transaksi outlet')->assertDontSee('Kolom dan data mengikuti file upload.');
        self::assertSame(['B'],$r->viewData('outlets')->pluck('id_outlet')->all());
        self::assertSame(['DUMAI'],$r->viewData('taps')->all());
        self::assertSame(['SF B','SF DUMAI'],$r->viewData('sfs')->all());
        $response=$this->get(route('surya.taskforce.download',['sales_cutoff'=>'2026-10-26','tap'=>'DUMAI','sf'=>'SF B']))->assertOk()->assertDownload('taskforce-2026-10.csv');
        $csv=ltrim($response->streamedContent(),"\xEF\xBB\xBF");
        $rows=array_map('str_getcsv',explode("\n",trim($csv)));
        self::assertSame(['ID Outlet','Visit Asmen','Tanggal Visit','Catatan Asmen'],$rows[0]);
        self::assertCount(2,$rows);
        self::assertSame('B',$rows[1][0]);
        $this->get($this->url(['tap'=>'DURI']))->assertOk()->assertViewHas('outlets',fn($rows)=>$rows->total()===0);
        $response=$this->get(route('surya.taskforce.download',['tap'=>'DURI','sales_cutoff'=>'2026-10-26']))->assertOk();
        self::assertCount(1,explode("\n",trim($response->streamedContent())));
        $this->postJson(route('surya.taskforce.push-so.store',['idOutlet'=>'A']),['sales_cutoff'=>'2026-10-26','visited'=>1,'visited_at'=>'2026-10-26','notes'=>'=1+1'])->assertOk();
        $response=$this->get(route('surya.taskforce.download',['sales_cutoff'=>'2026-10-26']))->assertOk();
        $rows=array_map('str_getcsv',explode("\n",trim(ltrim($response->streamedContent(),"\xEF\xBB\xBF"))));
        self::assertSame(['A','Sudah visit','26/10/2026',"'=1+1"],$rows[1]);
        $this->asUser('admin_super','SBP_DUMAI');
        $response=$this->get($this->url(['tap'=>'DURI']))->assertOk();
        self::assertSame(['C'],$response->viewData('outlets')->pluck('id_outlet')->all());
        self::assertSame(['SF DURI'],$response->viewData('sfs')->all());
    }
}
