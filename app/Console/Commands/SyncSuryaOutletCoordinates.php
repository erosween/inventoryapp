<?php

namespace App\Console\Commands;

use App\Services\SuryaOutletCoordinates;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncSuryaOutletCoordinates extends Command
{
    protected $signature = 'surya:sync-outlet-coordinates {--dry-run : Periksa tanpa mengubah data} {--outlet=* : Batasi ke ID outlet tertentu}';

    protected $description = 'Sinkronkan master outlet dari GPS visit valid/disetujui terbaru berdasarkan waktu visit';

    public function handle(SuryaOutletCoordinates $coordinates): int
    {
        $ids = $this->option('outlet') ?: null;
        $dryRun = (bool) $this->option('dry-run');
        if ($ids !== null) {
            $visits = $coordinates->latestVisits($ids)->get()->keyBy('coordinate_outlet_id');
            $latest = DB::table('surya_visits')->whereIn('id_outlet', $ids)
                ->orderByDesc('visited_at')->orderByDesc('id')
                ->get(['id', 'id_outlet', 'visited_at', 'gps_status', 'latitude', 'longitude'])->unique('id_outlet')->keyBy('id_outlet');
            $masters = DB::table('appsdumais')->whereIn('id_outlet', $ids)->get(['id_outlet', 'latitude', 'longitude'])->keyBy('id_outlet');
            $this->table(['ID OUTLET', 'GPS MASTER', 'VISIT TERBARU / STATUS', 'GPS VISIT TERBARU', 'VISIT DIPAKAI'], collect($ids)->map(function ($id) use ($visits, $latest, $masters) {
                $visit = $visits->get($id);
                $last = $latest->get($id);
                $master = $masters->get($id);

                return [$id, $master ? $master->latitude.', '.$master->longitude : 'OUTLET TIDAK ADA',
                    $last ? '#'.$last->id.' / '.$last->visited_at.' / '.$last->gps_status : 'BELUM ADA',
                    $last ? $last->latitude.', '.$last->longitude : '-',
                    $visit ? '#'.$visit->coordinate_visit_id.' / '.$visit->coordinate_visited_at : 'MASTER DIPERTAHANKAN'];
            })->all());
        }
        $counts = $coordinates->synchronize($dryRun, $ids);
        $this->info($dryRun ? 'AUDIT SAJA — tidak ada data diubah.' : 'Sinkronisasi koordinat selesai.');
        $this->table(['DIPERIKSA', $dryRun ? 'PERLU DIPERBARUI' : 'DIPERBARUI', 'SUDAH SESUAI', 'TANPA VISIT VALID'], [array_values($counts)]);

        return self::SUCCESS;
    }
}
