<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $account = [
            'idtap' => 'UJUNG TANJUNG',
            'password' => Hash::make('123321'),
            'level' => '2',
        ];

        if (DB::table('login')->where('username', 'asmen_ujung_tanjung')->exists()) {
            DB::table('login')
                ->where('username', 'asmen_ujung_tanjung')
                ->update($account);
        } else {
            DB::table('login')->insert($account + [
                'username' => 'asmen_ujung_tanjung',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('login')->where('username', 'asmen_ujung_tanjung')->delete();
    }
};
