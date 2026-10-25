<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\TestCase;

class SuryaStockScopeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pv_validities_are_ordered_by_duration_regardless_of_target_insertion_order(): void
    {
        $admin = User::where('username', 'admin_super')->firstOrFail();
        DB::table('idsf')->insert(['idsf' => 'SF-VALIDITY-ORDER', 'namasf' => 'VALIDITY ORDER TEST', 'idtap' => 'DUMAI']);
        foreach ([28, 7, 1, 5, 3, 2] as $days) {
            DB::table('surya_sf_targets')->insert([
                'idsf' => 'SF-VALIDITY-ORDER', 'product_family' => 'PV', 'validity' => $days.' HARI',
                'target_qty' => $days * 100, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($admin)->withSession(['idtap' => $admin->idtap])
            ->get(route('surya.stock-on-hand', ['family' => 'PV']))->assertOk();
        $items = $response->viewData('rows')->firstWhere('idsf', 'SF-VALIDITY-ORDER')['items'];
        $this->assertSame(['1 HARI', '2 HARI', '3 HARI', '5 HARI', '7 HARI', '28 HARI'], $items->pluck('validity')->all());
        $this->assertSame([100, 200, 300, 500, 700, 2800], $items->pluck('target')->all());
    }

    #[DataProviderExternal(SuryaOutletScopeTest::class, 'users')]
    public function test_stock_taps_and_sales_follow_detail_outlet_scope_for_both_product_tabs(string $username, array $allowedTaps): void
    {
        $allTaps = ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING', 'BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'];
        foreach ($allTaps as $index => $tap) {
            $idsf = 'SF-TAP-SCOPE-'.$index;
            DB::table('idsf')->insert(['idsf' => $idsf, 'namasf' => 'SALES SCOPE '.$index, 'idtap' => $tap]);
            DB::table('appsdumais')->insert([
                'id_outlet' => 990009001 + $index, 'nama_outlet' => 'Stock Scope '.$tap,
                'tap' => $tap, 'sf' => 'SALES SCOPE '.$index, 'pjp' => 'Senin',
            ]);
            foreach (['PV' => '1 HARI', 'SA' => '3GB'] as $family => $validity) {
                DB::table('surya_sf_targets')->insert([
                    'idsf' => $idsf, 'product_family' => $family, 'validity' => $validity,
                    'target_qty' => 100, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
        $user = User::where('username', $username)->firstOrFail();
        $this->actingAs($user)->withSession(['idtap' => $user->idtap]);
        $outlet = $this->get(route('surya.outlet-detail'))->assertOk();

        foreach (['PV', 'SA'] as $family) {
            $response = $this->get(route('surya.stock-on-hand', ['family' => $family]))->assertOk();
            $this->assertEqualsCanonicalizing($outlet->viewData('taps')->all(), $response->viewData('taps')->all());
            $rows = $response->viewData('rows');
            $this->assertEmpty($rows->pluck('idtap')->diff($allowedTaps)->all());
            $fixtureRows = $rows->filter(fn ($row) => str_starts_with($row['idsf'], 'SF-TAP-SCOPE-'));
            $this->assertEqualsCanonicalizing($allowedTaps, $fixtureRows->pluck('idtap')->all());
            $response->assertSee('aria-label="Filter TAP"', false)->assertSee('PV REGULER')->assertSee('>SA</a>', false);

            $tap = $allowedTaps[0];
            $query = ['family' => $family, 'tap' => $tap, 'q' => 'SALES SCOPE'];
            $filtered = $this->get(route('surya.stock-on-hand', $query))->assertOk()
                ->assertViewHas('selectedTap', $tap)->assertViewHas('search', 'SALES SCOPE');
            $this->assertSame([$tap], $filtered->viewData('rows')->pluck('idtap')->unique()->values()->all());
            $otherFamily = $family === 'PV' ? 'SA' : 'PV';
            $filtered->assertSee(route('surya.stock-on-hand', array_merge($query, ['family' => $otherFamily])));

            $forbiddenTap = collect($allTaps)->diff($allowedTaps)->first();
            if ($forbiddenTap !== null) {
                $this->get(route('surya.stock-on-hand', ['family' => $family, 'tap' => $forbiddenTap]))
                    ->assertOk()->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
            }
        }
    }
}
