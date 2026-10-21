<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuryaOutletListTest extends TestCase
{
    use DatabaseTransactions;

    public function test_surya_transaction_upload_uses_the_shared_workspace_shell(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Fixture admin_super belum tersedia.');
        }

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->get(route('surya.transaction-upload'))
            ->assertOk()
            ->assertSee('id="sidebar-toggle"', false)
            ->assertSee('id="sidebar-close"', false)
            ->assertSee('PETA OUTLET')
            ->assertSee('LIST OUTLET')
            ->assertSee('sidebar-link active')
            ->assertSee('SURYA Leader')
            ->assertSee('Upload Transaksi Outlet');
    }

    public function test_surya_outlet_list_can_update_sf_and_pjp_day(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        $sales = DB::table('idsf')->whereNotNull('idtap')->whereNotNull('namasf')->first();
        if (! $admin || ! $sales) {
            $this->markTestSkipped('Fixture admin dan SF belum tersedia.');
        }

        $idOutlet = 990000001;
        DB::table('appsdumais')->insert([
            'id_outlet' => $idOutlet,
            'nama_outlet' => 'Outlet Uji List',
            'tap' => $sales->idtap,
            'sf' => 'UNMAPPING',
            'pjp' => 'UNMAPPING',
        ]);

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->get(route('surya.outlet-list', ['q' => $idOutlet]))
            ->assertOk()
            ->assertSee('LIST OUTLET')
            ->assertSee('Outlet Uji List')
            ->assertSee('Edit PJP')
            ->assertSee('pagination-summary')
            ->assertDontSee('Showing 1 to')
            ->assertSee('css/surya-sidebar.css', false)
            ->assertSee('surya-sidebar-precollapsed')
            ->assertSee('class="enterprise-sidebar"', false)
            ->assertSee('id="sidebar-toggle"', false)
            ->assertSee('id="sidebar-close"', false)
            ->assertSee('sidebar-collapsed')
            ->assertSee('width:236px', false)
            ->assertSee('SURYA Leader');

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->put(route('surya.outlet-list.update', $idOutlet), [
                'sf' => $sales->namasf,
                'pjp' => 'Rabu',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appsdumais', [
            'id_outlet' => $idOutlet,
            'sf' => $sales->namasf,
            'pjp' => 'Rabu',
        ]);
    }

    public function test_surya_outlet_list_is_hidden_and_forbidden_for_non_super_admins(): void
    {
        $admin = User::whereIn('username', ['gm', 'admin_cluster'])
            ->orWhere('username', 'like', 'asmen\_%')
            ->first();
        if (! $admin) {
            $this->markTestSkipped('Fixture user SURYA non-admin_super belum tersedia.');
        }

        $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get(route('surya.outlet-list'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->view('surya.partials.sidebar', ['activePage' => 'dashboard'])
            ->assertDontSee('LIST OUTLET')
            ->assertDontSee(route('surya.outlet-list'), false);
    }

    public function test_outlet_detail_combines_sales_and_uses_latest_uploaded_metrics(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Fixture admin belum tersedia.');
        }

        DB::table('denom')->insert([
            ['iddenom' => 'TEST-DETAIL-SA', 'denom' => 'Test Detail SA', 'group_name' => 'TEST', 'product_group' => 'SA'],
            ['iddenom' => 'TEST-DETAIL-PV', 'denom' => 'Test Detail PV', 'group_name' => 'TEST', 'product_group' => 'PV'],
        ]);

        $idOutlet = 990000002;
        DB::table('appsdumais')->insert([
            'id_outlet' => $idOutlet,
            'nama_outlet' => 'Outlet Uji Detail',
            'kecamatan' => 'Kecamatan Uji',
            'tap' => 'DUMAI',
        ]);
        DB::table('keluarsf')->insert([
            ['iddenom' => 'TEST-DETAIL-SA', 'idsf' => 'SF-TEST', 'id_outlet' => $idOutlet, 'qty' => 2, 'tgl' => '2026-10-20', 'idtap' => 'DUMAI'],
            ['iddenom' => 'TEST-DETAIL-PV', 'idsf' => 'SF-TEST', 'id_outlet' => $idOutlet, 'qty' => 3, 'tgl' => '2026-10-20', 'idtap' => 'DUMAI'],
        ]);
        DB::table('mobile_penjualan')->insert([
            ['tgl' => '2026-10-20', 'id_outlet' => $idOutlet, 'idtap' => 'DUMAI', 'idsf' => 'SF-TEST', 'iddenom' => 'TEST-DETAIL-SA', 'qty' => 5, 'status' => 'approved'],
            ['tgl' => '2026-10-20', 'id_outlet' => $idOutlet, 'idtap' => 'DUMAI', 'idsf' => 'SF-TEST', 'iddenom' => 'TEST-DETAIL-PV', 'qty' => 11, 'status' => 'pending'],
        ]);
        DB::table('surya_daily_metrics')->insert([
            ['id_outlet' => $idOutlet, 'metric_date' => '2026-10-19', 'cvm_m' => 1, 'ss_m' => 2, 'comsak_m' => 3, 'hot_promo_m' => 4],
            ['id_outlet' => $idOutlet, 'metric_date' => '2026-10-20', 'cvm_m' => 10, 'ss_m' => 20, 'comsak_m' => 30, 'hot_promo_m' => 40],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get(route('surya.outlet-detail', ['q' => $idOutlet]));

        $response->assertOk()
            ->assertSee('DETAIL OUTLET')
            ->assertSee('Outlet Uji Detail')
            ->assertSee('Kecamatan Uji');

        $outlet = $response->viewData('outlets')->first();
        $this->assertSame(7, (int) $outlet->st_sa);
        $this->assertSame(3, (int) $outlet->st_pv);
        $this->assertSame(10, (int) $outlet->cvm);
        $this->assertSame(20, (int) $outlet->super_seru);
        $this->assertSame(30, (int) $outlet->comsak);
        $this->assertSame(40, (int) $outlet->hot_promo);
    }
}
