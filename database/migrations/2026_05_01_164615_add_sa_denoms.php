<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('denom')->insert([
            [
                'iddenom' => 'SA_SIMPATI_3GB',
                'denom' => 'SA SIMPATI 3GB',
                'group_name' => 'SA',
                'kategori_inject' => 'SA',
                'harga_jual' => 0
            ],
            [
                'iddenom' => 'SA_BYU_3GB',
                'denom' => 'SA BYU 3GB',
                'group_name' => 'SA',
                'kategori_inject' => 'SA',
                'harga_jual' => 0
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('denom')->whereIn('iddenom', ['SA_SIMPATI_3GB', 'SA_BYU_3GB'])->delete();
    }
};
