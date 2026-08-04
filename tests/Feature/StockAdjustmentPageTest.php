<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockAdjustmentPageTest extends TestCase
{
    public function test_admin_super_can_render_stock_adjustment_page(): void
    {
        $admin = User::where('username', 'admin_super')->first();

        if (!$admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/stock-adjustment')
            ->assertOk()
            ->assertSee('Penyesuaian Stok')
            ->assertSee('Petugas / Sales')
            ->assertSee('Qty Saat Ini')
            ->assertSee('Qty Baru')
            ->assertSee('Cari Denom')
            ->assertSee('denom_search', false)
            ->assertDontSee('Pilih denom');
    }

    public function test_admin_super_can_load_all_current_stocks_for_a_target(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        $tap = DB::table('kodetap')->value('idtap');
        $denom = DB::table('denom')->value('iddenom');

        if (!$admin || !$tap || !$denom) {
            $this->markTestSkipped('Data admin_super, TAP, atau denom belum tersedia.');
        }

        $this->actingAs($admin)
            ->withSession(['idtap' => $tap])
            ->getJson('/stock-adjustment/current?target_type=tap&target_id=' . urlencode($tap))
            ->assertOk()
            ->assertJsonStructure(['stocks' => [$denom]]);
    }
}
