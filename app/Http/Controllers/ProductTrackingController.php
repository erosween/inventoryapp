<?php

namespace App\Http\Controllers;

use App\Services\ProductTrackingFile;
use App\Support\DenomGrouping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ProductTrackingController extends MonitaDumaiController
{
    private array $maxScanSerialRangeCountByLength = [];

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
        $summaryTableLevel = $tableOptions['summary_table'];
        abort_unless(Schema::hasTable('mobile_sales_serials'), 503, 'Data SN Mobile SURYA belum tersedia. Jalankan migrasi terbaru terlebih dahulu.');
        $hasScanUploads = DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
            ->where('upload.kind', 'scan')->where('upload.status', 'complete')
            ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])->exists();

        $activeTab = $filters['tab'];
        $sourceQuery = $this->mobileSerialsQuery($filters)
            ->where('serial.identifier_type', 'sn')
            ->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'sale.idsf')
            ->select([
                'serial.id', 'serial.serial_start', 'serial.serial_end', 'serial.qty', 'serial.iddenom',
                'sale.id_outlet', 'sale.idsf', 'outlet.nama_outlet', DB::raw("COALESCE(NULLIF(sales_force.idtap, ''), outlet.tap) AS tap"), 'outlet.kecamatan', 'sales_force.namasf',
            ])
            ->selectSub(function ($query) use ($filters) {
                $query->from('product_tracking_digipos as digipos')
                    ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
                    ->where('upload.kind', 'scan')->where('upload.status', 'complete')
                    ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])
                    ->whereColumn('digipos.id_outlet', 'sale.id_outlet')
                    ->whereRaw('CAST(digipos.serial_start AS DECIMAL(24,0)) <= CAST(serial.serial_end AS DECIMAL(24,0))')
                    ->whereRaw('CAST(digipos.serial_end AS DECIMAL(24,0)) >= CAST(serial.serial_start AS DECIMAL(24,0))')
                    ->selectRaw('COALESCE(SUM(GREATEST(LEAST(CAST(digipos.serial_end AS DECIMAL(24,0)), CAST(serial.serial_end AS DECIMAL(24,0))) - GREATEST(CAST(digipos.serial_start AS DECIMAL(24,0)), CAST(serial.serial_start AS DECIMAL(24,0))) + 1, 0)), 0)');
            }, 'matched_qty')
            ->orderBy('sales_force.idtap')->orderBy('outlet.nama_outlet');

        // Aggregate at the database so validation stays responsive for large sales periods.
        $outletTotals = DB::query()->fromSub(clone $sourceQuery, 'rows')
            ->selectRaw('id_outlet, MAX(nama_outlet) AS nama_outlet, COALESCE(MAX(tap), ?) AS tap, MAX(idsf) AS idsf, MAX(namasf) AS namasf, MAX(kecamatan) AS kecamatan, SUM(qty) AS qty, SUM(LEAST(qty, matched_qty)) AS matched_qty', ['Tanpa TAP'])
            ->groupBy('id_outlet');
        $outletQuery = DB::query()->fromSub($outletTotals, 'outlet_totals')
            ->selectRaw('id_outlet, nama_outlet, tap, idsf, namasf, kecamatan, qty, matched_qty, GREATEST(qty - matched_qty, 0) AS mismatch_qty');
        $tableRows = DB::query()->fromSub($outletQuery, 'outlet_rows');
        foreach (['columns', 'exclude'] as $mode) {
            $detailTableOptions[$mode] = array_filter($detailTableOptions[$mode] ?? [], fn ($values, $column) => ! in_array($column, ['sf','outlet_count','matched_outlets','mismatch_outlets'], true), ARRAY_FILTER_USE_BOTH);
            $tableOptions[$mode] = array_filter($tableOptions[$mode] ?? [], fn ($values, $column) => in_array($column, ['sf','outlet_count','matched_outlets','mismatch_outlets'], true), ARRAY_FILTER_USE_BOTH);
            foreach ($detailTableOptions[$mode] ?? [] as $column => $values) {
                $expression = $this->soldTableExpression($column);
                $tableRows->whereRaw($expression.($mode === 'columns' ? ' IN (' : ' NOT IN (').implode(',', array_fill(0, count($values), '?')).')', $values);
            }
        }
        if (! empty($summaryTarget['summary_level']) && isset($summaryTarget['summary_key'])) {
            $summaryField = $summaryTarget['summary_level'] === 'tap' ? 'tap' : 'idsf';
            if ($summaryTarget['summary_key'] === '__NULL__') {
                $tableRows->whereNull('outlet_rows.'.$summaryField);
            } else {
                $tableRows->where('outlet_rows.'.$summaryField, $summaryTarget['summary_key']);
            }
        }
        if (($summaryTarget['summary_status'] ?? '') === 'matched') {
            $tableRows->where('outlet_rows.mismatch_qty', 0);
        } elseif (($summaryTarget['summary_status'] ?? '') === 'mismatch') {
            $tableRows->where('outlet_rows.mismatch_qty', '>', 0);
        }
        $sort = $tableOptions['sort'] ?? 'nama_outlet';
        $sortExpression = match ($sort) {
            'nama_outlet' => 'outlet_rows.nama_outlet',
            'id_outlet' => 'outlet_rows.id_outlet',
            'tap' => 'outlet_rows.tap',
            'namasf' => 'outlet_rows.namasf',
            'kecamatan' => 'kecamatan',
            'status' => 'outlet_rows.mismatch_qty',
            'sf', 'outlet_count', 'matched_outlets', 'mismatch_outlets' => 'outlet_rows.nama_outlet',
            default => 'outlet_rows.'.$sort,
        };
        $outlets = (clone $tableRows)->orderBy($sortExpression, $tableOptions['direction'] ?? 'asc')->orderBy('outlet_rows.id_outlet')->get();

        $tapOrder = ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING', 'BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'];
        $tapSummaries = DB::query()->fromSub($outletQuery, 'outlets')
            ->selectRaw('tap AS `key`, tap AS label, COUNT(*) AS outlet_count, SUM(CASE WHEN mismatch_qty = 0 THEN 1 ELSE 0 END) AS matched_outlets, SUM(CASE WHEN mismatch_qty > 0 THEN 1 ELSE 0 END) AS mismatch_outlets, SUM(qty) AS qty, SUM(matched_qty) AS matched_qty, SUM(mismatch_qty) AS mismatch_qty')
            ->whereIn('tap', $tapOrder)->groupBy('tap')->get()
            ->sortBy(fn ($group) => array_search(strtoupper($group->key), $tapOrder, true))
            ->values();

        $sfTotals = DB::query()->fromSub(clone $sourceQuery, 'rows')
            ->selectRaw('idsf, id_outlet, MAX(namasf) AS namasf, MAX(tap) AS tap, SUM(qty) AS qty, SUM(LEAST(qty, matched_qty)) AS matched_qty')
            ->groupBy('idsf', 'id_outlet');
        $sfSummaries = DB::query()->fromSub($sfTotals, 'sf_totals')
            ->selectRaw("idsf AS `key`, COALESCE(MAX(namasf), idsf, ?) AS label, COALESCE(GROUP_CONCAT(DISTINCT tap ORDER BY tap SEPARATOR ', '), ?) AS tap, COUNT(*) AS outlet_count, SUM(CASE WHEN matched_qty >= qty THEN 1 ELSE 0 END) AS matched_outlets, SUM(CASE WHEN matched_qty < qty THEN 1 ELSE 0 END) AS mismatch_outlets, SUM(qty) AS qty, SUM(matched_qty) AS matched_qty, SUM(GREATEST(qty - matched_qty, 0)) AS mismatch_qty", ['Tanpa Sales Force', 'Tanpa TAP'])
            ->groupBy('idsf')->orderBy('label')->get();

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

        $totals = DB::query()->fromSub($outletQuery, 'outlets')->selectRaw('COUNT(*) AS outlet_count, SUM(CASE WHEN mismatch_qty = 0 THEN 1 ELSE 0 END) AS matched_outlets, SUM(CASE WHEN mismatch_qty > 0 THEN 1 ELSE 0 END) AS mismatch_outlets, SUM(qty) AS serial_count, SUM(matched_qty) AS matched_qty, SUM(mismatch_qty) AS mismatch_qty')->first();
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
            'tableOptions' => $detailTableOptions,
            'summaryTableOptions' => $tableOptions,
            'summaryTableLevel' => $summaryTableLevel,
            'summaryTarget' => $summaryTarget,
            'tapSummaries' => $tapSummaries,
            'clusterSummaries' => $clusterSummaries,
            'sfSummaries' => $sfSummaries,
            'taps' => DB::table('idsf')->when($this->suryaTapScopes(), fn ($query, $scopes) => $query->whereIn('idtap', $scopes))->distinct()->orderBy('idtap')->pluck('idtap'),
            'sfs' => $this->mobileSerialsQuery($filters, false)->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'sale.idsf')->distinct()->orderBy('sales_force.namasf')->get(['sale.idsf', 'sales_force.namasf']),
            'denoms' => $this->mobileSerialsQuery($filters, false)->join('denom as option_denom', 'option_denom.iddenom', '=', 'serial.iddenom')->distinct()->orderBy('option_denom.denom')->get(['serial.iddenom', 'option_denom.denom']),
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
        ]);
        $page = (int) ($data['page'] ?? 1);
        $scanRanges = DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
            ->where('upload.kind', 'scan')->where('upload.status', 'complete')
            ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])
            ->where('digipos.id_outlet', $data['id_outlet'])
            ->orderBy('digipos.serial_start')
            ->get(['digipos.serial_start', 'digipos.serial_end']);
        $rows = $this->mobileSerialsQuery($filters)
            ->where('sale.id_outlet', $data['id_outlet'])
            ->where('serial.identifier_type', 'sn')
            ->join('denom as outlet_serial_denom', 'outlet_serial_denom.iddenom', '=', 'serial.iddenom')
            ->orderBy('outlet_serial_denom.denom')->orderBy('serial.serial_start')
            ->offset(($page - 1) * 50)->limit(51)
            ->get(['serial.serial_start', 'serial.serial_end', 'serial.qty', 'serial.iddenom', 'outlet_serial_denom.denom']);

        $unmatchedRows = $rows->take(50)->flatMap(function ($row) use ($scanRanges) {
            $start = (string) $row->serial_start;
            $end = (string) $row->serial_end;
            $cursor = $start;
            $unmatched = [];
            foreach ($scanRanges as $scan) {
                $scanStart = (string) $scan->serial_start;
                $scanEnd = (string) $scan->serial_end;
                if ($this->compareSerials($scanEnd, $cursor) < 0 || $this->compareSerials($scanStart, $end) > 0) continue;
                if ($this->compareSerials($scanStart, $cursor) > 0) {
                    $gapEnd = $this->subtractOneFromSerial($scanStart);
                    $unmatched[] = ['start' => $cursor, 'end' => $gapEnd, 'qty' => (int) $this->serialRangeCount($cursor, $gapEnd), 'denom' => $row->denom ?: $row->iddenom];
                }
                if ($this->compareSerials($scanEnd, $end) >= 0) { $cursor = null; break; }
                if ($this->compareSerials($scanEnd, $cursor) >= 0) $cursor = $this->incrementSerial($scanEnd);
            }
            if ($cursor !== null && $this->compareSerials($cursor, $end) <= 0) {
                $unmatched[] = ['start' => $cursor, 'end' => $end, 'qty' => (int) $this->serialRangeCount($cursor, $end), 'denom' => $row->denom ?: $row->iddenom];
            }
            return $unmatched;
        })->values();

        return response()->json([
            'rows' => $unmatchedRows,
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
        $nota = $this->mobileSerialsQuery($filters)
            ->where('sale.id_outlet', $data['id_outlet'])
            ->where('serial.identifier_type', 'sn')
            ->whereRaw('CAST(serial.serial_start AS DECIMAL(24,0)) <= CAST(? AS DECIMAL(24,0))', [$sn])
            ->whereRaw('CAST(serial.serial_end AS DECIMAL(24,0)) >= CAST(? AS DECIMAL(24,0))', [$sn])
            ->leftJoin('denom as outlet_lookup_denom', 'outlet_lookup_denom.iddenom', '=', 'serial.iddenom')
            ->orderByDesc('sale.tgl')->limit(20)
            ->get(['sale.tgl', 'serial.serial_start', 'serial.serial_end', 'serial.qty', 'serial.iddenom', 'outlet_lookup_denom.denom']);

        $digipos = DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
            ->leftJoin('appsdumais as scan_outlet', 'scan_outlet.id_outlet', '=', 'digipos.id_outlet')
            ->where('upload.kind', 'scan')->where('upload.status', 'complete')
            ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])
            ->whereRaw('CAST(digipos.serial_start AS DECIMAL(24,0)) <= CAST(? AS DECIMAL(24,0))', [$sn])
            ->whereRaw('CAST(digipos.serial_end AS DECIMAL(24,0)) >= CAST(? AS DECIMAL(24,0))', [$sn])
            ->orderByDesc('digipos.scan_date')->limit(20)
            ->get(['digipos.scan_date', 'digipos.id_outlet', 'scan_outlet.nama_outlet', 'digipos.serial_start', 'digipos.serial_end', 'digipos.product_name']);

        return response()->json([
            'sn' => $sn,
            'nota' => $nota->map(fn ($row) => [
                'date' => $row->tgl,
                'start' => $row->serial_start,
                'end' => $row->serial_end,
                'qty' => (int) $row->qty,
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
        $request->validate(['column' => 'required|in:nama_outlet,id_outlet,kecamatan,tap,namasf,qty,matched_qty,mismatch_qty,status,sf,outlet_count,matched_outlets,mismatch_outlets', 'summary_table' => 'nullable|in:tap,sf', 'q' => 'nullable|string|max:150', 'value_page' => 'nullable|integer|min:1|max:10000']);
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
            'sort' => 'nullable|in:nama_outlet,id_outlet,kecamatan,tap,namasf,qty,matched_qty,mismatch_qty,status,sf,outlet_count,matched_outlets,mismatch_outlets',
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
                if (! in_array($column, ['nama_outlet','id_outlet','kecamatan','tap','namasf','qty','matched_qty','mismatch_qty','status','sf','outlet_count','matched_outlets','mismatch_outlets'], true)) {
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
            'namasf' => "COALESCE(NULLIF(`outlet_rows`.`namasf`, ''), `outlet_rows`.`idsf`, '—')",
            'qty' => 'COALESCE(`outlet_rows`.`qty`, 0)',
            'matched_qty' => 'COALESCE(`outlet_rows`.`matched_qty`, 0)',
            'mismatch_qty' => 'COALESCE(`outlet_rows`.`mismatch_qty`, 0)',
            'status' => 'CASE WHEN `mismatch_qty` = 0 THEN \'Sesuai\' ELSE \'Perlu dicek\' END',
            default => throw ValidationException::withMessages(['column' => 'Kolom tidak valid.']),
        };
    }

    private function soldOutletRows(array $filters)
    {
        $sourceQuery = $this->mobileSerialsQuery($filters)
            ->where('serial.identifier_type', 'sn')
            ->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'sale.idsf')
            ->select([
                'serial.id', 'serial.serial_start', 'serial.serial_end', 'serial.qty', 'serial.iddenom',
                'sale.id_outlet', 'sale.idsf', 'outlet.nama_outlet', DB::raw("COALESCE(NULLIF(sales_force.idtap, ''), outlet.tap) AS tap"), 'outlet.kecamatan', 'sales_force.namasf',
            ])
            ->selectSub(function ($query) use ($filters) {
                $query->from('product_tracking_digipos as digipos')
                    ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
                    ->where('upload.kind', 'scan')->where('upload.status', 'complete')
                    ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])
                    ->whereColumn('digipos.id_outlet', 'sale.id_outlet')
                    ->whereRaw('CAST(digipos.serial_start AS DECIMAL(24,0)) <= CAST(serial.serial_end AS DECIMAL(24,0))')
                    ->whereRaw('CAST(digipos.serial_end AS DECIMAL(24,0)) >= CAST(serial.serial_start AS DECIMAL(24,0))')
                    ->selectRaw('COALESCE(SUM(GREATEST(LEAST(CAST(digipos.serial_end AS DECIMAL(24,0)), CAST(serial.serial_end AS DECIMAL(24,0))) - GREATEST(CAST(digipos.serial_start AS DECIMAL(24,0)), CAST(serial.serial_start AS DECIMAL(24,0))) + 1, 0)), 0)');
            }, 'matched_qty');

        $outletTotals = DB::query()->fromSub($sourceQuery, 'rows')
            ->selectRaw('id_outlet, MAX(nama_outlet) AS nama_outlet, COALESCE(MAX(tap), ?) AS tap, MAX(idsf) AS idsf, MAX(namasf) AS namasf, MAX(kecamatan) AS kecamatan, SUM(qty) AS qty, SUM(LEAST(qty, matched_qty)) AS matched_qty', ['Tanpa TAP'])
            ->groupBy('id_outlet');
        return DB::query()->fromSub($outletTotals, 'outlet_totals')
            ->selectRaw('id_outlet, nama_outlet, tap, idsf, namasf, kecamatan, qty, matched_qty, GREATEST(qty - matched_qty, 0) AS mismatch_qty');
    }

    private function soldSummaryRows(array $filters, string $level)
    {
        $source = $this->soldOutletRows($filters);
        $key = $level === 'tap' ? 'tap' : 'idsf';
        $label = $level === 'tap' ? 'tap' : 'COALESCE(MAX(namasf), idsf, \'Tanpa Sales Force\')';

        return DB::query()->fromSub($source, 'summary_source')
            ->selectRaw("$key AS `summary_key`, $label AS label, COUNT(*) AS outlet_count, SUM(CASE WHEN mismatch_qty = 0 THEN 1 ELSE 0 END) AS matched_outlets, SUM(CASE WHEN mismatch_qty > 0 THEN 1 ELSE 0 END) AS mismatch_outlets, SUM(qty) AS qty, SUM(matched_qty) AS matched_qty, SUM(mismatch_qty) AS mismatch_qty")
            ->groupBy($key);
    }

    public function downloadSoldSerials(Request $request)
    {
        $filters = $this->soldSerialFilters($request);
        abort_unless(Schema::hasTable('mobile_sales_serials'), 503, 'Data SN Mobile SURYA belum tersedia. Jalankan migrasi terbaru terlebih dahulu.');

        $query = $this->mobileSerialsQuery($filters);
        $serialCount = (int) (clone $query)->sum('serial.qty');
        if ($serialCount > 500000) {
            return redirect()->route('surya.product-tracking.sold', $request->query())
                ->withErrors(['download' => 'Hasil lebih dari 500.000 SN. Persempit tanggal atau filter agar file tetap aman diproses.']);
        }

        $filename = 'sn-'.$filters['tab'].'-'.$filters['date'].'.csv';
        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal ST', 'ID Outlet', 'Nama Outlet', 'TAP', 'Sales Force', 'Produk', 'Group Product', 'Sub Product', 'Validity Product', 'SN / Nomor']);

            foreach ($query->select([
                'serial.identifier_type', 'serial.serial_start', 'serial.serial_end', 'serial.qty', 'serial.iddenom',
                'sale.tgl as sale_date', 'sale.id_outlet', 'sale.idsf', 'outlet.nama_outlet', DB::raw("COALESCE(NULLIF(sales_force.idtap, ''), outlet.tap) AS tap"),
                'sales_force.namasf', 'denom.denom', 'denom.product', 'denom.product_variant', 'denom.product_group', 'denom.kategori_inject', 'denom.group_name',
            ])->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'sale.idsf')->orderBy('sale.tgl')->orderBy('sale.id')->orderBy('serial.id')->cursor() as $row) {
                $current = (string) $row->serial_start;
                $last = (string) $row->serial_end;
                $qty = (int) $row->qty;
                $inferred = DenomGrouping::infer((object) [
                    'iddenom' => $row->iddenom,
                    'denom' => $row->denom,
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
                        $row->sale_date, $row->id_outlet,
                        $this->csvSafe($row->nama_outlet ?? ''), $this->csvSafe($row->tap ?? ''), $this->csvSafe($row->namasf ?? $row->idsf),
                        $this->csvSafe($row->denom ?? $row->iddenom), $groupProduct, $subProduct,
                        $this->csvSafe($validity), $current,
                    ]);
                    if ($current === $last || $row->identifier_type === 'number') break;
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

    private function mobileSerialsQuery(array $filters, bool $applyFilters = true)
    {
        $query = DB::table('mobile_sales_serials as serial')
            ->join('mobile_penjualan as sale', 'sale.id', '=', 'serial.mobile_penjualan_id')
            ->leftJoin('appsdumais as outlet', 'outlet.id_outlet', '=', 'sale.id_outlet')
            ->leftJoin('idsf as owner_sf', 'owner_sf.idsf', '=', 'sale.idsf')
            ->leftJoin('denom', 'denom.iddenom', '=', 'serial.iddenom')
            ->when($filters['tab'] === 'approve', fn ($query) => $query->where('sale.status', 'approved'), fn ($query) => $query->whereNotIn('sale.status', ['draft_wa', 'rejected']))
            ->whereBetween('sale.tgl', [$filters['date_from'], $filters['date_to']]);

        if ($scopes = $this->suryaTapScopes()) {
            $query->whereIn(DB::raw("COALESCE(NULLIF(owner_sf.idtap, ''), outlet.tap)"), $scopes);
        }
        if ($applyFilters) {
            $query->when($filters['tap'] !== '', fn ($builder) => $builder->whereRaw("COALESCE(NULLIF(owner_sf.idtap, ''), outlet.tap) = ?", [$filters['tap']]))
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
        $kind = $request->query('kind', 'warehouse');
        abort_unless(in_array($kind, ['warehouse', 'scan'], true), 404);
        $period = $request->query('period', 'daily');
        abort_unless(in_array($period, ['daily', 'monthly'], true), 404);
        $totalsQuery = $kind === 'scan' ? DB::table('product_tracking_digipos as d')
                ->join('product_tracking_uploads as u', 'u.id', '=', 'd.upload_id')
                ->where('d.kind', 'scan')->where('u.kind', 'scan')->where('u.status', 'complete')
                ->whereNotNull('d.scan_date')
                ->selectRaw("SUM(CASE WHEN LOWER(u.filename) REGEXP '^pv_(dumai|bengkalis|duri|rupat|sei_pakning)_' THEN d.serial_count ELSE 0 END) AS dumai_bengkalis_pv_qty")
                ->selectRaw("SUM(CASE WHEN LOWER(u.filename) REGEXP '^sa_(dumai|bengkalis|duri|rupat|sei_pakning)_' THEN d.serial_count ELSE 0 END) AS dumai_bengkalis_sa_qty")
                ->selectRaw("SUM(CASE WHEN LOWER(u.filename) REGEXP '^pv_(rokan_hilir|rohil|rokan|bagan_batu|bagan_siapi_api|bagan_siapi-api|ujung_tanjung)_' THEN d.serial_count ELSE 0 END) AS rokan_hilir_pv_qty")
                ->selectRaw("SUM(CASE WHEN LOWER(u.filename) REGEXP '^sa_(rokan_hilir|rohil|rokan|bagan_batu|bagan_siapi_api|bagan_siapi-api|ujung_tanjung)_' THEN d.serial_count ELSE 0 END) AS rokan_hilir_sa_qty")
                ->selectRaw("SUM(CASE WHEN LEFT(LOWER(u.filename), 3) = 'pv_' THEN d.serial_count ELSE 0 END) AS total_pv_qty")
                ->selectRaw("SUM(CASE WHEN LEFT(LOWER(u.filename), 3) = 'sa_' THEN d.serial_count ELSE 0 END) AS total_sa_qty")
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
            'uploads' => DB::table('product_tracking_uploads')->where('kind', $kind)->whereIn('status', ['complete', 'duplicate'])->orderByDesc('id')->limit(30)->get(),
            'pending' => DB::table('product_tracking_uploads')->where('kind', $kind)->where('status', 'mapping')->orderByDesc('id')->get(),
            'dailyTotals' => $dailyTotals,
        ]);
    }

    public function downloadDuplicateScanReport(int $upload)
    {
        $this->requireEditor();
        $record = DB::table('product_tracking_uploads')->where('id', $upload)->where('kind', 'scan')->where('status', 'mapping')->first();
        abort_unless($record, 404);
        $path = "product-tracking/duplicate-reports/{$upload}.csv";
        abort_unless(Storage::disk('local')->exists($path), 404, 'Belum ada laporan SN duplikat untuk upload ini.');
        return Storage::disk('local')->download($path, 'sn-duplikat-upload-'.$upload.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function salesStock()
    {
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
                if (in_array('', [$sfcode, $tap, $cluster, $iddenom, $start, $end, $qtyRaw], true)) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: semua kolom wajib diisi."]);
                }
                if (mb_strlen($iddenom) > 25) throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: IDDENOM maksimal 25 karakter."]);
                if (! preg_match('/^\\d{12}$|^0\\d{15}$/', $start) || ! preg_match('/^\\d{12}$|^0\\d{15}$/', $end) || strlen($start) !== strlen($end)) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: SN harus 12 digit untuk PV atau 16 digit diawali 0 untuk SA; panjang SN awal dan akhir harus sama."]);
                }
                if (strcmp($end, $start) < 0) throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: SN akhir harus sama dengan atau lebih besar dari SN awal."]);
                if (! preg_match('/^\\d+$/', $qtyRaw) || (int) $qtyRaw < 1 || (int) $qtyRaw > 2000000) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: QTY harus bilangan bulat antara 1 dan 2.000.000."]);
                }
                $qty = (int) $qtyRaw;
                if ((int) $end - (int) $start + 1 !== $qty) throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: QTY harus sama dengan jumlah SN dalam rentang."]);
                $group = $sfcode."\x1f".strlen($start);
                $intervals[$group][] = ['start' => $start, 'end' => $end, 'row' => $rowNumber];
                $denomLengths[$iddenom][$rowNumber] = strlen($start);
                $rows[] = ['sfcode' => $sfcode, 'tap' => $tap, 'cluster' => $cluster, 'iddenom' => $iddenom, 'serial_start' => $start, 'serial_end' => $end, 'qty' => $qty];
            }
        } finally {
            Storage::disk('local')->delete($temporaryPath);
        }
        if ($rows === []) throw ValidationException::withMessages(['file' => 'File tidak berisi data stok SN.']);
        $denoms = DB::table('denom')->whereIn('iddenom', array_keys($denomLengths))->get(['iddenom', 'product_group', 'kategori_inject', 'group_name'])->keyBy('iddenom');
        foreach ($denomLengths as $iddenom => $lengths) {
            $denom = $denoms->get($iddenom);
            if (! $denom) throw ValidationException::withMessages(['file' => "IDDENOM {$iddenom} tidak ditemukan di master denom."]);
            $product = DenomGrouping::infer($denom)['product'];
            $expectedLength = $product === 'SA' ? 16 : 12;
            foreach ($lengths as $rowNumber => $length) {
                if ($length !== $expectedLength) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: IDDENOM {$iddenom} adalah produk {$product}, jadi SN harus {$expectedLength} digit."]);
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

        return redirect()->route('surya.product-tracking.sales-stock')->with('success', number_format(count($rows)).' baris stok SN Sales berhasil diunggah.');
    }

    public function stageImport(Request $request, ProductTrackingFile $reader)
    {
        $this->requireEditor();
        $data = $request->validate([
            'kind' => 'required|in:warehouse,scan',
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
        $record = DB::table('product_tracking_uploads')->where('id', $upload)->where('status', 'mapping')->first();
        abort_unless($record && $record->storage_path && Storage::disk('local')->exists($record->storage_path), 404);
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
        $record = DB::table('product_tracking_uploads')->where('id', $upload)->where('status', 'mapping')->first();
        abort_unless($record, 404);

        if ($record->storage_path) {
            Storage::disk('local')->delete($record->storage_path);
        }
        DB::table('product_tracking_uploads')->where('id', $upload)->where('status', 'mapping')->delete();

        return redirect()->route('surya.product-tracking.imports', ['kind' => $record->kind])->with('success', 'Upload dibatalkan dan file sementara dihapus.');
    }

    public function completeImport(Request $request, int $upload, ProductTrackingFile $reader)
    {
        $this->requireEditor();
        $record = DB::table('product_tracking_uploads')->where('id', $upload)->where('status', 'mapping')->first();
        abort_unless($record && $record->storage_path && Storage::disk('local')->exists($record->storage_path), 404);
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
        if ($record->kind === 'scan' && empty($mapping['date_column'])) {
            throw ValidationException::withMessages(['date_column' => 'Pilih kolom tanggal dari file scan agar tanggal scan dapat dicatat otomatis.']);
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
                if (isset($seenSerials[$serial])) {
                    throw ValidationException::withMessages(['file' => "SN duplikat {$serial} ditemukan di baris {$seenSerials[$serial]} dan {$rowNumber}. Hapus salah satu baris lalu upload ulang."]);
                }
                $seenSerials[$serial] = $rowNumber;
                $soldAtValue = $this->mappedValue($values, $mapping['date_column'] ?? null);
                if ($record->kind === 'scan' && $soldAtValue === '') {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: tanggal scan wajib diisi dengan format dd-mmm-yyyy, contoh 12-oct-2026."]);
                }
                $soldAt = $soldAtValue !== ''
                    ? ($record->kind === 'scan' ? ProductTrackingFile::scanDate($soldAtValue) : ProductTrackingFile::date($soldAtValue))
                    : null;
                if ($soldAtValue !== '' && ! $soldAt) {
                    $hint = $record->kind === 'scan' ? 'Gunakan format dd-mmm-yyyy, contoh 12-oct-2026.' : 'Format tanggal tidak dikenali.';
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: {$hint}"]);
                }
                if ($record->kind === 'scan' && $detectedScanDate === null && $soldAt !== null) {
                    $detectedScanDate = $soldAt;
                } elseif ($record->kind === 'scan' && $soldAt !== null && $detectedScanDate !== $soldAt) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: tanggal scan berbeda dari baris sebelumnya. Satu file upload harus memakai satu tanggal scan yang sama."]);
                }
                $sf = mb_substr($this->mappedValue($values, $mapping['sf_column'] ?? null), 0, 150);
                $product = mb_substr($this->mappedValue($values, $mapping['product_column'] ?? null), 0, 200);
                $date = $soldAt ?? ($record->kind === 'scan' ? $detectedScanDate : null);
                $key = implode("\x1f", [$date ?? '', $outletId, $sf, $product]);
                if ($range !== null && $key === $range['key'] && $this->incrementSerial($range['serial_end']) === $serial) {
                    $range['serial_end'] = $serial;
                    $range['serial_count']++;
                } else {
                    if ($range !== null) {
                        $matches = $record->kind === 'scan' ? $this->findScanRangeDuplicates($range) : [];
                        if ($matches) {
                            foreach ($matches as $match) $duplicateRanges[] = $match;
                        } else {
                            $batch[] = $this->digiposRangeRow($record->id, $record->kind, $range, $now);
                        }
                    }
                    $range = ['key' => $key, 'scan_date' => $record->kind === 'scan' ? $date : null, 'serial_start' => $serial, 'serial_end' => $serial, 'serial_count' => 1, 'id_outlet' => $outletId, 'sf' => $sf, 'sold_at' => $date, 'product_name' => $product, 'source_row' => $rowNumber];
                }
                $count++;
                if (count($batch) >= 500) {
                    DB::table('product_tracking_digipos')->insert($batch);
                    $batch = [];
                }
            }
            if ($range !== null) {
                $matches = $record->kind === 'scan' ? $this->findScanRangeDuplicates($range) : [];
                if ($matches) {
                    foreach ($matches as $match) $duplicateRanges[] = $match;
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
            throw $exception;
        }
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
        $warehouseId = DB::table('product_tracking_uploads')->where('kind', 'warehouse')->where('status', 'complete')->orderByDesc('id')->value('id');
        $sales = $this->scopedSales()
            ->whereBetween('sale.sale_date', [$from, $to])
            ->when(! empty($filters['tap']), fn ($query) => $query->where('outlet.tap', $filters['tap']))
            ->when(! empty($filters['sf']), fn ($query) => $query->where('sale.sf', $filters['sf']))
            ->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'sale.sf')
            ->select('sale.*', 'outlet.tap', 'sales_force.namasf')
            ->selectSub($this->matchedDigiposQuery('warehouse', $warehouseId), 'warehouse_matches')
            ->selectSub($this->matchedDigiposQuery('warehouse', $warehouseId, true), 'warehouse_outlet_matches')
            ->selectSub($this->matchedDigiposQuery('scan', null, false, $from, $to), 'scan_matches')
            ->selectSub($this->matchedDigiposQuery('scan', null, true, $from, $to), 'scan_outlet_matches')
            ->orderByDesc('sale.sale_date')->orderByDesc('sale.id')
            ->paginate((int) ($filters['per_page'] ?? 25))->appends($request->query());
        $taps = DB::table('appsdumais')->when($this->suryaTapScopes(), fn ($query, $scopes) => $query->whereIn('tap', $scopes))->distinct()->orderBy('tap')->pluck('tap');
        $sfs = DB::table('product_tracking_sales as product_sales')
            ->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'product_sales.sf')
            ->when($this->suryaTapScopes(), function ($query, $scopes) {
                $query->whereIn('product_sales.id_outlet', DB::table('appsdumais')->select('id_outlet')->whereIn('tap', $scopes));
            })->select('product_sales.sf', 'sales_force.namasf')->distinct()->orderBy('sales_force.namasf')->get();
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
                    $sa->where('sale.identifier_type', 'sn')->whereRaw("UPPER(COALESCE(tracking_denom.product_group, '')) IN ('SA', 'SP')")
                        ->whereRaw('CAST(sale.serial_start AS DECIMAL(24,0)) <= CAST(? AS DECIMAL(24,0)) AND CAST(sale.serial_end AS DECIMAL(24,0)) >= CAST(? AS DECIMAL(24,0))', [$queryValue, $queryValue]);
                })->orWhere(function ($pv) use ($queryValue) {
                    $pv->where('sale.identifier_type', 'sn')->whereRaw("UPPER(COALESCE(tracking_denom.product_group, 'PV')) NOT IN ('SA', 'SP')")
                        ->where('sale.serial_start', '<=', $queryValue)->where('sale.serial_end', '>=', $queryValue);
                })->orWhere(function ($sub) use ($queryValue) {
                    $sub->where('sale.identifier_type', 'number')->where('sale.serial_start', $queryValue);
                });
            })->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'sale.sf')
                ->leftJoin('appsdumais as sales_outlet', 'sales_outlet.id_outlet', '=', 'sale.id_outlet')
                ->leftJoin('denom as sales_denom', 'sales_denom.iddenom', '=', 'sale.iddenom')
                ->orderByDesc('sale.sale_date')->get(['sale.*', 'sales_force.namasf', 'sales_outlet.nama_outlet', 'sales_outlet.kecamatan', 'sales_denom.denom as denom_name']);
            $scansQuery = DB::table('product_tracking_digipos as d')->join('product_tracking_uploads as u', 'u.id', '=', 'd.upload_id')
                ->where('u.kind', 'scan')->where('u.status', 'complete')->where(function ($match) use ($queryValue) {
                    $match->where(function ($sa) use ($queryValue) {
                        $sa->whereRaw("LEFT(LOWER(u.filename), 3) = 'sa_'")
                            ->whereRaw('CAST(d.serial_start AS DECIMAL(24,0)) <= CAST(? AS DECIMAL(24,0)) AND CAST(d.serial_end AS DECIMAL(24,0)) >= CAST(? AS DECIMAL(24,0))', [$queryValue, $queryValue]);
                    })->orWhere(function ($pv) use ($queryValue) {
                        $pv->whereRaw("LEFT(LOWER(u.filename), 3) <> 'sa_'")
                            ->where('d.serial_start', '<=', $queryValue)->where('d.serial_end', '>=', $queryValue);
                    });
                })
                ->orderByDesc('d.scan_date')->orderByDesc('d.id')->limit(100);
            if ($scopes = $this->suryaTapScopes()) $scansQuery->whereIn('d.id_outlet', DB::table('appsdumais')->select('id_outlet')->whereIn('tap', $scopes));
            $scans = $scansQuery->leftJoin('idsf as digipos_sales_force', 'digipos_sales_force.digipos_id', '=', 'd.sf')
                ->leftJoin('appsdumais as digipos_outlet', 'digipos_outlet.id_outlet', '=', 'd.id_outlet')
                ->get(['d.*', 'u.filename', 'digipos_sales_force.namasf', 'digipos_outlet.nama_outlet', 'digipos_outlet.kecamatan']);
            if (Schema::hasTable('mobile_sales_serials')) {
                $mobileQuery = DB::table('mobile_sales_serials as serial')->join('mobile_penjualan as sale', 'sale.id', '=', 'serial.mobile_penjualan_id')
                    ->leftJoin('denom as mobile_tracking_denom', 'mobile_tracking_denom.iddenom', '=', 'serial.iddenom')
                    ->where(function ($query) use ($queryValue) {
                        $query->where(function ($sa) use ($queryValue) { $sa->where('serial.identifier_type', 'sn')->whereRaw("UPPER(COALESCE(mobile_tracking_denom.product_group, '')) IN ('SA', 'SP')")->whereRaw('CAST(serial.serial_start AS DECIMAL(24,0)) <= CAST(? AS DECIMAL(24,0)) AND CAST(serial.serial_end AS DECIMAL(24,0)) >= CAST(? AS DECIMAL(24,0))', [$queryValue, $queryValue]); })
                            ->orWhere(function ($pv) use ($queryValue) { $pv->where('serial.identifier_type', 'sn')->whereRaw("UPPER(COALESCE(mobile_tracking_denom.product_group, 'PV')) NOT IN ('SA', 'SP')")->where('serial.serial_start', '<=', $queryValue)->where('serial.serial_end', '>=', $queryValue); })
                            ->orWhere(function ($sub) use ($queryValue) { $sub->where('serial.identifier_type', 'number')->where('serial.serial_start', $queryValue); });
                    })->whereNotIn('sale.status', ['draft_wa', 'rejected'])->orderByDesc('sale.tgl')->limit(50);
                if ($scopes = $this->suryaTapScopes()) $mobileQuery->whereIn('sale.id_outlet', DB::table('appsdumais')->select('id_outlet')->whereIn('tap', $scopes));
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

    private function matchedDigiposQuery(string $kind, $warehouseId = null, bool $sameOutlet = false, ?string $from = null, ?string $to = null)
    {
        $query = DB::table('product_tracking_digipos as digipos')->selectRaw('COUNT(*)')
            ->whereRaw("(sale.identifier_type = 'sn' AND digipos.serial_start <= sale.serial_end AND digipos.serial_end >= sale.serial_start)");
        if ($scopes = $this->suryaTapScopes()) {
            $query->join('appsdumais as digipos_outlet', 'digipos_outlet.id_outlet', '=', 'digipos.id_outlet')->whereIn('digipos_outlet.tap', $scopes);
        }
        if ($sameOutlet) $query->whereColumn('digipos.id_outlet', 'sale.id_outlet');
        if ($kind === 'warehouse') $query->where('digipos.upload_id', $warehouseId ?? 0);
        else {
            $query->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
                ->where('upload.kind', 'scan')->where('upload.status', 'complete')
                ->whereBetween('upload.scan_date', [$from, $to]);
        }
        return $query;
    }

    private function scopedSales()
    {
        $query = DB::table('product_tracking_sales as sale')->leftJoin('appsdumais as outlet', 'outlet.id_outlet', '=', 'sale.id_outlet');
        if ($scopes = $this->suryaTapScopes()) $query->whereIn('outlet.tap', $scopes);
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
        $length = strlen($range['serial_start']);
        if (! array_key_exists($length, $this->maxScanSerialRangeCountByLength)) {
            $this->maxScanSerialRangeCountByLength[$length] = (int) (DB::table('product_tracking_digipos as digipos')
                ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
                ->where('digipos.kind', 'scan')->where('upload.kind', 'scan')->where('upload.status', 'complete')
                ->whereRaw('CHAR_LENGTH(digipos.serial_start) = ?', [$length])
                ->max('digipos.serial_count') ?? 1);
        }
        $minimumStart = $this->subtractSerialCount($range['serial_start'], $this->maxScanSerialRangeCountByLength[$length]);

        $existing = DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
            ->where('digipos.kind', 'scan')
            ->where('upload.kind', 'scan')->where('upload.status', 'complete')
            ->where('digipos.serial_start', '>=', $minimumStart)
            ->where('digipos.serial_start', '<=', $range['serial_end'])
            ->where('digipos.serial_end', '>=', $range['serial_start'])
            ->whereRaw('CHAR_LENGTH(digipos.serial_start) = ?', [$length])
            ->orderBy('digipos.serial_start')
            ->get(['digipos.serial_start', 'digipos.serial_end', 'digipos.scan_date', 'digipos.id_outlet', 'digipos.sf', 'digipos.product_name', 'upload.filename']);

        return $existing->map(function ($row) use ($range) {
            $start = strcmp((string) $row->serial_start, $range['serial_start']) > 0 ? (string) $row->serial_start : $range['serial_start'];
            $end = strcmp((string) $row->serial_end, $range['serial_end']) < 0 ? (string) $row->serial_end : $range['serial_end'];
            return [
                'source_row' => $range['source_row'], 'duplicate_start' => $start, 'duplicate_end' => $end,
                'duplicate_qty' => $this->serialRangeCount($start, $end),
                'existing_filename' => $row->filename, 'existing_scan_date' => $row->scan_date,
                'existing_outlet' => $row->id_outlet, 'existing_sf' => $row->sf, 'existing_product' => $row->product_name,
            ];
        })->all();
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
