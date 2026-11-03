<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Http\Controllers\ReturSfController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

class ReturSfDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');$schema=DB::getSchemaBuilder();
        $schema->create('denom',function(Blueprint $t){$t->string('iddenom');$t->string('denom');});
        DB::table('denom')->insert(['iddenom'=>'V1','denom'=>'1GB/3hari']);
        $schema->create('retursf',function(Blueprint $t){$t->integer('idretur')->primary();$t->string('idtap');$t->string('idsf');$t->string('iddenom');$t->integer('qty');});
        $schema->create('stockawaltap',function(Blueprint $t){$t->string('idtap');$t->string('iddenom');$t->integer('stock');$t->primary(['idtap','iddenom']);});
        $schema->create('stockawalsf',function(Blueprint $t){$t->string('idsf');$t->string('iddenom');$t->integer('stock');$t->primary(['idsf','iddenom']);});
        $schema->create('logs',function(Blueprint $t){$t->id();foreach(['username','action','module','record_id','old_values','new_values','ip_address','created_at','updated_at']as$c)$t->text($c)->nullable();});
        DB::table('retursf')->insert(['idretur'=>1,'idtap'=>'DUMAI','idsf'=>'SF1','iddenom'=>'V1','qty'=>40]);
        DB::table('stockawaltap')->insert(['idtap'=>'DUMAI','iddenom'=>'V1','stock'=>100]);
        DB::table('stockawalsf')->insert(['idsf'=>'SF1','iddenom'=>'V1','stock'=>10]);
        $this->actingAs(new User(['id'=>1,'username'=>'admin_cluster']))->withSession(['idtap'=>'SBP_DUMAI']);
    }
    public function test_insufficient_tap_stock_returns_message_instead_of_500_and_changes_nothing(): void
    {
        DB::table('stockawaltap')->update(['stock'=>2]);
        $response=app(ReturSfController::class)->delete(Request::create('/retursf/1','POST'),1);
        $this->assertSame(302,$response->getStatusCode());
        $this->assertStringContainsString('tersedia 2',$response->getSession()->get('error'));
        $this->assertStringContainsString('1GB/3hari',$response->getSession()->get('error'));
        $this->assertStringNotContainsString('denom V1',$response->getSession()->get('error'));
        $this->assertSame(2,DB::table('stockawaltap')->value('stock'));
        $this->assertSame(10,DB::table('stockawalsf')->value('stock'));
        $this->assertSame(1,DB::table('retursf')->count());
        $this->assertSame(0,DB::table('logs')->count());
    }
    public function test_successful_delete_reverses_stock_and_a_retry_does_not_double_reverse(): void
    {
        $controller=app(ReturSfController::class);
        $controller->delete(Request::create('/','POST'),1);
        $controller->delete(Request::create('/','POST'),1);
        $this->assertSame(60,DB::table('stockawaltap')->value('stock'));
        $this->assertSame(50,DB::table('stockawalsf')->value('stock'));
        $this->assertSame(0,DB::table('retursf')->count());
        $this->assertSame(1,DB::table('logs')->count());
    }
    public function test_bulk_failure_rolls_back_all_rows_and_stock(): void
    {
        DB::table('stockawaltap')->update(['stock'=>70]);
        DB::table('retursf')->insert(['idretur'=>2,'idtap'=>'DUMAI','idsf'=>'SF1','iddenom'=>'V1','qty'=>40]);
        $response=app(ReturSfController::class)->bulkDelete(Request::create('/','POST',['ids'=>[1,2]]));
        $this->assertFalse($response->getData()->success);
        $this->assertSame(70,DB::table('stockawaltap')->value('stock'));
        $this->assertSame(10,DB::table('stockawalsf')->value('stock'));
        $this->assertSame(2,DB::table('retursf')->count());
        $this->assertSame(0,DB::table('logs')->count());
    }
    public function test_bulk_counts_actual_deleted_rows_and_ignores_duplicate_ids(): void
    {
        $response=app(ReturSfController::class)->bulkDelete(Request::create('/','POST',['ids'=>[1,1,999]]));
        $this->assertTrue($response->getData()->success);
        $this->assertSame('1 data retur berhasil dihapus',$response->getData()->message);
        $this->assertSame(50,DB::table('stockawalsf')->value('stock'));
    }
    public function test_missing_sf_stock_row_is_created_before_returning_stock(): void
    {
        DB::table('stockawalsf')->delete();
        app(ReturSfController::class)->delete(Request::create('/','POST'),1);
        $this->assertSame(40,DB::table('stockawalsf')->value('stock'));
        $this->assertSame(60,DB::table('stockawaltap')->value('stock'));
    }
    public function test_non_admin_cannot_delete(): void
    {
        $this->actingAs(new User(['id'=>2,'username'=>'asmen_dumai']));
        app(ReturSfController::class)->delete(Request::create('/','POST'),1);
        $this->assertSame(1,DB::table('retursf')->count());
        $this->assertSame(100,DB::table('stockawaltap')->value('stock'));
    }
}
