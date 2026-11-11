<?php

namespace App\Http\Controllers;

use App\Services\OutletCvmColumns;
use App\Services\OutletCvmImport;
use App\Services\OutletCvmReport;
use App\Support\SuryaTapScope;
use App\Support\OutletReportPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OutletCvmController extends Controller
{
    public function index(Request $request, OutletCvmReport $report, OutletCvmColumns $filters)
    {
        $context = $this->context($request);
        ['profiles' => $profiles, 'cutoff' => $cutoff, 'pageSize' => $pageSize] = $context;
        $metrics = OutletCvmImport::METRICS;
        $previousEnd = OutletReportPeriod::previousEnd($cutoff);
        $comparison = $cutoff->month > 1 && $previousEnd->day < $previousEnd->daysInMonth;
        $columns = $this->monthColumns($cutoff, $comparison);

        if ($request->boolean('rekap')) {
            return view('surya.partials.outlet-cvm-summary', compact('metrics', 'cutoff', 'columns')
                + $report->summary($profiles, $cutoff));
        }

        $identity = OutletCvmColumns::IDENTITY;
        $options = $filters->options($request);
        $keys = $filters->activeKeys($options);
        if ($keys === []) {
            $outlets = (clone $profiles)->orderBy('nama_outlet')->orderBy('id_outlet')
                ->paginate($pageSize, array_keys($identity));
        } else {
            $rows = $filters->apply($filters->rows($profiles, $report, $cutoff, $keys), $options);
            $page = (int) $request->input('page', 1);
            $outlets = new LengthAwarePaginator(
                $rows->forPage($page, $pageSize)->map(fn ($row) => (object) $row)->values(),
                $rows->count(), $pageSize, $page, ['path' => $request->url()]
            );
        }
        $outlets->withQueryString()->appends(['cutoff' => $cutoff->toDateString()]);

        unset($context['profiles']);

        $details = $report->details($outlets->pluck('id_outlet'), $cutoff);
        $summaryUrl = route('surya.outlet-cvm', [
            'rekap' => 1, 'cutoff' => $cutoff->toDateString(), 'tap' => $context['tap'], 'q' => $context['search'],
        ]);
        return view('surya.outlet-transactions-cvm', $context + compact(
            'identity', 'outlets', 'options', 'metrics', 'details', 'summaryUrl', 'previousEnd', 'comparison', 'columns'
        ));
    }

    private function monthColumns(Carbon $cutoff, bool $comparison): array
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        $columns = [];
        for ($month = 1; $month <= $cutoff->month; $month++) {
            $columns['m'.$month] = $months[$month - 1];
            if ($comparison && $month === $cutoff->month - 1) {
                $columns['compare'] = $months[$month - 1].'-MoM';
            }
        }
        $columns['total'] = 'Total '.$cutoff->year;
        for ($month = 2; $month <= $cutoff->month; $month++) {
            $columns['gap_m'.$month] = 'GAP '.$months[$month - 1];
            $columns['mom_pct_m'.$month] = 'MoM % '.$months[$month - 1];
        }
        return $columns;
    }

    public function values(Request $request, OutletCvmReport $report, OutletCvmColumns $filters)
    {
        $input = $request->validate([
            'column' => 'required|string',
            'q' => 'nullable|string|max:150',
            'outlet_q' => 'nullable|string|max:150',
            'value_page' => 'nullable|integer|min:1|max:10000',
        ]);
        $column = $input['column'];
        $filters->check($column);
        $source = $request->duplicate();
        $source->merge(['q' => $input['outlet_q'] ?? '']);
        ['profiles' => $profiles, 'cutoff' => $cutoff] = $this->context($source);
        $options = $filters->options($request);
        unset($options['sort'], $options['direction'], $options['columns'][$column], $options['exclude'][$column]);

        $rows = $filters->rows($profiles, $report, $cutoff, [...$filters->activeKeys($options), $column]);
        $search = $input['q'] ?? '';
        $values = $filters->apply($rows, $options)->pluck($column)
            ->map(fn ($value) => (string) $value)->unique()
            ->filter(fn ($value) => $search === '' || mb_stripos($value, $search) !== false)
            ->sort(SORT_NATURAL)->values()
            ->slice(((int) ($input['value_page'] ?? 1) - 1) * 100, 101);

        return response()->json([
            'values' => $values->take(100)->values(),
            'has_more' => $values->count() > 100,
        ]);
    }

    private function context(Request $request): array
    {
        $today = now('Asia/Jakarta')->toDateString();
        $filters = $request->validate([
            'cutoff' => 'nullable|date_format:Y-m-d|before_or_equal:'.$today,
            'to' => 'nullable|date_format:Y-m-d|before_or_equal:'.$today,
            'tap' => 'nullable|string|max:100',
            'q' => 'nullable|string|max:150',
            'per_page' => 'nullable|in:25,50,100',
            'page' => 'nullable|integer|min:1',
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

        $profiles = $profiles
            ->when($tap !== '', fn ($query) => $query->where('tap', $tap))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    foreach (['id_outlet', 'nama_outlet', 'nomor_rs', 'sf', 'kecamatan'] as $column) {
                        $query->orWhere($column, 'like', '%'.$search.'%');
                    }
                });
            });
        return compact('profiles', 'today', 'latest', 'cutoff', 'tap', 'taps', 'search', 'pageSize');
    }

    public function store(Request $request, string $kind, OutletCvmImport $import)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $request->validate([
            'cvm_file' => 'required|file|mimes:csv,txt|extensions:csv|max:20480',
        ]);
        $count = $import->import($request->file('cvm_file'), $kind);
        \Illuminate\Support\Facades\Cache::put('surya-cvm-upload-revision', (string) \Illuminate\Support\Str::uuid());
        $label = $kind === 'cvm' ? 'CVM' : 'Sub CVM';

        return redirect()->route('surya.transaction-upload')
            ->with('success', $label.' berhasil diupload. '.number_format($count).' baris diproses.');
    }
}
