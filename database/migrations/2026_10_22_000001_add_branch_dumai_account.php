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

        DB::table('login')->updateOrInsert(['username' => 'branch_dumai'], $values);
    }

    public function down(): void
    {
        DB::table('login')->where('username', 'branch_dumai')->delete();
    }
};
