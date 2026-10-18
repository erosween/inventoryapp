<?php

return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::table('denom')->whereIn('iddenom', ['SP6', 'SP7'])->update(['product_group' => 'SP']);
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('denom')->whereIn('iddenom', ['SP6', 'SP7'])->update(['product_group' => 'PV']);
    }
};
