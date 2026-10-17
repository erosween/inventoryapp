<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (! Schema::hasColumn('denom', 'product_group')) Schema::table('denom', function (Blueprint $table) { $table->string('product_group', 10)->default('PV')->after('kategori_inject'); $table->index('product_group'); });
        DB::table('denom')->update(['product_group' => 'PV']);
        DB::table('denom')->where(function ($query) {
            $query->whereRaw("UPPER(TRIM(denom)) LIKE 'SA %'")
                ->orWhereRaw("UPPER(TRIM(denom)) LIKE 'SA_%'")
                ->orWhereRaw("UPPER(TRIM(denom)) LIKE 'SP SEGEL%'")
                ->orWhereRaw("UPPER(TRIM(denom)) LIKE 'SP BYU SEGEL%'");
        })->update(['product_group' => 'SP']);
    }
    public function down(): void { if (Schema::hasColumn('denom', 'product_group')) Schema::table('denom', function (Blueprint $table) { $table->dropIndex(['product_group']); $table->dropColumn('product_group'); }); }
};
