<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    private array $accounts = [
        'asmen_dumai' => 'DUMAI',
        'asmen_duri' => 'DURI',
        'asmen_bengkalis' => 'BENGKALIS',
        'asmen_sei_pakning' => 'SEI PAKNING',
        'asmen_rupat' => 'RUPAT',
        'asmen_bagan_batu' => 'BAGAN BATU',
        'asmen_bagan_siapi_api' => 'BAGAN SIAPI-API',
    ];

    public function up(): void
    {
        foreach ($this->accounts as $username => $idtap) {
            $existing = DB::table('login')->where('username', $username)->first();

            if ($existing) {
                DB::table('login')
                    ->where('username', $username)
                    ->update([
                        'idtap' => $idtap,
                        'password' => Hash::make('123321'),
                        'level' => '2',
                    ]);
            } else {
                DB::table('login')->insert([
                    'idtap' => $idtap,
                    'username' => $username,
                    'password' => Hash::make('123321'),
                    'level' => '2',
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('login')->whereIn('username', array_keys($this->accounts))->delete();
    }
};
