<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\OutletlistImport;
use App\Services\SuryaOutletCoordinates;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OutletlistImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);
        DB::purge('sqlite');
        Schema::create('idsf',function(Blueprint $t){$t->string('idsf');$t->string('idtap');$t->string('namasf');});
        DB::table('idsf')->insert(['idsf'=>'SF1','idtap'=>'DUMAI','namasf'=>'SALES A']);
        Schema::create('appsdumais',function(Blueprint $t){
            $t->unsignedBigInteger('id_outlet')->primary();
            foreach(array_diff(OutletlistImport::COLUMNS,['id_outlet']) as $c) $t->string($c)->nullable();
            foreach(OutletlistImport::PROTECTED_COLUMNS as $c) $t->decimal($c,11,8)->nullable();
        });
        Schema::create('surya_visits',function(Blueprint $t){
            $t->id();$t->string('id_outlet');$t->string('gps_status');$t->dateTime('visited_at')->nullable();
            $t->decimal('latitude',10,8);$t->decimal('longitude',11,8);
        });
        DB::table('appsdumais')->insert(['id_outlet'=>1200000001,'nama_outlet'=>'Outlet lama','tap'=>'DUMAI','pjp'=>'Senin','latitude'=>'1.0','longitude'=>'101.0']);
    }
    private function file(array $rows, ?array $headers=null, string $delimiter=','): UploadedFile
    {
        $h=fopen('php://temp','w+');fwrite($h,"\xEF\xBB\xBF");fputcsv($h,$headers??OutletlistImport::COLUMNS,$delimiter,'"','');
        foreach($rows as$row)fputcsv($h,$row,$delimiter,'"','');rewind($h);$csv=stream_get_contents($h);fclose($h);
        return UploadedFile::fake()->createWithContent('outletlist.csv',$csv);
    }
    private function row(string $id='1200000001'): array
    {
        return [$id,'Outlet, baru','62812000000','Dumai Kota','DUMAI BENGKALIS','SALES A','DUMAI','101.25','1.25','Selasa'];
    }
    private function visit(array $attributes=[]): int
    {
        return DB::table('surya_visits')->insertGetId(array_merge(['id_outlet'=>'1200000001','gps_status'=>'approved','visited_at'=>'2026-10-27 12:00:00','latitude'=>1.5,'longitude'=>101.5],$attributes));
    }
    public function test_raw_digipos_upload_and_reupload_never_overwrite_confirmed_checkin(): void
    {
        $this->visit();$coordinates=app(SuryaOutletCoordinates::class);$this->assertTrue($coordinates->syncOutlet('1200000001'));
        $this->assertEquals(1.0,DB::table('appsdumais')->value('latitude'));
        $headers=array_merge(OutletlistImport::COLUMNS,OutletlistImport::PROTECTED_COLUMNS);
        $row=array_merge($this->row(),['77','177']);
        $this->assertSame(['count'=>1],app(OutletlistImport::class)->import($this->file([$row],$headers,';')));
        $master=DB::table('appsdumais')->first();
        $this->assertEquals(1.25,$master->latitude);$this->assertEquals(101.25,$master->longitude);
        $this->assertEquals(1.5,$master->latitude_checkin);$this->assertEquals(101.5,$master->longitude_checkin);
        $this->assertSame('Outlet, baru',$master->nama_outlet);$this->assertSame('Selasa',$master->pjp);
        app(OutletlistImport::class)->import($this->file([$this->row()]));
        $resolved=$coordinates->outlets()->first();$this->assertEquals(1.5,$resolved->latitude);$this->assertEquals(101.5,$resolved->longitude);
        $this->assertSame('visit',$resolved->coordinate_source);$this->assertSame(1,DB::table('appsdumais')->count());
    }
    public function test_uploaded_name_and_pjp_resolve_to_master_name_and_day(): void
    {
        $row=$this->row();$row[5]='  sales   a  ';$row[9]=' selasa ';
        app(OutletlistImport::class)->import($this->file([$row]));
        $master=DB::table('appsdumais')->first();
        $this->assertSame('SALES A',$master->sf);
        $this->assertSame('Selasa',$master->pjp);
    }
    public function test_unmapping_pjp_with_empty_or_unmapped_sf_is_accepted(): void
    {
        foreach (['', ' unmapping '] as $sf) {
            $row=$this->row();$row[5]=$sf;$row[9]=' unmapping ';
            app(OutletlistImport::class)->import($this->file([$row]));
            $master=DB::table('appsdumais')->first();
            $this->assertSame('UNMAPPING',$master->pjp);
            $this->assertSame($sf === '' ? null : 'UNMAPPING',$master->sf);
            $this->assertSame(1,DB::table('appsdumais')->count());
        }
    }
    public function test_blank_null_zero_and_partial_coordinates_are_accepted_without_losing_checkin(): void
    {
        $this->visit();app(SuryaOutletCoordinates::class)->syncOutlet('1200000001');
        foreach ([['',''],['NULL','null'],['0','0'],['0.0','0,00'],['','101.25'],['1.25',''],['0','']] as [$latitude,$longitude]) {
            $row=$this->row();$row[7]=$longitude;$row[8]=$latitude;
            app(OutletlistImport::class)->import($this->file([$row]));
            $master=DB::table('appsdumais')->first();
            $this->assertNull($master->latitude);$this->assertNull($master->longitude);
            $this->assertEquals(1.5,$master->latitude_checkin);$this->assertEquals(101.5,$master->longitude_checkin);
            $resolved=app(SuryaOutletCoordinates::class)->outlets()->first();
            $this->assertEquals(1.5,$resolved->latitude);$this->assertEquals(101.5,$resolved->longitude);
        }
    }
    public function test_new_outlet_with_missing_coordinates_is_imported_and_a_valid_equator_point_is_kept(): void
    {
        $row=$this->row('1200000002');$row[7]='';$row[8]='NULL';
        app(OutletlistImport::class)->import($this->file([$row]));
        $resolved=app(SuryaOutletCoordinates::class)->outlets()->where('id_outlet','1200000002')->first();
        $this->assertNull($resolved->latitude);$this->assertNull($resolved->longitude);
        $row[7]='101.25';$row[8]='0';
        app(OutletlistImport::class)->import($this->file([$row]));
        $master=DB::table('appsdumais')->where('id_outlet','1200000002')->first();
        $this->assertEquals(0,$master->latitude);$this->assertEquals(101.25,$master->longitude);
    }
    public function test_unknown_name_wrong_tap_or_invalid_day_rejects_without_partial_update(): void
    {
        foreach ([['sf'=>'SF1'],['sf'=>'UNKNOWN'],['tap'=>'DURI'],['pjp'=>'Tuesday']] as $changes) {
            $row=array_combine(OutletlistImport::COLUMNS,$this->row());
            foreach($changes as $key=>$value)$row[$key]=$value;
            try { app(OutletlistImport::class)->import($this->file([array_values($row)])); $this->fail('Invalid assignment accepted'); }
            catch(ValidationException $e){$this->assertArrayHasKey('outletlist_file',$e->errors());}
            $this->assertSame('Outlet lama',DB::table('appsdumais')->value('nama_outlet'));
        }
    }
    public function test_reassigned_outlet_keeps_latest_accepted_checkin_by_id_and_ignores_rejected_point(): void
    {
        DB::table('idsf')->insert(['idsf'=>'SF2','idtap'=>'DUMAI','namasf'=>'SALES B']);
        $this->visit(['latitude'=>1.4,'longitude'=>101.4]);
        $this->visit(['visited_at'=>'2026-11-01 10:00:00','latitude'=>1.6,'longitude'=>101.6]);
        $this->visit(['gps_status'=>'rejected','visited_at'=>'2026-11-02 10:00:00','latitude'=>9,'longitude'=>109]);
        $row=$this->row();$row[5]='SALES B';$row[9]='Rabu';
        app(OutletlistImport::class)->import($this->file([$row]));
        $outlet=app(SuryaOutletCoordinates::class)->outlets()->where('id_outlet','1200000001')->first();
        $this->assertSame('SALES B',$outlet->sf);$this->assertSame('Rabu',$outlet->pjp);
        $this->assertEquals(1.6,$outlet->latitude);$this->assertEquals(101.6,$outlet->longitude);
        $this->assertSame(1,DB::table('appsdumais')->count());
    }
    public function test_new_outlet_without_visits_uses_uploaded_coordinates_and_unlisted_outlets_remain(): void
    {
        app(OutletlistImport::class)->import($this->file([$this->row('1200000002')]));
        $outlet=app(SuryaOutletCoordinates::class)->outlets()->where('id_outlet','1200000002')->first();
        $this->assertEquals(1.25,$outlet->latitude);$this->assertSame('master',$outlet->coordinate_source);
        $this->assertNull($outlet->latitude_checkin);$this->assertSame(2,DB::table('appsdumais')->count());
    }
    public function test_new_approved_visits_update_only_checkin_columns_and_rejected_visits_do_not_win(): void
    {
        $this->visit();$this->visit(['gps_status'=>'rejected','visited_at'=>'2026-10-28 12:00:00','latitude'=>9]);
        $service=app(SuryaOutletCoordinates::class);$this->assertTrue($service->syncOutlet('1200000001'));
        $this->assertFalse($service->syncOutlet('1200000001'));
        $this->assertEquals(1.5,DB::table('appsdumais')->value('latitude_checkin'));
        $this->assertEquals(1,DB::table('appsdumais')->value('latitude'));
        $this->assertSame(0,$service->synchronize()['changed']);
        $this->visit(['id_outlet'=>'1200000001-OTHER','latitude'=>8]);
        $this->assertFalse($service->syncOutlet('1200000001-OTHER'));
        $this->assertEquals(1.5,DB::table('appsdumais')->value('latitude_checkin'));
    }
    public function test_saved_checkin_is_used_when_visit_history_is_not_available(): void
    {
        DB::table('appsdumais')->update(['latitude_checkin'=>1.7,'longitude_checkin'=>101.7]);
        app(OutletlistImport::class)->import($this->file([$this->row()]));
        $resolved=app(SuryaOutletCoordinates::class)->outlets()->first();
        $this->assertEquals(1.7,$resolved->latitude);$this->assertEquals(101.7,$resolved->longitude);
        $this->assertSame('visit',$resolved->coordinate_source);
    }
    public function test_invalid_late_row_or_duplicate_rejects_entire_file_without_master_changes(): void
    {
        $rows=[];for($i=0;$i<501;$i++)$rows[]=$this->row((string)(1200000001+$i));
        $rows[500][8]='91';
        try{app(OutletlistImport::class)->import($this->file($rows));$this->fail('Invalid latitude accepted');}catch(ValidationException $e){$this->assertArrayHasKey('outletlist_file',$e->errors());}
        $this->assertSame('Outlet lama',DB::table('appsdumais')->value('nama_outlet'));$this->assertSame(1,DB::table('appsdumais')->count());
        try{app(OutletlistImport::class)->import($this->file([$this->row(),$this->row()]));$this->fail('Duplicate accepted');}catch(ValidationException $e){$this->assertStringContainsString('berulang',$e->getMessage());}
        $this->assertSame('Outlet lama',DB::table('appsdumais')->value('nama_outlet'));
    }
    public function test_header_validation_and_empty_files_do_not_modify_data(): void
    {
        foreach([$this->file([]),$this->file([$this->row()],array_merge(['bad_id'],array_slice(OutletlistImport::COLUMNS,1)))] as$file){
            try{app(OutletlistImport::class)->import($file);$this->fail('Invalid file accepted');}catch(ValidationException $e){$this->assertArrayHasKey('outletlist_file',$e->errors());}
        }
        $this->assertSame(1,DB::table('appsdumais')->count());
    }
    public function test_migration_preserves_accepted_points_and_appends_columns_after_pjp(): void
    {
        Schema::table('appsdumais',fn(Blueprint $t)=>$t->dropColumn(OutletlistImport::PROTECTED_COLUMNS));
        $this->visit();$this->visit(['id_outlet'=>'1200000001-OTHER','latitude'=>8,'visited_at'=>'2026-10-28 12:00:00']);
        $migration=require database_path('migrations/2026_10_28_190000_separate_outlet_checkin_coordinates.php');$migration->up();
        $this->assertEquals(1.5,DB::table('appsdumais')->value('latitude_checkin'));
        $this->assertEquals(1.0,DB::table('appsdumais')->value('latitude'));
        $columns=Schema::getColumnListing('appsdumais');$this->assertSame(['pjp','latitude_checkin','longitude_checkin'],array_slice($columns,-3));
    }
    public function test_only_admin_super_can_upload_or_download_template(): void
    {
        $u=new User();$u->username='asmen_dumai';$this->actingAs($u)->withSession(['idtap'=>'DUMAI']);
        $this->postJson(route('surya.outletlist.upload'),['outletlist_file'=>$this->file([$this->row()])])->assertForbidden();
        $this->getJson(route('surya.outletlist.template'))->assertForbidden();
        $u->username='admin_super';$this->actingAs($u);
        $template=$this->get(route('surya.outletlist.template'))->assertOk()->streamedContent();
        $this->assertStringContainsString('pjp,latitude_checkin,longitude_checkin',$template);
        $this->postJson(route('surya.outletlist.upload'),['outletlist_file'=>$this->file([$this->row()])])->assertRedirect(route('surya.transaction-upload'))->assertSessionHasNoErrors();
    }
}
