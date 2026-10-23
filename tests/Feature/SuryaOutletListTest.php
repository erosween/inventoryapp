<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class SuryaOutletListTest extends TestCase
{
    use DatabaseTransactions;

    public function test_visit_workbook_includes_selected_consignment_denomination(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 18:00:00', 'Asia/Jakarta'));
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Fixture admin_super belum tersedia.');
        }

        DB::table('denom')->insert([
            'iddenom' => 'CONS-EXPORT-TEST',
            'denom' => 'Perdana Konsinyasi Export',
            'group_name' => 'SA',
        ]);
        DB::table('surya_sa_consignment_numbers')->insert([
            'reported_at' => now(),
            'id_outlet' => 'CONS-OUTLET-TEST',
            'idtap' => 'DUMAI',
            'idsf' => 'CONS-SF-TEST',
            'iddenom' => 'CONS-EXPORT-TEST',
            'serial_number' => '6281234500999',
            'status' => 'consigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->get(route('surya.visits.export'))
            ->assertOk();

        \Maatwebsite\Excel\Facades\Excel::assertDownloaded(
            'surya-penjualan-2026-10-01-sampai-2026-10-23.xlsx',
            function ($export) {
                $sheet = $export->sheets()[3];
                $row = $sheet->collection()->first(fn (array $row) => $row[7] === '6281234500999');

                return $sheet->headings()[6] === 'Denom Konsinyasi'
                    && $row !== null
                    && $row[6] === 'Perdana Konsinyasi Export';
            }
        );

        Carbon::setTestNow();
    }

    public function test_helper_visit_keeps_actual_and_sales_on_helper_and_shows_all_receipt_photos(): void
    {
        Storage::fake('public');
        Carbon::setTestNow(Carbon::parse('2026-10-23 17:00:00', 'Asia/Jakarta'));
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Fixture admin belum tersedia.');
        }

        DB::table('idsf')->insert([
            ['idsf' => 'HELPER-REKAP', 'namasf' => 'Helper Rekap', 'idtap' => 'DUMAI'],
            ['idsf' => 'OWNER-REKAP', 'namasf' => 'Owner Rekap', 'idtap' => 'DUMAI'],
        ]);
        DB::table('denom')->insert([
            ['iddenom' => 'REKAP-SA', 'denom' => 'Rekap SA', 'group_name' => 'SA', 'product_group' => 'SA'],
            ['iddenom' => 'REKAP-PV', 'denom' => 'Rekap PV', 'group_name' => 'PV', 'product_group' => 'PV'],
        ]);
        DB::table('appsdumais')->insert([
            'id_outlet' => 990000099, 'nama_outlet' => 'Outlet Helper Rekap',
            'tap' => 'DUMAI', 'sf' => 'Owner Rekap', 'pjp' => 'Jumat',
            'latitude' => 1.5, 'longitude' => 101.5,
        ]);
        DB::table('surya_visits')->insert([
            'visited_at' => now(), 'id_outlet' => '990000099', 'idtap' => 'DUMAI',
            'idsf' => 'HELPER-REKAP', 'latitude' => 1.5, 'longitude' => 101.5,
            'photo_path' => 'visits/front.webp', 'photo_front_path' => 'visits/front.webp',
            'photo_inside_path' => 'visits/inside.webp', 'gps_status' => 'valid',
        ]);
        DB::table('mobile_penjualan')->insert([
            ['tgl' => now()->toDateString(), 'id_outlet' => '990000099', 'idtap' => 'DUMAI', 'idsf' => 'HELPER-REKAP', 'owner_idsf' => 'OWNER-REKAP', 'iddenom' => 'REKAP-SA', 'qty' => 4, 'status' => 'pending'],
            ['tgl' => now()->toDateString(), 'id_outlet' => '990000099', 'idtap' => 'DUMAI', 'idsf' => 'HELPER-REKAP', 'owner_idsf' => 'OWNER-REKAP', 'iddenom' => 'REKAP-PV', 'qty' => 6, 'status' => 'pending'],
        ]);
        Storage::disk('public')->put('receipts/first.webp', 'first');
        Storage::disk('public')->put('receipts/second.webp', 'second');
        DB::table('mobile_sales_receipts')->insert([
            ['receipt_number' => 'HELPER-BON-1', 'tgl' => now()->toDateString(), 'id_outlet' => '990000099', 'idtap' => 'DUMAI', 'idsf' => 'HELPER-REKAP', 'total_qty' => 4, 'total_amount' => 0, 'receipt_photo_path' => 'receipts/first.webp'],
            ['receipt_number' => 'HELPER-BON-2', 'tgl' => now()->toDateString(), 'id_outlet' => '990000099', 'idtap' => 'DUMAI', 'idsf' => 'HELPER-REKAP', 'total_qty' => 6, 'total_amount' => 0, 'receipt_photo_path' => 'receipts/second.webp'],
        ]);

        $controller = app(\App\Http\Controllers\MonitaDumaiController::class);
        $visitMethod = new \ReflectionMethod($controller, 'buildVisitDetails');
        $visitDetails = collect($visitMethod->invoke($controller, now()->toDateString(), null));
        $helper = $visitDetails->firstWhere('sfcode', 'HELPER-REKAP');
        $this->assertNotNull($helper);
        $this->assertSame(1, $helper['actual_call']);
        $this->assertSame(4, $helper['total_sa']);
        $this->assertSame(6, $helper['total_pv']);
        $this->assertNull($visitDetails->firstWhere('sfcode', 'OWNER-REKAP'));

        $approvalMethod = new \ReflectionMethod($controller, 'buildApprovalVisits');
        $approvalVisits = collect($approvalMethod->invoke($controller, now()->toDateString(), null));
        $approvalVisit = $approvalVisits->firstWhere('id_outlet', '990000099');
        $this->assertNotNull($approvalVisit);
        $this->assertCount(2, $approvalVisit->receipt_photos);

        $mapResponse = $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->getJson('/surya/search?keyword=990000099')
            ->assertOk();
        $this->assertCount(2, $mapResponse->json('0.receipt_photos'));
        Carbon::setTestNow();
    }

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
        Carbon::setTestNow(Carbon::parse('2026-10-22 12:00:00', 'Asia/Jakarta'));
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
            ['id_outlet' => $idOutlet, 'metric_date' => '2026-10-19', 'cvm_fm1' => 0, 'cvm_m1' => 0, 'cvm_m' => 1, 'ss_fm1' => 0, 'ss_m1' => 0, 'ss_m' => 2, 'comsak_fm1' => 0, 'comsak_m1' => 0, 'comsak_m' => 3, 'hot_promo_fm1' => 0, 'hot_promo_m1' => 0, 'hot_promo_m' => 4, 'bio_fm1' => 0, 'bio_m1' => 0, 'bio_m' => 0, 'uploaded_metrics' => null],
            ['id_outlet' => $idOutlet, 'metric_date' => '2026-10-20', 'cvm_fm1' => 8, 'cvm_m1' => 9, 'cvm_m' => 10, 'ss_fm1' => 18, 'ss_m1' => 19, 'ss_m' => 20, 'comsak_fm1' => 28, 'comsak_m1' => 29, 'comsak_m' => 30, 'hot_promo_fm1' => 38, 'hot_promo_m1' => 39, 'hot_promo_m' => 40, 'bio_fm1' => 0, 'bio_m1' => 0, 'bio_m' => 0, 'uploaded_metrics' => 'cvm,ss,comsak,hot_promo'],
            ['id_outlet' => $idOutlet, 'metric_date' => '2026-10-21', 'cvm_fm1' => 0, 'cvm_m1' => 0, 'cvm_m' => 0, 'ss_fm1' => 0, 'ss_m1' => 0, 'ss_m' => 0, 'comsak_fm1' => 0, 'comsak_m1' => 0, 'comsak_m' => 0, 'hot_promo_fm1' => 0, 'hot_promo_m1' => 0, 'hot_promo_m' => 0, 'bio_fm1' => 48, 'bio_m1' => 49, 'bio_m' => 50, 'uploaded_metrics' => 'bio'],
        ]);
        DB::table('surya_outlet_sales_history')->insert([
            ['transaction_date' => '2026-09-01', 'id_outlet' => $idOutlet, 'sa' => 4, 'pv' => 6],
            ['transaction_date' => '2026-09-21', 'id_outlet' => $idOutlet, 'sa' => 2, 'pv' => 3],
            ['transaction_date' => '2026-09-22', 'id_outlet' => $idOutlet, 'sa' => 10, 'pv' => 20],
            ['transaction_date' => '2026-09-30', 'id_outlet' => $idOutlet, 'sa' => 1, 'pv' => 2],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get(route('surya.outlet-detail', ['q' => 'Outlet Uji Detail']));

        $response->assertOk()
            ->assertSee('DETAIL OUTLET')
            ->assertSee('Gap MoM')
            ->assertSee('%MoM')
            ->assertSee('Outlet Uji Detail')
            ->assertSee('Menampilkan 1–1 dari 1 outlet')
            ->assertSee('Export Excel')
            ->assertSee('id="sales-cutoff"', false)
            ->assertSee('value="2026-10-20"', false)
            ->assertSee('id="profile-modal"', false)
            ->assertSee('outlet-profile-link', false)
            ->assertSee('position:sticky;top:50px', false)
            ->assertSee('position:sticky;left:130px', false)
            ->assertSee('min-height:610px', false)
            ->assertDontSee('DataTable(', false)
            ->assertDontSee('detail-outlet\\/data', false);

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
        $this->assertSame(8, (int) $mapDetailResponse->json('0.fm1_digipos'));
        $this->assertSame(9, (int) $mapDetailResponse->json('0.m1_digipos'));
        $this->assertSame(10, (int) $mapDetailResponse->json('0.m_digipos'));
        $this->assertSame(20, (int) $mapDetailResponse->json('0.m_super'));
        $this->assertSame(30, (int) $mapDetailResponse->json('0.m_comsak'));
        $this->assertSame(40, (int) $mapDetailResponse->json('0.m_hot'));
        $this->assertSame(48, (int) $mapDetailResponse->json('0.fm1_bio'));
        $this->assertSame(49, (int) $mapDetailResponse->json('0.m1_bio'));
        $this->assertSame(50, (int) $mapDetailResponse->json('0.m_bio'));

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
        Carbon::setTestNow();
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
        DB::table('surya_daily_metrics')->insert([
            'id_outlet' => $idOutlet,
            'metric_date' => now()->subDay()->toDateString(),
            'cvm_fm1' => 21,
            'cvm_m1' => 13,
            'cvm_m' => 17,
            'ss_fm1' => 31,
            'ss_m1' => 23,
            'ss_m' => 27,
            'comsak_fm1' => 41,
            'comsak_m1' => 33,
            'comsak_m' => 37,
            'hot_promo_fm1' => 51,
            'hot_promo_m1' => 43,
            'hot_promo_m' => 47,
            'uploaded_metrics' => 'cvm,ss,comsak,hot_promo',
        ]);
        DB::table('surya_daily_metrics')->insert([
            'id_outlet' => $idOutlet,
            'metric_date' => now()->toDateString(),
            'uploaded_metrics' => 'ss,hot_promo,cvm,insak,comsak,digital,bio',
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
                'uploaded_metrics' => 'bio',
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
            $this->assertSame(21, (int) $response->json('data.0.cvm_fm1'));
            $this->assertSame(13, (int) $response->json('data.0.cvm_m1'));
            $this->assertSame(17, (int) $response->json('data.0.cvm_mtd'));
            $this->assertSame(27, (int) $response->json('data.0.super_seru_mtd'));
            $this->assertSame(37, (int) $response->json('data.0.comsak_mtd'));
            $this->assertSame(47, (int) $response->json('data.0.hot_promo_mtd'));

            $export = $this->actingAs($admin)
                ->withSession(['idtap' => 'SBP_DUMAI'])
                ->get(route('surya.outlet-detail.export', ['q' => 'Outlet Uji Biometrik']))
                ->assertOk();
            $sheet = IOFactory::load($export->baseResponse->getFile()->getPathname())->getActiveSheet();
            $this->assertSame(17, (int) $sheet->getCell('S2')->getValue());
            $this->assertSame(27, (int) $sheet->getCell('X2')->getValue());
            $this->assertSame(37, (int) $sheet->getCell('AC2')->getValue());
            $this->assertSame(47, (int) $sheet->getCell('AH2')->getValue());
            $this->assertSame(10, (int) $sheet->getCell('AM2')->getValue());
        } finally {
            @unlink($path);
        }
    }

    public function test_partial_transaction_upload_preserves_other_metrics_and_is_visible_in_detail(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Fixture admin belum tersedia.');
        }

        $idOutlet = 990000006;
        DB::table('appsdumais')->insert([
            'id_outlet' => $idOutlet,
            'nama_outlet' => 'Outlet Uji Upload Parsial',
            'kecamatan' => 'Kecamatan Uji',
            'tap' => 'DUMAI',
            'sf' => 'SF UJI PARSIAL',
            'pjp' => 'Rabu',
        ]);
        DB::table('surya_daily_metrics')->insert([
            'id_outlet' => $idOutlet,
            'metric_date' => '2026-10-20',
            'ss_fm1' => 30,
            'ss_m1' => 20,
            'ss_m' => 25,
            'uploaded_metrics' => 'ss',
        ]);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['id_outlet', 'cvm_fm1', 'cvm_m1', 'cvm_m', 'update'],
            [$idOutlet, 12, 8, 10, '2026-10-20'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'surya-cvm-');
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        try {
            $this->actingAs($admin)
                ->withSession(['idtap' => 'SBP_DUMAI'])
                ->post(route('surya.transaction-upload.store'), [
                    'metrics_file' => new UploadedFile($path, 'cvm.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
                ])
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertDatabaseHas('surya_daily_metrics', [
                'id_outlet' => $idOutlet,
                'metric_date' => '2026-10-20',
                'ss_m' => 25,
                'cvm_m' => 10,
                'uploaded_metrics' => 'ss,cvm',
            ]);

            $response = $this->actingAs($admin)
                ->withSession(['idtap' => 'SBP_DUMAI'])
                ->getJson(route('surya.outlet-detail.data', [
                    'draw' => 1,
                    'start' => 0,
                    'length' => 25,
                    'search' => ['value' => 'Outlet Uji Upload Parsial'],
                ]))
                ->assertOk();

            $this->assertSame(25, (int) $response->json('data.0.super_seru_mtd'));
            $this->assertSame(10, (int) $response->json('data.0.cvm_mtd'));

            $export = $this->actingAs($admin)
                ->withSession(['idtap' => 'SBP_DUMAI'])
                ->get(route('surya.outlet-detail.export', ['q' => 'Outlet Uji Upload Parsial']))
                ->assertOk();
            $sheet = IOFactory::load($export->baseResponse->getFile()->getPathname())->getActiveSheet();
            $this->assertSame(10, (int) $sheet->getCell('S2')->getValue());
            $this->assertSame(25, (int) $sheet->getCell('X2')->getValue());
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
