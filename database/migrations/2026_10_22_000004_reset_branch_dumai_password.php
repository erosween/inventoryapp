<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('login')->where('username', 'branch_dumai')->update([
            'password' => Hash::make('123321'),
        ]);
    }

    public function down(): void
    {
        // Password lama tidak dapat dipulihkan karena disimpan dalam bentuk hash.
    }
};
