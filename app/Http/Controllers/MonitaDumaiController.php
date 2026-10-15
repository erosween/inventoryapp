<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\OptimizedImageStorage;
use Carbon\Carbon;

class MonitaDumaiController extends Controller
{
    public function index(Request $request)
    {
        $showLeaderDashboard = $this->hasSuryaAccess($request);

        if (!$showLeaderDashboard) {
            return redirect('/');
        }

        $visitDate = $request->date('visit_date')?->toDateString() ?? now()->toDateString();
        $dashboard = $this->buildLeaderDashboard($visitDate);

        return view('monitadumai.index', compact('dashboard', 'showLeaderDashboard', 'visitDate'));
    }

    public function showLeaderLogin()
    {
        return redirect('/');
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
        return $request->session()->has('monita_leader_auth')
            || (Auth::check() && strcasecmp((string) Auth::user()->username, 'admin_surya') === 0);
    }

    private function buildLeaderDashboard(string $visitDate): array
    {
        // Modul performa lama sengaja tidak dibaca lagi. Dashboard SURYA saat ini
        // hanya memakai master outlet, visit, dan transaksi dari SURYA Mobile.
        return [
            'summary' => [],
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
            'visitDetails' => $this->buildVisitDetails($visitDate),
            'approvalVisits' => $this->buildApprovalVisits($visitDate),
            'visitDate' => $visitDate,
        ];
    }

    private function buildVisitDetails(string $visitDate): array
    {
        $actualRows = DB::table('surya_visits')
            ->whereDate('visited_at', $visitDate)
            ->orderBy('visited_at')
            ->get(['id', 'visited_at', 'checkout_at', 'idsf', 'idtap', 'id_outlet', 'gps_status', 'checkout_reason', 'photo_front_path']);

        $salesRows = DB::table('mobile_penjualan as m')
            ->leftJoin('denom as d', 'd.iddenom', '=', 'm.iddenom')
            ->whereDate('m.tgl', $visitDate)
            ->whereIn('m.status', ['pending', 'approved'])
            ->get([
                'm.idsf',
                'm.id_outlet',
                'm.qty',
                'm.iddenom',
                'd.denom',
                'd.group_name',
            ]);

        $consignmentRows = DB::table('surya_sa_consignment_numbers')
            ->whereDate('reported_at', $visitDate)
            ->where('status', 'consigned')
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
                $actualCall = $actual->pluck('id_outlet')->filter()->unique()->count();
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

    private function buildApprovalVisits(string $visitDate): array
    {
        return DB::table('surya_visits as v')
            ->leftJoin('appsdumais as o', 'o.id_outlet', '=', 'v.id_outlet')
            ->leftJoin('idsf as s', 's.idsf', '=', 'v.idsf')
            ->whereDate('v.visited_at', $visitDate)
            ->orderByRaw("CASE WHEN v.gps_status = 'pending_admin' THEN 0 ELSE 1 END")
            ->orderByDesc('v.visited_at')
            ->get([
                'v.id', 'v.id_outlet', 'v.idtap', 'v.idsf', 'v.visited_at', 'v.gps_status',
                'v.distance_meters', 'v.photo_front_path', 'v.photo_inside_path',
                'o.nama_outlet', 's.namasf',
            ])
            ->map(function ($visit) {
                $visit->front_photo_url = $this->visitPhotoUrl($visit->photo_front_path);
                $visit->front_thumbnail_url = $this->visitPhotoUrl($visit->photo_front_path, true);
                $visit->inside_photo_url = $this->visitPhotoUrl($visit->photo_inside_path);
                $visit->inside_thumbnail_url = $this->visitPhotoUrl($visit->photo_inside_path, true);

                return $visit;
            })
            ->all();
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
        $data = DB::table('appsdumais')
            ->select('appsdumais.*')
            ->where('appsdumais.id_outlet', $keyword)
            ->orWhere('appsdumais.nama_outlet', 'like', '%' . $keyword . '%')
            ->get()
            ->map(fn ($row) => $this->decorateOutletVisitPhoto($row));

        return response()->json($data);
    }

    public function suggest(Request $request)
    {
        if (!$this->hasSuryaAccess($request)) {
            return response()->json(['message' => 'Akses dashboard diperlukan.'], 403);
        }

        $keyword = $request->input('keyword');
        $data = DB::table('appsdumais')
            ->where('nama_outlet', 'like', '%' . $keyword . '%')
            ->orWhere('id_outlet', 'like', '%' . $keyword . '%')
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

        $outlets = DB::table('appsdumais')
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

        $latestFrontPhotos = DB::table('surya_visits as v')
            ->joinSub(
                DB::table('surya_visits')
                    ->whereNotNull('photo_front_path')
                    ->select('id_outlet', DB::raw('MAX(id) as latest_visit_id'))
                    ->groupBy('id_outlet'),
                'latest_visit',
                fn ($join) => $join->on('latest_visit.latest_visit_id', '=', 'v.id')
            )
            ->whereIn('v.id_outlet', $outlets->pluck('id_outlet')->map(fn ($id) => (string) $id)->all())
            ->get(['v.id_outlet', 'v.photo_front_path', 'v.visited_at'])
            ->keyBy(fn ($row) => (string) $row->id_outlet);

        $data = $outlets->map(function ($outlet) use ($latestFrontPhotos) {
            $latestPhoto = $latestFrontPhotos->get((string) $outlet->id_outlet);
            $outlet->photo_front_path = $latestPhoto->photo_front_path ?? null;
            $outlet->front_photo_visited_at = $latestPhoto->visited_at ?? null;

            return $this->decorateOutletVisitPhoto($outlet);
        });

        return response()->json($data);
    }

    public function nearby(Request $request)
    {
        if (!$this->hasSuryaAccess($request)) {
            return response()->json(['message' => 'Akses dashboard diperlukan.'], 403);
        }

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

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return $outlet;
        }

        $thumbnailPath = OptimizedImageStorage::thumbnailPathFor($path);
        $outlet->front_photo_url = '/storage/'.ltrim($path, '/');
        $outlet->front_thumbnail_url = Storage::disk('public')->exists($thumbnailPath)
            ? '/storage/'.ltrim($thumbnailPath, '/')
            : $outlet->front_photo_url;

        return $outlet;
    }
}
