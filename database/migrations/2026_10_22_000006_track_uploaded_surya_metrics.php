<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('surya_daily_metrics', 'uploaded_metrics')) {
            Schema::table('surya_daily_metrics', function (Blueprint $table) {
                $table->string('uploaded_metrics', 160)->nullable()->after('source_updated_at');
            });
        }

        $metricColumns = [
            'ss' => ['ss_fm1', 'ss_m1', 'ss_m'],
            'hot_promo' => ['hot_promo_fm1', 'hot_promo_m1', 'hot_promo_m'],
            'cvm' => ['cvm_fm1', 'cvm_m1', 'cvm_m'],
            'insak' => ['insak_fm1', 'insak_m1', 'insak_m'],
            'comsak' => ['comsak_fm1', 'comsak_m1', 'comsak_m'],
            'digital' => ['digital_fm1', 'digital_m1', 'digital_m'],
            'bio' => ['bio_fm1', 'bio_m1', 'bio_m'],
        ];

        DB::table('surya_daily_metrics')->orderBy('id')->chunkById(500, function ($rows) use ($metricColumns) {
            $updates = [];
            foreach ($rows as $row) {
                $uploaded = [];
                foreach ($metricColumns as $metric => $columns) {
                    foreach ($columns as $column) {
                        if ((int) ($row->{$column} ?? 0) !== 0) {
                            $uploaded[] = $metric;
                            break;
                        }
                    }
                }

                // A completely zero legacy snapshot cannot reveal which headers were present.
                // Keep the old full-snapshot behaviour only for that ambiguous case.
                if ($uploaded === []) {
                    $uploaded = array_keys($metricColumns);
                }

                $updates[] = [
                    'id' => $row->id,
                    'uploaded_metrics' => implode(',', $uploaded),
                ];
            }

            DB::table('surya_daily_metrics')->upsert($updates, ['id'], ['uploaded_metrics']);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('surya_daily_metrics', 'uploaded_metrics')) {
            Schema::table('surya_daily_metrics', function (Blueprint $table) {
                $table->dropColumn('uploaded_metrics');
            });
        }
    }
};
