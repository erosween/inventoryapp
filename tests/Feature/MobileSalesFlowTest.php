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

        $this->withSession([
            'mobile_sf_id' => 'SF001',
            'mobile_sf_name' => 'Sales Test',
            'idtap' => 'TAP001',
        ])->post(route('mobile.store'), [
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
        ])
            ->assertRedirect(route('mobile.history'));

        $this->assertDatabaseHas('mobile_penjualan', [
            'tgl' => '2026-04-29',
            'id_outlet' => 'OUTLET-1',
            'idsf' => 'SF001',
            'iddenom' => 'D001',
            'qty' => 3,
            'latitude' => -6.20000000,
            'longitude' => 106.81666600,
        ]);

        $this->assertDatabaseMissing('mobile_penjualan', [
            'tgl' => '2026-04-01',
            'id_outlet' => 'OUTLET-1',
        ]);
    }

    private function createMobileSchema(): void
    {
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

        Schema::create('mobile_penjualan', function ($table) {
            $table->id();
            $table->date('tgl');
            $table->string('id_outlet')->nullable();
            $table->string('idtap', 25);
            $table->string('idsf', 25);
            $table->string('iddenom', 25);
            $table->integer('qty');
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
