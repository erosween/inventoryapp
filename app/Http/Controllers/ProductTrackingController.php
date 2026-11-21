<?php

namespace App\Http\Controllers;

use App\Services\ProductTrackingFile;
use App\Support\DenomGrouping;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ProductTrackingController extends MonitaDumaiController
{
    private array $scanRangesByLength = [];
    private ?array $completeScanUploadIds = null;

    public function index(Request $request)
    {
        return redirect()->route('surya.product-tracking.sold', $request->query());
    }

    public function soldSerials(Request $request)
    {
        $filters = $this->soldSerialFilters($request);
        $tableOptions = $this->soldTableOptions($request);
        $detailTableOptions = $tableOptions;
        $summaryTarget = $request->validate([
            'summary_level' => 'nullable|in:tap,sf',
            'summary_key' => 'nullable|string|max:150',
            'summary_status' => 'nullable|in:matched,mismatch',
        ]);
        $request->validate(['page' => 'nullable|integer|min:1|max:100000']);
        $summaryTableLevel = $tableOptions['summary_table'];
        abort_unless(Schema::hasTable('mobile_sales_serials'), 503, 'Data SN Mobile SURYA belum tersedia. Jalankan migrasi terbaru terlebih dahulu.');
        $hasScanUploads = DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
            ->whereRaw("CONVERT(upload.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'scan' COLLATE utf8mb4_unicode_ci")
            ->whereRaw("CONVERT(upload.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
            ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])->exists();

        $activeTab = $filters['tab'];
        $sourceQuery = $this->soldValidationSource($filters);

        // Load the small daily scan set once and match serial intervals in memory.
        // The former correlated range query ran once per sale row and repeatedly
        // cast both sides of the scan indexes, causing the 504 on busy dates.
        $scanRangesByOutlet = DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
            ->whereRaw("CONVERT(upload.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'scan' COLLATE utf8mb4_unicode_ci")
            ->whereRaw("CONVERT(upload.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
            ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])
            ->get(['digipos.id_outlet', 'digipos.serial_start', 'digipos.serial_end'])
            ->groupBy('id_outlet');
        $allOutletRows = $sourceQuery->get()->groupBy('id_outlet')->map(function ($sales, $idOutlet) use ($scanRangesByOutlet) {
            $first = $sales->first();
            $qty = 0;
            $matchedQty = 0;
            foreach ($sales as $sale) {
                $saleQty = (int) $sale->qty;
                $qty += $saleQty;
                $matched = 0;
                foreach ($scanRangesByOutlet->get($idOutlet, collect()) as $scan) {
                    $scanStart = (string) $scan->serial_start;
                    $scanEnd = (string) $scan->serial_end;
                    $saleStart = (string) $sale->serial_start;
                    $saleEnd = (string) $sale->serial_end;
                    if ($this->compareSerials($scanEnd, $saleStart) < 0 || $this->compareSerials($scanStart, $saleEnd) > 0) continue;
                    $overlapStart = $this->compareSerials($saleStart, $scanStart) >= 0 ? $saleStart : $scanStart;
                    $overlapEnd = $this->compareSerials($saleEnd, $scanEnd) <= 0 ? $saleEnd : $scanEnd;
                    $matched += (int) $this->serialRangeCount($overlapStart, $overlapEnd);
                }
                $matchedQty += min($saleQty, $matched);
            }

            return (object) [
                'id_outlet' => (string) $idOutlet,
                'nama_outlet' => $sales->pluck('nama_outlet')->filter()->sort()->last(),
                'tap' => $sales->pluck('tap')->filter()->sort()->last() ?: 'Tanpa TAP',
                'idsf' => $sales->pluck('idsf')->filter()->sort()->last(),
                'namasf' => $sales->pluck('outlet_sf')->filter()->map(fn ($name) => trim((string) $name))->filter()->sort()->last()
                    ?: $sales->pluck('namasf')->filter()->map(fn ($name) => trim((string) $name))->filter()->sort()->last(),
                'kategori_outlet' => $sales->pluck('kategori_outlet')->filter()->map(fn ($value) => trim((string) $value))->filter()->sort()->last() ?: 'Reguler',
                'kecamatan' => $sales->pluck('kecamatan')->filter()->sort()->last(),
                'qty' => $qty,
                'matched_qty' => $matchedQty,
                'mismatch_qty' => max($qty - $matchedQty, 0),
            ];
        })->values();
        $outletRows = $allOutletRows;
        foreach (['columns', 'exclude'] as $mode) {
            $detailTableOptions[$mode] = array_filter($detailTableOptions[$mode] ?? [], fn ($values, $column) => ! in_array($column, ['sf','outlet_count','matched_outlets','mismatch_outlets'], true), ARRAY_FILTER_USE_BOTH);
            $tableOptions[$mode] = array_filter($tableOptions[$mode] ?? [], fn ($values, $column) => in_array($column, ['sf','outlet_count','matched_outlets','mismatch_outlets'], true), ARRAY_FILTER_USE_BOTH);
            foreach ($detailTableOptions[$mode] ?? [] as $column => $values) {
                $outletRows = $outletRows->filter(function ($row) use ($column, $values, $mode) {
                    $value = (string) ($row->{$column} ?? '');
                    if ($column === 'status') $value = $row->mismatch_qty === 0 ? 'Sesuai' : 'Perlu dicek';
                    $included = in_array($value, $values, true);
                    return $mode === 'columns' ? $included : ! $included;
                });
            }
        }
        if (! empty($summaryTarget['summary_level']) && isset($summaryTarget['summary_key'])) {
            $summaryField = $summaryTarget['summary_level'] === 'tap' ? 'tap' : 'namasf';
            $outletRows = $outletRows->filter(fn ($row) => $summaryTarget['summary_key'] === '__NULL__'
                ? $row->{$summaryField} === null
                : (string) $row->{$summaryField} === $summaryTarget['summary_key']);
        }
        if (($summaryTarget['summary_status'] ?? '') === 'matched') {
            $outletRows = $outletRows->where('mismatch_qty', 0);
        } elseif (($summaryTarget['summary_status'] ?? '') === 'mismatch') {
            $outletRows = $outletRows->filter(fn ($row) => $row->mismatch_qty > 0);
        }
        $sort = $tableOptions['sort'] ?? 'nama_outlet';
        $sortColumn = in_array($sort, ['sf','outlet_count','matched_outlets','mismatch_outlets'], true) ? 'nama_outlet' : $sort;
        $direction = $tableOptions['direction'] ?? 'asc';
        $outletRows = $direction === 'desc'
            ? $outletRows->sortByDesc($sortColumn, SORT_NATURAL | SORT_FLAG_CASE)
            : $outletRows->sortBy($sortColumn, SORT_NATURAL | SORT_FLAG_CASE);
        $outletRows = $outletRows->values();
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $outlets = new LengthAwarePaginator($outletRows->forPage($currentPage, 20)->values(), $outletRows->count(), 20, $currentPage, ['path' => LengthAwarePaginator::resolveCurrentPath()]);
        $outlets->appends($request->query());

        $tapOrder = ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING', 'BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'];
        $tapSummaries = $allOutletRows->whereIn('tap', $tapOrder)->groupBy('tap')->map(fn ($rows, $tap) => $this->summarizeSoldOutlets($rows, $tap, $tap))->sortBy(fn ($group) => array_search(strtoupper($group->key), $tapOrder, true))->values();
        $registeredSfNames = DB::table('idsf')->get(['idsf', 'namasf'])
            ->filter(fn ($sf) => str_starts_with(strtoupper(trim((string) $sf->idsf)), 'SF'))
            ->pluck('namasf')->map(fn ($name) => mb_strtolower(trim((string) $name)))->flip();
        $sfSummaries = $allOutletRows->filter(fn ($row) => ! str_starts_with((string) $row->id_outlet, 'NON-DIGIPOS-') && $registeredSfNames->has(mb_strtolower(trim((string) $row->namasf))))
            ->groupBy('namasf')->map(function ($rows, $name) use ($tapOrder) {
                $group = $this->summarizeSoldOutlets($rows, $name, $name);
                $group->tap = $rows->pluck('tap')->unique()->sortBy(fn ($tap) => array_search(strtoupper((string) $tap), $tapOrder, true))->implode(', ') ?: 'Tanpa TAP';
                return $group;
            })->sortBy(fn ($group) => [array_search(strtoupper(strtok($group->tap, ',')), $tapOrder, true), mb_strtolower($group->label)])->values();

        $tapSummaries = $this->filterSoldSummaries($tapSummaries, 'tap', $tableOptions);
        $sfSummaries = $this->filterSoldSummaries($sfSummaries, 'sf', $tableOptions);

        $clusterSummaries = collect([
            'Dumai Bengkalis' => ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING'],
            'Rokan Hilir' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
        ])->map(function ($clusterTaps, $cluster) use ($tapSummaries) {
            $groups = $tapSummaries->whereIn('key', $clusterTaps);

            return (object) [
                'label' => $cluster,
                'outlet_count' => (int) $groups->sum('outlet_count'),
                'matched_outlets' => (int) $groups->sum('matched_outlets'),
                'mismatch_outlets' => (int) $groups->sum('mismatch_outlets'),
                'qty' => (int) $groups->sum('qty'),
                'matched_qty' => (int) $groups->sum('matched_qty'),
                'mismatch_qty' => (int) $groups->sum('mismatch_qty'),
            ];
        })->values();
        $clusterSummaries->push((object) [
            'label' => 'Grand Total',
            'outlet_count' => (int) $clusterSummaries->sum('outlet_count'),
            'matched_outlets' => (int) $clusterSummaries->sum('matched_outlets'),
            'mismatch_outlets' => (int) $clusterSummaries->sum('mismatch_outlets'),
            'qty' => (int) $clusterSummaries->sum('qty'),
            'matched_qty' => (int) $clusterSummaries->sum('matched_qty'),
            'mismatch_qty' => (int) $clusterSummaries->sum('mismatch_qty'),
        ]);

        $totals = $allOutletRows->reduce(function ($totals, $row) {
            $totals->outlet_count++;
            $totals->matched_outlets += $row->mismatch_qty === 0 ? 1 : 0;
            $totals->mismatch_outlets += $row->mismatch_qty > 0 ? 1 : 0;
            $totals->serial_count += (int) $row->qty;
            $totals->matched_qty += (int) $row->matched_qty;
            $totals->mismatch_qty += (int) $row->mismatch_qty;
            return $totals;
        }, (object) ['outlet_count' => 0, 'matched_outlets' => 0, 'mismatch_outlets' => 0, 'serial_count' => 0, 'matched_qty' => 0, 'mismatch_qty' => 0]);
        $summary = (object) [
            'outlet_count' => (int) ($totals->outlet_count ?? 0),
            'matched_outlets' => (int) ($totals->matched_outlets ?? 0),
            'mismatch_outlets' => (int) ($totals->mismatch_outlets ?? 0),
            'serial_count' => (int) ($totals->serial_count ?? 0),
            'matched_qty' => (int) ($totals->matched_qty ?? 0),
            'mismatch_qty' => (int) ($totals->mismatch_qty ?? 0),
        ];

        return view('surya.product-tracking.sold', [
            'filters' => $filters,
            'summary' => $summary,
            'hasScanUploads' => $hasScanUploads,
            'outlets' => $outlets,
            'outletPagination' => $outlets,
            'tableOptions' => $detailTableOptions,
            'summaryTableOptions' => $tableOptions,
            'summaryTableLevel' => $summaryTableLevel,
            'summaryTarget' => $summaryTarget,
            'tapSummaries' => $tapSummaries,
            'clusterSummaries' => $clusterSummaries,
            'sfSummaries' => $sfSummaries,
            'taps' => DB::table('idsf')->when($this->suryaTapScopes(), fn ($query, $scopes) => $this->applyTapScopeFilter($query, 'idtap', $scopes))->distinct()->orderBy('idtap')->pluck('idtap'),
            'sfs' => $this->soldValidationSource($filters, false)->select('idsf', 'namasf')->distinct()->orderBy('namasf')->get(),
            'denoms' => $this->soldValidationSource($filters, false)->select('iddenom', 'denom_label as denom')->distinct()->orderBy('denom_label')->get(),
            'activeTab' => $activeTab,
            'exportLimit' => 500000,
        ]);
    }

    public function soldOutletSerials(Request $request)
    {
        $filters = $this->soldSerialFilters($request);
        $data = $request->validate([
            'id_outlet' => 'required|string|max:50',
            'page' => 'nullable|integer|min:1|max:10000',
            'match_type' => 'nullable|in:matched,mismatch',
        ]);
        $page = (int) ($data['page'] ?? 1);
        $matchType = $data['match_type'] ?? 'mismatch';
        $scanRanges = DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
            ->whereRaw("CONVERT(upload.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'scan' COLLATE utf8mb4_unicode_ci")
            ->whereRaw("CONVERT(upload.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
            ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])
            ->where('digipos.id_outlet', $data['id_outlet'])
            ->orderBy('digipos.serial_start')
            ->get(['digipos.serial_start', 'digipos.serial_end']);
        $rows = $this->soldValidationSource($filters)
            ->where('id_outlet', $data['id_outlet'])
            ->orderBy('denom_label')->orderBy('serial_start')
            ->offset(($page - 1) * 50)->limit(51)
            ->select('serial_start', 'serial_end', 'qty', 'iddenom', 'denom_label as denom', 'source')
            ->get();

        $displayRows = $rows->take(50)->flatMap(function ($row) use ($scanRanges, $matchType) {
            $start = (string) $row->serial_start;
            $end = (string) $row->serial_end;
            if ($matchType === 'matched') {
                $matched = [];
                foreach ($scanRanges as $scan) {
                    $scanStart = (string) $scan->serial_start;
                    $scanEnd = (string) $scan->serial_end;
                    if ($this->compareSerials($scanEnd, $start) < 0 || $this->compareSerials($scanStart, $end) > 0) continue;
                    $matchedStart = $this->compareSerials($start, $scanStart) >= 0 ? $start : $scanStart;
                    $matchedEnd = $this->compareSerials($end, $scanEnd) <= 0 ? $end : $scanEnd;
                    if ($this->compareSerials($matchedStart, $matchedEnd) <= 0) {
                        $matched[] = ['start' => $matchedStart, 'end' => $matchedEnd, 'qty' => (int) $this->serialRangeCount($matchedStart, $matchedEnd), 'denom' => $row->denom ?: $row->iddenom, 'source' => $row->source];
                    }
                }
                return $matched;
            }
            $cursor = $start;
            $unmatched = [];
            foreach ($scanRanges as $scan) {
                $scanStart = (string) $scan->serial_start;
                $scanEnd = (string) $scan->serial_end;
                if ($this->compareSerials($scanEnd, $cursor) < 0 || $this->compareSerials($scanStart, $end) > 0) continue;
                if ($this->compareSerials($scanStart, $cursor) > 0) {
                    $gapEnd = $this->subtractOneFromSerial($scanStart);
                    $unmatched[] = ['start' => $cursor, 'end' => $gapEnd, 'qty' => (int) $this->serialRangeCount($cursor, $gapEnd), 'denom' => $row->denom ?: $row->iddenom, 'source' => $row->source];
                }
                if ($this->compareSerials($scanEnd, $end) >= 0) { $cursor = null; break; }
                if ($this->compareSerials($scanEnd, $cursor) >= 0) $cursor = $this->incrementSerial($scanEnd);
            }
            if ($cursor !== null && $this->compareSerials($cursor, $end) <= 0) {
                $unmatched[] = ['start' => $cursor, 'end' => $end, 'qty' => (int) $this->serialRangeCount($cursor, $end), 'denom' => $row->denom ?: $row->iddenom, 'source' => $row->source];
            }
            return $unmatched;
        })->values();

        return response()->json([
            'rows' => $displayRows,
            'has_more' => $rows->count() > 50,
            'page' => $page,
        ]);
    }

    public function soldOutletSnLookup(Request $request)
    {
        $filters = $this->soldSerialFilters($request);
        $data = $request->validate([
            'id_outlet' => 'required|string|max:50',
            'sn' => ['required', 'regex:/^\\d{9,24}$/'],
        ]);
        $sn = $data['sn'];
        $nota = $this->soldValidationSource($filters)
            ->where('id_outlet', $data['id_outlet'])
            ->whereRaw('CAST(serial_start AS DECIMAL(24,0)) <= CAST(? AS DECIMAL(24,0))', [$sn])
            ->whereRaw('CAST(serial_end AS DECIMAL(24,0)) >= CAST(? AS DECIMAL(24,0))', [$sn])
            ->orderByDesc('sale_date')->limit(20)
            ->select('sale_date as tgl', 'serial_start', 'serial_end', 'qty', 'iddenom', 'denom_label as denom', 'source')
            ->get();

        $digipos = DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
            ->leftJoin('appsdumais as scan_outlet', 'scan_outlet.id_outlet', '=', 'digipos.id_outlet')
            ->whereRaw("CONVERT(upload.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'scan' COLLATE utf8mb4_unicode_ci")
            ->whereRaw("CONVERT(upload.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
            ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])
            ->whereRaw('CAST(digipos.serial_start AS DECIMAL(24,0)) <= CAST(? AS DECIMAL(24,0))', [$sn])
            ->whereRaw('CAST(digipos.serial_end AS DECIMAL(24,0)) >= CAST(? AS DECIMAL(24,0))', [$sn])
            ->orderByDesc('digipos.scan_date')->limit(20)
            ->get(['digipos.scan_date', 'digipos.id_outlet', 'scan_outlet.nama_outlet', 'digipos.serial_start', 'digipos.serial_end', 'digipos.product_name']);

        return response()->json([
            'sn' => $sn,
            'nota' => $nota->map(fn ($row) => [
                'date' => $row->tgl,
                'source' => $row->source,
                'denom' => $row->denom ?: $row->iddenom,
            ])->values(),
            'digipos' => $digipos->map(fn ($row) => [
                'date' => $row->scan_date,
                'id_outlet' => $row->id_outlet,
                'outlet' => $row->nama_outlet ?: 'Nama outlet tidak tersedia',
                'start' => $row->serial_start,
                'end' => $row->serial_end,
                'product' => $row->product_name ?: '—',
            ])->values(),
        ]);
    }

    public function soldColumnValues(Request $request)
    {
        $filters = $this->soldSerialFilters($request);
        $request->validate(['column' => 'required|in:nama_outlet,id_outlet,kecamatan,tap,namasf,kategori_outlet,qty,matched_qty,mismatch_qty,status,sf,outlet_count,matched_outlets,mismatch_outlets', 'summary_table' => 'nullable|in:tap,sf', 'q' => 'nullable|string|max:150', 'value_page' => 'nullable|integer|min:1|max:10000']);
        $column = $request->input('column');
        $summaryTableLevel = $request->input('summary_table') === 'sf' ? 'sf' : 'tap';
        if ($request->filled('summary_table')) {
            abort_unless(in_array($column, [$summaryTableLevel, 'outlet_count', 'matched_outlets', 'mismatch_outlets', 'qty', 'matched_qty', 'mismatch_qty'], true) || ($summaryTableLevel === 'sf' && $column === 'tap'), 422, 'Kolom ringkasan tidak valid.');
            if ($summaryTableLevel === 'sf' && $column === 'tap') {
                $values = DB::query()->fromSub($this->soldOutletRows($filters), 'outlet_rows')
                    ->select('outlet_rows.tap as value')->distinct()
                    ->when($request->filled('q'), fn ($query) => $query->where('outlet_rows.tap', 'like', '%'.$request->input('q').'%'))
                    ->orderBy('value')->offset((max(1, (int) $request->input('value_page', 1)) - 1) * 100)->limit(101)->pluck('value');

                return response()->json(['values' => $values->take(100)->map(fn ($value) => (string) $value)->values(), 'has_more' => $values->count() > 100]);
            }
            $rows = $this->soldSummaryRows($filters, $summaryTableLevel);
            $summaryExpression = $column === $summaryTableLevel
                ? ($summaryTableLevel === 'sf' ? '`outlet_rows`.`label`' : '`outlet_rows`.`summary_key`')
                : '`outlet_rows`.`'.$column.'`';
            $values = DB::query()->fromSub($rows, 'outlet_rows')
                ->selectRaw($summaryExpression.' AS value')
                ->distinct()
                ->when($request->filled('q'), fn ($query) => $query->whereRaw('CAST('.$summaryExpression.' AS CHAR) LIKE ?', ['%'.$request->input('q').'%']))
                ->orderBy('value')->offset((max(1, (int) $request->input('value_page', 1)) - 1) * 100)->limit(101)->pluck('value');

            return response()->json(['values' => $values->take(100)->map(fn ($value) => (string) $value)->values(), 'has_more' => $values->count() > 100]);
        }
        $rows = $this->soldOutletRows($filters);
        $values = DB::query()->fromSub($rows, 'outlet_rows')
            ->selectRaw($this->soldTableExpression($column).' AS value')
            ->distinct()
            ->when($request->filled('q'), fn ($query) => $query->whereRaw($this->soldTableExpression($column).' LIKE ?', ['%'.$request->input('q').'%']))
            ->orderBy('value')->offset((max(1, (int) $request->input('value_page', 1)) - 1) * 100)->limit(101)->pluck('value');

        return response()->json(['values' => $values->take(100)->map(fn ($value) => (string) $value)->values(), 'has_more' => $values->count() > 100]);
    }

    private function soldTableOptions(Request $request): array
    {
        $data = $request->validate([
            'columns' => 'nullable|array|max:16', 'columns.*' => 'array|min:1|max:100', 'columns.*.*' => 'nullable|string|max:5000',
            'exclude' => 'nullable|array|max:16', 'exclude.*' => 'array|min:1|max:100', 'exclude.*.*' => 'nullable|string|max:5000',
            'sort' => 'nullable|in:nama_outlet,id_outlet,kecamatan,tap,namasf,kategori_outlet,qty,matched_qty,mismatch_qty,status,sf,outlet_count,matched_outlets,mismatch_outlets',
            'direction' => 'nullable|in:asc,desc',
            'summary_tap_columns' => 'nullable|array|max:8', 'summary_tap_columns.*' => 'array|min:1|max:100', 'summary_tap_columns.*.*' => 'nullable|string|max:5000',
            'summary_tap_exclude' => 'nullable|array|max:8', 'summary_tap_exclude.*' => 'array|min:1|max:100', 'summary_tap_exclude.*.*' => 'nullable|string|max:5000',
            'summary_sf_columns' => 'nullable|array|max:8', 'summary_sf_columns.*' => 'array|min:1|max:100', 'summary_sf_columns.*.*' => 'nullable|string|max:5000',
            'summary_sf_exclude' => 'nullable|array|max:8', 'summary_sf_exclude.*' => 'array|min:1|max:100', 'summary_sf_exclude.*.*' => 'nullable|string|max:5000',
            'summary_sort' => 'nullable|in:tap,sf,outlet_count,matched_outlets,mismatch_outlets,qty,matched_qty,mismatch_qty',
            'summary_direction' => 'nullable|in:asc,desc', 'summary_table' => 'nullable|in:tap,sf',
        ]);
        foreach (['columns', 'exclude'] as $mode) {
            foreach ($data[$mode] ?? [] as $column => $values) {
                if (! in_array($column, ['nama_outlet','id_outlet','kecamatan','tap','namasf','kategori_outlet','qty','matched_qty','mismatch_qty','status','sf','outlet_count','matched_outlets','mismatch_outlets'], true)) {
                    throw ValidationException::withMessages(['column' => 'Kolom filter tidak valid.']);
                }
                $data[$mode][$column] = array_map(fn ($value) => (string) $value, $values);
            }
        }
        $data['sort'] ??= 'nama_outlet';
        $data['direction'] ??= 'asc';
        foreach (['summary_tap_columns', 'summary_tap_exclude', 'summary_sf_columns', 'summary_sf_exclude'] as $mode) {
            foreach ($data[$mode] ?? [] as $column => $values) {
                if (! in_array($column, ['tap','sf','outlet_count','matched_outlets','mismatch_outlets','qty','matched_qty','mismatch_qty'], true)) {
                    throw ValidationException::withMessages(['column' => 'Kolom ringkasan tidak valid.']);
                }
                $data[$mode][$column] = array_map(fn ($value) => (string) $value, $values);
            }
        }
        $data['summary_direction'] ??= 'asc';
        $data['summary_table'] ??= 'tap';

        return $data;
    }

    private function filterSoldSummaries($groups, string $level, array $options)
    {
        foreach (["summary_{$level}_columns", "summary_{$level}_exclude"] as $mode) {
            foreach ($options[$mode] ?? [] as $column => $values) {
                if ($column === $level) {
                    $groups = $groups->filter(fn ($group) => in_array((string) ($level === 'sf' ? $group->label : $group->key), $values, true));
                } elseif ($level === 'sf' && $column === 'tap') {
                    $groups = str_ends_with($mode, '_columns')
                        ? $groups->filter(fn ($group) => count(array_intersect($values, array_map('trim', explode(',', (string) $group->tap)))) > 0)
                        : $groups->reject(fn ($group) => count(array_intersect($values, array_map('trim', explode(',', (string) $group->tap)))) > 0);
                } elseif (str_ends_with($mode, '_columns')) {
                    $groups = $groups->filter(fn ($group) => in_array((string) ($group->{$column} ?? ''), $values, true));
                } else {
                    if ($level === 'sf' && $column === 'tap') {
                        $groups = $groups->reject(fn ($group) => count(array_intersect($values, array_map('trim', explode(',', (string) $group->tap)))) > 0);
                    } else {
                        $groups = $groups->reject(fn ($group) => in_array((string) ($group->{$column} ?? ''), $values, true));
                    }
                }
            }
        }
        if (($options['summary_table'] ?? '') === $level && ! empty($options['summary_sort'])) {
            $column = $options['summary_sort'];
            $groups = $groups->sortBy(fn ($group) => $column === $level ? ($level === 'sf' ? $group->label : $group->key) : ($group->{$column} ?? ''), SORT_NATURAL | SORT_FLAG_CASE, $options['summary_direction'] === 'desc')->values();
        }

        return $groups->values();
    }

    private function summarizeSoldOutlets($rows, string $key, string $label): object
    {
        return (object) [
            'key' => $key,
            'label' => $label,
            'outlet_count' => $rows->count(),
            'matched_outlets' => $rows->where('mismatch_qty', 0)->count(),
            'mismatch_outlets' => $rows->filter(fn ($row) => $row->mismatch_qty > 0)->count(),
            'qty' => (int) $rows->sum('qty'),
            'matched_qty' => (int) $rows->sum('matched_qty'),
            'mismatch_qty' => (int) $rows->sum('mismatch_qty'),
        ];
    }

    private function applyTapScopeFilter($query, string $expression, array $scopes)
    {
        if (! $scopes) return $query;
        $bindings = array_values($scopes);
        $values = implode(', ', array_fill(0, count($bindings), 'CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci'));

        return $query->whereRaw("CONVERT(($expression) USING utf8mb4) COLLATE utf8mb4_unicode_ci IN ({$values})", $bindings);
    }

    private function applyTextEquals($query, string $expression, string $value)
    {
        return $query->whereRaw("CONVERT(($expression) USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci", [$value]);
    }

    private function applyTextIn($query, string $expression, array $values, bool $not = false)
    {
        if (! $values) return $query;
        $bindings = array_values($values);
        $placeholders = implode(', ', array_fill(0, count($bindings), 'CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci'));
        return $query->whereRaw('CONVERT(('.$expression.') USING utf8mb4) COLLATE utf8mb4_unicode_ci '.($not ? 'NOT IN' : 'IN')." ({$placeholders})", $bindings);
    }

    private function soldTableExpression(string $column): string
    {
        return match ($column) {
            'sf' => "COALESCE(`outlet_rows`.`label`, '')",
            'outlet_count' => 'COALESCE(`outlet_rows`.`outlet_count`, 0)',
            'matched_outlets' => 'COALESCE(`outlet_rows`.`matched_outlets`, 0)',
            'mismatch_outlets' => 'COALESCE(`outlet_rows`.`mismatch_outlets`, 0)',
            'nama_outlet' => "COALESCE(NULLIF(`outlet_rows`.`nama_outlet`, ''), 'Nama outlet tidak tersedia')",
            'id_outlet' => "COALESCE(CAST(`outlet_rows`.`id_outlet` AS CHAR), '')",
            'kecamatan' => "COALESCE(`outlet_rows`.`kecamatan`, '')",
            'tap' => "COALESCE(`outlet_rows`.`tap`, '')",
            'namasf' => "COALESCE(NULLIF(`outlet_rows`.`namasf`, ''), '—')",
            'kategori_outlet' => "COALESCE(NULLIF(`outlet_rows`.`kategori_outlet`, ''), 'Reguler')",
            'qty' => 'COALESCE(`outlet_rows`.`qty`, 0)',
            'matched_qty' => 'COALESCE(`outlet_rows`.`matched_qty`, 0)',
            'mismatch_qty' => 'COALESCE(`outlet_rows`.`mismatch_qty`, 0)',
            'status' => 'CASE WHEN `mismatch_qty` = 0 THEN \'Sesuai\' ELSE \'Perlu dicek\' END',
            default => throw ValidationException::withMessages(['column' => 'Kolom tidak valid.']),
        };
    }

    private function soldOutletRows(array $filters)
    {
        $outletTotals = DB::query()->fromSub($this->soldValidationRows($filters), 'rows')
            ->selectRaw("id_outlet, MAX(nama_outlet COLLATE utf8mb4_unicode_ci) AS nama_outlet, COALESCE(MAX(tap COLLATE utf8mb4_unicode_ci), CONVERT(0x54616e706120544150 USING utf8mb4) COLLATE utf8mb4_unicode_ci) AS tap, MAX(idsf COLLATE utf8mb4_unicode_ci) AS idsf, COALESCE(MAX(NULLIF(TRIM(outlet_sf), '') COLLATE utf8mb4_unicode_ci), MAX(NULLIF(TRIM(namasf), '') COLLATE utf8mb4_unicode_ci)) AS namasf, COALESCE(MAX(NULLIF(TRIM(kategori_outlet), '') COLLATE utf8mb4_unicode_ci), CONVERT(0x526567756c6572 USING utf8mb4) COLLATE utf8mb4_unicode_ci) AS kategori_outlet, MAX(kecamatan COLLATE utf8mb4_unicode_ci) AS kecamatan, SUM(qty) AS qty, SUM(LEAST(qty, matched_qty)) AS matched_qty")
            ->groupBy('id_outlet');
        return DB::query()->fromSub($outletTotals, 'outlet_totals')
            ->selectRaw('id_outlet, nama_outlet, tap, idsf, namasf, kategori_outlet, kecamatan, qty, matched_qty, GREATEST(qty - matched_qty, 0) AS mismatch_qty');
    }

    private function soldSummaryRows(array $filters, string $level)
    {
        $source = $this->soldOutletRows($filters);
        $key = $level === 'tap' ? 'tap' : 'namasf';
        $label = $level === 'tap' ? 'tap' : 'namasf';

        $query = DB::query()->fromSub($source, 'summary_source');
        if ($level === 'sf') {
            $query->whereRaw("summary_source.id_outlet NOT LIKE 'NON-DIGIPOS-%' AND EXISTS (SELECT 1 FROM idsf AS registered_sf WHERE registered_sf.idsf LIKE 'SF%' AND CONVERT(registered_sf.namasf USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(summary_source.namasf USING utf8mb4) COLLATE utf8mb4_unicode_ci)");
        }

        return $query
            ->selectRaw("$key AS `summary_key`, $label AS label, COUNT(*) AS outlet_count, SUM(CASE WHEN mismatch_qty = 0 THEN 1 ELSE 0 END) AS matched_outlets, SUM(CASE WHEN mismatch_qty > 0 THEN 1 ELSE 0 END) AS mismatch_outlets, SUM(qty) AS qty, SUM(matched_qty) AS matched_qty, SUM(mismatch_qty) AS mismatch_qty")
            ->groupBy($key);
    }

    public function downloadSoldSerials(Request $request)
    {
        $filters = $this->soldSerialFilters($request);
        abort_unless(Schema::hasTable('mobile_sales_serials'), 503, 'Data SN Mobile SURYA belum tersedia. Jalankan migrasi terbaru terlebih dahulu.');

        $query = $this->soldValidationRows($filters);
        $serialCount = (int) (clone $query)->sum('qty');
        if ($serialCount > 500000) {
            return redirect()->route('surya.product-tracking.sold', $request->query())
                ->withErrors(['download' => 'Hasil lebih dari 500.000 SN. Persempit tanggal atau filter agar file tetap aman diproses.']);
        }

        $filename = 'sn-'.$filters['tab'].'-'.$filters['date'].'.csv';
        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal ST', 'Sumber', 'ID Outlet', 'Nama Outlet', 'TAP', 'Sales Force', 'Produk', 'Group Product', 'Sub Product', 'Validity Product', 'SN / Nomor']);

            foreach ($query->orderBy('sale_date')->orderBy('source_id')->cursor() as $row) {
                $current = (string) $row->serial_start;
                $last = (string) $row->serial_end;
                $qty = (int) $row->qty;
                $inferred = DenomGrouping::infer((object) [
                    'iddenom' => $row->iddenom,
                    'denom' => $row->denom_label,
                    'product_group' => $row->product_group,
                    'product' => $row->product,
                    'product_variant' => $row->product_variant,
                    'kategori_inject' => $row->kategori_inject,
                    'group_name' => $row->group_name,
                ]);
                $groupProduct = in_array(strtoupper(trim((string) $row->product)), ['SA', 'PV'], true)
                    ? strtoupper(trim((string) $row->product))
                    : $inferred['product'];
                $variant = strtoupper(trim((string) ($row->product_variant ?: $inferred['product_variant'])));
                $subProduct = $groupProduct === 'SA'
                    ? ($variant === 'BYU' ? 'SA BYU' : 'SA SIMPATI')
                    : ($variant === 'BYU' ? 'PV BYU' : 'PV REGULER');
                $validity = $groupProduct === 'SA' ? '' : trim((string) $row->group_name);
                for ($index = 0; $index < $qty; $index++) {
                    fputcsv($output, [
                        $row->sale_date, $row->source, $row->id_outlet,
                        $this->csvSafe($row->nama_outlet ?? ''), $this->csvSafe($row->tap ?? ''), $this->csvSafe($row->namasf ?? $row->idsf),
                        $this->csvSafe($row->denom_label ?? $row->iddenom), $groupProduct, $subProduct,
                        $this->csvSafe($validity), $current,
                    ]);
                    if ($current === $last) break;
                    $current = $this->incrementSerial($current);
                }
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function soldSerialFilters(Request $request): array
    {
        $today = now('Asia/Jakarta')->toDateString();
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'tap' => 'nullable|string|max:100',
            'sf' => 'nullable|string|max:150',
            'iddenom' => 'nullable|string|max:25',
            'tab' => 'nullable|in:approve,progress',
        ]);
        $date = $data['date'] ?? $today;

        return [
            'date' => $date,
            'date_from' => $date,
            'date_to' => $date,
            'tap' => $data['tap'] ?? '',
            'sf' => $data['sf'] ?? '',
            'iddenom' => $data['iddenom'] ?? '',
            'identifier_type' => 'sn',
            'tab' => $data['tab'] ?? 'progress',
        ];
    }

    private function soldValidationSource(array $filters, bool $applyFilters = true)
    {
        $hasOutletCategory = Schema::hasColumn('appsdumais', 'kategori_outlet');
        $mobileOutletCategory = $hasOutletCategory
            ? DB::raw('CONVERT(outlet.kategori_outlet USING utf8mb4) COLLATE utf8mb4_unicode_ci AS kategori_outlet')
            : DB::raw("_utf8mb4'Reguler' COLLATE utf8mb4_unicode_ci AS kategori_outlet");
        $formOutletCategory = $hasOutletCategory
            ? DB::raw('CONVERT(form_outlet.kategori_outlet USING utf8mb4) COLLATE utf8mb4_unicode_ci AS kategori_outlet')
            : DB::raw("_utf8mb4'Reguler' COLLATE utf8mb4_unicode_ci AS kategori_outlet");
        $latestNonDigiposVisit = DB::table('surya_visits')
            ->selectRaw('id_outlet, MAX(id) AS id')
            ->whereRaw("CONVERT(visit_type USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'non_digipos' COLLATE utf8mb4_unicode_ci")
            ->groupBy('id_outlet');
        $mobile = $this->mobileSerialsQuery($filters, false)
            ->whereRaw("CONVERT(serial.identifier_type USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'sn' COLLATE utf8mb4_unicode_ci")
            ->leftJoinSub(clone $latestNonDigiposVisit, 'latest_non_digipos_visit', fn ($join) => $join->on('latest_non_digipos_visit.id_outlet', '=', 'sale.id_outlet'))
            ->leftJoin('surya_visits as non_digipos_visit', 'non_digipos_visit.id', '=', 'latest_non_digipos_visit.id');
        $mobile->select([
            'serial.id as source_id', 'serial.iddenom', 'serial.serial_start', 'serial.serial_end', 'serial.qty',
            'sale.tgl as sale_date', 'sale.id_outlet', 'sale.idsf',
            DB::raw("COALESCE(CONVERT(owner_sf.idtap USING utf8mb4) COLLATE utf8mb4_unicode_ci, CONVERT(outlet.tap USING utf8mb4) COLLATE utf8mb4_unicode_ci) AS tap"),
            DB::raw('COALESCE(CONVERT(outlet.nama_outlet USING utf8mb4) COLLATE utf8mb4_unicode_ci, CONVERT(non_digipos_visit.outlet_name USING utf8mb4) COLLATE utf8mb4_unicode_ci) AS nama_outlet'),
            DB::raw('CONVERT(outlet.kecamatan USING utf8mb4) COLLATE utf8mb4_unicode_ci AS kecamatan'),
            $mobileOutletCategory,
            DB::raw('CONVERT(outlet.sf USING utf8mb4) COLLATE utf8mb4_unicode_ci AS outlet_sf'),
            DB::raw('CONVERT(owner_sf.namasf USING utf8mb4) COLLATE utf8mb4_unicode_ci AS namasf'),
            DB::raw('CONVERT(denom.denom USING utf8mb4) COLLATE utf8mb4_unicode_ci AS denom_label'),
            'denom.product', 'denom.product_variant', 'denom.product_group', 'denom.kategori_inject', 'denom.group_name',
            DB::raw('CONVERT(0x5346204d6f62696c65 USING utf8mb4) COLLATE utf8mb4_unicode_ci AS source'),
        ]);

        $webForm = DB::table('keluarsf as form_sale')
            ->join('denom as form_denom', 'form_denom.iddenom', '=', 'form_sale.iddenom')
            ->leftJoin('appsdumais as form_outlet', 'form_outlet.id_outlet', '=', 'form_sale.id_outlet')
            ->leftJoin('idsf as form_sf', 'form_sf.idsf', '=', 'form_sale.idsf')
            ->leftJoinSub(clone $latestNonDigiposVisit, 'latest_non_digipos_visit', fn ($join) => $join->on('latest_non_digipos_visit.id_outlet', '=', 'form_sale.id_outlet'))
            ->leftJoin('surya_visits as non_digipos_visit', 'non_digipos_visit.id', '=', 'latest_non_digipos_visit.id')
            ->whereBetween('form_sale.tgl', [$filters['date_from'], $filters['date_to']])
            ->whereNotNull('form_sale.id_outlet')->whereRaw("TRIM(form_sale.id_outlet) <> ''")
            ->whereRaw("CONVERT(COALESCE(form_sale.tambahanket, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci NOT LIKE _utf8mb4'Mobile Approval ID: %' COLLATE utf8mb4_unicode_ci")
            ->whereRaw("(form_sale.identifier_type IS NULL OR CONVERT(form_sale.identifier_type USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'sn' COLLATE utf8mb4_unicode_ci)")
            ->whereRaw("(form_sale.serial_mode IS NULL OR CONVERT(form_sale.serial_mode USING utf8mb4) COLLATE utf8mb4_unicode_ci <> _utf8mb4'broken' COLLATE utf8mb4_unicode_ci)")
            ->whereNotNull('form_sale.sn_awal')->whereRaw("TRIM(form_sale.sn_awal) <> ''")
            ->whereRaw("(form_sale.sn_akhir IS NULL OR TRIM(CONVERT(form_sale.sn_akhir USING utf8mb4) COLLATE utf8mb4_unicode_ci) = _utf8mb4'' COLLATE utf8mb4_unicode_ci OR CONVERT(form_sale.sn_akhir USING utf8mb4) COLLATE utf8mb4_unicode_ci >= CONVERT(form_sale.sn_awal USING utf8mb4) COLLATE utf8mb4_unicode_ci)")
            ->select([
                'form_sale.idkeluar as source_id', 'form_sale.iddenom',
                DB::raw("CONVERT(form_sale.sn_awal USING utf8mb4) COLLATE utf8mb4_unicode_ci AS serial_start"),
                DB::raw("COALESCE(NULLIF(CONVERT(form_sale.sn_akhir USING utf8mb4) COLLATE utf8mb4_unicode_ci, _utf8mb4'' COLLATE utf8mb4_unicode_ci), CONVERT(form_sale.sn_awal USING utf8mb4) COLLATE utf8mb4_unicode_ci) AS serial_end"),
                'form_sale.qty', 'form_sale.tgl as sale_date', 'form_sale.id_outlet', 'form_sale.idsf',
                DB::raw("COALESCE(CONVERT(form_sf.idtap USING utf8mb4) COLLATE utf8mb4_unicode_ci, CONVERT(form_outlet.tap USING utf8mb4) COLLATE utf8mb4_unicode_ci) AS tap"),
                DB::raw('COALESCE(CONVERT(form_outlet.nama_outlet USING utf8mb4) COLLATE utf8mb4_unicode_ci, CONVERT(non_digipos_visit.outlet_name USING utf8mb4) COLLATE utf8mb4_unicode_ci) AS nama_outlet'),
                DB::raw('CONVERT(form_outlet.kecamatan USING utf8mb4) COLLATE utf8mb4_unicode_ci AS kecamatan'),
                $formOutletCategory,
                DB::raw('CONVERT(form_outlet.sf USING utf8mb4) COLLATE utf8mb4_unicode_ci AS outlet_sf'),
                DB::raw('CONVERT(form_sf.namasf USING utf8mb4) COLLATE utf8mb4_unicode_ci AS namasf'),
                DB::raw('CONVERT(form_denom.denom USING utf8mb4) COLLATE utf8mb4_unicode_ci AS denom_label'),
                'form_denom.product', 'form_denom.product_variant', 'form_denom.product_group', 'form_denom.kategori_inject', 'form_denom.group_name',
                DB::raw('CONVERT(0x466f726d205346204b656c756172 USING utf8mb4) COLLATE utf8mb4_unicode_ci AS source'),
            ]);

        $source = DB::query()->fromSub($mobile->unionAll($webForm), 'sales_rows');
        $scopes = $this->suryaTapScopes();
        if ($scopes) $this->applyTapScopeFilter($source, 'sales_rows.tap', $scopes);

        if ($applyFilters) {
            $source->when($filters['tap'] !== '', fn ($query) => $query->where('sales_rows.tap', $filters['tap']))
                ->when($filters['sf'] !== '', fn ($query) => $query->where('sales_rows.idsf', $filters['sf']))
                ->when($filters['iddenom'] !== '', fn ($query) => $query->where('sales_rows.iddenom', $filters['iddenom']));
        }

        return $source->select('sales_rows.*');
    }

    private function soldValidationRows(array $filters)
    {
        return DB::query()->fromSub($this->soldValidationSource($filters), 'sales_rows')
            ->select('sales_rows.*')
            ->selectSub(function ($query) use ($filters) {
                $query->from('product_tracking_digipos as digipos')
                    ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
                    ->whereRaw("CONVERT(upload.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'scan' COLLATE utf8mb4_unicode_ci")
                    ->whereRaw("CONVERT(upload.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
                    ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])
                    ->whereRaw('CONVERT(digipos.id_outlet USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(sales_rows.id_outlet USING utf8mb4) COLLATE utf8mb4_unicode_ci')
                    ->whereRaw('CAST(digipos.serial_start AS DECIMAL(24,0)) <= CAST(sales_rows.serial_end AS DECIMAL(24,0))')
                    ->whereRaw('CAST(digipos.serial_end AS DECIMAL(24,0)) >= CAST(sales_rows.serial_start AS DECIMAL(24,0))')
                    ->selectRaw('COALESCE(SUM(GREATEST(LEAST(CAST(digipos.serial_end AS DECIMAL(24,0)), CAST(sales_rows.serial_end AS DECIMAL(24,0))) - GREATEST(CAST(digipos.serial_start AS DECIMAL(24,0)), CAST(sales_rows.serial_start AS DECIMAL(24,0))) + 1, 0)), 0)');
            }, 'matched_qty');
    }

    private function mobileSerialsQuery(array $filters, bool $applyFilters = true)
    {
        $query = DB::table('mobile_sales_serials as serial')
            ->join('mobile_penjualan as sale', 'sale.id', '=', 'serial.mobile_penjualan_id')
            ->leftJoin('appsdumais as outlet', 'outlet.id_outlet', '=', 'sale.id_outlet')
            ->leftJoin('idsf as owner_sf', 'owner_sf.idsf', '=', 'sale.idsf')
            ->leftJoin('denom', 'denom.iddenom', '=', 'serial.iddenom')
            ->when($filters['tab'] === 'approve', fn ($query) => $query->whereRaw("CONVERT(sale.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'approved' COLLATE utf8mb4_unicode_ci"), fn ($query) => $query->whereRaw("CONVERT(sale.status USING utf8mb4) COLLATE utf8mb4_unicode_ci NOT IN (_utf8mb4'draft_wa' COLLATE utf8mb4_unicode_ci, _utf8mb4'rejected' COLLATE utf8mb4_unicode_ci)"))
            ->whereBetween('sale.tgl', [$filters['date_from'], $filters['date_to']]);

        $tapExpression = "COALESCE(NULLIF(CONVERT(owner_sf.idtap USING utf8mb4) COLLATE utf8mb4_unicode_ci, _utf8mb4'' COLLATE utf8mb4_unicode_ci), CONVERT(outlet.tap USING utf8mb4) COLLATE utf8mb4_unicode_ci)";
        if ($scopes = $this->suryaTapScopes()) {
            $this->applyTapScopeFilter($query, $tapExpression, $scopes);
        }
        if ($applyFilters) {
            $query->when($filters['tap'] !== '', fn ($builder) => $builder->whereRaw("CONVERT(($tapExpression) USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci", [$filters['tap']]))
                ->when($filters['sf'] !== '', fn ($builder) => $builder->where('sale.idsf', $filters['sf']))
                ->when($filters['iddenom'] !== '', fn ($builder) => $builder->where('serial.iddenom', $filters['iddenom']))
                ->when($filters['identifier_type'] !== '', fn ($builder) => $builder->where('serial.identifier_type', $filters['identifier_type']));
        }

        return $query;
    }

    private function incrementSerial(string $serial): string
    {
        for ($index = strlen($serial) - 1; $index >= 0; $index--) {
            if ($serial[$index] !== '9') {
                $serial[$index] = chr(ord($serial[$index]) + 1);
                return $serial;
            }
            $serial[$index] = '0';
        }

        return '1'.$serial;
    }

    private function csvSafe(string $value): string
    {
        return preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value;
    }

    public function imports(Request $request)
    {
        $this->requireEditor();
        $kind = $request->query('kind', 'scan');
        abort_unless(in_array($kind, ['scan', 'so-pv', 'so-sa', 'sales-stock'], true), 404);
        if ($kind === 'sales-stock') {
            abort_unless(Schema::hasTable('product_tracking_sales_stock_uploads'), 503, 'Fitur upload stok SN Sales belum diaktifkan. Jalankan migrasi terbaru terlebih dahulu.');
        }
        $period = $request->query('period', 'daily');
        abort_unless(in_array($period, ['daily', 'monthly'], true), 404);
        $scanClusterSql = "CASE WHEN LOWER(u.filename) REGEXP '^(pv|sa)_dumai_' THEN 'DUMAI' WHEN LOWER(u.filename) REGEXP '^(pv|sa)_(rohil|rokan_hilir)_' THEN 'ROHIL' WHEN UPPER(COALESCE(outlet.tap, '')) IN ('DUMAI','BENGKALIS','DURI','RUPAT','SEI PAKNING') THEN 'DUMAI' WHEN UPPER(COALESCE(outlet.tap, '')) IN ('BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG') THEN 'ROHIL' ELSE NULL END";
        $scanProductSql = "CASE WHEN LEFT(LOWER(u.filename), 3) = 'pv_' THEN 'PV' WHEN LEFT(LOWER(u.filename), 3) = 'sa_' THEN 'SA' WHEN CHAR_LENGTH(d.serial_start) = 12 THEN 'PV' WHEN CHAR_LENGTH(d.serial_start) IN (15,16) THEN 'SA' ELSE NULL END";
        $totalsQuery = $kind === 'scan' ? DB::table('product_tracking_digipos as d')
                ->join('product_tracking_uploads as u', 'u.id', '=', 'd.upload_id')
                ->leftJoin('appsdumais as outlet', 'outlet.id_outlet', '=', 'd.id_outlet')
                ->whereRaw("CONVERT(d.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'scan' COLLATE utf8mb4_unicode_ci")
                ->whereRaw("CONVERT(u.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'scan' COLLATE utf8mb4_unicode_ci")
                ->whereRaw("CONVERT(u.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
                ->whereNotNull('d.scan_date')
                ->selectRaw("SUM(CASE WHEN ({$scanClusterSql}) = 'DUMAI' AND ({$scanProductSql}) = 'PV' THEN d.serial_count ELSE 0 END) AS dumai_bengkalis_pv_qty")
                ->selectRaw("SUM(CASE WHEN ({$scanClusterSql}) = 'DUMAI' AND ({$scanProductSql}) = 'SA' THEN d.serial_count ELSE 0 END) AS dumai_bengkalis_sa_qty")
                ->selectRaw("SUM(CASE WHEN ({$scanClusterSql}) = 'ROHIL' AND ({$scanProductSql}) = 'PV' THEN d.serial_count ELSE 0 END) AS rokan_hilir_pv_qty")
                ->selectRaw("SUM(CASE WHEN ({$scanClusterSql}) = 'ROHIL' AND ({$scanProductSql}) = 'SA' THEN d.serial_count ELSE 0 END) AS rokan_hilir_sa_qty")
                ->selectRaw("SUM(CASE WHEN ({$scanProductSql}) = 'PV' THEN d.serial_count ELSE 0 END) AS total_pv_qty")
                ->selectRaw("SUM(CASE WHEN ({$scanProductSql}) = 'SA' THEN d.serial_count ELSE 0 END) AS total_sa_qty")
                ->selectRaw('SUM(d.serial_count) AS total_qty') : null;
        if ($totalsQuery) {
            if ($period === 'monthly') {
                $totalsQuery->selectRaw('MIN(d.scan_date) AS scan_date')
                    ->groupByRaw("DATE_FORMAT(d.scan_date, '%Y-%m')")
                    ->orderByRaw("DATE_FORMAT(d.scan_date, '%Y-%m') DESC");
            } else {
                $totalsQuery->addSelect('d.scan_date')->groupBy('d.scan_date')->orderByDesc('d.scan_date');
            }
            $dailyTotals = $totalsQuery->simplePaginate(10)->appends(['kind' => $kind, 'period' => $period]);
        } else {
            $dailyTotals = collect();
        }

        return view('surya.product-tracking.imports', [
            'kind' => $kind,
            'period' => $period,
            'uploads' => $kind === 'sales-stock'
                ? DB::table('product_tracking_sales_stock_uploads')->orderByDesc('id')->limit(30)->get()
                : $this->applyTextIn(DB::table('product_tracking_uploads'), 'kind', [$kind])->whereRaw("CONVERT(status USING utf8mb4) COLLATE utf8mb4_unicode_ci IN (_utf8mb4'complete' COLLATE utf8mb4_unicode_ci, _utf8mb4'duplicate' COLLATE utf8mb4_unicode_ci)")->orderByDesc('id')->limit(30)->get(),
            'pending' => $this->applyTextEquals(DB::table('product_tracking_uploads'), 'kind', $kind)->whereRaw("CONVERT(status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'mapping' COLLATE utf8mb4_unicode_ci")->orderByDesc('id')->get(),
            'dailyTotals' => $dailyTotals,
        ]);
    }

    public function stockOutlet(Request $request)
    {
        $product = strtoupper((string) $request->query('product', 'PV'));
        abort_unless(in_array($product, ['PV', 'SA'], true), 404);
        $outletCategory = Schema::hasColumn('appsdumais', 'kategori_outlet')
            ? DB::raw('outlet.kategori_outlet AS kategori_outlet')
            : DB::raw("'Reguler' AS kategori_outlet");
        $soKind = 'so-'.strtolower($product);
        abort_unless(Schema::hasTable('mobile_sales_serials') && Schema::hasColumn('keluarsf', 'sn_awal') && Schema::hasColumn('keluarsf', 'sn_akhir'), 503, 'Sumber SN ST Nota belum tersedia. Jalankan migrasi terbaru terlebih dahulu.');
        $serialLengthSql = $product === 'PV' ? '12' : '15,16';
        $mobileSt = DB::table('mobile_sales_serials as serial')
            ->join('mobile_penjualan as sale', 'sale.id', '=', 'serial.mobile_penjualan_id')
            ->whereRaw("CONVERT(serial.identifier_type USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'sn' COLLATE utf8mb4_unicode_ci")
            ->whereRaw("CONVERT(sale.status USING utf8mb4) COLLATE utf8mb4_unicode_ci NOT IN (_utf8mb4'draft_wa' COLLATE utf8mb4_unicode_ci, _utf8mb4'rejected' COLLATE utf8mb4_unicode_ci)")
            ->whereNotNull('sale.id_outlet')->whereRaw("CHAR_LENGTH(serial.serial_start) IN ({$serialLengthSql})")
            ->selectRaw('sale.id_outlet, serial.serial_start, serial.serial_end, serial.qty AS serial_count, sale.tgl AS st_date');
        $webSt = DB::table('keluarsf as form_sale')
            ->whereNotNull('form_sale.id_outlet')->whereRaw("TRIM(form_sale.id_outlet) <> ''")
            ->whereRaw("CONVERT(COALESCE(form_sale.tambahanket, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci NOT LIKE _utf8mb4'Mobile Approval ID: %' COLLATE utf8mb4_unicode_ci")
            ->whereRaw("(form_sale.identifier_type IS NULL OR CONVERT(form_sale.identifier_type USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'sn' COLLATE utf8mb4_unicode_ci)")
            ->whereRaw("(form_sale.serial_mode IS NULL OR CONVERT(form_sale.serial_mode USING utf8mb4) COLLATE utf8mb4_unicode_ci <> _utf8mb4'broken' COLLATE utf8mb4_unicode_ci)")
            ->whereNotNull('form_sale.sn_awal')->whereRaw("TRIM(form_sale.sn_awal) <> ''")
            ->whereRaw("(form_sale.sn_akhir IS NULL OR TRIM(CONVERT(form_sale.sn_akhir USING utf8mb4) COLLATE utf8mb4_unicode_ci) = _utf8mb4'' COLLATE utf8mb4_unicode_ci OR CONVERT(form_sale.sn_akhir USING utf8mb4) COLLATE utf8mb4_unicode_ci >= CONVERT(form_sale.sn_awal USING utf8mb4) COLLATE utf8mb4_unicode_ci)")
            ->whereRaw("CHAR_LENGTH(form_sale.sn_awal) IN ({$serialLengthSql})")
            ->selectRaw("form_sale.id_outlet, form_sale.sn_awal AS serial_start, COALESCE(NULLIF(form_sale.sn_akhir, ''), form_sale.sn_awal) AS serial_end, form_sale.qty AS serial_count, form_sale.tgl AS st_date");
        $stSources = $mobileSt->unionAll($webSt);
        $st = DB::query()->fromSub($stSources, 'source')
            ->selectRaw('source.id_outlet, SUM(source.serial_count) AS st_qty')->groupBy('source.id_outlet');
        $so = DB::table('product_tracking_digipos as so')
            ->join('product_tracking_uploads as u', 'u.id', '=', 'so.upload_id')
            ->whereRaw("CONVERT(so.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci", [$soKind])
            ->whereRaw("CONVERT(u.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci", [$soKind])
            ->whereRaw("CONVERT(u.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
            ->selectRaw('so.id_outlet, SUM(so.serial_count) AS so_qty')->groupBy('so.id_outlet');
        $redeem = DB::query()->fromSub($stSources, 'st')
            ->join('product_tracking_digipos as so', function ($join) use ($soKind, $serialLengthSql) {
                $join->on('so.id_outlet', '=', 'st.id_outlet')->where('so.kind', '=', $soKind)
                    ->whereRaw("CHAR_LENGTH(st.serial_start) IN ({$serialLengthSql})")
                    ->whereRaw('CAST(so.serial_start AS DECIMAL(24,0)) <= CAST(st.serial_end AS DECIMAL(24,0))')
                    ->whereRaw('CAST(so.serial_end AS DECIMAL(24,0)) >= CAST(st.serial_start AS DECIMAL(24,0))');
            })
            ->join('product_tracking_uploads as so_upload', 'so_upload.id', '=', 'so.upload_id')
            ->whereRaw("CONVERT(so_upload.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci", [$soKind])
            ->whereRaw("CONVERT(so_upload.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
            ->selectRaw('st.id_outlet, SUM(GREATEST(LEAST(CAST(st.serial_end AS DECIMAL(24,0)), CAST(so.serial_end AS DECIMAL(24,0))) - GREATEST(CAST(st.serial_start AS DECIMAL(24,0)), CAST(so.serial_start AS DECIMAL(24,0))) + 1, 0)) AS redeemed_qty')
            ->selectRaw('SUM(GREATEST(LEAST(CAST(st.serial_end AS DECIMAL(24,0)), CAST(so.serial_end AS DECIMAL(24,0))) - GREATEST(CAST(st.serial_start AS DECIMAL(24,0)), CAST(so.serial_start AS DECIMAL(24,0))) + 1, 0) * GREATEST(DATEDIFF(so.sold_at, st.st_date), 0)) AS days_to_customer_weighted')
            ->groupBy('st.id_outlet');
        $outletQuery = DB::table('appsdumais as outlet')
            ->leftJoinSub($st, 'st', 'st.id_outlet', '=', 'outlet.id_outlet')
            ->leftJoinSub($so, 'so', 'so.id_outlet', '=', 'outlet.id_outlet')
            ->leftJoinSub($redeem, 'redeem', 'redeem.id_outlet', '=', 'outlet.id_outlet')
            ->select('outlet.id_outlet', 'outlet.nama_outlet', 'outlet.kecamatan', 'outlet.tap', 'outlet.sf')
            ->addSelect($outletCategory)
            ->selectRaw('COALESCE(st.st_qty, 0) AS st_qty, COALESCE(so.so_qty, 0) AS so_qty, COALESCE(redeem.redeemed_qty, 0) AS redeemed_qty, GREATEST(COALESCE(st.st_qty, 0) - COALESCE(redeem.redeemed_qty, 0), 0) AS remaining_qty')
            ->selectRaw('NULL AS days_sf_to_outlet, CASE WHEN COALESCE(redeem.redeemed_qty, 0) > 0 THEN ROUND(redeem.days_to_customer_weighted / redeem.redeemed_qty, 1) ELSE NULL END AS days_outlet_to_customer')
            ->where(function ($query) { $query->whereNotNull('st.id_outlet')->orWhereNotNull('so.id_outlet'); })
            ->orderBy('outlet.nama_outlet')->orderBy('outlet.id_outlet');
        if ($scopes = $this->suryaTapScopes()) $this->applyTapScopeFilter($outletQuery, 'outlet.tap', $scopes);
        $outlets = $outletQuery->paginate(20)->withQueryString();
        return view('surya.product-tracking.stock-outlet', compact('product', 'outlets', 'soKind'));
    }

    public function downloadDuplicateScanReport(int $upload)
    {
        $this->requireEditor();
        $record = DB::table('product_tracking_uploads')->where('id', $upload)
            ->whereRaw("CONVERT(kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'scan' COLLATE utf8mb4_unicode_ci")
            ->whereRaw("CONVERT(status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'mapping' COLLATE utf8mb4_unicode_ci")->first();
        abort_unless($record, 404);
        $path = "product-tracking/duplicate-reports/{$upload}.csv";
        abort_unless(Storage::disk('local')->exists($path), 404, 'Belum ada laporan SN duplikat untuk upload ini.');
        return Storage::disk('local')->download($path, 'sn-duplikat-upload-'.$upload.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function salesStock()
    {
        $this->requireEditor();
        abort_unless(Schema::hasTable('product_tracking_sales_stock_uploads'), 503, 'Fitur upload stok SN Sales belum diaktifkan. Jalankan migrasi terbaru terlebih dahulu.');
        return view('surya.product-tracking.sales-stock', [
            'uploads' => DB::table('product_tracking_sales_stock_uploads')->orderByDesc('id')->limit(30)->get(),
        ]);
    }

    public function salesStockTemplate()
    {
        $this->requireEditor();
        $headers = ['SFCODE', 'TAP', 'CLUSTER', 'IDDENOM', 'SN AWAL', 'SN AKHIR', 'QTY'];
        return response(implode(',', $headers)."\r\n", 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=template-stok-sn-sales.csv',
        ]);
    }

    public function uploadSalesStock(Request $request, ProductTrackingFile $reader)
    {
        $this->requireEditor();
        $data = $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:102400']);
        try {
            $headers = $reader->headers($data['file']);
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['file' => 'File tidak bisa dibaca. Pastikan baris pertama berisi header template.']);
        }
        $expected = ['SFCODE', 'TAP', 'CLUSTER', 'IDDENOM', 'SN AWAL', 'SN AKHIR', 'QTY'];
        $normalized = array_map(fn ($header) => mb_strtoupper(preg_replace('/\\s+/', ' ', trim(preg_replace('/^\\xEF\\xBB\\xBF/', '', (string) $header)))), array_values($headers));
        if ($normalized !== $expected || count($headers) !== count($expected)) {
            throw ValidationException::withMessages(['file' => 'Header harus berurutan persis: '.implode(' | ', $expected).'.']);
        }

        $rows = [];
        $intervals = [];
        $denomLengths = [];
        $temporaryPath = $data['file']->storeAs('product-tracking/sales-stock-pending', (string) Str::uuid().'.'.$data['file']->getClientOriginalExtension(), 'local');
        try {
            foreach ($reader->rows(Storage::disk('local')->path($temporaryPath), $headers) as $rowNumber => $values) {
                $row = array_map(fn ($value) => trim((string) $value), array_values($values));
                if (count(array_filter($row, fn ($value) => $value !== '')) === 0) continue;
                [$sfcode, $tap, $cluster, $iddenom, $start, $end, $qtyRaw] = array_pad($row, 7, '');
                if (in_array('', [$sfcode, $tap, $cluster, $iddenom, $start, $end], true)) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: SFCODE, TAP, CLUSTER, IDDENOM, SN AWAL, dan SN AKHIR wajib diisi."]);
                }
                if (mb_strlen($iddenom) > 25) throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: IDDENOM maksimal 25 karakter."]);
                if (! preg_match('/^\\d{12}$|^\\d{15}$|^0\\d{15}$/', $start) || ! preg_match('/^\\d{12}$|^\\d{15}$|^0\\d{15}$/', $end) || strlen($start) !== strlen($end)) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: SN harus 12 digit untuk PV atau 15 digit untuk SA (boleh 16 digit jika diawali 0); panjang SN awal dan akhir harus sama."]);
                }
                if (strcmp($end, $start) < 0) throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: SN akhir harus sama dengan atau lebih besar dari SN awal."]);
                if ($qtyRaw !== '' && (! preg_match('/^\\d+$/', $qtyRaw) || (int) $qtyRaw < 1 || (int) $qtyRaw > 2000000)) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: QTY harus kosong atau bilangan bulat antara 1 dan 2.000.000."]);
                }
                $rangeQty = (int) $end - (int) $start + 1;
                if ($rangeQty < 1 || $rangeQty > 2000000) throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: rentang SN maksimal 2.000.000 nomor."]);
                $qty = $qtyRaw === '' ? $rangeQty : (int) $qtyRaw;
                if ($qty !== $rangeQty) throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: QTY harus sama dengan jumlah SN dalam rentang."]);
                $group = $sfcode."\x1f".strlen($start);
                $intervals[$group][] = ['start' => $start, 'end' => $end, 'row' => $rowNumber];
                $denomLengths[$iddenom][$rowNumber] = $start;
                $rows[] = ['sfcode' => $sfcode, 'tap' => $tap, 'cluster' => $cluster, 'iddenom' => $iddenom, 'serial_start' => $start, 'serial_end' => $end, 'qty' => $qty];
            }
        } finally {
            Storage::disk('local')->delete($temporaryPath);
        }
        if ($rows === []) throw ValidationException::withMessages(['file' => 'File tidak berisi data stok SN.']);
        $denoms = DB::table('denom')->whereIn('iddenom', array_keys($denomLengths))->get(['iddenom', 'product_group', 'kategori_inject', 'group_name'])->keyBy('iddenom');
        foreach ($denomLengths as $iddenom => $serials) {
            $denom = $denoms->get($iddenom);
            if (! $denom) throw ValidationException::withMessages(['file' => "IDDENOM {$iddenom} tidak ditemukan di master denom."]);
            $product = DenomGrouping::infer($denom)['product'];
            $allowedLengths = $product === 'SA' ? [15, 16] : [12];
            foreach ($serials as $rowNumber => $serial) {
                $length = strlen($serial);
                if (! in_array($length, $allowedLengths, true) || ($product === 'SA' && $length === 16 && ! str_starts_with($serial, '0'))) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: IDDENOM {$iddenom} adalah produk {$product}, jadi SN harus ".($product === 'SA' ? '15 digit, atau 16 digit diawali 0.' : '12 digit.')]);
                }
            }
        }
        foreach ($intervals as $ranges) {
            usort($ranges, fn ($a, $b) => strcmp($a['start'], $b['start']));
            $previous = null;
            foreach ($ranges as $range) {
                if ($previous !== null && strcmp($range['start'], $previous['end']) <= 0) {
                    throw ValidationException::withMessages(['file' => "Rentang SN duplikat/bertumpuk pada baris {$previous['row']} dan {$range['row']} untuk SFCODE yang sama."]);
                }
                $previous = $range;
            }
        }

        DB::transaction(function () use ($data, $rows) {
            $now = now();
            $uploadId = DB::table('product_tracking_sales_stock_uploads')->insertGetId([
                'filename' => mb_substr(basename($data['file']->getClientOriginalName()), 0, 255),
                'row_count' => count($rows), 'username' => auth()->user()->username,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach (array_chunk($rows, 500) as $chunk) {
                $chunk = array_map(fn ($row) => $row + ['upload_id' => $uploadId, 'created_at' => $now, 'updated_at' => $now], $chunk);
                DB::table('product_tracking_sales_stock_ranges')->insert($chunk);
            }
        });

        return redirect()->route('surya.product-tracking.imports', ['kind' => 'sales-stock'])->with('success', number_format(count($rows)).' baris stok SN Sales berhasil diunggah.');
    }

    public function stageImport(Request $request, ProductTrackingFile $reader)
    {
        $this->requireEditor();
        $data = $request->validate([
            'kind' => 'required|in:scan,so-pv,so-sa,sales-stock',
            'scan_date' => 'nullable|date|before_or_equal:today',
            'file' => 'required|file|mimes:csv,txt|max:102400',
        ]);
        try {
            $headers = $reader->headers($data['file']);
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['file' => 'File tidak bisa dibaca. Pastikan baris pertama berisi nama kolom.']);
        }
        if (count($headers) < 2 || count($headers) > 80) {
            throw ValidationException::withMessages(['file' => 'CSV harus berisi 2–80 kolom dengan header di baris pertama.']);
        }
        $storedName = (string) Str::uuid().'.'.$data['file']->getClientOriginalExtension();
        $path = $data['file']->storeAs('product-tracking/pending', $storedName, 'local');
        $id = DB::table('product_tracking_uploads')->insertGetId([
            'kind' => $data['kind'], 'scan_date' => $data['scan_date'] ?? null,
            'filename' => mb_substr(basename($data['file']->getClientOriginalName()), 0, 255),
            'headers' => json_encode($headers, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'storage_path' => $path, 'status' => 'mapping', 'row_count' => 0,
            'username' => auth()->user()->username, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return redirect()->route('surya.product-tracking.mapping', $id)->with('success', 'File berhasil dibaca. Petakan kolom sebelum menyimpan datanya.');
    }

    public function mapping(int $upload, ProductTrackingFile $reader)
    {
        $this->requireEditor();
        $record = DB::table('product_tracking_uploads')->where('id', $upload)->whereRaw("CONVERT(status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'mapping' COLLATE utf8mb4_unicode_ci")->first();
        abort_unless($record && $record->kind !== 'warehouse' && $record->storage_path && Storage::disk('local')->exists($record->storage_path), 404);
        $headers = json_decode($record->headers, true, 512, JSON_THROW_ON_ERROR);
        return view('surya.product-tracking.mapping', [
            'upload' => $record,
            'headers' => $headers,
            'preview' => $reader->preview(Storage::disk('local')->path($record->storage_path), $headers),
            'duplicateReportAvailable' => $record->kind === 'scan' && Storage::disk('local')->exists("product-tracking/duplicate-reports/{$record->id}.csv"),
        ]);
    }

    public function cancelImport(int $upload)
    {
        $this->requireEditor();
        $record = DB::table('product_tracking_uploads')->where('id', $upload)->whereRaw("CONVERT(status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'mapping' COLLATE utf8mb4_unicode_ci")->first();
        abort_unless($record && $record->kind !== 'warehouse', 404);

        if ($record->storage_path) {
            Storage::disk('local')->delete($record->storage_path);
        }
        DB::table('product_tracking_uploads')->where('id', $upload)->whereRaw("CONVERT(status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'mapping' COLLATE utf8mb4_unicode_ci")->delete();

        return redirect()->route('surya.product-tracking.imports', ['kind' => $record->kind])->with('success', 'Upload dibatalkan dan file sementara dihapus.');
    }

    public function completeImport(Request $request, int $upload, ProductTrackingFile $reader)
    {
        $this->requireEditor();
        $record = DB::table('product_tracking_uploads')->where('id', $upload)->whereRaw("CONVERT(status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'mapping' COLLATE utf8mb4_unicode_ci")->first();
        abort_unless($record && $record->kind !== 'warehouse' && $record->storage_path && Storage::disk('local')->exists($record->storage_path), 404);
        $headers = json_decode($record->headers, true, 512, JSON_THROW_ON_ERROR);
        $columnRule = Rule::in(array_map('strval', array_keys($headers)));
        $mapping = $request->validate([
            'serial_column' => ['nullable', $columnRule],
            'outlet_column' => ['required', $columnRule],
            'sf_column' => ['nullable', Rule::in(array_map('strval', array_keys($headers)))],
            'date_column' => ['nullable', Rule::in(array_map('strval', array_keys($headers)))],
            'product_column' => ['nullable', Rule::in(array_map('strval', array_keys($headers)))],
        ]);
        if (empty($mapping['serial_column'])) throw ValidationException::withMessages(['serial_column' => 'Pilih kolom SN.']);
        if ($record->kind !== 'warehouse' && empty($mapping['date_column'])) {
            throw ValidationException::withMessages(['date_column' => 'Pilih kolom tanggal agar tanggal scan/redeem tercatat otomatis.']);
        }
        $path = Storage::disk('local')->path($record->storage_path);
        $now = now();
        $count = 0;
        $detectedScanDate = $record->scan_date;
        $batch = [];
        $range = null;
        $seenSerials = [];
        $duplicateRanges = [];
        $duplicateReportPath = "product-tracking/duplicate-reports/{$record->id}.csv";
        $seenTable = 'pt_import_seen_serials';
        DB::statement("DROP TEMPORARY TABLE IF EXISTS `{$seenTable}`");
        DB::statement("CREATE TEMPORARY TABLE `{$seenTable}` (serial_number VARBINARY(24) NOT NULL PRIMARY KEY, source_row INT UNSIGNED NOT NULL) ENGINE=InnoDB");
        $scanRangeBatch = [];
        $flushSeenSerials = function () use (&$seenSerials, $seenTable) {
            if ($seenSerials === []) return;
            $existing = DB::table($seenTable)->whereIn('serial_number', array_keys($seenSerials))->first(['serial_number', 'source_row']);
            if ($existing) {
                $serial = (string) $existing->serial_number;
                throw ValidationException::withMessages(['file' => "SN duplikat {$serial} ditemukan di baris {$existing->source_row} dan {$seenSerials[$serial]}. Hapus salah satu baris lalu upload ulang."]);
            }
            $insert = [];
            foreach ($seenSerials as $serial => $sourceRow) $insert[] = ['serial_number' => $serial, 'source_row' => $sourceRow];
            DB::table($seenTable)->insert($insert);
            $seenSerials = [];
        };
        $flushScanRanges = function () use (&$scanRangeBatch, &$duplicateRanges, &$batch, $record, $now) {
            if ($scanRangeBatch === []) return;
            $matchesByRange = $this->findScanRangeDuplicatesBatch($scanRangeBatch);
            foreach ($scanRangeBatch as $rangeCandidate) {
                $matches = $matchesByRange[$rangeCandidate['source_row']] ?? [];
                if ($matches !== []) foreach ($matches as $match) $duplicateRanges[] = $match;
                else $batch[] = $this->digiposRangeRow($record->id, $record->kind, $rangeCandidate, $now);
            }
            $scanRangeBatch = [];
        };
        Storage::disk('local')->delete($duplicateReportPath);
        try {
            foreach ($reader->rows($path, $headers) as $rowNumber => $values) {
                $serial = $this->mappedValue($values, $mapping['serial_column'] ?? null);
                if (in_array($serial, ['-', '—'], true)) $serial = '';
                $outletId = $this->mappedValue($values, $mapping['outlet_column']);
                if ($serial === '' && $outletId === '') continue;
                if ($outletId === '') throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: ID Outlet kosong."]);
                if (strlen($outletId) > 50 || ! preg_match('/^[A-Za-z0-9_-]+$/', $outletId)) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: format ID Outlet tidak valid. Simpan ID sebagai teks."]);
                }
                if ($serial === '') throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: SN kosong."]);
                if (! preg_match('/^\d{1,24}$/', $serial)) throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: SN harus berupa angka maksimal 24 digit."]);
                if ($record->kind === 'so-pv' && strlen($serial) !== 12) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: Upload SO PV harus berisi SN 12 digit."]);
                }
                if ($record->kind === 'so-sa' && ! preg_match('/^0\d{15}$/', $serial)) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: Upload SO SA harus berisi SN 16 digit yang diawali 0."]);
                }
                if (isset($seenSerials[$serial])) {
                    throw ValidationException::withMessages(['file' => "SN duplikat {$serial} ditemukan di baris {$seenSerials[$serial]} dan {$rowNumber}. Hapus salah satu baris lalu upload ulang."]);
                }
                $seenSerials[$serial] = $rowNumber;
                if (count($seenSerials) >= 500) $flushSeenSerials();
                $soldAtValue = $this->mappedValue($values, $mapping['date_column'] ?? null);
                if ($record->kind !== 'warehouse' && $soldAtValue === '') {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: tanggal wajib diisi dengan format dd-mmm-yyyy, contoh 12-oct-2026."]);
                }
                $soldAt = $soldAtValue !== ''
                    ? ($record->kind !== 'warehouse' ? ProductTrackingFile::scanDate($soldAtValue) : ProductTrackingFile::date($soldAtValue))
                    : null;
                if ($soldAtValue !== '' && ! $soldAt) {
                    $hint = $record->kind !== 'warehouse' ? 'Gunakan format dd-mmm-yyyy, contoh 12-oct-2026.' : 'Format tanggal tidak dikenali.';
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: {$hint}"]);
                }
                if ($record->kind !== 'warehouse' && $detectedScanDate === null && $soldAt !== null) {
                    $detectedScanDate = $soldAt;
                } elseif ($record->kind !== 'warehouse' && $soldAt !== null && $detectedScanDate !== $soldAt) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: tanggal berbeda dari baris sebelumnya. Satu file upload harus memakai satu tanggal yang sama."]);
                }
                $sf = mb_substr($this->mappedValue($values, $mapping['sf_column'] ?? null), 0, 150);
                $product = mb_substr($this->mappedValue($values, $mapping['product_column'] ?? null), 0, 200);
                $date = $soldAt ?? ($record->kind !== 'warehouse' ? $detectedScanDate : null);
                $key = implode("\x1f", [$date ?? '', $outletId, $sf, $product]);
                if ($range !== null && $key === $range['key'] && $this->incrementSerial($range['serial_end']) === $serial) {
                    $range['serial_end'] = $serial;
                    $range['serial_count']++;
                } else {
                    if ($range !== null) {
                        if ($record->kind === 'scan') {
                            $scanRangeBatch[] = $range;
                            if (count($scanRangeBatch) >= 50) $flushScanRanges();
                        } elseif (str_starts_with($record->kind, 'so-') && $this->hasExistingOutletRedeemRange($record->kind, $range)) {
                            throw ValidationException::withMessages(['file' => "Baris {$range['source_row']}: rentang SN ini sudah pernah diupload sebagai SO untuk outlet {$range['id_outlet']}. Upload dibatalkan agar jumlah redeem tidak terhitung ganda."]);
                        } else {
                            $batch[] = $this->digiposRangeRow($record->id, $record->kind, $range, $now);
                        }
                    }
                    $range = ['key' => $key, 'scan_date' => $record->kind !== 'warehouse' ? $date : null, 'serial_start' => $serial, 'serial_end' => $serial, 'serial_count' => 1, 'id_outlet' => $outletId, 'sf' => $sf, 'sold_at' => $date, 'product_name' => $product, 'source_row' => $rowNumber];
                }
                $count++;
                if (count($batch) >= 500) {
                    DB::table('product_tracking_digipos')->insert($batch);
                    $batch = [];
                }
            }
            $flushSeenSerials();
            if ($range !== null) {
                if ($record->kind === 'scan') {
                    $scanRangeBatch[] = $range;
                    $flushScanRanges();
                } elseif (str_starts_with($record->kind, 'so-') && $this->hasExistingOutletRedeemRange($record->kind, $range)) {
                    throw ValidationException::withMessages(['file' => "Baris {$range['source_row']}: rentang SN ini sudah pernah diupload sebagai SO untuk outlet {$range['id_outlet']}. Upload dibatalkan agar jumlah redeem tidak terhitung ganda."]);
                } else {
                    $batch[] = $this->digiposRangeRow($record->id, $record->kind, $range, $now);
                }
            }
            if ($duplicateRanges !== []) {
                $this->storeDuplicateScanReport($duplicateReportPath, $duplicateRanges);
                $first = $duplicateRanges[0];
                throw ValidationException::withMessages(['file' => count($duplicateRanges).' rentang SN duplikat ditemukan. Contoh: baris '.$first['source_row'].', SN '.$first['duplicate_start'].($first['duplicate_end'] !== $first['duplicate_start'] ? '–'.$first['duplicate_end'] : '').' sudah ada di '.$first['existing_filename'].'. Data tidak disimpan; unduh daftar SN duplikat di bawah.']);
            }
            if ($batch !== []) DB::table('product_tracking_digipos')->insert($batch);
            if ($count === 0) throw ValidationException::withMessages(['file' => 'File tidak berisi baris transaksi yang dapat diimpor.']);
            if ($record->kind === 'scan' && $detectedScanDate === null) {
                throw ValidationException::withMessages(['date_column' => 'Kolom tanggal pada file tidak berisi tanggal yang dapat dibaca.']);
            }
            DB::table('product_tracking_uploads')->where('id', $record->id)->update([
                'status' => 'complete', 'scan_date' => $detectedScanDate, 'row_count' => $count, 'storage_path' => null, 'updated_at' => $now,
            ]);
        } catch (\Throwable $exception) {
            DB::table('product_tracking_digipos')->where('upload_id', $record->id)->delete();
            try { DB::statement("DROP TEMPORARY TABLE IF EXISTS `{$seenTable}`"); } catch (\Throwable) {}
            throw $exception;
        }
        DB::statement("DROP TEMPORARY TABLE IF EXISTS `{$seenTable}`");
        Storage::disk('local')->delete($record->storage_path);
        return redirect()->route('surya.product-tracking.imports', ['kind' => $record->kind])->with('success', number_format($count).' baris Digipos berhasil diimpor.');
    }

    public function report(Request $request)
    {
        $filters = $request->validate([
            'date_from' => 'nullable|date|before_or_equal:today', 'date_to' => 'nullable|date|before_or_equal:today',
            'tap' => 'nullable|string|max:100', 'sf' => 'nullable|string|max:150', 'per_page' => 'nullable|in:25,50,100',
        ]);
        $from = $filters['date_from'] ?? now('Asia/Jakarta')->startOfMonth()->toDateString();
        $to = $filters['date_to'] ?? now('Asia/Jakarta')->toDateString();
        if ($from > $to) throw ValidationException::withMessages(['date_to' => 'Tanggal akhir harus sama atau sesudah tanggal awal.']);
        $warehouseId = DB::table('product_tracking_uploads')
            ->whereRaw("CONVERT(kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'warehouse' COLLATE utf8mb4_unicode_ci")
            ->whereRaw("CONVERT(status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
            ->orderByDesc('id')->value('id');
        $sales = $this->scopedSales()
            ->whereBetween('sale.sale_date', [$from, $to])
            ->when(! empty($filters['tap']), fn ($query) => $this->applyTextEquals($query, 'outlet.tap', $filters['tap']))
            ->when(! empty($filters['sf']), fn ($query) => $query->where('sale.sf', $filters['sf']))
            ->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'sale.sf')
            ->select('sale.*', 'outlet.tap', 'sales_force.namasf')
            ->selectSub($this->matchedDigiposQuery('warehouse', $warehouseId), 'warehouse_matches')
            ->selectSub($this->matchedDigiposQuery('warehouse', $warehouseId, true), 'warehouse_outlet_matches')
            ->selectSub($this->matchedDigiposQuery('scan', null, false, $from, $to), 'scan_matches')
            ->selectSub($this->matchedDigiposQuery('scan', null, true, $from, $to), 'scan_outlet_matches')
            ->orderByDesc('sale.sale_date')->orderByDesc('sale.id')
            ->paginate((int) ($filters['per_page'] ?? 25))->appends($request->query());
        $taps = DB::table('appsdumais')->when($this->suryaTapScopes(), fn ($query, $scopes) => $this->applyTapScopeFilter($query, 'tap', $scopes))->distinct()->orderBy('tap')->pluck('tap');
        $outletScope = DB::table('appsdumais')->select('id_outlet');
        if ($scopes = $this->suryaTapScopes()) $this->applyTapScopeFilter($outletScope, 'tap', $scopes);
        $sfs = DB::table('product_tracking_sales as product_sales')
            ->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'product_sales.sf')
            ->whereIn('product_sales.id_outlet', $outletScope)->select('product_sales.sf', 'sales_force.namasf')->distinct()->orderBy('sales_force.namasf')->get();
        return view('surya.product-tracking.report', compact('sales', 'filters', 'from', 'to', 'taps', 'sfs', 'warehouseId'));
    }

    public function tracking(Request $request)
    {
        $queryValue = trim((string) $request->query('q', ''));
        $sales = collect(); $scans = collect(); $mobile = collect();
        if ($queryValue !== '') {
            abort_if(strlen($queryValue) > 24 || ! preg_match('/^\d{9,16}$/', $queryValue), 422, 'Masukkan SN atau nomor 9–16 digit.');
            $sales = $this->scopedSales()->leftJoin('denom as tracking_denom', 'tracking_denom.iddenom', '=', 'sale.iddenom')->where(function ($query) use ($queryValue) {
                $query->where(function ($sa) use ($queryValue) {
                    $sa->whereRaw("CONVERT(sale.identifier_type USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'sn' COLLATE utf8mb4_unicode_ci")
                        ->whereRaw("CONVERT(UPPER(COALESCE(tracking_denom.product_group, '')) USING utf8mb4) COLLATE utf8mb4_unicode_ci IN (_utf8mb4'SA' COLLATE utf8mb4_unicode_ci, _utf8mb4'SP' COLLATE utf8mb4_unicode_ci)")
                        ->whereRaw('CAST(sale.serial_start AS DECIMAL(24,0)) <= CAST(? AS DECIMAL(24,0)) AND CAST(sale.serial_end AS DECIMAL(24,0)) >= CAST(? AS DECIMAL(24,0))', [$queryValue, $queryValue]);
                })->orWhere(function ($pv) use ($queryValue) {
                    $pv->whereRaw("CONVERT(sale.identifier_type USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'sn' COLLATE utf8mb4_unicode_ci")
                        ->whereRaw("CONVERT(UPPER(COALESCE(tracking_denom.product_group, 'PV')) USING utf8mb4) COLLATE utf8mb4_unicode_ci NOT IN (_utf8mb4'SA' COLLATE utf8mb4_unicode_ci, _utf8mb4'SP' COLLATE utf8mb4_unicode_ci)")
                        ->where('sale.serial_start', '<=', $queryValue)->where('sale.serial_end', '>=', $queryValue);
                })->orWhere(function ($sub) use ($queryValue) {
                    $sub->where('sale.identifier_type', 'number')->where('sale.serial_start', $queryValue);
                });
            })->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'sale.sf')
                ->leftJoin('appsdumais as sales_outlet', 'sales_outlet.id_outlet', '=', 'sale.id_outlet')
                ->leftJoin('denom as sales_denom', 'sales_denom.iddenom', '=', 'sale.iddenom')
                ->orderByDesc('sale.sale_date')->get(['sale.*', 'sales_force.namasf', 'sales_outlet.nama_outlet', 'sales_outlet.kecamatan', 'sales_denom.denom as denom_name']);
            $scansQuery = DB::table('product_tracking_digipos as d')->join('product_tracking_uploads as u', 'u.id', '=', 'd.upload_id')
                ->whereRaw("CONVERT(u.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'scan' COLLATE utf8mb4_unicode_ci")
                ->whereRaw("CONVERT(u.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")->where(function ($match) use ($queryValue) {
                    $match->where(function ($sa) use ($queryValue) {
                        $sa->whereRaw("LEFT(LOWER(u.filename), 3) = _utf8mb4'sa_' COLLATE utf8mb4_unicode_ci")
                            ->whereRaw('CAST(d.serial_start AS DECIMAL(24,0)) <= CAST(? AS DECIMAL(24,0)) AND CAST(d.serial_end AS DECIMAL(24,0)) >= CAST(? AS DECIMAL(24,0))', [$queryValue, $queryValue]);
                    })->orWhere(function ($pv) use ($queryValue) {
                        $pv->whereRaw("LEFT(LOWER(u.filename), 3) <> _utf8mb4'sa_' COLLATE utf8mb4_unicode_ci")
                            ->where('d.serial_start', '<=', $queryValue)->where('d.serial_end', '>=', $queryValue);
                    });
                })
                ->orderByDesc('d.scan_date')->orderByDesc('d.id')->limit(100);
            if ($scopes = $this->suryaTapScopes()) {
                $outletScope = DB::table('appsdumais')->select('id_outlet');
                $this->applyTapScopeFilter($outletScope, 'tap', $scopes);
                $scansQuery->whereIn('d.id_outlet', $outletScope);
            }
            $scans = $scansQuery->leftJoin('idsf as digipos_sales_force', 'digipos_sales_force.digipos_id', '=', 'd.sf')
                ->leftJoin('appsdumais as digipos_outlet', 'digipos_outlet.id_outlet', '=', 'd.id_outlet')
                ->get(['d.*', 'u.filename', 'digipos_sales_force.namasf', 'digipos_outlet.nama_outlet', 'digipos_outlet.kecamatan']);
            if (Schema::hasTable('mobile_sales_serials')) {
                $mobileQuery = DB::table('mobile_sales_serials as serial')->join('mobile_penjualan as sale', 'sale.id', '=', 'serial.mobile_penjualan_id')
                    ->leftJoin('denom as mobile_tracking_denom', 'mobile_tracking_denom.iddenom', '=', 'serial.iddenom')
                    ->where(function ($query) use ($queryValue) {
                        $query->where(function ($sa) use ($queryValue) { $sa->whereRaw("CONVERT(serial.identifier_type USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'sn' COLLATE utf8mb4_unicode_ci")->whereRaw("CONVERT(UPPER(COALESCE(mobile_tracking_denom.product_group, '')) USING utf8mb4) COLLATE utf8mb4_unicode_ci IN (_utf8mb4'SA' COLLATE utf8mb4_unicode_ci, _utf8mb4'SP' COLLATE utf8mb4_unicode_ci)")->whereRaw('CAST(serial.serial_start AS DECIMAL(24,0)) <= CAST(? AS DECIMAL(24,0)) AND CAST(serial.serial_end AS DECIMAL(24,0)) >= CAST(? AS DECIMAL(24,0))', [$queryValue, $queryValue]); })
                            ->orWhere(function ($pv) use ($queryValue) { $pv->whereRaw("CONVERT(serial.identifier_type USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'sn' COLLATE utf8mb4_unicode_ci")->whereRaw("CONVERT(UPPER(COALESCE(mobile_tracking_denom.product_group, 'PV')) USING utf8mb4) COLLATE utf8mb4_unicode_ci NOT IN (_utf8mb4'SA' COLLATE utf8mb4_unicode_ci, _utf8mb4'SP' COLLATE utf8mb4_unicode_ci)")->where('serial.serial_start', '<=', $queryValue)->where('serial.serial_end', '>=', $queryValue); })
                            ->orWhere(function ($sub) use ($queryValue) { $sub->where('serial.identifier_type', 'number')->where('serial.serial_start', $queryValue); });
                    })->whereRaw("CONVERT(sale.status USING utf8mb4) COLLATE utf8mb4_unicode_ci NOT IN (_utf8mb4'draft_wa' COLLATE utf8mb4_unicode_ci, _utf8mb4'rejected' COLLATE utf8mb4_unicode_ci)")->orderByDesc('sale.tgl')->limit(50);
                if ($scopes = $this->suryaTapScopes()) {
                    $outletScope = DB::table('appsdumais')->select('id_outlet');
                    $this->applyTapScopeFilter($outletScope, 'tap', $scopes);
                    $mobileQuery->whereIn('sale.id_outlet', $outletScope);
                }
                $mobile = $mobileQuery->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'sale.idsf')
                    ->leftJoin('appsdumais as mobile_outlet', 'mobile_outlet.id_outlet', '=', 'sale.id_outlet')
                    ->leftJoin('denom as mobile_denom', 'mobile_denom.iddenom', '=', 'serial.iddenom')
                    ->get(['serial.*', 'sale.tgl', 'sale.id_outlet', 'sale.idsf', 'sale.status', 'sale.qty', 'sales_force.namasf', 'mobile_outlet.nama_outlet', 'mobile_outlet.kecamatan', 'mobile_denom.denom as denom_name']);
            }

            $notaRows = $sales->concat($mobile);
            foreach ($scans as $scan) {
                if (! in_array(trim((string) $scan->product_name), ['', '-', '—'], true)) continue;
                $matchedNota = $notaRows->first(fn ($row) => (string) $row->id_outlet === (string) $scan->id_outlet
                    && trim((string) ($row->denom_name ?? $row->iddenom ?? '')) !== '');
                if ($matchedNota) $scan->product_name = $matchedNota->denom_name ?: $matchedNota->iddenom;
            }
        }
        return view('surya.product-tracking.tracking', compact('queryValue', 'sales', 'scans', 'mobile'));
    }

    public function sfStockLookup(Request $request)
    {
        $this->requireEditor();
        $tab = $request->query('tab') === 'add' ? 'add' : 'tracking';
        if ($tab === 'add') $this->requireEditor();
        $raw = trim((string) $request->query('q', ''));
        $serial = preg_replace('/\\D+/', '', $raw);
        $matches = collect();
        $error = null;

        if ($raw !== '') {
            $length = strlen($serial);
            $isPv = $length === 12;
            $isSa = $length === 15 || ($length === 16 && str_starts_with($serial, '0'));
            if (! $isPv && ! $isSa) {
                $error = 'Masukkan SN PV 12 digit atau SN SA 15 digit (boleh 16 digit dengan 0 di depan).';
            } else {
                $canonical = $isSa && $length === 16 ? substr($serial, 1) : $serial;
                $scopes = $this->suryaTapScopes();

                if (Schema::hasTable('product_tracking_sales_stock_ranges')) {
                    $uploaded = DB::table('product_tracking_sales_stock_ranges as stock')
                        ->join('product_tracking_sales_stock_uploads as stock_upload', 'stock_upload.id', '=', 'stock.upload_id')
                        ->leftJoin('idsf as owner', 'owner.idsf', '=', 'stock.sfcode')
                        ->leftJoin('denom as d', 'd.iddenom', '=', 'stock.iddenom')
                        ->where(function ($query) use ($isPv, $canonical) {
                            if ($isPv) {
                                $query->where(function ($query) use ($canonical) {
                                    $query->whereRaw('CHAR_LENGTH(stock.serial_start) = 12')->where('stock.serial_start', '<=', $canonical)->where('stock.serial_end', '>=', $canonical);
                                });
                            } else {
                                $query->where(function ($query) use ($canonical) {
                                    $query->whereRaw('CHAR_LENGTH(stock.serial_start) = 15')->where('stock.serial_start', '<=', $canonical)->where('stock.serial_end', '>=', $canonical);
                                })->orWhere(function ($query) use ($canonical) {
                                    $query->whereRaw('CHAR_LENGTH(stock.serial_start) = 16')->where('stock.serial_start', '<=', '0'.$canonical)->where('stock.serial_end', '>=', '0'.$canonical);
                                });
                            }
                        });
                    if ($scopes) $this->applyTapScopeFilter($uploaded, 'owner.idtap', $scopes);
                    foreach ($uploaded->get(['stock.sfcode as sfcode', 'owner.namasf', 'owner.idtap', 'stock.tap', 'stock.cluster', 'stock.iddenom', 'd.denom', 'stock.serial_start', 'stock.serial_end', 'stock.qty', 'stock_upload.filename', 'stock_upload.username', 'stock_upload.created_at']) as $row) {
                        $matches->push((object) [
                            'sfcode' => $row->sfcode,
                            'namasf' => $row->namasf,
                            'tap' => $row->tap ?: $row->idtap,
                            'cluster' => $row->cluster ?: $this->trackingClusterForTap((string) ($row->tap ?: $row->idtap)),
                            'iddenom' => $row->iddenom,
                            'denom' => $row->denom,
                            'source' => str_starts_with(strtoupper((string) $row->filename), 'MANUAL:') ? 'Input manual' : 'Upload Stok SN Sales',
                            'serial_range' => $row->serial_start === $row->serial_end ? $row->serial_start : $row->serial_start.' – '.$row->serial_end,
                            'date' => $row->created_at,
                        ]);
                    }
                }

                $normalizedSn = "REPLACE(REPLACE(TRIM(m.sn), CHAR(9), '-'), ' ', '')";
                $rangeStart = "SUBSTRING_INDEX({$normalizedSn}, '-', 1)";
                $rangeEnd = "SUBSTRING_INDEX({$normalizedSn}, '-', -1)";
                $admin = DB::table('masuksf as m')
                    ->join('idsf as owner', 'owner.idsf', '=', 'm.idsf')
                    ->leftJoin('denom as d', 'd.iddenom', '=', 'm.iddenom')
                    ->whereRaw("{$normalizedSn} REGEXP '^[0-9]+(-[0-9]+)?$'")
                    ->whereRaw("CHAR_LENGTH({$rangeStart}) = CHAR_LENGTH({$rangeEnd})")
                    ->whereRaw("({$rangeStart} = {$rangeEnd} OR (m.qty BETWEEN 1 AND 2000000 AND CAST({$rangeEnd} AS DECIMAL(24,0)) >= CAST({$rangeStart} AS DECIMAL(24,0)) AND CAST({$rangeEnd} AS DECIMAL(24,0)) - CAST({$rangeStart} AS DECIMAL(24,0)) + 1 = m.qty))")
                    ->whereRaw('CAST('.$rangeStart.' AS DECIMAL(24,0)) <= CAST(? AS DECIMAL(24,0)) AND CAST('.$rangeEnd.' AS DECIMAL(24,0)) >= CAST(? AS DECIMAL(24,0))', [$canonical, $canonical])
                    ->where(function ($query) use ($isPv, $rangeStart) {
                        if ($isPv) $query->whereRaw("CHAR_LENGTH({$rangeStart}) = 12");
                        else $query->whereRaw("CHAR_LENGTH({$rangeStart}) IN (15, 16)");
                    });
                if ($scopes) $this->applyTapScopeFilter($admin, 'owner.idtap', $scopes);
                foreach ($admin->get(['m.idsf as sfcode', 'owner.namasf', 'owner.idtap', 'm.iddenom', 'd.denom', 'm.sn', 'm.qty', 'm.tgl']) as $row) {
                    $parts = preg_split('/(?:\\s*[-–—]\\s*|\\s+)/u', trim((string) $row->sn), 2, PREG_SPLIT_NO_EMPTY) ?: [];
                    $storedRange = count($parts) > 1 ? $parts[0].' – '.$parts[1] : ($parts[0] ?? (string) $row->sn);
                    $matches->push((object) [
                        'sfcode' => $row->sfcode,
                        'namasf' => $row->namasf,
                        'tap' => $row->idtap,
                        'cluster' => $this->trackingClusterForTap((string) $row->idtap),
                        'iddenom' => $row->iddenom,
                        'denom' => $row->denom,
                        'source' => 'SF Masuk admin',
                        'serial_range' => $storedRange,
                        'date' => $row->tgl,
                    ]);
                }
            }
        }

        $salesForces = collect();
        $eligibleDenoms = collect();
        $recentManual = null;
        $editingManual = null;
        if ($tab === 'add') {
            $sfQuery = DB::table('idsf')->whereRaw("UPPER(idsf) LIKE 'SF%' ");
            if ($scopes = $this->suryaTapScopes()) $this->applyTapScopeFilter($sfQuery, 'idtap', $scopes);
            $salesForces = $sfQuery->orderBy('namasf')->get(['idsf', 'namasf', 'idtap']);

            $eligibleDenoms = DB::table('denom')->orderBy('denom')
                ->get(['iddenom', 'denom', 'product_group', 'kategori_inject', 'group_name'])
                ->filter(fn ($denom) => in_array(DenomGrouping::infer($denom)['product'], ['PV', 'SA'], true))
                ->values();

            if (Schema::hasTable('product_tracking_sales_stock_ranges')) {
                $manualQuery = DB::table('product_tracking_sales_stock_ranges as stock')
                    ->join('product_tracking_sales_stock_uploads as source', 'source.id', '=', 'stock.upload_id')
                    ->leftJoin('idsf as owner', 'owner.idsf', '=', 'stock.sfcode')
                    ->leftJoin('denom as d', 'd.iddenom', '=', 'stock.iddenom')
                    ->where('source.filename', 'like', 'MANUAL:%');
                if ($scopes = $this->suryaTapScopes()) $this->applyTapScopeFilter($manualQuery, 'owner.idtap', $scopes);
                $recentManual = $manualQuery->orderByDesc('source.updated_at')->orderByDesc('stock.id')->paginate(20, [
                    'stock.id', 'stock.sfcode', 'owner.namasf', 'owner.idtap', 'stock.iddenom', 'd.denom',
                    'stock.serial_start', 'stock.serial_end', 'stock.qty', 'source.username', 'source.updated_at as created_at',
                ], 'manual_page')->withQueryString();
                if ($request->filled('edit')) {
                    $editingManual = $this->findManualSfStockRange((int) $request->query('edit'));
                }
            }
        }

        return view('surya.product-tracking.sf-stock-lookup', [
            'queryValue' => $serial,
            'matches' => $matches,
            'error' => $error,
            'tab' => $tab,
            'salesForces' => $salesForces,
            'eligibleDenoms' => $eligibleDenoms,
            'recentManual' => $recentManual,
            'editingManual' => $editingManual,
        ]);
    }

    public function storeSfStockRange(Request $request)
    {
        $this->requireEditor();
        abort_unless(Schema::hasTable('product_tracking_sales_stock_uploads') && Schema::hasTable('product_tracking_sales_stock_ranges'), 503, 'Tabel stok SN Sales belum tersedia.');

        $data = $request->validate([
            'sfcode' => 'required|string|max:100',
            'iddenom' => 'required|string|max:100|exists:denom,iddenom',
            'serial_start' => 'required|string|regex:/^\\d{12,16}$/',
            'serial_end' => 'required|string|regex:/^\\d{12,16}$/',
        ]);
        $sfQuery = DB::table('idsf')->where('idsf', $data['sfcode'])->whereRaw("UPPER(idsf) LIKE 'SF%' ");
        if ($scopes = $this->suryaTapScopes()) $this->applyTapScopeFilter($sfQuery, 'idtap', $scopes);
        $sf = $sfQuery->first(['idsf', 'namasf', 'idtap']);
        if (! $sf) throw ValidationException::withMessages(['sfcode' => 'Pilih Sales Force terdaftar dalam cakupan akun ini.']);

        $denom = DB::table('denom')->where('iddenom', $data['iddenom'])
            ->first(['iddenom', 'denom', 'product_group', 'kategori_inject', 'group_name']);
        $product = DenomGrouping::infer($denom)['product'];
        $start = trim($data['serial_start']);
        $end = trim($data['serial_end']);
        $isSa = $product === 'SA';
        $validSerial = function (string $serial) use ($isSa): bool {
            if (! ctype_digit($serial)) return false;
            if (! $isSa) return strlen($serial) === 12;
            return (strlen($serial) === 15 && ! str_starts_with($serial, '0'))
                || (strlen($serial) === 16 && str_starts_with($serial, '0'));
        };
        if (! $validSerial($start) || ! $validSerial($end) || strlen($start) !== strlen($end)) {
            throw ValidationException::withMessages(['serial_start' => $isSa ? 'SN SA harus 15 digit, atau 16 digit dengan 0 di depan; panjang awal dan akhir harus sama.' : 'SN PV harus tepat 12 digit; panjang awal dan akhir harus sama.']);
        }
        if (strcmp($start, $end) > 0) throw ValidationException::withMessages(['serial_end' => 'SN akhir harus sama dengan atau lebih besar dari SN awal.']);
        $rangeQty = (int) $end - (int) $start + 1;
        if ($rangeQty < 1 || $rangeQty > 2_000_000) throw ValidationException::withMessages(['serial_end' => 'Rentang harus berisi maksimal 2.000.000 SN.']);

        $startNumber = (int) $start;
        $endNumber = (int) $end;
        $sourceConflict = DB::table('product_tracking_sales_stock_ranges as stock')
            ->join('idsf as owner', 'owner.idsf', '=', 'stock.sfcode')
            ->whereRaw('CHAR_LENGTH(stock.serial_start) IN ('.($isSa ? '15, 16' : '12').')')
            ->whereRaw('CAST(stock.serial_start AS DECIMAL(24,0)) <= ? AND CAST(stock.serial_end AS DECIMAL(24,0)) >= ?', [$endNumber, $startNumber])
            ->first(['stock.sfcode', 'owner.namasf']);

        $normalizedSn = "REPLACE(REPLACE(TRIM(m.sn), CHAR(9), '-'), ' ', '')";
        $rangeStart = "SUBSTRING_INDEX({$normalizedSn}, '-', 1)";
        $rangeEnd = "SUBSTRING_INDEX({$normalizedSn}, '-', -1)";
        $adminConflict = DB::table('masuksf as m')
            ->join('idsf as owner', 'owner.idsf', '=', 'm.idsf')
            ->whereRaw("{$normalizedSn} REGEXP '^[0-9]+(-[0-9]+)?$'")
            ->whereRaw("CHAR_LENGTH({$rangeStart}) = CHAR_LENGTH({$rangeEnd})")
            ->whereRaw("({$rangeStart} = {$rangeEnd} OR (m.qty BETWEEN 1 AND 2000000 AND CAST({$rangeEnd} AS DECIMAL(24,0)) >= CAST({$rangeStart} AS DECIMAL(24,0)) AND CAST({$rangeEnd} AS DECIMAL(24,0)) - CAST({$rangeStart} AS DECIMAL(24,0)) + 1 = m.qty))")
            ->whereRaw("CHAR_LENGTH({$rangeStart}) IN (".($isSa ? '15, 16' : '12').")")
            ->whereRaw('CAST('.$rangeStart.' AS DECIMAL(24,0)) <= ? AND CAST('.$rangeEnd.' AS DECIMAL(24,0)) >= ?', [$endNumber, $startNumber])
            ->first(['m.idsf as sfcode', 'owner.namasf']);

        $conflict = $sourceConflict ?: $adminConflict;
        if ($conflict) {
            throw ValidationException::withMessages(['serial_start' => "Rentang bertumpuk dengan SN yang sudah tercatat untuk {$conflict->sfcode} — ".($conflict->namasf ?: 'nama SF tidak tersedia').'. Periksa dulu sebelum menambahkan.']);
        }

        DB::transaction(function () use ($data, $sf, $start, $end, $rangeQty) {
            $now = now();
            $uploadId = DB::table('product_tracking_sales_stock_uploads')->insertGetId([
                'filename' => 'MANUAL: Cek SN SF',
                'row_count' => 1,
                'username' => auth()->user()->username,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('product_tracking_sales_stock_ranges')->insert([
                'upload_id' => $uploadId,
                'sfcode' => $sf->idsf,
                'tap' => $sf->idtap,
                'cluster' => $this->trackingClusterForTap((string) $sf->idtap),
                'iddenom' => $data['iddenom'],
                'serial_start' => $start,
                'serial_end' => $end,
                'qty' => $rangeQty,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            // This is an SN-to-owner mapping only; it deliberately does not change masuksf or stockawalsf.
        });

        return redirect()->route('surya.product-tracking.sf-stock-lookup', ['tab' => 'add'])
            ->with('success', number_format($rangeQty).' SN berhasil dicatat untuk '.$sf->idsf.' · '.$data['iddenom'].'.');
    }

    public function updateSfStockRange(Request $request, int $range)
    {
        $this->requireEditor();
        $record = $this->findManualSfStockRange($range);
        $data = $request->validate([
            'sfcode' => 'required|string|max:100',
            'iddenom' => 'required|string|max:100|exists:denom,iddenom',
            'serial_start' => 'required|string|regex:/^\\d{12,16}$/',
            'serial_end' => 'required|string|regex:/^\\d{12,16}$/',
        ]);
        $sfQuery = DB::table('idsf')->where('idsf', $data['sfcode'])->whereRaw("UPPER(idsf) LIKE 'SF%' ");
        if ($scopes = $this->suryaTapScopes()) $this->applyTapScopeFilter($sfQuery, 'idtap', $scopes);
        $sf = $sfQuery->first(['idsf', 'idtap']);
        if (! $sf) throw ValidationException::withMessages(['sfcode' => 'Pilih Sales Force terdaftar dalam cakupan akun ini.']);

        $denom = DB::table('denom')->where('iddenom', $data['iddenom'])
            ->first(['iddenom', 'product_group', 'kategori_inject', 'group_name']);
        $product = DenomGrouping::infer($denom)['product'];
        $start = trim($data['serial_start']);
        $end = trim($data['serial_end']);
        $isSa = $product === 'SA';
        $validSerial = fn (string $serial): bool => ctype_digit($serial)
            && ($isSa ? ((strlen($serial) === 15 && ! str_starts_with($serial, '0')) || (strlen($serial) === 16 && str_starts_with($serial, '0')))
                : strlen($serial) === 12);
        if (! $validSerial($start) || ! $validSerial($end) || strlen($start) !== strlen($end)) {
            throw ValidationException::withMessages(['serial_start' => $isSa ? 'SN SA harus 15 digit, atau 16 digit dengan 0 di depan; panjang awal dan akhir harus sama.' : 'SN PV harus tepat 12 digit; panjang awal dan akhir harus sama.']);
        }
        if (strcmp($start, $end) > 0) throw ValidationException::withMessages(['serial_end' => 'SN akhir harus sama dengan atau lebih besar dari SN awal.']);
        $rangeQty = (int) $end - (int) $start + 1;
        if ($rangeQty < 1 || $rangeQty > 2_000_000) throw ValidationException::withMessages(['serial_end' => 'Rentang harus berisi maksimal 2.000.000 SN.']);

        $lengths = $isSa ? '15, 16' : '12';
        $conflict = DB::table('product_tracking_sales_stock_ranges as stock')
            ->join('idsf as owner', 'owner.idsf', '=', 'stock.sfcode')
            ->where('stock.id', '!=', $range)
            ->whereRaw("CHAR_LENGTH(stock.serial_start) IN ({$lengths})")
            ->whereRaw('CAST(stock.serial_start AS DECIMAL(24,0)) <= ? AND CAST(stock.serial_end AS DECIMAL(24,0)) >= ?', [(int) $end, (int) $start])
            ->first(['stock.sfcode', 'owner.namasf']);
        if ($conflict) {
            throw ValidationException::withMessages(['serial_start' => "Rentang bertumpuk dengan SN yang sudah tercatat untuk {$conflict->sfcode} — ".($conflict->namasf ?: 'nama SF tidak tersedia').'.']);
        }

        $normalizedSn = "REPLACE(REPLACE(TRIM(m.sn), CHAR(9), '-'), ' ', '')";
        $rangeStart = "SUBSTRING_INDEX({$normalizedSn}, '-', 1)";
        $rangeEnd = "SUBSTRING_INDEX({$normalizedSn}, '-', -1)";
        $adminConflict = DB::table('masuksf as m')
            ->whereRaw("{$normalizedSn} REGEXP '^[0-9]+(-[0-9]+)?$'")
            ->whereRaw("CHAR_LENGTH({$rangeStart}) = CHAR_LENGTH({$rangeEnd}) AND CHAR_LENGTH({$rangeStart}) IN ({$lengths})")
            ->whereRaw("({$rangeStart} = {$rangeEnd} OR (m.qty BETWEEN 1 AND 2000000 AND CAST({$rangeEnd} AS DECIMAL(24,0)) - CAST({$rangeStart} AS DECIMAL(24,0)) + 1 = m.qty))")
            ->whereRaw('CAST('.$rangeStart.' AS DECIMAL(24,0)) <= ? AND CAST('.$rangeEnd.' AS DECIMAL(24,0)) >= ?', [(int) $end, (int) $start])
            ->exists();
        if ($adminConflict) throw ValidationException::withMessages(['serial_start' => 'Rentang bertumpuk dengan SN yang sudah tercatat di SF Masuk admin.']);

        DB::transaction(function () use ($record, $sf, $data, $start, $end, $rangeQty) {
            DB::table('product_tracking_sales_stock_ranges')->where('id', $record->id)->update([
                'sfcode' => $sf->idsf, 'tap' => $sf->idtap,
                'cluster' => $this->trackingClusterForTap((string) $sf->idtap),
                'iddenom' => $data['iddenom'], 'serial_start' => $start, 'serial_end' => $end,
                'qty' => $rangeQty, 'updated_at' => now(),
            ]);
            DB::table('product_tracking_sales_stock_uploads')->where('id', $record->upload_id)->update([
                'username' => auth()->user()->username, 'updated_at' => now(),
            ]);
        });

        return redirect()->route('surya.product-tracking.sf-stock-lookup', ['tab' => 'add'])
            ->with('success', 'Rentang SN berhasil diperbarui.');
    }

    public function deleteSfStockRange(int $range)
    {
        $this->requireEditor();
        $record = $this->findManualSfStockRange($range);
        DB::transaction(function () use ($record) {
            DB::table('product_tracking_sales_stock_ranges')->where('id', $record->id)->delete();
            if (! DB::table('product_tracking_sales_stock_ranges')->where('upload_id', $record->upload_id)->exists()) {
                DB::table('product_tracking_sales_stock_uploads')->where('id', $record->upload_id)->delete();
            }
        });

        return redirect()->route('surya.product-tracking.sf-stock-lookup', ['tab' => 'add'])
            ->with('success', 'Rentang SN berhasil dihapus.');
    }

    private function findManualSfStockRange(int $range): object
    {
        $query = DB::table('product_tracking_sales_stock_ranges as stock')
            ->join('product_tracking_sales_stock_uploads as source', 'source.id', '=', 'stock.upload_id')
            ->leftJoin('idsf as owner', 'owner.idsf', '=', 'stock.sfcode')
            ->where('stock.id', $range)->where('source.filename', 'like', 'MANUAL:%');
        if ($scopes = $this->suryaTapScopes()) $this->applyTapScopeFilter($query, 'owner.idtap', $scopes);
        $record = $query->first(['stock.*', 'source.id as source_id', 'owner.idtap']);
        abort_unless($record, 404);
        return $record;
    }

    private function trackingClusterForTap(string $tap): string
    {
        $tap = strtoupper(trim($tap));
        if (in_array($tap, ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING'], true)) return 'DUMAI BENGKALIS';
        if (in_array($tap, ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'], true)) return 'ROKAN HILIR';
        return '—';
    }

    private function matchedDigiposQuery(string $kind, $warehouseId = null, bool $sameOutlet = false, ?string $from = null, ?string $to = null)
    {
        $query = DB::table('product_tracking_digipos as digipos')->selectRaw('COUNT(*)')
            ->whereRaw("(sale.identifier_type = 'sn' AND digipos.serial_start <= sale.serial_end AND digipos.serial_end >= sale.serial_start)");
        if ($scopes = $this->suryaTapScopes()) {
            $query->join('appsdumais as digipos_outlet', 'digipos_outlet.id_outlet', '=', 'digipos.id_outlet');
            $this->applyTapScopeFilter($query, 'digipos_outlet.tap', $scopes);
        }
        if ($sameOutlet) $query->whereColumn('digipos.id_outlet', 'sale.id_outlet');
        if ($kind === 'warehouse') $query->where('digipos.upload_id', $warehouseId ?? 0);
        else {
            $query->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
                ->whereRaw("CONVERT(upload.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'scan' COLLATE utf8mb4_unicode_ci")
                ->whereRaw("CONVERT(upload.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
                ->whereBetween('upload.scan_date', [$from, $to]);
        }
        return $query;
    }

    private function scopedSales()
    {
        $query = DB::table('product_tracking_sales as sale')->leftJoin('appsdumais as outlet', 'outlet.id_outlet', '=', 'sale.id_outlet');
        if ($scopes = $this->suryaTapScopes()) $this->applyTapScopeFilter($query, 'outlet.tap', $scopes);
        return $query;
    }

    private function requireEditor(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Fitur ini hanya dapat digunakan admin.');
    }

    private function mappedValue(array $row, ?string $column): string
    {
        return $column !== null && $column !== '' ? trim((string) ($row[$column] ?? '')) : '';
    }

    private function digiposRangeRow(int $uploadId, string $kind, array $range, $now): array
    {
        return [
            'upload_id' => $uploadId, 'kind' => $kind, 'scan_date' => $range['scan_date'],
            'serial_start' => $range['serial_start'], 'serial_end' => $range['serial_end'], 'serial_count' => $range['serial_count'],
            'id_outlet' => $range['id_outlet'],
            'sf' => $range['sf'] !== '' ? $range['sf'] : null, 'sold_at' => $range['sold_at'],
            'product_name' => $range['product_name'] !== '' ? $range['product_name'] : null,
            'created_at' => $now, 'updated_at' => $now,
        ];
    }

    private function findScanRangeDuplicates(array $range): array
    {
        return $this->findScanRangeDuplicatesBatch([$range])[$range['source_row']] ?? [];
    }

    private function findScanRangeDuplicatesBatch(array $ranges): array
    {
        $matchesByRow = [];
        foreach (collect($ranges)->groupBy(fn ($range) => strlen($range['serial_start'])) as $length => $lengthRanges) {
            $length = (int) $length;
            if (! array_key_exists($length, $this->scanRangesByLength)) {
                $this->completeScanUploadIds ??= DB::table('product_tracking_uploads')
                    ->where('kind', 'scan')->where('status', 'complete')->pluck('id')->map(fn ($id) => (int) $id)->all();
                $rangesForLength = $this->completeScanUploadIds === [] ? collect() : DB::table('product_tracking_digipos')
                    ->where('kind', 'scan')
                    ->whereBetween('serial_start', [str_repeat('0', $length), str_repeat('9', $length)])
                    ->whereRaw('CHAR_LENGTH(serial_start) = ?', [$length])
                    ->orderBy('serial_start')
                    ->get(['upload_id', 'serial_start', 'serial_end', 'serial_count', 'scan_date', 'id_outlet', 'sf', 'product_name'])
                    ->filter(fn ($row) => in_array((int) $row->upload_id, $this->completeScanUploadIds, true))
                    ->map(fn ($row) => (array) $row)->values();
                $this->scanRangesByLength[$length] = [
                    'max_count' => (int) ($rangesForLength->max('serial_count') ?? 0),
                    'ranges' => $rangesForLength->all(),
                ];
            }
            $existing = $this->scanRangesByLength[$length]['ranges'];
            $maxCount = $this->scanRangesByLength[$length]['max_count'];
            if ($maxCount === 0 || $existing === []) continue;

            $candidateRanges = $lengthRanges->sortBy('serial_start', SORT_STRING)->values();
            foreach ($candidateRanges as $range) {
                $minimumStart = $this->subtractSerialCount($range['serial_start'], $maxCount);
                $low = 0;
                $high = count($existing);
                while ($low < $high) {
                    $middle = intdiv($low + $high, 2);
                    if (strcmp($existing[$middle]['serial_start'], $minimumStart) < 0) $low = $middle + 1;
                    else $high = $middle;
                }
                for ($index = $low, $total = count($existing); $index < $total && strcmp($existing[$index]['serial_start'], $range['serial_end']) <= 0; $index++) {
                    $row = $existing[$index];
                    if (strcmp($row['serial_end'], $range['serial_start']) < 0) continue;
                    $start = strcmp($row['serial_start'], $range['serial_start']) > 0 ? $row['serial_start'] : $range['serial_start'];
                    $end = strcmp($row['serial_end'], $range['serial_end']) < 0 ? $row['serial_end'] : $range['serial_end'];
                    $matchesByRow[$range['source_row']][] = [
                        'source_row' => $range['source_row'], 'duplicate_start' => $start, 'duplicate_end' => $end,
                        'duplicate_qty' => $this->serialRangeCount($start, $end), 'upload_id' => (int) $row['upload_id'],
                        'existing_scan_date' => $row['scan_date'], 'existing_outlet' => $row['id_outlet'],
                        'existing_sf' => $row['sf'], 'existing_product' => $row['product_name'],
                    ];
                }
            }
        }

        $uploadIds = collect($matchesByRow)->flatten(1)->pluck('upload_id')->unique()->values();
        $filenames = $uploadIds->isEmpty() ? collect() : DB::table('product_tracking_uploads')->whereIn('id', $uploadIds)->pluck('filename', 'id');
        foreach ($matchesByRow as &$matches) {
            foreach ($matches as &$match) {
                $match['existing_filename'] = $filenames->get($match['upload_id']) ?? '';
                unset($match['upload_id']);
            }
            unset($match);
        }
        unset($matches);

        return $matchesByRow;
    }

    private function hasExistingOutletRedeemRange(string $kind, array $range): bool
    {
        return DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
            ->whereRaw("CONVERT(digipos.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci", [$kind])
            ->whereRaw("CONVERT(upload.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci", [$kind])
            ->whereRaw("CONVERT(upload.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
            ->where('digipos.id_outlet', $range['id_outlet'])
            ->whereRaw('CHAR_LENGTH(digipos.serial_start) = ?', [strlen($range['serial_start'])])
            ->whereRaw('digipos.serial_start <= ? AND digipos.serial_end >= ?', [$range['serial_end'], $range['serial_start']])
            ->exists();
    }

    private function subtractSerialCount(string $serial, int $count): string
    {
        $length = strlen($serial);
        $amount = (string) max(0, $count);
        if (strlen($amount) > $length || (strlen($amount) === $length && strcmp($amount, $serial) > 0)) {
            return str_repeat('0', $length);
        }

        $borrow = 0;
        $result = '';
        for ($i = $length - 1, $j = strlen($amount) - 1; $i >= 0; $i--, $j--) {
            $digit = (int) $serial[$i] - (int) ($j >= 0 ? $amount[$j] : '0') - $borrow;
            $borrow = $digit < 0 ? 1 : 0;
            if ($borrow) $digit += 10;
            $result = $digit.$result;
        }

        return str_pad($result, $length, '0', STR_PAD_LEFT);
    }

    private function compareSerials(string $left, string $right): int
    {
        $left = ltrim($left, '0') ?: '0';
        $right = ltrim($right, '0') ?: '0';
        return strlen($left) <=> strlen($right) ?: strcmp($left, $right);
    }

    private function subtractOneFromSerial(string $serial): string
    {
        return $this->subtractSerialCount($serial, 1);
    }

    private function serialRangeCount(string $start, string $end): string
    {
        $borrow = 0;
        $difference = '';
        for ($i = strlen($end) - 1; $i >= 0; $i--) {
            $digit = (int) $end[$i] - (int) ($start[$i] ?? '0') - $borrow;
            $borrow = $digit < 0 ? 1 : 0;
            if ($borrow) $digit += 10;
            $difference = $digit.$difference;
        }
        $carry = 1;
        for ($i = strlen($difference) - 1; $i >= 0; $i--) {
            $digit = (int) $difference[$i] + $carry;
            $difference[$i] = (string) ($digit % 10);
            $carry = $digit >= 10 ? 1 : 0;
        }
        return ($carry ? '1' : '').(ltrim($difference, '0') ?: '0');
    }

    private function storeDuplicateScanReport(string $path, array $rows): void
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Baris awal file baru', 'SN duplikat awal', 'SN duplikat akhir', 'Qty SN duplikat', 'File upload sebelumnya', 'Tanggal scan sebelumnya', 'ID Outlet sebelumnya', 'Sales Force sebelumnya', 'Denom sebelumnya']);
        foreach ($rows as $row) {
            fputcsv($stream, [
                $row['source_row'], $row['duplicate_start'], $row['duplicate_end'], $row['duplicate_qty'],
                $this->csvSafe((string) $row['existing_filename']), $row['existing_scan_date'],
                $this->csvSafe((string) $row['existing_outlet']), $this->csvSafe((string) $row['existing_sf']),
                $this->csvSafe((string) $row['existing_product']),
            ]);
        }
        rewind($stream);
        Storage::disk('local')->put($path, "\xEF\xBB\xBF".stream_get_contents($stream));
        fclose($stream);
    }
}
