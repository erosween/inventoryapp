<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Http\Controllers\SfController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SfMasterManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        Schema::create('idsf',function(Blueprint $t){$t->string('idsf')->primary();$t->string('idtap');$t->string('namasf');$t->string('login_code');$t->string('digipos_id')->nullable()->unique();$t->boolean('is_hidden')->default(false);});
        Schema::create('kodetap',fn(Blueprint $t)=>$t->string('idtap'));
        Schema::create('stockawalsf',function(Blueprint $t){$t->string('idsf');$t->integer('stock');});
        Schema::create('mobile_penjualan',function(Blueprint $t){$t->string('idsf');$t->string('owner_idsf')->nullable();});
        DB::table('idsf')->insert(['idsf'=>'SF1','idtap'=>'DUMAI','namasf'=>'SALESMAN','login_code'=>'ABC123']);
        DB::table('kodetap')->insert(['idtap'=>'DUMAI']);
        DB::table('stockawalsf')->insert(['idsf'=>'SF1','stock'=>0]);
        $u=new User();$u->username='admin_super';$this->actingAs($u);
    }
    public function test_edit_changes_name_but_preserves_identity_and_login(): void
    {
        app(SfController::class)->update(Request::create('/','POST',['idtap'=>'DUMAI','namasf'=>'Sales Baru']),'SF1');
        $this->assertSame('SALES BARU',DB::table('idsf')->value('namasf'));
        $this->assertSame('ABC123',DB::table('idsf')->value('login_code'));
    }
    public function test_digipos_id_is_normalized_and_does_not_replace_surya_id(): void
    {
        app(SfController::class)->update(Request::create('/','POST',['idtap'=>'DUMAI','namasf'=>'Sales Baru','digipos_id'=>' 00012abc ']),'SF1');
        $this->assertSame('00012ABC',DB::table('idsf')->where('idsf','SF1')->value('digipos_id'));
        $this->assertSame('ABC123',DB::table('idsf')->value('login_code'));
    }
    public function test_duplicate_digipos_id_is_rejected(): void
    {
        DB::table('idsf')->insert(['idsf'=>'SF2','idtap'=>'DUMAI','namasf'=>'OTHER','login_code'=>'DEF123','digipos_id'=>'DIG123']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(SfController::class)->update(Request::create('/','POST',['idtap'=>'DUMAI','namasf'=>'Sales Baru','digipos_id'=>'DIG123']),'SF1');
    }
    public function test_helper_name_longer_than_old_database_limit_can_be_saved(): void
    {
        app(SfController::class)->update(Request::create('/','POST',['idtap'=>'DUMAI','namasf'=>'HELPER - AFIF ALWAN SYAHRIDHO']),'SF1');
        $this->assertSame('HELPER - AFIF ALWAN SYAHRIDHO',DB::table('idsf')->value('namasf'));
    }
    public function test_overlong_name_is_validation_error(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(SfController::class)->update(Request::create('/','POST',['idtap'=>'DUMAI','namasf'=>str_repeat('X',101)]),'SF1');
    }
    public function test_hide_and_unhide_preserves_master_and_stock_and_filters_both_dropdowns(): void
    {
        $request=Request::create('/','POST',['idtap'=>'DUMAI','namasf'=>'SALESMAN','is_hidden'=>'1']);
        app(SfController::class)->update($request,'SF1');
        $this->assertEquals(1,DB::table('idsf')->value('is_hidden'));
        $this->assertSame(1,DB::table('stockawalsf')->count());
        $this->withSession(['idtap'=>'DUMAI']);
        $get=Request::create('/','POST',['idtap'=>'DUMAI']);
        foreach ([\App\Http\Controllers\SfmasukController::class,\App\Http\Controllers\SfkeluarController::class] as $controller) {
            $this->assertCount(0,app($controller)->getSf($get)->getData());
        }
        app(SfController::class)->update(Request::create('/','POST',['idtap'=>'DUMAI','namasf'=>'SALESMAN']),'SF1');
        foreach ([\App\Http\Controllers\SfmasukController::class,\App\Http\Controllers\SfkeluarController::class] as $controller) {
            $this->assertCount(1,app($controller)->getSf($get)->getData());
        }
        $this->assertSame('ABC123',DB::table('idsf')->value('login_code'));
    }
    public function test_unused_sf_and_zero_stock_can_be_deleted(): void
    {
        app(SfController::class)->destroy('SF1');
        $this->assertSame(0,DB::table('idsf')->count());
        $this->assertSame(0,DB::table('stockawalsf')->count());
    }
    public function test_nonzero_stock_blocks_delete(): void
    {
        DB::table('stockawalsf')->update(['stock'=>12]);
        $response=app(SfController::class)->destroy('SF1');
        $this->assertArrayHasKey('error',$response->getSession()->all());
        $this->assertSame(1,DB::table('idsf')->count());
        $this->assertSame(12,DB::table('stockawalsf')->value('stock'));
    }
    public function test_mobile_stock_owner_history_blocks_delete(): void
    {
        DB::table('mobile_penjualan')->insert(['idsf'=>'HELPER','owner_idsf'=>'SF1']);
        app(SfController::class)->destroy('SF1');
        $this->assertSame(1,DB::table('idsf')->count());
        $this->assertSame(1,DB::table('mobile_penjualan')->count());
    }
    public function test_non_admin_cannot_delete(): void
    {
        $u=new User();$u->username='asmen_dumai';$this->actingAs($u);
        try { app(SfController::class)->destroy('SF1'); $this->fail('Expected 403'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403,$e->getStatusCode()); }
        $this->assertSame(1,DB::table('idsf')->count());
    }
}
