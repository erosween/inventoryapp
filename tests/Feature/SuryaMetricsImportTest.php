<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SuryaMetricsImport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class SuryaMetricsImportTest extends TestCase
{
    use DatabaseTransactions;

    private function signIn(): User
    {
        $admin = User::where('username', 'admin_super')->firstOrFail();
        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI']);
        Storage::fake('local');
        return $admin;
    }

    private function file(array $rows, bool $secondSheet = false): UploadedFile
    {
        $book = new Spreadsheet();
        $sheet = $secondSheet ? $book->createSheet() : $book->getActiveSheet();
        if ($secondSheet) {
            $book->getSheet(0)->setCellValue('A1', 'Not the selected worksheet');
            $sheet->setTitle('Transactions');
            $book->setActiveSheetIndex(1);
        }
        $sheet->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'surya-metrics-');
        IOFactory::createWriter($book, 'Xlsx')->save($path);
        $book->disconnectWorksheets();
        $file = new UploadedFile($path, 'transactions.xlsx', null, null, true);
        $this->beforeApplicationDestroyed(fn () => @unlink($path));
        return $file;
    }

    public function test_large_upload_is_bounded_bulk_and_retries_do_not_advance_an_extra_batch(): void
    {
        $this->signIn();
        $rows = [['id_outlet', 'cvm_m', 'ss_m', 'comsak_m', 'update']];
        for ($i = 1; $i <= 1005; $i++) {
            $rows[] = ['BATCH-OUTLET-'.$i, $i, 5, 9, '2026-10-26'];
        }
        $queries = 0;
        DB::listen(function ($query) use (&$queries) {
            if (str_contains($query->sql, 'surya_daily_metrics')) $queries++;
        });
        $state = $this->post(route('surya.transaction-upload.store', ['staged' => 1]),
            ['metrics_file' => $this->file($rows, true)], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('next_row', 2)->assertJsonPath('imported', 0)->json();
        $this->assertSame(0, $queries, 'Receiving a file must not synchronously import all rows.');
        $url = route('surya.transaction-upload.process', ['upload' => $state['id']]);
        $this->postJson($url, ['next_row' => 2])->assertOk()
            ->assertJsonPath('next_row', 1002)->assertJsonPath('imported', 1000)->assertJsonPath('status', 'processing');
        $this->assertLessThanOrEqual(3, $queries, 'One lookup and at most two bulk upserts per 1000-row chunk.');
        $this->assertDatabaseMissing('surya_daily_metrics', ['id_outlet' => 'BATCH-OUTLET-1005']);
        $this->postJson($url, ['next_row' => 2])->assertOk()->assertJsonPath('next_row', 1002)->assertJsonPath('imported', 1000);
        $this->postJson($url)->assertOk()->assertJsonPath('imported', 1000); // Status-only resume.
        $this->postJson($url, ['next_row' => 1002])->assertOk()
            ->assertJsonPath('imported', 1005)->assertJsonPath('status', 'complete');
        $this->postJson($url, ['next_row' => 1002])->assertOk()->assertJsonPath('imported', 1005);
        $this->assertDatabaseHas('surya_daily_metrics', [
            'id_outlet' => 'BATCH-OUTLET-1005', 'cvm_m' => 1005, 'ss_m' => 5, 'comsak_m' => 9,
            'uploaded_metrics' => 'ss,cvm,comsak',
        ]);
        Storage::disk('local')->assertMissing('surya-metric-imports/'.$state['id'].'/source.xlsx');
    }

    public function test_four_named_kpi_columns_are_not_mistaken_for_biometric_data(): void
    {
        $this->signIn();
        $this->post(route('surya.transaction-upload.store'), ['metrics_file' => $this->file([
            ['id_outlet', 'cvm_fm1', 'cvm_m1', 'cvm_m'],
            ['FOUR-COLUMN-KPI', 12, 8, 10],
        ])])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('surya_daily_metrics', [
            'id_outlet' => 'FOUR-COLUMN-KPI', 'cvm_m' => 10, 'bio_m' => 0, 'uploaded_metrics' => 'cvm',
        ]);
    }

    public function test_upload_preserves_created_at_other_kpis_and_deduplicates_outlets_within_a_batch(): void
    {
        $this->signIn();
        DB::table('surya_daily_metrics')->insert([
            'id_outlet' => 'PRESERVE-KPI', 'metric_date' => '2026-10-26', 'ss_m' => 27,
            'uploaded_metrics' => 'ss', 'created_at' => '2026-10-01 00:00:00', 'updated_at' => now(),
        ]);
        $this->post(route('surya.transaction-upload.store'), ['metrics_file' => $this->file([
            ['id_outlet', 'cvm_m', 'comsak_m', 'update'],
            ['PRESERVE-KPI', 10, 5, '2026-10-26'],
            ['PRESERVE-KPI', 15, 0, '2026-10-26'],
        ])])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('surya_daily_metrics', [
            'id_outlet' => 'PRESERVE-KPI', 'metric_date' => '2026-10-26', 'ss_m' => 27, 'cvm_m' => 15,
            'comsak_m' => 0, 'uploaded_metrics' => 'ss,cvm,comsak', 'created_at' => '2026-10-01 00:00:00',
        ]);
        $this->assertSame(1, DB::table('surya_daily_metrics')->where('id_outlet', 'PRESERVE-KPI')->count());
    }

    public function test_another_owner_cannot_inspect_or_process_a_stored_upload(): void
    {
        $admin = $this->signIn();
        $state = app(SuryaMetricsImport::class)->start($this->file([
            ['id_outlet', 'cvm_m'], ['PRIVATE-UPLOAD', 9],
        ]), $admin->id + 100000);
        $url = route('surya.transaction-upload.process', ['upload' => $state['id']]);
        $this->postJson($url)->assertForbidden();
        $this->postJson($url, ['next_row' => 2])->assertForbidden();
        $this->assertDatabaseMissing('surya_daily_metrics', ['id_outlet' => 'PRIVATE-UPLOAD']);
    }

    public function test_failed_batch_can_be_corrected_and_retried_without_losing_prior_progress(): void
    {
        $this->signIn();
        $state = $this->post(route('surya.transaction-upload.store', ['staged' => 1]),
            ['metrics_file' => $this->file([['id_outlet', 'cvm_m'], ['RETRY-BATCH', 7]])],
            ['Accept' => 'application/json'])->assertCreated()->json();
        $url = route('surya.transaction-upload.process', ['upload' => $state['id']]);
        $path = Storage::disk('local')->path('surya-metric-imports/'.$state['id'].'/source.xlsx');
        $contents = file_get_contents($path);
        file_put_contents($path, 'unreadable');
        $this->postJson($url, ['next_row' => 2])->assertStatus(500);
        $this->postJson($url)->assertOk()->assertJsonPath('next_row', 2)->assertJsonPath('imported', 0);
        file_put_contents($path, $contents);
        $this->postJson($url, ['next_row' => 2])->assertOk()->assertJsonPath('imported', 1)->assertJsonPath('status', 'complete');
    }

    public function test_cancelling_a_paused_upload_removes_the_source_and_prevents_further_batches(): void
    {
        $admin = $this->signIn();
        $state = app(SuryaMetricsImport::class)->start($this->file([
            ['id_outlet', 'cvm_m'], ['CANCELLED-UPLOAD', 9],
        ]), $admin->id);
        $url = route('surya.transaction-upload.process', ['upload' => $state['id']]);
        $this->postJson($url, ['cancel' => true])->assertOk()->assertJsonPath('status', 'cancelled');
        $this->postJson($url, ['next_row' => 2])->assertOk()->assertJsonPath('imported', 0)->assertJsonPath('status', 'cancelled');
        Storage::disk('local')->assertMissing('surya-metric-imports/'.$state['id'].'/source.xlsx');
        $this->assertDatabaseMissing('surya_daily_metrics', ['id_outlet' => 'CANCELLED-UPLOAD']);
    }

    public function test_csv_import_and_unrecognized_headers(): void
    {
        $this->signIn();
        $path = tempnam(sys_get_temp_dir(), 'surya-csv-');
        try {
            file_put_contents($path, "id_outlet,cvm_m,ss_m,comsak_m,update\nCSV-OUTLET,10,20,30,2026-10-26\n");
            $this->post(route('surya.transaction-upload.store'), [
                'metrics_file' => new UploadedFile($path, 'metrics.csv', 'text/csv', null, true),
            ])->assertRedirect()->assertSessionHas('success');
            $this->assertDatabaseHas('surya_daily_metrics', ['id_outlet' => 'CSV-OUTLET', 'cvm_m' => 10, 'ss_m' => 20, 'comsak_m' => 30]);
        } finally { @unlink($path); }
        $this->post(route('surya.transaction-upload.store', ['staged' => 1]), [
            'metrics_file' => $this->file([['not', 'a', 'recognized', 'metrics', 'file'], ['OUTLET', 1, 2, 3, 4]]),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('metrics_file');
    }
}

