<?php

use App\Support\IntertapTransferKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('keluar', 'canonical_fingerprint')) {
            Schema::table('keluar', function (Blueprint $table) {
                $table->char('canonical_fingerprint', 64)->nullable()->after('request_fingerprint');
            });
        }

        $denoms = DB::table('denom')->pluck('denom', 'iddenom');
        $seen = [];
        DB::table('keluar')->orderBy('idkeluar')->chunkById(500, function ($rows) use ($denoms, &$seen) {
            foreach ($rows as $row) {
                $key = IntertapTransferKey::make(
                    $row->tgl, $row->pengirim, $row->penerima,
                    $denoms[$row->iddenom] ?? $row->iddenom, $row->qty, $row->sn
                );
                if (! isset($seen[$key])) {
                    DB::table('keluar')->where('idkeluar', $row->idkeluar)->update(['canonical_fingerprint' => $key]);
                    $seen[$key] = true;
                }
            }
        }, 'idkeluar');

        Schema::table('keluar', function (Blueprint $table) {
            $table->unique('canonical_fingerprint', 'keluar_canonical_fingerprint_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('keluar', 'canonical_fingerprint')) {
            Schema::table('keluar', function (Blueprint $table) {
                $table->dropUnique('keluar_canonical_fingerprint_unique');
                $table->dropColumn('canonical_fingerprint');
            });
        }
    }
};
