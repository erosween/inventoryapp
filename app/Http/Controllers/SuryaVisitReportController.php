<?php

namespace App\Http\Controllers;

use App\Models\SuryaVisit;
use App\Services\OptimizedImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SuryaVisitReportController extends Controller
{
    public function approve(SuryaVisit $visit)
    {
        abort_unless($visit->gps_status === 'pending_admin', 422, 'Visit ini tidak menunggu persetujuan.');

        DB::transaction(function () use ($visit) {
            $visit->update(['gps_status' => 'approved']);

            DB::table('appsdumais')
                ->where('id_outlet', $visit->id_outlet)
                ->update([
                    'latitude' => $visit->latitude,
                    'longitude' => $visit->longitude,
                ]);
        });

        return back()->with('success', 'Visit disetujui dan koordinat outlet diperbarui ke lokasi foto terbaru.');
    }

    public function index(Request $request)
    {
        $idtap = session('idtap');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $latestVisit = DB::table('surya_visits as v')
            ->select('v.id_outlet', DB::raw('MAX(v.visited_at) as latest_visit'))
            ->when($dateFrom, fn ($q) => $q->whereDate('v.visited_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('v.visited_at', '<=', $dateTo))
            ->groupBy('v.id_outlet');

        $visits = DB::table('surya_visits as v')
            ->joinSub($latestVisit, 'latest', function ($join) {
                $join->on('latest.id_outlet', '=', 'v.id_outlet')
                    ->on('latest.latest_visit', '=', 'v.visited_at');
            })
            ->leftJoin('appsdumais as o', 'o.id_outlet', '=', 'v.id_outlet')
            ->leftJoin('idsf as s', 's.idsf', '=', 'v.idsf')
            ->when($idtap && $idtap !== 'SBP_DUMAI', fn ($q) => $q->where('v.idtap', $idtap))
            ->orderByDesc('v.visited_at')
            ->paginate(25)
            ->withQueryString();

        $disk = Storage::disk('public');
        $visits->getCollection()->each(function ($visit) use ($disk) {
            $frontPath = $visit->photo_front_path ?? $visit->photo_path;

            foreach (['front' => $frontPath, 'inside' => $visit->photo_inside_path] as $type => $path) {
                if (! $path || ! $disk->exists($path)) {
                    $visit->{$type.'_photo_url'} = null;
                    $visit->{$type.'_thumbnail_url'} = null;

                    continue;
                }

                $thumbnailPath = $path ? OptimizedImageStorage::thumbnailPathFor($path) : null;
                // Keep media URLs relative to the active host. The production
                // APP_URL can differ from the domain opened by the admin.
                $visit->{$type.'_photo_url'} = $this->publicStorageUrl($path);
                $visit->{$type.'_thumbnail_url'} = $thumbnailPath && $disk->exists($thumbnailPath)
                    ? $this->publicStorageUrl($thumbnailPath)
                    : $visit->{$type.'_photo_url'};
            }
        });

        return view('surya.visits-report', compact('visits', 'dateFrom', 'dateTo'));
    }

    private function publicStorageUrl(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
    }
}
