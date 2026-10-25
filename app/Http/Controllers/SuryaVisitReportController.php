<?php

namespace App\Http\Controllers;

use App\Models\SuryaVisit;
use App\Services\OptimizedImageStorage;
use App\Services\SuryaOutletCoordinates;
use App\Support\SuryaTapScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SuryaVisitReportController extends Controller
{
    public function autoApprovePending(Request $request)
    {
        return response()->json(['approved' => $this->approveExpiredPendingVisits()]);
    }

    private function approveExpiredPendingVisits(): int
    {
        return app(SuryaOutletCoordinates::class)->approveExpiredVisits(
            SuryaTapScope::forUser(auth()->user(), session('idtap'))
        );
    }

    public function approve(SuryaVisit $visit)
    {
        // The approval poller and a user click can arrive at nearly the same
        // time. Treat a visit that was already handled as a harmless repeat
        // submission instead of showing the generic 422 error page.
        if ($visit->gps_status !== 'pending_admin') {
            if (in_array($visit->gps_status, ['valid', 'approved'], true)) {
                app(SuryaOutletCoordinates::class)->syncOutlet((string) $visit->id_outlet);
            }
            return $this->approvalRedirect($visit, 'Visit ini sudah diproses sebelumnya.');
        }

        if (! app(SuryaOutletCoordinates::class)->approveVisit($visit->id)) {
            $visit->refresh();
            if ($visit->gps_status === 'pending_admin') {
                return back()->with('error', 'GPS visit tidak valid. Minta SF mengambil ulang lokasi sebelum disetujui.');
            }
            return $this->approvalRedirect($visit, 'Visit ini sudah diproses sebelumnya.');
        }

        if (request()->input('return_to') === 'visit-detail') {
            $visitDate = request()->input('visit_date', $visit->visited_at?->toDateString() ?? now()->toDateString());

            return redirect(route('surya.dashboard', [
                'panel' => 'visit-detail',
                'visit_date' => $visitDate,
                'visit_tab' => 'approval',
            ]).'#visit-detail')
                ->with('success', 'Visit disetujui dan koordinat outlet diperbarui ke lokasi foto terbaru.');
        }

        return back()->with('success', 'Visit disetujui dan koordinat outlet diperbarui ke lokasi foto terbaru.');
    }

    public function reject(SuryaVisit $visit)
    {
        if ($visit->gps_status !== 'pending_admin') {
            return $this->approvalRedirect($visit, 'Visit ini sudah diproses sebelumnya.');
        }

        if (! SuryaVisit::query()->whereKey($visit->id)->where('gps_status', 'pending_admin')->update(['gps_status' => 'rejected'])) {
            $visit->refresh();
            return $this->approvalRedirect($visit, 'Visit ini sudah diproses sebelumnya.');
        }

        if (request()->input('return_to') === 'visit-detail') {
            $visitDate = request()->input('visit_date', $visit->visited_at?->toDateString() ?? now()->toDateString());

            return redirect(route('surya.dashboard', [
                'panel' => 'visit-detail',
                'visit_date' => $visitDate,
                'visit_tab' => 'approval',
            ]).'#visit-detail')
                ->with('success', 'Foto visit ditolak. Sales Force dapat mengambil ulang foto pada outlet ini.');
        }

        return back()->with('success', 'Foto visit ditolak. Sales Force dapat mengambil ulang foto pada outlet ini.');
    }

    private function approvalRedirect(SuryaVisit $visit, string $message)
    {
        if (request()->input('return_to') === 'visit-detail') {
            $visitDate = request()->input('visit_date', $visit->visited_at?->toDateString() ?? now()->toDateString());

            return redirect(route('surya.dashboard', [
                'panel' => 'visit-detail',
                'visit_date' => $visitDate,
                'visit_tab' => 'approval',
            ]).'#visit-detail')->with('info', $message);
        }

        return back()->with('info', $message);
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
                $thumbnailPath = OptimizedImageStorage::thumbnailPathFor($path);
                $visit->{$type.'_photo_url'} = '/storage/'.ltrim($path, '/');
                $visit->{$type.'_thumbnail_url'} = $disk->exists($thumbnailPath)
                    ? '/storage/'.ltrim($thumbnailPath, '/')
                    : $visit->{$type.'_photo_url'};
            }
        });

        return view('surya.visits-report', compact('visits', 'dateFrom', 'dateTo'));
    }
}
