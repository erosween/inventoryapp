<?php

namespace App\Http\Controllers;

use App\Services\OutletCvmImport;
use App\Services\OutletCvmReport;
use App\Support\SuryaTapScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutletCvmController extends Controller
{
    public function index(Request $request, OutletCvmReport $report)
    {
        $today = now('Asia/Jakarta')->toDateString();
        $filters = $request->validate([
            'cutoff' => 'nullable|date_format:Y-m-d|before_or_equal:'.$today,
            'to' => 'nullable|date_format:Y-m-d|before_or_equal:'.$today,
            'tap' => 'nullable|string|max:100',
            'q' => 'nullable|string|max:150',
            'per_page' => 'nullable|in:25,50,100',
        ]);

        $latest = DB::table('surya_outlet_cvm')->max('transaction_date');
        $cutoff = Carbon::parse($filters['cutoff'] ?? $filters['to'] ?? $latest ?? $today, 'Asia/Jakarta');

        $tap = trim($filters['tap'] ?? '');
        $search = trim($filters['q'] ?? '');
        $pageSize = (int) ($filters['per_page'] ?? 25);
        $scopes = SuryaTapScope::forUser($request->user(), session('idtap'));
        $profiles = DB::table('appsdumais')
            ->whereNotNull('id_outlet')
            ->when($scopes !== null, fn ($query) => $query->whereIn('tap', $scopes));
        $taps = (clone $profiles)->whereNotNull('tap')->where('tap', '<>', '')
            ->distinct()->orderBy('tap')->pluck('tap');

        $identity = [
            'id_outlet' => 'ID Outlet',
            'nama_outlet' => 'Nama Outlet',
            'tap' => 'TAP',
            'cluster' => 'Cluster',
            'nomor_rs' => 'Nomor RS',
            'kecamatan' => 'Kecamatan',
            'sf' => 'SF',
            'pjp' => 'Hari PJP',
            'kategori_outlet' => 'Kategori Outlet',
        ];
        $profiles = $profiles
            ->when($tap !== '', fn ($query) => $query->where('tap', $tap))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    foreach (['id_outlet', 'nama_outlet', 'nomor_rs', 'sf', 'kecamatan'] as $column) {
                        $query->orWhere($column, 'like', '%'.$search.'%');
                    }
                });
            });
        $outlets = (clone $profiles)->orderBy('nama_outlet')->orderBy('id_outlet')
            ->paginate($pageSize, array_keys($identity))->withQueryString()->appends(['cutoff' => $cutoff->toDateString()]);

        return view('surya.outlet-transactions-cvm', compact(
            'today', 'latest', 'cutoff', 'tap', 'taps', 'search', 'pageSize', 'identity', 'outlets'
        ) + ['metrics' => OutletCvmImport::METRICS] + $report->data($profiles, $outlets->pluck('id_outlet'), $cutoff));
    }

    public function store(Request $request, string $kind, OutletCvmImport $import)
    {
        abort_unless(strtolower(trim((string) $request->user()?->username)) === 'admin_super', 403);

        $request->validate([
            'cvm_file' => 'required|file|mimes:csv,txt|extensions:csv|max:20480',
        ]);
        $count = $import->import($request->file('cvm_file'), $kind);
        $label = $kind === 'cvm' ? 'CVM' : 'Sub CVM';

        return redirect()->route('surya.transaction-upload')
            ->with('success', $label.' berhasil diupload. '.number_format($count).' baris diproses.');
    }
}
