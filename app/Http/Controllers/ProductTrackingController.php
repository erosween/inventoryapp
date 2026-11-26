<?php

namespace App\Http\Controllers;

use App\Services\ProductTrackingFile;
use App\Services\SalesStockSerialConflict;
use App\Support\DenomGrouping;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ProductTrackingController extends MonitaDumaiController
{
    private array $sfStockOwnershipHistoryCache = [];

    public function index(Request $request)
    {
        return redirect()->route('surya.product-tracking.sold', $request->query());
    }

    private function soldValidationCacheKey(array $filters): string
    {
        return 'product-tracking:sold-outlet-validation:v2:'.sha1(json_encode([
            $filters['date'] ?? null,
            $filters['tab'] ?? null,
            $filters['tap'] ?? null,
            $filters['sf'] ?? null,
            $filters['iddenom'] ?? null,
            $filters['identifier_type'] ?? null,
            $this->suryaTapScopes(),
        ]));
    }

    private function buildSoldValidationOutletRows(array $filters): \Illuminate\Support\Collection
    {
        $sourceQuery = $this->soldValidationSource($filters);
        // Load the small daily scan set once and match serial intervals in memory.
        // The former correlated range query ran once per sale row and repeatedly
        // cast both sides of the scan indexes, causing the 504 on busy dates.
        $canonicalSerial = static fn ($serial): string => strlen((string) $serial) === 16 && str_starts_with((string) $serial, '0')
            ? substr((string) $serial, 1) : (string) $serial;
        $scanRanges = DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
            ->whereRaw("CONVERT(upload.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'scan' COLLATE utf8mb4_unicode_ci")
            ->whereRaw("CONVERT(upload.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
            ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])
            ->get(['digipos.serial_start', 'digipos.serial_end'])
            ->map(fn ($scan) => (object) [
                'serial_start' => $canonicalSerial($scan->serial_start),
                'serial_end' => $canonicalSerial($scan->serial_end),
            ])
            ->sort(fn ($a, $b) => $this->compareSerials($a->serial_start, $b->serial_start)
                    ?: $this->compareSerials($a->serial_end, $b->serial_end))->values();
        $maxEnd = null;
        foreach ($scanRanges as $range) {
            if ($maxEnd === null || $this->compareSerials($range->serial_end, $maxEnd) > 0) $maxEnd = $range->serial_end;
            $range->prefix_end = $maxEnd;
        }
        $allCoverageRanges = $this->allCompleteDigiposCoverageRanges();
        $currentCoverageRanges = $this->mergeSerialRanges($scanRanges->map(fn ($range) => [
            'serial_start' => $range->serial_start,
            'serial_end' => $range->serial_end,
        ])->all());

        $allOutletRows = $sourceQuery->get()->groupBy('id_outlet')->map(function ($sales, $idOutlet) use ($scanRanges, $canonicalSerial, $allCoverageRanges, $currentCoverageRanges) {
            $first = $sales->first();
            $qty = 0;
            $matchedQty = 0;
            $mismatchAlreadyDigiposQty = 0;
            // DigiPOS and ST Nota may store different IDs for the same physical outlet.
            // Validate by SN range across the selected scan window, not by outlet ID.
            $scans = $scanRanges;
            foreach ($sales as $sale) {
                $saleQty = (int) $sale->qty;
                $qty += $saleQty;
                $matched = 0;
                $saleStart = $canonicalSerial($sale->serial_start);
                $saleEnd = $canonicalSerial($sale->serial_end);
                $low = 0;
                $high = count($scans);
                while ($low < $high) {
                    $middle = intdiv($low + $high, 2);
                    if ($this->compareSerials($scans[$middle]->prefix_end, $saleStart) < 0) $low = $middle + 1;
                    else $high = $middle;
                }
                for ($index = $low, $scanCount = count($scans); $index < $scanCount; $index++) {
                    $scan = $scans[$index];
                    if ($this->compareSerials($scan->serial_start, $saleEnd) > 0) break;
                    if ($this->compareSerials($scan->serial_end, $saleStart) < 0) continue;
                    $scanStart = $scan->serial_start;
                    $scanEnd = $scan->serial_end;
                    $overlapStart = $this->compareSerials($saleStart, $scanStart) >= 0 ? $saleStart : $scanStart;
                    $overlapEnd = $this->compareSerials($saleEnd, $scanEnd) <= 0 ? $saleEnd : $scanEnd;
                    $matched += (int) $this->serialRangeCount($overlapStart, $overlapEnd);
                }
                $matchedQty += min($saleQty, $matched);

                $coveredCurrent = min($saleQty, $this->serialCoverageCount($saleStart, $saleEnd, $currentCoverageRanges));
                $coveredAnyDate = min($saleQty, $this->serialCoverageCount($saleStart, $saleEnd, $allCoverageRanges));
                $saleMismatchQty = max($saleQty - min($saleQty, $matched), 0);
                $mismatchAlreadyDigiposQty += min($saleMismatchQty, max($coveredAnyDate - $coveredCurrent, 0));
            }

            $mismatchQty = max($qty - $matchedQty, 0);
            $mismatchAlreadyDigiposQty = min($mismatchQty, $mismatchAlreadyDigiposQty);

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
                'mismatch_qty' => $mismatchQty,
                'mismatch_digipos_qty' => $mismatchAlreadyDigiposQty,
                'mismatch_non_digipos_qty' => max($mismatchQty - $mismatchAlreadyDigiposQty, 0),
                'has_mobile_sale' => $sales->contains(fn ($sale) => $sale->source === 'SF Mobile'),
                'latest_input_at' => $sales->pluck('input_at')->filter()->sort()->last(),
            ];
        })->values();
        $outletIds = $allOutletRows->pluck('id_outlet')->filter()->unique()->values();
        $visitTimes = $outletIds->isEmpty() ? collect() : DB::table('surya_visits')
            ->whereIn('id_outlet', $outletIds)
            ->where('visited_at', '<', \Carbon\Carbon::parse($filters['date'])->addDay()->toDateString().' 00:00:00')
            ->selectRaw('id_outlet, MAX(visited_at) AS visit_at')
            ->groupBy('id_outlet')
            ->pluck('visit_at', 'id_outlet');
        $allOutletRows->each(function ($row) use ($filters, $visitTimes) {
            if ($row->has_mobile_sale) {
                $row->visit_at = $visitTimes->get($row->id_outlet);
                $row->visit_at_display = null;
                if ($row->visit_at) {
                    $visitAt = \Carbon\Carbon::parse($row->visit_at, 'Asia/Jakarta')->setTimezone('Asia/Jakarta');
                    $format = $visitAt->toDateString() === $filters['date'] ? 'H:i' : 'd M Y · H:i';
                    $row->visit_at_display = $visitAt->format($format).' WIB';
                }
                return;
            }

            $inputAt = $row->latest_input_at
                ? \Carbon\Carbon::parse($row->latest_input_at, 'Asia/Jakarta')->setTimezone('Asia/Jakarta')->format('H:i').' WIB'
                : '—';
            $row->visit_at = $row->latest_input_at;
            $row->visit_at_display = $inputAt;
        });

        return $allOutletRows;
    }

    public function soldSerials(Request $request)
    {
        $filters = $this->soldSerialFilters($request);
        $tableOptions = $this->soldTableOptions($request);
        $detailTableOptions = $tableOptions;
        $summaryTarget = $request->validate([
            'summary_level' => 'nullable|in:tap,sf,kecamatan',
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
        $validationRowsCacheKey = $this->soldValidationCacheKey($filters);
        $allOutletRows = Cache::remember(
            $validationRowsCacheKey,
            now()->addMinutes(5),
            fn () => $this->buildSoldValidationOutletRows($filters),
        );
        $outletRows = $allOutletRows;
        foreach (['columns', 'exclude'] as $mode) {
            $detailTableOptions[$mode] = array_filter($detailTableOptions[$mode] ?? [], fn ($values, $column) => ! in_array($column, ['sf','outlet_count','matched_outlets','mismatch_outlets'], true), ARRAY_FILTER_USE_BOTH);
            $tableOptions[$mode] = array_filter($tableOptions[$mode] ?? [], fn ($values, $column) => in_array($column, ['sf','outlet_count','matched_outlets','mismatch_outlets'], true), ARRAY_FILTER_USE_BOTH);
            foreach ($detailTableOptions[$mode] ?? [] as $column => $values) {
                $outletRows = $outletRows->filter(function ($row) use ($column, $values, $mode) {
                    $value = $column === 'visit_at'
                        ? (string) ($row->visit_at_display ?: '—')
                        : (string) ($row->{$column} ?? '');
                    if ($column === 'status') $value = $row->mismatch_qty === 0 ? 'Sesuai' : 'Perlu dicek';
                    $included = in_array($value, $values, true);
                    return $mode === 'columns' ? $included : ! $included;
                });
            }
        }
        if (! empty($summaryTarget['summary_level']) && isset($summaryTarget['summary_key'])) {
            $summaryField = match ($summaryTarget['summary_level']) {
                'tap' => 'tap', 'sf' => 'namasf', default => 'kecamatan',
            };
            $outletRows = $outletRows->filter(fn ($row) => $summaryTarget['summary_key'] === '__NULL__'
                ? trim((string) ($row->{$summaryField} ?? '')) === ''
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
        $summaryCacheKey = $validationRowsCacheKey.':grouped-summaries:v3';
        $baseSummaries = Cache::remember($summaryCacheKey, now()->addMinutes(5), function () use ($allOutletRows, $tapOrder) {
            $tapSummaries = $allOutletRows->whereIn('tap', $tapOrder)->groupBy('tap')->map(fn ($rows, $tap) => $this->summarizeSoldOutlets($rows, $tap, $tap))->sortBy(fn ($group) => array_search(strtoupper($group->key), $tapOrder, true))->values();
            $registeredSfNames = Cache::remember('product-tracking:registered-sf-names:v1', now()->addMinutes(5), fn () => DB::table('idsf')->get(['idsf', 'namasf'])
                ->filter(fn ($sf) => str_starts_with(strtoupper(trim((string) $sf->idsf)), 'SF'))
                ->pluck('namasf')->map(fn ($name) => mb_strtolower(trim((string) $name)))->flip());
            $sfSummaries = $allOutletRows->filter(fn ($row) => ! str_starts_with((string) $row->id_outlet, 'NON-DIGIPOS-') && $registeredSfNames->has(mb_strtolower(trim((string) $row->namasf))))
                ->groupBy('namasf')->map(function ($rows, $name) use ($tapOrder) {
                    $group = $this->summarizeSoldOutlets($rows, $name, $name);
                    $group->tap = $rows->pluck('tap')->unique()->sortBy(fn ($tap) => array_search(strtoupper((string) $tap), $tapOrder, true))->implode(', ') ?: 'Tanpa TAP';
                    return $group;
                })->sortBy(fn ($group) => [array_search(strtoupper(strtok($group->tap, ',')), $tapOrder, true), mb_strtolower($group->label)])->values();

            $clusterTaps = [
                'DUMAI BENGKALIS' => ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING'],
                'ROKAN HILIR' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
            ];
            $kecamatanSummaries = $allOutletRows
                ->reject(fn ($row) => str_starts_with((string) $row->id_outlet, 'NON-DIGIPOS-') && trim((string) ($row->kecamatan ?? '')) === '')
                ->groupBy(fn ($row) => trim((string) ($row->kecamatan ?? '')) ?: '__NULL__')
                ->map(function ($rows, $key) use ($clusterTaps) {
                    $group = $this->summarizeSoldOutlets($rows, (string) $key, $key === '__NULL__' ? 'Tanpa kecamatan' : (string) $key);
                    $tapNames = $rows->pluck('tap')->map(fn ($tap) => strtoupper(trim((string) $tap)))->unique();
                    $clusters = collect($clusterTaps)->filter(fn ($taps) => $tapNames->intersect($taps)->isNotEmpty())->keys();
                    $group->cluster = $clusters->count() > 1 ? 'CAMPURAN' : ($clusters->first() ?? 'LAINNYA');
                    return $group;
                })
                ->sortBy(fn ($group) => [array_search($group->cluster, ['DUMAI BENGKALIS', 'ROKAN HILIR', 'CAMPURAN', 'LAINNYA'], true), mb_strtolower($group->label)])
                ->values();

            return ['tap' => $tapSummaries, 'sf' => $sfSummaries, 'kecamatan' => $kecamatanSummaries];
        });
        $tapSummaries = $baseSummaries['tap'];
        $sfSummaries = $baseSummaries['sf'];
        $kecamatanSummaries = $baseSummaries['kecamatan'];

        $tapSummaries = $this->filterSoldSummaries($tapSummaries, 'tap', $tableOptions);
        $sfSummaries = $this->filterSoldSummaries($sfSummaries, 'sf', $tableOptions);
        $kecamatanSummaries = $this->filterSoldSummaries($kecamatanSummaries, 'kecamatan', $tableOptions);

        $clusterSummaries = collect([
            'DUMAI BENGKALIS' => ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING'],
            'ROKAN HILIR' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
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
                'mismatch_digipos_qty' => (int) $groups->sum('mismatch_digipos_qty'),
                'mismatch_non_digipos_qty' => (int) $groups->sum('mismatch_non_digipos_qty'),
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
            'mismatch_digipos_qty' => (int) $clusterSummaries->sum('mismatch_digipos_qty'),
            'mismatch_non_digipos_qty' => (int) $clusterSummaries->sum('mismatch_non_digipos_qty'),
        ]);

        $totals = $allOutletRows->reduce(function ($totals, $row) {
            $totals->outlet_count++;
            $totals->matched_outlets += $row->mismatch_qty === 0 ? 1 : 0;
            $totals->mismatch_outlets += $row->mismatch_qty > 0 ? 1 : 0;
            $totals->serial_count += (int) $row->qty;
            $totals->matched_qty += (int) $row->matched_qty;
            $totals->mismatch_qty += (int) $row->mismatch_qty;
            $totals->mismatch_digipos_qty += (int) $row->mismatch_digipos_qty;
            $totals->mismatch_non_digipos_qty += (int) $row->mismatch_non_digipos_qty;
            return $totals;
        }, (object) ['outlet_count' => 0, 'matched_outlets' => 0, 'mismatch_outlets' => 0, 'serial_count' => 0, 'matched_qty' => 0, 'mismatch_qty' => 0, 'mismatch_digipos_qty' => 0, 'mismatch_non_digipos_qty' => 0]);
        $summary = (object) [
            'outlet_count' => (int) ($totals->outlet_count ?? 0),
            'matched_outlets' => (int) ($totals->matched_outlets ?? 0),
            'mismatch_outlets' => (int) ($totals->mismatch_outlets ?? 0),
            'serial_count' => (int) ($totals->serial_count ?? 0),
            'matched_qty' => (int) ($totals->matched_qty ?? 0),
            'mismatch_qty' => (int) ($totals->mismatch_qty ?? 0),
            'mismatch_digipos_qty' => (int) ($totals->mismatch_digipos_qty ?? 0),
            'mismatch_non_digipos_qty' => (int) ($totals->mismatch_non_digipos_qty ?? 0),
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
            'kecamatanSummaries' => $kecamatanSummaries,
            'taps' => Cache::remember('product-tracking:sold-taps:v1:'.sha1(json_encode($this->suryaTapScopes())), now()->addMinutes(5), fn () => DB::table('idsf')->when($this->suryaTapScopes(), fn ($query, $scopes) => $this->applyTapScopeFilter($query, 'idtap', $scopes))->distinct()->orderBy('idtap')->pluck('idtap')),
            'sfs' => Cache::remember($validationRowsCacheKey.':sf-options', now()->addMinutes(5), fn () => $this->soldValidationSource($filters, false)->select('idsf', 'namasf')->distinct()->orderBy('namasf')->get()),
            'denoms' => Cache::remember($validationRowsCacheKey.':denom-options', now()->addMinutes(5), fn () => $this->soldValidationSource($filters, false)->select('iddenom', 'denom_label as denom')->distinct()->orderBy('denom_label')->get()),
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
        $allCoverageRanges = $matchType === 'mismatch' ? $this->allCompleteDigiposCoverageRanges() : [];
        $scanRanges = DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
            ->whereRaw("CONVERT(upload.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'scan' COLLATE utf8mb4_unicode_ci")
            ->whereRaw("CONVERT(upload.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
            ->whereBetween('digipos.scan_date', [$filters['date_from'], $filters['date_to']])
            ->orderBy('digipos.serial_start')
            ->get(['digipos.serial_start', 'digipos.serial_end']);
        $rows = $this->soldValidationSource($filters)
            ->where('id_outlet', $data['id_outlet'])
            ->orderBy('denom_label')->orderBy('serial_start')
            ->offset(($page - 1) * 50)->limit(51)
            ->select('serial_start', 'serial_end', 'qty', 'iddenom', 'denom_label as denom', 'source')
            ->get();

        $displayRows = $rows->take(50)->flatMap(function ($row) use ($scanRanges, $matchType, $allCoverageRanges) {
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
                        $matched[] = ['start' => $matchedStart, 'end' => $matchedEnd, 'qty' => (int) $this->serialRangeCount($matchedStart, $matchedEnd), 'denom' => $row->denom ?: $row->iddenom, 'source' => $row->source, 'coverage' => 'ST'];
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
            return collect($unmatched)->flatMap(function ($range) use ($allCoverageRanges) {
                return array_map(fn ($piece) => [
                    'start' => $piece['start'],
                    'end' => $piece['end'],
                    'qty' => (int) $this->serialRangeCount($piece['start'], $piece['end']),
                    'denom' => $range['denom'],
                    'source' => $range['source'],
                    'coverage' => $piece['in_digipos'] ? 'ST' : 'Non ST',
                ], $this->splitSerialRangeByCoverage($range['start'], $range['end'], $allCoverageRanges));
            })->values();
        })->values();

        $groupedRows = collect();
        foreach ($displayRows as $displayRow) {
            $lastIndex = $groupedRows->count() - 1;
            if ($lastIndex >= 0) {
                $last = $groupedRows[$lastIndex];
                $nextSerial = $this->incrementSerial((string) $last['end']);
                $sameGroup = $last['denom'] === $displayRow['denom']
                    && $last['coverage'] === $displayRow['coverage']
                    && $this->compareSerials((string) $displayRow['start'], $nextSerial) <= 0;

                if ($sameGroup) {
                    if ($this->compareSerials((string) $displayRow['end'], (string) $last['end']) > 0) {
                        $last['end'] = $displayRow['end'];
                    }
                    $last['qty'] = (int) $this->serialRangeCount((string) $last['start'], (string) $last['end']);
                    $groupedRows[$lastIndex] = $last;
                    continue;
                }
            }

            $groupedRows->push($displayRow);
        }

        return response()->json([
            'rows' => $groupedRows->values(),
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

        $digiposQuery = DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
            ->leftJoin('appsdumais as scan_outlet', 'scan_outlet.id_outlet', '=', 'digipos.id_outlet')
            ->whereRaw("CONVERT(upload.kind USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'scan' COLLATE utf8mb4_unicode_ci")
            ->whereRaw("CONVERT(upload.status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'complete' COLLATE utf8mb4_unicode_ci")
            ->whereRaw('CAST(digipos.serial_start AS DECIMAL(24,0)) <= CAST(? AS DECIMAL(24,0))', [$sn])
            ->whereRaw('CAST(digipos.serial_end AS DECIMAL(24,0)) >= CAST(? AS DECIMAL(24,0))', [$sn])
            ->orderByDesc('digipos.scan_date')->limit(20);
        // A Digipos scan can be recorded on another day and under its own outlet ID.
        // Match by SN across scan history, while keeping the user's TAP access scope.
        if ($scopes = $this->suryaTapScopes()) {
            $outletScope = DB::table('appsdumais')->select('id_outlet');
            $this->applyTapScopeFilter($outletScope, 'tap', $scopes);
            $digiposQuery->whereIn('digipos.id_outlet', $outletScope);
        }
        $digipos = $digiposQuery->get([
            'digipos.scan_date', 'digipos.id_outlet', 'scan_outlet.nama_outlet',
            'digipos.serial_start', 'digipos.serial_end', 'digipos.product_name',
        ]);

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

    public function soldOutletRangeDigipos(Request $request)
    {
        $filters = $this->soldSerialFilters($request);
        $data = $request->validate([
            'id_outlet' => 'required|string|max:50',
            'start' => ['required', 'regex:/^\\d{9,24}$/'],
            'end' => ['required', 'regex:/^\\d{9,24}$/'],
        ]);
        abort_unless($this->soldValidationSource($filters)->where('id_outlet', $data['id_outlet'])->exists(), 404);
        $start = (string) $data['start'];
        $end = (string) $data['end'];
        abort_if($this->compareSerials($start, $end) > 0, 422, 'Rentang SN tidak valid.');

        $query = DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
            ->leftJoin('appsdumais as scan_outlet', 'scan_outlet.id_outlet', '=', 'digipos.id_outlet')
            ->where('upload.kind', 'scan')->where('upload.status', 'complete')
            ->whereRaw('CAST(digipos.serial_start AS DECIMAL(24,0)) <= CAST(? AS DECIMAL(24,0))', [$end])
            ->whereRaw('CAST(digipos.serial_end AS DECIMAL(24,0)) >= CAST(? AS DECIMAL(24,0))', [$start]);
        if ($scopes = $this->suryaTapScopes()) {
            $outletScope = DB::table('appsdumais')->select('id_outlet');
            $this->applyTapScopeFilter($outletScope, 'tap', $scopes);
            $query->whereIn('digipos.id_outlet', $outletScope);
        }
        $rows = $query->orderByDesc('digipos.scan_date')->orderBy('digipos.id_outlet')->orderBy('digipos.product_name')->orderBy('digipos.serial_start')
            ->get([
                'digipos.scan_date', 'digipos.id_outlet', 'scan_outlet.nama_outlet',
                'digipos.serial_start', 'digipos.serial_end', 'digipos.product_name',
            ]);

        $matches = $rows->map(function ($row) use ($start, $end) {
            $overlapStart = $this->compareSerials($start, (string) $row->serial_start) >= 0 ? $start : (string) $row->serial_start;
            $overlapEnd = $this->compareSerials($end, (string) $row->serial_end) <= 0 ? $end : (string) $row->serial_end;

            return [
                'date' => $row->scan_date,
                'id_outlet' => $row->id_outlet,
                'outlet' => $row->nama_outlet ?: 'Nama outlet tidak tersedia',
                'product' => $row->product_name ?: '—',
                'start' => $row->serial_start,
                'end' => $row->serial_end,
                'overlap_start' => $overlapStart,
                'overlap_end' => $overlapEnd,
                'qty' => (int) $this->serialRangeCount($overlapStart, $overlapEnd),
            ];
        })->values();

        $groupedMatches = collect();
        foreach ($matches as $match) {
            $lastIndex = $groupedMatches->count() - 1;
            if ($lastIndex >= 0) {
                $last = $groupedMatches[$lastIndex];
                $nextSerial = $this->incrementSerial((string) $last['overlap_end']);
                $sameGroup = $last['date'] === $match['date']
                    && $last['id_outlet'] === $match['id_outlet']
                    && $last['product'] === $match['product']
                    && $this->compareSerials((string) $match['overlap_start'], $nextSerial) <= 0;

                if ($sameGroup) {
                    if ($this->compareSerials((string) $match['overlap_end'], (string) $last['overlap_end']) > 0) {
                        $last['overlap_end'] = $match['overlap_end'];
                    }
                    $last['qty'] = (int) $this->serialRangeCount((string) $last['overlap_start'], (string) $last['overlap_end']);
                    $groupedMatches[$lastIndex] = $last;
                    continue;
                }
            }

            $groupedMatches->push($match);
        }

        return response()->json(['rows' => $groupedMatches->values(), 'has_more' => false]);
    }

    public function soldColumnValues(Request $request)
    {
        $filters = $this->soldSerialFilters($request);
        $request->validate(['column' => 'required|in:visit_at,nama_outlet,id_outlet,kecamatan,tap,namasf,kategori_outlet,qty,matched_qty,mismatch_qty,status,sf,outlet_count,matched_outlets,mismatch_outlets', 'summary_table' => 'nullable|in:tap,sf,kecamatan', 'q' => 'nullable|string|max:150', 'value_page' => 'nullable|integer|min:1|max:10000']);
        $column = $request->input('column');
        $summaryTableLevel = $request->input('summary_table', 'tap');
        if ($request->filled('summary_table')) {
            abort_unless(in_array($column, [$summaryTableLevel, 'outlet_count', 'matched_outlets', 'mismatch_outlets', 'qty', 'matched_qty', 'mismatch_qty'], true) || ($summaryTableLevel === 'sf' && $column === 'tap'), 422, 'Kolom ringkasan tidak valid.');
        }
        $validationRowsCacheKey = $this->soldValidationCacheKey($filters);
        $cachedOutletRows = Cache::remember(
            $validationRowsCacheKey,
            now()->addMinutes(5),
            fn () => $this->buildSoldValidationOutletRows($filters),
        );
        if ($cachedOutletRows instanceof \Illuminate\Support\Collection) {
            $valueSource = $cachedOutletRows;
            $summaryGroupsBuilt = false;
            if ($request->filled('summary_table')) {
                if ($summaryTableLevel === 'sf' && $column !== 'tap') {
                    $registeredSfNames = Cache::remember('product-tracking:registered-sf-names:v1', now()->addMinutes(5), fn () => DB::table('idsf')->get(['idsf', 'namasf'])
                        ->filter(fn ($sf) => str_starts_with(strtoupper(trim((string) $sf->idsf)), 'SF'))
                        ->pluck('namasf')->map(fn ($name) => mb_strtolower(trim((string) $name)))->flip());
                    $valueSource = $valueSource
                        ->filter(fn ($row) => ! str_starts_with((string) $row->id_outlet, 'NON-DIGIPOS-') && $registeredSfNames->has(mb_strtolower(trim((string) $row->namasf))))
                        ->groupBy('namasf')
                        ->map(function ($rows, $name) {
                            $group = $this->summarizeSoldOutlets($rows, (string) $name, (string) $name);
                            $group->tap = $rows->pluck('tap')->unique()->implode(', ') ?: 'Tanpa TAP';
                            return $group;
                        });
                    $summaryGroupsBuilt = true;
                } else {
                    if (! ($summaryTableLevel === 'sf' && $column === 'tap')) {
                        $summaryField = match ($summaryTableLevel) { 'tap' => 'tap', 'sf' => 'namasf', default => 'kecamatan' };
                        $valueSource = $valueSource->groupBy(fn ($row) => trim((string) ($row->{$summaryField} ?? '')) ?: '__NULL__')
                            ->map(fn ($rows, $key) => $this->summarizeSoldOutlets($rows, (string) $key, $key === '__NULL__' ? 'Tanpa kecamatan' : (string) $key));
                        $summaryGroupsBuilt = true;
                    }
                }
                if ($summaryGroupsBuilt) {
                    $valueSource = $valueSource->map(fn ($group) => (object) array_merge((array) $group, [
                        'summary_key' => $group->key,
                        'label' => $group->label,
                    ]));
                }
            }

            $values = $valueSource->map(function ($row) use ($column, $request, $summaryTableLevel) {
                if ($request->filled('summary_table') && $column === $summaryTableLevel) {
                    return $summaryTableLevel === 'tap' ? (string) $row->summary_key : (string) $row->label;
                }
                if ($column === 'status') return $row->mismatch_qty === 0 ? 'Sesuai' : 'Perlu dicek';
                if ($column === 'nama_outlet') return $row->nama_outlet ?: 'Nama outlet tidak tersedia';
                if ($column === 'visit_at') return $row->visit_at_display ?: '—';
                if ($column === 'namasf') return $row->namasf ?: '—';
                if ($column === 'kategori_outlet') return $row->kategori_outlet ?: 'Reguler';
                return (string) ($row->{$column} ?? '');
            })->filter(fn ($value) => ! $request->filled('q') || mb_stripos((string) $value, (string) $request->input('q')) !== false)
                ->unique(fn ($value) => mb_strtolower((string) $value));
            $numericColumns = ['outlet_count', 'matched_outlets', 'mismatch_outlets', 'qty', 'matched_qty', 'mismatch_qty'];
            $values = in_array($column, $numericColumns, true)
                ? $values->sortBy(fn ($value) => (float) $value)->values()
                : $values->sortBy(fn ($value) => mb_strtolower((string) $value), SORT_STRING)->values();
            $offset = (max(1, (int) $request->input('value_page', 1)) - 1) * 100;
            $pageValues = $values->slice($offset, 101)->values();

            return response()->json([
                'values' => $pageValues->take(100)->map(fn ($value) => (string) $value)->values(),
                'has_more' => $pageValues->count() > 100,
            ]);
        }
        if ($request->filled('summary_table')) {
            if ($summaryTableLevel === 'sf' && $column === 'tap') {
                $values = DB::query()->fromSub($this->soldOutletRows($filters), 'outlet_rows')
                    ->select('outlet_rows.tap as value')->distinct()
                    ->when($request->filled('q'), fn ($query) => $query->where('outlet_rows.tap', 'like', '%'.$request->input('q').'%'))
                    ->orderBy('value')->offset((max(1, (int) $request->input('value_page', 1)) - 1) * 100)->limit(101)->pluck('value');

                return response()->json(['values' => $values->take(100)->map(fn ($value) => (string) $value)->values(), 'has_more' => $values->count() > 100]);
            }
            $rows = $this->soldSummaryRows($filters, $summaryTableLevel);
            $summaryExpression = $column === $summaryTableLevel
                ? '`outlet_rows`.`label`'
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
            'sort' => 'nullable|in:visit_at,nama_outlet,id_outlet,kecamatan,tap,namasf,kategori_outlet,qty,matched_qty,mismatch_qty,status,sf,outlet_count,matched_outlets,mismatch_outlets',
            'direction' => 'nullable|in:asc,desc',
            'summary_tap_columns' => 'nullable|array|max:8', 'summary_tap_columns.*' => 'array|min:1|max:100', 'summary_tap_columns.*.*' => 'nullable|string|max:5000',
            'summary_tap_exclude' => 'nullable|array|max:8', 'summary_tap_exclude.*' => 'array|min:1|max:100', 'summary_tap_exclude.*.*' => 'nullable|string|max:5000',
            'summary_sf_columns' => 'nullable|array|max:8', 'summary_sf_columns.*' => 'array|min:1|max:100', 'summary_sf_columns.*.*' => 'nullable|string|max:5000',
            'summary_sf_exclude' => 'nullable|array|max:8', 'summary_sf_exclude.*' => 'array|min:1|max:100', 'summary_sf_exclude.*.*' => 'nullable|string|max:5000',
            'summary_kecamatan_columns' => 'nullable|array|max:8', 'summary_kecamatan_columns.*' => 'array|min:1|max:100', 'summary_kecamatan_columns.*.*' => 'nullable|string|max:5000',
            'summary_kecamatan_exclude' => 'nullable|array|max:8', 'summary_kecamatan_exclude.*' => 'array|min:1|max:100', 'summary_kecamatan_exclude.*.*' => 'nullable|string|max:5000',
            'summary_sort' => 'nullable|in:tap,sf,kecamatan,outlet_count,matched_outlets,mismatch_outlets,qty,matched_qty,mismatch_qty',
            'summary_direction' => 'nullable|in:asc,desc', 'summary_table' => 'nullable|in:tap,sf,kecamatan',
        ]);
        foreach (['columns', 'exclude'] as $mode) {
            foreach ($data[$mode] ?? [] as $column => $values) {
                if (! in_array($column, ['visit_at','nama_outlet','id_outlet','kecamatan','tap','namasf','kategori_outlet','qty','matched_qty','mismatch_qty','status','sf','outlet_count','matched_outlets','mismatch_outlets'], true)) {
                    throw ValidationException::withMessages(['column' => 'Kolom filter tidak valid.']);
                }
                $data[$mode][$column] = array_map(fn ($value) => (string) $value, $values);
            }
        }
        $data['sort'] ??= 'nama_outlet';
        $data['direction'] ??= 'asc';
        foreach (['summary_tap_columns', 'summary_tap_exclude', 'summary_sf_columns', 'summary_sf_exclude', 'summary_kecamatan_columns', 'summary_kecamatan_exclude'] as $mode) {
            foreach ($data[$mode] ?? [] as $column => $values) {
                if (! in_array($column, ['tap','sf','kecamatan','outlet_count','matched_outlets','mismatch_outlets','qty','matched_qty','mismatch_qty'], true)) {
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
                    $groups = $groups->filter(fn ($group) => in_array((string) ($level === 'tap' ? $group->key : $group->label), $values, true));
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
            $groups = $groups->sortBy(fn ($group) => $column === $level ? ($level === 'tap' ? $group->key : $group->label) : ($group->{$column} ?? ''), SORT_NATURAL | SORT_FLAG_CASE, $options['summary_direction'] === 'desc')->values();
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
            'mismatch_digipos_qty' => (int) $rows->sum('mismatch_digipos_qty'),
            'mismatch_non_digipos_qty' => (int) $rows->sum('mismatch_non_digipos_qty'),
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
        $key = match ($level) { 'tap' => 'tap', 'sf' => 'namasf', default => 'kecamatan' };
        $label = $level === 'kecamatan' ? "COALESCE(NULLIF(TRIM(kecamatan), ''), 'Tanpa kecamatan')" : $key;

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
        $bulkInputTimes = DB::table('logs')
            ->where('action', 'INSERT')
            ->where('module', 'Stok Keluar SF (Bulk)')
            ->select('record_id')
            ->selectRaw('MAX(created_at) AS input_at')
            ->groupBy('record_id');
        $mobile = $this->mobileSerialsQuery($filters, false)
            ->whereRaw("CONVERT(serial.identifier_type USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'sn' COLLATE utf8mb4_unicode_ci")
            ->leftJoinSub(clone $latestNonDigiposVisit, 'latest_non_digipos_visit', fn ($join) => $join->on('latest_non_digipos_visit.id_outlet', '=', 'sale.id_outlet'))
            ->leftJoin('surya_visits as non_digipos_visit', 'non_digipos_visit.id', '=', 'latest_non_digipos_visit.id');
        $mobile->select([
            'serial.id as source_id', 'serial.iddenom', 'serial.serial_start', 'serial.serial_end', 'serial.qty',
            'sale.tgl as sale_date', 'sale.created_at as input_at', 'sale.id_outlet', 'sale.idsf',
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
            ->leftJoinSub(clone $bulkInputTimes, 'bulk_input', fn ($join) => $join->on('bulk_input.record_id', '=', 'form_sale.idkeluar'))
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
                'form_sale.qty', 'form_sale.tgl as sale_date', 'bulk_input.input_at', 'form_sale.id_outlet', 'form_sale.idsf',
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

        $uploads = $kind === 'sales-stock'
            ? DB::table('product_tracking_sales_stock_uploads')->orderByDesc('id')->simplePaginate(10, ['*'], 'upload_page')
            : $this->applyTextIn(DB::table('product_tracking_uploads'), 'kind', [$kind])
                ->whereRaw("CONVERT(status USING utf8mb4) COLLATE utf8mb4_unicode_ci IN (_utf8mb4'complete' COLLATE utf8mb4_unicode_ci, _utf8mb4'duplicate' COLLATE utf8mb4_unicode_ci)")
                ->orderByDesc('id')->simplePaginate(10, ['*'], 'upload_page');
        $uploads->appends($request->except('upload_page'));
        if (str_starts_with($kind, 'so-') && $uploads->isNotEmpty()) {
            $dateSummaries = DB::table('product_tracking_digipos')
                ->whereIn('upload_id', $uploads->pluck('id'))
                ->selectRaw('upload_id, MIN(sold_at) AS first_date, MAX(sold_at) AS last_date, COUNT(DISTINCT sold_at) AS date_count')
                ->groupBy('upload_id')->get()->keyBy('upload_id');
            foreach ($uploads as $item) {
                $dates = $dateSummaries->get($item->id);
                if (! $dates || ! $dates->first_date) continue;
                $first = \Carbon\Carbon::parse($dates->first_date)->format('d/m/Y');
                $last = \Carbon\Carbon::parse($dates->last_date)->format('d/m/Y');
                $item->scan_date = (int) $dates->date_count > 1 ? "Beragam · {$first}–{$last}" : $first;
            }
        }

        return view('surya.product-tracking.imports', [
            'kind' => $kind,
            'period' => $period,
            'uploads' => $uploads,
            'pending' => $this->applyTextEquals(DB::table('product_tracking_uploads'), 'kind', $kind)->whereRaw("CONVERT(status USING utf8mb4) COLLATE utf8mb4_unicode_ci = _utf8mb4'mapping' COLLATE utf8mb4_unicode_ci")->orderByDesc('id')->get(),
            'dailyTotals' => $dailyTotals,
        ]);
    }

    public function stockOutlet(Request $request)
    {
        $startedAt = microtime(true);
        if (! $request->boolean('_single_product')) {
            return $this->combinedStockOutlet($request);
        }
        $product = strtoupper((string) $request->query('product', 'PV'));
        abort_unless(in_array($product, ['PV', 'SA'], true), 404);
        $stockColumns = $this->stockOutletColumnMap();
        $options = $this->stockOutletTableOptions($request, array_keys($stockColumns));
        $hasOutletCategory = Schema::hasColumn('appsdumais', 'kategori_outlet');
        $outletCategory = $hasOutletCategory
            ? DB::raw('outlet.kategori_outlet AS kategori_outlet')
            : DB::raw("'Reguler' AS kategori_outlet");
        $soKind = 'so-'.strtolower($product);
        abort_unless(Schema::hasTable('mobile_sales_serials') && Schema::hasColumn('keluarsf', 'sn_awal') && Schema::hasColumn('keluarsf', 'sn_akhir'), 503, 'Sumber SN ST Nota belum tersedia. Jalankan migrasi terbaru terlebih dahulu.');
        $serialLengths = $product === 'PV' ? [12] : [15, 16];
        $applySerialLengths = static function ($query, string $column) use ($serialLengths): void {
            $query->where(function ($lengthQuery) use ($column, $serialLengths) {
                foreach ($serialLengths as $length) {
                    $lengthQuery->orWhere(function ($rangeQuery) use ($column, $length) {
                        $rangeQuery->whereBetween($column, [str_repeat('0', $length), str_repeat('9', $length)])
                            ->whereRaw("CHAR_LENGTH({$column}) = ?", [$length]);
                    });
                }
            });
        };
        $mobileSt = DB::table('mobile_sales_serials as serial')
            ->join('mobile_penjualan as sale', 'sale.id', '=', 'serial.mobile_penjualan_id')
            ->where('serial.identifier_type', 'sn')
            ->whereNotIn('sale.status', ['draft_wa', 'rejected'])
            ->whereNotNull('sale.id_outlet')
            ->selectRaw("sale.id_outlet, COALESCE(NULLIF(sale.owner_idsf, ''), sale.idsf) AS owner_sf, serial.iddenom, serial.serial_start, serial.serial_end, serial.qty AS serial_count, sale.tgl AS st_date");
        $applySerialLengths($mobileSt, 'serial.serial_start');
        $webSt = DB::table('keluarsf as form_sale')
            ->whereNotNull('form_sale.id_outlet')->where('form_sale.id_outlet', '<>', '')
            ->whereRaw("COALESCE(form_sale.tambahanket, '') NOT LIKE 'Mobile Approval ID: %'")
            ->where(function ($query) { $query->whereNull('form_sale.identifier_type')->orWhere('form_sale.identifier_type', 'sn'); })
            ->where(function ($query) { $query->whereNull('form_sale.serial_mode')->orWhere('form_sale.serial_mode', '<>', 'broken'); })
            ->whereNotNull('form_sale.sn_awal')->where('form_sale.sn_awal', '<>', '')
            ->whereRaw("(form_sale.sn_akhir IS NULL OR TRIM(CONVERT(form_sale.sn_akhir USING utf8mb4) COLLATE utf8mb4_unicode_ci) = _utf8mb4'' COLLATE utf8mb4_unicode_ci OR CONVERT(form_sale.sn_akhir USING utf8mb4) COLLATE utf8mb4_unicode_ci >= CONVERT(form_sale.sn_awal USING utf8mb4) COLLATE utf8mb4_unicode_ci)")
            ->selectRaw("form_sale.id_outlet, form_sale.idsf AS owner_sf, form_sale.iddenom, form_sale.sn_awal AS serial_start, COALESCE(NULLIF(form_sale.sn_akhir, ''), form_sale.sn_awal) AS serial_end, form_sale.qty AS serial_count, form_sale.tgl AS st_date");
        $applySerialLengths($webSt, 'form_sale.sn_awal');
        $soBase = DB::table('product_tracking_digipos as so')
            ->join('product_tracking_uploads as u', 'u.id', '=', 'so.upload_id')
            ->where('so.kind', $soKind)
            ->where('u.kind', $soKind)
            ->where('u.status', 'complete')
            ->select('so.serial_start', 'so.serial_end', 'so.sold_at');
        $applySerialLengths($soBase, 'so.serial_start');
        // Find eligible outlets from the ST sources in one pass. Correlated EXISTS
        // checks made the list query re-scan serial history for every outlet row.
        $stOutletIds = (clone $mobileSt)->select('sale.id_outlet')
            ->union((clone $webSt)->select('form_sale.id_outlet'));
        // Keep the UNION as a derived table and join it once. MariaDB rewrites
        // WHERE IN (UNION ...) as a dependent subquery and rechecks the serial
        // sources for every outlet (20k+ rows), even when the SN index is used.
        $outletQuery = DB::table('appsdumais as outlet')
            ->joinSub($stOutletIds, 'eligible_outlets', function ($join) {
                $join->on('eligible_outlets.id_outlet', '=', 'outlet.id_outlet');
            })
            ->select('outlet.id_outlet', 'outlet.nama_outlet', 'outlet.kecamatan', 'outlet.tap', 'outlet.sf')
            ->selectRaw("CASE WHEN UPPER(TRIM(outlet.tap)) IN ('DUMAI','BENGKALIS','DURI','RUPAT','SEI PAKNING') THEN 'DUMAI BENGKALIS' WHEN UPPER(TRIM(outlet.tap)) IN ('BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG') THEN 'ROKAN HILIR' ELSE '—' END AS cluster")
            ->addSelect($outletCategory);
        if ($scopes = $this->suryaTapScopes()) $this->applyTapScopeFilter($outletQuery, 'outlet.tap', $scopes);
        // Compute metrics across the full outlet set before sorting/filtering so
        // controls on calculated columns work globally, not just on the visible page.
        $cacheKey = $this->stockOutletReportCacheKey($product);
        $cachedRows = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($outletQuery, $mobileSt, $webSt, $soBase, $product) {
            $outletRows = $outletQuery->get();
            if ($outletRows->isEmpty()) return [];
            $eligibleOutletIds = $outletRows->pluck('id_outlet')->map(fn ($id) => (string) $id)->all();
            $scopedMobileSt = (clone $mobileSt)->whereIn('sale.id_outlet', $eligibleOutletIds);
            $scopedWebSt = (clone $webSt)->whereIn('form_sale.id_outlet', $eligibleOutletIds);
            $stSources = $scopedMobileSt->unionAll($scopedWebSt);
            $stRangesQuery = DB::query()->fromSub($stSources, 'source')->select([
                'source.id_outlet', 'source.owner_sf', 'source.iddenom', 'source.serial_start',
                'source.serial_end', 'source.serial_count', 'source.st_date',
            ]);

            // Keep current stock ranges in memory; stream SO candidates from MariaDB.
            $canonical = static fn ($serial): string => strlen((string) $serial) === 16 && str_starts_with((string) $serial, '0')
                ? substr((string) $serial, 1) : (string) $serial;
            $stRanges = (clone $stRangesQuery)->get();
            $metrics = [];
            $rangesByLength = [];
            foreach ($stRanges as $stRange) {
                $outletId = (string) $stRange->id_outlet;
                $metrics[$outletId] ??= ['st_qty' => 0, 'redeemed_qty' => 0, 'timed_qty' => 0, 'days_weighted' => 0];
                $metrics[$outletId]['st_qty'] += (int) $stRange->serial_count;
                $start = $canonical($stRange->serial_start);
                $end = $canonical($stRange->serial_end);
                if (strlen($start) !== strlen($end) || ! ctype_digit($start) || ! ctype_digit($end)) continue;
                $length = strlen($start);
                $rangesByLength[$length][] = ['range' => $stRange, 'start' => (int) $start, 'end' => (int) $end,
                    'length' => $length, 'start_text' => $start, 'end_text' => $end];
            }
            foreach ($rangesByLength as &$ranges) {
                usort($ranges, fn ($a, $b) => $a['start'] <=> $b['start'] ?: $a['end'] <=> $b['end']);
                $maxEnd = -1;
                foreach ($ranges as &$range) {
                    $maxEnd = max($maxEnd, $range['end']);
                    $range['prefix_end'] = $maxEnd;
                }
                unset($range);
            }
            unset($ranges);

            foreach ($rangesByLength as $length => $stockRanges) {
                if ($stockRanges === []) continue;
                $minStart = str_pad((string) min(array_column($stockRanges, 'start')), $length, '0', STR_PAD_LEFT);
                $maxEnd = str_pad((string) max(array_column($stockRanges, 'end')), $length, '0', STR_PAD_LEFT);
                $families = $length === 15
                    ? [['source_length' => 15, 'prefix' => '', 'prefix_mode' => 'any'], ['source_length' => 16, 'prefix' => '0', 'prefix_mode' => 'zero']]
                    : [['source_length' => $length, 'prefix' => '', 'prefix_mode' => $length === 16 ? 'nonzero' : 'any']];
                foreach ($families as $family) {
                    $sourceLength = $family['source_length'];
                    $prefix = $family['prefix'];
                    $soQuery = (clone $soBase)
                        ->whereRaw('CHAR_LENGTH(so.serial_start) = ?', [$sourceLength])
                        ->whereRaw('CHAR_LENGTH(so.serial_end) = ?', [$sourceLength])
                        ->where('so.serial_end', '>=', $prefix.$minStart)
                        ->where('so.serial_start', '<=', $prefix.$maxEnd);
                    if ($family['prefix_mode'] === 'zero') {
                        $soQuery->whereRaw("LEFT(so.serial_start, 1) = '0' AND LEFT(so.serial_end, 1) = '0'");
                    } elseif ($family['prefix_mode'] === 'nonzero') {
                        $soQuery->whereRaw("LEFT(so.serial_start, 1) <> '0' AND LEFT(so.serial_end, 1) <> '0'");
                    }
                    $pdo = DB::connection()->getPdo();
                    $bufferedQuery = DB::connection()->getDriverName() === 'mysql'
                        ? $pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY)
                        : null;
                    if ($bufferedQuery !== null) $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                    try {
                        foreach ($soQuery->cursor() as $sale) {
                            $saleStartText = $canonical($sale->serial_start);
                            $saleEndText = $canonical($sale->serial_end);
                            if (strlen($saleStartText) !== $length || strlen($saleEndText) !== $length
                                || ! ctype_digit($saleStartText) || ! ctype_digit($saleEndText)) continue;
                            $saleStart = (int) $saleStartText;
                            $saleEnd = (int) $saleEndText;
                            $low = 0;
                            $high = count($stockRanges);
                            while ($low < $high) {
                                $middle = intdiv($low + $high, 2);
                                if ($stockRanges[$middle]['prefix_end'] < $saleStart) $low = $middle + 1;
                                else $high = $middle;
                            }
                            for ($i = $low, $count = count($stockRanges); $i < $count && $stockRanges[$i]['start'] <= $saleEnd; $i++) {
                                $stockRange = $stockRanges[$i];
                                if ($stockRange['end'] < $saleStart) continue;
                                $overlap = min($saleEnd, $stockRange['end']) - max($saleStart, $stockRange['start']) + 1;
                                if ($overlap <= 0) continue;
                                $outletId = (string) $stockRange['range']->id_outlet;
                                $metrics[$outletId]['redeemed_qty'] += $overlap;
                                $stTime = $stockRange['range']->st_date ? strtotime((string) $stockRange['range']->st_date) : false;
                                $soTime = $sale->sold_at ? strtotime((string) $sale->sold_at) : false;
                                if ($stTime !== false && $soTime !== false) {
                                    $metrics[$outletId]['timed_qty'] += $overlap;
                                    $metrics[$outletId]['days_weighted'] += $overlap * max(0, intdiv($soTime - $stTime, 86400));
                                }
                            }
                        }
                    } finally {
                        if ($bufferedQuery !== null) $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $bufferedQuery);
                    }
                }
            }

            $sourceMeta = DB::query()->fromSub(clone $stSources, 'source')->select('source.owner_sf', 'source.iddenom')->distinct()->get();
            $owners = $sourceMeta->pluck('owner_sf')->filter()->unique()->values()->all();
            $denoms = $sourceMeta->pluck('iddenom')->filter()->unique()->values()->all();
            $daysToOutlet = app(\App\Services\StockOutletTiming::class)->daysToOutlet(
                $stRanges, $product, $owners, $denoms,
            );
            $outletRows->transform(function ($outlet) use ($metrics, $daysToOutlet) {
                $metric = $metrics[(string) $outlet->id_outlet] ?? ['st_qty' => 0, 'redeemed_qty' => 0, 'timed_qty' => 0, 'days_weighted' => 0];
                $sfTiming = $daysToOutlet[(string) $outlet->id_outlet] ?? ['qty' => 0, 'weighted_days' => 0, 'average_days' => null];
                $outlet->st_qty = (int) $metric['st_qty'];
                $outlet->so_qty = (int) $metric['redeemed_qty'];
                $outlet->redeemed_qty = (int) $metric['redeemed_qty'];
                $outlet->remaining_qty = max($outlet->st_qty - $outlet->redeemed_qty, 0);
                $outlet->days_sf_to_outlet = $sfTiming['average_days'];
                $outlet->days_sf_to_outlet_qty = $sfTiming['qty'];
                $outlet->days_sf_to_outlet_weighted = $sfTiming['weighted_days'];
                $outlet->days_outlet_to_customer = $metric['timed_qty'] > 0
                    ? (int) round($metric['days_weighted'] / $metric['timed_qty']) : null;
                $outlet->days_outlet_to_customer_qty = (int) $metric['timed_qty'];
                $outlet->days_outlet_to_customer_weighted = (int) $metric['days_weighted'];
                return $outlet;
            });
            return $outletRows->map(fn ($row) => (array) $row)->all();
        });
        $outletRows = collect($cachedRows)->map(fn ($row) => (object) $row);
        $viewMode = (string) $request->query('view', 'outlet');
        abort_unless(in_array($viewMode, ['outlet', 'tap'], true), 404);
        if ($viewMode !== 'outlet') {
            $summarizeStockGroup = static function ($group, string $label, string $rowType, string $cluster = ''): object {
                $summary = [
                    'group_label' => $label,
                    'row_type' => $rowType,
                    'cluster' => $cluster,
                    'outlet_count' => $group->count(),
                ];
                foreach (['pv', 'sa'] as $suffix) {
                    $stQty = (int) $group->sum('st_qty_'.$suffix);
                    $soQty = (int) $group->sum('so_qty_'.$suffix);
                    $sfQty = (int) $group->sum('days_sf_to_outlet_qty_'.$suffix);
                    $customerQty = (int) $group->sum('days_outlet_to_customer_qty_'.$suffix);
                    $summary['st_qty_'.$suffix] = $stQty;
                    $summary['so_qty_'.$suffix] = $soQty;
                    $summary['remaining_qty_'.$suffix] = max($stQty - $soQty, 0);
                    $summary['days_sf_to_outlet_'.$suffix] = $sfQty > 0 ? (int) round($group->sum('days_sf_to_outlet_weighted_'.$suffix) / $sfQty) : null;
                    $summary['days_outlet_to_customer_'.$suffix] = $customerQty > 0 ? (int) round($group->sum('days_outlet_to_customer_weighted_'.$suffix) / $customerQty) : null;
                }
                return (object) $summary;
            };
            $clusterOrder = ['DUMAI BENGKALIS', 'ROKAN HILIR'];
            $tapOrder = ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING', 'BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'];
            $clusterGroups = $outletRows->groupBy(fn ($row) => trim((string) ($row->cluster ?? '')) ?: 'Cluster Lainnya');
            $summaryRows = collect();
            foreach ($clusterOrder as $clusterName) {
                $clusterOutlets = $clusterGroups->get($clusterName, collect());
                if ($clusterOutlets->isEmpty()) continue;
                if ($viewMode === 'tap') {
                    $tapGroups = $clusterOutlets->groupBy(fn ($row) => strtoupper(trim((string) ($row->tap ?? ''))) ?: 'TANPA TAP');
                    foreach ($tapOrder as $tapName) {
                        $tapOutlets = $tapGroups->get($tapName, collect());
                        if ($tapOutlets->isNotEmpty()) $summaryRows->push($summarizeStockGroup($tapOutlets, $tapName, 'detail', $clusterName));
                    }
                    foreach ($tapGroups as $tapName => $tapOutlets) {
                        if (! in_array($tapName, $tapOrder, true)) $summaryRows->push($summarizeStockGroup($tapOutlets, $tapName, 'detail', $clusterName));
                    }
                } else {
                    $summaryRows->push($summarizeStockGroup($clusterOutlets, $clusterName, 'detail', $clusterName));
                }
                if ($viewMode === 'tap') $summaryRows->push($summarizeStockGroup($clusterOutlets, 'Total '.$clusterName, 'cluster-total', $clusterName));
            }
            foreach ($clusterGroups as $clusterName => $clusterOutlets) {
                if (! in_array($clusterName, $clusterOrder, true)) {
                    $summaryRows->push($summarizeStockGroup($clusterOutlets, $clusterName, 'detail', $clusterName));
                    if ($viewMode === 'tap') $summaryRows->push($summarizeStockGroup($clusterOutlets, 'Total '.$clusterName, 'cluster-total', $clusterName));
                }
            }
            if ($outletRows->isNotEmpty()) $summaryRows->push($summarizeStockGroup($outletRows, 'Grand Total', 'grand-total'));
            $outletRows = $summaryRows->values();
        } else {
            $outletRows = $this->filterAndSortStockOutletRows($outletRows, $options);
        }
        $page = LengthAwarePaginator::resolveCurrentPage();
        $allRows = filter_var($request->query('all_rows', false), FILTER_VALIDATE_BOOLEAN);
        $outlets = new LengthAwarePaginator(
            $allRows ? $outletRows : $outletRows->forPage($page, 20)->values(),
            $outletRows->count(),
            $allRows ? max(1, $outletRows->count()) : 20,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        return response()->view('surya.product-tracking.stock-outlet', [
            'product' => $product,
            'outlets' => $outlets,
            'soKind' => $soKind,
            'stockTableOptions' => $options,
            'viewMode' => $viewMode,
        ])->header('Server-Timing', 'stock-outlet;dur='.round((microtime(true) - $startedAt) * 1000));
    }

    private function combinedStockOutlet(Request $request)
    {
        $stockColumns = $this->stockOutletColumnMap();
        $options = $this->stockOutletTableOptions($request, array_keys($stockColumns));
        $combinedCacheKey = 'stock-outlet-combined-v1-'.sha1(json_encode([
            $this->stockOutletReportCacheKey('PV'),
            $this->stockOutletReportCacheKey('SA'),
        ]));
        $combined = Cache::remember($combinedCacheKey, now()->addMinutes(5), function () use ($request) {
            $rowsByOutlet = [];
            foreach (['PV', 'SA'] as $reportProduct) {
                foreach ($this->stockOutletRowsForFilters($request, $reportProduct, false) as $row) {
                    $id = (string) $row['id_outlet'];
                    $rowsByOutlet[$id] ??= array_intersect_key($row, array_flip(['id_outlet', 'nama_outlet', 'kecamatan', 'tap', 'cluster', 'sf', 'kategori_outlet']));
                    $suffix = strtolower($reportProduct);
                    foreach (['remaining_qty', 'st_qty', 'so_qty', 'days_sf_to_outlet', 'days_sf_to_outlet_qty', 'days_sf_to_outlet_weighted', 'days_outlet_to_customer', 'days_outlet_to_customer_qty', 'days_outlet_to_customer_weighted'] as $metric) {
                        $rowsByOutlet[$id][$metric.'_'.$suffix] = $row[$metric] ?? null;
                    }
                }
            }
            return $rowsByOutlet;
        });
        $metricDefaults = [];
        foreach (['pv', 'sa'] as $suffix) {
            foreach (['remaining_qty', 'st_qty', 'so_qty', 'days_sf_to_outlet', 'days_sf_to_outlet_qty', 'days_sf_to_outlet_weighted', 'days_outlet_to_customer', 'days_outlet_to_customer_qty', 'days_outlet_to_customer_weighted'] as $metric) {
                $metricDefaults[$metric.'_'.$suffix] = null;
            }
        }
        $outletRows = collect($combined)->map(fn ($row) => (object) array_merge($metricDefaults, $row));
        $viewMode = (string) $request->query('view', 'outlet');
        abort_unless(in_array($viewMode, ['outlet', 'tap'], true), 404);
        if ($viewMode === 'tap') {
            $summarizeStockGroup = static function ($group, string $label, string $rowType, string $cluster = ''): object {
                $summary = ['group_label' => $label, 'row_type' => $rowType, 'cluster' => $cluster, 'outlet_count' => $group->count()];
                foreach (['pv', 'sa'] as $suffix) {
                    $stQty = (int) $group->sum('st_qty_'.$suffix);
                    $soQty = (int) $group->sum('so_qty_'.$suffix);
                    $sfQty = (int) $group->sum('days_sf_to_outlet_qty_'.$suffix);
                    $customerQty = (int) $group->sum('days_outlet_to_customer_qty_'.$suffix);
                    $summary['st_qty_'.$suffix] = $stQty;
                    $summary['so_qty_'.$suffix] = $soQty;
                    $summary['remaining_qty_'.$suffix] = max($stQty - $soQty, 0);
                    $summary['days_sf_to_outlet_'.$suffix] = $sfQty > 0 ? (int) round($group->sum('days_sf_to_outlet_weighted_'.$suffix) / $sfQty) : null;
                    $summary['days_outlet_to_customer_'.$suffix] = $customerQty > 0 ? (int) round($group->sum('days_outlet_to_customer_weighted_'.$suffix) / $customerQty) : null;
                }
                return (object) $summary;
            };
            $clusterOrder = ['DUMAI BENGKALIS', 'ROKAN HILIR'];
            $tapOrder = ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING', 'BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'];
            $clusterGroups = $outletRows->groupBy(fn ($row) => trim((string) ($row->cluster ?? '')) ?: 'Cluster Lainnya');
            $summaryRows = collect();
            foreach ($clusterOrder as $clusterName) {
                $clusterOutlets = $clusterGroups->get($clusterName, collect());
                if ($clusterOutlets->isEmpty()) continue;
                $tapGroups = $clusterOutlets->groupBy(fn ($row) => strtoupper(trim((string) ($row->tap ?? ''))) ?: 'TANPA TAP');
                foreach ($tapOrder as $tapName) {
                    $tapOutlets = $tapGroups->get($tapName, collect());
                    if ($tapOutlets->isNotEmpty()) $summaryRows->push($summarizeStockGroup($tapOutlets, $tapName, 'detail', $clusterName));
                }
                foreach ($tapGroups as $tapName => $tapOutlets) {
                    if (! in_array($tapName, $tapOrder, true)) $summaryRows->push($summarizeStockGroup($tapOutlets, $tapName, 'detail', $clusterName));
                }
                $summaryRows->push($summarizeStockGroup($clusterOutlets, 'Total '.$clusterName, 'cluster-total', $clusterName));
            }
            foreach ($clusterGroups as $clusterName => $clusterOutlets) {
                if (! in_array($clusterName, $clusterOrder, true)) {
                    $summaryRows->push($summarizeStockGroup($clusterOutlets, $clusterName, 'detail', $clusterName));
                    $summaryRows->push($summarizeStockGroup($clusterOutlets, 'Total '.$clusterName, 'cluster-total', $clusterName));
                }
            }
            if ($outletRows->isNotEmpty()) $summaryRows->push($summarizeStockGroup($outletRows, 'Grand Total', 'grand-total'));
            $outletRows = $summaryRows->values();
        } else {
            $outletRows = $this->filterAndSortStockOutletRows($outletRows, $options);
        }
        $page = LengthAwarePaginator::resolveCurrentPage();
        $allRows = filter_var($request->query('all_rows', false), FILTER_VALIDATE_BOOLEAN);
        $outlets = new LengthAwarePaginator(
            $allRows ? $outletRows : $outletRows->forPage($page, 20)->values(),
            $outletRows->count(),
            $allRows ? max(1, $outletRows->count()) : 20,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('surya.product-tracking.stock-outlet', [
            'product' => 'PV', 'outlets' => $outlets, 'soKind' => 'so-pv',
            'stockTableOptions' => $options, 'viewMode' => $viewMode,
        ]);
    }

    public function stockOutletColumnValues(Request $request)
    {
        $startedAt = microtime(true);
        $columns = $this->stockOutletColumnMap();
        $request->validate([
            'column' => ['required', Rule::in(array_keys($columns))],
            'q' => 'nullable|string|max:150',
            'value_page' => 'nullable|integer|min:1|max:10000',
        ]);
        $key = (string) $request->query('column');
        $product = strtoupper((string) $request->query('product', 'PV'));
        if (str_ends_with($key, '_sa')) $product = 'SA';
        elseif (str_ends_with($key, '_pv')) $product = 'PV';
        abort_unless(in_array($product, ['PV', 'SA'], true), 404);
        $allRequest = clone $request;
        // Keep facets scoped to the requested product. Forcing PV here made the
        // SA tab calculate and expose PV filter values on every facet request.
        $allRequest->query->set('product', $product);
        $allRequest->query->set('_single_product', 1);
        $allRequest->query->set('view', 'outlet');
        $allRequest->query->set('all_rows', 1);
        foreach (['columns', 'exclude', 'sort', 'direction', 'page'] as $option) $allRequest->query->remove($option);
        $facetColumn = str_ends_with($key, '_'.strtolower($product))
            ? substr($key, 0, -strlen('_'.strtolower($product)))
            : $key;
        $rows = $this->stockOutletResponseRows($allRequest)->map(fn ($row) => (array) $row)->all();
        $needle = mb_strtolower(trim((string) $request->query('q')));
        $values = collect($rows)->map(fn ($row) => (string) ($row[$facetColumn] ?? ''))->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->filter(fn ($value) => $needle === '' || str_contains(mb_strtolower($value), $needle))
            ->values();
        $page = max(1, (int) $request->query('value_page', 1));
        $values = $values->slice(($page - 1) * 100, 101)->values();

        return response()->json(['values' => $values->take(100), 'has_more' => $values->count() > 100])
            ->header('Server-Timing', 'stock-outlet-facets;dur='.round((microtime(true) - $startedAt) * 1000));
    }

    public function downloadStockOutlet(Request $request)
    {
        $product = strtoupper((string) $request->query('product', 'PV'));
        abort_unless(in_array($product, ['PV', 'SA'], true), 404);
        $allowedColumns = array_keys($this->stockOutletColumnMap());
        $options = $this->stockOutletTableOptions($request, $allowedColumns);
        $allRequest = clone $request;
        $allRequest->query->set('product', 'PV');
        $allRequest->query->set('view', 'outlet');
        $allRequest->query->set('all_rows', 1);
        foreach (['columns', 'exclude', 'sort', 'direction', 'page'] as $option) $allRequest->query->remove($option);
        $combinedRows = $this->stockOutletResponseRows($allRequest);
        $rows = $this->filterAndSortStockOutletRows($combinedRows, $options);

        return response()->streamDownload(function () use ($rows) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'ID Outlet', 'Nama Outlet', 'Kecamatan', 'TAP', 'Cluster', 'Sales Force', 'Kategori Outlet',
                'Sisa Stock PV', 'ST PV', 'SO PV', 'SF ke Outlet PV (hari)', 'Outlet ke Pelanggan PV (hari)',
                'Sisa Stock SA', 'ST SA', 'SO SA', 'SF ke Outlet SA (hari)', 'Outlet ke Pelanggan SA (hari)',
            ]);
            foreach ($rows as $outlet) {
                fputcsv($stream, [
                    $outlet->id_outlet, $outlet->nama_outlet, $outlet->kecamatan, $outlet->tap, $outlet->cluster, $outlet->sf, $outlet->kategori_outlet,
                    $outlet->remaining_qty_pv ?? 0, $outlet->st_qty_pv ?? 0, $outlet->so_qty_pv ?? 0,
                    $outlet->days_sf_to_outlet_pv ?? null, $outlet->days_outlet_to_customer_pv ?? null,
                    $outlet->remaining_qty_sa ?? 0, $outlet->st_qty_sa ?? 0, $outlet->so_qty_sa ?? 0,
                    $outlet->days_sf_to_outlet_sa ?? null, $outlet->days_outlet_to_customer_sa ?? null,
                ]);
            }
            fclose($stream);
        }, 'stock-outlet-gabungan-semua-outlet.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function stockOutletColumnMap(): array
    {
        return [
            'id_outlet' => 'outlet.id_outlet',
            'nama_outlet' => 'outlet.nama_outlet',
            'kecamatan' => 'outlet.kecamatan',
            'tap' => 'outlet.tap',
            'cluster' => 'cluster',
            'sf' => 'outlet.sf',
            'kategori_outlet' => Schema::hasColumn('appsdumais', 'kategori_outlet') ? 'outlet.kategori_outlet' : 'kategori_outlet',
            'remaining_qty_pv' => 'remaining_qty_pv', 'st_qty_pv' => 'st_qty_pv', 'so_qty_pv' => 'so_qty_pv',
            'days_sf_to_outlet_pv' => 'days_sf_to_outlet_pv', 'days_outlet_to_customer_pv' => 'days_outlet_to_customer_pv',
            'remaining_qty_sa' => 'remaining_qty_sa', 'st_qty_sa' => 'st_qty_sa', 'so_qty_sa' => 'so_qty_sa',
            'days_sf_to_outlet_sa' => 'days_sf_to_outlet_sa', 'days_outlet_to_customer_sa' => 'days_outlet_to_customer_sa',
        ];
    }

    private function filterAndSortStockOutletRows($rows, array $options)
    {
        foreach (['columns', 'exclude'] as $mode) {
            foreach ($options[$mode] ?? [] as $column => $values) {
                $rows = $rows->filter(function ($row) use ($column, $values, $mode) {
                    $value = $row->{$column} ?? '';
                    $matches = in_array((string) $value, $values, true);
                    return $mode === 'columns' ? $matches : ! $matches;
                });
            }
        }

        $sort = $options['sort'];
        $numeric = in_array($sort, ['remaining_qty_pv', 'st_qty_pv', 'so_qty_pv', 'days_sf_to_outlet_pv', 'days_outlet_to_customer_pv', 'remaining_qty_sa', 'st_qty_sa', 'so_qty_sa', 'days_sf_to_outlet_sa', 'days_outlet_to_customer_sa'], true);
        $direction = $options['direction'] === 'desc' ? -1 : 1;
        return $rows->sort(function ($a, $b) use ($sort, $numeric, $direction) {
            $left = $a->{$sort} ?? null;
            $right = $b->{$sort} ?? null;
            $comparison = $numeric ? ((float) $left <=> (float) $right) : strnatcasecmp((string) $left, (string) $right);
            return ($comparison * $direction) ?: strnatcasecmp((string) $a->id_outlet, (string) $b->id_outlet);
        })->values();
    }

    private function stockOutletRowsForFilters(Request $request, string $product, bool $includeFilters = true): array
    {
        $allRequest = clone $request;
        $allRequest->query->set('product', $product);
        $allRequest->query->set('all_rows', 1);
        $allRequest->query->set('_single_product', 1);
        $allRequest->query->set('view', 'outlet');
        $allRequest->query->remove('page');
        if (! $includeFilters) {
            foreach (['columns', 'exclude', 'sort', 'direction'] as $key) $allRequest->query->remove($key);
        }
        return $this->stockOutletResponseRows($allRequest)->map(fn ($row) => (array) $row)->all();
    }

    private function stockOutletResponseRows(Request $request)
    {
        $response = $this->stockOutlet($request);
        $view = $response->getOriginalContent();
        if (! $view instanceof \Illuminate\View\View) {
            throw new \LogicException('Data Stock Outlet tidak tersedia dari response laporan.');
        }
        $data = $view->getData();

        return collect($data['outlets']->getCollection());
    }

    private function stockOutletReportCacheKey(string $product): string
    {
        $soKind = 'so-'.strtolower($product);
        $reportVersion = [
            'web_st' => DB::table('keluarsf')->max('idkeluar'),
            'mobile_st' => DB::table('mobile_sales_serials')->max('id'),
            'mobile_sales_updated' => DB::table('mobile_penjualan')->max('updated_at'),
            'so_upload_updated' => DB::table('product_tracking_uploads')->where('kind', $soKind)->max('updated_at'),
            'stock_origin' => DB::table('product_tracking_sales_stock_ranges')->max('id'),
            'sf_stock_origin' => DB::table('masuksf')->max('idmasuk'),
            'audited_changes' => DB::table('logs')->whereIn('module', [
                'Stok Keluar SF', 'Stok Keluar SF (Bulk)', 'Pending Stor SF',
                'Stok Masuk SF', 'Stok Masuk SF (Bulk)', 'Mobile Sales',
            ])->max('created_at'),
        ];

        return 'stock-outlet-report-v10-'.sha1(json_encode([
            strtoupper($product), $this->suryaTapScopes(), $reportVersion,
        ]));
    }

    private function stockOutletTableOptions(Request $request, array $allowedColumns): array
    {
        $data = $request->validate([
            'columns' => 'nullable|array|max:20', 'columns.*' => 'array|min:1|max:100', 'columns.*.*' => 'nullable|string|max:5000',
            'exclude' => 'nullable|array|max:20', 'exclude.*' => 'array|min:1|max:100', 'exclude.*.*' => 'nullable|string|max:5000',
            'sort' => ['nullable', Rule::in($allowedColumns)],
            'direction' => 'nullable|in:asc,desc',
            'page' => 'nullable|integer|min:1|max:100000',
            'all_rows' => 'nullable|boolean',
        ]);
        foreach (['columns', 'exclude'] as $mode) {
            foreach ($data[$mode] ?? [] as $column => $values) {
                if (! in_array($column, $allowedColumns, true)) throw ValidationException::withMessages(['column' => 'Kolom Stock Outlet tidak valid.']);
                $data[$mode][$column] = array_map(fn ($value) => (string) $value, $values);
            }
        }

        return [
            'columns' => $data['columns'] ?? [],
            'exclude' => $data['exclude'] ?? [],
            'sort' => $data['sort'] ?? 'nama_outlet',
            'direction' => $data['direction'] ?? 'asc',
        ];
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

    public function duplicateUploadRanges(Request $request)
    {
        $this->requireEditor();
        $data = $request->validate(['kind' => 'required|in:scan,so-pv,so-sa']);
        $kind = $data['kind'];
        $serialFamilies = $kind === 'scan' ? [[12], [15, 16]] : [str_ends_with($kind, 'pv') ? [12] : [15, 16]];
        $duplicates = [];
        $limit = 500;
        $truncated = false;

        foreach ($serialFamilies as $lengths) {
            $normalizedStart = count($lengths) === 2
                ? "CASE WHEN CHAR_LENGTH(digipos.serial_start) = 16 AND LEFT(digipos.serial_start, 1) = '0' THEN SUBSTRING(digipos.serial_start, 2) ELSE digipos.serial_start END"
                : 'digipos.serial_start';
            $normalizedEnd = count($lengths) === 2
                ? "CASE WHEN CHAR_LENGTH(digipos.serial_end) = 16 AND LEFT(digipos.serial_end, 1) = '0' THEN SUBSTRING(digipos.serial_end, 2) ELSE digipos.serial_end END"
                : 'digipos.serial_end';
            $query = DB::table('product_tracking_digipos as digipos')
                ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
                ->where('digipos.kind', $kind)->where('upload.kind', $kind)->where('upload.status', 'complete')
                ->whereNotNull('digipos.serial_start')->whereNotNull('digipos.serial_end')
                ->where(function ($lengthQuery) use ($lengths) {
                    foreach ($lengths as $length) {
                        $lengthQuery->orWhere(function ($matchLength) use ($length) {
                            $matchLength->whereRaw('CHAR_LENGTH(digipos.serial_start) = ?', [$length]);
                            if ($length === 16) $matchLength->whereRaw("LEFT(digipos.serial_start, 1) = '0' AND CHAR_LENGTH(digipos.serial_end) = 16 AND LEFT(digipos.serial_end, 1) = '0'");
                        });
                    }
                })
                ->select('digipos.id', 'digipos.upload_id', 'digipos.serial_start', 'digipos.serial_end', 'digipos.sold_at', 'digipos.scan_date', 'digipos.id_outlet', 'upload.filename')
                ->selectRaw("{$normalizedStart} AS normalized_start, {$normalizedEnd} AS normalized_end")
                ->orderByRaw("{$normalizedStart} ASC")->orderBy('digipos.id');
            $activeRanges = [];

            foreach ($query->cursor() as $row) {
                $start = (string) $row->normalized_start;
                $end = (string) $row->normalized_end;
                $activeRanges = array_values(array_filter($activeRanges, fn ($active) => strcmp($active['end'], $start) >= 0));

                foreach ($activeRanges as $active) {
                    $duplicateEnd = strcmp($active['end'], $end) <= 0 ? $active['end'] : $end;
                    $duplicates[] = [
                        'first_id' => (int) $active['id'], 'duplicate_id' => (int) $row->id,
                        'serial_start' => $start, 'serial_end' => $duplicateEnd,
                        'quantity' => $this->serialRangeCount($start, $duplicateEnd),
                        'first_file' => $active['filename'], 'first_date' => $active['sold_at'] ?: $active['scan_date'],
                        'first_outlet' => $active['id_outlet'], 'duplicate_file' => $row->filename,
                        'duplicate_date' => $row->sold_at ?: $row->scan_date, 'duplicate_outlet' => $row->id_outlet,
                    ];
                    if (count($duplicates) >= $limit) {
                        $truncated = true;
                        break 3;
                    }
                }

                $activeRanges[] = ['id' => $row->id, 'start' => $start, 'end' => $end, 'filename' => $row->filename,
                    'sold_at' => $row->sold_at, 'scan_date' => $row->scan_date, 'id_outlet' => $row->id_outlet];
            }
        }

        return view('surya.product-tracking.duplicates', compact('kind', 'duplicates', 'truncated'));
    }

    public function removeDuplicateUploadRange(Request $request)
    {
        $this->requireEditor();
        $data = $request->validate([
            'kind' => 'required|in:scan,so-pv,so-sa',
            'first_id' => 'required|integer|min:1|different:duplicate_id',
            'duplicate_id' => 'required|integer|min:1',
            'remove_from' => 'required|in:first,duplicate',
        ]);

        $removedCount = DB::transaction(function () use ($data) {
            $rows = DB::table('product_tracking_digipos as d')
                ->join('product_tracking_uploads as u', 'u.id', '=', 'd.upload_id')
                ->whereIn('d.id', [$data['first_id'], $data['duplicate_id']])
                ->where('d.kind', $data['kind'])->where('u.kind', $data['kind'])->where('u.status', 'complete')
                ->select('d.*')->orderBy('d.id')->lockForUpdate()->get()->keyBy('id');

            abort_unless($rows->count() === 2, 409, 'Data upload sudah berubah. Muat ulang daftar duplikat.');
            $first = $rows->get((int) $data['first_id']);
            $duplicate = $rows->get((int) $data['duplicate_id']);
            abort_unless($first && $duplicate, 409, 'Data upload sudah berubah. Muat ulang daftar duplikat.');

            $canonical = static fn (string $serial): string => strlen($serial) === 16 && str_starts_with($serial, '0') ? substr($serial, 1) : $serial;
            $firstStart = $canonical((string) $first->serial_start);
            $firstEnd = $canonical((string) $first->serial_end);
            $duplicateStart = $canonical((string) $duplicate->serial_start);
            $duplicateEnd = $canonical((string) $duplicate->serial_end);
            $overlapStart = strcmp($firstStart, $duplicateStart) >= 0 ? $firstStart : $duplicateStart;
            $overlapEnd = strcmp($firstEnd, $duplicateEnd) <= 0 ? $firstEnd : $duplicateEnd;
            abort_unless(strlen($overlapStart) === strlen($overlapEnd) && strcmp($overlapStart, $overlapEnd) <= 0, 409, 'Rentang tidak lagi bertumpuk. Muat ulang daftar duplikat.');

            $target = $data['remove_from'] === 'first' ? $first : $duplicate;
            $targetStart = (string) $target->serial_start;
            $targetEnd = (string) $target->serial_end;
            $pad = strlen($targetStart) === 16 && str_starts_with($targetStart, '0');
            $cutStart = $pad ? '0'.$overlapStart : $overlapStart;
            $cutEnd = $pad ? '0'.$overlapEnd : $overlapEnd;
            $segments = [];

            if (strcmp($targetStart, $cutStart) < 0) {
                $leftEnd = $this->subtractOneFromSerial($cutStart);
                if (strcmp($targetStart, $leftEnd) <= 0) $segments[] = [$targetStart, $leftEnd];
            }
            if (strcmp($cutEnd, $targetEnd) < 0) {
                $rightStart = $this->incrementSerial($cutEnd);
                if (strcmp($rightStart, $targetEnd) <= 0) $segments[] = [$rightStart, $targetEnd];
            }

            DB::table('product_tracking_digipos')->where('id', $target->id)->delete();
            foreach ($segments as [$start, $end]) {
                DB::table('product_tracking_digipos')->insert([
                    'upload_id' => $target->upload_id, 'kind' => $target->kind,
                    'scan_date' => $target->scan_date, 'serial_start' => $start, 'serial_end' => $end,
                    'serial_count' => (int) $this->serialRangeCount($start, $end),
                    'id_outlet' => $target->id_outlet, 'sf' => $target->sf, 'sold_at' => $target->sold_at,
                    'product_name' => $target->product_name, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            return (int) $this->serialRangeCount($cutStart, $cutEnd);
        });

        $this->invalidateDigiposScanCoverageRanges($data['kind']);

        return redirect()->route('surya.product-tracking.imports.duplicates', ['kind' => $data['kind']])
            ->with('success', number_format($removedCount).' SN dihapus dari catatan yang dipilih. Bagian rentang yang tidak duplikat tetap tersimpan.');
    }

    public function removeAllDuplicateUploadRanges(Request $request)
    {
        $this->requireEditor();
        $data = $request->validate(['kind' => 'required|in:scan,so-pv,so-sa']);

        [$removedCount, $changedRanges] = DB::transaction(function () use ($data) {
            $kind = $data['kind'];
            $families = $kind === 'scan' ? [[12], [15, 16]] : [str_ends_with($kind, 'pv') ? [12] : [15, 16]];
            $removedCount = 0;
            $changedRanges = 0;

            foreach ($families as $lengths) {
                $normalizedStart = count($lengths) === 2
                    ? "CASE WHEN CHAR_LENGTH(d.serial_start) = 16 AND LEFT(d.serial_start, 1) = '0' THEN SUBSTRING(d.serial_start, 2) ELSE d.serial_start END"
                    : 'd.serial_start';
                $normalizedEnd = count($lengths) === 2
                    ? "CASE WHEN CHAR_LENGTH(d.serial_end) = 16 AND LEFT(d.serial_end, 1) = '0' THEN SUBSTRING(d.serial_end, 2) ELSE d.serial_end END"
                    : 'd.serial_end';
                $query = DB::table('product_tracking_digipos as d')
                    ->join('product_tracking_uploads as u', 'u.id', '=', 'd.upload_id')
                    ->where('d.kind', $kind)->where('u.kind', $kind)->where('u.status', 'complete')
                    ->whereNotNull('d.serial_start')->whereNotNull('d.serial_end')
                    ->where(function ($lengthQuery) use ($lengths) {
                        foreach ($lengths as $length) {
                            $lengthQuery->orWhere(function ($matchLength) use ($length) {
                                $matchLength->whereRaw('CHAR_LENGTH(d.serial_start) = ?', [$length]);
                                if ($length === 16) $matchLength->whereRaw("LEFT(d.serial_start, 1) = '0' AND CHAR_LENGTH(d.serial_end) = 16 AND LEFT(d.serial_end, 1) = '0'");
                            });
                        }
                    })
                    ->select('d.*')->selectRaw("{$normalizedStart} AS normalized_start, {$normalizedEnd} AS normalized_end")
                    ->orderByRaw("{$normalizedStart} ASC")->orderBy('d.id');

                // Earlier ranges win. For each later row, retain only serials not covered before it.
                $covered = [];
                $changes = [];
                foreach ($query->cursor() as $row) {
                    $start = (string) $row->normalized_start;
                    $end = (string) $row->normalized_end;
                    $covered = array_values(array_filter($covered, fn ($range) => strcmp($range['end'], $start) >= 0));
                    usort($covered, fn ($left, $right) => strcmp($left['start'], $right['start']));

                    $segments = [];
                    $cursor = $start;
                    foreach ($covered as $range) {
                        if (strcmp($range['end'], $cursor) < 0) continue;
                        if (strcmp($range['start'], $end) > 0) break;
                        if (strcmp($range['start'], $cursor) > 0) {
                            $gapEnd = $this->subtractOneFromSerial($range['start']);
                            if (strcmp($cursor, $gapEnd) <= 0) $segments[] = [$cursor, $gapEnd];
                        }
                        if (strcmp($range['end'], $cursor) >= 0) $cursor = $this->incrementSerial($range['end']);
                        if (strcmp($cursor, $end) > 0) break;
                    }
                    if (strcmp($cursor, $end) <= 0) $segments[] = [$cursor, $end];

                    if (count($segments) !== 1 || $segments[0][0] !== $start || $segments[0][1] !== $end) {
                        $changes[] = ['row' => $row, 'segments' => $segments, 'start' => $start, 'end' => $end];
                    }
                    $covered[] = ['start' => $start, 'end' => $end];
                }

                foreach ($changes as $change) {
                    $row = $change['row'];
                    $pad = strlen((string) $row->serial_start) === 16 && str_starts_with((string) $row->serial_start, '0');
                    $keptCount = 0;
                    foreach ($change['segments'] as [$segmentStart, $segmentEnd]) {
                        $keptCount += (int) $this->serialRangeCount($segmentStart, $segmentEnd);
                    }
                    $removedCount += max(0, (int) $this->serialRangeCount((string) $row->serial_start, (string) $row->serial_end) - $keptCount);
                    DB::table('product_tracking_digipos')->where('id', $row->id)->delete();
                    foreach ($change['segments'] as [$segmentStart, $segmentEnd]) {
                        if ($pad) {
                            $segmentStart = '0'.$segmentStart;
                            $segmentEnd = '0'.$segmentEnd;
                        }
                        $copy = (array) $row;
                        unset($copy['id'], $copy['normalized_start'], $copy['normalized_end']);
                        $copy['serial_start'] = $segmentStart;
                        $copy['serial_end'] = $segmentEnd;
                        $copy['serial_count'] = (int) $this->serialRangeCount($segmentStart, $segmentEnd);
                        $copy['created_at'] = now();
                        $copy['updated_at'] = now();
                        DB::table('product_tracking_digipos')->insert($copy);
                    }
                    $changedRanges++;
                }
            }

            return [$removedCount, $changedRanges];
        }, 3);

        $this->invalidateDigiposScanCoverageRanges($data['kind']);

        return redirect()->route('surya.product-tracking.imports.duplicates', ['kind' => $data['kind']])
            ->with('success', number_format($removedCount).' SN duplikat dibersihkan dari '.$changedRanges.' rentang. Bagian rentang yang unik tetap disimpan.');
    }

    public function salesStock()
    {
        $this->requireEditor();
        abort_unless(Schema::hasTable('product_tracking_sales_stock_uploads'), 503, 'Fitur upload stok SN Sales belum diaktifkan. Jalankan migrasi terbaru terlebih dahulu.');
        return view('surya.product-tracking.sales-stock', [
            'uploads' => DB::table('product_tracking_sales_stock_uploads')->orderByDesc('id')->limit(30)->get(),
        ]);
    }

    public function sfStock(Request $request)
    {
        abort_unless(strtolower(trim((string) auth()->user()?->username)) === 'admin_super', 403, 'Fitur ini hanya dapat digunakan admin_super.');
        abort_unless(
            Schema::hasTable('product_tracking_sales_stock_uploads') && Schema::hasTable('product_tracking_sales_stock_ranges'),
            503,
            'Sumber label stok SN Sales belum tersedia.'
        );

        $filters = $request->validate([
            'sf' => 'nullable|string|max:100', 'product' => 'nullable|in:PV,SA',
            'iddenom' => 'nullable|string|max:25', 'source' => 'nullable|in:SF Masuk,Upload Stok SN Sales,Add Label SN',
            'sn' => ['nullable', 'regex:/^\\d{9,16}$/'], 'detail_sf' => 'nullable|string|max:100',
            'sort' => 'nullable|in:sf,tap,total',
            'direction' => 'nullable|in:asc,desc', 'page' => 'nullable|integer|min:1|max:100000',
            'sn_page' => 'nullable|integer|min:1|max:100000',
        ]);
        $filterDefaults = ['sf' => '', 'product' => '', 'iddenom' => '', 'source' => '', 'sn' => '', 'detail_sf' => '', 'sort' => 'sf', 'direction' => 'asc'];
        // ConvertEmptyStringsToNull turns the blank “all” options into null.
        // Restore defaults before applying filters so an empty dropdown never
        // gets mistaken for an unknown SF/denom and triggers a 404.
        $filters = array_replace($filterDefaults, array_filter($filters, static fn ($value) => $value !== null));

        $denoms = DB::table('denom')->orderBy('denom')->get(['iddenom', 'denom', 'product_group', 'kategori_inject', 'group_name']);
        $eligibleDenoms = $denoms->filter(fn ($denom) => in_array(DenomGrouping::infer($denom)['product'], ['PV', 'SA'], true))->values();
        $denomsById = $eligibleDenoms->keyBy('iddenom');
        $salesForces = DB::table('idsf')->whereRaw("UPPER(idsf) LIKE 'SF%'")
            ->whereRaw("UPPER(TRIM(namasf)) NOT LIKE 'N/A%'")
            ->orderBy('namasf')->get(['idsf', 'namasf', 'idtap']);
        $salesById = $salesForces->keyBy('idsf');
        if ($filters['sf'] !== '' && ! $salesById->has($filters['sf'])) abort(404);
        if ($filters['iddenom'] !== '' && ! $denomsById->has($filters['iddenom'])) abort(404);

        // Merge compact ranges rather than expanding stock into rows until the
        // requested page is rendered. Corrections win ownership, then SF Masuk,
        // then upload ranges. Every serial is claimed once across all sources.
        // Uploaded stock files are full snapshots: use the newest snapshot only,
        // instead of rereading and comparing every historical upload per page.
        $latestUploadId = DB::table('product_tracking_sales_stock_uploads')
            ->where('filename', 'not like', 'MANUAL:%')->max('id');
        $latestMasukId = DB::table('masuksf')->max('idmasuk') ?: 0;
        $latestStockRangeId = DB::table('product_tracking_sales_stock_ranges')->max('id') ?: 0;
        $cacheKey = 'product-tracking:sf-stock-ranges:union:v3:'.sha1(implode(':', [
            $latestUploadId ?: 0, $latestMasukId, $latestStockRangeId,
        ]));
        $snapshot = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($denomsById, $latestUploadId) {
            $candidates = [];
            $masukRows = DB::table('masuksf')->orderByDesc('tgl')->orderByDesc('idmasuk')->cursor(['idmasuk', 'idsf', 'iddenom', 'sn', 'qty', 'tgl']);
            foreach ($masukRows as $row) {
                $denom = $denomsById->get($row->iddenom);
                if (! $denom) continue;
                $raw = trim((string) $row->sn);
                if (preg_match('/^(\\d+)(?:\\s*[-–—]\\s*|\\s+)(\\d+)$/u', $raw, $match)) [$start, $end] = [$match[1], $match[2]];
                elseif (preg_match('/^\\d+$/', $raw)) $start = $end = $raw;
                else continue;
                if (strlen($start) !== strlen($end) || strlen($start) < 12 || strlen($start) > 16 || $this->compareSerials($start, $end) > 0) continue;
                if (strlen($start) === 16 && (! str_starts_with($start, '0') || ! str_starts_with($end, '0'))) continue;
                if (strlen($start) === 16) { $start = substr($start, 1); $end = substr($end, 1); }
                if ((int) $this->serialRangeCount($start, $end) > 2_000_000 || ((int) $this->serialRangeCount($start, $end) !== (int) $row->qty && $start !== $end)) continue;
                $product = DenomGrouping::infer($denom)['product'];
                if (($product === 'PV' && strlen($start) !== 12) || ($product === 'SA' && ! in_array(strlen($start), [15, 16], true))) continue;
                $candidates[] = [(string) $row->idsf, (string) $row->iddenom, $start, $end,
                    'SF Masuk', 1, (string) $row->tgl, (int) $row->idmasuk];
            }

            $stockRows = DB::table('product_tracking_sales_stock_ranges as stock')
                ->join('product_tracking_sales_stock_uploads as upload', 'upload.id', '=', 'stock.upload_id')
                ->where(function ($query) use ($latestUploadId) {
                    $query->where('upload.filename', 'like', 'MANUAL:%');
                    if ($latestUploadId) $query->orWhere('stock.upload_id', $latestUploadId);
                })
                ->orderByRaw("CASE WHEN upload.filename LIKE 'MANUAL:%' THEN 0 ELSE 2 END")
                ->orderByDesc('stock.upload_id')->orderByDesc('stock.id');
            foreach ($stockRows->cursor(['stock.id', 'stock.sfcode', 'stock.iddenom', 'stock.serial_start', 'stock.serial_end', 'upload.filename']) as $row) {
                $denom = $denomsById->get($row->iddenom);
                if (! $denom) continue;
                $start = (string) $row->serial_start;
                $end = (string) $row->serial_end;
                if (strlen($start) === 16 && str_starts_with($start, '0') && str_starts_with($end, '0')) {
                    $start = substr($start, 1); $end = substr($end, 1);
                }
                $product = DenomGrouping::infer($denom)['product'];
                if (! ctype_digit($start) || ! ctype_digit($end) || strlen($start) !== strlen($end)
                    || ($product === 'PV' && strlen($start) !== 12) || ($product === 'SA' && strlen($start) !== 15)
                    || $this->compareSerials($start, $end) > 0) continue;
                $manual = str_starts_with(strtoupper((string) $row->filename), 'MANUAL:');
                $candidates[] = [(string) $row->sfcode, (string) $row->iddenom, $start, $end,
                    $manual ? 'Add Label SN' : 'Upload Stok SN Sales', $manual ? 0 : 2, '', (int) $row->id];
            }

            usort($candidates, fn ($a, $b) => $a[5] <=> $b[5]
                ?: strcmp($b[6], $a[6]) ?: $b[7] <=> $a[7]);
            // Assign ownership with a sweep line. Subtracting each candidate
            // from a repeatedly re-sorted coverage list was quadratic on the
            // 100k+ SF Masuk rows and caused the page request to time out.
            $eventsByLength = [];
            foreach ($candidates as $rank => $candidate) {
                $length = strlen($candidate[2]);
                $start = (int) $candidate[2];
                $endExclusive = (int) $candidate[3] + 1;
                $eventsByLength[$length][] = [$start, $rank, 1];
                $eventsByLength[$length][] = [$endExclusive, $rank, 0];
            }

            $uniqueRanges = [];
            foreach ($eventsByLength as $length => $events) {
                usort($events, fn ($a, $b) => $a[0] <=> $b[0]);
                $queue = new \SplPriorityQueue();
                $queue->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
                $active = [];
                $previousPosition = null;
                $lastWinner = null;
                $lastOutputIndex = null;
                $lastSegmentEnd = null;
                $eventCount = count($events);

                for ($index = 0; $index < $eventCount;) {
                    $position = $events[$index][0];
                    if ($previousPosition !== null && $previousPosition < $position) {
                        while (! $queue->isEmpty()) {
                            $top = $queue->top();
                            if (! empty($active[$top['data']])) break;
                            $queue->extract();
                        }
                        if (! $queue->isEmpty()) {
                            $winner = (int) $queue->top()['data'];
                            $segmentStart = $previousPosition;
                            $segmentEnd = $position - 1;
                            if ($lastWinner === $winner && $lastSegmentEnd + 1 === $segmentStart) {
                                $serialEnd = str_pad((string) $segmentEnd, (int) $length, '0', STR_PAD_LEFT);
                                $uniqueRanges[$lastOutputIndex]['serial_end'] = $serialEnd;
                                $uniqueRanges[$lastOutputIndex]['qty'] += (int) $this->serialRangeCount(
                                    str_pad((string) $segmentStart, (int) $length, '0', STR_PAD_LEFT),
                                    $serialEnd,
                                );
                            } else {
                                $candidate = $candidates[$winner];
                                $serialStart = str_pad((string) $segmentStart, (int) $length, '0', STR_PAD_LEFT);
                                $serialEnd = str_pad((string) $segmentEnd, (int) $length, '0', STR_PAD_LEFT);
                                $uniqueRanges[] = ['sfcode' => $candidate[0], 'iddenom' => $candidate[1],
                                    'serial_start' => $serialStart, 'serial_end' => $serialEnd, 'source' => $candidate[4],
                                    'qty' => (int) $this->serialRangeCount($serialStart, $serialEnd)];
                                $lastOutputIndex = array_key_last($uniqueRanges);
                            }
                            $lastWinner = $winner;
                            $lastSegmentEnd = $segmentEnd;
                        }
                    }

                    while ($index < $eventCount && $events[$index][0] === $position) {
                        [, $rank, $isStart] = $events[$index++];
                        if ($isStart) {
                            $active[$rank] = true;
                            $queue->insert($rank, -$rank);
                        } else {
                            unset($active[$rank]);
                        }
                    }
                    $previousPosition = $position;
                }

                unset($eventsByLength[$length], $events, $queue, $active);
            }
            unset($eventsByLength, $candidates);
            return $uniqueRanges;
        });

        $ranges = collect($snapshot)->map(function ($range) use ($salesById, $denomsById) {
            $sf = $salesById->get($range['sfcode']); $denom = $denomsById->get($range['iddenom']);
            $range['namasf'] = $sf->namasf ?? $range['sfcode']; $range['idtap'] = $sf->idtap ?? '—';
            $range['denom'] = $denom->denom ?? $range['iddenom'];
            $range['product'] = $denom ? DenomGrouping::infer($denom)['product'] : '—';
            return $range;
        })->filter(fn ($range) => $salesById->has($range['sfcode']))
            ->when($filters['sf'] !== '', fn ($rows) => $rows->where('sfcode', $filters['sf']))
            ->when($filters['product'] !== '', fn ($rows) => $rows->where('product', $filters['product']))
            ->when($filters['iddenom'] !== '', fn ($rows) => $rows->where('iddenom', $filters['iddenom']))
            ->when($filters['source'] !== '', fn ($rows) => $rows->where('source', $filters['source']))
            ->when($filters['sn'] !== '', function ($rows) use ($filters) {
                $needle = $filters['sn'];
                if (strlen($needle) === 16 && str_starts_with($needle, '0')) $needle = substr($needle, 1);
                return $rows->filter(fn ($range) => strlen($needle) === strlen($range['serial_start'])
                    && $this->compareSerials($needle, $range['serial_start']) >= 0 && $this->compareSerials($needle, $range['serial_end']) <= 0)
                    ->map(function ($range) use ($needle) { $range['serial_start'] = $needle; $range['serial_end'] = $needle; $range['qty'] = 1; return $range; });
            })->values();

        $ranges = $ranges->sort(fn ($a, $b) => strnatcasecmp($a['namasf'], $b['namasf'])
            ?: strnatcasecmp($a['denom'], $b['denom'])
            ?: $this->compareSerials($a['serial_start'], $b['serial_start']))->values();

        $summary = $ranges->groupBy('sfcode')->map(function ($rows, $sfcode) {
            $first = $rows->first();
            return (object) ['sfcode' => $sfcode, 'namasf' => $first['namasf'], 'idtap' => $first['idtap'],
                'range_count' => $rows->count(), 'serial_count' => (int) $rows->sum('qty')];
        })->values();
        $summarySort = $filters['sort'];
        $summaryDirection = $filters['direction'];
        $summary = $summary->sort(function ($left, $right) use ($summarySort, $summaryDirection) {
            $comparison = match ($summarySort) {
                'tap' => strnatcasecmp((string) $left->idtap, (string) $right->idtap)
                    ?: strnatcasecmp($left->namasf, $right->namasf),
                'total' => $left->serial_count <=> $right->serial_count
                    ?: strnatcasecmp($left->namasf, $right->namasf),
                default => strnatcasecmp($left->namasf, $right->namasf)
                    ?: strnatcasecmp((string) $left->idtap, (string) $right->idtap),
            };
            return $summaryDirection === 'desc' ? -$comparison : $comparison;
        })->values();
        $summaryTotals = (object) ['sales_forces' => $summary->count(), 'ranges' => (int) $summary->sum('range_count'), 'serials' => (int) $summary->sum('serial_count')];

        $summaryPerPage = 10;
        $summaryPage = max(1, (int) $request->query('page', 1));
        $summaryPaginator = new LengthAwarePaginator(
            $summary->forPage($summaryPage, $summaryPerPage)->values(), $summary->count(), $summaryPerPage, $summaryPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->except(['page', 'sn_page'])]
        );

        $detailSf = (string) $filters['detail_sf'];
        $detailRanges = $detailSf === '' ? collect() : $ranges->where('sfcode', $detailSf)->values();
        $detailTotal = (int) $detailRanges->sum('qty'); $perPage = 100; $page = max(1, (int) $request->query('sn_page', 1));
        $offset = ($page - 1) * $perPage; $items = [];
        foreach ($detailRanges as $range) {
            $qty = (int) $range['qty'];
            if ($offset >= $qty) { $offset -= $qty; continue; }
            $start = str_pad((string) ((int) $range['serial_start'] + $offset), strlen($range['serial_start']), '0', STR_PAD_LEFT);
            $offset = 0;
            while (count($items) < $perPage && $this->compareSerials($start, $range['serial_end']) <= 0) {
                $items[] = (object) ['sn' => $start, 'iddenom' => $range['iddenom'], 'denom' => $range['denom'],
                    'product' => $range['product'], 'sfcode' => $range['sfcode'], 'namasf' => $range['namasf'], 'idtap' => $range['idtap'], 'source' => $range['source']];
                $start = $this->incrementSerial($start);
            }
            if (count($items) >= $perPage) break;
        }
        $serials = new LengthAwarePaginator($items, $detailTotal, $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'sn_page', 'query' => $request->except('sn_page'),
        ]);

        return view('surya.product-tracking.sf-stock', compact(
            'filters', 'salesForces', 'eligibleDenoms', 'summaryPaginator', 'summaryTotals', 'serials', 'detailTotal', 'perPage', 'detailSf'
        ));
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
        $serialConflicts = app(SalesStockSerialConflict::class);
        $serialConflicts->assertFileRangesDoNotOverlap($rows);
        $serialConflicts->lock();
        try {
        DB::transaction(function () use ($data, $rows, $serialConflicts) {
            foreach ($rows as $row) {
                $serialConflicts->assertAvailable($row['serial_start'], $row['serial_end'], null, null, false);
            }
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
        } finally {
            $serialConflicts->unlock();
        }

        $message = number_format(count($rows)).' baris stok SN Sales berhasil diunggah.';
        return redirect()->route('surya.product-tracking.imports', ['kind' => 'sales-stock'])->with('success', $message);
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
            'outlet_column' => [$record->kind === 'scan' ? 'required' : 'nullable', $columnRule],
            'sf_column' => ['nullable', Rule::in(array_map('strval', array_keys($headers)))],
            'date_column' => ['nullable', Rule::in(array_map('strval', array_keys($headers)))],
            'product_column' => ['nullable', Rule::in(array_map('strval', array_keys($headers)))],
        ]);
        if (empty($mapping['serial_column'])) throw ValidationException::withMessages(['serial_column' => 'Pilih kolom SN.']);
        if ($record->kind === 'scan' && empty($mapping['outlet_column'])) {
            throw ValidationException::withMessages(['outlet_column' => 'Pilih kolom ID Outlet untuk data ST Digipos.']);
        }
        if ($record->kind !== 'warehouse' && empty($mapping['date_column'])) {
            throw ValidationException::withMessages(['date_column' => 'Pilih kolom tanggal agar tanggal scan/redeem tercatat otomatis.']);
        }
        $path = Storage::disk('local')->path($record->storage_path);
        $now = now();
        $count = 0;
        $detectedScanDate = $record->scan_date;
        $mixedSoDates = false;
        $batch = [];
        $range = null;
        $appendRange = function (array $rangeToStore) use (&$batch, $record, $now): void {
            $batch[] = $this->digiposRangeRow($record->id, $record->kind, $rangeToStore, $now);
            if (count($batch) >= 500) {
                DB::table('product_tracking_digipos')->insert($batch);
                $batch = [];
            }
        };
        try {
            foreach ($reader->rows($path, $headers) as $rowNumber => $values) {
                $serial = $this->mappedValue($values, $mapping['serial_column'] ?? null);
                if (in_array($serial, ['-', '—'], true)) $serial = '';
                $outletId = str_starts_with($record->kind, 'so-') ? '' : $this->mappedValue($values, $mapping['outlet_column'] ?? null);
                if ($serial === '' && $outletId === '') continue;
                if ($outletId !== '' && (strlen($outletId) > 50 || ! preg_match('/^[A-Za-z0-9_-]+$/', $outletId))) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: format ID Outlet tidak valid. Simpan ID sebagai teks."]);
                }
                if ($serial === '') throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: SN kosong."]);
                if (! preg_match('/^\d{1,24}$/', $serial)) throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: SN harus berupa angka maksimal 24 digit."]);
                if ($record->kind === 'so-pv' && strlen($serial) !== 12) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: Upload SO PV harus berisi SN 12 digit."]);
                }
                if ($record->kind === 'so-sa' && ! preg_match('/^(?:[1-9]\d{14}|0\d{15})$/', $serial)) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: Upload SO SA harus berisi SN 15 digit, atau 16 digit dengan 0 di depan."]);
                }
                // Store SA SO serials in the canonical 15-digit form. A leading zero
                // is only a file-format variant and must not create a second redeem.
                if ($record->kind === 'so-sa' && strlen($serial) === 16) $serial = substr($serial, 1);
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
                if ($record->kind === 'scan' && $detectedScanDate === null && $soldAt !== null) {
                    $detectedScanDate = $soldAt;
                } elseif ($record->kind === 'scan' && $soldAt !== null && $detectedScanDate !== $soldAt) {
                    throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: tanggal berbeda dari baris sebelumnya. Satu file upload harus memakai satu tanggal yang sama."]);
                } elseif (str_starts_with($record->kind, 'so-') && ! $mixedSoDates && $detectedScanDate === null && $soldAt !== null) {
                    $detectedScanDate = $soldAt;
                } elseif (str_starts_with($record->kind, 'so-') && $soldAt !== null && $detectedScanDate !== $soldAt) {
                    $detectedScanDate = null;
                    $mixedSoDates = true;
                }
                $sf = mb_substr($this->mappedValue($values, $mapping['sf_column'] ?? null), 0, 150);
                $product = mb_substr($this->mappedValue($values, $mapping['product_column'] ?? null), 0, 200);
                $date = $soldAt ?? ($record->kind !== 'warehouse' ? $detectedScanDate : null);
                $key = implode("\x1f", [$date ?? '', $record->kind === 'scan' ? $outletId : '', $sf, $product]);
                if ($range !== null && $key === $range['key'] && $this->incrementSerial($range['serial_end']) === $serial) {
                    $range['serial_end'] = $serial;
                    $range['serial_count']++;
                } else {
                    if ($range !== null) $appendRange($range);
                    $range = ['key' => $key, 'scan_date' => $record->kind !== 'warehouse' ? $date : null, 'serial_start' => $serial, 'serial_end' => $serial, 'serial_count' => 1, 'id_outlet' => $outletId !== '' ? $outletId : null, 'sf' => $sf, 'sold_at' => $date, 'product_name' => $product, 'source_row' => $rowNumber];
                }
                $count++;
            }
            if ($range !== null) $appendRange($range);
            if ($batch !== []) DB::table('product_tracking_digipos')->insert($batch);
            if ($count === 0) throw ValidationException::withMessages(['file' => 'File tidak berisi baris transaksi yang dapat diimpor.']);
            if ($record->kind === 'scan' && $detectedScanDate === null) {
                throw ValidationException::withMessages(['date_column' => 'Kolom tanggal pada file tidak berisi tanggal yang dapat dibaca.']);
            }
            DB::table('product_tracking_uploads')->where('id', $record->id)->update([
                'status' => 'complete', 'scan_date' => $detectedScanDate, 'row_count' => $count, 'storage_path' => null, 'updated_at' => $now,
            ]);
            if ($record->kind === 'scan') {
                $this->invalidateDigiposScanCoverageRanges('scan');
            }
        } catch (\Throwable $exception) {
            DB::table('product_tracking_digipos')->where('upload_id', $record->id)->delete();
            throw $exception;
        }
        Storage::disk('local')->delete($record->storage_path);
        $label = str_starts_with($record->kind, 'so-') ? 'baris SO' : ($record->kind === 'scan' ? 'baris ST Digipos' : 'baris Digipos');
        return redirect()->route('surya.product-tracking.imports', ['kind' => $record->kind])->with('success', number_format($count).' '.$label.' berhasil diimpor.');
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
        $sales = collect(); $scans = collect(); $mobile = collect(); $sfMatches = collect();
        if ($queryValue !== '') {
            abort_if(strlen($queryValue) > 24 || ! preg_match('/^\d{9,16}$/', $queryValue), 422, 'Masukkan SN atau nomor 9–16 digit.');
            if (in_array(strlen($queryValue), [12, 15, 16], true)) {
                [, $sfMatches] = $this->sfOwnershipMatches($queryValue);
            }
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
        return view('surya.product-tracking.tracking', compact('queryValue', 'sales', 'scans', 'mobile', 'sfMatches'));
    }

    private function sfOwnershipMatches(string $raw): array
    {
        $raw = trim($raw);
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
                    $uploaded->whereNotExists(function ($override) use ($canonical, $isPv) {
                        $override->selectRaw('1')
                            ->from('product_tracking_sales_stock_ranges as correction')
                            ->join('product_tracking_sales_stock_uploads as correction_upload', 'correction_upload.id', '=', 'correction.upload_id')
                            ->where('stock_upload.filename', 'not like', 'MANUAL:%')
                            ->whereColumn('correction.iddenom', 'stock.iddenom')
                            ->where('correction_upload.filename', 'like', 'MANUAL:%')
                            ->whereRaw('CHAR_LENGTH(correction.serial_start) IN ('.($isPv ? '12' : '15, 16').')')
                            ->whereRaw('CAST(correction.serial_start AS DECIMAL(24,0)) <= ? AND CAST(correction.serial_end AS DECIMAL(24,0)) >= ?', [$canonical, $canonical]);
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
                $admin->whereNotExists(function ($override) use ($canonical, $isPv) {
                    $override->selectRaw('1')
                        ->from('product_tracking_sales_stock_ranges as correction')
                        ->join('product_tracking_sales_stock_uploads as correction_upload', 'correction_upload.id', '=', 'correction.upload_id')
                        ->whereColumn('correction.iddenom', 'm.iddenom')
                        ->where('correction_upload.filename', 'like', 'MANUAL:%')
                        ->whereRaw('CHAR_LENGTH(correction.serial_start) IN ('.($isPv ? '12' : '15, 16').')')
                        ->whereRaw('CAST(correction.serial_start AS DECIMAL(24,0)) <= ? AND CAST(correction.serial_end AS DECIMAL(24,0)) >= ?', [$canonical, $canonical]);
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

        return [$serial, $matches, $error];
    }

    public function sfStockLookup(Request $request)
    {
        $this->requireEditor();
        $tab = 'add';
        $serial = '';
        $matches = collect();
        $error = null;

        $salesForces = collect();
        $eligibleDenoms = collect();
        $recentManual = null;
        $editingManual = null;
        if ($tab === 'add') {
            $sfQuery = DB::table('idsf')->whereRaw("UPPER(idsf) LIKE 'SF%' ")
                ->whereRaw("UPPER(TRIM(namasf)) NOT LIKE 'N/A%'");
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
                $recentManual = $manualQuery->orderByDesc('source.updated_at')->orderByDesc('stock.id')->paginate(10, [
                    'stock.id', 'stock.sfcode', 'owner.namasf', 'owner.idtap', 'stock.iddenom', 'd.denom',
                    'stock.serial_start', 'stock.serial_end', 'stock.qty', 'stock.keterangan', 'source.username', 'source.updated_at as created_at',
                ], 'manual_page')->withQueryString();
                $recentManual->getCollection()->transform(function ($row) use ($eligibleDenoms) {
                    $denom = $eligibleDenoms->firstWhere('iddenom', $row->iddenom);
                    $isSa = $denom && DenomGrouping::infer($denom)['product'] === 'SA';
                    $row->keterangan = $this->previousSfLocationNote($row->sfcode, $row->iddenom, $row->serial_start, $row->serial_end, $isSa);
                    return $row;
                });
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

        $previousLocationNote = $this->previousSfLocationNote($data['sfcode'], $data['iddenom'], $start, $end, $isSa);
        $serialConflicts = app(SalesStockSerialConflict::class);
        $serialConflicts->lock();
        try {
        DB::transaction(function () use ($data, $sf, $start, $end, $rangeQty, $previousLocationNote, $serialConflicts) {
            $serialConflicts->assertAvailable($start, $end);
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
                'keterangan' => $previousLocationNote,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
        } finally {
            $serialConflicts->unlock();
        }

        return redirect()->route('surya.product-tracking.sf-stock-lookup', ['tab' => 'add'])
            ->with('success', number_format($rangeQty)." SN berhasil dipetakan ke {$sf->idsf}; saldo stok tidak berubah.");
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

        $previousLocationNote = $this->previousSfLocationNote($data['sfcode'], $data['iddenom'], $start, $end, $isSa);
        $serialConflicts = app(SalesStockSerialConflict::class);
        $serialConflicts->lock();
        try {
        DB::transaction(function () use ($record, $sf, $data, $start, $end, $rangeQty, $previousLocationNote, $serialConflicts, $range) {
            $serialConflicts->assertAvailable($start, $end, (int) $range);
            DB::table('product_tracking_sales_stock_ranges')->where('id', $record->id)->update([
                'sfcode' => $sf->idsf, 'tap' => $sf->idtap,
                'cluster' => $this->trackingClusterForTap((string) $sf->idtap),
                'iddenom' => $data['iddenom'], 'serial_start' => $start, 'serial_end' => $end,
                'qty' => $rangeQty, 'updated_at' => now(),
                'keterangan' => $previousLocationNote,
            ]);
            DB::table('product_tracking_sales_stock_uploads')->where('id', $record->upload_id)->update([
                'username' => auth()->user()->username, 'updated_at' => now(),
            ]);
        });
        } finally {
            $serialConflicts->unlock();
        }

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

    /** Report only the last known holder of each overlapping SN segment before manual reassignment. */
    private function previousSfLocationNote(string $newSf, string $iddenom, string $start, string $end, bool $isSa): ?string
    {
        $widths = $isSa ? [15, 16] : [12];
        $targetStart = (int) ltrim($start, '0');
        $targetEnd = (int) ltrim($end, '0');
        $pattern = $isSa ? '[0-9]{15,16}' : '[0-9]{12}';
        $cacheKey = $iddenom.'|'.($isSa ? 'SA' : 'PV');
        if (! isset($this->sfStockOwnershipHistoryCache[$cacheKey])) {
            $receipts = DB::table('masuksf as incoming')
                ->where('incoming.iddenom', $iddenom)
                ->whereRaw('incoming.sn REGEXP ?', [$pattern])
                ->get(['incoming.idsf', 'incoming.sn', 'incoming.tgl', 'incoming.idmasuk']);
            $returns = DB::table('retursf as returned')
                ->where('returned.iddenom', $iddenom)
                ->where(function ($query) use ($pattern) {
                    $query->whereRaw('COALESCE(NULLIF(returned.sn_awal, ?), returned.sn) REGEXP ?', ['', $pattern])
                        ->orWhereRaw('COALESCE(NULLIF(returned.sn_akhir, ?), returned.sn) REGEXP ?', ['', $pattern]);
                })
                ->get(['returned.idsf', 'returned.sn', 'returned.sn_awal', 'returned.sn_akhir', 'returned.tgl', 'returned.idretur']);
            $this->sfStockOwnershipHistoryCache[$cacheKey] = [$receipts, $returns];
        }
        [$receipts, $returns] = $this->sfStockOwnershipHistoryCache[$cacheKey];

        $events = [];
        $appendRanges = function (string $sfCode, string $rawRanges, $date, int $id, string $type) use (&$events, $widths, $isSa, $targetStart, $targetEnd): void {
            $parts = preg_split('/\s*[,;|\n]+\s*/u', trim($rawRanges), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($parts as $part) {
                if (preg_match('/^(\d+)\s*[-–—]\s*(\d+)$/u', trim($part), $match)) {
                    [$serialStart, $serialEnd] = [$match[1], $match[2]];
                } elseif (preg_match('/^\d+$/', trim($part))) {
                    $serialStart = $serialEnd = trim($part);
                } else {
                    continue;
                }
                if (! in_array(strlen($serialStart), $widths, true) || strlen($serialStart) !== strlen($serialEnd)) continue;
                if ($isSa && strlen($serialStart) === 16 && (! str_starts_with($serialStart, '0') || ! str_starts_with($serialEnd, '0'))) continue;
                $low = (int) ltrim($serialStart, '0');
                $high = (int) ltrim($serialEnd, '0');
                if ($high < $low || $low > $targetEnd || $high < $targetStart) continue;
                $events[] = [
                    'sfcode' => $sfCode,
                    'start' => max($targetStart, $low),
                    'end' => min($targetEnd, $high),
                    'date' => strtotime((string) $date) ?: 0,
                    'type' => $type,
                    'id' => $id,
                ];
            }
        };

        foreach ($receipts as $receipt) {
            $appendRanges((string) $receipt->idsf, (string) $receipt->sn, $receipt->tgl, (int) $receipt->idmasuk, 'receipt');
        }
        foreach ($returns as $return) {
            $returnStart = trim((string) ($return->sn_awal ?? ''));
            $returnEnd = trim((string) ($return->sn_akhir ?? ''));
            $rawRange = $returnStart !== '' ? ($returnEnd !== '' ? $returnStart.'-'.$returnEnd : $returnStart) : (string) $return->sn;
            $appendRanges((string) $return->idsf, $rawRange, $return->tgl, (int) $return->idretur, 'return');
        }
        if ($events === []) return null;

        $boundaries = [$targetStart, $targetEnd + 1];
        foreach ($events as $event) {
            $boundaries[] = $event['start'];
            $boundaries[] = $event['end'] + 1;
        }
        $boundaries = array_values(array_unique($boundaries));
        sort($boundaries, SORT_NUMERIC);
        $latestSegments = [];
        foreach (array_slice($boundaries, 0, -1) as $index => $segmentStart) {
            $segmentEnd = $boundaries[$index + 1] - 1;
            $active = array_filter($events, fn ($event) => $event['start'] <= $segmentStart && $event['end'] >= $segmentEnd);
            if ($active === []) continue;
            usort($active, fn ($a, $b) => ($a['date'] <=> $b['date'])
                ?: (($a['type'] === 'receipt' ? 1 : 0) <=> ($b['type'] === 'receipt' ? 1 : 0))
                ?: ($a['id'] <=> $b['id']));
            $latest = end($active);
            if ($latest['type'] === 'receipt' && $latest['sfcode'] === $newSf) continue;
            $latestSegments[] = array_merge($latest, ['start' => $segmentStart, 'end' => $segmentEnd]);
        }
        if ($latestSegments === []) return null;

        $sfCodes = collect($latestSegments)->pluck('sfcode')->unique()->all();
        $names = DB::table('idsf')->whereIn('idsf', $sfCodes)->pluck('namasf', 'idsf');
        $requiresReturn = false;
        $labels = collect($latestSegments)->map(function ($segment) use ($names, &$requiresReturn, $start) {
            $width = strlen($start);
            $serialRange = str_pad((string) $segment['start'], $width, '0', STR_PAD_LEFT)
                .'-'.str_pad((string) $segment['end'], $width, '0', STR_PAD_LEFT);
            $name = $names[$segment['sfcode']] ?? $segment['sfcode'];
            if ($segment['type'] === 'return') return 'TAP setelah retur dari '.$name.' ('.$segment['sfcode'].'; '.$serialRange.')';
            $requiresReturn = true;
            return $name.' ('.$segment['sfcode'].'; '.$serialRange.')';
        })->unique()->values();

        return 'Lokasi terakhir sebelum mapping: '.$labels->implode('; ').'. '
            .($requiresReturn ? 'Retur admin masih diperlukan untuk SN yang masih tercatat di SF.' : 'SN tersebut sudah tercatat diretur ke TAP.');
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

    private function mergeSerialRanges(iterable $ranges): array
    {
        $ranges = collect($ranges)
            ->map(fn ($range) => [
                'serial_start' => (string) (is_array($range) ? ($range['serial_start'] ?? '') : ($range->serial_start ?? '')),
                'serial_end' => (string) (is_array($range) ? ($range['serial_end'] ?? '') : ($range->serial_end ?? '')),
            ])
            ->filter(fn ($range) => $range['serial_start'] !== '' && $range['serial_end'] !== '' && $this->compareSerials($range['serial_start'], $range['serial_end']) <= 0)
            ->sort(fn ($left, $right) => $this->compareSerials($left['serial_start'], $right['serial_start']) ?: $this->compareSerials($left['serial_end'], $right['serial_end']))
            ->values();

        $merged = [];
        foreach ($ranges as $range) {
            $lastIndex = count($merged) - 1;
            if ($lastIndex >= 0 && $this->compareSerials($range['serial_start'], $merged[$lastIndex]['serial_end']) <= 0) {
                if ($this->compareSerials($range['serial_end'], $merged[$lastIndex]['serial_end']) > 0) {
                    $merged[$lastIndex]['serial_end'] = $range['serial_end'];
                }
                continue;
            }
            $merged[] = $range;
        }

        return $merged;
    }

    private function allCompleteDigiposCoverageRanges(): array
    {
        $latestCompleteScanUpload = DB::table('product_tracking_uploads')
            ->where('kind', 'scan')->where('status', 'complete')->max('id');
        $canonicalSerial = static fn ($serial): string => strlen((string) $serial) === 16 && str_starts_with((string) $serial, '0')
            ? substr((string) $serial, 1) : (string) $serial;

        return Cache::remember(
            'product-tracking:sold-all-digipos-ranges:v2:'.($latestCompleteScanUpload ?: 'none'),
            now()->addDay(),
            fn () => $this->mergeSerialRangesFromQuery(DB::table('product_tracking_digipos as digipos')
                ->join('product_tracking_uploads as upload', 'upload.id', '=', 'digipos.upload_id')
                ->where('upload.kind', 'scan')->where('upload.status', 'complete'), $canonicalSerial)
        );
    }

    private function invalidateDigiposScanCoverageRanges(string $kind): void
    {
        if ($kind !== 'scan') {
            return;
        }

        $latestCompleteScanUpload = DB::table('product_tracking_uploads')
            ->where('kind', 'scan')->where('status', 'complete')->max('id');

        Cache::forget('product-tracking:sold-all-digipos-ranges:v2:'.($latestCompleteScanUpload ?: 'none'));
    }

    private function splitSerialRangeByCoverage(string $start, string $end, array $coverageRanges): array
    {
        $pieces = [];
        $cursor = $start;
        foreach ($coverageRanges as $coverage) {
            if ($this->compareSerials($coverage['serial_end'], $cursor) < 0) continue;
            if ($this->compareSerials($coverage['serial_start'], $end) > 0) break;

            if ($this->compareSerials($coverage['serial_start'], $cursor) > 0) {
                $gapEnd = $this->subtractOneFromSerial($coverage['serial_start']);
                if ($this->compareSerials($cursor, $gapEnd) <= 0) {
                    $pieces[] = ['start' => $cursor, 'end' => $gapEnd, 'in_digipos' => false];
                }
            }

            $overlapStart = $this->compareSerials($cursor, $coverage['serial_start']) >= 0 ? $cursor : $coverage['serial_start'];
            $overlapEnd = $this->compareSerials($end, $coverage['serial_end']) <= 0 ? $end : $coverage['serial_end'];
            if ($this->compareSerials($overlapStart, $overlapEnd) <= 0) {
                $pieces[] = ['start' => $overlapStart, 'end' => $overlapEnd, 'in_digipos' => true];
                if ($this->compareSerials($overlapEnd, $end) >= 0) return $pieces;
                $cursor = $this->incrementSerial($overlapEnd);
            }
        }

        if ($this->compareSerials($cursor, $end) <= 0) {
            $pieces[] = ['start' => $cursor, 'end' => $end, 'in_digipos' => false];
        }

        return $pieces;
    }

    private function mergeSerialRangesFromQuery($query, callable $canonicalSerial): array
    {
        $merged = [];
        $rows = $query
            ->orderByRaw('CAST(digipos.serial_start AS DECIMAL(24,0))')
            ->orderByRaw('CAST(digipos.serial_end AS DECIMAL(24,0))')
            ->select(['digipos.serial_start', 'digipos.serial_end'])
            ->cursor();

        foreach ($rows as $row) {
            $start = $canonicalSerial($row->serial_start);
            $end = $canonicalSerial($row->serial_end);
            if ($start === '' || $end === '' || $this->compareSerials($start, $end) > 0) continue;

            $lastIndex = count($merged) - 1;
            if ($lastIndex >= 0 && $this->compareSerials($start, $merged[$lastIndex]['serial_end']) <= 0) {
                if ($this->compareSerials($end, $merged[$lastIndex]['serial_end']) > 0) {
                    $merged[$lastIndex]['serial_end'] = $end;
                }
                continue;
            }

            $merged[] = ['serial_start' => $start, 'serial_end' => $end];
        }

        return $merged;
    }

    private function serialCoverageCount(string $start, string $end, array $ranges): int
    {
        $low = 0;
        $high = count($ranges);
        while ($low < $high) {
            $middle = intdiv($low + $high, 2);
            if ($this->compareSerials($ranges[$middle]['serial_end'], $start) < 0) $low = $middle + 1;
            else $high = $middle;
        }

        $covered = 0;
        for ($index = $low, $rangeCount = count($ranges); $index < $rangeCount; $index++) {
            $range = $ranges[$index];
            if ($this->compareSerials($range['serial_start'], $end) > 0) break;
            $overlapStart = $this->compareSerials($start, $range['serial_start']) >= 0 ? $start : $range['serial_start'];
            $overlapEnd = $this->compareSerials($end, $range['serial_end']) <= 0 ? $end : $range['serial_end'];
            if ($this->compareSerials($overlapStart, $overlapEnd) <= 0) {
                $covered += (int) $this->serialRangeCount($overlapStart, $overlapEnd);
            }
        }

        return $covered;
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


}
