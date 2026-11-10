<?php

namespace App\Http\Controllers;

use App\Services\OutletCvmImport;
use App\Support\SuryaTapScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutletCvmController extends Controller
{
    public function index(Request $request)
    {
        $today = now('Asia/Jakarta')->toDateString();
        $filters = $request->validate([
            'from' => 'nullable|date_format:Y-m-d|before_or_equal:'.$today,
            'to' => 'nullable|date_format:Y-m-d|before_or_equal:'.$today,
            'tap' => 'nullable|string|max:100',
            'q' => 'nullable|string|max:150',
            'per_page' => 'nullable|in:25,50,100',
        ]);

        $latest = DB::table('surya_outlet_cvm')->max('transaction_date');
        $to = $filters['to'] ?? max($latest ?? $today, $filters['from'] ?? '');
        $from = $filters['from'] ?? substr($to, 0, 4).'-01-01';
        if ($from > $to) {
            throw ValidationException::withMessages(['from' => 'Tanggal awal harus sebelum atau sama dengan tanggal akhir.']);
        }

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
        $outlets = $profiles
            ->when($tap !== '', fn ($query) => $query->where('tap', $tap))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    foreach (['id_outlet', 'nama_outlet', 'nomor_rs', 'sf', 'kecamatan'] as $column) {
                        $query->orWhere($column, 'like', '%'.$search.'%');
                    }
                });
            })
            ->orderBy('nama_outlet')->orderBy('id_outlet')
            ->paginate($pageSize, array_keys($identity))->withQueryString()->appends(['from' => $from, 'to' => $to]);

        $metrics = OutletCvmImport::METRICS;
        $totals = collect();
        if ($outlets->isNotEmpty()) {
            $totalsQuery = DB::table('surya_outlet_cvm')
                ->whereIn('id_outlet', $outlets->pluck('id_outlet'))
                ->whereBetween('transaction_date', [$from, $to])
                ->select('id_outlet')
                ->groupBy('id_outlet');
            foreach (array_keys($metrics) as $metric) {
                $totalsQuery->selectRaw("SUM({$metric}) AS {$metric}");
            }
            $totals = $totalsQuery->get()->keyBy('id_outlet');
        }

        return view('surya.outlet-transactions-cvm', compact(
            'today', 'latest', 'from', 'to', 'tap', 'taps', 'search', 'pageSize',
            'identity', 'outlets', 'metrics', 'totals'
        ));
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
