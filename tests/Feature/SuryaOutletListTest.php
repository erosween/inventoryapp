<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuryaOutletListTest extends TestCase
{
    use DatabaseTransactions;

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
            ->assertSee('id="sidebar-toggle"', false)
            ->assertSee('id="sidebar-close"', false)
            ->assertSee('sidebar-collapsed');

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
}
