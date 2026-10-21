<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            ['iddenom' => 'TEST-DETAIL-SA', 'denom' => 'Test Detail SA', 'group_name' => 'TEST', 'product_group' => 'SP'],
            ['iddenom' => 'TEST-DETAIL-PV', 'denom' => 'Test Detail PV', 'group_name' => 'TEST', 'product_group' => 'PV'],
        ]);

        $idOutlet = 990000002;
        DB::table('appsdumais')->insert([
            'id_outlet' => $idOutlet,
            'nama_outlet' => 'Outlet Uji Detail',
            'kecamatan' => 'Kecamatan Uji',
            'tap' => 'DUMAI',
            'sf' => 'SF UJI DETAIL',
            'pjp' => 'Senin',
        ]);
        DB::table('appsdumais')->insert([
            'id_outlet' => 990000003,
            'nama_outlet' => 'Outlet Uji Unmapping',
            'kecamatan' => 'Kecamatan Uji',
            'tap' => 'DUMAI',
            'pjp' => 'UNMAPPING',
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
            ['id_outlet' => $idOutlet, 'metric_date' => '2026-10-19', 'cvm_fm1' => 0, 'cvm_m1' => 0, 'cvm_m' => 1, 'ss_fm1' => 0, 'ss_m1' => 0, 'ss_m' => 2, 'comsak_fm1' => 0, 'comsak_m1' => 0, 'comsak_m' => 3, 'hot_promo_fm1' => 0, 'hot_promo_m1' => 0, 'hot_promo_m' => 4],
            ['id_outlet' => $idOutlet, 'metric_date' => '2026-10-20', 'cvm_fm1' => 8, 'cvm_m1' => 9, 'cvm_m' => 10, 'ss_fm1' => 18, 'ss_m1' => 19, 'ss_m' => 20, 'comsak_fm1' => 28, 'comsak_m1' => 29, 'comsak_m' => 30, 'hot_promo_fm1' => 38, 'hot_promo_m1' => 39, 'hot_promo_m' => 40],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get(route('surya.outlet-detail'));

        $response->assertOk()
            ->assertSee('DETAIL OUTLET')
            ->assertSee('Gap MoM')
            ->assertSee('%MoM')
            ->assertSee('serverSide:true', false)
            ->assertSee('ordering:false', false)
            ->assertSee('Export Excel')
            ->assertSee('position:sticky;top:50px', false)
            ->assertSee('position:sticky;left:130px', false)
            ->assertSee('max-height:clamp(600px', false)
            ->assertSee("document.querySelector('.card').appendChild(footer)", false)
            ->assertSee('detail-outlet\\/data', false);

        $dataResponse = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->getJson(route('surya.outlet-detail.data', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'search' => ['value' => 'Outlet Uji'],
                'columns' => [['data' => 'nama_outlet']],
                'order' => [['column' => 0, 'dir' => 'asc']],
            ]));

        $dataResponse->assertOk()->assertJsonCount(1, 'data');
        $outlet = (object) $dataResponse->json('data.0');
        $this->assertSame('Outlet Uji Detail', $outlet->nama_outlet);
        $this->assertSame('SF UJI DETAIL', $outlet->sf);
        $this->assertSame(7, (int) $outlet->st_sa_mtd);
        $this->assertSame(3, (int) $outlet->st_pv_mtd);
        $this->assertNull($outlet->st_sa_fm1);
        $this->assertNull($outlet->st_pv_m1);
        $this->assertSame(8, (int) $outlet->cvm_fm1);
        $this->assertSame(9, (int) $outlet->cvm_m1);
        $this->assertSame(10, (int) $outlet->cvm_mtd);
        $this->assertSame(1, (int) $outlet->cvm_gap);
        $this->assertEqualsWithDelta(11.111, (float) $outlet->cvm_mom_pct, 0.01);
        $this->assertSame(20, (int) $outlet->super_seru_mtd);
        $this->assertSame(30, (int) $outlet->comsak_mtd);
        $this->assertSame(40, (int) $outlet->hot_promo_mtd);

        $exportResponse = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get(route('surya.outlet-detail.export', ['q' => 'Outlet Uji Detail']))
            ->assertOk()
            ->assertHeader('content-disposition');

        $sheet = IOFactory::load($exportResponse->baseResponse->getFile()->getPathname())->getActiveSheet();

        $this->assertSame('Detail Outlet', $sheet->getTitle());
        $this->assertSame('AJ', $sheet->getHighestColumn());
        $this->assertSame(2, $sheet->getHighestRow());
        $this->assertSame('ID Outlet', $sheet->getCell('A1')->getValue());
        $this->assertSame('Hot Promo %MoM', $sheet->getCell('AJ1')->getValue());
        $this->assertSame((string) $idOutlet, (string) $sheet->getCell('A2')->getValue());
        $this->assertSame('Outlet Uji Detail', $sheet->getCell('B2')->getValue());
        $this->assertSame(7, (int) $sheet->getCell('I2')->getValue());
        $this->assertSame(10, (int) $sheet->getCell('S2')->getValue());
    }
}
