<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class AuditController extends Controller
{
    public function index()
    {
        // Hanya admin cluster yang bisa lihat log
        if (!auth()->user()->hasClusterAdminAccess()) {
            return redirect('dashboard')->with('error', 'Akses ditolak.');
        }

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $loginSummary = DB::table('login as users')
            ->leftJoin('logs as login_logs', function ($join) use ($monthStart, $monthEnd) {
                $join->on('login_logs.username', '=', 'users.username')
                    ->where('login_logs.action', '=', 'LOGIN')
                    ->whereBetween('login_logs.created_at', [$monthStart, $monthEnd]);
            })
            ->selectRaw('users.username, users.idtap, COUNT(login_logs.id) as login_count, MAX(login_logs.created_at) as last_login_at')
            ->groupBy('users.username', 'users.idtap')
            ->orderByDesc('login_count')
            ->orderBy('users.username')
            ->get();

        return view('audit', [
            'loginSummary' => $loginSummary,
            'loginSummaryPeriod' => $monthStart->translatedFormat('F Y'),
            'loginSummaryTotal' => (int) $loginSummary->sum('login_count'),
            'loginSummaryActiveUsers' => $loginSummary->where('login_count', '>', 0)->count(),
        ]);
    }

    public function data(Request $request)
    {
        $query = DB::table('logs')
            ->orderBy('created_at', 'desc');

        if ($request->filled('daterange')) {
            [$start, $end] = explode(' - ', $request->daterange);
            $query->whereBetween('created_at', [
                $start . ' 00:00:00',
                $end   . ' 23:59:59'
            ]);
        }

        return DataTables::of($query)
            ->editColumn('created_at', fn($r) => Carbon::parse($r->created_at)->format('d-m-Y H:i:s'))
            ->editColumn('old_values', function($r) {
                if (!$r->old_values) return '-';
                return '<pre class="mb-0 text-muted small" style="max-height:100px; overflow-y:auto;">' . json_encode(json_decode($r->old_values), JSON_PRETTY_PRINT) . '</pre>';
            })
            ->editColumn('new_values', function($r) {
                if (!$r->new_values) return '-';
                return '<pre class="mb-0 text-primary small" style="max-height:100px; overflow-y:auto;">' . json_encode(json_decode($r->new_values), JSON_PRETTY_PRINT) . '</pre>';
            })
            ->rawColumns(['old_values', 'new_values'])
            ->make(true);
    }
}
