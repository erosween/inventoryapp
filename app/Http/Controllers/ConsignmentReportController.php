<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsignmentReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        $selectedDate = $validated['date'] ?? now()->toDateString();
        $idtap = session('idtap');

        // Daily activity is a read-only log. Group by serial so duplicate
        // records from legacy receipts never appear as separate numbers.
        $dailyLatestRows = DB::table('surya_sa_consignment_numbers')
            ->whereDate('reported_at', $selectedDate)
            ->whereIn('status', ['draft', 'consigned', 'approved'])
            ->when($idtap && $idtap !== 'SBP_DUMAI', fn ($query) => $query->where('idtap', $idtap))
            ->select('serial_number')
            ->selectRaw('MAX(id) AS latest_id')
            ->groupBy('serial_number');

        $totalNumbers = DB::query()->fromSub(clone $dailyLatestRows, 'daily_consignment_numbers')->count();

        $rows = DB::table('surya_sa_consignment_numbers as c')
            ->joinSub($dailyLatestRows, 'daily', fn ($join) => $join->on('daily.latest_id', '=', 'c.id'))
            ->leftJoin('appsdumais as o', 'o.id_outlet', '=', 'c.id_outlet')
            ->leftJoin('idsf as sf', 'sf.idsf', '=', 'c.idsf')
            ->leftJoin('denom as d', 'd.iddenom', '=', 'c.iddenom')
            ->orderBy('c.reported_at')
            ->orderBy('c.idtap')
            ->orderBy('o.nama_outlet')
            ->orderBy('c.serial_number')
            ->get([
                'c.reported_at', 'c.idtap', 'c.id_outlet', 'o.nama_outlet',
                'c.idsf', 'sf.namasf', 'c.iddenom', 'd.denom', 'c.serial_number',
                'c.status', 'c.notes',
            ]);

        return view('admin.consignment-report', compact('rows', 'selectedDate', 'totalNumbers'));
    }
}
