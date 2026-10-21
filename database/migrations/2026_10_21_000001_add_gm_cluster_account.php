<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $values = [
            'idtap' => 'ALL TAP',
            'password' => Hash::make('123321'),
            'level' => '1',
        ];

        if (DB::table('login')->where('username', 'gm_cluster')->exists()) {
            DB::table('login')->where('username', 'gm_cluster')->update($values);
        } else {
            DB::table('login')->insert($values + ['username' => 'gm_cluster']);
        }
    }

    public function down(): void
    {
        DB::table('login')->where('username', 'gm_cluster')->delete();
    }
};
