<?php

namespace Tests\Feature;

use App\Models\User;
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
            ->assertSee('Petugas / Sales');
    }
}
