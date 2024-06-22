<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{

    public function run(): void
    {
        DB::table('login')->insert([
            'idtap' => 'SBP_DUMAI',
            'username' => 'admin_cluster',
            'password' => Hash::make(123321),
            'level' => '1',
        ]);

        DB::table('login')->insert([
            'idtap' => 'DUMAI',
            'username' => 'admin_dumai',
            'password' => Hash::make(345321),
            'level' => '2',
        ]);

        DB::table('login')->insert([
            'idtap' => 'DURI',
            'username' => 'admin_duri',
            'password' => Hash::make(578431),
            'level' => '2',
        ]);

        DB::table('login')->insert([
            'idtap' => 'BENGKALIS',
            'username' => 'admin_bengkalis',
            'password' => Hash::make(779021),
            'level' => '2',
        ]);

        DB::table('login')->insert([
            'idtap' => 'SEI PAKNING',
            'username' => 'admin_sei_pakning',
            'password' => Hash::make(809217),
            'level' => '2',
        ]);

        DB::table('login')->insert([
            'idtap' => 'RUPAT',
            'username' => 'admin_rupat',
            'password' => Hash::make(785621),
            'level' => '2',
        ]);

        DB::table('login')->insert([
            'idtap' => 'BAGAN BATU',
            'username' => 'admin_bagan_batu',
            'password' => Hash::make(987216),
            'level' => '2',
        ]);

        DB::table('login')->insert([
            'idtap' => 'BAGAN SIAPI-API',
            'username' => 'admin_bagan_siapi',
            'password' => Hash::make(750912),
            'level' => '2',
        ]);

        DB::table('login')->insert([
            'idtap' => 'UJUNG TANJUNG',
            'username' => 'admin_ujung_tanjung',
            'password' => Hash::make(332181),
            'level' => '2',
        ]);

        DB::table('login')->insert([
            'idtap' => 'SB DUMAI',
            'username' => 'nocan_dmi',
            'password' => Hash::make(123321),
            'level' => '2',
        ]);
        DB::table('login')->insert([
            'idtap' => 'SB SIDEMPUAN',
            'username' => 'nocan_psp',
            'password' => Hash::make(882231),
            'level' => '2',
        ]);
    }
}
