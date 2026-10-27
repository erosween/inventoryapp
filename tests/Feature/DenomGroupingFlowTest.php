<?php

namespace Tests\Feature;

use App\Http\Controllers\FormInjectbyuController;
use App\Http\Controllers\FormInjectsegelController;
use App\Http\Controllers\InputDOController;
use App\Support\DenomGrouping;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DenomGroupingFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite', 'database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        foreach (['denom','kodetap','kategori_bo','stockawaltap','stockawalsf','injectvf','masuk'] as $name) {
            Schema::create($name, function (Blueprint $t) use ($name) {
                $t->increments('id');
                if (in_array($name, ['denom','stockawaltap','stockawalsf','injectvf','masuk'])) $t->string('iddenom')->nullable();
                if ($name === 'denom') {
                    foreach (['denom','group_name','kategori_inject','product_group','product','product_variant','stock_form','inject_source_id'] as $c) $t->string($c)->nullable();
                    $t->boolean('allow_do')->default(false);
                }
                if ($name !== 'denom') $t->string('idtap')->nullable();
                if ($name === 'kategori_bo') $t->string('namabo');
                if (str_starts_with($name, 'stockawal')) { $t->integer('stock')->default(0); $t->string('idsf')->nullable(); }
                if (in_array($name, ['injectvf','masuk'])) {
                    $t->integer('qty'); $t->string('tgl'); $t->string('sn')->nullable();
                    if ($name === 'injectvf') $t->string('kategori');
                    else foreach (['pengirim','penerima','nomor_do','week','idtappenerima'] as $c) $t->string($c)->nullable();
                }
            });
        }
        DB::table('kodetap')->insert(['idtap'=>'TEST TAP']);
        DB::table('kategori_bo')->insert(['idtap'=>'TEST TAP','namabo'=>'TEST BO']);
        foreach ([['SEGEL','SEGEL','SEGEL'],['V33','BYU SEGEL','BYU'],['SP6','PERDANA SEGEL SIMPATI','SA'],['SP7','PERDANA SEGEL BYU','SA'],['SP1','SA 3GB','SA'],['SP5','SA BYU 3GB','SA'],['V1','PV 1GB','SEGEL'],['V34','BYU 2GB','BYU']] as [$id,$name,$category]) {
            $base=['iddenom'=>$id,'denom'=>$name,'kategori_inject'=>$category,'group_name'=>in_array($id,DenomGrouping::SOURCES)?'SEGEL':'3 HARI','product_group'=>str_starts_with($id,'SP')?'SP':'PV'];
            DB::table('denom')->insert($base + DenomGrouping::infer((object) $base));
            DB::table('stockawaltap')->insert(['idtap'=>'TEST TAP','iddenom'=>$id,'stock'=>100]);
        }
        session(['idtap'=>'TEST TAP']);
    }

    public function test_source_selection_includes_sp1_only_under_sp6(): void
    {
        self::assertSame(['SP1'], DenomGrouping::destinations(DB::table('denom'), 'SP6')->pluck('iddenom')->all());
        self::assertSame(['SP5'], DenomGrouping::destinations(DB::table('denom'), 'SP7')->pluck('iddenom')->all());
        self::assertSame(['V1'], DenomGrouping::destinations(DB::table('denom'), 'SEGEL')->pluck('iddenom')->all());
        self::assertSame(['V34'], DenomGrouping::destinations(DB::table('denom'), 'V33')->pluck('iddenom')->all());
    }

    public function test_do_allows_sp1_without_consuming_sp6(): void
    {
        app(InputDOController::class)->masukProses($this->doRequest('SP1'));
        self::assertSame(107, (int) DB::table('stockawaltap')->where('iddenom','SP1')->value('stock'));
        self::assertSame(100, (int) DB::table('stockawaltap')->where('iddenom','SP6')->value('stock'));
    }

    public function test_do_rejects_other_finished_denoms(): void
    {
        $this->expectException(ValidationException::class);
        app(InputDOController::class)->masukProses($this->doRequest('V1'));
    }

    public function test_inject_consumes_correct_source_and_rejects_cross_product(): void
    {
        app(FormInjectsegelController::class)->injectProses($this->injectRequest('SP6','SP1'));
        self::assertSame(93, (int) DB::table('stockawaltap')->where('iddenom','SP6')->value('stock'));
        self::assertSame(107, (int) DB::table('stockawaltap')->where('iddenom','SP1')->value('stock'));
        self::assertSame('SP6', DB::table('injectvf')->value('kategori'));
        try {
            app(FormInjectsegelController::class)->injectProses($this->injectRequest('SP7','SP1'));
            self::fail('SP1 must not consume SP7');
        } catch (ValidationException $e) {
            self::assertSame(100, (int) DB::table('stockawaltap')->where('iddenom','SP7')->value('stock'));
            self::assertSame(1, DB::table('injectvf')->count());
        }
    }

    public function test_byu_controller_cannot_inject_sa_or_a_sealed_destination(): void
    {
        app(FormInjectbyuController::class)->injectProses($this->injectRequest('V33','V34'));
        self::assertSame(93, (int) DB::table('stockawaltap')->where('iddenom','V33')->value('stock'));
        $this->expectException(ValidationException::class);
        app(FormInjectbyuController::class)->injectProses($this->injectRequest('V33','SP7'));
    }

    public function test_filters_partition_pv_and_sa_without_losing_sp1(): void
    {
        $all=DB::table('denom')->count();
        $pv=DenomGrouping::filterDenoms(DB::table('denom'),'pv_all')->pluck('iddenom');
        $sa=DenomGrouping::filterDenoms(DB::table('denom'),'sa_all')->pluck('iddenom');
        self::assertCount(0,$pv->intersect($sa));
        self::assertSame($all,$pv->count()+$sa->count());
        self::assertTrue($sa->contains('SP1'));
        self::assertFalse($pv->contains('SP7'));
    }

    private function doRequest(string $code): Request
    {
        return new Request(['kategorisegel'=>$code,'penerima'=>'TEST BO','qty'=>7,'tgl'=>now()->toDateString(),'nomordo'=>'TEST-DO','week'=>'W1','idtap'=>'TEST TAP']);
    }

    private function injectRequest(string $source, string $dest): Request
    {
        return new Request(['category'=>$source,'idtap'=>'TEST TAP','tgl'=>now()->toDateString(),'items'=>[['iddenom'=>$dest,'qty'=>7,'sn'=>'TEST-SN']]]);
    }
}
