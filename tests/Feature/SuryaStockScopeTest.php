<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SuryaStockScopeTest extends TestCase
{
    use DatabaseTransactions;

    public static function productFamilies(): array
    {
        return ['PV reguler' => ['PV', '1 HARI', 'PV'], 'SA' => ['SA', '3GB', 'SA']];
    }

    #[DataProvider('productFamilies')]
    public function test_stock_status_uses_daily_gap_and_shows_buffer_and_achievement_details(string $family, string $validity, string $category): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-26 12:00:00', 'Asia/Jakarta'));
        $denom = 'TEST-STATUS-'.$family;
        DB::table('denom')->insert([
            'iddenom' => $denom, 'denom' => $family === 'SA' ? 'SA 3GB' : 'PV STATUS',
            'group_name' => $validity, 'kategori_inject' => $category,
        ]);
        foreach ([80, 100, 200, 300, 400, 200] as $index => $stock) {
            $idsf = 'SF-STATUS-'.$family.'-'.$index;
            DB::table('idsf')->insert(['idsf' => $idsf, 'namasf' => 'STATUS SALES '.$index, 'idtap' => 'DUMAI']);
            DB::table('surya_sf_targets')->insert([
                'idsf' => $idsf, 'product_family' => $family, 'validity' => $validity,
                'target_qty' => 500, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('stockawalsf')->insert(['idsf' => $idsf, 'iddenom' => $denom, 'stock' => $stock]);
            if ($index === 5) {
                DB::table('keluarsf')->insert(['idsf' => $idsf, 'iddenom' => $denom, 'qty' => 500, 'tgl' => now(), 'idtap' => 'DUMAI']);
            }
        }
        $admin = User::where('username', 'admin_super')->firstOrFail();
        $response = $this->actingAs($admin)->withSession(['idtap' => $admin->idtap])
            ->get(route('surya.stock-on-hand', ['family' => $family]))->assertOk()
            ->assertSee('Keterangan status stok')->assertSee('STOCK OVER')->assertSee('CUKUP 2 HARI')
            ->assertSee('KURANG 20 PCS')->assertSee('TAHAN PENAMBAHAN')->assertSee('TARGET TERCAPAI · SISA STOK 200 PCS');
        $rows = $response->viewData('rows')->keyBy('idsf');
        $expected = ['Stock Kurang', 'Stock Aman', 'Stock Aman', 'Stock Aman', 'Stock Over', 'Stock Aman'];
        foreach ($expected as $index => $status) {
            $item = $rows['SF-STATUS-'.$family.'-'.$index]['items']->first();
            $this->assertSame($status, $item['status']);
            $this->assertSame($index === 5 ? 0 : 100, $item['daily']);
            $this->assertSame($index === 0 ? 20 : 0, $item['recommendation']);
        }
        $this->travelBack();
    }

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
