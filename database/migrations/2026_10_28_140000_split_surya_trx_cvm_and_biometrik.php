<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach (\App\Support\SuryaTransactions::GROUPS as $kind => $metrics) {
            $name = $kind === 'cvm' ? 'surya_trx_cvm' : 'surya_biometrik';
            if (!Schema::hasTable($name)) Schema::create($name, function (Blueprint $table) use ($metrics) {
                $table->id();
                $table->string('id_outlet');
                $table->date('metric_date')->index();
                foreach ($metrics as $metric) foreach (['fm1', 'm1', 'm'] as $period) $table->unsignedInteger($metric.'_'.$period)->default(0);
                $table->date('source_updated_at')->nullable();
                $table->string('uploaded_metrics', 160)->nullable();
                $table->timestamps();
                $table->unique(['id_outlet', 'metric_date']);
            });
            DB::table('surya_daily_metrics')->orderBy('id')->chunkById(500, function ($rows) use ($name, $metrics) {
                $batch = [];
                foreach ($rows as $row) {
                    $markers = array_filter(explode(',', (string) $row->uploaded_metrics));
                    $included = $markers === [] ? $metrics : array_values(array_intersect($metrics, $markers));
                    if ($included === []) continue;
                    $values = array_intersect_key((array)$row, array_flip(['id_outlet', 'metric_date', 'source_updated_at', 'created_at', 'updated_at']));
                    $values['uploaded_metrics'] = implode(',', $included);
                    foreach ($metrics as $metric) foreach (['fm1','m1','m'] as $period) $values[$metric.'_'.$period] = $row->{$metric.'_'.$period} ?? 0;
                    $batch[] = $values;
                }
                if ($batch) DB::table($name)->insertOrIgnore($batch);
            });
        }
        // Keep the original table intact as a recovery copy.
    }
    public function down(): void
    {
        throw new RuntimeException('Automatic rollback is disabled to protect newer uploaded transactions.');
    }
};
