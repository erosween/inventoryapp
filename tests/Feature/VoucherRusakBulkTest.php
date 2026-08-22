<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class VoucherRusakBulkTest extends TestCase
{
    use DatabaseTransactions;

    public function test_form_shows_ready_stock_and_stock_endpoint_returns_tap_balances(): void
    {
        [$admin, $tap, $stocks] = $this->fixture(1);
        $stock = $stocks->first();

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->get('/form/form-vrusak')
            ->assertOk()
            ->assertSee('Stok Ready');

        $this->postJson('/vrusak/stock-tap', ['idtap' => $tap])
            ->assertOk()
            ->assertJsonPath($stock->iddenom, (int) $stock->stock);
    }

    public function test_bulk_voucher_rusak_reduces_tap_stock_and_creates_each_record(): void
    {
        [$admin, $tap, $stocks] = $this->fixture();
        $prefix = 'UJI-RUSAK-' . Str::random(8);

        $items = $stocks->values()->map(fn ($stock, $index) => [
            'iddenom' => $stock->iddenom,
            'qty' => $index + 1,
            'sn' => $prefix . '-' . ($index + 1),
            'ketvf' => $index === 0 ? 'RUSAK' : 'MATI',
            'tambahanket' => 'Pengujian voucher rusak massal',
        ])->all();

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->post('/vrusak', [
                'tgl' => now()->toDateString(),
                'pengirim' => $tap,
                'items' => $items,
            ])
            ->assertRedirect('vrusak')
            ->assertSessionHasNoErrors();

        foreach ($stocks->values() as $index => $stock) {
            $this->assertSame(
                (int) $stock->stock - ($index + 1),
                (int) DB::table('stockawaltap')
                    ->where('idtap', $tap)
                    ->where('iddenom', $stock->iddenom)
                    ->value('stock')
            );
            $this->assertDatabaseHas('returvfrusak', [
                'idtap' => $tap,
                'iddenom' => $stock->iddenom,
                'qty' => $index + 1,
                'sn' => $prefix . '-' . ($index + 1),
            ]);
        }
    }

    public function test_bulk_voucher_rusak_rolls_back_all_rows_when_total_stock_is_insufficient(): void
    {
        [$admin, $tap, $stocks] = $this->fixture();
        $stock = $stocks->first();
        $sn = 'UJI-RUSAK-GAGAL-' . Str::random(8);

        $response = $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->from('/form/form-vrusak')
            ->post('/vrusak', [
                'tgl' => now()->toDateString(),
                'pengirim' => $tap,
                'items' => [
                    [
                        'iddenom' => $stock->iddenom,
                        'qty' => 1,
                        'sn' => $sn . '-1',
                        'ketvf' => 'RUSAK',
                        'tambahanket' => 'Uji rollback',
                    ],
                    [
                        'iddenom' => $stock->iddenom,
                        'qty' => (int) $stock->stock,
                        'sn' => $sn . '-2',
                        'ketvf' => 'MATI',
                        'tambahanket' => 'Uji rollback',
                    ],
                ],
            ])
            ->assertRedirect('/form/form-vrusak')
            ->assertSessionHasErrors('items');

        $denomName = DB::table('denom')->where('iddenom', $stock->iddenom)->value('denom');
        $this->assertStringContainsString(
            "Stok denom {$denomName} ({$stock->iddenom}) tidak mencukupi",
            $response->getSession()->get('errors')->first('items')
        );

        $this->get('/form/form-vrusak')
            ->assertOk()
            ->assertSee($sn . '-1')
            ->assertSee($sn . '-2')
            ->assertSee($stock->iddenom)
            ->assertSee('Uji rollback');

        $this->assertSame(
            (int) $stock->stock,
            (int) DB::table('stockawaltap')
                ->where('idtap', $tap)
                ->where('iddenom', $stock->iddenom)
                ->value('stock')
        );
        $this->assertSame(0, DB::table('returvfrusak')->where('sn', 'like', $sn . '%')->count());
    }

    public function test_voucher_rusak_requires_valid_status_and_description(): void
    {
        [$admin, $tap, $stocks] = $this->fixture(1);

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->from('/form/form-vrusak')
            ->post('/vrusak', [
                'tgl' => now()->toDateString(),
                'pengirim' => $tap,
                'items' => [[
                    'iddenom' => $stocks->first()->iddenom,
                    'qty' => 1,
                    'sn' => 'UJI-VALIDASI-' . Str::random(8),
                    'ketvf' => 'HILANG',
                    'tambahanket' => '',
                ]],
            ])
            ->assertRedirect('/form/form-vrusak')
            ->assertSessionHasErrors(['items.0.ketvf', 'items.0.tambahanket']);
    }

    private function fixture(int $count = 2): array
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $tap = DB::table('stockawaltap')
            ->where('stock', '>=', 2)
            ->groupBy('idtap')
            ->havingRaw('COUNT(*) >= ?', [$count])
            ->value('idtap');
        if (! $tap) {
            $this->markTestSkipped('Tidak ada TAP dengan stok yang cukup untuk pengujian.');
        }

        $stocks = DB::table('stockawaltap')
            ->where('idtap', $tap)
            ->where('stock', '>=', 2)
            ->orderBy('iddenom')
            ->limit($count)
            ->get(['iddenom', 'stock']);

        return [$admin, $tap, $stocks];
    }
}
