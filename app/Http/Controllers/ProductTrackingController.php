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
                'sale.id_outlet', 'sale.idsf', 'outlet.nama_outlet', 'outlet.tap', 'outlet.kecamatan', 'sales_force.namasf',
            ])
            ->selectSub(function ($query) use ($filters) {
                $query->from('product_tracking_digipos as digipos')
                    ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
                    ->where('upload.kind', 'scan')->where('upload.status', 'complete')
                    ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])
                    ->whereColumn('digipos.id_outlet', 'sale.id_outlet')
                    ->whereColumn('digipos.serial_start', '<=', 'serial.serial_end')
                    ->whereColumn('digipos.serial_end', '>=', 'serial.serial_start')
                    ->whereRaw('LENGTH(digipos.serial_start) = LENGTH(serial.serial_start)')
                    ->selectRaw('COALESCE(SUM(CAST(CASE WHEN digipos.serial_end < serial.serial_end THEN digipos.serial_end ELSE serial.serial_end END AS DECIMAL(24,0)) - CAST(CASE WHEN digipos.serial_start > serial.serial_start THEN digipos.serial_start ELSE serial.serial_start END AS DECIMAL(24,0)) + 1), 0)');
            }, 'matched_qty')
            ->orderBy('outlet.tap')->orderBy('outlet.nama_outlet');

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

        $tapSummaries = DB::query()->fromSub($outletQuery, 'outlets')
            ->selectRaw('tap AS `key`, tap AS label, COUNT(*) AS outlet_count, SUM(CASE WHEN mismatch_qty = 0 THEN 1 ELSE 0 END) AS matched_outlets, SUM(CASE WHEN mismatch_qty > 0 THEN 1 ELSE 0 END) AS mismatch_outlets, SUM(qty) AS qty, SUM(matched_qty) AS matched_qty, SUM(mismatch_qty) AS mismatch_qty')
            ->groupBy('tap')->orderBy('tap')->get();

        $sfTotals = DB::query()->fromSub(clone $sourceQuery, 'rows')
            ->selectRaw('idsf, id_outlet, MAX(namasf) AS namasf, SUM(qty) AS qty, SUM(LEAST(qty, matched_qty)) AS matched_qty')
            ->groupBy('idsf', 'id_outlet');
        $sfSummaries = DB::query()->fromSub($sfTotals, 'sf_totals')
            ->selectRaw('idsf AS `key`, COALESCE(MAX(namasf), idsf, ?) AS label, COUNT(*) AS outlet_count, SUM(CASE WHEN matched_qty >= qty THEN 1 ELSE 0 END) AS matched_outlets, SUM(CASE WHEN matched_qty < qty THEN 1 ELSE 0 END) AS mismatch_outlets, SUM(qty) AS qty, SUM(matched_qty) AS matched_qty, SUM(GREATEST(qty - matched_qty, 0)) AS mismatch_qty', ['Tanpa Sales Force'])
            ->groupBy('idsf')->orderBy('label')->get();

        $tapSummaries = $this->filterSoldSummaries($tapSummaries, 'tap', $tableOptions);
        $sfSummaries = $this->filterSoldSummaries($sfSummaries, 'sf', $tableOptions);

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
            'sfSummaries' => $sfSummaries,
            'taps' => DB::table('appsdumais')->when($this->suryaTapScopes(), fn ($query, $scopes) => $query->whereIn('tap', $scopes))->whereNotNull('tap')->distinct()->orderBy('tap')->pluck('tap'),
            'sfs' => $this->mobileSerialsQuery($filters, false)->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'sale.idsf')->distinct()->orderBy('sales_force.namasf')->get(['sale.idsf', 'sales_force.namasf']),
            'denoms' => $this->mobileSerialsQuery($filters, false)->join('denom as option_denom', 'option_denom.iddenom', '=', 'serial.iddenom')->distinct()->orderBy('option_denom.denom')->get(['serial.iddenom', 'option_denom.denom']),
            'activeTab' => $activeTab,
            'exportLimit' => 500000,
        ]);
    }

    public function soldColumnValues(Request $request)
    {
        $filters = $this->soldSerialFilters($request);
        $request->validate(['column' => 'required|in:nama_outlet,id_outlet,kecamatan,tap,namasf,qty,matched_qty,mismatch_qty,status,sf,outlet_count,matched_outlets,mismatch_outlets', 'summary_table' => 'nullable|in:tap,sf', 'q' => 'nullable|string|max:150', 'value_page' => 'nullable|integer|min:1|max:10000']);
        $column = $request->input('column');
        $summaryTableLevel = $request->input('summary_table') === 'sf' ? 'sf' : 'tap';
        if ($request->filled('summary_table')) {
            abort_unless(in_array($column, [$summaryTableLevel, 'outlet_count', 'matched_outlets', 'mismatch_outlets', 'qty', 'matched_qty', 'mismatch_qty'], true), 422, 'Kolom ringkasan tidak valid.');
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
                } elseif (str_ends_with($mode, '_columns')) {
                    $groups = $groups->filter(fn ($group) => in_array((string) ($group->{$column} ?? ''), $values, true));
                } else {
                    $groups = $groups->reject(fn ($group) => in_array((string) ($group->{$column} ?? ''), $values, true));
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
                'sale.id_outlet', 'sale.idsf', 'outlet.nama_outlet', 'outlet.tap', 'outlet.kecamatan', 'sales_force.namasf',
            ])
            ->selectSub(function ($query) use ($filters) {
                $query->from('product_tracking_digipos as digipos')
                    ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
                    ->where('upload.kind', 'scan')->where('upload.status', 'complete')
                    ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])
                    ->whereColumn('digipos.id_outlet', 'sale.id_outlet')
                    ->whereColumn('digipos.serial_start', '<=', 'serial.serial_end')
                    ->whereColumn('digipos.serial_end', '>=', 'serial.serial_start')
                    ->whereRaw('LENGTH(digipos.serial_start) = LENGTH(serial.serial_start)')
                    ->selectRaw('COALESCE(SUM(CAST(CASE WHEN digipos.serial_end < serial.serial_end THEN digipos.serial_end ELSE serial.serial_end END AS DECIMAL(24,0)) - CAST(CASE WHEN digipos.serial_start > serial.serial_start THEN digipos.serial_start ELSE serial.serial_start END AS DECIMAL(24,0)) + 1), 0)');
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

        $filename = 'sn-'.$filters['tab'].'-'.$filters['date_from'].'-'.$filters['date_to'].'.csv';
        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal ST', 'ID Outlet', 'Nama Outlet', 'TAP', 'Sales Force', 'Produk', 'Group Product', 'Sub Product', 'Validity Product', 'SN / Nomor']);

            foreach ($query->select([
                'serial.identifier_type', 'serial.serial_start', 'serial.serial_end', 'serial.qty', 'serial.iddenom',
                'sale.tgl as sale_date', 'sale.id_outlet', 'sale.idsf', 'outlet.nama_outlet', 'outlet.tap',
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
        $data = $request->validate([
            'date_from' => 'nullable|date|before_or_equal:today',
            'date_to' => 'nullable|date|before_or_equal:today',
            'tap' => 'nullable|string|max:100',
            'sf' => 'nullable|string|max:150',
            'iddenom' => 'nullable|string|max:25',
            'tab' => 'nullable|in:approve,progress',
        ]);
        $from = $data['date_from'] ?? now('Asia/Jakarta')->startOfMonth()->toDateString();
        $to = $data['date_to'] ?? now('Asia/Jakarta')->toDateString();
        if ($from > $to) {
            throw ValidationException::withMessages(['date_to' => 'Tanggal akhir harus sama atau sesudah tanggal awal.']);
        }

        return [
            'date_from' => $from,
            'date_to' => $to,
            'tap' => $data['tap'] ?? '',
            'sf' => $data['sf'] ?? '',
            'iddenom' => $data['iddenom'] ?? '',
            'identifier_type' => 'sn',
            'tab' => $data['tab'] ?? 'approve',
        ];
    }

    private function mobileSerialsQuery(array $filters, bool $applyFilters = true)
    {
        $query = DB::table('mobile_sales_serials as serial')
            ->join('mobile_penjualan as sale', 'sale.id', '=', 'serial.mobile_penjualan_id')
            ->leftJoin('appsdumais as outlet', 'outlet.id_outlet', '=', 'sale.id_outlet')
            ->leftJoin('denom', 'denom.iddenom', '=', 'serial.iddenom')
            ->where('sale.status', $filters['tab'] === 'progress' ? 'pending' : 'approved')
            ->whereBetween('sale.tgl', [$filters['date_from'], $filters['date_to']]);

        if ($scopes = $this->suryaTapScopes()) {
            $query->whereIn('outlet.tap', $scopes);
        }
        if ($applyFilters) {
            $query->when($filters['tap'] !== '', fn ($builder) => $builder->where('outlet.tap', $filters['tap']))
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
        return view('surya.product-tracking.imports', [
            'kind' => $kind,
            'uploads' => DB::table('product_tracking_uploads')->where('kind', $kind)->whereIn('status', ['complete', 'duplicate'])->orderByDesc('id')->limit(30)->get(),
            'pending' => DB::table('product_tracking_uploads')->where('kind', $kind)->where('status', 'mapping')->orderByDesc('id')->get(),
        ]);
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
                $soldAt = $soldAtValue !== '' ? ProductTrackingFile::date($soldAtValue) : null;
                if ($soldAtValue !== '' && ! $soldAt) throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: tanggal tidak dikenali."]);
                if ($record->kind === 'scan' && $detectedScanDate === null && $soldAt !== null) {
                    $detectedScanDate = $soldAt;
                }
                $sf = mb_substr($this->mappedValue($values, $mapping['sf_column'] ?? null), 0, 150);
                $product = mb_substr($this->mappedValue($values, $mapping['product_column'] ?? null), 0, 200);
                $date = $soldAt ?? ($record->kind === 'scan' ? $detectedScanDate : null);
                $key = implode("\x1f", [$date ?? '', $outletId, $sf, $product]);
                if ($range !== null && $key === $range['key'] && $this->incrementSerial($range['serial_end']) === $serial) {
                    $range['serial_end'] = $serial;
                    $range['serial_count']++;
                } else {
                    if ($range !== null) $batch[] = $this->digiposRangeRow($record->id, $record->kind, $range, $now);
                    $range = ['key' => $key, 'scan_date' => $record->kind === 'scan' ? $date : null, 'serial_start' => $serial, 'serial_end' => $serial, 'serial_count' => 1, 'id_outlet' => $outletId, 'sf' => $sf, 'sold_at' => $date, 'product_name' => $product];
                }
                $count++;
                if (count($batch) >= 500) {
                    DB::table('product_tracking_digipos')->insert($batch);
                    $batch = [];
                }
            }
            if ($range !== null) $batch[] = $this->digiposRangeRow($record->id, $record->kind, $range, $now);
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
            $sales = $this->scopedSales()->where(function ($query) use ($queryValue) {
                $query->where(function ($sub) use ($queryValue) {
                    $sub->where('sale.identifier_type', 'sn')->where('sale.serial_start', '<=', $queryValue)->where('sale.serial_end', '>=', $queryValue);
                })->orWhere(function ($sub) use ($queryValue) {
                    $sub->where('sale.identifier_type', 'number')->where('sale.serial_start', $queryValue);
                });
            })->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'sale.sf')
                ->leftJoin('appsdumais as sales_outlet', 'sales_outlet.id_outlet', '=', 'sale.id_outlet')
                ->leftJoin('denom as sales_denom', 'sales_denom.iddenom', '=', 'sale.iddenom')
                ->orderByDesc('sale.sale_date')->get(['sale.*', 'sales_force.namasf', 'sales_outlet.nama_outlet', 'sales_outlet.kecamatan', 'sales_denom.denom as denom_name']);
            $scansQuery = DB::table('product_tracking_digipos as d')->join('product_tracking_uploads as u', 'u.id', '=', 'd.upload_id')
                ->where('u.kind', 'scan')->where('u.status', 'complete')->where('d.serial_start', '<=', $queryValue)->where('d.serial_end', '>=', $queryValue)
                ->orderByDesc('u.scan_date')->orderByDesc('d.id')->limit(100);
            if ($scopes = $this->suryaTapScopes()) $scansQuery->whereIn('d.id_outlet', DB::table('appsdumais')->select('id_outlet')->whereIn('tap', $scopes));
            $scans = $scansQuery->leftJoin('idsf as digipos_sales_force', 'digipos_sales_force.digipos_id', '=', 'd.sf')
                ->leftJoin('appsdumais as digipos_outlet', 'digipos_outlet.id_outlet', '=', 'd.id_outlet')
                ->get(['d.*', 'u.filename', 'u.scan_date', 'digipos_sales_force.namasf', 'digipos_outlet.nama_outlet', 'digipos_outlet.kecamatan']);
            if (Schema::hasTable('mobile_sales_serials')) {
                $mobileQuery = DB::table('mobile_sales_serials as serial')->join('mobile_penjualan as sale', 'sale.id', '=', 'serial.mobile_penjualan_id')
                    ->where(function ($query) use ($queryValue) {
                        $query->where(function ($sub) use ($queryValue) { $sub->where('serial.identifier_type', 'sn')->where('serial.serial_start', '<=', $queryValue)->where('serial.serial_end', '>=', $queryValue); })
                            ->orWhere(function ($sub) use ($queryValue) { $sub->where('serial.identifier_type', 'number')->where('serial.serial_start', $queryValue); });
                    })->whereNotIn('sale.status', ['draft_wa', 'rejected'])->orderByDesc('sale.tgl')->limit(50);
                if ($scopes = $this->suryaTapScopes()) $mobileQuery->whereIn('sale.id_outlet', DB::table('appsdumais')->select('id_outlet')->whereIn('tap', $scopes));
                $mobile = $mobileQuery->leftJoin('idsf as sales_force', 'sales_force.idsf', '=', 'sale.idsf')
                    ->leftJoin('appsdumais as mobile_outlet', 'mobile_outlet.id_outlet', '=', 'sale.id_outlet')
                    ->leftJoin('denom as mobile_denom', 'mobile_denom.iddenom', '=', 'serial.iddenom')
                    ->get(['serial.*', 'sale.tgl', 'sale.id_outlet', 'sale.idsf', 'sale.status', 'sale.qty', 'sales_force.namasf', 'mobile_outlet.nama_outlet', 'mobile_outlet.kecamatan', 'mobile_denom.denom as denom_name']);
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
}
