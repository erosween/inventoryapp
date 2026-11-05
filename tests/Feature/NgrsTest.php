<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\NgrsImport;
use App\Services\NgrsReport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NgrsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);DB::purge('sqlite');
        Schema::create('keluar',function(Blueprint $t){$t->string('penerima')->nullable();$t->integer('status');});
        Schema::create('appsdumais',function(Blueprint $t){$t->string('id_outlet')->primary();$t->string('nama_outlet');$t->string('nomor_rs')->nullable();$t->string('tap');});
        DB::table('appsdumais')->insert([
            ['id_outlet'=>'1200000001','nama_outlet'=>'Outlet A','nomor_rs'=>'081234567890','tap'=>'DUMAI'],
            ['id_outlet'=>'1200000002','nama_outlet'=>'Outlet Tanpa Trx','nomor_rs'=>'6281234567891','tap'=>'DUMAI'],
            ['id_outlet'=>'1200000003','nama_outlet'=>'=FORMULA','nomor_rs'=>'6281234567892','tap'=>'DURI'],
        ]);
        $m=require database_path('migrations/2026_11_05_000002_create_surya_ngrs.php');$m->up();$map=require database_path('migrations/2026_11_05_000003_create_surya_ngrs_outlet_map.php');$map->up();$annual=require database_path('migrations/2026_11_05_000004_create_surya_ngrs_annual.php');$annual->up();$comparison=require database_path('migrations/2026_11_05_000005_add_ngrs_comparison_totals.php');$comparison->up();
        $u=new User();$u->username='admin_super';$this->actingAs($u);
    }
    private function file(array $rows): UploadedFile
    {
        $h=fopen('php://temp','w+');fwrite($h,"\xEF\xBB\xBF");fputcsv($h,NgrsImport::HEADERS,',','"','');foreach($rows as$r)fputcsv($h,$r,',','"','');rewind($h);$text=stream_get_contents($h);fclose($h);return UploadedFile::fake()->createWithContent('ngrs.csv',$text);
    }
    private function row(string $date='01/01/2026',string $rs='6281234567890',string $org='121302-PT. MEDIA SWARA PRIMA',int $qty=2,string $tap='DURI',string $cluster='DUMAI BENGKALIS'): array
    {
        return [$date,$rs,$org,(string)$qty,'20,450',$tap,$cluster];
    }
    private function import(array $rows): array {return app(NgrsImport::class)->import($this->file($rows),null);}
    private function filters(array $replace=[]): array {return array_merge(['year'=>2026,'metric'=>'trx','q'=>'','tap'=>'','per_page'=>25],$replace);}
    public function test_reupload_is_monthly_and_organizations_are_not_deduplicated(): void
    {
        $this->import([$this->row(),$this->row('01/01/2026','6281234567890','121314-PT. MEDIA SWARA PRIMA',3),$this->row('01/02/2026')]);
        $r=app(NgrsReport::class);$s=$r->summary($this->filters());$this->assertSame(7,(int)$s['stats']->trx);$this->assertSame(61350,(int)$s['stats']->rech);
        $details=$r->details($this->filters())->get();$a=$details->firstWhere('rs','6281234567890');$this->assertSame('DURI',$a->tap);$this->assertSame('Outlet A',$a->nama_outlet);$this->assertSame(5,(int)$a->m1);$this->assertSame(2,(int)$a->m2);
        $this->assertSame(0,(int)$details->firstWhere('rs','6281234567891')->total);
        $this->import([$this->row('02/01/2026','081234567890','121302-PT. MEDIA SWARA PRIMA',9)]);
        $s=$r->summary($this->filters());$this->assertSame(11,(int)$s['stats']->trx);$this->assertSame(9,$s['grand']['months'][1]);$this->assertSame(2,$s['grand']['months'][2]);
    }
    public function test_infiltration_clusters_unmapped_rs_and_ambiguous_master_do_not_multiply_sales(): void
    {
        DB::table('appsdumais')->insert(['id_outlet'=>'1200000004','nama_outlet'=>'Duplicate RS','nomor_rs'=>'6281234567890','tap'=>'DUMAI']);
        $this->import([$this->row('01/01/2026','6281234567890','ORG',2,'DUMAI'),$this->row('01/01/2026','6289999999999','ORG',3,'INFILTRASI','INF RH'),$this->row('01/01/2026','6289999999998','ORG',4,'INFILTRASI','INF DB')]);
        $r=app(NgrsReport::class);$s=$r->summary($this->filters());$this->assertSame(9,$s['grand']['total']);$this->assertSame(6,$s['clusters']['Dumai Bengkalis']['total']);$this->assertSame(3,$s['clusters']['Rokan Hilir']['total']);$this->assertSame(3,(int)$s['stats']->unmapped);
        $a=$r->details($this->filters())->get()->firstWhere('rs','6281234567890');$this->assertSame(2,(int)$a->candidates);$this->assertSame(2,(int)$a->total);
        $this->assertSame(3,$r->summary($this->filters(['q'=>'6289999999999']))['grand']['total']);
    }
    public function test_bad_upload_preserves_active_data_and_cleans_partial_rows(): void
    {
        $this->import([$this->row()]);$original=DB::table('surya_ngrs_months')->value('upload_id');
        $bad=$this->row();$bad[0]='31/02/2026';
        foreach([[$this->row(),$this->row()],[$bad],[],[array_replace($this->row(),[1=>'6.281E11'])],[array_replace($this->row(),[4=>'-1'])],[array_replace($this->row(),[5=>'INFILTRASI'])]]as$rows){
            try{$this->import($rows);$this->fail('Bad file accepted');}catch(ValidationException $e){$this->assertArrayHasKey('ngrs_file',$e->errors());}
            $this->assertSame($original,DB::table('surya_ngrs_months')->value('upload_id'));$this->assertSame(2,app(NgrsReport::class)->summary($this->filters())['grand']['total']);
        }
        $rows=[];for($i=1;$i<=501;$i++)$rows[]=$this->row('01/01/2026','6281234567890','ORG'.$i);$rows[]=$bad;
        try{$this->import($rows);$this->fail('Late invalid row accepted');}catch(ValidationException $e){$this->assertArrayHasKey('ngrs_file',$e->errors());}
        $this->assertSame(1,DB::table('surya_ngrs_transactions')->count());
    }
    public function test_tap_and_cluster_roles_keep_upload_tap_scope_in_views_and_exports(): void
    {
        $this->import([$this->row(),$this->row('01/01/2026','6281234567892','ORG',4,'DUMAI'),$this->row('01/01/2026','6289999999999','ORG',8,'INFILTRASI','INF RH')]);
        auth()->user()->username='asmen_dumai';$r=app(NgrsReport::class);$this->assertSame(4,$r->summary($this->filters())['grand']['total']);
        $this->assertSame(0,$r->summary($this->filters(['tap'=>'DURI']))['grand']['total']);
        $this->get(route('surya.ngrs'))->assertOk()->assertSee('NGRS')->assertSee('Rekap per TAP');
        $csv=$this->get(route('surya.ngrs.export'))->assertOk()->streamedContent();$this->assertStringNotContainsString('6281234567890',$csv);$this->assertStringContainsString("'=FORMULA",$csv);
        $this->postJson(route('surya.ngrs.upload'))->assertForbidden();$this->getJson(route('surya.ngrs.template'))->assertForbidden();
        auth()->user()->username='cluster_rohil';$this->assertSame(8,$r->summary($this->filters())['grand']['total']);
        auth()->user()->username='admin_super';$template=$this->get(route('surya.ngrs.template'))->assertOk()->streamedContent();$this->assertStringContainsString('TOP ORGANIZATION',$template);
        $this->post(route('surya.ngrs.upload'),['ngrs_file'=>$this->file([$this->row()])])->assertRedirect(route('surya.transaction-upload'))->assertSessionHasNoErrors();
    }
    public function test_empty_year_and_rech_view_and_export_keep_missing_months_blank(): void
    {
        $this->get(route('surya.ngrs'))->assertOk()->assertSee('Belum ada upload NGRS')->assertSee('Outlet Tanpa Trx');
        $this->import([$this->row()]);
        $this->get(route('surya.ngrs',['metric'=>'rech']))->assertOk()->assertDontSee('Total RECH')->assertDontSee('20.450')->assertSee('Detail outlet · TRX');
        $csv=$this->get(route('surya.ngrs.export',['metric'=>'rech','q'=>'6281234567890']))->assertOk()->streamedContent();
        $h=fopen('php://temp','w+');fwrite($h,$csv);rewind($h);$head=fgetcsv($h,0,',','"','');$row=fgetcsv($h,0,',','"','');fclose($h);
        $this->assertSame('20450',$row[6]);$this->assertSame('',$row[7]);$this->assertSame('20450',$row[array_search('Total RECH',$head)]);
        $this->assertContains('Total RECH',$head);
    }
    public function test_header_filters_numeric_sort_facets_and_export_share_scoped_results(): void
    {
        $this->import([$this->row('01/01/2026','6281234567890','A',2,'DUMAI'),$this->row('01/01/2026','6281234567891','A',10,'DUMAI'),$this->row('01/01/2026','6281234567892','A',100,'DURI')]);
        $options=['year'=>2026,'sort'=>'m1','direction'=>'desc','columns'=>['tap'=>['DUMAI']]];
        $html=$this->get(route('surya.ngrs',$options))->assertOk()->assertSee('Filter dan urutkan Jan')->getContent();
        $detail=substr($html,strpos($html,'<table class="ngrs-detail">'));
        $this->assertLessThan(strpos($detail,'6281234567890'),strpos($detail,'6281234567891'));
        $this->assertStringNotContainsString('6281234567892',$detail);
        $csv=$this->get(route('surya.ngrs.export',$options))->assertOk()->streamedContent();
        $rows=array_map('str_getcsv',array_filter(explode("\n",trim($csv))));
        $this->assertCount(3,$rows);$this->assertSame('6281234567891',$rows[1][0]);$this->assertSame('10',$rows[1][6]);
        $u=new User();$u->username='asmen_dumai';$this->actingAs($u);
        $this->getJson(route('surya.ngrs.filter-values',['column'=>'tap','columns'=>['tap'=>['DURI']]]))->assertOk()->assertJsonPath('values',['DUMAI']);
        $this->getJson(route('surya.ngrs.filter-values',['column'=>'rs','columns'=>['tap'=>['DURI']]]))->assertOk()->assertJsonPath('values',[]);
        $this->getJson(route('surya.ngrs.filter-values',['column'=>'m1','q'=>'10']))->assertOk()->assertJsonPath('values',['10']);
        $this->getJson(route('surya.ngrs',['sort'=>'rech']))->assertUnprocessable();
        $this->getJson(route('surya.ngrs.filter-values',['column'=>'bad']))->assertUnprocessable();
    }
    public function test_tap_average_uses_calendar_days_and_global_active_file_cutoff(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-05 12:00:00','Asia/Jakarta'));
        $this->import([$this->row('01/02/2026','6281234567890','A',56,'DUMAI'),$this->row('01/11/2026','6281234567890','A',12,'DUMAI'),$this->row('04/11/2026','6281234567892','A',8,'DURI')]);
        $r=app(NgrsReport::class);$this->assertSame([2=>28,11=>4],$r->dayDivisors(2026,[2,11]));
        $u=new User();$u->username='asmen_dumai';$this->actingAs($u);
        $this->assertSame(4,$r->dayDivisors(2026,[11])[11]);
        $html=$this->get(route('surya.ngrs',['summary_view'=>'average']))->assertOk()->assertSee('Average TRX')->getContent();
        $summary=substr($html,strpos($html,'id="ngrs-summary-panel"'));$summary=substr($summary,0,strpos($summary,'</section>'));
        $this->assertStringContainsString('data-ngrs-days="4">3</td>',$summary);
        $this->assertStringContainsString('data-ngrs-days="28">2</td>',$summary);
        $this->assertStringContainsString('data-ngrs-days="32">3</td>',$summary);
        $this->assertStringContainsString('Detail outlet · TRX',$html);
        // The active replacement, not a superseded batch, defines the current cutoff.
        $this->import([$this->row('02/11/2026','6281234567890','A',6,'DUMAI')]);
        $this->assertSame(2,$r->dayDivisors(2026,[11])[11]);
        $this->travelBack();
    }
    public function test_indexed_master_map_refreshes_when_source_changes_and_keeps_ambiguity(): void
    {
        app(\App\Services\NgrsOutletMap::class)->refresh();
        $this->assertSame('Outlet A',DB::table('surya_ngrs_outlet_map')->where('rs','6281234567890')->value('nama_outlet'));
        DB::table('appsdumais')->where('id_outlet','1200000001')->update(['nama_outlet'=>'Nama Terbaru','tap'=>'DURI']);
        app(\App\Services\NgrsOutletMap::class)->refresh();
        $this->assertSame('Nama Terbaru',DB::table('surya_ngrs_outlet_map')->where('rs','6281234567890')->value('nama_outlet'));
        DB::table('appsdumais')->insert(['id_outlet'=>'1200000004','nama_outlet'=>'RS Ganda','nomor_rs'=>'6281234567890','tap'=>'DUMAI']);
        app(\App\Services\NgrsOutletMap::class)->refresh();
        $this->assertSame(2,DB::table('surya_ngrs_outlet_map')->where('rs','6281234567890')->value('candidates'));
        $this->import([$this->row()]);$r=app(NgrsReport::class);$this->assertSame(2,$r->summary($this->filters())['grand']['total']);
        $this->assertSame(1,(int)$r->summary($this->filters())['stats']->unmapped);
    }
    public function test_summary_recharge_mode_and_ceiling_average_do_not_change_outlet_trx(): void
    {
        $this->assertSame('5.143',\App\Support\NgrsFormat::value(154283,0,30,'average','trx'));
        $this->assertSame('0.100',\App\Support\NgrsFormat::value(0,100000000,30,'monthly','rech'));
        $this->assertSame('0',\App\Support\NgrsFormat::value(0,0,30,'average','trx'));
        $this->import([array_replace($this->row(),[4=>'100000000'])]);
        $html=$this->get(route('surya.ngrs',['summary_metric'=>'rech']))->assertOk()->assertSee('0.100')->assertSee('Detail outlet · TRX')->assertDontSee('Skala sesuai permintaan')->assertDontSee('0.100 mio')->getContent();
        $this->assertStringContainsString('data-ngrs-metric="rech"',$html);
        $this->assertSame(100000000,app(NgrsReport::class)->summary($this->filters())['grand']['total_recharge']);
    }
    public function test_annual_read_model_tracks_month_replacement_without_accumulation(): void
    {
        $this->import([$this->row('01/01/2026','6281234567890','A',4),$this->row('01/02/2026','6281234567890','A',6)]);
        $r=app(NgrsReport::class);$first=$r->details($this->filters())->where('rs','6281234567890')->first();
        $this->assertSame(10,(int)$first->total);$this->assertSame(1,DB::table('surya_ngrs_annual')->count());
        $again=$r->details($this->filters())->where('rs','6281234567890')->first();$this->assertSame(10,(int)$again->total);
        $this->import([$this->row('01/01/2026','6281234567890','A',9)]);
        $next=$r->details($this->filters())->where('rs','6281234567890')->first();
        $this->assertSame(15,(int)$next->total);$this->assertSame(9,(int)$next->m1);$this->assertSame(6,(int)$next->m2);
        $this->assertSame(1,DB::table('surya_ngrs_annual')->count());
    }
    public function test_detail_mom_uses_matching_file_cutoff_and_export_filter_sort_match(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-08 12:00:00','Asia/Jakarta'));
        $rows=[];foreach(['01/01/2026'=>10,'01/02/2026'=>7,'30/09/2026'=>100,'01/10/2026'=>20,'05/10/2026'=>30,'06/10/2026'=>100,'31/10/2026'=>200,'01/11/2026'=>25,'05/11/2026'=>35]as$date=>$qty)$rows[]=$this->row($date,'6281234567890','A',$qty,'DUMAI');
        $this->import($rows);$r=new NgrsReport;$detail=$r->details($this->filters())->where('rs','6281234567890')->first();
        $this->assertSame(350,(int)$detail->m10);$this->assertSame(50,(int)$detail->mom_compare);$this->assertSame(60,(int)$detail->m11);$this->assertSame(10,(int)$detail->gap_m11);$this->assertEquals(20,$detail->mom_pct_m11);$this->assertEquals(-30,$detail->mom_pct_m2);
        $this->assertSame(250,(int)$detail->gap_m10);$this->assertSame(-3,(int)$detail->gap_m2);$this->assertNull($detail->gap_m3);$this->assertNull($detail->mom_pct_m3);$this->assertNull($detail->gap_m12);
        $this->get(route('surya.ngrs'))->assertOk()->assertSee('Okt-MoM')->assertSee('GAP Nov');
        $options=['columns'=>['rs'=>['6281234567890'],'gap_m11'=>['10']],'sort'=>'gap_m2','direction'=>'asc'];
        $csv=$this->get(route('surya.ngrs.export',$options))->assertOk()->streamedContent();$h=fopen('php://temp','w+');fwrite($h,$csv);rewind($h);$header=fgetcsv($h,0,',','"','');$row=fgetcsv($h,0,',','"','');fclose($h);
        $this->assertSame('350',$row[array_search('2026-10',$header)]);$this->assertSame('50',$row[array_search('2026-10 MoM 1-05',$header)]);$this->assertSame('10',$row[array_search('GAP MoM 2026-11',$header)]);$this->assertSame('-3',$row[array_search('GAP MoM 2026-02',$header)]);$this->assertSame('20',$row[array_search('MoM % 2026-11',$header)]);
        $this->getJson(route('surya.ngrs.filter-values',['column'=>'gap_m11','columns'=>['rs'=>['6281234567890']]]))->assertOk()->assertJsonPath('values',['10']);
        $this->travelTo(\Carbon\Carbon::parse('2026-12-08 12:00:00','Asia/Jakarta'));
        $closed=(new NgrsReport)->details($this->filters())->where('rs','6281234567890')->first();$this->assertSame(-290,(int)$closed->gap_m11);$this->assertNull($closed->mom_compare);
        $this->travelBack();
    }
    public function test_mom_clamps_short_previous_month_and_missing_previous_month_is_blank():void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-03-31 12:00:00','Asia/Jakarta'));
        $this->import([$this->row('28/02/2026','6281234567890','A',15),$this->row('31/03/2026','6281234567890','A',20)]);
        $plan=app(\App\Services\NgrsComparison::class)->plan(2026);$this->assertSame('2026-02-28',$plan['previous_end']);
        $detail=(new NgrsReport)->details($this->filters())->where('rs','6281234567890')->first();$this->assertSame(5,(int)$detail->gap_m3);$this->assertNull($detail->gap_m2);
        $this->travelBack();
    }
    public function test_tap_summary_mom_all_modes_uses_fair_cutoff_and_direction_styles():void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-08 12:00:00','Asia/Jakarta'));
        $rows=[];foreach(['01/01/2026'=>[100,100000000],'01/02/2026'=>[100,100000000],'01/03/2026'=>[50,50000000],'01/04/2026'=>[120,120000000],'01/10/2026'=>[20,100000000],'05/10/2026'=>[30,200000000],'31/10/2026'=>[300,900000000],'01/11/2026'=>[25,150000000],'05/11/2026'=>[35,200000000]]as$date=>$v)$rows[]=array_replace($this->row($date,'6281234567890','A',$v[0],'DUMAI'),[4=>(string)$v[1]]);
        $this->import($rows);$summary=(new NgrsReport)->summary($this->filters());$row=$summary['rows']['DUMAI|DUMAI BENGKALIS'];
        $this->assertSame(50,$row['compare_trx']);$this->assertSame(300000000,$row['compare_rech']);$this->assertSame(50,$summary['grand']['compare_trx']);$this->assertSame(50,$summary['clusters']['Dumai Bengkalis']['compare_trx']);
        foreach([['monthly','trx','▲ +10'],['average','trx','▲ +2'],['monthly','rech','▲ +0.050'],['average','rech','▲ +0.010']]as[$period,$metric,$gap]){
            $html=$this->get(route('surya.ngrs',['summary_view'=>$period,'summary_metric'=>$metric]))->assertOk()->assertSee('Okt-MoM')->assertSee($gap)->getContent();
            $this->assertStringContainsString('ngrs-summary-gap is-positive',$html);$this->assertStringContainsString('ngrs-summary-gap is-negative',$html);$this->assertStringContainsString('ngrs-summary-gap is-flat',$html);
        }
        $this->assertSame('▼ -50',\App\Support\NgrsFormat::gap(50,0,31,100,0,28,'monthly','trx')['text']);
        $this->assertSame('flat',\App\Support\NgrsFormat::gap(0,0,31,0,0,28,'average','trx')['direction']);
        $this->assertNull(\App\Support\NgrsFormat::gap(5,0,5,0,0,0,'monthly','trx')['value']);
        // Closing the month invalidates the summary comparison cache as well.
        $this->travelTo(\Carbon\Carbon::parse('2026-12-08 12:00:00','Asia/Jakarta'));
        $closed=(new NgrsReport)->summary($this->filters());$this->assertArrayNotHasKey('compare_trx',$closed['rows']['DUMAI|DUMAI BENGKALIS']);
        $this->travelBack();
    }
    public function test_cumulative_daily_november_upload_replaces_month_and_replay_never_adds():void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-08 12:00:00','Asia/Jakarta'));
        $this->import([array_replace($this->row('01/10/2026','6281234567890','A',20,'DUMAI'),[4=>'200']),array_replace($this->row('01/11/2026','6281234567890','A',100,'DUMAI'),[4=>'1000']),array_replace($this->row('02/11/2026','6281234567891','A',40,'DUMAI'),[4=>'400'])]);
        $r=new NgrsReport;$r->summary($this->filters());$r->details($this->filters())->get();
        $latest=[array_replace($this->row('01/11/2026','6281234567890','A',120,'DUMAI'),[4=>'500']),array_replace($this->row('03/11/2026','6281234567890','A',60,'DUMAI'),[4=>'600'])];
        foreach([1,2]as$pass){
            $this->import($latest);$report=new NgrsReport;$summary=$report->summary($this->filters());
            $this->assertSame(200,$summary['grand']['total']);$this->assertSame(1300,$summary['grand']['total_recharge']);$this->assertSame(180,$summary['grand']['months'][11]);$this->assertSame(20,$summary['grand']['months'][10]);$this->assertSame(20,$summary['grand']['compare_trx']);
            $detail=$report->details($this->filters())->where('rs','6281234567890')->first();$this->assertSame(180,(int)$detail->m11);$this->assertSame(160,(int)$detail->gap_m11);
            $removed=$report->details($this->filters())->where('rs','6281234567891')->first();$this->assertSame(0,(int)$removed->m11);
            $this->assertSame(2,DB::table('surya_ngrs_months')->count());
        }
        $this->travelBack();
    }
    public function test_summary_excel_matches_selected_modes_scope_cutoff_and_numeric_gaps():void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-08 12:00:00','Asia/Jakarta'));
        $rows=[];foreach(['01/01/2026'=>[10,100000000],'01/02/2026'=>[7,50000000],'01/10/2026'=>[20,100000000],'05/10/2026'=>[30,200000000],'31/10/2026'=>[300,900000000],'01/11/2026'=>[25,150000000],'05/11/2026'=>[35,200000000]]as$date=>$v)$rows[]=array_replace($this->row($date,'6281234567890','A',$v[0],'DUMAI'),[4=>(string)$v[1]]);
        $rows[]=$this->row('05/11/2026','6281234567892','A',999,'DURI');$this->import($rows);
        $u=new User();$u->username='asmen_dumai';$this->actingAs($u);
        foreach([['monthly','trx',60,50,10],['average','trx',12,10,2],['monthly','rech',0.350,0.300,0.050],['average','rech',0.070,0.060,0.010]]as[$period,$metric,$nov,$oct,$gap]){
            $response=$this->get(route('surya.ngrs.summary-export',['year'=>2026,'summary_view'=>$period,'summary_metric'=>$metric]));$response->assertOk()->assertDownload('Rekap_TAP_NGRS_2026_'.strtoupper($metric).'_'.$period.'.xlsx');
            $path=$response->baseResponse->getFile()->getPathname();$book=\PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            try{
                $sheet=$book->getActiveSheet();$header=$sheet->rangeToArray('A4:'.$sheet->getHighestColumn().'4',null,true,false)[0];
                $cell=fn($label,$row=5)=>$sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(array_search($label,$header)+1).$row);
                $this->assertSame('DUMAI',$sheet->getCell('A5')->getValue());$this->assertSame('CLUSTER · Dumai Bengkalis',$sheet->getCell('A6')->getValue());$this->assertSame('GRAND TOTAL',$sheet->getCell('A7')->getValue());$this->assertSame(7,$sheet->getHighestRow());
                $this->assertEquals($nov,$cell('Nov')->getValue());$this->assertEquals($oct,$cell('Okt-MoM (1-5)')->getValue());$this->assertEquals($gap,$cell('GAP Nov')->getValue());$this->assertEquals($gap,$cell('GAP Nov',7)->getValue());
                $this->assertEquals($metric==='trx'?0.2:0.167,$cell('MoM % Nov')->getValue());$this->assertSame('n',$cell('MoM % Nov')->getDataType());$this->assertStringContainsString('%',$cell('MoM % Nov')->getStyle()->getNumberFormat()->getFormatCode());$this->assertSame('n',$cell('GAP Nov')->getDataType());$this->assertSame('12805C',$cell('GAP Nov')->getStyle()->getFont()->getColor()->getRGB());$this->assertStringContainsString('▲',$cell('GAP Nov')->getStyle()->getNumberFormat()->getFormatCode());
                $this->assertSame('B5',$sheet->getFreezePane());$this->assertStringContainsString('05/11/2026',$sheet->getCell('A2')->getValue());
                if($period==='monthly'&&$metric==='trx'){$this->assertSame(-3,$cell('GAP Feb')->getValue());$this->assertSame('n',$cell('GAP Feb')->getDataType());$this->assertSame('D92D20',$cell('GAP Feb')->getStyle()->getFont()->getColor()->getRGB());}
                if($period==='average'&&$metric==='trx'){$this->assertSame(0,$cell('GAP Feb')->getValue());$this->assertSame('172033',$cell('GAP Feb')->getStyle()->getFont()->getColor()->getRGB());$this->assertSame('EEF2F6',$cell('GAP Feb')->getStyle()->getFill()->getStartColor()->getRGB());}
            }finally{$book->disconnectWorksheets();unlink($path);}
        }
        $this->travelBack();
    }
    public function test_percent_handles_zero_base_loss_and_fair_average_values():void
    {
        $this->assertSame(20.0,\App\Support\NgrsFormat::percent(60,0,5,50,0,5,'monthly','trx')['value']);
        $this->assertSame(20.0,\App\Support\NgrsFormat::percent(60,0,5,50,0,5,'average','trx')['value']);
        $this->assertSame(16.7,\App\Support\NgrsFormat::percent(0,350000000,5,0,300000000,5,'average','rech')['value']);
        $this->assertSame('▼ -100,0%',\App\Support\NgrsFormat::percent(0,0,5,50,0,5,'monthly','trx')['text']);
        $this->assertNull(\App\Support\NgrsFormat::percent(50,0,5,0,0,5,'monthly','trx')['value']);
        $this->import([$this->row('01/01/2026','6281234567890','A',10,'DUMAI'),$this->row('01/02/2026','6281234567890','A',7,'DUMAI'),$this->row('01/02/2026','6281234567891','A',5,'DUMAI')]);
        $r=new NgrsReport;$row=$r->details($this->filters())->where('rs','6281234567891')->first();$this->assertNull($row->mom_pct_m2);
        $this->getJson(route('surya.ngrs.filter-values',['column'=>'mom_pct_m2','columns'=>['rs'=>['6281234567890']]]))->assertOk()->assertJsonPath('values',['-30.0']);
        $this->get(route('surya.ngrs',['columns'=>['mom_pct_m2'=>['-30.0']],'sort'=>'mom_pct_m2','direction'=>'asc']))->assertOk()->assertSee('MoM % Feb')->assertSee('▼ -30,0%');
    }
}
