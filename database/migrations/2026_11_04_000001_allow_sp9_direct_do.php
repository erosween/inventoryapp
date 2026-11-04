<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('denom')->where('iddenom', 'SP9')->update(['allow_do' => true]);
    }

    public function down(): void
    {
        DB::table('denom')->where('iddenom', 'SP9')->update(['allow_do' => false]);
    }
};
