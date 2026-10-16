<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $reservations = DB::table('mobile_penjualan')
                ->where('status', 'pending')
                ->whereNotNull('stock_reserved_at')
                ->select('idsf', 'iddenom', DB::raw('SUM(qty) as reserved_qty'))
                ->groupBy('idsf', 'iddenom')
                ->get();

            foreach ($reservations as $reservation) {
                $stock = DB::table('stockawalsf')
                    ->where('idsf', $reservation->idsf)
                    ->where('iddenom', $reservation->iddenom)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    DB::table('stockawalsf')
                        ->where('idsf', $reservation->idsf)
                        ->where('iddenom', $reservation->iddenom)
                        ->increment('stock', (int) $reservation->reserved_qty);
                } else {
                    DB::table('stockawalsf')->insert([
                        'idsf' => $reservation->idsf,
                        'iddenom' => $reservation->iddenom,
                        'stock' => (int) $reservation->reserved_qty,
                    ]);
                }
            }

            DB::table('mobile_penjualan')
                ->where('status', 'pending')
                ->whereNotNull('stock_reserved_at')
                ->update(['stock_reserved_at' => null]);
        });
    }

    public function down(): void
    {
        // Rekonsiliasi stok tidak aman untuk dibalik otomatis.
    }
};
