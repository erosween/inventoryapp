<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KpiSvpEntriesTest extends TestCase
{
    public function test_only_super_admin_can_save_and_all_surya_users_can_read_individual_cutoffs(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
        DB::purge('sqlite');
        \Illuminate\Support\Facades\Schema::create('keluar', function ($table) {
            $table->integer('status');
            $table->string('penerima')->nullable();
        });
        (require database_path('migrations/2026_11_11_000001_create_surya_kpi_svp_entries.php'))->up();

        $url = route('surya.kpi-svp.entries');
        $row = ['period' => '2026-11', 'region' => 'dumai', 'segment' => 'mobile', 'metric' => 0,
            'target' => 300, 'actual' => 120, 'actual_date' => '2026-11-10'];
        $reader = new User(['username' => 'asmen_dumai']);
        $this->actingAs($reader)->putJson($url, $row)->assertForbidden();
        $this->get(route('surya.kpi-svp'))->assertOk()->assertDontSee('svp-inline-form', false);

        $this->actingAs(new User(['username' => 'admin_super']));
        $this->get(route('surya.kpi-svp'))->assertOk()->assertSee('svp-inline-form', false);
        $this->putJson($url, array_replace($row, ['actual_date' => '2026-10-31']))->assertUnprocessable();
        $this->putJson($url, array_replace($row, ['actual_date' => null]))->assertUnprocessable();
        $this->putJson($url, $row)->assertOk();
        $this->putJson($url, array_replace($row, ['metric' => 1, 'actual_date' => '2026-11-20']))->assertOk();
        $this->actingAs($reader)->getJson($url.'?period=2026-12')->assertOk()
            ->assertJsonFragment(['metric' => 0, 'actual_date' => '2026-11-10'])
            ->assertJsonFragment(['metric' => 1, 'actual_date' => '2026-11-20']);
    }
}
