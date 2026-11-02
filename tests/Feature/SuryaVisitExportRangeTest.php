<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class SuryaVisitExportRangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        $definitions=[
            'surya_visits'=>['visited_at','checkout_at','id_outlet','idtap','idsf','latitude','longitude','distance_meters','gps_status','checkout_reason','visit_type','outlet_name'],
            'appsdumais'=>['id_outlet','nama_outlet','latitude','longitude','pjp'],
            'idsf'=>['idsf','namasf'],
            'denom'=>['iddenom','denom','kategori_inject','harga_jual'],
            'mobile_penjualan'=>['tgl','idsf','owner_idsf','idtap','id_outlet','iddenom','qty','unit_price','status'],
            'keluarsf'=>['tgl','idtap','idsf','id_outlet','iddenom','tambahanket','qty'],
            'surya_sa_consignment_numbers'=>['reported_at','idtap','idsf','id_outlet','iddenom','serial_number','status','notes'],
            'surya_sa_consignment_movements'=>['moved_at','serial_number','from_id_outlet','to_id_outlet','idtap','idsf','receipt_id'],
        ];
        foreach($definitions as $name=>$columns) Schema::create($name,function(Blueprint $t)use($columns){$t->id();foreach($columns as$c)$t->string($c)->nullable();});
        $u=new User();$u->username='admin_super';$this->actingAs($u)->withSession(['idtap'=>'SBP_DUMAI']);
        $this->travelTo(\Carbon\Carbon::parse('2026-11-02 12:00:00','Asia/Jakarta'));
        foreach(['2026-09-30 23:59:59','2026-10-01 00:00:00','2026-10-31 23:59:59','2026-11-01 00:00:00'] as $date) {
            foreach(['DUMAI','BAGAN BATU'] as $tap) {
                DB::table('surya_visits')->insert(['visited_at'=>$date,'idtap'=>$tap,'id_outlet'=>'12001','idsf'=>'SF1']);
                DB::table('mobile_penjualan')->insert(['tgl'=>$date,'idtap'=>$tap,'id_outlet'=>'12001','idsf'=>'SF1','qty'=>1]);
                DB::table('keluarsf')->insert(['tgl'=>$date,'idtap'=>$tap,'id_outlet'=>'12001','idsf'=>'SF1','qty'=>1]);
                DB::table('surya_sa_consignment_numbers')->insert(['reported_at'=>$date,'idtap'=>$tap,'id_outlet'=>'12001','idsf'=>'SF1']);
                DB::table('surya_sa_consignment_movements')->insert(['moved_at'=>$date,'idtap'=>$tap,'to_id_outlet'=>'12001','idsf'=>'SF1']);
            }
        }
        Excel::fake();
    }
    public function test_october_range_is_inclusive_and_applies_to_every_sheet(): void
    {
        $this->get(route('surya.visits.export',['date_from'=>'2026-10-01','date_to'=>'2026-10-31']))->assertOk();
        Excel::assertDownloaded('surya-penjualan-2026-10-01-sampai-2026-10-31.xlsx',function($export){
            foreach($export->sheets() as $index=>$sheet){
                $this->assertCount($index===3?8:4,$sheet->collection());
                foreach($sheet->collection() as$row)$this->assertStringContainsString('/10/2026',$row[0]);
            }
            return true;
        });
    }
    public function test_range_preserves_asmen_tap_scope(): void
    {
        $u=new User();$u->username='asmen_dumai';$this->actingAs($u)->withSession(['idtap'=>'DUMAI']);
        $this->get(route('surya.visits.export',['date_from'=>'2026-10-01','date_to'=>'2026-10-31']))->assertOk();
        Excel::assertDownloaded('surya-penjualan-2026-10-01-sampai-2026-10-31.xlsx',function($export){
            foreach($export->sheets() as$index=>$sheet)$this->assertCount($index===3?4:2,$sheet->collection());
            return true;
        });
    }
    public function test_invalid_or_incomplete_range_returns_validation_error(): void
    {
        foreach([['date_from'=>'2026-10-31','date_to'=>'2026-10-01'],['date_from'=>'2026-10-01'],['date_to'=>'2026-10-31'],['date_from'=>'bad','date_to'=>'bad']] as$params) {
            $this->getJson(route('surya.visits.export',$params))->assertUnprocessable();
        }
    }
    public function test_old_link_without_range_keeps_month_to_date_default(): void
    {
        $this->get(route('surya.visits.export'))->assertOk();
        Excel::assertDownloaded('surya-penjualan-2026-11-01-sampai-2026-11-02.xlsx');
    }
}
