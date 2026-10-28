<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use App\Services\SuryaMetricsImport;
use App\Support\SuryaTransactions;

class SuryaTransactionsSplitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_10_17_000004_create_surya_daily_metrics_table.php'))->up();
        (require database_path('migrations/2026_10_22_000002_add_biometric_metrics_to_surya_daily_metrics.php'))->up();
        Schema::table('surya_daily_metrics',fn($t)=>$t->string('uploaded_metrics')->nullable());
    }
    private function split(): void
    {
        (require database_path('migrations/2026_10_28_140000_split_surya_trx_cvm_and_biometrik.php'))->up();
    }
    public function test_migration_preserves_history_and_readers_keep_independent_latest_dates(): void
    {
        DB::table('surya_daily_metrics')->insert([
            ['id_outlet'=>'A','metric_date'=>'2026-10-25','uploaded_metrics'=>'cvm,bio','cvm_m'=>12,'bio_m'=>8],
            ['id_outlet'=>'A','metric_date'=>'2026-10-26','uploaded_metrics'=>'cvm','cvm_m'=>0,'bio_m'=>99],
        ]);
        $this->split();
        $this->assertSame(2,DB::table('surya_trx_cvm')->count());
        $this->assertSame(1,DB::table('surya_biometrik')->count());
        $this->assertSame(2,DB::table('surya_daily_metrics')->count());
        $rows=SuryaTransactions::query()->where('id_outlet','A')->orderByDesc('metric_date')->get();
        $this->assertSame(0,(int)$rows->first(fn($r)=>in_array('cvm',explode(',',$r->uploaded_metrics)))->cvm_m);
        $this->assertSame(8,(int)$rows->first(fn($r)=>in_array('bio',explode(',',$r->uploaded_metrics)))->bio_m);
    }
    public function test_uploads_write_only_their_own_table_and_same_date_replaces_values(): void
    {
        $this->split();
        $import=app(SuryaMetricsImport::class);
        foreach ([['trx_cvm.csv',"id_outlet,cvm_m,ss_m,update\nA,12,7,2026-10-28\n"],['biometrik.csv',"ID Outlet,Bio FM1,Bio M1,Bio MTD\nA,9,8,6\n"],['trx_cvm.csv',"id_outlet,cvm_m,ss_m,update\nA,0,2,2026-10-28\n"]] as [$name,$contents]) {
            $path=tempnam(sys_get_temp_dir(),'split-');file_put_contents($path,$contents);
            try { $meta=$import->inspect($path,$name);$import->importChunk($path,$meta,2); } finally {unlink($path);}
        }
        $this->assertSame(1,DB::table('surya_trx_cvm')->count());
        $this->assertSame(0,(int)DB::table('surya_trx_cvm')->value('cvm_m'));
        $this->assertSame(6,(int)DB::table('surya_biometrik')->value('bio_m'));
        $this->assertSame(0,DB::table('surya_daily_metrics')->count());
    }
    public function test_filename_must_identify_exactly_one_type_and_match_contents(): void
    {
        $import=app(SuryaMetricsImport::class);
        $this->assertSame('cvm',$import->fileKind('TRX CVM Oktober.xlsx'));
        $this->assertSame('biometrik',$import->fileKind('Biometrik_28.xlsx'));
        foreach(['data.xlsx','cvm_biometrik.xlsx'] as $name) {
            try {$import->fileKind($name);$this->fail('Expected rejection');} catch(ValidationException $e){$this->assertArrayHasKey('metrics_file',$e->errors());}
        }
        $path=tempnam(sys_get_temp_dir(),'split-');file_put_contents($path,"ID Outlet,Bio FM1,Bio M1,Bio MTD\nA,9,8,6\n");
        try {$import->inspect($path,'cvm.csv');$this->fail('Wrong name accepted');} catch(ValidationException $e){$this->assertArrayHasKey('metrics_file',$e->errors());} finally {unlink($path);}
    }
}
