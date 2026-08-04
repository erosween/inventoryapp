<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unified_stock_page_exposes_non_zero_rows_and_active_denoms_for_each_mode(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $page = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/sisastock')
            ->assertOk()
            ->assertSee('Stock Gudang')
            ->assertSee('REGULER')
            ->assertSee('BYU')
            ->assertSee('SA');

        $this->assertSame(
            ['REGULER', 'BYU', 'SA'],
            array_keys($page->viewData('groups'))
        );

        $groups = $page->viewData('groups');
        $pageContent = $page->getContent();
        $this->assertStringContainsString('"category":"REGULER"', $pageContent);
        $this->assertStringContainsString('"category":"BYU"', $pageContent);
        foreach ($groups as $category => $validityGroups) {
            foreach ($validityGroups as $validity => $denoms) {
                $this->assertNotSame('VOICE', strtoupper((string) $validity));

                foreach ($denoms as $denom) {
                    $injectCategory = strtoupper((string) $denom->kategori_inject);
                    $this->assertNotSame('ROAMAX', $injectCategory);

                    if ($category === 'BYU') {
                        $this->assertSame('BYU', $injectCategory, "Denom {$denom->iddenom} salah masuk header BYU.");
                    } elseif ($category === 'SA') {
                        $this->assertSame('SA', $injectCategory, "Denom {$denom->iddenom} salah masuk header SA.");
                    } else {
                        $this->assertNotContains(
                            $injectCategory,
                            ['BYU', 'SA', 'ROAMAX'],
                            "Denom {$denom->iddenom} salah masuk header REGULER."
                        );
                    }
                }
            }
        }

        $hiddenDenomIds = DB::table('denom')
            ->where(function ($query) {
                $query->whereRaw('UPPER(COALESCE(group_name, ?)) = ?', ['', 'VOICE'])
                    ->orWhereRaw('UPPER(COALESCE(kategori_inject, ?)) = ?', ['', 'ROAMAX']);
            })
            ->pluck('iddenom')
            ->all();

        foreach (['all', 'tap', 'sf'] as $mode) {
            $response = $this->actingAs($admin)
                ->withSession(['idtap' => 'SBP_DUMAI'])
                ->postJson('/sisastock/data', [
                    'mode' => $mode,
                    'date' => now()->toDateString(),
                    'draw' => 1,
                    'start' => 0,
                    'length' => -1,
                ])
                ->assertOk()
                ->assertJsonPath('mode', $mode)
                ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered', 'active_denoms']);

            $this->assertSame(
                [],
                array_values(array_intersect($hiddenDenomIds, $response->json('active_denoms'))),
                "RoaMAX/VOICE masih aktif pada mode {$mode}."
            );

            foreach ($response->json('data') as $row) {
                if ($mode !== 'sf') {
                    $this->assertNotSame(
                        0,
                        (int) $row['grand_total'],
                        "Baris stok nol masih tampil pada mode {$mode}."
                    );
                }
                $this->assertSame(
                    (int) $row['grand_total'],
                    (int) $row['grand_total_end'],
                    "Grand Total paling kanan berbeda pada mode {$mode}."
                );
                foreach ($hiddenDenomIds as $hiddenDenomId) {
                    $this->assertArrayNotHasKey(
                        $hiddenDenomId,
                        $row,
                        "Kolom {$hiddenDenomId} masih terkirim pada mode {$mode}."
                    );
                }

                foreach (['REGULER', 'BYU'] as $summaryCategory) {
                    foreach ($groups[$summaryCategory] ?? [] as $validity => $denoms) {
                        $summaryField = 'summary_' . md5($summaryCategory . '|' . $validity);
                        $expectedTotal = collect($denoms)->sum(
                            fn ($denom) => (int) ($row[$denom->iddenom] ?? 0)
                        );
                        $this->assertArrayHasKey($summaryField, $row);
                        $this->assertSame(
                            $expectedTotal,
                            (int) $row[$summaryField],
                            "Ringkasan {$summaryCategory} {$validity} salah pada mode {$mode}."
                        );
                    }
                }
            }
        }

        $allSfRows = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->postJson('/sisastock/data', [
                'mode' => 'sf',
                'date' => now()->toDateString(),
                'draw' => 1,
                'start' => 0,
                'length' => -1,
            ])
            ->assertOk()
            ->json('data');

        $csv = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/sisastock/export/csv?mode=sf&date=' . now()->toDateString())
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csvContent = file_get_contents($csv->baseResponse->getFile()->getPathname());
        $csvLines = preg_split('/\r\n|\r|\n/', trim($csvContent));
        $this->assertCount(count($allSfRows) + 1, $csvLines, 'CSV tidak memuat seluruh petugas SF.');
        $this->assertGreaterThan(25, count($allSfRows), 'Fixture harus membuktikan export lebih dari satu halaman.');
    }

    public function test_new_sf_with_zero_stock_remains_visible_in_sf_mode(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $tap = DB::table('kodetap')->value('idtap');
        if (! $tap) {
            $this->markTestSkipped('Data TAP belum tersedia.');
        }

        $idsf = 'TEST_ZERO_' . strtoupper(substr(md5((string) microtime(true)), 0, 8));
        DB::table('idsf')->insert([
            'idsf' => $idsf,
            'idtap' => $tap,
            'namasf' => 'DS TEST STOK NOL',
            'login_code' => 'TST000',
            'password' => bcrypt('123'),
        ]);

        $rows = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->postJson('/sisastock/data', [
                'mode' => 'sf',
                'date' => now()->toDateString(),
                'draw' => 1,
                'start' => 0,
                'length' => -1,
            ])
            ->assertOk()
            ->json('data');

        $newSf = collect($rows)->firstWhere('idsf', $idsf);
        $this->assertNotNull($newSf, 'SF baru dengan stok nol tidak tampil di Stock Gudang.');
        $this->assertSame(0, (int) $newSf['grand_total']);
    }

    public function test_stock_date_filter_is_open_and_is_not_clamped_to_today(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $futureDate = now()->addYear()->toDateString();
        $response = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/sisastock?mode=all&date=' . $futureDate)
            ->assertOk()
            ->assertViewHas('date', $futureDate);

        $this->assertStringContainsString('value="' . $futureDate . '"', $response->getContent());
        $this->assertStringNotContainsString('max="' . now()->toDateString() . '"', $response->getContent());
    }

    public function test_stock_movement_end_date_is_open_and_is_not_clamped_to_today(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $endDate = now()->addYear()->toDateString();
        $startDate = now()->addYear()->subDays(13)->toDateString();
        $response = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/stock-journey?start_date=' . $startDate . '&end_date=' . $endDate)
            ->assertOk()
            ->assertViewHas('startDate', $startDate)
            ->assertViewHas('endDate', $endDate);

        $this->assertMatchesRegularExpression(
            '/id="end_date"[^>]*value="' . preg_quote($endDate, '/') . '"[^>]*>/',
            $response->getContent()
        );
        $this->assertDoesNotMatchRegularExpression(
            '/id="end_date"[^>]*max=/',
            $response->getContent()
        );
    }
}
