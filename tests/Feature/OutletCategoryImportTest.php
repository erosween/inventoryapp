<?php
namespace Tests\Feature;

use App\Services\OutletCategoryImport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OutletCategoryImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);
        DB::purge('sqlite');
        Schema::create('appsdumais', function (Blueprint $t) {
            $t->unsignedBigInteger('id_outlet')->primary();
            $t->string('kategori_outlet')->default('Reguler');
            $t->string('nama_outlet');
        });
        DB::table('appsdumais')->insert([
            ['id_outlet'=>1200000001,'nama_outlet'=>'A','kategori_outlet'=>'Top 20 TAP'],
            ['id_outlet'=>1200000002,'nama_outlet'=>'B','kategori_outlet'=>'Reguler'],
        ]);
    }

    public function test_latest_list_replaces_categories_without_changing_master_fields(): void
    {
        $file = UploadedFile::fake()->createWithContent('kategori.csv', "\xEF\xBB\xBFid_outlet;kategori_outlet\n1200000002;top 20 tap\n");
        $this->assertSame(1, app(OutletCategoryImport::class)->import($file));
        $this->assertSame(['Reguler','Top 20 TAP'], DB::table('appsdumais')->orderBy('id_outlet')->pluck('kategori_outlet')->all());
        $this->assertSame(['A','B'], DB::table('appsdumais')->orderBy('id_outlet')->pluck('nama_outlet')->all());
        DB::table('appsdumais')->insert(['id_outlet'=>1200000003,'nama_outlet'=>'C']);
        $this->assertSame('Reguler', DB::table('appsdumais')->where('id_outlet',1200000003)->value('kategori_outlet'));
    }

    public function test_invalid_upload_never_resets_existing_categories(): void
    {
        foreach ([
            "id_outlet,kategori_outlet\n",
            "id_outlet,kategori_outlet\n1200000099,Top 20 TAP\n",
            "id_outlet,kategori_outlet\n1200000002,Top 20 TAP\n1200000002,Top 20 TAP\n",
            "id_outlet,kategori_outlet\n1200000002,Invalid\n",
            "id_outlet,kategori_outlet\n1.2E9,Top 20 TAP\n",
        ] as $csv) {
            try {
                app(OutletCategoryImport::class)->import(UploadedFile::fake()->createWithContent('kategori.csv', $csv));
                $this->fail('Invalid file accepted');
            } catch (ValidationException $e) { $this->assertArrayHasKey('category_file', $e->errors()); }
            $this->assertSame(['Top 20 TAP','Reguler'], DB::table('appsdumais')->orderBy('id_outlet')->pluck('kategori_outlet')->all());
        }
    }

    public function test_uploaded_top_20_outlet_tap_label_is_normalized(): void
    {
        $file = UploadedFile::fake()->createWithContent('TOP 20.csv', "id_outlet,kategori_outlet\n1200000002,TOP 20 OUTLET TAP\n");
        $this->assertSame(1, app(OutletCategoryImport::class)->import($file));
        $this->assertSame('Top 20 TAP', DB::table('appsdumais')->where('id_outlet',1200000002)->value('kategori_outlet'));
    }
}
