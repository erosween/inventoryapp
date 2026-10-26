<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class SuryaOutletCoordinates
{
    private function eligibleVisits(string $alias, ?array $outletIds = null): Builder
    {
        return DB::table('surya_visits as '.$alias)
            ->whereIn($alias.'.gps_status', ['valid', 'approved'])
            ->whereNotNull($alias.'.visited_at')
            ->whereBetween($alias.'.latitude', [-90, 90])
            ->whereBetween($alias.'.longitude', [-180, 180])
            ->where(fn ($query) => $query->where($alias.'.latitude', '!=', 0)->orWhere($alias.'.longitude', '!=', 0))
            ->when($outletIds !== null, fn ($query) => $query->whereIn($alias.'.id_outlet', $outletIds));
    }

    /** Pick by visit time, using ID only to break a tie (imports can have newer IDs). */
    public function latestVisits(?array $outletIds = null): Builder
    {
        $times = $this->eligibleVisits('dated_visit', $outletIds)
            ->select('dated_visit.id_outlet')->selectRaw('MAX(dated_visit.visited_at) as coordinate_time')
            ->groupBy('dated_visit.id_outlet');
        $ids = $this->eligibleVisits('selected_visit', $outletIds)
            ->joinSub($times, 'latest_time', fn ($join) => $join
                ->on('latest_time.id_outlet', '=', 'selected_visit.id_outlet')
                ->on('latest_time.coordinate_time', '=', 'selected_visit.visited_at'))
            ->selectRaw('MAX(selected_visit.id) as coordinate_id')->groupBy('selected_visit.id_outlet');

        return DB::table('surya_visits as coordinate_visit')
            ->joinSub($ids, 'latest_id', 'latest_id.coordinate_id', '=', 'coordinate_visit.id')
            ->select([
                'coordinate_visit.id_outlet as coordinate_outlet_id',
                'coordinate_visit.id as coordinate_visit_id',
                'coordinate_visit.visited_at as coordinate_visited_at',
                'coordinate_visit.latitude as visit_latitude',
                'coordinate_visit.longitude as visit_longitude',
            ]);
    }

    /** Every map/search reader gets the same coordinates, even before a backfill. */
    public function outlets(string $alias = 'appsdumais'): Builder
    {
        $columns = collect(DB::getSchemaBuilder()->getColumnListing('appsdumais'))
            ->reject(fn ($column) => in_array($column, ['latitude', 'longitude'], true))
            ->map(fn ($column) => 'outlet_master.'.$column)->all();
        $resolved = DB::table('appsdumais as outlet_master')
            ->leftJoinSub($this->latestVisits(), 'coordinates', 'coordinates.coordinate_outlet_id', '=', 'outlet_master.id_outlet')
            ->select($columns)
            ->addSelect('coordinates.coordinate_visit_id', 'coordinates.coordinate_visited_at')
            ->selectRaw('CASE WHEN coordinates.coordinate_visit_id IS NULL THEN \'master\' ELSE \'visit\' END as coordinate_source')
            ->selectRaw('COALESCE(coordinates.visit_latitude, outlet_master.latitude) as latitude')
            ->selectRaw('COALESCE(coordinates.visit_longitude, outlet_master.longitude) as longitude');

        return DB::query()->fromSub($resolved, $alias);
    }

    public function withVisitState(Collection $outlets): Collection
    {
        if ($outlets->isEmpty()) return $outlets;

        $latest = DB::table('surya_visits')->whereIn('id_outlet', $outlets->pluck('id_outlet')->map(fn ($id) => (string) $id)->all())
            ->orderByDesc('visited_at')->orderByDesc('id')
            ->get(['id', 'id_outlet', 'visited_at', 'gps_status', 'latitude', 'longitude', 'accuracy', 'photo_front_path', 'photo_inside_path'])
            ->unique('id_outlet')->keyBy(fn ($visit) => (string) $visit->id_outlet);

        return $outlets->map(function ($outlet) use ($latest) {
            $visit = $latest->get((string) $outlet->id_outlet);
            $outlet->latest_visit_status = $visit->gps_status ?? null;
            $outlet->latest_visit_at = $visit->visited_at ?? null;
            $outlet->latest_visit_latitude = $visit->latitude ?? null;
            $outlet->latest_visit_longitude = $visit->longitude ?? null;
            $outlet->latest_visit_accuracy = $visit->accuracy ?? null;
            $outlet->map_visit_state = match (true) {
                $visit === null => 'unvisited',
                $visit->gps_status === 'rejected' => 'rejected',
                $visit->gps_status === 'pending_admin' => 'pending',
                in_array($visit->gps_status, ['valid', 'approved'], true)
                    && (int) ($outlet->coordinate_visit_id ?? 0) === (int) $visit->id
                    && filled($visit->photo_front_path) && filled($visit->photo_inside_path) => 'verified',
                default => 'unverified',
            };

            return $outlet;
        });
    }

    public function syncOutlet(string $idOutlet): bool
    {
        return DB::transaction(function () use ($idOutlet) {
            $outlet = DB::table('appsdumais')->where('id_outlet', $idOutlet)->lockForUpdate()->first(['id_outlet', 'latitude', 'longitude']);
            $visit = $this->latestVisits([$idOutlet])->first();
            if (! $outlet || ! $visit || ! $this->different($outlet, $visit)) {
                return false;
            }
            DB::table('appsdumais')->where('id_outlet', $idOutlet)->update([
                'latitude' => $visit->visit_latitude,
                'longitude' => $visit->visit_longitude,
            ]);

            return true;
        });
    }

    private function different(object $outlet, object $visit): bool
    {
        return ! is_numeric($outlet->latitude) || ! is_numeric($outlet->longitude)
            || abs((float) $outlet->latitude - (float) $visit->visit_latitude) > 0.000000005
            || abs((float) $outlet->longitude - (float) $visit->visit_longitude) > 0.000000005;
    }

    public function approveVisit(int $id, bool $expiredOnly = false): bool
    {
        return DB::transaction(function () use ($id, $expiredOnly) {
            $visit = DB::table('surya_visits')->where('id', $id)->lockForUpdate()->first();
            if (! $visit || $visit->gps_status !== 'pending_admin') {
                return false;
            }
            if ($expiredOnly && (! filled($visit->photo_front_path) || ! filled($visit->photo_inside_path)
                || ! $visit->visited_at || $visit->visited_at > now('Asia/Jakarta')->subSeconds(15)->toDateTimeString())) {
                return false;
            }
            if (! is_numeric($visit->latitude) || ! is_numeric($visit->longitude)
                || abs((float) $visit->latitude) > 90 || abs((float) $visit->longitude) > 180
                || ((float) $visit->latitude === 0.0 && (float) $visit->longitude === 0.0)) {
                return false;
            }
            DB::table('surya_visits')->where('id', $id)->update(['gps_status' => 'approved']);
            $this->syncOutlet((string) $visit->id_outlet);

            return true;
        });
    }

    public function approveExpiredVisits(?array $tapScopes = null, ?array $outletIds = null): int
    {
        $ids = DB::table('surya_visits')->where('gps_status', 'pending_admin')
            ->whereNotNull('photo_front_path')->where('photo_front_path', '!=', '')
            ->whereNotNull('photo_inside_path')->where('photo_inside_path', '!=', '')
            ->where('visited_at', '<=', now('Asia/Jakarta')->subSeconds(15))
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('idtap', $tapScopes))
            ->when($outletIds !== null, fn ($query) => $query->whereIn('id_outlet', $outletIds))
            ->pluck('id');

        return $ids->filter(fn ($id) => $this->approveVisit((int) $id, true))->count();
    }

    public function synchronize(bool $dryRun = false, ?array $outletIds = null): array
    {
        $counts = ['checked' => 0, 'changed' => 0, 'unchanged' => 0, 'without_visit' => 0];
        DB::table('appsdumais as master')
            ->leftJoinSub($this->latestVisits($outletIds), 'coordinates', 'coordinates.coordinate_outlet_id', '=', 'master.id_outlet')
            ->when($outletIds !== null, fn ($query) => $query->whereIn('master.id_outlet', $outletIds))
            ->select('master.id_outlet', 'master.latitude', 'master.longitude', 'coordinates.visit_latitude', 'coordinates.visit_longitude')
            ->orderBy('master.id_outlet')->chunk(250, function ($rows) use (&$counts, $dryRun) {
                foreach ($rows as $row) {
                    $counts['checked']++;
                    if ($row->visit_latitude === null || $row->visit_longitude === null) {
                        $counts['without_visit']++;
                    } elseif ($this->different($row, $row) && ($dryRun || $this->syncOutlet((string) $row->id_outlet))) {
                        $counts['changed']++;
                    } else {
                        $counts['unchanged']++;
                    }
                }
            });

        return $counts;
    }
}
