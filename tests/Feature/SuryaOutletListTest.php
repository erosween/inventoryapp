<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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
            ->assertDontSee('LIST OUTLET')
            ->assertSee('sidebar-link active')
            ->assertSee('SURYA Leader')
            ->assertSee('Upload Transaksi Outlet')
            ->assertSee('Upload historis SA/PV')
            ->assertSee('Nama header bebas');
    }

    public function test_admin_super_can_update_outlet_profile_from_detail_outlet(): void
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
            ->get(route('surya.outlet-detail.profile', $idOutlet))
            ->assertOk()
            ->assertJsonPath('outlet.nama_outlet', 'Outlet Uji List')
            ->assertJsonPath('outlet.pjp', 'UNMAPPING');

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->putJson(route('surya.outlet-detail.profile.update', $idOutlet), [
                'sf' => $sales->namasf,
                'pjp' => 'Rabu',
            ])
            ->assertOk()
            ->assertJsonPath('message', "Profil outlet {$idOutlet} berhasil diperbarui.");

        $this->assertDatabaseHas('appsdumais', [
            'id_outlet' => $idOutlet,
            'sf' => $sales->namasf,
            'pjp' => 'Rabu',
        ]);
    }

    public function test_outlet_list_is_removed_and_profile_edit_is_forbidden_for_non_super_admins(): void
    {
        $admin = User::whereIn('username', ['gm', 'admin_cluster'])
            ->orWhere('username', 'like', 'asmen\_%')
            ->first();
        if (! $admin) {
            $this->markTestSkipped('Fixture user SURYA non-admin_super belum tersedia.');
        }

        $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/surya/list-outlet')
            ->assertNotFound();

        $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get(route('surya.outlet-detail.profile', 1))
            ->assertForbidden();

        $this->actingAs($admin)
            ->view('surya.partials.sidebar', ['activePage' => 'dashboard'])
            ->assertDontSee('LIST OUTLET');
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
            ['iddenom' => 'TEST-DETAIL-SA', 'idsf' => 'SF-TEST', 'id_outlet' => $idOutlet, 'qty' => 500, 'tgl' => '2026-10-22', 'idtap' => 'DUMAI'],
            ['iddenom' => 'TEST-DETAIL-PV', 'idsf' => 'SF-TEST', 'id_outlet' => $idOutlet, 'qty' => 90, 'tgl' => '2026-09-20', 'idtap' => 'DUMAI'],
        ]);
        DB::table('mobile_penjualan')->insert([
            ['tgl' => '2026-10-20', 'id_outlet' => $idOutlet, 'idtap' => 'DUMAI', 'idsf' => 'SF-TEST', 'iddenom' => 'TEST-DETAIL-SA', 'qty' => 5, 'status' => 'approved'],
            ['tgl' => '2026-10-22', 'id_outlet' => $idOutlet, 'idtap' => 'DUMAI', 'idsf' => 'SF-TEST', 'iddenom' => 'TEST-DETAIL-PV', 'qty' => 700, 'status' => 'approved'],
            ['tgl' => '2026-10-20', 'id_outlet' => $idOutlet, 'idtap' => 'DUMAI', 'idsf' => 'SF-TEST', 'iddenom' => 'TEST-DETAIL-PV', 'qty' => 11, 'status' => 'pending'],
            ['tgl' => '2026-09-20', 'id_outlet' => $idOutlet, 'idtap' => 'DUMAI', 'idsf' => 'SF-TEST', 'iddenom' => 'TEST-DETAIL-SA', 'qty' => 50, 'status' => 'approved'],
        ]);
        DB::table('surya_daily_metrics')->insert([
            ['id_outlet' => $idOutlet, 'metric_date' => '2026-10-19', 'cvm_fm1' => 0, 'cvm_m1' => 0, 'cvm_m' => 1, 'ss_fm1' => 0, 'ss_m1' => 0, 'ss_m' => 2, 'comsak_fm1' => 0, 'comsak_m1' => 0, 'comsak_m' => 3, 'hot_promo_fm1' => 0, 'hot_promo_m1' => 0, 'hot_promo_m' => 4, 'bio_fm1' => 0, 'bio_m1' => 0, 'bio_m' => 0],
            ['id_outlet' => $idOutlet, 'metric_date' => '2026-10-20', 'cvm_fm1' => 8, 'cvm_m1' => 9, 'cvm_m' => 10, 'ss_fm1' => 18, 'ss_m1' => 19, 'ss_m' => 20, 'comsak_fm1' => 28, 'comsak_m1' => 29, 'comsak_m' => 30, 'hot_promo_fm1' => 38, 'hot_promo_m1' => 39, 'hot_promo_m' => 40, 'bio_fm1' => 48, 'bio_m1' => 49, 'bio_m' => 50],
        ]);
        DB::table('surya_outlet_sales_history')->insert([
            ['transaction_date' => '2026-09-01', 'id_outlet' => $idOutlet, 'sa' => 4, 'pv' => 6],
            ['transaction_date' => '2026-09-21', 'id_outlet' => $idOutlet, 'sa' => 2, 'pv' => 3],
            ['transaction_date' => '2026-09-22', 'id_outlet' => $idOutlet, 'sa' => 10, 'pv' => 20],
            ['transaction_date' => '2026-09-30', 'id_outlet' => $idOutlet, 'sa' => 1, 'pv' => 2],
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
            ->assertSee('id="sales-cutoff"', false)
            ->assertSee('value="2026-10-20"', false)
            ->assertSee('id="profile-modal"', false)
            ->assertSee('outlet-profile-link', false)
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
        $this->assertSame(17, (int) $outlet->st_sa_fm1);
        $this->assertSame(4, (int) $outlet->st_sa_m1);
        $this->assertSame(31, (int) $outlet->st_pv_fm1);
        $this->assertSame(6, (int) $outlet->st_pv_m1);
        $this->assertSame(3, (int) $outlet->st_sa_gap);
        $this->assertSame(-3, (int) $outlet->st_pv_gap);
        $this->assertSame(8, (int) $outlet->cvm_fm1);
        $this->assertSame(9, (int) $outlet->cvm_m1);
        $this->assertSame(10, (int) $outlet->cvm_mtd);
        $this->assertSame(1, (int) $outlet->cvm_gap);
        $this->assertEqualsWithDelta(11.111, (float) $outlet->cvm_mom_pct, 0.01);
        $this->assertSame(20, (int) $outlet->super_seru_mtd);
        $this->assertSame(30, (int) $outlet->comsak_mtd);
        $this->assertSame(40, (int) $outlet->hot_promo_mtd);
        $this->assertSame(48, (int) $outlet->biometrik_fm1);
        $this->assertSame(49, (int) $outlet->biometrik_m1);
        $this->assertSame(50, (int) $outlet->biometrik_mtd);
        $this->assertSame(1, (int) $outlet->biometrik_gap);

        $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->getJson(route('surya.outlet-detail.data', [
                'draw' => 3,
                'start' => 0,
                'length' => 25,
                'sales_cutoff' => '2026-10-20',
            ]))
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        $customCutoffResponse = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->getJson(route('surya.outlet-detail.data', [
                'draw' => 2,
                'start' => 0,
                'length' => 25,
                'sales_cutoff' => '2026-10-21',
                'search' => ['value' => 'Outlet Uji'],
            ]))
            ->assertOk();
        $this->assertSame(6, (int) $customCutoffResponse->json('data.0.st_sa_m1'));
        $this->assertSame(9, (int) $customCutoffResponse->json('data.0.st_pv_m1'));

        $mapDetailResponse = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->getJson('/surya/search?keyword='.$idOutlet);
        $mapDetailResponse->assertOk()->assertJsonCount(1);
        $this->assertSame(7, (int) $mapDetailResponse->json('0.m_stsa'));
        $this->assertSame(3, (int) $mapDetailResponse->json('0.m_stpv'));
        $this->assertNull($mapDetailResponse->json('0.fm1_stsa'));
        $this->assertNull($mapDetailResponse->json('0.m1_stpv'));
        $this->assertNull($mapDetailResponse->json('0.mom_stsa'));

        $exportResponse = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get(route('surya.outlet-detail.export', ['q' => 'Outlet Uji Detail']))
            ->assertOk()
            ->assertHeader('content-disposition');

        $sheet = IOFactory::load($exportResponse->baseResponse->getFile()->getPathname())->getActiveSheet();

        $this->assertSame('Detail Outlet', $sheet->getTitle());
        $this->assertSame('AO', $sheet->getHighestColumn());
        $this->assertSame(2, $sheet->getHighestRow());
        $this->assertSame('ID Outlet', $sheet->getCell('A1')->getValue());
        $this->assertSame('Biometrik %MoM', $sheet->getCell('AO1')->getValue());
        $this->assertSame((string) $idOutlet, (string) $sheet->getCell('A2')->getValue());
        $this->assertSame('Outlet Uji Detail', $sheet->getCell('B2')->getValue());
        $this->assertSame(7, (int) $sheet->getCell('I2')->getValue());
        $this->assertSame(3, (int) $sheet->getCell('N2')->getValue());
        $this->assertSame(17, (int) $sheet->getCell('G2')->getValue());
        $this->assertSame(4, (int) $sheet->getCell('H2')->getValue());
        $this->assertSame(10, (int) $sheet->getCell('S2')->getValue());
        $this->assertSame(48, (int) $sheet->getCell('AK2')->getValue());
        $this->assertSame(50, (int) $sheet->getCell('AM2')->getValue());
    }

    public function test_biometric_upload_uses_column_position_and_updates_outlet_detail(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Fixture admin belum tersedia.');
        }

        $idOutlet = 990000004;
        DB::table('appsdumais')->insert([
            'id_outlet' => $idOutlet,
            'nama_outlet' => 'Outlet Uji Biometrik',
            'kecamatan' => 'Kecamatan Uji',
            'tap' => 'DUMAI',
            'sf' => 'SF UJI BIOMETRIK',
            'pjp' => 'Selasa',
        ]);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['kolom bebas 1', 'kolom bebas 2', 'kolom bebas 3', 'kolom bebas 4'],
            [$idOutlet, 12, 8, 10],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'surya-bio-');
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        try {
            $this->actingAs($admin)
                ->withSession(['idtap' => 'SBP_DUMAI'])
                ->post(route('surya.transaction-upload.store'), [
                    'metrics_file' => new UploadedFile($path, 'biometrik.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
                ])
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertDatabaseHas('surya_daily_metrics', [
                'id_outlet' => $idOutlet,
                'metric_date' => now()->toDateString(),
                'bio_fm1' => 12,
                'bio_m1' => 8,
                'bio_m' => 10,
            ]);

            $response = $this->actingAs($admin)
                ->withSession(['idtap' => 'SBP_DUMAI'])
                ->getJson(route('surya.outlet-detail.data', [
                    'draw' => 1,
                    'start' => 0,
                    'length' => 25,
                    'search' => ['value' => 'Outlet Uji Biometrik'],
                ]))
                ->assertOk();

            $this->assertSame(12, (int) $response->json('data.0.biometrik_fm1'));
            $this->assertSame(8, (int) $response->json('data.0.biometrik_m1'));
            $this->assertSame(10, (int) $response->json('data.0.biometrik_mtd'));
            $this->assertSame(2, (int) $response->json('data.0.biometrik_gap'));
            $this->assertEqualsWithDelta(25.0, (float) $response->json('data.0.biometrik_mom_pct'), 0.01);
        } finally {
            @unlink($path);
        }
    }

    public function test_historical_sales_upload_uses_date_outlet_sa_pv_column_order(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Fixture admin belum tersedia.');
        }

        $idOutlet = 990000005;
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Tanggal Trx', 'ID Outlet', 'SA', 'PV'],
            ['21/09/2026', $idOutlet, 14, 27],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'surya-sales-');
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        try {
            $this->actingAs($admin)
                ->withSession(['idtap' => 'SBP_DUMAI'])
                ->post(route('surya.transaction-upload.store'), [
                    'metrics_file' => new UploadedFile($path, 'historis-sa-pv.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
                ])
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertDatabaseHas('surya_outlet_sales_history', [
                'transaction_date' => '2026-09-21',
                'id_outlet' => $idOutlet,
                'sa' => 14,
                'pv' => 27,
            ]);
        } finally {
            @unlink($path);
        }
    }
}
