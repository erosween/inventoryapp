<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StockVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_stock_pages_only_expose_positive_rows_and_denoms_for_current_scope(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        foreach (['/stock', '/stocktap', '/stocksf'] as $path) {
            $response = $this->actingAs($admin)
                ->withSession(['idtap' => 'SBP_DUMAI'])
                ->get($path)
                ->assertOk();

            $data = $response->viewData('data');
            $groups = $response->viewData('groups');
            $expectedSummaryColumns = collect($groups)
                ->flatMap(fn ($validityGroups) => collect($validityGroups)->keys())
                ->filter(fn ($groupName) => str_contains(strtoupper($groupName), 'HARI'))
                ->count();

            $this->assertSame(
                $expectedSummaryColumns,
                substr_count($response->getContent(), 'class="th-main validity-summary-header"'),
                "Jumlah kolom ringkasan validity salah di {$path}."
            );

            foreach ($data as $row) {
                $this->assertGreaterThan(0, (int) $row->grand_total, "Baris stok nol masih tampil di {$path}.");
            }

            foreach ($groups as $voucherType => $validityGroups) {
                $voucherLabel = $voucherType === 'VOUCHER by.U' ? 'BYU' : 'REGULER';
                foreach ($validityGroups as $groupName => $denoms) {
                    $response->assertSee(
                        'data-export-title="TOTAL '.$voucherLabel.' '.$groupName.'"',
                        false
                    );
                    foreach ($denoms as $denom) {
                        $this->assertGreaterThan(
                            0,
                            (int) $data->sum($denom->iddenom),
                            "Denom nol {$denom->iddenom} masih tampil di {$path}."
                        );
                    }
                }
            }
        }
    }
}
