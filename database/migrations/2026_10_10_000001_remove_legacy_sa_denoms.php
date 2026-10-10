<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $legacyDenoms = [
        'SA_SIMPATI_3GB',
        'SA_BYU_3GB',
    ];

    public function up(): void
    {
        // Hapus saldo awal yang masih tersisa; histori transaksi tetap dipertahankan.
        DB::table('stockawaltap')->whereIn('iddenom', $this->legacyDenoms)->delete();
        DB::table('stockawalfeb')->whereIn('iddenom', $this->legacyDenoms)->delete();
        DB::table('stockawalsf')->whereIn('iddenom', $this->legacyDenoms)->delete();
        DB::table('denom')->whereIn('iddenom', $this->legacyDenoms)->delete();
    }

    public function down(): void
    {
        DB::table('denom')->insert([
            [
                'iddenom' => 'SA_SIMPATI_3GB',
                'denom' => 'SA SIMPATI 3GB',
                'group_name' => 'SA',
                'kategori_inject' => 'SA',
                'harga_jual' => 0,
            ],
            [
                'iddenom' => 'SA_BYU_3GB',
                'denom' => 'SA BYU 3GB',
                'group_name' => 'SA',
                'kategori_inject' => 'SA',
                'harga_jual' => 0,
            ],
        ]);
    }
};
