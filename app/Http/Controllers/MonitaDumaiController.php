<?php

namespace App\Http\Controllers;

use App\Exports\SuryaSalesProductExport;
use App\Exports\SuryaAdminSalesExport;
use App\Exports\SuryaVisitExport;
use App\Exports\SuryaWorkbookExport;
use App\Exports\SuryaOutletDetailExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Services\OptimizedImageStorage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class MonitaDumaiController extends Controller
{
    public function index(Request $request)
    {
        $showLeaderDashboard = $this->hasSuryaAccess($request);

        if (!$showLeaderDashboard) {
            return redirect('/');
        }

        $this->approveExpiredVisitCoordinates();
        $visitDate = $request->date('visit_date')?->toDateString() ?? now()->toDateString();
        $dashboard = $this->buildLeaderDashboard($visitDate);

        return response()
            ->view('monitadumai.index', array_merge(
                compact('dashboard', 'showLeaderDashboard', 'visitDate'),
                ['clusterCoverage' => $dashboard['clusterCoverage']['groups'] ?? [], 'summary' => $dashboard['summary'] ?? []]
            ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function approveExpiredVisitCoordinates(): void
    {
        $pendingVisits = DB::table('surya_visits')
            ->where('gps_status', 'pending_admin')
            ->whereNotNull('photo_front_path')
            ->whereNotNull('photo_inside_path')
            ->where('visited_at', '<=', now('Asia/Jakarta')->subSeconds(15))
            ->get(['id', 'id_outlet', 'latitude', 'longitude']);

        foreach ($pendingVisits as $visit) {
            $updated = DB::table('surya_visits')
                ->where('id', $visit->id)
                ->where('gps_status', 'pending_admin')
                ->update(['gps_status' => 'approved']);

            if ($updated) {
                DB::table('appsdumais')->where('id_outlet', $visit->id_outlet)->update([
                    'latitude' => $visit->latitude,
                    'longitude' => $visit->longitude,
                ]);
            }
        }
    }

    public function metricsUploadForm(Request $request)
    {
        abort_unless($this->isSuryaAdminSuper(), 403);

        return view('monitadumai.metrics-upload');
    }

    public function outletProfile(string $idOutlet)
    {
        abort_unless($this->isSuryaAdminSuper(), 403);

        $outlet = DB::table('appsdumais')
            ->where('id_outlet', $idOutlet)
            ->first(['id_outlet', 'nama_outlet', 'kecamatan', 'tap', 'sf', 'pjp']);
        abort_unless($outlet, 404);

        return response()->json([
            'outlet' => $outlet,
            'sales' => DB::table('idsf')->where('idtap', $outlet->tap)
                ->orderBy('namasf')->get(['idsf', 'namasf']),
            'days' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu', 'UNMAPPING'],
        ]);
    }

    public function updateOutletProfile(Request $request, string $idOutlet)
    {
        abort_unless($this->isSuryaAdminSuper(), 403);

        $outlet = DB::table('appsdumais')->where('id_outlet', $idOutlet)->first();
        abort_unless($outlet, 404);
        $validated = $request->validate([
            'sf' => ['required', 'string', 'max:150'],
            'pjp' => ['required', 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu,UNMAPPING'],
        ]);
        $validSales = strtoupper($validated['sf']) === 'UNMAPPING'
            || DB::table('idsf')->where('idtap', $outlet->tap)->where('namasf', $validated['sf'])->exists();
        if (! $validSales) {
            return back()->withErrors(['sf' => 'Sales Force tidak terdaftar pada TAP outlet tersebut.'])->withInput();
        }

        DB::table('appsdumais')->where('id_outlet', $idOutlet)->update([
            'sf' => $validated['sf'],
            'pjp' => $validated['pjp'],
        ]);

        return response()->json(['message' => "Profil outlet {$idOutlet} berhasil diperbarui."]);
    }

    public function outletDetail(Request $request)
    {
        abort_unless($this->hasSuryaAccess($request), 403);

        $tapScopes = $this->suryaTapScopes();
        $taps = DB::table('appsdumais')
            ->whereNotNull('tap')
            ->whereNotNull('pjp')
            ->whereRaw("TRIM(pjp) <> ''")
            ->whereRaw("UPPER(TRIM(pjp)) <> 'UNMAPPING'")
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('tap', $tapScopes))
            ->distinct()
            ->orderBy('tap')
            ->pluck('tap');
        $outletCount = $this->outletDetailBaseQuery($tapScopes)->count();
        $selectedTap = trim((string) $request->input('tap'));
        $search = trim((string) $request->input('q'));
        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $canEditOutlet = $this->isSuryaAdminSuper();
        $salesCutoff = $this->outletSalesCutoff($request);
        $salesCutoffDate = $salesCutoff->translatedFormat('d M Y');
        $salesCutoffValue = $salesCutoff->toDateString();
        $salesCutoffMax = now('Asia/Jakarta')->toDateString();
        $outlets = $this->outletDetailBaseQuery($tapScopes, $selectedTap, $search)
            ->select('outlet.id_outlet', 'outlet.nama_outlet', 'outlet.kecamatan', 'outlet.sf', 'outlet.tap', 'outlet.pjp')
            ->orderBy('outlet.nama_outlet')
            ->paginate($perPage)
            ->withQueryString();
        $outlets->setCollection($this->hydrateOutletDetailPage($outlets->getCollection(), $salesCutoff));

        return response()->view('surya.outlet-detail', compact(
            'taps', 'outletCount', 'canEditOutlet', 'salesCutoffDate', 'salesCutoffValue',
            'salesCutoffMax', 'outlets', 'selectedTap', 'search', 'perPage'
        ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function outletDetailData(Request $request)
    {
        abort_unless($this->hasSuryaAccess($request), 403);

        try {
            $tapScopes = $this->suryaTapScopes();
            $selectedTap = trim((string) $request->input('tap'));
            $searchInput = $request->input('search', []);
            $search = trim((string) (is_array($searchInput) ? ($searchInput['value'] ?? '') : $searchInput));
            $base = $this->outletDetailBaseQuery($tapScopes, $selectedTap, $search);
            $recordsTotal = $this->outletDetailBaseQuery($tapScopes)->count();
            $recordsFiltered = (clone $base)->count();

            $length = min(100, max(10, (int) $request->input('length', 25)));
            $start = max(0, (int) $request->input('start', 0));
            $rows = (clone $base)
                ->select('outlet.id_outlet', 'outlet.nama_outlet', 'outlet.kecamatan', 'outlet.sf', 'outlet.tap', 'outlet.pjp')
                ->orderBy('outlet.nama_outlet')
                ->offset($start)
                ->limit($length)
                ->get();

            try {
                $rows = $this->hydrateOutletDetailPage($rows, $this->outletSalesCutoff($request));
            } catch (\Throwable $e) {
                logger()->error('Detail Outlet hydration failed', ['exception' => $e]);
            }

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $rows,
            ]);
        } catch (\Throwable $e) {
            logger()->error('Detail Outlet Ajax failed', ['exception' => $e]);

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    public function outletDetailExport(Request $request)
    {
        abort_unless($this->hasSuryaAccess($request), 403);

        $tapScopes = $this->suryaTapScopes();
        $selectedTap = trim((string) $request->input('tap'));
        $search = trim((string) $request->input('q'));
        $salesCutoff = $this->outletSalesCutoff($request);
        $rows = $this->outletDetailBaseQuery($tapScopes, $selectedTap, $search)
            ->select('outlet.id_outlet', 'outlet.nama_outlet', 'outlet.kecamatan', 'outlet.sf', 'outlet.tap', 'outlet.pjp')
            ->orderBy('outlet.nama_outlet')
            ->get()
            ->chunk(200)
            ->flatMap(fn ($chunk) => $this->hydrateOutletDetailPage($chunk, $salesCutoff))
            ->values();

        return Excel::download(
            new SuryaOutletDetailExport($rows),
            'surya-detail-outlet-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    private function hydrateOutletDetailPage($rows, Carbon $cutoff)
    {
        $pageIds = $rows->pluck('id_outlet')->map(fn ($id) => (string) $id);
        $cutoff = $cutoff->copy()->startOfDay();
        $monthStart = $cutoff->copy()->startOfMonth();
        $previousMonthStart = $monthStart->copy()->subMonth();
        $previousMonthEnd = $previousMonthStart->copy()->endOfMonth();
        $previousComparableEnd = $previousMonthStart->copy()
            ->day(min($cutoff->day, $previousMonthEnd->day));
        $keluar = $this->safeOutletDetailSource('keluarsf', function () use ($monthStart, $cutoff, $pageIds) {
            return DB::table('keluarsf as k')->leftJoin('denom as d', 'd.iddenom', '=', 'k.iddenom')
                ->whereBetween('k.tgl', [$monthStart->toDateString(), $cutoff->toDateString()])
                ->whereIn('k.id_outlet', $pageIds)->select('k.id_outlet')
                ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(d.product_group, 'PV')) IN ('SA', 'SP') THEN k.qty ELSE 0 END) as st_sa")
                ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(d.product_group, 'PV')) = 'PV' THEN k.qty ELSE 0 END) as st_pv")
                ->groupBy('k.id_outlet')->get()->keyBy(fn ($row) => (string) $row->id_outlet);
        });
        $mobile = $this->safeOutletDetailSource('mobile_penjualan', function () use ($monthStart, $cutoff, $pageIds) {
            return DB::table('mobile_penjualan as m')->leftJoin('denom as d', 'd.iddenom', '=', 'm.iddenom')
                ->whereBetween('m.tgl', [$monthStart->toDateString(), $cutoff->toDateString()])
                ->where('m.status', 'approved')->whereIn('m.id_outlet', $pageIds)->select('m.id_outlet')
                ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(d.product_group, 'PV')) IN ('SA', 'SP') THEN m.qty ELSE 0 END) as st_sa")
                ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(d.product_group, 'PV')) = 'PV' THEN m.qty ELSE 0 END) as st_pv")
                ->groupBy('m.id_outlet')->get()->keyBy(fn ($row) => (string) $row->id_outlet);
        });
        // Tetap layani Detail Outlet saat deployment aplikasi selesai lebih dulu
        // daripada migrasi tabel histori. Setelah migrasi tersedia, FM1/M1 langsung
        // dibaca tanpa perlu cache atau restart tambahan.
        $history = collect();
        if (Schema::hasTable('surya_outlet_sales_history')) {
            $history = $this->safeOutletDetailSource('surya_outlet_sales_history', function () use ($pageIds, $previousMonthStart, $previousMonthEnd, $previousComparableEnd) {
                return DB::table('surya_outlet_sales_history')
                    ->whereIn('id_outlet', $pageIds)
                    ->whereBetween('transaction_date', [$previousMonthStart->toDateString(), $previousMonthEnd->toDateString()])
                    ->select('id_outlet')
                    ->selectRaw('SUM(sa) as sa_fm1, SUM(pv) as pv_fm1')
                    ->selectRaw('SUM(CASE WHEN transaction_date <= ? THEN sa ELSE 0 END) as sa_m1', [$previousComparableEnd->toDateString()])
                    ->selectRaw('SUM(CASE WHEN transaction_date <= ? THEN pv ELSE 0 END) as pv_m1', [$previousComparableEnd->toDateString()])
                    ->groupBy('id_outlet')->get()->keyBy(fn ($row) => (string) $row->id_outlet);
            });
        }
        $metrics = $this->safeOutletDetailSource('surya_daily_metrics', function () use ($pageIds) {
            return DB::table('surya_daily_metrics')->whereIn('id_outlet', $pageIds)->orderByDesc('metric_date')->get()
                ->groupBy(fn ($row) => (string) $row->id_outlet);
        });

        return $rows->transform(function ($row) use ($keluar, $mobile, $history, $metrics) {
            $id = (string) $row->id_outlet;
            $legacy = $keluar->get($id);
            $approved = $mobile->get($id);
            $historical = $history->get($id);
            $outletMetrics = $metrics->get($id, collect());
            foreach (['st_sa' => 'sa', 'st_pv' => 'pv'] as $stock => $historyColumn) {
                $fm1 = (int) ($historical->{$historyColumn . '_fm1'} ?? 0);
                $m1 = (int) ($historical->{$historyColumn . '_m1'} ?? 0);
                $mtd = (int) ($legacy->{$stock} ?? 0) + (int) ($approved->{$stock} ?? 0);
                $row->{$stock . '_fm1'} = $fm1;
                $row->{$stock . '_m1'} = $m1;
                $row->{$stock . '_mtd'} = $mtd;
                $row->{$stock . '_gap'} = $mtd - $m1;
                $row->{$stock . '_mom_pct'} = $m1 === 0 ? null : (($mtd / $m1) - 1) * 100;
            }
            foreach (['cvm' => 'cvm', 'super_seru' => 'ss', 'comsak' => 'comsak', 'hot_promo' => 'hot_promo', 'biometrik' => 'bio'] as $alias => $column) {
                $metric = $outletMetrics->first(function ($candidate) use ($column) {
                    $uploaded = array_filter(explode(',', (string) ($candidate->uploaded_metrics ?? '')));

                    return $uploaded === [] || in_array($column, $uploaded, true);
                });
                $fm1 = (int) ($metric->{$column . '_fm1'} ?? 0);
                $m1 = (int) ($metric->{$column . '_m1'} ?? 0);
                $mtd = (int) ($metric->{$column . '_m'} ?? 0);
                $row->{$alias . '_fm1'} = $fm1;
                $row->{$alias . '_m1'} = $m1;
                $row->{$alias . '_mtd'} = $mtd;
                $row->{$alias . '_gap'} = $mtd - $m1;
                $row->{$alias . '_mom_pct'} = $m1 === 0 ? null : (($mtd / $m1) - 1) * 100;
            }

            return $row;
        });
    }

    private function safeOutletDetailSource(string $source, callable $query)
    {
        try {
            return $query();
        } catch (\Throwable $e) {
            logger()->error('Detail Outlet metric source failed', [
                'source' => $source,
                'exception' => $e,
            ]);

            return collect();
        }
    }

    private function outletSalesCutoff(Request $request): Carbon
    {
        $default = now('Asia/Jakarta')->subDays(2)->startOfDay();
        $value = trim((string) $request->input('sales_cutoff'));
        if ($value === '') {
            return $default;
        }

        try {
            $selected = Carbon::createFromFormat('!Y-m-d', $value, 'Asia/Jakarta');
        } catch (\Throwable $e) {
            return $default;
        }

        $today = now('Asia/Jakarta')->startOfDay();

        return ! $selected || $selected->greaterThan($today) ? $today : $selected;
    }

    private function outletDetailBaseQuery(?array $tapScopes, string $selectedTap = '', string $search = '')
    {
        return DB::table('appsdumais as outlet')
            ->whereNotNull('outlet.pjp')
            ->whereRaw("TRIM(outlet.pjp) <> ''")
            ->whereRaw("UPPER(TRIM(outlet.pjp)) <> 'UNMAPPING'")
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('outlet.tap', $tapScopes))
            ->when($selectedTap !== '', fn ($query) => $query->where('outlet.tap', $selectedTap))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($filter) use ($search) {
                    $filter->where('outlet.id_outlet', 'like', "%{$search}%")
                        ->orWhere('outlet.nama_outlet', 'like', "%{$search}%")
                        ->orWhere('outlet.kecamatan', 'like', "%{$search}%")
                        ->orWhere('outlet.sf', 'like', "%{$search}%")
                        ->orWhere('outlet.tap', 'like', "%{$search}%")
                        ->orWhere('outlet.pjp', 'like', "%{$search}%");
                });
            });
    }

    public function metricsUpload(Request $request)
    {
        abort_unless($this->isSuryaAdminSuper(), 403);
        $request->validate([
            'metrics_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ]);

        try {
            $reader = IOFactory::createReaderForFile($request->file('metrics_file')->getRealPath());
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($request->file('metrics_file')->getRealPath())->getActiveSheet();
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'metrics_file' => 'File Excel tidak dapat dibaca. Pastikan file tidak rusak dan formatnya XLSX, XLS, atau CSV.',
            ]);
        }

        $highestDataRow = $sheet->getHighestDataRow();
        $highestDataColumn = $sheet->getHighestDataColumn();
        $headerRows = $sheet->rangeToArray("A1:{$highestDataColumn}1", null, true, true, true);
        $header = $headerRows[1] ?? [];
        $headerValues = collect($header)->filter(fn ($value) => trim((string) $value) !== '')->values();
        $sampleEnd = min($highestDataRow, 30);
        $sampleRows = $sampleEnd >= 2
            ? $sheet->rangeToArray("A2:D{$sampleEnd}", null, true, true, true)
            : [];
        $firstDataRow = collect($sampleRows)->first(fn ($row) => trim((string) ($row['A'] ?? '')) !== '');
        $salesHistoryDate = $this->parseUploadedSalesDate($firstDataRow['A'] ?? null);
        $isSalesHistoryFile = $headerValues->count() === 4 && $salesHistoryDate !== null;
        $isBiometricFile = $headerValues->count() === 4 && ! $isSalesHistoryFile;
        $headers = collect($header)->mapWithKeys(function ($value, $key) {
            $name = strtolower(trim((string) $value));
            $name = preg_replace('/[^a-z0-9]+/', '_', $name);
            return [$key => trim($name, '_')];
        })->all();

        $metrics = ['ss', 'hot_promo', 'cvm', 'insak', 'comsak', 'digital'];
        $imported = 0;
        $skipped = 0;
        if ($isSalesHistoryFile) {
            if (! Schema::hasTable('surya_outlet_sales_history')) {
                return back()->withErrors([
                    'metrics_file' => 'Tabel histori SA/PV belum tersedia. Jalankan php artisan migrate --force lalu upload ulang.',
                ]);
            }

            try {
                $now = now();
                for ($startRow = 2; $startRow <= $highestDataRow; $startRow += 500) {
                    $endRow = min($startRow + 499, $highestDataRow);
                    $chunkRows = $sheet->rangeToArray("A{$startRow}:D{$endRow}", null, true, true, true);
                    $batch = [];

                    foreach ($chunkRows as $row) {
                        $transactionDate = $this->parseUploadedSalesDate($row['A'] ?? null);
                        $idOutlet = trim((string) ($row['B'] ?? ''));
                        if ($transactionDate === null || $idOutlet === '') {
                            $skipped++;
                            continue;
                        }

                        $batch[$transactionDate.'|'.$idOutlet] = [
                            'transaction_date' => $transactionDate,
                            'id_outlet' => $idOutlet,
                            'sa' => max(0, (int) ($row['C'] ?? 0)),
                            'pv' => max(0, (int) ($row['D'] ?? 0)),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $imported++;
                    }

                    if ($batch !== []) {
                        DB::table('surya_outlet_sales_history')->upsert(
                            array_values($batch),
                            ['transaction_date', 'id_outlet'],
                            ['sa', 'pv', 'updated_at']
                        );
                    }
                }
            } catch (\Throwable $e) {
                report($e);

                return back()->withErrors([
                    'metrics_file' => 'Upload histori SA/PV gagal diproses. Periksa isi tanggal dan ID outlet, lalu coba kembali.',
                ]);
            }

            return back()->with('success', "Upload historis SA/PV berhasil: {$imported} baris diproses, {$skipped} baris dilewati.");
        }

        $rows = $sheet->toArray(null, true, true, true);
        array_shift($rows);
        foreach ($rows as $row) {
            if ($isBiometricFile) {
                $idOutlet = trim((string) ($row['A'] ?? ''));
                if ($idOutlet === '') {
                    $skipped++;
                    continue;
                }

                $now = now();
                $existing = DB::table('surya_daily_metrics')
                    ->where('id_outlet', $idOutlet)
                    ->where('metric_date', $now->toDateString())
                    ->first();
                $uploadedMetrics = $this->mergeUploadedMetrics($existing, ['bio']);
                DB::table('surya_daily_metrics')->updateOrInsert(
                    ['id_outlet' => $idOutlet, 'metric_date' => $now->toDateString()],
                    [
                        'bio_fm1' => max(0, (int) ($row['B'] ?? 0)),
                        'bio_m1' => max(0, (int) ($row['C'] ?? 0)),
                        'bio_m' => max(0, (int) ($row['D'] ?? 0)),
                        'source_updated_at' => $now->toDateString(),
                        'uploaded_metrics' => $uploadedMetrics,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
                $imported++;
                continue;
            }

            $data = [];
            foreach ($headers as $key => $name) {
                if ($name !== '') $data[$name] = $row[$key] ?? null;
            }

            $idOutlet = trim((string) ($data['id_outlet'] ?? ''));
            if ($idOutlet === '') {
                $skipped++;
                continue;
            }

            $presentMetrics = collect($metrics)->filter(function ($metric) use ($data) {
                return collect(['fm1', 'm1', 'm'])->contains(fn ($period) => array_key_exists("{$metric}_{$period}", $data));
            })->values()->all();
            if ($presentMetrics === []) {
                $skipped++;
                continue;
            }

            $metricDate = $this->parseMetricsDate($data['update'] ?? null);
            $now = now();
            $values = ['updated_at' => $now, 'created_at' => $now];
            foreach ($presentMetrics as $metric) {
                foreach (['fm1', 'm1', 'm'] as $period) {
                    if (array_key_exists("{$metric}_{$period}", $data)) {
                        $values["{$metric}_{$period}"] = max(0, (int) ($data["{$metric}_{$period}"] ?? 0));
                    }
                }
            }
            $existing = DB::table('surya_daily_metrics')
                ->where('id_outlet', $idOutlet)
                ->where('metric_date', $metricDate)
                ->first();
            $values['uploaded_metrics'] = $this->mergeUploadedMetrics($existing, $presentMetrics);
            $values['source_updated_at'] = $metricDate;
            DB::table('surya_daily_metrics')->updateOrInsert(
                ['id_outlet' => $idOutlet, 'metric_date' => $metricDate],
                $values
            );
            $imported++;
        }

        return back()->with('success', "Upload berhasil: {$imported} outlet diproses, {$skipped} baris dilewati.");
    }

    private function mergeUploadedMetrics(?object $existing, array $metrics): string
    {
        $metricColumns = [
            'ss' => ['ss_fm1', 'ss_m1', 'ss_m'],
            'hot_promo' => ['hot_promo_fm1', 'hot_promo_m1', 'hot_promo_m'],
            'cvm' => ['cvm_fm1', 'cvm_m1', 'cvm_m'],
            'insak' => ['insak_fm1', 'insak_m1', 'insak_m'],
            'comsak' => ['comsak_fm1', 'comsak_m1', 'comsak_m'],
            'digital' => ['digital_fm1', 'digital_m1', 'digital_m'],
            'bio' => ['bio_fm1', 'bio_m1', 'bio_m'],
        ];
        $existingMetrics = collect(explode(',', (string) ($existing->uploaded_metrics ?? '')))
            ->map(fn ($metric) => trim((string) $metric))
            ->filter()
            ->unique();

        // Migrasi awal menandai row nol yang ambigu sebagai seluruh KPI. Saat file
        // biometrik masuk ke row tersebut, marker legacy itu membuat Detail Outlet
        // menganggap nilai transaksi nol sebagai upload terbaru dan menutupi histori.
        // Marker eksplisit parsial tetap dipertahankan, sedangkan marker kosong/penuh
        // dinormalisasi dari kolom yang benar-benar berisi data sebelum KPI baru digabung.
        $allMetrics = collect(array_keys($metricColumns));
        if ($existing && ($existingMetrics->isEmpty() || $allMetrics->diff($existingMetrics)->isEmpty())) {
            $existingMetrics = $allMetrics->filter(function ($metric) use ($existing, $metricColumns) {
                return collect($metricColumns[$metric])->contains(
                    fn ($column) => (int) ($existing->{$column} ?? 0) !== 0
                );
            });
        }

        return $existingMetrics
            ->concat($metrics)
            ->map(fn ($metric) => trim((string) $metric))
            ->filter(fn ($metric) => array_key_exists($metric, $metricColumns))
            ->unique()
            ->implode(',');
    }

    private function parseUploadedSalesDate($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (float) $value;
            if ($serial < 20000 || $serial > 80000) {
                return null;
            }

            try {
                return ExcelDate::excelToDateTimeObject($serial)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $text = trim((string) $value);
        if (! preg_match('/^\d{1,4}[\/-]\d{1,2}[\/-]\d{1,4}$/', $text)) {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $text);
                if ($date && $date->format($format) === $text) {
                    return $date->toDateString();
                }
            } catch (\Throwable $e) {
                // Coba format tanggal berikutnya.
            }
        }

        return null;
    }

    private function parseMetricsDate($value): string
    {
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                // Fallback to the application date below.
            }
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable $e) {
            return now('Asia/Jakarta')->toDateString();
        }
    }

    public function exportVisits(Request $request)
    {
        abort_unless($this->hasSuryaAccess($request), 403);

        $today = now('Asia/Jakarta')->toDateString();
        $monthStart = now('Asia/Jakarta')->startOfMonth()->toDateString();
        $tapScopes = $this->suryaTapScopes();
        $rows = DB::table('surya_visits as v')
            ->leftJoin('appsdumais as o', 'o.id_outlet', '=', 'v.id_outlet')
            ->leftJoin('idsf as s', 's.idsf', '=', 'v.idsf')
            ->whereBetween('v.visited_at', [$monthStart . ' 00:00:00', $today . ' 23:59:59'])
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('v.idtap', $tapScopes))
            ->orderBy('v.idtap')
            ->orderBy('s.namasf')
            ->orderBy('v.visited_at')
            ->get([
                'v.visited_at', 'v.checkout_at', 'v.id_outlet', 'v.idtap', 'v.idsf',
                'v.latitude as visit_latitude', 'v.longitude as visit_longitude',
                'v.distance_meters', 'v.gps_status', 'v.checkout_reason',
                'o.nama_outlet', 'o.latitude as outlet_latitude', 'o.longitude as outlet_longitude',
                'o.pjp', 's.namasf',
            ])
            ->map(function ($row) {
                $distance = null;
                if ($row->visit_latitude !== null && $row->visit_longitude !== null
                    && $row->outlet_latitude !== null && $row->outlet_longitude !== null) {
                    $distance = round($this->distanceInMeters(
                        (float) $row->visit_latitude,
                        (float) $row->visit_longitude,
                        (float) $row->outlet_latitude,
                        (float) $row->outlet_longitude
                    ), 1);
                }

                return [
                    Carbon::parse($row->visited_at)->format('d/m/Y'),
                    Carbon::parse($row->visited_at)->format('H:i:s'),
                    $row->checkout_at ? Carbon::parse($row->checkout_at)->format('H:i:s') : null,
                    $row->idsf,
                    $row->namasf ?: $row->idsf,
                    $row->idtap,
                    $this->clusterForTap((string) $row->idtap),
                    $row->id_outlet,
                    $row->nama_outlet ?: $row->id_outlet,
                    $row->pjp,
                    $row->outlet_latitude,
                    $row->outlet_longitude,
                    $row->visit_latitude,
                    $row->visit_longitude,
                    $distance,
                    $distance === null ? 'BELUM ADA KOORDINAT MASTER' : ($distance <= 10 ? 'SESUAI' : 'TIDAK SESUAI'),
                    strtoupper(str_replace('_', ' ', (string) $row->gps_status)),
                    $row->checkout_reason,
                ];
            });

        $sales = DB::table('mobile_penjualan as m')
            ->leftJoin('denom as d', 'd.iddenom', '=', 'm.iddenom')
            ->leftJoin('idsf as s', 's.idsf', '=', 'm.idsf')
            ->leftJoin('idsf as owner', 'owner.idsf', '=', DB::raw('COALESCE(m.owner_idsf, m.idsf)'))
            ->leftJoin('appsdumais as o', 'o.id_outlet', '=', 'm.id_outlet')
            ->whereBetween('m.tgl', [$monthStart . ' 00:00:00', $today . ' 23:59:59'])
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('m.idtap', $tapScopes))
            ->orderBy('m.idtap')->orderBy('s.namasf')->orderBy('o.nama_outlet')->orderBy('d.denom')
            ->get([
                'm.tgl', 'm.idsf', 's.namasf', 'm.owner_idsf', 'owner.namasf as owner_namasf', 'm.idtap', 'm.id_outlet', 'o.nama_outlet',
                'd.denom', 'd.kategori_inject', 'm.qty',
                DB::raw('COALESCE(m.unit_price, d.harga_jual, 0) as unit_price'),
                DB::raw('(m.qty * COALESCE(m.unit_price, d.harga_jual, 0)) as total'),
                'm.status',
            ])->map(fn ($row) => [
                Carbon::parse($row->tgl)->format('d/m/Y'), $row->owner_idsf ?: $row->idsf, $row->owner_namasf ?: ($row->namasf ?: $row->idsf),
                (($row->idsf !== ($row->owner_idsf ?: $row->idsf)) ? ($row->namasf ?: $row->idsf) : ''),
                $row->idtap, $row->id_outlet, $row->nama_outlet ?: $row->id_outlet,
                $row->denom ?: $row->iddenom, $row->kategori_inject ?: '—', (int) $row->qty,
                (float) $row->unit_price, (float) $row->total, strtoupper((string) $row->status),
            ]);

        $adminSales = DB::table('keluarsf as k')
            ->leftJoin('denom as d', 'd.iddenom', '=', 'k.iddenom')
            ->leftJoin('idsf as s', 's.idsf', '=', 'k.idsf')
            ->whereBetween('k.tgl', [$monthStart . ' 00:00:00', $today . ' 23:59:59'])
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('k.idtap', $tapScopes))
            ->orderBy('k.tgl')->orderBy('k.idtap')->orderBy('s.namasf')
            ->get(['k.tgl', 'k.idtap', 'k.idsf', 'k.id_outlet', 'k.iddenom', 's.namasf', 'k.tambahanket', 'd.denom', 'k.qty'])
            ->map(fn ($row) => [
                Carbon::parse($row->tgl)->format('d/m/Y'),
                $row->idtap,
                $row->idsf,
                $row->namasf ?: $row->idsf,
                $row->id_outlet ?: ($row->tambahanket ?: '—'),
                $row->denom ?: $row->iddenom,
                (int) $row->qty,
                $row->tambahanket,
            ]);

        $consignments = DB::table('surya_sa_consignment_numbers as c')
            ->leftJoin('idsf as s', 's.idsf', '=', 'c.idsf')
            ->leftJoin('appsdumais as o', 'o.id_outlet', '=', 'c.id_outlet')
            ->whereBetween('c.reported_at', [$monthStart . ' 00:00:00', $today . ' 23:59:59'])
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('c.idtap', $tapScopes))
            ->orderBy('c.reported_at')->orderBy('c.idtap')->orderBy('o.nama_outlet')
            ->get(['c.reported_at', 'c.idtap', 'c.idsf', 's.namasf', 'c.id_outlet', 'o.nama_outlet', 'c.serial_number', 'c.status', 'c.notes'])
            ->map(fn ($row) => [
                Carbon::parse($row->reported_at)->format('d/m/Y H:i:s'),
                $row->idtap,
                $row->idsf,
                $row->namasf ?: $row->idsf,
                $row->id_outlet,
                $row->nama_outlet ?: $row->id_outlet,
                $row->serial_number,
                strtoupper((string) $row->status),
                $row->notes,
                'POSISI TERKINI',
                '—',
                $row->id_outlet,
            ]);

        $consignmentMovements = DB::table('surya_sa_consignment_movements as h')
            ->leftJoin('appsdumais as from_o', 'from_o.id_outlet', '=', 'h.from_id_outlet')
            ->leftJoin('appsdumais as to_o', 'to_o.id_outlet', '=', 'h.to_id_outlet')
            ->leftJoin('idsf as s', 's.idsf', '=', 'h.idsf')
            ->whereBetween('h.moved_at', [$monthStart . ' 00:00:00', $today . ' 23:59:59'])
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('h.idtap', $tapScopes))
            ->orderBy('h.moved_at')
            ->get(['h.moved_at', 'h.serial_number', 'h.from_id_outlet', 'from_o.nama_outlet as from_name', 'h.to_id_outlet', 'to_o.nama_outlet as to_name', 'h.idtap', 'h.idsf', 's.namasf', 'h.receipt_id'])
            ->map(fn ($row) => [
                Carbon::parse($row->moved_at)->format('d/m/Y H:i:s'), $row->idtap, $row->idsf,
                $row->namasf ?: $row->idsf, $row->to_id_outlet, $row->to_name ?: $row->to_id_outlet,
                $row->serial_number, 'MIGRATED', 'Perpindahan konsinyasi', 'RIWAYAT MIGRASI',
                $row->from_id_outlet ?: '—', $row->to_id_outlet,
            ]);

        $consignments = $consignments->merge($consignmentMovements);

        return Excel::download(
            new SuryaWorkbookExport($rows, $sales, $adminSales, $consignments),
            "surya-penjualan-{$monthStart}-sampai-{$today}.xlsx"
        );
    }

    public function showLeaderLogin()
    {
        return redirect('/');
    }

    private function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }

    public function leaderLogin(Request $request)
    {
        return redirect('/');
    }

    public function leaderLogout(Request $request)
    {
        $request->session()->forget(['monita_leader_auth', 'monita_leader_login_at']);

        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect('/')->with('success', 'Akses SURYA ditutup.');
    }

    private function hasSuryaAccess(Request $request): bool
    {
        if ($request->session()->has('monita_leader_auth')) {
            return true;
        }

        if (! Auth::check()) {
            return false;
        }

        $username = strtolower(trim((string) Auth::user()->username));

        return in_array($username, ['gm', 'gm_cluster', 'branch_dumai', 'admin_super', 'admin_cluster', 'cluster_dumai', 'cluster_rohil'], true)
            || str_starts_with($username, 'asmen_');
    }

    private function isSuryaAdminSuper(): bool
    {
        return Auth::check()
            && strtolower(trim((string) Auth::user()->username)) === 'admin_super';
    }

    private function buildLeaderDashboard(string $visitDate): array
    {
        $tapScopes = $this->suryaTapScopes();
        // Modul performa lama sengaja tidak dibaca lagi. Dashboard SURYA saat ini
        // hanya memakai master outlet, visit, dan transaksi dari SURYA Mobile.
        return [
            'summary' => $this->mobileSalesSummary($visitDate, $tapScopes),
            'areas' => [],
            'tapRows' => [],
            'riskTaps' => [],
            'growthTaps' => [],
            'leaderActions' => [],
            'outletPoints' => [],
            'clusterCoverage' => ['groups' => []],
            'coveragePoints' => [],
            'competitionPoints' => [],
            'monitoringUpdateDate' => null,
            'visitDetails' => $this->buildVisitDetails($visitDate, $tapScopes),
            'approvalVisits' => $this->buildApprovalVisits($visitDate, $tapScopes),
            'visitDate' => $visitDate,
        ];
    }

    private function mobileSalesSummary(string $visitDate, ?array $tapScopes): array
    {
        $rows = DB::table('mobile_penjualan as m')
            ->leftJoin('denom as d', 'd.iddenom', '=', 'm.iddenom')
            ->whereDate('m.tgl', $visitDate)
            ->where('m.status', 'approved')
            ->when($tapScopes !== null, fn ($q) => $q->whereIn('m.idtap', $tapScopes))
            ->selectRaw("COALESCE(SUM(CASE WHEN UPPER(COALESCE(d.product_group, 'PV')) IN ('SA', 'SP') THEN m.qty ELSE 0 END), 0) AS st_sa")
            ->selectRaw("COALESCE(SUM(CASE WHEN UPPER(COALESCE(d.product_group, 'PV')) = 'PV' THEN m.qty ELSE 0 END), 0) AS st_pv")
            ->first();

        return ['st_sa' => (int) ($rows->st_sa ?? 0), 'st_pv' => (int) ($rows->st_pv ?? 0), 'trx_cvm' => 0];
    }

    private function currentMonthSalesByOutlet(?array $tapScopes, $outletIds = null): array
    {
        $cutoff = now('Asia/Jakarta')->subDay();
        $monthStart = $cutoff->copy()->startOfMonth()->toDateString();
        $cutoffDate = $cutoff->toDateString();
        $ids = $outletIds ? collect($outletIds)->map(fn ($id) => (string) $id)->all() : null;

        $legacy = DB::table('keluarsf as k')
            ->leftJoin('denom as d', 'd.iddenom', '=', 'k.iddenom')
            ->whereBetween('k.tgl', [$monthStart, $cutoffDate])
            ->when($tapScopes !== null, fn ($q) => $q->whereIn('k.idtap', $tapScopes))
            ->when($ids !== null, fn ($q) => $q->whereIn('k.id_outlet', $ids))
            ->groupBy('k.id_outlet')
            ->select('k.id_outlet')
            ->selectRaw("COALESCE(SUM(CASE WHEN UPPER(COALESCE(d.product_group, 'PV')) IN ('SA', 'SP') THEN k.qty ELSE 0 END), 0) AS m_stsa")
            ->selectRaw("COALESCE(SUM(CASE WHEN UPPER(COALESCE(d.product_group, 'PV')) = 'PV' THEN k.qty ELSE 0 END), 0) AS m_stpv")
            ->get()->keyBy(fn ($row) => (string) $row->id_outlet);

        $approved = DB::table('mobile_penjualan as m')
            ->leftJoin('denom as d', 'd.iddenom', '=', 'm.iddenom')
            ->where('m.status', 'approved')
            ->whereBetween('m.tgl', [$monthStart, $cutoffDate])
            ->when($tapScopes !== null, fn ($q) => $q->whereIn('m.idtap', $tapScopes))
            ->when($ids !== null, fn ($q) => $q->whereIn('m.id_outlet', $ids))
            ->groupBy('m.id_outlet')
            ->select('m.id_outlet')
            ->selectRaw("COALESCE(SUM(CASE WHEN UPPER(COALESCE(d.product_group, 'PV')) IN ('SA', 'SP') THEN m.qty ELSE 0 END), 0) AS m_stsa")
            ->selectRaw("COALESCE(SUM(CASE WHEN UPPER(COALESCE(d.product_group, 'PV')) = 'PV' THEN m.qty ELSE 0 END), 0) AS m_stpv")
            ->get()->keyBy(fn ($row) => (string) $row->id_outlet);

        $rows = $legacy->union($approved)->map(function ($row, $id) use ($legacy, $approved) {
            return (object) [
                'id_outlet' => $id,
                'm_stsa' => (int) ($legacy->get($id)->m_stsa ?? 0) + (int) ($approved->get($id)->m_stsa ?? 0),
                'm_stpv' => (int) ($legacy->get($id)->m_stpv ?? 0) + (int) ($approved->get($id)->m_stpv ?? 0),
            ];
        });

        return $rows->all();
    }

    private function buildVisitDetails(string $visitDate, ?array $tapScopes = null): array
    {
        $actualRows = DB::table('surya_visits')
            ->whereDate('visited_at', $visitDate)
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('idtap', $tapScopes))
            ->orderBy('visited_at')
            ->get(['id', 'visited_at', 'checkout_at', 'idsf', 'idtap', 'id_outlet', 'gps_status', 'checkout_reason', 'photo_front_path']);

        $salesRows = DB::table('mobile_penjualan as m')
            ->leftJoin('denom as d', 'd.iddenom', '=', 'm.iddenom')
            ->whereDate('m.tgl', $visitDate)
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('m.idtap', $tapScopes))
            ->whereIn('m.status', ['pending', 'approved'])
            ->get([
                // Rekap SURYA mengikuti petugas yang benar-benar melakukan
                // visit/input. owner_idsf hanya menentukan stok dan grouping
                // approval mobile, bukan pemilik aktivitas lapangan.
                'm.idsf',
                'm.owner_idsf',
                'm.id_outlet',
                'm.qty',
                'm.iddenom',
                'd.denom',
                'd.group_name',
            ]);

        $consignmentRows = DB::table('surya_sa_consignment_numbers')
            ->whereDate('reported_at', $visitDate)
            ->where('status', 'consigned')
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('idtap', $tapScopes))
            ->get(['idsf', 'id_outlet', 'serial_number']);

        $activeSfIds = $actualRows->pluck('idsf')
            ->merge($salesRows->pluck('idsf'))
            ->merge($consignmentRows->pluck('idsf'))
            ->filter()
            ->unique()
            ->values();

        if ($activeSfIds->isEmpty()) {
            return [];
        }

        $actualBySf = $actualRows->groupBy('idsf');
        $salesBySf = $salesRows->groupBy('idsf');
        $consignmentBySf = $consignmentRows->groupBy('idsf');
        $outletMaster = DB::table('appsdumais')
            ->whereIn('id_outlet', $actualRows->pluck('id_outlet')->filter()->unique()->all())
            ->get(['id_outlet', 'nama_outlet', 'pjp'])
            ->keyBy('id_outlet');

        $pjpDay = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ][Carbon::parse($visitDate)->format('l')];

        $pjpCountsBySalesForce = DB::table('appsdumais')
            ->whereRaw('UPPER(TRIM(pjp)) = ?', [mb_strtoupper($pjpDay)])
            ->whereNotNull('sf')
            ->get(['sf'])
            ->countBy(fn ($outlet) => mb_strtoupper(trim((string) $outlet->sf)));

        return DB::table('idsf')
            ->whereIn('idsf', $activeSfIds)
            ->orderBy('idtap')
            ->orderBy('namasf')
            ->get(['idsf', 'namasf', 'idtap'])
            ->map(function ($sf) use ($actualBySf, $salesBySf, $consignmentBySf, $outletMaster, $pjpDay, $pjpCountsBySalesForce) {
                $actual = $actualBySf->get($sf->idsf, collect());
                $sales = $salesBySf->get($sf->idsf, collect());
                $consignments = $consignmentBySf->get($sf->idsf, collect());
                $effectiveOutlets = $sales->pluck('id_outlet')
                    ->merge($consignments->pluck('id_outlet'))
                    ->filter()
                    ->unique();

                $saSales = $sales->filter(function ($row) {
                    $group = strtoupper(trim((string) ($row->group_name ?? '')));
                    $id = strtoupper((string) ($row->iddenom ?? ''));

                    return $group === 'SA' || str_starts_with($id, 'SA_');
                })->sum(fn ($row) => (int) $row->qty);

                $pvSales = $sales->reject(function ($row) {
                    $group = strtoupper(trim((string) ($row->group_name ?? '')));
                    $id = strtoupper((string) ($row->iddenom ?? ''));

                    return $group === 'SA' || str_starts_with($id, 'SA_');
                })->sum(fn ($row) => (int) $row->qty);

                $salesByOutlet = $sales->groupBy('id_outlet');
                $consignmentByOutlet = $consignments->groupBy('id_outlet');
                $actualDetails = $actual->groupBy('id_outlet')->map(function ($visits, $idOutlet) use ($outletMaster, $pjpDay, $salesByOutlet, $consignmentByOutlet) {
                    $visit = $visits->sortByDesc('visited_at')->first();
                    $master = $outletMaster->get($idOutlet);
                    $outletSales = $salesByOutlet->get($idOutlet, collect());
                    $outletConsignments = $consignmentByOutlet->get($idOutlet, collect());
                    $saItems = $outletSales->filter(function ($row) {
                        $group = strtoupper(trim((string) ($row->group_name ?? '')));
                        $id = strtoupper((string) ($row->iddenom ?? ''));

                        return $group === 'SA' || str_starts_with($id, 'SA_');
                    });
                    $pvItems = $outletSales->reject(function ($row) {
                        $group = strtoupper(trim((string) ($row->group_name ?? '')));
                        $id = strtoupper((string) ($row->iddenom ?? ''));

                        return $group === 'SA' || str_starts_with($id, 'SA_');
                    });
                    $sa = $saItems->sum(fn ($row) => (int) $row->qty);
                    $pv = $pvItems->sum(fn ($row) => (int) $row->qty);
                    $durationMinutes = $visit->checkout_at
                        ? max(0, Carbon::parse($visit->visited_at)->diffInMinutes(Carbon::parse($visit->checkout_at)))
                        : null;

                    return [
                        'id_outlet' => (string) $idOutlet,
                        'outlet_name' => $master->nama_outlet ?? (string) $idOutlet,
                        'visited_at' => $visit->visited_at,
                        'checkout_at' => $visit->checkout_at,
                        'duration_minutes' => $durationMinutes,
                        'gps_status' => $visit->gps_status,
                        'checkout_reason' => $visit->checkout_reason,
                        'total_pv' => $pv,
                        'total_sa' => $sa,
                        'consignment_qty' => $outletConsignments->count(),
                        'pv_items' => $pvItems->map(fn ($row) => ['name' => $row->denom ?: $row->iddenom, 'qty' => (int) $row->qty])->values()->all(),
                        'sa_items' => $saItems->map(fn ($row) => ['name' => $row->denom ?: $row->iddenom, 'qty' => (int) $row->qty])->values()->all(),
                        'consignment_numbers' => $outletConsignments->pluck('serial_number')->filter()->values()->all(),
                        'is_pjp' => strcasecmp(trim((string) ($master->pjp ?? '')), $pjpDay) === 0,
                        'effective' => $outletSales->isNotEmpty() || $outletConsignments->isNotEmpty(),
                        'front_thumbnail_url' => $this->visitPhotoUrl($visit->photo_front_path, true),
                        'front_photo_url' => $this->visitPhotoUrl($visit->photo_front_path),
                    ];
                })->values()->all();

                $nonPjp = collect($actualDetails)->where('is_pjp', false)->count();
                $nonBuying = collect($actualDetails)->where('effective', false)->count();
                // Actual Call untuk persentase visit hanya menghitung outlet
                // yang memang masuk jadwal PJP. Extra Call ditampilkan
                // terpisah dan tidak boleh menaikkan % Visit.
                $actualCall = collect($actualDetails)->where('is_pjp', true)->count();
                $pjpTotal = (int) $pjpCountsBySalesForce->get(mb_strtoupper(trim((string) $sf->namasf)), 0);

                return [
                    'sfcode' => $sf->idsf,
                    'name' => $sf->namasf,
                    'tap' => $sf->idtap,
                    'cluster' => $this->clusterForTap((string) $sf->idtap),
                    'pjp_total' => $pjpTotal,
                    'actual_call' => $actualCall,
                    'visit_percentage' => $pjpTotal > 0 ? round(($actualCall / $pjpTotal) * 100, 1) : 0,
                    'effective_call' => $effectiveOutlets->count(),
                    'non_pjp' => $nonPjp,
                    'non_buying' => $nonBuying,
                    'total_pv' => $pvSales,
                    'total_sa' => $saSales,
                    'outlets' => $actualDetails,
                ];
            })
            ->values()
            ->all();
    }

    private function buildApprovalVisits(string $visitDate, ?array $tapScopes = null): array
    {
        app(\App\Http\Controllers\SuryaVisitReportController::class)->autoApprovePending(request());

        $visits = DB::table('surya_visits as v')
            ->leftJoin('appsdumais as o', 'o.id_outlet', '=', 'v.id_outlet')
            ->leftJoin('idsf as s', 's.idsf', '=', 'v.idsf')
            ->whereDate('v.visited_at', $visitDate)
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('v.idtap', $tapScopes))
            ->orderByRaw("CASE WHEN v.gps_status = 'pending_admin' THEN 0 ELSE 1 END")
            ->orderByDesc('v.visited_at')
            ->get([
                'v.id', 'v.id_outlet', 'v.idtap', 'v.idsf', 'v.visited_at', 'v.gps_status',
                'v.distance_meters', 'v.photo_front_path', 'v.photo_inside_path', 'v.notes',
                'o.nama_outlet', 's.namasf',
            ]);

        $receiptPhotos = collect();
        if (Schema::hasColumn('mobile_sales_receipts', 'receipt_photo_path') && $visits->isNotEmpty()) {
            $receiptPhotos = DB::table('mobile_sales_receipts')
                ->whereDate('tgl', $visitDate)
                ->whereIn('id_outlet', $visits->pluck('id_outlet')->filter()->unique()->all())
                ->whereNotNull('receipt_photo_path')
                ->orderBy('id')
                ->get(['id', 'id_outlet', 'idsf', 'tgl', 'receipt_photo_path'])
                ->groupBy(fn ($receipt) => implode('|', [
                    (string) $receipt->id_outlet,
                    (string) $receipt->idsf,
                    Carbon::parse($receipt->tgl)->toDateString(),
                ]));
        }

        return $visits
            ->map(function ($visit) {
                $visit->front_photo_url = $this->visitPhotoUrl($visit->photo_front_path);
                $visit->front_thumbnail_url = $this->visitPhotoUrl($visit->photo_front_path, true);
                $visit->inside_photo_url = $this->visitPhotoUrl($visit->photo_inside_path);
                $visit->inside_thumbnail_url = $this->visitPhotoUrl($visit->photo_inside_path, true);

                return $visit;
            })
            ->map(function ($visit) use ($receiptPhotos) {
                $key = implode('|', [
                    (string) $visit->id_outlet,
                    (string) $visit->idsf,
                    Carbon::parse($visit->visited_at)->toDateString(),
                ]);
                $visit->receipt_photos = $receiptPhotos->get($key, collect())
                    ->map(fn ($receipt) => [
                        'id' => (int) $receipt->id,
                        'url' => $this->visitPhotoUrl($receipt->receipt_photo_path),
                        'thumbnail_url' => $this->visitPhotoUrl($receipt->receipt_photo_path, true),
                    ])
                    ->filter(fn ($photo) => $photo['url'] !== null)
                    ->values()
                    ->all();

                return $visit;
            })
            ->all();
    }

    private function suryaTapScope(): ?string
    {
        $username = strtolower(trim((string) Auth::user()?->username));
        if (! str_starts_with($username, 'asmen_')) {
            return null;
        }

        $suffix = strtoupper(substr($username, 6));
        return match ($suffix) {
            'DUMAI' => 'DUMAI',
            'BENGKALIS' => 'BENGKALIS',
            'BAGAN_BATU', 'BAGANBATU' => 'BAGAN BATU',
            'BAGAN_SIAPI_API', 'BAGANSIAPI_API', 'BAGANSIAPIAPI' => 'BAGAN SIAPI-API',
            'UJUNG_TANJUNG', 'UJUNGTANJUNG' => 'UJUNG TANJUNG',
            'ROHIL', 'ROKAN_HILIR' => 'ROHIL',
            default => $suffix !== '' ? str_replace('_', ' ', $suffix) : null,
        };
    }

    /** Return null for unrestricted users, otherwise the exact tap list allowed in SURYA. */
    private function suryaTapScopes(): ?array
    {
        $user = Auth::user();
        $username = strtolower(trim((string) ($user?->username ?? '')));

        if (in_array($username, ['admin_super', 'gm_cluster', 'branch_dumai'], true)) {
            return null;
        }

        $clusterScopes = [
            'cluster_dumai' => ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING'],
            'cluster_rohil' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
        ];
        if (isset($clusterScopes[$username])) {
            return $clusterScopes[$username];
        }

        if (str_starts_with($username, 'asmen_')) {
            $tap = $this->suryaTapScope();
            return $tap ? [$tap] : [];
        }

        $scope = strtoupper(trim((string) ($user?->level ?? '')));
        $sessionTap = strtoupper(trim((string) session('idtap')));
        if ($username === 'gm' || $scope === 'GM') {
            return match ($sessionTap) {
                'CLUSTER_DUMAI' => ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING'],
                'CLUSTER_ROHIL' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
                default => null,
            };
        }

        if (in_array($scope, ['TAP', 'ADMIN TAP', 'ADMIN_TAP'], true) && $sessionTap !== '') {
            return [$sessionTap];
        }

        return null;
    }

    private function visitPhotoUrl(?string $path, bool $thumbnail = false): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        if ($thumbnail) {
            $thumbnailPath = OptimizedImageStorage::thumbnailPathFor($path);
            if (Storage::disk('public')->exists($thumbnailPath)) {
                $path = $thumbnailPath;
            }
        }

        return '/storage/'.ltrim($path, '/');
    }

    private function clusterForTap(string $tap): string
    {
        $tap = strtoupper(trim($tap));

        return in_array($tap, ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING'], true)
            ? 'DUMAI BENGKALIS'
            : (in_array($tap, ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'], true)
                ? 'ROKAN HILIR'
                : '-');
    }

    private function buildPvMonitoring(): array
    {
        return $this->buildPerformanceMonitoring([
            'segments' => [
                ['key' => 'reg_1d', 'label' => 'REG 1D', 'short_label' => '1D', 'category' => 'reg', 'm1' => 'pvreg_1d_m1', 'mtd' => 'pvreg_1d_m'],
                ['key' => 'reg_2d', 'label' => 'REG 2D', 'short_label' => '2D', 'category' => 'reg', 'm1' => 'pvreg_2d_m1', 'mtd' => 'pvreg_2d_m'],
                ['key' => 'reg_3d', 'label' => 'REG 3D', 'short_label' => '3D', 'category' => 'reg', 'm1' => 'pvreg_3d_m1', 'mtd' => 'pvreg_3d_m'],
                ['key' => 'reg_5d', 'label' => 'REG 5D', 'short_label' => '5D', 'category' => 'reg', 'm1' => 'pvreg_5d_m1', 'mtd' => 'pvreg_5d_m'],
                ['key' => 'reg_7d', 'label' => 'REG 7D', 'short_label' => '7D', 'category' => 'reg', 'm1' => 'pvreg_7d_m1', 'mtd' => 'pvreg_7d_m'],
                ['key' => 'reg_28d', 'label' => 'REG 28D', 'short_label' => '28D', 'category' => 'reg', 'm1' => 'pvreg_28d_m1', 'mtd' => 'pvreg_28d_m'],
                ['key' => 'reg_30d', 'label' => 'REG 30D', 'short_label' => '30D', 'category' => 'reg', 'm1' => 'pvreg_30d_m1', 'mtd' => 'pvreg_30d_m'],
                ['key' => 'byu_1d', 'label' => 'BYU 1D', 'short_label' => '1D', 'category' => 'byu', 'm1' => 'pvbyu_1d_m1', 'mtd' => 'pvbyu_1d_m'],
                ['key' => 'byu_2d', 'label' => 'BYU 2D', 'short_label' => '2D', 'category' => 'byu', 'm1' => 'pvbyu_2d_m1', 'mtd' => 'pvbyu_2d_m'],
                ['key' => 'byu_3d', 'label' => 'BYU 3D', 'short_label' => '3D', 'category' => 'byu', 'm1' => 'pvbyu_3d_m1', 'mtd' => 'pvbyu_3d_m'],
                ['key' => 'byu_5d', 'label' => 'BYU 5D', 'short_label' => '5D', 'category' => 'byu', 'm1' => 'pvbyu_5d_m1', 'mtd' => 'pvbyu_5d_m'],
                ['key' => 'byu_7d', 'label' => 'BYU 7D', 'short_label' => '7D', 'category' => 'byu', 'm1' => 'pvbyu_7d_m1', 'mtd' => 'pvbyu_7d_m'],
                ['key' => 'byu_14d', 'label' => 'BYU 14D', 'short_label' => '14D', 'category' => 'byu', 'm1' => 'pvbyu_14d_m1', 'mtd' => 'pvbyu_14d_m'],
                ['key' => 'byu_30d', 'label' => 'BYU 30D', 'short_label' => '30D', 'category' => 'byu', 'm1' => 'pvbyu_30d_m1', 'mtd' => 'pvbyu_30d_m'],
            ],
            'categories' => [
                ['key' => 'all', 'label' => 'ALL'],
                ['key' => 'reg', 'label' => 'REG'],
                ['key' => 'byu', 'label' => 'BYU'],
            ],
            'total_columns' => [
                'reg' => ['m1' => 'total_pvreg_m1', 'mtd' => 'total_pvreg_m'],
                'byu' => ['m1' => 'total_pvbyu_m1', 'mtd' => 'total_pvbyu_m'],
            ],
        ]);
    }

    private function buildSaMonitoring(): array
    {
        return $this->buildPerformanceMonitoring([
            'segments' => [
                ['key' => 'sa', 'label' => 'SA', 'short_label' => 'SA', 'category' => 'sa', 'm1' => 'sp_simpati_m1', 'mtd' => 'sp_simpati_m'],
                ['key' => 'byu', 'label' => 'BYU', 'short_label' => 'BYU', 'category' => 'byu', 'm1' => 'sp_byu_m1', 'mtd' => 'sp_byu_m'],
            ],
            'categories' => [
                ['key' => 'all', 'label' => 'ALL'],
            ],
            'total_columns' => [
                'all' => ['m1' => 'total_sp_m1', 'mtd' => 'total_sp_m'],
                'sa' => ['m1' => 'sp_simpati_m1', 'mtd' => 'sp_simpati_m'],
                'byu' => ['m1' => 'sp_byu_m1', 'mtd' => 'sp_byu_m'],
            ],
        ]);
    }

    private function buildCvmMonitoring(): array
    {
        return $this->buildPerformanceMonitoring([
            'segments' => [
                ['key' => 'cvm', 'label' => 'CVM', 'short_label' => 'CVM', 'category' => 'cvm', 'm1' => 'cvm_m1', 'mtd' => 'cvm_m'],
                ['key' => 'comsak', 'label' => 'COMSAK', 'short_label' => 'COMSAK', 'category' => 'comsak', 'm1' => 'comsak_m1', 'mtd' => 'comsak_m'],
                ['key' => 'insak', 'label' => 'INSAK', 'short_label' => 'INSAK', 'category' => 'insak', 'm1' => 'insak_m1', 'mtd' => 'insak_m'],
                ['key' => 'super_seru', 'label' => 'SUPER SERU', 'short_label' => 'SUPER SERU', 'category' => 'super_seru', 'm1' => 'ss_m1', 'mtd' => 'ss_m'],
                ['key' => 'hot_promo', 'label' => 'HOT PROMO', 'short_label' => 'HOT PROMO', 'category' => 'hot_promo', 'm1' => 'hot_promo_m1', 'mtd' => 'hot_promo_m'],
            ],
            'categories' => [
                ['key' => 'all', 'label' => 'ALL'],
            ],
        ]);
    }

    private function buildPerformanceMonitoring(array $config): array
    {
        $tableColumns = collect(DB::getSchemaBuilder()->getColumnListing('outlet_performance'))->flip();
        $segments = collect($config['segments'] ?? [])
            ->filter(fn ($column) => $tableColumns->has($column['m1']) && $tableColumns->has($column['mtd']))
            ->map(fn ($column) => array_merge($column, [
                'm1_alias' => $column['key'] . '_m1',
                'mtd_alias' => $column['key'] . '_mtd',
            ]))
            ->values();
        $validities = $segments->pluck('key')->values();
        $validityHeaders = $segments
            ->mapWithKeys(fn ($column) => [
                $column['key'] => [
                    'label' => $column['label'],
                    'short_label' => $column['short_label'],
                    'category' => $column['category'],
                ],
            ])
            ->all();
        $categories = collect($config['categories'] ?? [])
            ->map(function ($category) use ($segments) {
                $validities = $category['validities'] ?? (
                    $category['key'] === 'all'
                        ? $segments->pluck('key')->values()->all()
                        : $segments->where('category', $category['key'])->pluck('key')->values()->all()
                );

                return array_merge($category, ['validities' => $validities]);
            })
            ->filter(fn ($category) => !empty($category['validities']))
            ->values();
        $totalColumns = collect($config['total_columns'] ?? [])
            ->filter(fn ($columns) => $tableColumns->has($columns['m1']) && $tableColumns->has($columns['mtd']))
            ->map(fn ($columns, $key) => [
                'key' => $key,
                'm1' => $columns['m1'],
                'mtd' => $columns['mtd'],
                'm1_alias' => $key . '_total_m1',
                'mtd_alias' => $key . '_total_mtd',
            ]);
        $selectColumns = $segments
            ->flatMap(fn ($column) => [
                "COALESCE(op.`{$column['m1']}`, 0) as `{$column['m1_alias']}`",
                "COALESCE(op.`{$column['mtd']}`, 0) as `{$column['mtd_alias']}`",
            ])
            ->concat($totalColumns->flatMap(fn ($column) => [
                "COALESCE(op.`{$column['m1']}`, 0) as `{$column['m1_alias']}`",
                "COALESCE(op.`{$column['mtd']}`, 0) as `{$column['mtd_alias']}`",
            ]))
            ->implode(', ');

        if ($segments->isEmpty() || $categories->isEmpty() || $selectColumns === '') {
            return [
                'categories' => [],
                'categoryValidities' => [],
                'validityHeaders' => [],
                'validities' => [],
                'rows' => [],
                'clusterSummary' => [],
                'period' => ['m1' => 'Outlet performance M-1', 'mtd' => 'Outlet performance MTD'],
            ];
        }

        $rows = DB::table('outlet_performance as op')
            ->join('appsdumais as a', 'a.id_outlet', '=', 'op.id_outlet')
            ->whereNotNull('a.sf')
            ->where('a.sf', '!=', '')
            ->where('a.sf', '!=', 'UNMAPPING')
            ->select('a.id_outlet', 'a.tap', 'a.sf as namasf', 'a.kecamatan')
            ->selectRaw($selectColumns)
            ->get();

        $latestUpdate = DB::table('outlet_performance')
            ->whereNotNull('tgl_update')
            ->where('tgl_update', '!=', '')
            ->max('tgl_update');
        $latestUpdateText = null;
        if ($latestUpdate) {
            try {
                $latestUpdateText = \Carbon\Carbon::parse($latestUpdate)->format('d M Y');
            } catch (\Throwable $e) {
                $latestUpdateText = (string) $latestUpdate;
            }
        }

        $calculateMom = fn ($mtd, $m1) => $m1 > 0 ? round((($mtd - $m1) / $m1) * 100, 1) : ($mtd > 0 ? 100 : 0);
        $clusterGroups = collect([
            'dumai_bengkalis' => [
                'label' => 'Dumai Bengkalis',
                'order' => 1,
                'taps' => ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING'],
            ],
            'rokan_hilir' => [
                'label' => 'Rokan Hilir',
                'order' => 2,
                'taps' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
            ],
        ]);
        $tapClusterMap = $clusterGroups
            ->flatMap(fn ($group) => collect($group['taps'])->mapWithKeys(fn ($tap, $tapIndex) => [
                $tap => ['label' => $group['label'], 'order' => $group['order'], 'tap_order' => $tapIndex + 1],
            ]));
        $clusterForTap = fn ($tap) => $tapClusterMap->get(strtoupper(trim((string) $tap)), [
            'label' => 'Cluster Lain',
            'order' => 99,
            'tap_order' => 999,
        ]);

        $makeValues = function ($items) use ($segments, $categories, $totalColumns, $calculateMom) {
            $values = [];
            foreach ($categories as $category) {
                foreach ($segments as $column) {
                    $values[$category['key']][$column['key']] = ['m1' => 0, 'mtd' => 0, 'mom' => 0];
                }
            }

            foreach ($segments as $column) {
                $m1 = (int) $items->sum($column['m1_alias']);
                $mtd = (int) $items->sum($column['mtd_alias']);
                $categoryKeys = collect(['all', $column['category']])
                    ->unique()
                    ->filter(fn ($categoryKey) => $categories->contains('key', $categoryKey));

                foreach ($categoryKeys as $categoryKey) {
                    $values[$categoryKey][$column['key']] = [
                        'm1' => $m1,
                        'mtd' => $mtd,
                        'mom' => $calculateMom($mtd, $m1),
                    ];
                }
            }

            $categoryTotal = function (string $categoryKey, string $period) use ($items, $values, $categories, $totalColumns) {
                $totalColumn = $totalColumns->firstWhere('key', $categoryKey);
                if ($totalColumn) {
                    return (int) $items->sum($period === 'm1' ? $totalColumn['m1_alias'] : $totalColumn['mtd_alias']);
                }

                $category = $categories->firstWhere('key', $categoryKey);
                return (int) collect($category['validities'] ?? [])->sum(fn ($validity) => $values[$categoryKey][$validity][$period] ?? 0);
            };

            foreach ($categories as $category) {
                if ($category['key'] === 'all' && !$totalColumns->has('all')) {
                    $sourceCategories = collect($segments)->pluck('category')->unique()->values();
                    if ($sourceCategories->every(fn ($categoryKey) => $categories->contains('key', $categoryKey))) {
                        $totalM1 = (int) $sourceCategories->sum(fn ($categoryKey) => $categoryTotal($categoryKey, 'm1'));
                        $totalMtd = (int) $sourceCategories->sum(fn ($categoryKey) => $categoryTotal($categoryKey, 'mtd'));
                    } else {
                        $totalM1 = $categoryTotal($category['key'], 'm1');
                        $totalMtd = $categoryTotal($category['key'], 'mtd');
                    }
                } else {
                    $totalM1 = $categoryTotal($category['key'], 'm1');
                    $totalMtd = $categoryTotal($category['key'], 'mtd');
                }

                $values[$category['key']]['grand_total'] = [
                    'm1' => $totalM1,
                    'mtd' => $totalMtd,
                    'mom' => $calculateMom($totalMtd, $totalM1),
                ];
            }

            return $values;
        };

        $sfRows = $rows->groupBy(fn ($row) => strtoupper(trim((string) $row->tap)) . '|' . strtoupper(trim((string) $row->namasf)))->map(function ($items) use ($makeValues, $clusterForTap) {
            $first = $items->first();
            $district = $items
                ->whereNotNull('kecamatan')
                ->where('kecamatan', '!=', '')
                ->countBy('kecamatan')
                ->sortDesc()
                ->keys()
                ->first();
            $cluster = $clusterForTap($first->tap);

            return [
                'dimension' => 'sf',
                'row_type' => 'detail',
                'name' => $first->namasf ?: 'SF BELUM ADA',
                'tap' => $first->tap ?: 'TAP BELUM ADA',
                'district' => $district ?: 'Belum termapping',
                'cluster' => $cluster['label'],
                'cluster_order' => $cluster['order'],
                'tap_order' => $cluster['tap_order'],
                'values' => $makeValues($items),
            ];
        })->sortBy(fn ($row) => sprintf('%02d|%03d|%s', $row['cluster_order'], $row['tap_order'], $row['name']))->values();

        $sumValues = function ($items) use ($validities, $categories, $calculateMom) {
            $values = [];

            foreach ($categories as $category) {
                foreach ($validities as $validity) {
                    $m1 = (int) $items->sum(fn ($row) => $row['values'][$category['key']][$validity]['m1'] ?? 0);
                    $mtd = (int) $items->sum(fn ($row) => $row['values'][$category['key']][$validity]['mtd'] ?? 0);
                    $values[$category['key']][$validity] = ['m1' => $m1, 'mtd' => $mtd, 'mom' => $calculateMom($mtd, $m1)];
                }

                $totalM1 = (int) $items->sum(fn ($row) => $row['values'][$category['key']]['grand_total']['m1'] ?? 0);
                $totalMtd = (int) $items->sum(fn ($row) => $row['values'][$category['key']]['grand_total']['mtd'] ?? 0);
                $values[$category['key']]['grand_total'] = ['m1' => $totalM1, 'mtd' => $totalMtd, 'mom' => $calculateMom($totalMtd, $totalM1)];
            }

            return $values;
        };

        $districtRows = $sfRows->groupBy(fn ($row) => $row['tap'] . '|' . $row['district'])->map(function ($items) use ($sumValues) {
            $first = $items->first();

            return [
                'dimension' => 'kecamatan',
                'row_type' => 'detail',
                'name' => $first['district'],
                'tap' => $first['tap'],
                'cluster' => $first['cluster'],
                'cluster_order' => $first['cluster_order'],
                'tap_order' => $first['tap_order'],
                'values' => $sumValues($items),
            ];
        })->sortBy(fn ($row) => sprintf('%02d|%03d|%s', $row['cluster_order'], $row['tap_order'], $row['name']))->values();

        $tapRows = $sfRows->groupBy('tap')->map(function ($items, $tap) use ($sumValues) {
            $first = $items->first();

            return [
                'dimension' => 'tap',
                'row_type' => 'group',
                'name' => $tap,
                'tap' => $tap,
                'cluster' => $first['cluster'],
                'cluster_order' => $first['cluster_order'],
                'tap_order' => $first['tap_order'],
                'values' => $sumValues($items),
            ];
        })->sortBy(fn ($row) => sprintf('%02d|%03d|%s', $row['cluster_order'], $row['tap_order'], $row['name']))->values();

        $sfByTap = $sfRows->groupBy('tap');
        $tapDisplayRows = collect();
        $tapRows->each(function ($tapRow) use ($sfByTap, $tapDisplayRows) {
            $tapDisplayRows->push($tapRow);
            $sfByTap->get($tapRow['tap'], collect())
                ->sortBy('name')
                ->each(function ($sfRow) use ($tapDisplayRows, $tapRow) {
                    $tapDisplayRows->push(array_merge($sfRow, [
                        'dimension' => 'tap',
                        'row_type' => 'child',
                        'parent_tap' => $tapRow['tap'],
                        'district' => null,
                    ]));
                });
        });

        $clusterSummary = $clusterGroups->map(function ($group) use ($sfRows, $sumValues) {
            $tapSet = collect($group['taps'])->map(fn ($tap) => strtoupper($tap))->flip();
            $items = $sfRows
                ->filter(fn ($row) => $tapSet->has(strtoupper((string) $row['tap'])))
                ->values();

            return [
                'label' => $group['label'],
                'sf_count' => $items->count(),
                'tap_count' => $items->pluck('tap')->unique()->count(),
                'values' => $sumValues($items),
            ];
        })->values()->all();

        return [
            'categories' => $categories
                ->map(fn ($category) => ['key' => $category['key'], 'label' => $category['label']])
                ->values()
                ->all(),
            'categoryValidities' => $categories
                ->mapWithKeys(fn ($category) => [$category['key'] => $category['validities']])
                ->all(),
            'validityHeaders' => $validityHeaders,
            'validities' => $validities->all(),
            'rows' => $tapDisplayRows->concat($districtRows)->concat($sfRows)->values()->all(),
            'clusterSummary' => $clusterSummary,
            'period' => ['m1' => 'Outlet performance M-1', 'mtd' => $latestUpdateText ? 'Update ' . $latestUpdateText : 'Outlet performance MTD'],
        ];
    }

    private function leaderMonitoringUpdateDate(): ?string
    {
        $latestDate = DB::table('appsdumais')
            ->whereNotNull('tgl_pack')
            ->where('tgl_pack', '!=', '')
            ->max('tgl_pack');

        if (!$latestDate) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($latestDate)->format('d-M');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function leaderCompetitionPoints(): array
    {
        if (!DB::getSchemaBuilder()->hasTable('peta_kompetisi')) {
            return [];
        }

        $operators = [
            ['key' => 'tsel', 'label' => 'TSEL', 'mtd' => 'tsel_mtd', 'mom' => 'tsel_mom', 'color' => '#e30613'],
            ['key' => 'isat', 'label' => 'ISAT', 'mtd' => 'isat_mtd', 'mom' => 'isat_mom', 'color' => '#f5c400'],
            ['key' => 'xl', 'label' => 'XL', 'mtd' => 'xl_mtd', 'mom' => 'xl_mom', 'color' => '#0057ff'],
            ['key' => 'tri', 'label' => '3', 'mtd' => '3_mtd', 'mom' => '3_mom', 'color' => '#111827'],
            ['key' => 'axis', 'label' => 'AXIS', 'mtd' => 'axis_mtd', 'mom' => 'axis_mom', 'color' => '#6f2c91'],
            ['key' => 'sfren', 'label' => 'SFREN', 'mtd' => 'sfren_mtd', 'mom' => 'sfren_mom', 'color' => '#ff4b8b'],
            ['key' => 'istri', 'label' => 'ISAT+3', 'mtd' => 'istri_mtd', 'mom' => 'istri_mom', 'color' => '#f97316'],
            ['key' => 'xlsf', 'label' => 'XL+SF', 'mtd' => 'xlsf_mtd', 'mom' => 'xlsf_mom', 'color' => '#00a7e1'],
        ];
        $competitionColumns = collect(DB::getSchemaBuilder()->getColumnListing('peta_kompetisi'))->flip();
        $operators = collect($operators)
            ->filter(fn ($operator) => $competitionColumns->has($operator['mtd']))
            ->map(fn ($operator) => array_merge($operator, [
                'mom' => $competitionColumns->has($operator['mom']) ? $operator['mom'] : null,
            ]))
            ->values()
            ->all();

        if (empty($operators)) {
            return [];
        }

        $competitionRows = DB::table('peta_kompetisi')
            ->orderBy('cluster')
            ->orderBy('tap')
            ->orderBy('kecamatan')
            ->get();

        if ($competitionRows->isEmpty()) {
            return [];
        }

        $outletCoordinates = DB::table('appsdumais')
            ->whereIn('tap', $competitionRows->pluck('tap')->filter()->unique()->values()->all())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->whereNotNull('sf')
            ->where('sf', '!=', '')
            ->where('sf', '!=', 'UNMAPPING')
            ->whereBetween(DB::raw('CAST(latitude AS DECIMAL(12,8))'), [0, 3])
            ->whereBetween(DB::raw('CAST(longitude AS DECIMAL(12,8))'), [100, 102.5])
            ->whereNotNull('kecamatan')
            ->where('kecamatan', '!=', '')
            ->select('kecamatan', 'tap', 'latitude', 'longitude')
            ->get()
            ->groupBy(fn ($row) => $this->competitionCenterKey((string) $row->kecamatan, (string) $row->tap));

        return $competitionRows
            ->map(function ($row) use ($operators, $outletCoordinates) {
                $values = collect($operators)->map(function ($operator) use ($row) {
                    return [
                        'key' => $operator['key'],
                        'label' => $operator['label'],
                        'color' => $operator['color'],
                        'share' => $this->parsePercentValue($row->{$operator['mtd']} ?? 0),
                        'mom' => $this->parsePercentValue($operator['mom'] ? ($row->{$operator['mom']} ?? 0) : 0),
                    ];
                })->values();

                $winner = $values->sortByDesc('share')->first();
                $coordinateRows = $outletCoordinates->get($this->competitionCenterKey((string) $row->kecamatan, (string) $row->tap), collect());
                $points = $coordinateRows
                    ->map(fn ($point) => [
                        'lat' => (float) $point->latitude,
                        'lng' => (float) $point->longitude,
                    ])
                    ->filter(fn ($point) => $point['lat'] && $point['lng'])
                    ->values();

                if ($points->isEmpty()) {
                    return null;
                }

                $latitude = $points->avg('lat');
                $longitude = $points->avg('lng');

                return [
                    'kecamatan' => $row->kecamatan,
                    'tap' => $row->tap,
                    'cluster' => $row->cluster,
                    'latitude' => (float) $latitude,
                    'longitude' => (float) $longitude,
                    'outlet_count' => $points->count(),
                    'winner_key' => $winner['key'],
                    'winner_operator' => $winner['label'],
                    'winner_share' => round($winner['share'], 2),
                    'winner_mom' => round($winner['mom'], 2),
                    'winner_color' => $winner['color'],
                    'operators' => $values->sortByDesc('share')->values()->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function parsePercentValue($value): float
    {
        $raw = trim((string) ($value ?? '0'));
        $hasPercent = str_contains($raw, '%');
        $number = (float) str_replace(['%', ',', ' '], ['', '.', ''], $raw);

        if (!$hasPercent && abs($number) <= 1) {
            return $number * 100;
        }

        return $number;
    }

    private function calculateMomPercentage($current, $previous): float
    {
        $current = (float) ($current ?? 0);
        $previous = (float) ($previous ?? 0);

        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    private function formatMomPercentage($current, $previous): string
    {
        return number_format($this->calculateMomPercentage($current, $previous), 1, '.', '') . '%';
    }

    private function decorateOutletDetailPerformance($row)
    {
        if (!$row) {
            return $row;
        }

        foreach (['digipos', 'cvm', 'comsak', 'hot', 'insak', 'digital', 'voice', 'renewal', 'super', 'hyper', 'sosa', 'sopv'] as $module) {
            $mtd = 'm_' . $module;
            $m1 = 'm1_' . $module;
            $mom = 'mom_' . $module;

            if (property_exists($row, $mtd) && property_exists($row, $m1)) {
                $row->{$mom} = $this->formatMomPercentage($row->{$mtd}, $row->{$m1});
            }
        }

        $row->mom_stsa = $this->formatMomPercentage($row->m_stsa ?? 0, $row->m1_stsa ?? 0);
        $row->mom_stpv = $this->formatMomPercentage($row->m_stpv ?? 0, $row->m1_stpv ?? 0);

        return $row;
    }

    private function decoratePvPerformance($row)
    {
        if (!$row) {
            return $row;
        }

        $validityColumns = [
            '1d' => 'pvreg_1d',
            '2d' => 'pvreg_2d',
            '3d' => 'pvreg_3d',
            '5d' => 'pvreg_5d',
            '7d' => 'pvreg_7d',
            '28d' => 'pvreg_28d',
            '30d' => 'pvreg_30d',
        ];

        foreach ($validityColumns as $label => $prefix) {
            $m1 = (int) ($row->{$prefix . '_m1'} ?? 0);
            $mtd = (int) ($row->{$prefix . '_m'} ?? 0);

            $row->{$label . '_m1'} = $m1;
            $row->{$label . '_m'} = $mtd;
            $row->{$label . '_mom'} = $this->formatMomPercentage($mtd, $m1);
        }

        $row->total_m1 = (int) ($row->total_pvreg_m1 ?? 0);
        $row->total_m = (int) ($row->total_pvreg_m ?? 0);
        $row->total_mom = $this->formatMomPercentage($row->total_m, $row->total_m1);

        return $row;
    }

    private function normalizeDistrictName(string $name): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($name)) ?: '';
    }

    private function competitionCenterKey(string $district, string $tap): string
    {
        return $this->normalizeDistrictName($district) . '|' . $this->normalizeDistrictName($tap);
    }

    private function leaderCoveragePoints(): array
    {
        $clusterGroups = [
            'dumai_bengkalis' => ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING'],
            'rokan_hilir' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
        ];

        $tapGroupMap = collect($clusterGroups)
            ->flatMap(fn ($taps, $key) => collect($taps)->mapWithKeys(fn ($tap) => [$tap => $key]));

        $outlets = DB::table('appsdumais')
            ->whereIn('tap', $tapGroupMap->keys()->all())
            ->whereNotNull('sf')
            ->where('sf', '!=', '')
            ->where('sf', '!=', 'UNMAPPING')
            ->select('id_outlet', 'tap', 'kecamatan', 'sf', 'm_cvm', 'm1_cvm')
            ->get();

        $performance = $outlets->isEmpty()
            ? collect()
            : DB::table('outlet_performance')
                ->whereIn('id_outlet', $outlets->pluck('id_outlet'))
                ->select('id_outlet', 'total_sp_m', 'total_sp_m1')
                ->selectRaw('COALESCE(total_pvreg_m, 0) as total_m, COALESCE(total_pvreg_m1, 0) as total_m1')
                ->get()
                ->keyBy('id_outlet');

        return $outlets->map(function ($outlet) use ($performance, $tapGroupMap) {
            $perf = $performance->get($outlet->id_outlet);

            return [
                'group' => $tapGroupMap->get($outlet->tap),
                'tap' => $outlet->tap ?: 'TAP BELUM ADA',
                'kecamatan' => $outlet->kecamatan ?: 'KECAMATAN BELUM ADA',
                'sf' => $outlet->sf ?: 'SF BELUM ADA',
                'st_sa' => (int) ($perf->total_sp_m ?? 0),
                'st_sa_m1' => (int) ($perf->total_sp_m1 ?? 0),
                'st_pv' => (int) ($perf->total_m ?? 0),
                'st_pv_m1' => (int) ($perf->total_m1 ?? 0),
                'trx_cvm' => (int) ($outlet->m_cvm ?? 0),
                'trx_cvm_m1' => (int) ($outlet->m1_cvm ?? 0),
            ];
        })->filter(fn ($point) => !empty($point['group']))->values()->all();
    }

    private function leaderClusterCoverage(array $thresholds): array
    {
        $clusterGroups = [
            'dumai_bengkalis' => [
                'label' => 'Dumai Bengkalis',
                'taps' => ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING'],
            ],
            'rokan_hilir' => [
                'label' => 'Rokan Hilir',
                'taps' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
            ],
        ];

        $tapGroupMap = collect($clusterGroups)
            ->flatMap(fn ($group, $key) => collect($group['taps'])->mapWithKeys(fn ($tap) => [$tap => $key]));

        $outlets = DB::table('appsdumais')
            ->whereIn('tap', $tapGroupMap->keys()->all())
            ->whereNotNull('sf')
            ->where('sf', '!=', '')
            ->where('sf', '!=', 'UNMAPPING')
            ->select('id_outlet', 'tap', 'm_cvm')
            ->get();

        $performance = $outlets->isEmpty()
            ? collect()
            : DB::table('outlet_performance')
                ->whereIn('id_outlet', $outlets->pluck('id_outlet'))
                ->select('id_outlet', 'total_sp_m')
                ->selectRaw('COALESCE(total_pvreg_m, 0) as total_m')
                ->get()
                ->keyBy('id_outlet');

        $coverage = collect($clusterGroups)->mapWithKeys(function ($group, $key) {
            return [$key => [
                'label' => $group['label'],
                'pjp' => 0,
                'sa' => 0,
                'pv' => 0,
                'cvm' => 0,
            ]];
        })->all();

        foreach ($outlets as $outlet) {
            $groupKey = $tapGroupMap->get($outlet->tap);
            if (!$groupKey) continue;

            $perf = $performance->get($outlet->id_outlet);
            $coverage[$groupKey]['pjp']++;
            $coverage[$groupKey]['sa'] += (int) ($perf->total_sp_m ?? 0) >= $thresholds['sa'] ? 1 : 0;
            $coverage[$groupKey]['pv'] += (int) ($perf->total_m ?? 0) >= $thresholds['pv'] ? 1 : 0;
            $coverage[$groupKey]['cvm'] += (int) ($outlet->m_cvm ?? 0) >= $thresholds['cvm'] ? 1 : 0;
        }

        return [
            'thresholds' => $thresholds,
            'groups' => array_values($coverage),
        ];
    }

    private function aggregateTapPerformance(string $tap): array
    {
        $currentExpr = $this->appsdumaiTransactionExpression('m_');
        $previousExpr = $this->appsdumaiTransactionExpression('m1_');
        $outletQuery = DB::table('appsdumais')->where('tap', $tap);

        $base = (clone $outletQuery)
            ->selectRaw("
                COUNT(*) as outlets,
                SUM(CASE WHEN sf IS NULL OR sf = '' OR sf = 'UNMAPPING' THEN 1 ELSE 0 END) as unmapped_outlets,
                AVG(NULLIF(latitude, '')) as avg_latitude,
                AVG(NULLIF(longitude, '')) as avg_longitude,
                SUM($currentExpr) as trx_m,
                SUM($previousExpr) as trx_m1,
                SUM(COALESCE(m_cvm, 0)) as trx_cvm
            ")
            ->first();

        $outletIds = (clone $outletQuery)->pluck('id_outlet');
        $performance = $outletIds->isEmpty()
            ? null
            : DB::table('outlet_performance')
                ->whereIn('id_outlet', $outletIds)
                ->selectRaw('
                    SUM(COALESCE(total_sp_m, 0)) as st_sa,
                    SUM(COALESCE(total_sp_m1, 0)) as st_sa_m1,
                    SUM(COALESCE(total_pvreg_m, 0)) as st_pv,
                    SUM(COALESCE(total_pvreg_m1, 0)) as st_pv_m1
                ')
                ->first();

        $appProductiveIds = (clone $outletQuery)
            ->whereRaw("$currentExpr > 0")
            ->pluck('id_outlet');

        $performanceProductiveIds = $outletIds->isEmpty()
            ? collect()
            : DB::table('outlet_performance')
                ->whereIn('id_outlet', $outletIds)
                ->whereRaw('COALESCE(total_sp_m, 0) + COALESCE(total_pvreg_m, 0) > 0')
                ->pluck('id_outlet');

        $productiveOutlets = $appProductiveIds
            ->merge($performanceProductiveIds)
            ->unique()
            ->count();

        $stSa = (int) ($performance->st_sa ?? 0);
        $stPv = (int) ($performance->st_pv ?? 0);
        $trxM = (int) ($base->trx_m ?? 0);
        $trxCvm = (int) ($base->trx_cvm ?? 0);
        $current = $stSa + $stPv + $trxM;
        $previous = (int) ($performance->st_sa_m1 ?? 0) + (int) ($performance->st_pv_m1 ?? 0) + (int) ($base->trx_m1 ?? 0);
        $outlets = (int) ($base->outlets ?? 0);
        $productivity = $outlets > 0 ? ($productiveOutlets / $outlets) * 100 : 0;
        $mom = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'tap' => $tap,
            'outlets' => $outlets,
            'unmapped_outlets' => (int) ($base->unmapped_outlets ?? 0),
            'productive_outlets' => $productiveOutlets,
            'productivity' => round($productivity, 1),
            'st_sa' => $stSa,
            'st_pv' => $stPv,
            'trx_m' => $trxM,
            'trx_cvm' => $trxCvm,
            'current' => $current,
            'previous' => $previous,
            'mom' => round($mom, 1),
            'latitude' => $base->avg_latitude ? (float) $base->avg_latitude : null,
            'longitude' => $base->avg_longitude ? (float) $base->avg_longitude : null,
        ];
    }

    private function appsdumaiTransactionExpression(string $prefix): string
    {
        $columns = ['digipos', 'cvm', 'comsak', 'hot', 'insak', 'digital', 'voice', 'renewal', 'super', 'hyper'];

        return collect($columns)
            ->map(fn ($column) => 'COALESCE(' . $prefix . $column . ', 0)')
            ->implode(' + ');
    }

    private function leaderOutletPoints(array $areaTaps): array
    {
        $tapAreaMap = collect($areaTaps)
            ->flatMap(fn ($taps, $area) => collect($taps)->mapWithKeys(fn ($tap) => [$tap => $area]));

        $currentExpr = $this->appsdumaiTransactionExpression('m_');
        $previousExpr = $this->appsdumaiTransactionExpression('m1_');
        $taps = $tapAreaMap->keys()->all();

        $outlets = DB::table('appsdumais')
            ->whereIn('tap', $taps)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->whereNotNull('sf')
            ->where('sf', '!=', '')
            ->where('sf', '!=', 'UNMAPPING')
            ->select(
                'id_outlet',
                'nama_outlet',
                'tap',
                'kecamatan',
                'sf',
                'latitude',
                'longitude',
                'm_cvm',
                'm1_cvm'
            )
            ->selectRaw("($currentExpr) as trx_m")
            ->selectRaw("($previousExpr) as trx_m1")
            ->orderByDesc('trx_m')
            ->limit(1000)
            ->get();

        $performance = $outlets->isEmpty()
            ? collect()
            : DB::table('outlet_performance')
                ->whereIn('id_outlet', $outlets->pluck('id_outlet'))
                ->select('id_outlet', 'total_sp_m', 'total_sp_m1')
                ->selectRaw('COALESCE(total_pvreg_m, 0) as total_m, COALESCE(total_pvreg_m1, 0) as total_m1')
                ->get()
                ->keyBy('id_outlet');

        return $outlets->map(function ($outlet) use ($performance, $tapAreaMap) {
            $perf = $performance->get($outlet->id_outlet);
            $stSa = (int) ($perf->total_sp_m ?? 0);
            $stSaM1 = (int) ($perf->total_sp_m1 ?? 0);
            $stPv = (int) ($perf->total_m ?? 0);
            $stPvM1 = (int) ($perf->total_m1 ?? 0);
            $trx = (int) ($outlet->trx_m ?? 0);
            $trxM1 = (int) ($outlet->trx_m1 ?? 0);
            $trxCvm = (int) ($outlet->m_cvm ?? 0);
            $trxCvmM1 = (int) ($outlet->m1_cvm ?? 0);
            $current = $stSa + $stPv + $trx;

            return [
                'id_outlet' => $outlet->id_outlet,
                'nama_outlet' => $outlet->nama_outlet,
                'tap' => $outlet->tap,
                'kecamatan' => $outlet->kecamatan ?: 'KECAMATAN BELUM ADA',
                'area' => $tapAreaMap->get($outlet->tap, 'Area lain'),
                'sf' => $outlet->sf ?: 'UNMAPPING',
                'latitude' => (float) $outlet->latitude,
                'longitude' => (float) $outlet->longitude,
                'trx_m' => $trx,
                'trx_m1' => $trxM1,
                'trx_cvm' => $trxCvm,
                'trx_cvm_m1' => $trxCvmM1,
                'st_sa' => $stSa,
                'st_sa_m1' => $stSaM1,
                'st_pv' => $stPv,
                'st_pv_m1' => $stPvM1,
                'current' => $current,
                'status' => $current > 0 ? 'hot' : 'cold',
            ];
        })->values()->all();
    }

    private function areaRiskLabel(float $mom, float $productivity): string
    {
        if ($mom < -15 || $productivity < 35) return 'Prioritas';
        if ($mom < 0 || $productivity < 50) return 'Pantau';
        return 'Aman';
    }

    private function leaderActions($riskTaps, $areas): array
    {
        $topRisk = $riskTaps->first();
        $lowestArea = $areas->sortBy('productivity')->first();

        return [
            [
                'title' => 'Recovery TAP prioritas',
                'body' => $topRisk
                    ? 'Fokuskan kunjungan leader ke ' . $topRisk['tap'] . ' karena produktivitas ' . $topRisk['productivity'] . '% dan MoM ' . $topRisk['mom'] . '%.'
                    : 'Semua TAP terlihat stabil, lanjutkan monitoring harian.',
                'tone' => 'danger',
            ],
            [
                'title' => 'Aktivasi outlet tidur',
                'body' => $lowestArea
                    ? $lowestArea['name'] . ' punya produktivitas outlet ' . $lowestArea['productivity'] . '%. Dorong sampling, retensi, dan follow-up outlet tanpa transaksi.'
                    : 'Data area belum cukup untuk menentukan outlet tidur.',
                'tone' => 'warning',
            ],
            [
                'title' => 'Jaga momentum SA dan PV',
                'body' => 'Bandingkan ST SA, ST PV, dan transaksi appsdumais di tiap TAP sebelum briefing pagi agar CTA sales lebih tajam.',
                'tone' => 'success',
            ],
        ];
    }

    public function search(Request $request)
    {
        if (!$this->hasSuryaAccess($request)) {
            return response()->json(['message' => 'Akses dashboard diperlukan.'], 403);
        }

        $keyword = $request->input('keyword');
        $tapScopes = $this->suryaTapScopes();
        $data = DB::table('appsdumais')
            ->when($tapScopes !== null, fn ($q) => $q->whereIn('tap', $tapScopes))
            ->select('appsdumais.*')
            ->where(function ($query) use ($keyword) {
                $query->where('appsdumais.id_outlet', $keyword)
                    ->orWhere('appsdumais.nama_outlet', 'like', '%' . $keyword . '%');
            })
            ->get();
        $salesByOutlet = $this->currentMonthSalesByOutlet($tapScopes, $data->pluck('id_outlet'));
        $metricRowsByOutlet = DB::table('surya_daily_metrics')
            ->whereIn('id_outlet', $data->pluck('id_outlet')->map(fn ($id) => (string) $id)->all())
            ->orderByDesc('metric_date')
            ->get()
            ->groupBy(fn ($row) => (string) $row->id_outlet);
        $data = $data->map(function ($row) use ($salesByOutlet, $metricRowsByOutlet) {
                $metricRows = $metricRowsByOutlet->get((string) $row->id_outlet, collect());
                $metric = function (string $name) use ($metricRows) {
                    return $metricRows->first(function ($candidate) use ($name) {
                        $uploaded = array_filter(explode(',', (string) ($candidate->uploaded_metrics ?? '')));

                        return $uploaded === [] || in_array($name, $uploaded, true);
                    });
                };
                $cvm = $metric('cvm');
                $super = $metric('ss');
                $hot = $metric('hot_promo');
                $comsak = $metric('comsak');
                $digital = $metric('digital');
                // The uploaded workbook calls this metric `cvm`; the detail
                // panel presents it as the first CVM row.
                $row->fm1_digipos = (int) ($cvm->cvm_fm1 ?? 0); $row->m1_digipos = (int) ($cvm->cvm_m1 ?? 0); $row->m_digipos = (int) ($cvm->cvm_m ?? 0);
                $row->m_cvm = $row->m_digipos;
                $row->fm1_super = (int) ($super->ss_fm1 ?? 0); $row->m1_super = (int) ($super->ss_m1 ?? 0); $row->m_super = (int) ($super->ss_m ?? 0);
                $row->fm1_hot = (int) ($hot->hot_promo_fm1 ?? 0); $row->m1_hot = (int) ($hot->hot_promo_m1 ?? 0); $row->m_hot = (int) ($hot->hot_promo_m ?? 0);
                $row->fm1_comsak = (int) ($comsak->comsak_fm1 ?? 0); $row->m1_comsak = (int) ($comsak->comsak_m1 ?? 0); $row->m_comsak = (int) ($comsak->comsak_m ?? 0);
                $row->fm1_digital = (int) ($digital->digital_fm1 ?? 0); $row->m1_digital = (int) ($digital->digital_m1 ?? 0); $row->m_digital = (int) ($digital->digital_m ?? 0);
                $row->mom_digipos = $this->metricMom($row->m_digipos, $row->m1_digipos);
                $row->mom_super = $this->metricMom($row->m_super, $row->m1_super);
                $row->mom_hot = $this->metricMom($row->m_hot, $row->m1_hot);
                $row->mom_comsak = $this->metricMom($row->m_comsak, $row->m1_comsak);
                $row->tgl_pack = $cvm->source_updated_at ?? $cvm->metric_date ?? null;
                $sales = $salesByOutlet[(string) $row->id_outlet] ?? null;
                $row->fm1_stsa = null;
                $row->m1_stsa = null;
                $row->m_stsa = (int) ($sales->m_stsa ?? 0);
                $row->fm1_stpv = null;
                $row->m1_stpv = null;
                $row->m_stpv = (int) ($sales->m_stpv ?? 0);
                $row->mom_stsa = null;
                $row->mom_stpv = null;
                $row->tgl_sa = null;
                $row->tgl_pv = null;
                return $this->decorateOutletVisitPhoto($row);
            });

        return response()->json($data)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    private function metricMom(int $mtd, int $m1): ?float
    {
        return $m1 === 0 ? null : round((($mtd / $m1) - 1) * 100, 1);
    }

    public function suggest(Request $request)
    {
        if (!$this->hasSuryaAccess($request)) {
            return response()->json(['message' => 'Akses dashboard diperlukan.'], 403);
        }

        $keyword = $request->input('keyword');
        $tapScopes = $this->suryaTapScopes();
        $data = DB::table('appsdumais')
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('tap', $tapScopes))
            ->where(function ($query) use ($keyword) {
                $query->where('nama_outlet', 'like', '%' . $keyword . '%')
                    ->orWhere('id_outlet', 'like', '%' . $keyword . '%');
            })
            ->select('id_outlet', 'nama_outlet', 'sf', 'tap')
            ->limit(10)
            ->get();

        return response()->json($data);
    }

    public function outlets(Request $request)
    {
        if (!$this->hasSuryaAccess($request)) {
            return response()->json(['message' => 'Akses dashboard diperlukan.'], 403);
        }

        $tapScopes = $this->suryaTapScopes();
        $outlets = DB::table('appsdumais')
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('tap', $tapScopes))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->where('sf', '!=', 'UNMAPPING')
            ->whereBetween(DB::raw('CAST(latitude AS DECIMAL(12,8))'), [0, 3])
            ->whereBetween(DB::raw('CAST(longitude AS DECIMAL(12,8))'), [100, 102.5])
            ->select(
                'id_outlet',
                'nama_outlet',
                'sf',
                'tap',
                'latitude',
                'longitude'
            )
            ->get();
        $salesByOutlet = $this->currentMonthSalesByOutlet($tapScopes, $outlets->pluck('id_outlet'));

        // The map only needs lightweight metadata for its first paint. Resolving
        // storage paths and checking every thumbnail here made the initial map
        // request needlessly expensive. Photo details are fetched on marker click.
        $visitedOutletIds = DB::table('surya_visits')
            ->whereNotNull('photo_front_path')
            ->whereIn('id_outlet', $outlets->pluck('id_outlet')->map(fn ($id) => (string) $id)->all())
            ->distinct()
            ->pluck('id_outlet')
            ->map(fn ($id) => (string) $id)
            ->flip();

        $data = $outlets->map(function ($outlet) use ($salesByOutlet, $visitedOutletIds) {
            $sales = $salesByOutlet[(string) $outlet->id_outlet] ?? null;
            $outlet->m_stsa = (int) ($sales->m_stsa ?? 0);
            $outlet->m_stpv = (int) ($sales->m_stpv ?? 0);
            $outlet->m_cvm = 0;
            $outlet->m_digital = 0;
            $outlet->mom_stsa = 0;
            $outlet->mom_stpv = 0;
            $outlet->mom_cvm = '—';
            $outlet->mom_digital = '—';
            $outlet->has_front_photo = $visitedOutletIds->has((string) $outlet->id_outlet);
            return $outlet;
        });

        return response()->json($data);
    }

    public function nearby(Request $request)
    {
        if (!$this->hasSuryaAccess($request)) {
            return response()->json(['message' => 'Akses dashboard diperlukan.'], 403);
        }

        $tapScopes = $this->suryaTapScopes();
        $lat = $request->input('latitude');
        $long = $request->input('longitude');
        $radius = $request->input('radius', 0.3);

        if (!$lat || !$long) {
            return response()->json(['error' => 'Latitude and longitude are required'], 400);
        }

        // Optimization: Bounding Box filter to use indices and reduce candidate set
        // 1 degree of latitude is approximately 111 km
        $lat_delta = $radius / 111.0;
        // 1 degree of longitude is approximately 111 km * cos(latitude)
        $lon_delta = $radius / (111.0 * cos(deg2rad($lat)));

        // Haversine formula to find outlets within preferred radius
        $data = DB::table('appsdumais')
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('tap', $tapScopes))
            ->whereBetween('latitude', [$lat - $lat_delta, $lat + $lat_delta])
            ->whereBetween('longitude', [$long - $lon_delta, $long + $lon_delta])
            ->select('appsdumais.*')
            ->selectRaw(
                '( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
                [$lat, $long, $lat]
            )
            ->where('sf', '!=', 'UNMAPPING')
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->limit(50)
            ->get()
            ->map(fn ($row) => $this->decorateOutletVisitPhoto($row));

        return response()->json($data);
    }

    public function performance(Request $request)
    {
        if (!$this->hasSuryaAccess($request)) {
            return response()->json(['message' => 'Akses dashboard diperlukan.'], 403);
        }

        $id_outlet = $request->input('id_outlet');
        $tapScopes = $this->suryaTapScopes();
        $canViewOutlet = DB::table('appsdumais')
            ->where('id_outlet', $id_outlet)
            ->when($tapScopes !== null, fn ($query) => $query->whereIn('tap', $tapScopes))
            ->exists();
        if (! $canViewOutlet) {
            return response()->json(['message' => 'Outlet tidak tersedia untuk cluster ini.'], 404);
        }

        $data = DB::table('outlet_performance')
            ->where('id_outlet', $id_outlet)
            ->first();

        return response()->json($this->decoratePvPerformance($data));
    }

    private function decorateOutletVisitPhoto(object $outlet): object
    {
        if (! isset($outlet->photo_front_path)) {
            $latestPhoto = DB::table('surya_visits')
                ->where('id_outlet', $outlet->id_outlet)
                ->whereNotNull('photo_front_path')
                ->orderByDesc('visited_at')
                ->orderByDesc('id')
                ->first(['photo_front_path', 'visited_at']);
            $outlet->photo_front_path = $latestPhoto->photo_front_path ?? null;
            $outlet->front_photo_visited_at = $latestPhoto->visited_at ?? null;
        }

        $path = $outlet->photo_front_path;
        $outlet->front_photo_url = null;
        $outlet->front_thumbnail_url = null;
        $outlet->receipt_photo_url = null;
        $outlet->receipt_thumbnail_url = null;
        $outlet->receipt_photo_date = null;
        $outlet->receipt_photos = [];

        if ($path && Storage::disk('public')->exists($path)) {
            $thumbnailPath = OptimizedImageStorage::thumbnailPathFor($path);
            $outlet->front_photo_url = '/storage/'.ltrim($path, '/');
            $outlet->front_thumbnail_url = Storage::disk('public')->exists($thumbnailPath)
                ? '/storage/'.ltrim($thumbnailPath, '/')
                : $outlet->front_photo_url;
        }

        $latestReceiptDate = DB::table('mobile_sales_receipts')
            ->where('id_outlet', $outlet->id_outlet)
            ->whereNotNull('receipt_photo_path')
            ->orderByDesc('tgl')
            ->orderByDesc('id')
            ->value('tgl');
        $latestReceipts = $latestReceiptDate
            ? DB::table('mobile_sales_receipts')
                ->where('id_outlet', $outlet->id_outlet)
                ->whereDate('tgl', Carbon::parse($latestReceiptDate)->toDateString())
                ->whereNotNull('receipt_photo_path')
                ->orderBy('id')
                ->get(['id', 'receipt_photo_path', 'tgl'])
            : collect();

        foreach ($latestReceipts as $receipt) {
            if (! Storage::disk('public')->exists($receipt->receipt_photo_path)) continue;
            $receiptPath = $receipt->receipt_photo_path;
            $photo = [
                'id' => (int) $receipt->id,
                'url' => '/storage/'.ltrim($receiptPath, '/'),
                'thumbnail_url' => $this->visitPhotoUrl($receiptPath, true),
                'date' => $receipt->tgl,
            ];
            $outlet->receipt_photos[] = $photo;
            $outlet->receipt_photo_url = $photo['url'];
            $outlet->receipt_thumbnail_url = $photo['thumbnail_url'];
            $outlet->receipt_photo_date = $photo['date'];
        }

        return $outlet;
    }
}
