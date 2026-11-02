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
        Schema::create('idsf',function(Blueprint $t){$t->string('idsf')->primary();$t->string('idtap');$t->string('namasf');$t->string('login_code');});
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
