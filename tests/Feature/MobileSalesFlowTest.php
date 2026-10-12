<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MobileSalesFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createMobileSchema();
    }

    public function test_mobile_pages_redirect_to_login_when_session_is_missing(): void
    {
        $this->get(route('mobile.stock'))
            ->assertRedirect(route('mobile.login'));
    }

    public function test_mobile_login_replaces_old_mobile_session_values(): void
    {
        DB::table('idsf')->insert([
            'idsf' => 'SF001',
            'namasf' => 'Sales Baru',
            'idtap' => 'TAP001',
            'login_code' => 'SF-NEW',
            'password' => Hash::make('secret'),
        ]);

        $this->withSession([
            'mobile_sf_id' => 'SF_OLD',
            'mobile_sf_name' => 'Sales Lama',
            'idtap' => 'TAP_OLD',
        ])->post(route('mobile.login.post'), [
            'login_code' => 'SF-NEW',
            'password' => 'secret',
        ])
            ->assertRedirect(route('mobile.index'))
            ->assertSessionHas('mobile_sf_id', 'SF001')
            ->assertSessionHas('mobile_sf_name', 'Sales Baru')
            ->assertSessionHas('idtap', 'TAP001');
    }

    public function test_stock_page_uses_fresh_response_headers_and_single_stock_header(): void
    {
        DB::table('denom')->insert([
            'iddenom' => 'D001',
            'denom' => '5GB/3hari',
            'group_name' => '3 HARI',
            'harga_jual' => 15000,
        ]);

        DB::table('stockawalsf')->insert([
            'idsf' => 'SF001',
            'iddenom' => 'D001',
            'stock' => 12,
        ]);

        $response = $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->get(route('mobile.stock'))
            ->assertOk()
            ->assertSee('Stok Saya')
            ->assertSee('5GB/3hari')
            ->assertDontSee('MSP Connect');

        $cacheControl = $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_mobile_layout_prefetches_and_smooths_nav_transitions(): void
    {
        $layout = file_get_contents(resource_path('views/layout/mobile_layout.blade.php'));

        $this->assertStringContainsString('rel = \'prefetch\'', $layout);
        $this->assertStringContainsString('page-is-leaving', $layout);
        $this->assertStringContainsString('is-loading', $layout);
        $this->assertStringContainsString('prefers-reduced-motion', $layout);
    }

    public function test_mobile_sales_form_warns_when_quantity_exceeds_stock(): void
    {
        $form = file_get_contents(resource_path('views/mobile/form.blade.php'));

        $this->assertStringContainsString('data-stock', $form);
        $this->assertStringContainsString('stockMap', $form);
        $this->assertStringContainsString('validateStock', $form);
        $this->assertStringContainsString('Stok tidak cukup', $form);
        $this->assertStringContainsString('stock-warning', $form);
    }

    public function test_prefilled_outlet_on_sales_form_is_locked_and_shows_outlet_name(): void
    {
        DB::table('appsdumais')->insert([
            'id_outlet' => '1200014153',
            'nama_outlet' => 'ELLY PONSEL',
            'sf' => 'Sales Test',
            'pjp' => 'Rabu',
        ]);

        $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->get(route('mobile.form', ['id_outlet' => '1200014153']))
            ->assertOk()
            ->assertSee('value="1200014153"', false)
            ->assertSee('name="id_outlet"', false)
            ->assertSee('disabled', false)
            ->assertSee('Nama Outlet')
            ->assertSee('ELLY PONSEL');
    }

    public function test_password_form_shows_live_confirmation_feedback(): void
    {
        $view = file_get_contents(resource_path('views/mobile/auth/password.blade.php'));

        $this->assertStringContainsString('password-match-feedback', $view);
        $this->assertStringContainsString('new_password_confirmation', $view);
        $this->assertStringContainsString('password-submit-btn', $view);
        $this->assertStringContainsString('Password sudah sesuai', $view);
        $this->assertStringContainsString('Password belum sama', $view);
    }

    public function test_mobile_sales_store_persists_location_data(): void
    {
        Carbon::setTestNow('2026-04-29 10:00:00');

        DB::table('denom')->insert([
            'iddenom' => 'D001',
            'denom' => '5GB/3hari',
            'group_name' => '3 HARI',
            'harga_jual' => 15000,
        ]);

        DB::table('stockawalsf')->insert([
            'idsf' => 'SF001',
            'iddenom' => 'D001',
            'stock' => 20,
        ]);

        DB::table('surya_visits')->insert([
            'visited_at' => now(),
            'id_outlet' => 'OUTLET-1',
            'idtap' => 'TAP001',
            'idsf' => 'SF001',
            'latitude' => -6.20000000,
            'longitude' => 106.81666600,
            'photo_path' => 'surya-visits/front/test.jpg',
            'photo_front_path' => 'surya-visits/front/test.jpg',
            'photo_inside_path' => 'surya-visits/inside/test.jpg',
            'gps_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $session = [
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ];

        $response = $this->withSession($session)->post(route('mobile.store'), [
            'tgl' => '2026-04-01',
            'id_outlet' => 'outlet-1',
            'products' => [
                [
                    'iddenom' => 'D001',
                    'qty' => 3,
                ],
            ],
            'latitude' => -6.20000000,
            'longitude' => 106.81666600,
        ]);

        $receipt = \App\Models\MobileSalesReceipt::query()->firstOrFail();
        $response->assertRedirect(route('mobile.receipt.show', $receipt));

        $this->assertDatabaseHas('mobile_penjualan', [
            'tgl' => '2026-04-29',
            'id_outlet' => 'OUTLET-1',
            'idsf' => 'SF001',
            'iddenom' => 'D001',
            'qty' => 3,
            'unit_price' => 15000,
            'latitude' => -6.20000000,
            'longitude' => 106.81666600,
            'status' => 'draft_wa',
        ]);

        $this->assertDatabaseMissing('mobile_penjualan', [
            'tgl' => '2026-04-01',
            'id_outlet' => 'OUTLET-1',
        ]);

        $this->assertTrue(
            \App\Models\SuryaVisit::query()
                ->inProgressForSalesForce('SF001', '2026-04-29')
                ->exists()
        );

        $prepare = $this->withSession($session)->postJson(route('mobile.receipt.whatsapp', $receipt), [
            'owner_whatsapp' => '0812-3456-7890',
        ])->assertOk()
            ->assertJson(['success' => true, 'owner_whatsapp' => '6281234567890']);

        $this->assertStringContainsString('https://wa.me/6281234567890', $prepare->json('whatsapp_url'));
        $this->assertDatabaseHas('mobile_penjualan', [
            'receipt_id' => $receipt->id,
            'status' => 'draft_wa',
        ]);

        $this->withSession($session)
            ->post(route('mobile.receipt.confirm', $receipt))
            ->assertRedirect(route('mobile.history'));

        $this->assertDatabaseHas('mobile_sales_receipts', [
            'id' => $receipt->id,
            'owner_whatsapp' => '6281234567890',
        ]);
        $this->assertNotNull($receipt->fresh()->whatsapp_shared_at);
        $this->assertDatabaseHas('mobile_penjualan', [
            'receipt_id' => $receipt->id,
            'status' => 'pending',
        ]);
        $this->assertFalse(
            \App\Models\SuryaVisit::query()
                ->inProgressForSalesForce('SF001', '2026-04-29')
                ->exists()
        );
    }

    public function test_sales_force_cannot_open_another_outlet_while_visit_is_in_progress(): void
    {
        DB::table('appsdumais')->insert([
            [
                'id_outlet' => 'OUTLET-AKTIF',
                'nama_outlet' => 'Outlet Sedang Berlangsung',
                'sf' => 'Sales Test',
            ],
            [
                'id_outlet' => 'OUTLET-LAIN',
                'nama_outlet' => 'Outlet Lain',
                'sf' => 'Sales Test',
            ],
        ]);

        DB::table('surya_visits')->insert([
            'visited_at' => now(),
            'id_outlet' => 'OUTLET-AKTIF',
            'idtap' => 'TAP001',
            'idsf' => 'SF001',
            'latitude' => 1.23456789,
            'longitude' => 101.98765432,
            'photo_path' => 'surya-visits/front/test.jpg',
            'photo_front_path' => 'surya-visits/front/test.jpg',
            'photo_inside_path' => 'surya-visits/inside/test.jpg',
            'gps_status' => 'valid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->get(route('mobile.visit.menu', ['id_outlet' => 'OUTLET-LAIN']))
            ->assertRedirect(route('mobile.visit.menu', ['id_outlet' => 'OUTLET-AKTIF']))
            ->assertSessionHas('error');
    }

    public function test_sales_receipt_is_rejected_when_product_price_is_not_configured(): void
    {
        DB::table('denom')->insert([
            'iddenom' => 'D-ZERO',
            'denom' => 'Produk Tanpa Harga',
            'group_name' => 'SA',
            'harga_jual' => 0,
        ]);
        DB::table('stockawalsf')->insert([
            'idsf' => 'SF001',
            'iddenom' => 'D-ZERO',
            'stock' => 10,
        ]);
        DB::table('surya_visits')->insert([
            'visited_at' => now(),
            'id_outlet' => 'OUTLET-ZERO',
            'idtap' => 'TAP001',
            'idsf' => 'SF001',
            'latitude' => 1.23456789,
            'longitude' => 101.98765432,
            'photo_path' => 'surya-visits/front/test.jpg',
            'photo_front_path' => 'surya-visits/front/test.jpg',
            'photo_inside_path' => 'surya-visits/inside/test.jpg',
            'gps_status' => 'valid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->from(route('mobile.form', ['id_outlet' => 'OUTLET-ZERO']))
            ->post(route('mobile.store'), [
                'id_outlet' => 'OUTLET-ZERO',
                'products' => [[
                    'iddenom' => 'D-ZERO',
                    'qty' => 1,
                ]],
            ])
            ->assertRedirect(route('mobile.form', ['id_outlet' => 'OUTLET-ZERO']))
            ->assertSessionHas('error', 'Harga jual Produk Tanpa Harga belum diatur oleh admin. Transaksi belum dapat dibuat.');

        $this->assertDatabaseCount('mobile_sales_receipts', 0);
        $this->assertDatabaseCount('mobile_penjualan', 0);
    }

    public function test_completed_visit_photos_cannot_be_opened_again_and_menu_is_not_clickable(): void
    {
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-AKTIF',
            'nama_outlet' => 'Outlet Sedang Berlangsung',
            'sf' => 'Sales Test',
        ]);

        DB::table('surya_visits')->insert([
            'visited_at' => now(),
            'id_outlet' => 'OUTLET-AKTIF',
            'idtap' => 'TAP001',
            'idsf' => 'SF001',
            'latitude' => 1.23456789,
            'longitude' => 101.98765432,
            'photo_path' => 'surya-visits/front/test.jpg',
            'photo_front_path' => 'surya-visits/front/test.jpg',
            'photo_inside_path' => 'surya-visits/inside/test.jpg',
            'gps_status' => 'valid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $session = [
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ];

        $this->withSession($session)
            ->get(route('mobile.visit', ['id_outlet' => 'OUTLET-AKTIF']))
            ->assertRedirect(route('mobile.visit.menu', ['id_outlet' => 'OUTLET-AKTIF']))
            ->assertSessionHas('error');

        $this->withSession($session)
            ->get(route('mobile.visit.menu', ['id_outlet' => 'OUTLET-AKTIF']))
            ->assertOk()
            ->assertSee('2 Foto Visit Sudah Diambil')
            ->assertSee('tidak dapat diubah')
            ->assertDontSee('Input 2 Foto Visit');
    }

    public function test_visit_photo_page_has_back_button_to_selected_outlet_activity(): void
    {
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-BARU',
            'nama_outlet' => 'Outlet Baru',
            'sf' => 'Sales Test',
        ]);

        $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->get(route('mobile.visit', ['id_outlet' => 'OUTLET-BARU']))
            ->assertOk()
            ->assertSee(route('mobile.visit.menu', ['id_outlet' => 'OUTLET-BARU']), false)
            ->assertSee('aria-label="Kembali"', false);
    }

    public function test_dashboard_marks_active_outlet_and_locks_other_outlets(): void
    {
        $days = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        DB::table('appsdumais')->insert([
            [
                'id_outlet' => 'OUTLET-AKTIF',
                'nama_outlet' => 'Outlet Sedang Berlangsung',
                'sf' => 'Sales Test',
                'pjp' => $days[date('l')],
            ],
            [
                'id_outlet' => 'OUTLET-LAIN',
                'nama_outlet' => 'Outlet Lain',
                'sf' => 'Sales Test',
                'pjp' => $days[date('l')],
            ],
        ]);

        DB::table('surya_visits')->insert([
            'visited_at' => now(),
            'id_outlet' => 'OUTLET-AKTIF',
            'idtap' => 'TAP001',
            'idsf' => 'SF001',
            'latitude' => 1.23456789,
            'longitude' => 101.98765432,
            'photo_path' => 'surya-visits/front/test.jpg',
            'photo_front_path' => 'surya-visits/front/test.jpg',
            'photo_inside_path' => 'surya-visits/inside/test.jpg',
            'gps_status' => 'valid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->get(route('mobile.index'))
            ->assertOk()
            ->assertSee('SEDANG BERLANGSUNG')
            ->assertSee('LANJUTKAN')
            ->assertSee('TERKUNCI');
    }

    public function test_admin_approval_updates_outlet_coordinates_and_unlocks_visit(): void
    {
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-2',
            'nama_outlet' => 'Outlet Koordinat Lama',
            'latitude' => 1.00000000,
            'longitude' => 101.00000000,
        ]);

        $visitId = DB::table('surya_visits')->insertGetId([
            'visited_at' => now(),
            'id_outlet' => 'OUTLET-2',
            'idtap' => 'TAP001',
            'idsf' => 'SF001',
            'latitude' => 1.23456789,
            'longitude' => 101.98765432,
            'photo_path' => 'surya-visits/front/test.jpg',
            'photo_front_path' => 'surya-visits/front/test.jpg',
            'photo_inside_path' => 'surya-visits/inside/test.jpg',
            'gps_status' => 'pending_admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $visit = \App\Models\SuryaVisit::findOrFail($visitId);
        app(\App\Http\Controllers\SuryaVisitReportController::class)->approve($visit);

        $this->assertDatabaseHas('surya_visits', [
            'id' => $visitId,
            'gps_status' => 'approved',
        ]);
        $this->assertDatabaseHas('appsdumais', [
            'id_outlet' => 'OUTLET-2',
            'latitude' => 1.23456789,
            'longitude' => 101.98765432,
        ]);
    }

    public function test_mobile_sales_update_removes_items_deleted_from_edit_form_and_updates_notes(): void
    {
        DB::table('mobile_penjualan')->insert([
            [
                'id' => 1,
                'tgl' => '2026-04-29',
                'id_outlet' => 'OUTLET-1',
                'idtap' => 'TAP001',
                'idsf' => 'SF001',
                'iddenom' => 'D001',
                'qty' => 2,
                'keterangan' => 'Catatan lama',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'tgl' => '2026-04-29',
                'id_outlet' => 'OUTLET-1',
                'idtap' => 'TAP001',
                'idsf' => 'SF001',
                'iddenom' => 'D002',
                'qty' => 4,
                'keterangan' => 'Catatan lama',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->put(route('mobile.update', ['OUTLET-1', '2026-04-29']), [
            'keterangan' => 'Catatan baru',
            'products' => [
                [
                    'id' => 1,
                    'iddenom' => 'D001',
                    'qty' => 5,
                ],
            ],
        ])->assertRedirect(route('mobile.history'));

        $this->assertDatabaseHas('mobile_penjualan', [
            'id' => 1,
            'qty' => 5,
            'keterangan' => 'Catatan baru',
        ]);
        $this->assertDatabaseMissing('mobile_penjualan', [
            'id' => 2,
        ]);
    }

    private function createMobileSchema(): void
    {
        Schema::create('idempotency_keys', function ($table) {
            $table->id();
            $table->uuid('key')->unique();
            $table->string('action');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('request_hash', 64);
            $table->timestamps();
        });

        Schema::create('idsf', function ($table) {
            $table->string('idsf')->primary();
            $table->string('namasf');
            $table->string('idtap');
            $table->string('login_code')->nullable();
            $table->string('password')->nullable();
        });

        Schema::create('denom', function ($table) {
            $table->string('iddenom')->primary();
            $table->string('denom');
            $table->string('group_name')->default('LAINNYA');
            $table->integer('harga_jual')->default(0);
        });

        Schema::create('stockawalsf', function ($table) {
            $table->string('idsf');
            $table->string('iddenom');
            $table->integer('stock')->default(0);
        });

        Schema::create('mobile_sales_receipts', function ($table) {
            $table->id();
            $table->string('receipt_number', 32)->unique();
            $table->date('tgl');
            $table->string('id_outlet');
            $table->string('idtap', 25);
            $table->string('idsf', 25);
            $table->string('owner_whatsapp', 20)->nullable();
            $table->unsignedInteger('total_qty')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->timestamp('whatsapp_opened_at')->nullable();
            $table->timestamp('whatsapp_shared_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mobile_penjualan', function ($table) {
            $table->id();
            $table->unsignedBigInteger('receipt_id')->nullable();
            $table->date('tgl');
            $table->string('id_outlet')->nullable();
            $table->string('idtap', 25);
            $table->string('idsf', 25);
            $table->string('iddenom', 25);
            $table->integer('qty');
            $table->unsignedInteger('unit_price')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });

        Schema::create('appsdumais', function ($table) {
            $table->string('id_outlet');
            $table->string('nama_outlet');
            $table->string('sf')->nullable();
            $table->string('pjp')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
        });

        Schema::create('surya_visits', function ($table) {
            $table->id();
            $table->dateTime('visited_at');
            $table->string('id_outlet');
            $table->string('idtap', 25);
            $table->string('idsf', 25);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('distance_meters', 10, 2)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_front_path')->nullable();
            $table->string('photo_inside_path')->nullable();
            $table->string('gps_status', 30)->default('valid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('masuksf', function ($table) {
            $table->id();
            $table->date('tgl');
            $table->string('idsf');
            $table->string('iddenom');
            $table->integer('qty')->default(0);
        });

        Schema::create('keluarsf', function ($table) {
            $table->id();
            $table->date('tgl');
            $table->string('idsf');
            $table->string('iddenom');
            $table->integer('qty')->default(0);
        });

        Schema::create('keluar', function ($table) {
            $table->id();
            $table->string('penerima')->nullable();
            $table->integer('status')->default(0);
        });
    }
}
