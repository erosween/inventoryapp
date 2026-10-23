<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('keluar', 'request_fingerprint')) {
            Schema::table('keluar', function (Blueprint $table) {
                $table->char('request_fingerprint', 64)->nullable()->after('status');
            });
        }

        $denoms = DB::table('denom')->pluck('denom', 'iddenom');
        $seen = [];
        DB::table('keluar')->orderBy('idkeluar')->chunkById(500, function ($rows) use ($denoms, &$seen) {
            foreach ($rows as $row) {
                $denom = mb_strtoupper(preg_replace('/\s+/', ' ', trim((string) ($denoms[$row->iddenom] ?? $row->iddenom))));
                $sn = mb_strtoupper(preg_replace('/\s+/', '', trim((string) $row->sn)));
                $fingerprint = hash('sha256', implode('|', [
                    substr((string) $row->tgl, 0, 10),
                    mb_strtoupper(trim((string) $row->pengirim)),
                    mb_strtoupper(trim((string) $row->penerima)),
                    $denom,
                    (int) $row->qty,
                    $sn,
                ]));

                if (! isset($seen[$fingerprint])) {
                    DB::table('keluar')->where('idkeluar', $row->idkeluar)->update(['request_fingerprint' => $fingerprint]);
                    $seen[$fingerprint] = true;
                }
            }
        }, 'idkeluar');

        Schema::table('keluar', function (Blueprint $table) {
            $table->unique('request_fingerprint', 'keluar_request_fingerprint_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('keluar', 'request_fingerprint')) {
            Schema::table('keluar', function (Blueprint $table) {
                $table->dropUnique('keluar_request_fingerprint_unique');
                $table->dropColumn('request_fingerprint');
            });
        }
    }
};
