<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockBulkFlowTest extends TestCase
{
    use DatabaseTransactions;

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
}
