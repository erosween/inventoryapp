<?php

namespace App\Http\Controllers;

use App\Models\SuryaVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuryaVisitReportController extends Controller
{
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

        return view('surya.visits-report', compact('visits', 'dateFrom', 'dateTo'));
    }
}
