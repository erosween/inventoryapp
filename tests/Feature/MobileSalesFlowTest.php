<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
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
            [
                'iddenom' => 'D001',
                'denom' => '5GB/3hari',
                'group_name' => '3 HARI',
                'harga_jual' => 15000,
            ],
            [
                'iddenom' => 'D002',
                'denom' => 'Produk Stok Kosong',
                'group_name' => '3 HARI',
                'harga_jual' => 25000,
            ],
        ]);

        DB::table('stockawalsf')->insert([
            [
                'idsf' => 'SF001',
                'iddenom' => 'D001',
                'stock' => 12,
            ],
            [
                'idsf' => 'SF001',
                'iddenom' => 'D002',
                'stock' => 0,
            ],
        ]);

        $response = $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->get(route('mobile.stock'))
            ->assertOk()
            ->assertSee('Stok Saya')
            ->assertSee('5GB/3hari')
            ->assertDontSee('Produk Stok Kosong')
            ->assertDontSee('Rp ')
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

    public function test_mobile_layout_supports_pull_to_refresh_without_discarding_form_drafts(): void
    {
        $layout = file_get_contents(resource_path('views/layout/mobile_layout.blade.php'));
        $form = file_get_contents(resource_path('views/mobile/form.blade.php'));

        $this->assertStringContainsString('mobile-pull-refresh', $layout);
        $this->assertStringContainsString('surya:pull-refresh:', $layout);
        $this->assertStringContainsString('mobile:before-refresh', $layout);
        $this->assertStringContainsString('preserveFormInputs', $layout);
        $this->assertStringContainsString('restoreFormInputs', $layout);
        $this->assertStringContainsString('hasUnsavedFiles', $layout);
        $this->assertStringContainsString("window.addEventListener('mobile:before-refresh', saveFormState);", $form);
    }

    public function test_mobile_bottom_navigation_keeps_the_safe_area_outside_the_compact_bar(): void
    {
        $layout = file_get_contents(resource_path('views/layout/mobile_layout.blade.php'));

        $this->assertStringContainsString('bottom: calc(7px + env(safe-area-inset-bottom, 0px));', $layout);
        $this->assertStringContainsString('min-height: 48px;', $layout);
        $this->assertStringNotContainsString('padding-bottom: calc(8px + env(safe-area-inset-bottom));', $layout);
    }

    public function test_mobile_sales_form_warns_when_quantity_exceeds_stock(): void
    {
        $form = file_get_contents(resource_path('views/mobile/form.blade.php'));

        $this->assertStringContainsString('data-stock', $form);
        $this->assertStringContainsString('stockMap', $form);
        $this->assertStringContainsString('validateStock', $form);
        $this->assertStringContainsString('Stok tidak cukup', $form);
        $this->assertStringContainsString('stock-warning', $form);
        $this->assertStringContainsString('outlet-search-inline', $form);
        $this->assertStringContainsString("route('mobile.search-outlet')", $form);
        $this->assertStringContainsString('Pilih outlet dari hasil pencarian', $form);
        $this->assertStringContainsString('outlet-details-card', $form);
        $this->assertStringContainsString('overflow: visible !important;', $form);
        $this->assertStringContainsString('z-index: 1085;', $form);
    }

    public function test_mobile_modals_and_toasts_respect_the_device_safe_area(): void
    {
        $layout = file_get_contents(resource_path('views/layout/mobile_layout.blade.php'));

        $this->assertStringContainsString('--mobile-safe-top: env(safe-area-inset-top, 0px);', $layout);
        $this->assertStringContainsString('.modal .modal-dialog:not(.modal-dialog-centered)', $layout);
        $this->assertStringContainsString('position: relative !important;', $layout);
        $this->assertStringContainsString('.swal2-container.swal2-top', $layout);
        $this->assertStringContainsString('padding-top: calc(var(--mobile-safe-top) + 54px) !important;', $layout);
        $this->assertStringContainsString("request()->is('mobile/history') || request()->is('mobile/stock') ? 'pt-0' : 'pt-2'", $layout);
    }

    public function test_unreleased_extra_features_are_disabled_and_marked_soon(): void
    {
        $layout = file_get_contents(resource_path('views/layout/mobile_layout.blade.php'));
        $form = file_get_contents(resource_path('views/mobile/form.blade.php'));

        $this->assertSame(2, substr_count($layout, 'class="soon-feature'));
        $this->assertSame(2, substr_count($layout, 'class="soon-badge">SOON'));
        $this->assertStringNotContainsString('Konsinyasi SA', $layout);
        $this->assertStringContainsString('id="consignment-toggle"', $form);
        $this->assertStringContainsString('name="consignment_numbers"', $form);
        $this->assertStringContainsString('consignment_numbers: $(\'#consignment_numbers\').val()', $form);
        $this->assertStringNotContainsString("href=\"{{ route('mobile.surya.consignment') }}\"", $layout);
        $this->assertStringNotContainsString("href=\"{{ route('mobile.surya.survey') }}\"", $layout);
        $this->assertStringNotContainsString("href=\"{{ route('mobile.surya.network') }}\"", $layout);

        $this->withSession([
            'mobile_sf_id' => 'SF001', 'mobile_sf_name' => 'Sales Test', 'idtap' => 'TAP001',
        ])->get(route('mobile.surya.consignment'))
            ->assertRedirect(route('mobile.form'));
    }

    public function test_mobile_sales_views_hide_currency_and_use_the_optimized_logo(): void
    {
        foreach (['index', 'form', 'edit', 'stock', 'history', 'receipt', 'surya-form'] as $view) {
            $contents = file_get_contents(resource_path("views/mobile/{$view}.blade.php"));

            $this->assertStringNotContainsString('Rp ', $contents, "Currency leaked into mobile view {$view}.");
        }

        $layout = file_get_contents(resource_path('views/layout/mobile_layout.blade.php'));
        $login = file_get_contents(resource_path('views/mobile/auth/login.blade.php'));
        $logo = public_path('assets/img/surya-logo-mobile.webp');

        $this->assertStringContainsString('surya-logo-mobile.webp', $layout);
        $this->assertStringContainsString('surya-logo-mobile.webp', $login);
        $this->assertFileExists($logo);
        $this->assertLessThan(10_000, filesize($logo));
    }

    public function test_enabled_consignment_requires_unique_valid_starter_numbers(): void
    {
        DB::table('denom')->insert([
            'iddenom' => 'SA-VALIDATION',
            'denom' => 'SA VALIDATION',
            'group_name' => 'SA',
            'harga_jual' => 0,
        ]);

        $session = [
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ];
        $payload = [
            'id_outlet' => 'OUTLET-VALIDATION',
            'products' => [['iddenom' => 'SA-VALIDATION', 'qty' => 1]],
            'consignment_enabled' => '1',
        ];

        $this->withSession($session)->from(route('mobile.form'))->post(route('mobile.store'), $payload)
            ->assertRedirect(route('mobile.form'))
            ->assertSessionHasErrors('consignment_numbers');

        $this->withSession($session)->from(route('mobile.form'))->post(route('mobile.store'), $payload + [
            'consignment_numbers' => "081234567890\n+62 812-3456-7890",
        ])->assertRedirect(route('mobile.form'))
            ->assertSessionHasErrors('consignment_numbers');

        $this->assertDatabaseCount('mobile_sales_receipts', 0);
        $this->assertDatabaseCount('surya_sa_consignment_numbers', 0);
    }

    public function test_sales_force_can_submit_consignment_without_sales_product(): void
    {
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-CONSIGN-ONLY',
            'nama_outlet' => 'Outlet Konsinyasi Saja',
            'sf' => 'Sales Test',
        ]);
        DB::table('surya_visits')->insert([
            'visited_at' => now(),
            'id_outlet' => 'OUTLET-CONSIGN-ONLY',
            'idtap' => 'TAP001',
            'idsf' => 'SF001',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'photo_path' => 'surya-visits/front/test.jpg',
            'photo_front_path' => 'surya-visits/front/test.jpg',
            'photo_inside_path' => 'surya-visits/inside/test.jpg',
            'gps_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $session = ['mobile_sf_id' => 'SF001', 'mobile_sf_name' => 'Sales Test', 'idtap' => 'TAP001'];

        $response = $this->withSession($session)->post(route('mobile.store'), [
            'id_outlet' => 'OUTLET-CONSIGN-ONLY',
            'products' => [],
            'consignment_enabled' => '1',
            'consignment_numbers' => "081299900001\n081299900002",
        ]);

        $receipt = \App\Models\MobileSalesReceipt::query()->firstOrFail();
        $response->assertRedirect(route('mobile.receipt.show', $receipt));
        $this->assertDatabaseCount('mobile_penjualan', 0);
        $this->assertDatabaseHas('surya_sa_consignment_numbers', [
            'receipt_id' => $receipt->id,
            'serial_number' => '6281299900001',
            'status' => 'draft',
        ]);

        $this->withSession($session)->get(route('mobile.receipt.show', $receipt))
            ->assertOk()
            ->assertSee('Konsinyasi SA')
            ->assertSee('6281299900001');

        $this->withSession($session)->post(route('mobile.receipt.confirm', $receipt))
            ->assertRedirect(route('mobile.history'));
        $this->assertSame(2, DB::table('surya_sa_consignment_numbers')->where('status', 'consigned')->count());
    }

    public function test_consignment_live_check_returns_existing_outlet_name(): void
    {
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-USED-NUMBER',
            'nama_outlet' => 'Outlet Pemilik Nomor',
            'sf' => 'Sales Test',
        ]);
        DB::table('surya_sa_consignment_numbers')->insert([
            'reported_at' => now(),
            'id_outlet' => 'OUTLET-USED-NUMBER',
            'idtap' => 'TAP001',
            'idsf' => 'SF001',
            'serial_number' => '6281234500000',
            'status' => 'consigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession([
            'mobile_sf_id' => 'SF001', 'mobile_sf_name' => 'Sales Test', 'idtap' => 'TAP001',
        ])->postJson(route('mobile.consignment.check'), [
            'numbers' => ['6281234500000', '6281234500001'],
        ])->assertOk()
            ->assertJsonPath('results.0.available', false)
            ->assertJsonPath('results.0.nama_outlet', 'Outlet Pemilik Nomor')
            ->assertJsonPath('results.1.available', true);
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

        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-1',
            'nama_outlet' => 'Outlet Satu',
            'sf' => 'Sales Test',
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
            'consignment_enabled' => '1',
            'consignment_numbers' => "081234567890\n+62 812-3456-7891",
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
        $this->assertDatabaseHas('surya_sa_consignment_numbers', [
            'receipt_id' => $receipt->id,
            'serial_number' => '6281234567890',
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('surya_sa_consignment_numbers', [
            'receipt_id' => $receipt->id,
            'serial_number' => '6281234567891',
            'status' => 'draft',
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
        $this->assertSame(2, DB::table('surya_sa_consignment_numbers')
            ->where('receipt_id', $receipt->id)
            ->where('status', 'consigned')
            ->count());
        $this->assertNull(
            DB::table('mobile_penjualan')->where('receipt_id', $receipt->id)->value('stock_reserved_at')
        );
        $this->assertSame(20, (int) DB::table('stockawalsf')
            ->where('idsf', 'SF001')
            ->where('iddenom', 'D001')
            ->value('stock'));
        $this->withSession($session)->get(route('mobile.stock'))
            ->assertOk()
            ->assertSee('17');
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

    public function test_stocked_product_can_be_sold_without_a_client_visible_price(): void
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
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-ZERO',
            'nama_outlet' => 'Outlet Tanpa Harga',
            'sf' => 'Sales Test',
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

        $response = $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->post(route('mobile.store'), [
                'id_outlet' => 'OUTLET-ZERO',
                'products' => [[
                    'iddenom' => 'D-ZERO',
                    'qty' => 1,
                ]],
            ]);

        $receipt = \App\Models\MobileSalesReceipt::query()->firstOrFail();

        $response->assertRedirect(route('mobile.receipt.show', $receipt));
        $this->assertDatabaseHas('mobile_penjualan', [
            'receipt_id' => $receipt->id,
            'iddenom' => 'D-ZERO',
            'qty' => 1,
            'unit_price' => null,
            'status' => 'draft_wa',
        ]);

        $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->get(route('mobile.receipt.show', $receipt))
            ->assertOk()
            ->assertSee('Produk Tanpa Harga')
            ->assertDontSee('Rp ');
    }

    public function test_sales_form_access_does_not_revalidate_visit_photos_on_submit(): void
    {
        DB::table('denom')->insert([
            'iddenom' => 'D-VISIT-ACCESS',
            'denom' => 'Produk Izin Visit',
            'group_name' => '1 HARI',
            'harga_jual' => 10000,
        ]);
        DB::table('stockawalsf')->insert([
            'idsf' => 'SF001',
            'iddenom' => 'D-VISIT-ACCESS',
            'stock' => 10,
        ]);
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-VISIT-ACCESS',
            'nama_outlet' => 'Outlet Izin Visit',
            'sf' => null,
            'tap' => 'TAP001',
        ]);
        $visitId = DB::table('surya_visits')->insertGetId([
            'visited_at' => now(),
            'id_outlet' => 'OUTLET-VISIT-ACCESS',
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
            ->get(route('mobile.form', ['id_outlet' => 'OUTLET-VISIT-ACCESS']))
            ->assertOk()
            ->assertSessionHas('mobile_sales_visit_access.id_outlet', 'OUTLET-VISIT-ACCESS');

        // Once the authorized sales form is open, transient photo/status
        // changes must not force the Sales Force to take the photos again.
        DB::table('surya_visits')->where('id', $visitId)->update([
            'photo_front_path' => null,
            'photo_inside_path' => null,
            'gps_status' => 'rejected',
        ]);

        $response = $this->post(route('mobile.store'), [
            'id_outlet' => 'OUTLET-VISIT-ACCESS',
            'products' => [[
                'iddenom' => 'D-VISIT-ACCESS',
                'qty' => 1,
            ]],
        ]);

        $response->assertSessionHasNoErrors()->assertSessionMissing('error');
        $receipt = \App\Models\MobileSalesReceipt::query()->firstOrFail();
        $response->assertRedirect(route('mobile.receipt.show', $receipt));
        $this->assertDatabaseHas('mobile_penjualan', [
            'receipt_id' => $receipt->id,
            'id_outlet' => 'OUTLET-VISIT-ACCESS',
            'iddenom' => 'D-VISIT-ACCESS',
            'qty' => 1,
        ]);
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

    public function test_visit_camera_buttons_are_compact_consistent_and_switchable(): void
    {
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-KAMERA',
            'nama_outlet' => 'Outlet Kamera',
            'sf' => 'Sales Test',
        ]);

        $response = $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->get(route('mobile.visit', ['id_outlet' => 'OUTLET-KAMERA']))
            ->assertOk()
            ->assertSee('Ganti Kamera')
            ->assertSee('camera-action', false)
            ->assertDontSee('Buka Kamera Depan')
            ->assertDontSee('Buka Kamera Dalam');

        $this->assertSame(2, substr_count($response->getContent(), '>Buka Kamera</button>'));
    }

    public function test_pending_visit_status_can_be_refreshed_until_sales_is_unlocked(): void
    {
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-STATUS',
            'nama_outlet' => 'Outlet Status',
            'sf' => 'Sales Test',
        ]);

        $visitId = DB::table('surya_visits')->insertGetId([
            'visited_at' => now(),
            'id_outlet' => 'OUTLET-STATUS',
            'idtap' => 'TAP001',
            'idsf' => 'SF001',
            'latitude' => 1.23456789,
            'longitude' => 101.98765432,
            'photo_path' => 'surya-visits/front/status.webp',
            'photo_front_path' => 'surya-visits/front/status.webp',
            'photo_inside_path' => 'surya-visits/inside/status.webp',
            'gps_status' => 'pending_admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $session = [
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ];

        $this->withSession($session)
            ->get(route('mobile.visit.menu', ['id_outlet' => 'OUTLET-STATUS']))
            ->assertOk()
            ->assertSee('Refresh Status');

        $pendingResponse = $this->withSession($session)
            ->getJson(route('mobile.visit.status', ['id_outlet' => 'OUTLET-STATUS']))
            ->assertOk()
            ->assertJson(['gps_status' => 'pending_admin', 'photo_done' => true, 'can_sell' => false]);
        $this->assertStringContainsString('no-store', $pendingResponse->headers->get('Cache-Control'));

        DB::table('surya_visits')->where('id', $visitId)->update(['gps_status' => 'approved']);

        $this->withSession($session)
            ->getJson(route('mobile.visit.status', ['id_outlet' => 'OUTLET-STATUS']))
            ->assertOk()
            ->assertJson(['gps_status' => 'approved', 'photo_done' => true, 'can_sell' => true]);
    }

    public function test_admin_visit_photos_use_relative_urls_and_zoom_viewer(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('surya-visits/front/photo.webp', 'front');
        Storage::disk('public')->put('surya-visits/front/thumbnails/photo.webp', 'thumb-front');
        Storage::disk('public')->put('surya-visits/inside/photo.webp', 'inside');
        Storage::disk('public')->put('surya-visits/inside/thumbnails/photo.webp', 'thumb-inside');

        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-FOTO',
            'nama_outlet' => 'Outlet Foto',
            'sf' => 'Sales Test',
        ]);
        DB::table('idsf')->insert([
            'idsf' => 'SF001',
            'namasf' => 'Sales Test',
            'idtap' => 'TAP001',
        ]);
        DB::table('surya_visits')->insert([
            'visited_at' => now(),
            'id_outlet' => 'OUTLET-FOTO',
            'idtap' => 'TAP001',
            'idsf' => 'SF001',
            'latitude' => 1.23456789,
            'longitude' => 101.98765432,
            'photo_path' => 'surya-visits/front/photo.webp',
            'photo_front_path' => 'surya-visits/front/photo.webp',
            'photo_inside_path' => 'surya-visits/inside/photo.webp',
            'gps_status' => 'valid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $view = app(\App\Http\Controllers\SuryaVisitReportController::class)
            ->index(Request::create('/surya/visits', 'GET'));
        $visit = $view->getData()['visits']->first();

        $this->assertSame('/storage/surya-visits/front/photo.webp', $visit->front_photo_url);
        $this->assertSame('/storage/surya-visits/front/thumbnails/photo.webp', $visit->front_thumbnail_url);

        $source = file_get_contents(resource_path('views/surya/visits-report.blade.php'));
        $this->assertStringContainsString('visit-photo-viewer', $source);
        $this->assertStringContainsString('data-photo-zoom-in', $source);
        $this->assertStringContainsString('image.removeAttribute(\'src\')', $source);
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
                'pjp' => $days[now()->format('l')],
            ],
            [
                'id_outlet' => 'OUTLET-LAIN',
                'nama_outlet' => 'Outlet Lain',
                'sf' => 'Sales Test',
                'pjp' => $days[now()->format('l')],
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
            ->assertSee('TERKUNCI')
            ->assertSee('pjp-card-row', false)
            ->assertSee('pjp-continue-button', false);
    }

    public function test_dashboard_splits_current_sales_into_pv_and_sa(): void
    {
        Carbon::setTestNow('2026-10-13 09:00:00');
        DB::table('denom')->insert([
            ['iddenom' => 'PV-1', 'denom' => 'PV 5GB', 'group_name' => '1 HARI', 'kategori_inject' => null, 'harga_jual' => 0],
            ['iddenom' => 'SA-1', 'denom' => 'SA SIMPATI', 'group_name' => 'SA', 'kategori_inject' => 'SA', 'harga_jual' => 0],
        ]);
        DB::table('mobile_penjualan')->insert([
            ['tgl' => '2026-10-13', 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001', 'idsf' => 'SF001', 'iddenom' => 'PV-1', 'qty' => 7, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['tgl' => '2026-10-13', 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001', 'idsf' => 'SF001', 'iddenom' => 'SA-1', 'qty' => 3, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()],
            ['tgl' => '2026-10-13', 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001', 'idsf' => 'SF001', 'iddenom' => 'SA-1', 'qty' => 50, 'status' => 'rejected', 'created_at' => now(), 'updated_at' => now()],
            ['tgl' => '2026-09-30', 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001', 'idsf' => 'SF001', 'iddenom' => 'PV-1', 'qty' => 99, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->get(route('mobile.index'));

        $response->assertOk()
            ->assertViewHas('todayTotals', fn ($totals) => (int) $totals->pv === 7 && (int) $totals->sa === 3)
            ->assertViewHas('monthTotals', fn ($totals) => (int) $totals->pv === 7 && (int) $totals->sa === 3)
            ->assertSeeInOrder(['Sales Hari Ini', 'PV', '7', 'SA', '3']);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_dashboard_uses_only_the_journey_plan_add_button(): void
    {
        $dashboard = file_get_contents(resource_path('views/mobile/index.blade.php'));

        $this->assertStringNotContainsString('class="hero-action"', $dashboard);
        $this->assertStringContainsString('data-bs-target="#searchOutletModal"', $dashboard);
        $this->assertStringContainsString('<i class="fas fa-plus me-1"></i> TAMBAH', $dashboard);
    }

    public function test_pjp_completion_resets_for_the_current_day(): void
    {
        Carbon::setTestNow('2026-10-13 09:00:00');
        DB::table('denom')->insert([
            'iddenom' => 'PV-1', 'denom' => 'PV 5GB', 'group_name' => '1 HARI', 'harga_jual' => 0,
        ]);
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-HARIAN',
            'nama_outlet' => 'Outlet Harian',
            'sf' => 'Sales Test',
            'pjp' => 'Selasa',
        ]);
        DB::table('mobile_penjualan')->insert([
            'tgl' => '2026-10-12',
            'id_outlet' => 'OUTLET-HARIAN',
            'idtap' => 'TAP001',
            'idsf' => 'SF001',
            'iddenom' => 'PV-1',
            'qty' => 2,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->get(route('mobile.index'))
            ->assertOk()
            ->assertViewHas('pjp_list', fn ($list) => $list->count() === 1 && $list->first()->is_visited === false)
            ->assertSee('0 dari 1 outlet PJP selesai');
    }

    public function test_pjp_list_is_reloaded_from_appsdumais_for_the_current_weekday(): void
    {
        DB::table('appsdumais')->insert([
            ['id_outlet' => 'OUTLET-SENIN', 'nama_outlet' => 'Outlet Senin', 'sf' => ' Sales Test ', 'pjp' => ' SENIN '],
            ['id_outlet' => 'OUTLET-SELASA', 'nama_outlet' => 'Outlet Selasa', 'sf' => 'Sales Test', 'pjp' => 'Selasa'],
        ]);
        $session = ['mobile_sf_id' => 'SF001', 'mobile_sf_name' => 'Sales Test', 'idtap' => 'TAP001'];

        Carbon::setTestNow('2026-10-12 08:00:00');
        $this->withSession($session)->get(route('mobile.index'))
            ->assertOk()
            ->assertSee('Outlet Senin')
            ->assertDontSee('Outlet Selasa');

        Carbon::setTestNow('2026-10-13 08:00:00');
        $this->withSession($session)->get(route('mobile.index'))
            ->assertOk()
            ->assertSee('Outlet Selasa')
            ->assertDontSee('Outlet Senin');
    }

    public function test_history_is_grouped_by_date_and_loads_outlet_details_on_demand(): void
    {
        DB::table('denom')->insert([
            ['iddenom' => 'PV-1', 'denom' => 'PV 5GB', 'group_name' => '1 HARI', 'kategori_inject' => null, 'harga_jual' => 0],
            ['iddenom' => 'SA-1', 'denom' => 'SA BYU', 'group_name' => 'SA', 'kategori_inject' => 'SEGEL', 'harga_jual' => 0],
        ]);
        DB::table('appsdumais')->insert([
            ['id_outlet' => 'OUTLET-1', 'nama_outlet' => 'Outlet Satu', 'sf' => 'Sales Test'],
            ['id_outlet' => 'OUTLET-2', 'nama_outlet' => 'Outlet Dua', 'sf' => 'Sales Test'],
        ]);
        DB::table('mobile_penjualan')->insert([
            ['tgl' => '2026-10-13', 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001', 'idsf' => 'SF001', 'iddenom' => 'PV-1', 'qty' => 4, 'status' => 'pending'],
            ['tgl' => '2026-10-13', 'id_outlet' => 'OUTLET-2', 'idtap' => 'TAP001', 'idsf' => 'SF001', 'iddenom' => 'PV-1', 'qty' => 6, 'status' => 'approved'],
            ['tgl' => '2026-10-13', 'id_outlet' => 'OUTLET-2', 'idtap' => 'TAP001', 'idsf' => 'SF001', 'iddenom' => 'SA-1', 'qty' => 2, 'status' => 'approved'],
            ['tgl' => '2026-10-12', 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001', 'idsf' => 'SF001', 'iddenom' => 'PV-1', 'qty' => 3, 'status' => 'approved'],
            ['tgl' => '2026-10-11', 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001', 'idsf' => 'SF001', 'iddenom' => 'PV-1', 'qty' => 99, 'status' => 'rejected'],
        ]);
        $session = ['mobile_sf_id' => 'SF001', 'mobile_sf_name' => 'Sales Test', 'idtap' => 'TAP001'];

        $response = $this->withSession($session)->get(route('mobile.history'));

        $response->assertOk()
            ->assertViewHas('history', fn ($history) => $history->count() === 2)
            ->assertViewHas('historyDenoms', function ($denoms) {
                $current = $denoms->get('2026-10-13');

                return $current
                    && (int) $current->firstWhere('iddenom', 'PV-1')->total_qty === 10
                    && (int) $current->firstWhere('iddenom', 'SA-1')->total_qty === 2;
            })
            ->assertSee('Detail outlet')
            ->assertSee('data-tgl="2026-10-13"', false);

        $this->withSession($session)->getJson(route('mobile.history.details', ['tgl' => '2026-10-13']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'outlets')
            ->assertJsonFragment(['id_outlet' => 'OUTLET-1', 'nama_outlet' => 'Outlet Satu'])
            ->assertJsonFragment(['produk' => 'SA BYU', 'qty' => 2, 'status' => 'approved']);

        $this->withSession($session)->getJson(route('mobile.history.details', ['tgl' => '2026-10-11']))
            ->assertOk()
            ->assertJsonPath('success', false);
    }

    public function test_completed_outlet_can_create_an_additional_transaction_and_locks_during_receipt_draft(): void
    {
        Carbon::setTestNow('2026-10-13 09:00:00');
        DB::table('denom')->insert([
            'iddenom' => 'PV-1', 'denom' => 'PV 5GB', 'group_name' => '1 HARI', 'harga_jual' => 0,
        ]);
        DB::table('stockawalsf')->insert(['idsf' => 'SF001', 'iddenom' => 'PV-1', 'stock' => 20]);
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-ULANG', 'nama_outlet' => 'Outlet Ulang', 'sf' => 'Sales Test', 'pjp' => 'Selasa',
        ]);
        DB::table('surya_visits')->insert([
            'visited_at' => now(), 'id_outlet' => 'OUTLET-ULANG', 'idtap' => 'TAP001', 'idsf' => 'SF001',
            'latitude' => 1.1, 'longitude' => 101.1, 'photo_path' => 'front.webp',
            'photo_front_path' => 'front.webp', 'photo_inside_path' => 'inside.webp', 'gps_status' => 'valid',
        ]);
        DB::table('mobile_penjualan')->insert([
            'tgl' => '2026-10-13', 'id_outlet' => 'OUTLET-ULANG', 'idtap' => 'TAP001', 'idsf' => 'SF001',
            'iddenom' => 'PV-1', 'qty' => 2, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $session = ['mobile_sf_id' => 'SF001', 'mobile_sf_name' => 'Sales Test', 'idtap' => 'TAP001'];

        $this->withSession($session)->get(route('mobile.index'))
            ->assertOk()
            ->assertSee('INPUT LAGI');

        $this->withSession($session)->post(route('mobile.store'), [
            'id_outlet' => 'OUTLET-ULANG',
            'products' => [['iddenom' => 'PV-1', 'qty' => 4]],
        ])->assertRedirect();

        $this->assertSame(2, DB::table('mobile_penjualan')->where('id_outlet', 'OUTLET-ULANG')->count());
        $this->assertTrue(
            \App\Models\SuryaVisit::query()->inProgressForSalesForce('SF001', '2026-10-13')->exists()
        );
    }

    public function test_outlet_search_only_returns_outlets_assigned_to_the_logged_in_sales_force(): void
    {
        DB::table('appsdumais')->insert([
            ['id_outlet' => 'MATCH-1', 'nama_outlet' => 'Outlet Cocok', 'sf' => 'Sales Test'],
            ['id_outlet' => 'MATCH-2', 'nama_outlet' => 'Outlet Spasi', 'sf' => ' sales test '],
            ['id_outlet' => 'OTHER-1', 'nama_outlet' => 'Outlet Sales Lain', 'sf' => 'Sales Tester'],
        ]);

        $this->withSession([
            'mobile_sf_id' => 'SF001', 'mobile_sf_name' => 'Sales Test', 'idtap' => 'TAP001',
        ])->getJson(route('mobile.search-outlet'))
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id_outlet' => 'MATCH-1'])
            ->assertJsonFragment(['id_outlet' => 'MATCH-2'])
            ->assertJsonMissing(['id_outlet' => 'OTHER-1']);
    }

    public function test_helper_can_search_all_outlets_in_their_tap(): void
    {
        DB::table('idsf')->insert([
            ['idsf' => 'SF-OWNER', 'namasf' => 'Sales Tester', 'idtap' => 'TAP001'],
        ]);
        DB::table('denom')->insert([
            ['iddenom' => 'D-OWNER', 'denom' => 'Produk Pemilik Outlet', 'group_name' => '1 HARI'],
            ['iddenom' => 'D-HELPER', 'denom' => 'Produk Pribadi Helper', 'group_name' => '1 HARI'],
        ]);
        DB::table('stockawalsf')->insert([
            ['idsf' => 'SF-OWNER', 'iddenom' => 'D-OWNER', 'stock' => 9],
            ['idsf' => 'HELPER7', 'iddenom' => 'D-HELPER', 'stock' => 20],
        ]);
        DB::table('mobile_penjualan')->insert([
            [
                'tgl' => now()->toDateString(),
                'id_outlet' => 'HELPER-TAP-2',
                'idtap' => 'TAP001',
                'idsf' => 'HELPER-LAIN',
                'owner_idsf' => 'SF-OWNER',
                'iddenom' => 'D-OWNER',
                'qty' => 2,
                'status' => 'pending',
            ],
            [
                'tgl' => now()->toDateString(),
                'id_outlet' => 'HELPER-TAP-2',
                'idtap' => 'TAP001',
                'idsf' => 'HELPER7',
                'owner_idsf' => 'SF-OWNER',
                'iddenom' => 'D-OWNER',
                'qty' => 100,
                'status' => 'draft_wa',
            ],
        ]);
        DB::table('appsdumais')->insert([
            ['id_outlet' => 'HELPER-TAP-1', 'nama_outlet' => 'Outlet TAP Helper', 'sf' => 'Sales Lain', 'tap' => 'TAP001'],
            ['id_outlet' => 'HELPER-TAP-2', 'nama_outlet' => 'Outlet TAP Lain', 'sf' => 'Sales Tester', 'tap' => 'TAP001'],
            ['id_outlet' => 'OTHER-TAP-1', 'nama_outlet' => 'Outlet TAP Berbeda', 'sf' => 'Sales Lain', 'tap' => 'TAP002'],
        ]);
        DB::table('surya_visits')->insert([
            'visited_at' => now(),
            'id_outlet' => 'HELPER-TAP-2',
            'idtap' => 'TAP001',
            'idsf' => 'HELPER7',
            'latitude' => 1.1,
            'longitude' => 101.1,
            'photo_path' => 'front.webp',
            'photo_front_path' => 'front.webp',
            'photo_inside_path' => 'inside.webp',
            'gps_status' => 'valid',
        ]);

        $session = [
            'mobile_sf_id' => 'HELPER7', 'mobile_sf_name' => 'ARIF PUTRA GANDA', 'idtap' => 'TAP001',
        ];

        $this->withSession($session)->getJson(route('mobile.search-outlet'))
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id_outlet' => 'HELPER-TAP-1'])
            ->assertJsonFragment(['id_outlet' => 'HELPER-TAP-2'])
            ->assertJsonMissing(['id_outlet' => 'OTHER-TAP-1']);

        $this->withSession($session)
            ->get(route('mobile.form', ['id_outlet' => 'HELPER-TAP-2']))
            ->assertOk()
            ->assertSee('Outlet TAP Lain')
            ->assertViewHas('denoms', function ($denoms) {
                $ownerProduct = $denoms->firstWhere('iddenom', 'D-OWNER');

                return $denoms->count() === 1
                    && $ownerProduct
                    && (int) $ownerProduct->stock_qty === 7;
            })
            ->assertSee('Produk Pemilik Outlet')
            ->assertDontSee('Produk Pribadi Helper');

        $this->post(route('mobile.store'), [
            'id_outlet' => 'HELPER-TAP-2',
            'products' => [['iddenom' => 'D-OWNER', 'qty' => 8]],
        ])->assertSessionHas('error', fn ($message) => str_contains($message, 'Stok tersedia di mobile: 7'));
    }

    public function test_helper_receipt_confirmation_uses_the_outlet_owner_stock(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD is required for receipt image processing.');
        }

        Storage::fake('public');
        DB::table('denom')->insert([
            'iddenom' => 'D-OWNER', 'denom' => 'Produk Pemilik Outlet', 'group_name' => '1 HARI',
        ]);
        DB::table('stockawalsf')->insert([
            'idsf' => 'SF-OWNER', 'iddenom' => 'D-OWNER', 'stock' => 10,
        ]);
        $receiptId = DB::table('mobile_sales_receipts')->insertGetId([
            'receipt_number' => 'SUR-HELPER-001',
            'tgl' => now()->toDateString(),
            'id_outlet' => 'HELPER-TAP-2',
            'idtap' => 'TAP001',
            'idsf' => 'HELPER7',
            'total_qty' => 6,
            'total_amount' => 0,
        ]);
        DB::table('mobile_penjualan')->insert([
            'receipt_id' => $receiptId,
            'tgl' => now()->toDateString(),
            'id_outlet' => 'HELPER-TAP-2',
            'idtap' => 'TAP001',
            'idsf' => 'HELPER7',
            'owner_idsf' => 'SF-OWNER',
            'iddenom' => 'D-OWNER',
            'qty' => 6,
            'status' => 'draft_wa',
        ]);

        $this->withSession([
            'mobile_sf_id' => 'HELPER7', 'mobile_sf_name' => 'ARIF PUTRA GANDA', 'idtap' => 'TAP001',
        ])->post(route('mobile.receipt.confirm', $receiptId), [
            'receipt_photo' => UploadedFile::fake()->image('receipt.jpg', 800, 1200),
        ])->assertRedirect(route('mobile.history'));

        $this->assertDatabaseHas('mobile_penjualan', [
            'receipt_id' => $receiptId,
            'idsf' => 'HELPER7',
            'owner_idsf' => 'SF-OWNER',
            'status' => 'pending',
        ]);
        $this->assertSame(10, (int) DB::table('stockawalsf')
            ->where('idsf', 'SF-OWNER')->where('iddenom', 'D-OWNER')->value('stock'));
    }

    public function test_sales_confirmation_hides_whatsapp_and_submits_pending_sale(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD is required for receipt JPEG rendering.');
        }

        DB::table('denom')->insert([
            'iddenom' => 'PV-1', 'denom' => 'PV 5GB 1 HARI', 'group_name' => '1 HARI', 'harga_jual' => 0,
        ]);
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-BON', 'nama_outlet' => 'TOKO CONTOH', 'sf' => 'Sales Test',
        ]);
        $receiptId = DB::table('mobile_sales_receipts')->insertGetId([
            'receipt_number' => 'SUR-TEST-001', 'tgl' => '2026-10-13', 'id_outlet' => 'OUTLET-BON',
            'idtap' => 'TAP001', 'idsf' => 'SF001', 'total_qty' => 6, 'total_amount' => 0,
        ]);
        DB::table('mobile_penjualan')->insert([
            'receipt_id' => $receiptId, 'tgl' => '2026-10-13', 'id_outlet' => 'OUTLET-BON',
            'idtap' => 'TAP001', 'idsf' => 'SF001', 'iddenom' => 'PV-1', 'qty' => 6, 'status' => 'draft_wa',
        ]);
        DB::table('stockawalsf')->insert([
            'idsf' => 'SF001', 'iddenom' => 'PV-1', 'stock' => 10,
        ]);
        $session = ['mobile_sf_id' => 'SF001', 'mobile_sf_name' => 'Sales Test', 'idtap' => 'TAP001'];

        $image = $this->withSession($session)->get(route('mobile.receipt.image', $receiptId));
        $image->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringStartsWith("\xFF\xD8", $image->getContent());

        $this->withSession($session)->get(route('mobile.receipt.show', $receiptId))
            ->assertOk()
            ->assertSee('Konfirmasi Penjualan')
            ->assertSee('PV 5GB 1 HARI')
            ->assertSee('Submit Penjualan')
            ->assertDontSee('WhatsApp')
            ->assertDontSee('PREVIEW NOTA JPEG');

        $this->withSession($session)->post(route('mobile.receipt.confirm', $receiptId))
            ->assertRedirect(route('mobile.history'));

        $this->assertDatabaseHas('mobile_penjualan', [
            'receipt_id' => $receiptId,
            'status' => 'pending',
        ]);
        $this->assertSame(10, (int) DB::table('stockawalsf')->where('idsf', 'SF001')->where('iddenom', 'PV-1')->value('stock'));
        $this->withSession($session)->get(route('mobile.stock'))
            ->assertOk()
            ->assertSee('4');
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

    public function test_admin_can_reject_visit_photos_and_sales_force_can_retake_them(): void
    {
        Carbon::setTestNow('2026-10-16 11:00:00');

        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-RETAKE',
            'nama_outlet' => 'Outlet Retake',
            'sf' => 'Sales Test',
            'latitude' => 1.00000000,
            'longitude' => 101.00000000,
        ]);

        $visitId = DB::table('surya_visits')->insertGetId([
            'visited_at' => now(),
            'id_outlet' => 'OUTLET-RETAKE',
            'idtap' => 'TAP001',
            'idsf' => 'SF001',
            'latitude' => 1.20000000,
            'longitude' => 101.20000000,
            'photo_path' => 'surya-visits/front/wrong.webp',
            'photo_front_path' => 'surya-visits/front/wrong.webp',
            'photo_inside_path' => 'surya-visits/inside/wrong.webp',
            'gps_status' => 'pending_admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(\App\Http\Controllers\SuryaVisitReportController::class)
            ->reject(\App\Models\SuryaVisit::findOrFail($visitId));

        $this->assertDatabaseHas('surya_visits', [
            'id' => $visitId,
            'gps_status' => 'rejected',
        ]);

        $session = ['mobile_sf_id' => 'SF001', 'mobile_sf_name' => 'Sales Test', 'idtap' => 'TAP001'];

        $this->withSession($session)
            ->get(route('mobile.visit.menu', ['id_outlet' => 'OUTLET-RETAKE']))
            ->assertOk()
            ->assertSee('Foto visit ditolak admin')
            ->assertSee('Ambil Ulang 2 Foto Visit');

        $this->withSession($session)
            ->get(route('mobile.visit.status', ['id_outlet' => 'OUTLET-RETAKE']))
            ->assertOk()
            ->assertJson([
                'gps_status' => 'rejected',
                'photo_done' => false,
                'can_sell' => false,
            ]);

        $this->withSession($session)
            ->get(route('mobile.visit', ['id_outlet' => 'OUTLET-RETAKE']))
            ->assertOk();
    }

    public function test_surya_visit_page_is_not_cached_and_visit_export_is_downloadable(): void
    {
        Carbon::setTestNow('2026-10-16 11:00:00');
        DB::table('idsf')->insert([
            'idsf' => 'SF001', 'namasf' => 'Sales Test', 'idtap' => 'TAP001',
        ]);
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-EXPORT', 'nama_outlet' => 'Outlet Export', 'sf' => 'Sales Test',
            'pjp' => 'Jumat', 'latitude' => 1.00000000, 'longitude' => 101.00000000,
        ]);
        DB::table('surya_visits')->insert([
            'visited_at' => now(), 'id_outlet' => 'OUTLET-EXPORT', 'idtap' => 'TAP001', 'idsf' => 'SF001',
            'latitude' => 1.10000000, 'longitude' => 101.10000000, 'distance_meters' => 15000,
            'gps_status' => 'pending_admin', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $session = ['monita_leader_auth' => true];
        $dashboardResponse = $this->withSession($session)
            ->get(route('surya.dashboard', ['panel' => 'visit-detail', 'visit_date' => '2026-10-16']))
            ->assertOk()
            ->assertSee('data-force-refresh', false)
            ->assertSee('Download Excel');
        $this->assertStringContainsString('no-store', (string) $dashboardResponse->headers->get('Cache-Control'));

        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->withSession($session)
            ->get(route('surya.visits.export', ['visit_date' => '2026-10-16']))
            ->assertOk();

        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('surya-visit-2026-10-16.xlsx', function ($export) {
            $row = $export->collection()->first();

            return $row[7] === 'OUTLET-EXPORT'
                && $row[15] === 'TIDAK SESUAI'
                && $row[16] === 'PENDING ADMIN';
        });
    }

    public function test_sales_force_can_checkout_without_sale_and_continue_to_next_outlet(): void
    {
        Carbon::setTestNow('2026-10-15 10:00:00');

        DB::table('surya_visits')->insert([
            'visited_at' => now(),
            'id_outlet' => 'OUTLET-TUTUP',
            'idtap' => 'TAP001',
            'idsf' => 'SF001',
            'latitude' => 1.23456789,
            'longitude' => 101.98765432,
            'photo_front_path' => 'surya-visits/front/test.webp',
            'photo_inside_path' => 'surya-visits/inside/test.webp',
            'gps_status' => 'valid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $session = ['mobile_sf_id' => 'SF001', 'mobile_sf_name' => 'Sales Test', 'idtap' => 'TAP001'];

        $this->withSession($session)
            ->post(route('mobile.visit.checkout'), [
                'id_outlet' => 'OUTLET-TUTUP',
                'checkout_reason' => 'outlet_tutup',
                'checkout_notes' => 'Rolling door tertutup.',
            ])
            ->assertRedirect(route('mobile.index'));

        $this->assertDatabaseHas('surya_visits', [
            'id_outlet' => 'OUTLET-TUTUP',
            'idsf' => 'SF001',
            'checkout_reason' => 'outlet_tutup',
            'checkout_notes' => 'Rolling door tertutup.',
        ]);
        $this->assertNotNull(DB::table('surya_visits')->where('id_outlet', 'OUTLET-TUTUP')->value('checkout_at'));
        $this->assertFalse(\App\Models\SuryaVisit::query()->inProgressForSalesForce('SF001')->exists());
    }

    public function test_admin_setoran_is_grouped_and_stock_is_deducted_only_on_approval(): void
    {
        Carbon::setTestNow('2026-10-14 17:00:00');
        DB::table('idsf')->insert([
            'idsf' => 'SF001', 'namasf' => 'Sales Test', 'idtap' => 'TAP001',
        ]);
        DB::table('denom')->insert([
            'iddenom' => 'D001', 'denom' => 'PV 5GB', 'group_name' => '1 HARI', 'harga_jual' => 0,
        ]);
        DB::table('stockawalsf')->insert([
            'idsf' => 'SF001', 'iddenom' => 'D001', 'stock' => 100,
        ]);
        DB::table('mobile_penjualan')->insert([
            [
                'id' => 101, 'receipt_id' => 501, 'tgl' => '2026-10-14', 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001',
                'idsf' => 'SF001', 'iddenom' => 'D001', 'qty' => 20, 'status' => 'pending',
                'stock_reserved_at' => null,
            ],
            [
                'id' => 102, 'receipt_id' => 501, 'tgl' => '2026-10-14', 'id_outlet' => 'OUTLET-2', 'idtap' => 'TAP001',
                'idsf' => 'SF001', 'iddenom' => 'D001', 'qty' => 10, 'status' => 'pending',
                'stock_reserved_at' => null,
            ],
        ]);
        DB::table('appsdumais')->insert([
            'id_outlet' => 'OUTLET-1', 'nama_outlet' => 'Outlet Konsinyasi', 'sf' => 'Sales Test',
        ]);
        DB::table('surya_sa_consignment_numbers')->insert([
            [
                'receipt_id' => 501, 'reported_at' => now(), 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001',
                'idsf' => 'SF001', 'serial_number' => '6281234567890', 'status' => 'consigned',
            ],
            [
                'receipt_id' => 501, 'reported_at' => now(), 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001',
                'idsf' => 'SF001', 'serial_number' => '6281234567891', 'status' => 'consigned',
            ],
        ]);
        session(['idtap' => 'TAP001']);
        $controller = app(\App\Http\Controllers\MobileApprovalController::class);

        $detail = $controller->getSfDetails(new Request([
            'idsf' => 'SF001', 'status' => 'pending', 'idtap' => 'TAP001', 'jenis' => 'VOUCHER FISIK',
        ]))->getData(true);

        $this->assertSame(1, $detail['count']);
        $this->assertSame(2, $detail['transaction_count']);
        $this->assertSame([101, 102], $detail['items'][0]['ids']);
        $this->assertSame('30', $detail['items'][0]['qty']);
        $this->assertArrayNotHasKey('outlet', $detail['items'][0]);
        $this->assertSame(2, $detail['consignment']['count']);
        $this->assertSame('Outlet Konsinyasi', $detail['consignment']['outlets'][0]['nama_outlet']);
        $this->assertSame(['6281234567890', '6281234567891'], $detail['consignment']['outlets'][0]['numbers']);

        $approval = $controller->bulkApprove(new Request(['ids' => [101, 102]]))->getData(true);
        $this->assertTrue($approval['success'], json_encode($approval));
        $this->assertSame(70, (int) DB::table('stockawalsf')->where('idsf', 'SF001')->where('iddenom', 'D001')->value('stock'));
        $this->assertSame(30, (int) DB::table('keluarsf')->where('idsf', 'SF001')->where('iddenom', 'D001')->sum('qty'));
        $this->assertSame(2, DB::table('mobile_penjualan')->where('status', 'approved')->count());
        $this->assertSame(2, DB::table('surya_sa_consignment_numbers')->where('status', 'approved')->count());
    }

    public function test_admin_approval_deducts_helper_sale_from_the_outlet_owner_stock(): void
    {
        DB::table('denom')->insert([
            'iddenom' => 'D-OWNER', 'denom' => 'Produk Pemilik Outlet', 'group_name' => '1 HARI',
        ]);
        DB::table('stockawalsf')->insert([
            'idsf' => 'SF-OWNER', 'iddenom' => 'D-OWNER', 'stock' => 10,
        ]);
        DB::table('mobile_penjualan')->insert([
            'id' => 601,
            'tgl' => now()->toDateString(),
            'id_outlet' => 'HELPER-TAP-2',
            'idtap' => 'TAP001',
            'idsf' => 'HELPER7',
            'owner_idsf' => 'SF-OWNER',
            'iddenom' => 'D-OWNER',
            'qty' => 6,
            'status' => 'pending',
        ]);
        session(['idtap' => 'TAP001']);

        $response = app(\App\Http\Controllers\MobileApprovalController::class)
            ->approve(new Request(['id' => 601]))
            ->getData(true);

        $this->assertTrue($response['success'], $response['message']);
        $this->assertSame(4, (int) DB::table('stockawalsf')
            ->where('idsf', 'SF-OWNER')->where('iddenom', 'D-OWNER')->value('stock'));
        $this->assertDatabaseHas('keluarsf', [
            'idsf' => 'SF-OWNER',
            'id_outlet' => 'HELPER-TAP-2',
            'iddenom' => 'D-OWNER',
            'qty' => 6,
        ]);
        $this->assertDatabaseHas('mobile_penjualan', [
            'id' => 601,
            'idsf' => 'HELPER7',
            'owner_idsf' => 'SF-OWNER',
            'status' => 'approved',
        ]);
    }

    public function test_admin_daily_recap_filters_sales_by_transaction_date(): void
    {
        DB::table('idsf')->insert([
            'idsf' => 'SF001', 'namasf' => 'Sales Test', 'idtap' => 'TAP001',
        ]);
        DB::table('denom')->insert([
            'iddenom' => 'D001', 'denom' => 'PV 5GB', 'group_name' => '1 HARI', 'harga_jual' => 1000,
        ]);
        DB::table('mobile_penjualan')->insert([
            ['id' => 301, 'tgl' => '2026-10-14', 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001', 'idsf' => 'SF001', 'iddenom' => 'D001', 'qty' => 4, 'status' => 'pending'],
            ['id' => 302, 'tgl' => '2026-10-13', 'id_outlet' => 'OUTLET-2', 'idtap' => 'TAP001', 'idsf' => 'SF001', 'iddenom' => 'D001', 'qty' => 9, 'status' => 'pending'],
        ]);
        session(['idtap' => 'TAP001']);
        $controller = app(\App\Http\Controllers\MobileApprovalController::class);

        $stats = $controller->getStats(new Request(['date' => '2026-10-14']))->getData(true);
        $details = $controller->getSfDetails(new Request([
            'idsf' => 'SF001',
            'status' => 'pending',
            'idtap' => 'TAP001',
            'jenis' => 'VOUCHER FISIK',
            'date' => '2026-10-14',
        ]))->getData(true);

        $this->assertSame('1 Data', $stats['count']);
        $this->assertSame('Rp 4.000', $stats['pending_amount']);
        $this->assertSame('2026-10-14', $stats['date']);
        $this->assertSame(1, $details['transaction_count']);
        $this->assertSame([301], $details['items'][0]['ids']);
        $this->assertSame('4', $details['items'][0]['qty']);

        $adminView = file_get_contents(resource_path('views/admin/mobile-approval.blade.php'));
        $this->assertStringContainsString('id="approval-date"', $adminView);
        $this->assertStringContainsString("d.date = selectedDate", $adminView);
    }

    public function test_rejecting_pending_sale_does_not_change_physical_stock(): void
    {
        DB::table('stockawalsf')->insert([
            'idsf' => 'SF001', 'iddenom' => 'D001', 'stock' => 7,
        ]);
        DB::table('mobile_sales_receipts')->insert([
            'id' => 91, 'receipt_number' => 'SUR-REJECT-001', 'tgl' => '2026-10-14',
            'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001', 'idsf' => 'SF001',
            'total_qty' => 8, 'total_amount' => 13000,
        ]);
        DB::table('mobile_penjualan')->insert([
            [
                'id' => 201, 'receipt_id' => 91, 'tgl' => '2026-10-14', 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001',
                'idsf' => 'SF001', 'iddenom' => 'D001', 'qty' => 3, 'unit_price' => 1000, 'status' => 'pending',
                'stock_reserved_at' => null,
            ],
            [
                'id' => 202, 'receipt_id' => 91, 'tgl' => '2026-10-14', 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001',
                'idsf' => 'SF001', 'iddenom' => 'D002', 'qty' => 5, 'unit_price' => 2000, 'status' => 'pending',
                'stock_reserved_at' => null,
            ],
        ]);
        DB::table('surya_sa_consignment_numbers')->insert([
            'receipt_id' => 91, 'reported_at' => now(), 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001',
            'idsf' => 'SF001', 'serial_number' => '628111111111', 'status' => 'consigned',
        ]);
        session(['idtap' => 'TAP001']);

        $result = app(\App\Http\Controllers\MobileApprovalController::class)
            ->bulkReject(new Request(['ids' => [201]]))
            ->getData(true);

        $this->assertTrue($result['success'], json_encode($result));
        $this->assertSame(7, (int) DB::table('stockawalsf')->where('idsf', 'SF001')->where('iddenom', 'D001')->value('stock'));
        $this->assertDatabaseMissing('mobile_penjualan', ['id' => 201]);
        $this->assertDatabaseHas('mobile_penjualan', ['id' => 202, 'status' => 'pending']);
        $this->assertDatabaseHas('mobile_sales_receipts', ['id' => 91, 'total_qty' => 5, 'total_amount' => 10000]);
        $this->assertDatabaseHas('surya_sa_consignment_numbers', ['receipt_id' => 91]);

        $secondResult = app(\App\Http\Controllers\MobileApprovalController::class)
            ->bulkReject(new Request(['ids' => [202]]))
            ->getData(true);

        $this->assertTrue($secondResult['success'], json_encode($secondResult));
        $this->assertDatabaseMissing('mobile_sales_receipts', ['id' => 91]);
        $this->assertDatabaseMissing('surya_sa_consignment_numbers', ['receipt_id' => 91]);
    }

    public function test_stock_transition_releases_old_pending_physical_reservations_once(): void
    {
        DB::table('stockawalsf')->insert([
            'idsf' => 'SF001', 'iddenom' => 'D001', 'stock' => 7,
        ]);
        DB::table('mobile_penjualan')->insert([
            'tgl' => '2026-10-16', 'id_outlet' => 'OUTLET-1', 'idtap' => 'TAP001',
            'idsf' => 'SF001', 'iddenom' => 'D001', 'qty' => 3, 'status' => 'pending',
            'stock_reserved_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_10_16_000001_release_pending_mobile_stock_reservations.php');
        $migration->up();
        $migration->up();

        $this->assertSame(10, (int) DB::table('stockawalsf')->where('idsf', 'SF001')->where('iddenom', 'D001')->value('stock'));
        $this->assertNull(DB::table('mobile_penjualan')->value('stock_reserved_at'));
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
            $table->string('product_group')->nullable();
            $table->string('kategori_inject')->nullable();
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
            $table->string('receipt_photo_path')->nullable();
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
            $table->string('owner_idsf', 25)->nullable();
            $table->string('iddenom', 25);
            $table->integer('qty');
            $table->unsignedInteger('unit_price')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('stock_reserved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('surya_sa_consignment_numbers', function ($table) {
            $table->id();
            $table->unsignedBigInteger('receipt_id')->nullable();
            $table->dateTime('reported_at');
            $table->string('id_outlet', 100);
            $table->string('idtap', 25);
            $table->string('idsf', 25);
            $table->string('serial_number', 30);
            $table->string('status', 20)->default('consigned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('appsdumais', function ($table) {
            $table->string('id_outlet');
            $table->string('nama_outlet');
            $table->string('sf')->nullable();
            $table->string('tap')->nullable();
            $table->string('pjp')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
        });

        Schema::create('surya_visits', function ($table) {
            $table->id();
            $table->dateTime('visited_at');
            $table->dateTime('checkout_at')->nullable();
            $table->string('checkout_reason', 80)->nullable();
            $table->text('checkout_notes')->nullable();
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
            $table->string('idtap')->nullable();
            $table->string('idsf');
            $table->string('id_outlet')->nullable();
            $table->string('iddenom');
            $table->integer('qty')->default(0);
            $table->text('tambahanket')->nullable();
        });

        Schema::create('keluar', function ($table) {
            $table->id();
            $table->string('penerima')->nullable();
            $table->integer('status')->default(0);
        });
    }
}
