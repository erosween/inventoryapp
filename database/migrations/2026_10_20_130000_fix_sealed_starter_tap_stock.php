<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('denom') || ! Schema::hasTable('stockawaltap')) {
            return;
        }

        $starters = [
            'SP6' => ['denom' => 'PERDANA SEGEL SIMPATI', 'category' => 'SA'],
            'SP7' => ['denom' => 'PERDANA SEGEL BYU', 'category' => 'BYU'],
        ];
        foreach ($starters as $id => $starter) {
            DB::table('denom')->insertOrIgnore([
                'iddenom' => $id,
                'denom' => $starter['denom'],
                'group_name' => 'SEGEL',
                'kategori_inject' => $starter['category'],
                'product_group' => 'SP',
                'harga_jual' => 0,
            ]);
            DB::table('denom')->where('iddenom', $id)->update(['product_group' => 'SP']);
        }

        $tapIds = Schema::hasTable('kodetap') ? DB::table('kodetap')->pluck('idtap') : collect();
        foreach ($tapIds as $idtap) {
            foreach (array_keys($starters) as $iddenom) {
                DB::table('stockawaltap')->insertOrIgnore([
                    'idtap' => $idtap,
                    'iddenom' => $iddenom,
                    'stock' => 0,
                ]);
            }
        }

        if (! Schema::hasTable('masuk')) {
            return;
        }

        // Older Input DO code routed SP6/SP7 to stockawalsf. Move that balance
        // back to its TAP. If the SF balance row never existed, the old
        // increment was a no-op, so restore the complete recorded DO quantity.
        $history = DB::table('masuk')
            ->whereIn('iddenom', array_keys($starters))
            ->select('iddenom', 'idtap', 'penerima', DB::raw('SUM(qty) as qty'))
            ->groupBy('iddenom', 'idtap', 'penerima')
            ->get();

        foreach ($history as $row) {
            if (! filled($row->idtap) || (int) $row->qty < 1) {
                continue;
            }

            $sfStockExists = Schema::hasTable('stockawalsf') && DB::table('stockawalsf')
                ->where('idsf', $row->penerima)
                ->where('iddenom', $row->iddenom)
                ->exists();
            $sfStock = $sfStockExists
                ? (int) DB::table('stockawalsf')->where('idsf', $row->penerima)->where('iddenom', $row->iddenom)->value('stock')
                : 0;

            $restoreQty = $sfStockExists
                ? min((int) $row->qty, max(0, $sfStock))
                : (int) $row->qty;

            if ($restoreQty < 1) {
                continue;
            }

            DB::table('stockawaltap')->insertOrIgnore([
                'idtap' => $row->idtap,
                'iddenom' => $row->iddenom,
                'stock' => 0,
            ]);
            DB::table('stockawaltap')
                ->where('idtap', $row->idtap)
                ->where('iddenom', $row->iddenom)
                ->increment('stock', $restoreQty);

            if ($sfStockExists) {
                DB::table('stockawalsf')
                    ->where('idsf', $row->penerima)
                    ->where('iddenom', $row->iddenom)
                    ->decrement('stock', $restoreQty);
            }
        }
    }

    public function down(): void
    {
        // Stock reconciliation is intentionally irreversible.
    }
};
