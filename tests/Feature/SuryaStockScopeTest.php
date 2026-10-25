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
