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

        return view('audit');
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
