<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SuryaInventoryDashboardTest extends TestCase
{
    use DatabaseTransactions;

    public static function filters(): array
    {
        return [
            'regular-monthly' => ['sales_product=pv_reg&inject_product=pv_reg&sales_period=monthly&sales_view=table'],
            'byu-chart' => ['sales_product=pv_byu&inject_product=pv_byu&sales_view=chart'],
            'sa-simpati' => ['sales_product=sa_simpati&inject_product=sa_simpati'],
            'sa-byu' => ['sales_product=sa_byu&inject_product=sa_byu'],
            'annual' => ['sales_period=annual&sales_view=chart&sales_annual_cutoff=full'],
        ];
    }

    #[DataProvider('filters')]
    public function test_inventory_and_surya_use_identical_data_and_keep_filter_forms_in_their_workspace(string $query): void
    {
        $admin = User::where('username', 'admin_super')->firstOrFail();
        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI']);
        $inventory = $this->get('/home?'.$query)->assertOk();
        $surya = $this->get('/surya/dashboard?'.$query)->assertOk();

        foreach (['stokSegel', 'stokInject', 'salesBulanIni', 'mom', 'matrixSales', 'matrixInject', 'annualSales', 'salesProductFilter', 'injectProductFilter', 'salesView', 'salesPeriod'] as $key) {
            $this->assertEquals($inventory->viewData($key), $surya->viewData($key), $key);
        }

        foreach (['heatmap-sales-card', 'heatmap-inject-card', 'mom-cluster-card'] as $anchor) {
            $surya->assertSee('action="'.route('surya.inventory-dashboard').'#'.$anchor.'"', false);
            $inventory->assertSee('action="'.url('home').'#'.$anchor.'"', false);
        }
        $surya->assertSee('Dashboard Inventory')->assertSee('Header SURYA')
            ->assertSee('id="pieSales"', false)->assertSee('id="chartSales"', false)
            ->assertSee('id="mom-cluster-card"', false)->assertDontSee('INVENTORY MSP');
        $inventory->assertSee('INVENTORY MSP')->assertDontSee('Header SURYA');
    }

    public function test_dashboard_keeps_cluster_scope(): void
    {
        foreach ([
            ['cluster_dumai', 'CLUSTER_DUMAI', ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING']],
            ['cluster_rohil', 'CLUSTER_ROHIL', ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG']],
        ] as [$username, $scope, $allowed]) {
            $user = User::where('username', $username)->firstOrFail();
            $response = $this->actingAs($user)->withSession(['idtap' => $scope])->get('/surya/dashboard')->assertOk();
            foreach (['matrixSales', 'matrixInject'] as $key) {
                $this->assertEmpty(array_diff(array_keys($response->viewData($key)), $allowed), $key);
            }
            $response->assertDontSee('INPUT TARGET')->assertDontSee('UPLOAD TRANSAKSI');
        }
    }

    public function test_asmen_can_open_the_dashboard_with_the_same_scope_as_home(): void
    {
        $asmen = User::where('username', 'like', 'asmen_%')->first();
        if (! $asmen) $this->markTestSkipped('Fixture ASMEN belum tersedia.');
        $this->actingAs($asmen)->withSession(['idtap' => $asmen->idtap]);
        $inventory = $this->get('/home')->assertOk();
        $surya = $this->get('/surya/dashboard')->assertOk();
        $this->assertEquals($inventory->viewData('matrixSales'), $surya->viewData('matrixSales'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/surya/dashboard')->assertRedirect(route('/'));
    }
}
