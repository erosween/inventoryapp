<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockBulkFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_bulk_stock_input_pages_render(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        foreach ([
            '/form/formkeluartap',
            '/form/form-sfmasuk',
            '/form/form-sfkeluar',
            '/form/form-retursf',
            '/form/forminject',
        ] as $path) {
            $this->actingAs($admin)
                ->withSession(['idtap' => 'SBP_DUMAI'])
                ->get($path)
                ->assertOk()
                ->assertSee('Daftar Denom');
        }
    }

    public function test_bulk_inject_pv_converts_segel_into_multiple_denoms_atomically(): void
    {
        [$admin, $tap, $sourceStock, $destinations] = $this->injectPvFixture();
        $beforeDestinations = $destinations->pluck('stock', 'iddenom');

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->post('/form/forminject', [
                'tgl' => now()->toDateString(),
                'idtap' => $tap,
                'items' => $destinations->values()->map(fn ($stock, $index) => [
                    'iddenom' => $stock->iddenom,
                    'qty' => $index + 1,
                    'sn' => 'UJI-INJECT-' . ($index + 1),
                ])->all(),
            ])->assertRedirect('injectvf');

        $totalQty = 3;
        $this->assertSame(
            $sourceStock - $totalQty,
            (int) DB::table('stockawaltap')->where('idtap', $tap)->where('iddenom', 'SEGEL')->value('stock')
        );
        foreach ($destinations->values() as $index => $stock) {
            $this->assertSame(
                (int) $beforeDestinations[$stock->iddenom] + $index + 1,
                (int) DB::table('stockawaltap')->where('idtap', $tap)->where('iddenom', $stock->iddenom)->value('stock')
            );
            $this->assertDatabaseHas('injectvf', [
                'idtap' => $tap,
                'iddenom' => $stock->iddenom,
                'qty' => $index + 1,
                'kategori' => 'SEGEL',
            ]);
        }
    }

    public function test_failed_bulk_inject_pv_does_not_change_any_stock(): void
    {
        [$admin, $tap, $sourceStock, $destinations] = $this->injectPvFixture();
        $beforeDestinations = $destinations->pluck('stock', 'iddenom');

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->from('/form/forminject')
            ->post('/form/forminject', [
                'tgl' => now()->toDateString(),
                'idtap' => $tap,
                'items' => [
                    ['iddenom' => $destinations[0]->iddenom, 'qty' => 1, 'sn' => 'UJI-GAGAL-1'],
                    ['iddenom' => $destinations[1]->iddenom, 'qty' => $sourceStock, 'sn' => 'UJI-GAGAL-2'],
                ],
            ])->assertRedirect('/form/forminject')
            ->assertSessionHasErrors('items');

        $this->assertSame(
            $sourceStock,
            (int) DB::table('stockawaltap')->where('idtap', $tap)->where('iddenom', 'SEGEL')->value('stock')
        );
        foreach ($destinations as $stock) {
            $this->assertSame(
                (int) $beforeDestinations[$stock->iddenom],
                (int) DB::table('stockawaltap')->where('idtap', $tap)->where('iddenom', $stock->iddenom)->value('stock')
            );
        }
    }

    public function test_bulk_sf_keluar_decrements_each_denom_once(): void
    {
        [$admin, $sales, $stocks] = $this->bulkFixture('stockawalsf', 'idsf');

        $items = $stocks->map(fn ($row) => [
            'iddenom' => $row->iddenom,
            'qty' => 1,
            'tambahanket' => 'Uji bulk otomatis',
        ])->all();

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->post('/sf-keluar', [
                'tgl' => now()->toDateString(),
                'idtap' => $sales->idtap,
                'idsf' => $sales->idsf,
                'items' => $items,
            ])->assertRedirect('sf-keluar');

        foreach ($stocks as $stock) {
            $this->assertSame(
                (int) $stock->stock - 1,
                (int) DB::table('stockawalsf')
                    ->where('idsf', $sales->idsf)
                    ->where('iddenom', $stock->iddenom)
                    ->value('stock')
            );
        }
    }

    public function test_failed_bulk_sf_keluar_does_not_change_any_stock(): void
    {
        [$admin, $sales, $stocks] = $this->bulkFixture('stockawalsf', 'idsf');

        $items = [
            ['iddenom' => $stocks[0]->iddenom, 'qty' => 1],
            ['iddenom' => $stocks[1]->iddenom, 'qty' => (int) $stocks[1]->stock + 1],
        ];

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->from('/form/form-sfkeluar')
            ->post('/sf-keluar', [
                'tgl' => now()->toDateString(),
                'idtap' => $sales->idtap,
                'idsf' => $sales->idsf,
                'items' => $items,
            ])->assertRedirect('/form/form-sfkeluar')
            ->assertSessionHasErrors('items');

        foreach ($stocks as $stock) {
            $this->assertSame(
                (int) $stock->stock,
                (int) DB::table('stockawalsf')
                    ->where('idsf', $sales->idsf)
                    ->where('iddenom', $stock->iddenom)
                    ->value('stock')
            );
        }
    }

    public function test_backdated_sf_keluar_cannot_use_stock_received_on_a_later_date(): void
    {
        [$admin, $sales, $stocks] = $this->bulkFixture('stockawalsf', 'idsf');
        $stock = $stocks->first();
        $receivedDate = now()->toDateString();
        $saleDate = now()->subDay()->toDateString();
        $qty = 6;

        DB::table('masuksf')->where('idsf', $sales->idsf)->where('iddenom', $stock->iddenom)->delete();
        DB::table('keluarsf')->where('idsf', $sales->idsf)->where('iddenom', $stock->iddenom)->delete();
        DB::table('retursf')->where('idsf', $sales->idsf)->where('iddenom', $stock->iddenom)->delete();
        DB::table('masuk')->where('pengirim', 'DO')->where('penerima', $sales->idsf)->where('iddenom', $stock->iddenom)->delete();
        DB::table('keluar')->where('pengirim', $sales->idsf)->where('iddenom', $stock->iddenom)->delete();

        DB::table('stockawalsf')
            ->where('idsf', $sales->idsf)
            ->where('iddenom', $stock->iddenom)
            ->update(['stock' => $qty]);
        DB::table('masuksf')->insert([
            'idtap' => $sales->idtap,
            'idsf' => $sales->idsf,
            'iddenom' => $stock->iddenom,
            'qty' => $qty,
            'sn' => 'UJI-HISTORICAL-STOCK',
            'tgl' => $receivedDate,
        ]);

        $beforeCount = DB::table('keluarsf')->count();
        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->from('/form/form-sfkeluar')
            ->post('/sf-keluar', [
                'tgl' => $saleDate,
                'idtap' => $sales->idtap,
                'idsf' => $sales->idsf,
                'items' => [[
                    'iddenom' => $stock->iddenom,
                    'qty' => $qty,
                    'tambahanket' => 'Uji saldo historis',
                ]],
            ])
            ->assertRedirect('/form/form-sfkeluar')
            ->assertSessionHasErrors('items');

        $this->assertSame($beforeCount, DB::table('keluarsf')->count());
        $this->assertSame(
            $qty,
            (int) DB::table('stockawalsf')
                ->where('idsf', $sales->idsf)
                ->where('iddenom', $stock->iddenom)
                ->value('stock')
        );
    }

    public function test_same_denom_can_be_repeated_and_uses_combined_quantity(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        $stock = DB::table('stockawalsf as st')
            ->join('idsf as s', 's.idsf', '=', 'st.idsf')
            ->where('st.stock', '>=', 2)
            ->select('st.idsf', 'st.iddenom', 'st.stock', 's.idtap')
            ->first();

        if (! $admin || ! $stock) {
            $this->markTestSkipped('Fixture denom berulang tidak tersedia.');
        }

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->post('/sf-keluar', [
                'tgl' => now()->toDateString(),
                'idtap' => $stock->idtap,
                'idsf' => $stock->idsf,
                'items' => [
                    ['iddenom' => $stock->iddenom, 'qty' => 1],
                    ['iddenom' => $stock->iddenom, 'qty' => 1],
                ],
            ])->assertRedirect('sf-keluar');

        $this->assertSame(
            (int) $stock->stock - 2,
            (int) DB::table('stockawalsf')
                ->where('idsf', $stock->idsf)
                ->where('iddenom', $stock->iddenom)
                ->value('stock')
        );
    }

    public function test_repeated_denom_cannot_exceed_combined_available_stock(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        $stock = DB::table('stockawalsf as st')
            ->join('idsf as s', 's.idsf', '=', 'st.idsf')
            ->where('st.stock', '>', 0)
            ->select('st.idsf', 'st.iddenom', 'st.stock', 's.idtap')
            ->first();

        if (! $admin || ! $stock) {
            $this->markTestSkipped('Fixture denom berulang tidak tersedia.');
        }

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->from('/form/form-sfkeluar')
            ->post('/sf-keluar', [
                'tgl' => now()->toDateString(),
                'idtap' => $stock->idtap,
                'idsf' => $stock->idsf,
                'items' => [
                    ['iddenom' => $stock->iddenom, 'qty' => (int) $stock->stock],
                    ['iddenom' => $stock->iddenom, 'qty' => 1],
                ],
            ])->assertRedirect('/form/form-sfkeluar')
            ->assertSessionHasErrors('items');

        $this->assertSame(
            (int) $stock->stock,
            (int) DB::table('stockawalsf')
                ->where('idsf', $stock->idsf)
                ->where('iddenom', $stock->iddenom)
                ->value('stock')
        );
    }

    public function test_bulk_sf_masuk_preserves_total_stock(): void
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $tap = DB::table('stockawaltap')
            ->where('stock', '>', 0)
            ->select('idtap')
            ->groupBy('idtap')
            ->havingRaw('COUNT(*) >= 2')
            ->first();
        $sales = $tap ? DB::table('idsf')->where('idtap', $tap->idtap)->first() : null;
        if (! $tap || ! $sales) {
            $this->markTestSkipped('Fixture stok TAP/petugas tidak tersedia.');
        }

        $stocks = DB::table('stockawaltap')
            ->where('idtap', $tap->idtap)
            ->where('stock', '>', 0)
            ->orderBy('iddenom')
            ->limit(2)
            ->get(['iddenom', 'stock']);

        $beforeTotals = $stocks->mapWithKeys(function ($stock) use ($sales) {
            $sfStock = DB::table('stockawalsf')
                ->where('idsf', $sales->idsf)
                ->where('iddenom', $stock->iddenom)
                ->value('stock') ?? 0;

            return [$stock->iddenom => (int) $stock->stock + (int) $sfStock];
        });

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->post('/sf-masuk', [
                'tgl' => now()->toDateString(),
                'idtap' => $tap->idtap,
                'idsf' => $sales->idsf,
                'items' => $stocks->map(fn ($stock) => [
                    'iddenom' => $stock->iddenom,
                    'qty' => 1,
                    'sn' => 'UJI-'.$stock->iddenom,
                ])->all(),
            ])->assertRedirect('sf-masuk');

        foreach ($stocks as $stock) {
            $afterTap = DB::table('stockawaltap')
                ->where('idtap', $tap->idtap)
                ->where('iddenom', $stock->iddenom)
                ->value('stock');
            $afterSf = DB::table('stockawalsf')
                ->where('idsf', $sales->idsf)
                ->where('iddenom', $stock->iddenom)
                ->value('stock');

            $this->assertSame($beforeTotals[$stock->iddenom], (int) $afterTap + (int) $afterSf);
        }
    }

    public function test_bulk_retur_sf_moves_each_denom_back_to_tap(): void
    {
        [$admin, $sales, $stocks] = $this->bulkFixture('stockawalsf', 'idsf');

        $before = $stocks->mapWithKeys(function ($stock) use ($sales) {
            $tapStock = DB::table('stockawaltap')
                ->where('idtap', $sales->idtap)
                ->where('iddenom', $stock->iddenom)
                ->value('stock') ?? 0;

            return [$stock->iddenom => [
                'sf' => (int) $stock->stock,
                'tap' => (int) $tapStock,
            ]];
        });

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->post('/retursf', [
                'tgl' => now()->toDateString(),
                'idtap' => $sales->idtap,
                'idsf' => $sales->idsf,
                'ketvf' => 'OK',
                'items' => $stocks->map(fn ($stock) => [
                    'iddenom' => $stock->iddenom,
                    'qty' => 1,
                    'sn' => 'UJI-RETUR-'.$stock->iddenom,
                    'tambahket' => 'Uji retur bulk otomatis',
                ])->all(),
            ])->assertRedirect('retursf');

        foreach ($stocks as $stock) {
            $afterSf = (int) DB::table('stockawalsf')
                ->where('idsf', $sales->idsf)
                ->where('iddenom', $stock->iddenom)
                ->value('stock');
            $afterTap = (int) DB::table('stockawaltap')
                ->where('idtap', $sales->idtap)
                ->where('iddenom', $stock->iddenom)
                ->value('stock');

            $this->assertSame($before[$stock->iddenom]['sf'] - 1, $afterSf);
            $this->assertSame($before[$stock->iddenom]['tap'] + 1, $afterTap);
            $this->assertSame(
                $before[$stock->iddenom]['sf'] + $before[$stock->iddenom]['tap'],
                $afterSf + $afterTap
            );
        }
    }

    public function test_failed_bulk_retur_sf_does_not_move_any_stock(): void
    {
        [$admin, $sales, $stocks] = $this->bulkFixture('stockawalsf', 'idsf');

        $tapStocks = $stocks->mapWithKeys(fn ($stock) => [
            $stock->iddenom => (int) (DB::table('stockawaltap')
                ->where('idtap', $sales->idtap)
                ->where('iddenom', $stock->iddenom)
                ->value('stock') ?? 0),
        ]);

        $this->actingAs($admin)->withSession(['idtap' => 'SBP_DUMAI'])
            ->from('/form/form-retursf')
            ->post('/retursf', [
                'tgl' => now()->toDateString(),
                'idtap' => $sales->idtap,
                'idsf' => $sales->idsf,
                'ketvf' => 'OK',
                'items' => [
                    [
                        'iddenom' => $stocks[0]->iddenom,
                        'qty' => 1,
                        'sn' => 'UJI-RETUR-1',
                        'tambahket' => 'Uji retur bulk otomatis',
                    ],
                    [
                        'iddenom' => $stocks[1]->iddenom,
                        'qty' => (int) $stocks[1]->stock + 1,
                        'sn' => 'UJI-RETUR-2',
                        'tambahket' => 'Uji retur bulk otomatis',
                    ],
                ],
            ])->assertRedirect('/form/form-retursf')
            ->assertSessionHasErrors('items');

        foreach ($stocks as $stock) {
            $this->assertSame(
                (int) $stock->stock,
                (int) DB::table('stockawalsf')
                    ->where('idsf', $sales->idsf)
                    ->where('iddenom', $stock->iddenom)
                    ->value('stock')
            );
            $this->assertSame(
                $tapStocks[$stock->iddenom],
                (int) (DB::table('stockawaltap')
                    ->where('idtap', $sales->idtap)
                    ->where('iddenom', $stock->iddenom)
                    ->value('stock') ?? 0)
            );
        }
    }

    private function bulkFixture(string $table, string $targetColumn): array
    {
        $admin = User::where('username', 'admin_super')->first();
        if (! $admin) {
            $this->markTestSkipped('Akun admin_super belum dimigrasikan.');
        }

        $sales = DB::table('idsf as s')
            ->join($table.' as st', 'st.'.$targetColumn, '=', 's.idsf')
            ->where('st.stock', '>', 0)
            ->select('s.idsf', 's.idtap')
            ->groupBy('s.idsf', 's.idtap')
            ->havingRaw('COUNT(*) >= 2')
            ->first();

        if (! $sales) {
            $this->markTestSkipped('Tidak ada petugas dengan dua denom berstok untuk pengujian bulk.');
        }

        $stocks = DB::table($table)
            ->where($targetColumn, $sales->idsf)
            ->where('stock', '>', 0)
            ->orderBy('iddenom')
            ->limit(2)
            ->get(['iddenom', 'stock']);

        return [$admin, $sales, $stocks];
    }

    private function injectPvFixture(): array
    {
        $admin = User::where('username', 'admin_super')->first();
        $allowedDenoms = DB::table('denom')
            ->where('kategori_inject', 'SEGEL')
            ->where('iddenom', '!=', 'SEGEL')
            ->pluck('iddenom');
        $source = DB::table('stockawaltap')
            ->where('iddenom', 'SEGEL')
            ->where('stock', '>=', 3)
            ->whereExists(function ($query) use ($allowedDenoms) {
                $query->selectRaw('1')
                    ->from('stockawaltap as destination')
                    ->whereColumn('destination.idtap', 'stockawaltap.idtap')
                    ->whereIn('destination.iddenom', $allowedDenoms);
            })
            ->first(['idtap', 'stock']);
        $destinations = $source
            ? DB::table('stockawaltap')
                ->where('idtap', $source->idtap)
                ->whereIn('iddenom', $allowedDenoms)
                ->orderBy('iddenom')
                ->limit(2)
                ->get(['iddenom', 'stock'])
            : collect();

        if (! $admin || ! $source || $destinations->count() < 2) {
            $this->markTestSkipped('Fixture Inject PV bulk tidak tersedia.');
        }

        return [$admin, $source->idtap, (int) $source->stock, $destinations];
    }
}
