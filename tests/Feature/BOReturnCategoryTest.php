<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BOReturnCategoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_bo_return_supports_all_sealed_product_categories(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Fixture admin_super belum tersedia.');
        }

        $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/form/formkeluarbo')
            ->assertOk()
            ->assertSee('PV SEGEL')
            ->assertSee('PV BYU SEGEL')
            ->assertSee('PERDANA SEGEL SIMPATI')
            ->assertSee('PERDANA SEGEL BYU');
    }

    public function test_bo_return_moves_starter_pack_stock_to_a_new_tap_stock_row(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Fixture admin_super belum tersedia.');
        }

        DB::table('stockawalsf')->updateOrInsert(
            ['idsf' => 'BO DUMAI', 'iddenom' => 'SP6'],
            ['stock' => 10]
        );
        DB::table('stockawaltap')->where('idtap', 'DUMAI')->where('iddenom', 'SP6')->delete();

        $this->actingAs($admin)
            ->withSession(['idtap' => 'SBP_DUMAI'])
            ->post(route('bo.store'), [
                'pengirim' => 'BO DUMAI',
                'penerima' => 'DUMAI',
                'iddenom' => 'SP6',
                'qty' => 4,
                'tgl' => now()->toDateString(),
                'sn' => 'SP6-TEST',
                'tambahanket' => 'Retur kategori perdana',
            ])
            ->assertRedirect('bo')
            ->assertSessionHas('success');

        $this->assertSame(6, (int) DB::table('stockawalsf')->where('idsf', 'BO DUMAI')->where('iddenom', 'SP6')->value('stock'));
        $this->assertSame(4, (int) DB::table('stockawaltap')->where('idtap', 'DUMAI')->where('iddenom', 'SP6')->value('stock'));
        $this->assertDatabaseHas('keluar', [
            'pengirim' => 'BO DUMAI',
            'penerima' => 'DUMAI',
            'iddenom' => 'SP6',
            'qty' => 4,
        ]);
    }
}
