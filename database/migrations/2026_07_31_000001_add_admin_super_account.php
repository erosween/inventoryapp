<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $values = [
            'idtap' => 'SBP_DUMAI',
            'password' => Hash::make('123321'),
            'level' => '1',
        ];

        if (DB::table('login')->where('username', 'admin_super')->exists()) {
            DB::table('login')->where('username', 'admin_super')->update($values);
        } else {
            DB::table('login')->insert($values + ['username' => 'admin_super']);
        }
    }

    public function down(): void
    {
        DB::table('login')->where('username', 'admin_super')->delete();
    }
};
