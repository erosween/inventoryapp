<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MobilePenjualan;
use App\Models\MobileSalesReceipt;
use App\Models\SuryaVisit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MobileSalesController extends Controller
{
    public function searchOutlet(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $namasf = trim((string) session('mobile_sf_name'));

        $outlets = DB::table('appsdumais')
            ->whereRaw('UPPER(TRIM(sf)) = ?', [mb_strtoupper($namasf)])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('nama_outlet', 'LIKE', "%{$search}%")
                        ->orWhere('id_outlet', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('nama_outlet')
            ->limit(30)
            ->get(['id_outlet', 'nama_outlet']);

        return response()->json($outlets);
    }

    public function index()
    {
        $idsf = session('mobile_sf_id');
        $namasf = trim((string) session('mobile_sf_name'));
        $today = now();
        $todayDate = $today->toDateString();
        
        $latest = MobilePenjualan::where('idsf', $idsf)
            ->where('status', '!=', 'draft_wa')
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select('mobile_penjualan.*', 'denom.denom')
            ->orderBy('mobile_penjualan.created_at', 'desc')
            ->limit(5)
            ->get();

        $salesBase = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->where('m.idsf', $idsf)
            ->whereNotIn('m.status', ['draft_wa', 'rejected']);
        $totalsSelect = <<<'SQL'
COALESCE(SUM(CASE WHEN UPPER(COALESCE(d.kategori_inject, '')) = 'SA' OR UPPER(COALESCE(d.group_name, '')) = 'SA' THEN m.qty ELSE 0 END), 0) AS sa,
COALESCE(SUM(CASE WHEN UPPER(COALESCE(d.kategori_inject, '')) = 'SA' OR UPPER(COALESCE(d.group_name, '')) = 'SA' THEN 0 ELSE m.qty END), 0) AS pv
SQL;

        $todayTotals = (clone $salesBase)
            ->whereDate('m.tgl', $todayDate)
            ->selectRaw($totalsSelect)
            ->first();
        $monthTotals = (clone $salesBase)
            ->whereMonth('m.tgl', $today->month)
            ->whereYear('m.tgl', $today->year)
            ->selectRaw($totalsSelect)
            ->first();

        // PJP List Logic
        $days = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];
        $today_name = $days[$today->format('l')];

        $pjp_list = DB::table('appsdumais')
            ->whereRaw('UPPER(TRIM(sf)) = ?', [mb_strtoupper($namasf)])
            ->whereRaw('UPPER(TRIM(pjp)) = ?', [mb_strtoupper($today_name)])
            ->orderBy('nama_outlet')
            ->get();

        // Check which ones are visited today
        $visited_outlets = MobilePenjualan::where('idsf', $idsf)
            ->whereDate('tgl', $todayDate)
            ->whereNotIn('status', ['draft_wa', 'rejected'])
            ->pluck('id_outlet')
            ->unique()
            ->toArray();

        $activeVisit = SuryaVisit::query()
            ->inProgressForSalesForce($idsf)
            ->latest('visited_at')
            ->first();

        if ($activeVisit) {
            $activeVisit->outlet_name = DB::table('appsdumais')
                ->where('id_outlet', $activeVisit->id_outlet)
                ->value('nama_outlet') ?? $activeVisit->id_outlet;
        }

        foreach($pjp_list as $pjp) {
            $pjp->is_visited = in_array($pjp->id_outlet, $visited_outlets);
            $visit = DB::table('surya_visits')
                ->where('idsf', $idsf)
                ->where('id_outlet', $pjp->id_outlet)
                ->whereDate('visited_at', $todayDate)
                ->orderByDesc('visited_at')
                ->first();
            $pjp->photo_status = $visit->gps_status ?? null;
        }

        // Calculate Non-PJP Visits
        $pjp_outlet_ids = $pjp_list->pluck('id_outlet')->toArray();
        $non_pjp_visited_ids = array_diff($visited_outlets, $pjp_outlet_ids);
        
        $non_pjp_list = DB::table('appsdumais')
            ->whereIn('id_outlet', $non_pjp_visited_ids)
            ->get();

        return response()
            ->view('mobile.index', compact('latest', 'todayTotals', 'monthTotals', 'pjp_list', 'non_pjp_list', 'today_name', 'activeVisit'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function form(Request $request)
    {
        $idtap = session('idtap');
        $idsf = session('mobile_sf_id');
        $namasf = session('mobile_sf_name');
        $pre_id_outlet = $request->query('id_outlet');

        $activeVisit = SuryaVisit::query()
            ->inProgressForSalesForce($idsf)
            ->latest('visited_at')
            ->first();

        if ($activeVisit && strtoupper((string) $pre_id_outlet) !== strtoupper($activeVisit->id_outlet)) {
            return redirect()
                ->route('mobile.visit.menu', ['id_outlet' => $activeVisit->id_outlet])
                ->with('error', 'Selesaikan penjualan outlet yang sedang berlangsung sebelum membuka outlet lain.');
        }

        $draftReceipt = $pre_id_outlet ? MobileSalesReceipt::query()
            ->where('idsf', $idsf)
            ->where('id_outlet', strtoupper($pre_id_outlet))
            ->whereDate('tgl', now()->toDateString())
            ->whereNull('whatsapp_shared_at')
            ->latest('id')
            ->first() : null;

        if ($draftReceipt) {
            return redirect()
                ->route('mobile.receipt.show', $draftReceipt)
                ->with('error', 'Selesaikan pengiriman e-Bon WhatsApp untuk transaksi ini terlebih dahulu.');
        }

        $selected_outlet = null;
        if ($pre_id_outlet) {
            $selected_outlet = DB::table('appsdumais')
                ->where('id_outlet', strtoupper($pre_id_outlet))
                ->whereRaw('UPPER(TRIM(sf)) = ?', [mb_strtoupper(trim((string) $namasf))])
                ->first(['id_outlet', 'nama_outlet']);

            if (! $selected_outlet) {
                return redirect()
                    ->route('mobile.index')
                    ->with('error', 'Outlet tidak terdaftar untuk Sales Force yang sedang login.');
            }
        }
        
        $denoms = DB::table('denom')
            ->join('stockawalsf', function($join) use ($idsf) {
                $join->on('denom.iddenom', '=', 'stockawalsf.iddenom')
                     ->where('stockawalsf.idsf', '=', $idsf);
            })
            ->where('stockawalsf.stock', '>', 0)
            ->select('denom.iddenom', 'denom.denom', 'denom.group_name', DB::raw('stockawalsf.stock as stock_qty'))
            ->get()
            ->sortByDesc(fn($item) => strtoupper((string) $item->group_name) === 'SA' ? 1 : 0);

        return view('mobile.form', compact('denoms', 'idtap', 'idsf', 'pre_id_outlet', 'selected_outlet'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_outlet' => 'required',
            'products' => 'required|array|min:1',
            'products.*.iddenom' => 'required|exists:denom,iddenom',
            'products.*.qty' => 'required|integer|min:1',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $idsf = session('mobile_sf_id');
        $salesDate = now()->toDateString();
        $requestedOutlet = strtoupper($request->id_outlet);

        $isAssignedOutlet = DB::table('appsdumais')
            ->where('id_outlet', $requestedOutlet)
            ->whereRaw('UPPER(TRIM(sf)) = ?', [mb_strtoupper(trim((string) session('mobile_sf_name')))])
            ->exists();
        if (! $isAssignedOutlet) {
            return back()->withInput()->with('error', 'Outlet tidak terdaftar pada Sales Force ini.');
        }

        $activeVisit = SuryaVisit::query()
            ->inProgressForSalesForce($idsf, $salesDate)
            ->latest('visited_at')
            ->first();

        if ($activeVisit && strtoupper($activeVisit->id_outlet) !== $requestedOutlet) {
            return redirect()
                ->route('mobile.visit.menu', ['id_outlet' => $activeVisit->id_outlet])
                ->with('error', 'Penjualan outlet yang sedang berlangsung harus diselesaikan terlebih dahulu.');
        }

        $draftReceipt = MobileSalesReceipt::query()
            ->where('idsf', $idsf)
            ->where('id_outlet', $requestedOutlet)
            ->whereDate('tgl', $salesDate)
            ->whereNull('whatsapp_shared_at')
            ->latest('id')
            ->first();

        if ($draftReceipt) {
            return redirect()
                ->route('mobile.receipt.show', $draftReceipt)
                ->with('error', 'Transaksi sudah tersimpan. Kirimkan e-Bon ke WhatsApp owner untuk menyelesaikan penjualan.');
        }

        $approvedVisit = DB::table('surya_visits')
            ->where('idsf', $idsf)
            ->where('id_outlet', $requestedOutlet)
            ->whereDate('visited_at', $salesDate)
            ->whereIn('gps_status', ['valid', 'approved'])
            ->whereNotNull('photo_front_path')
            ->whereNotNull('photo_inside_path')
            ->exists();
        if (! $approvedVisit) {
            return back()->withInput()->with('error', 'Lengkapi foto outlet tampak depan dan tampak dalam, lalu pastikan lokasi valid atau sudah disetujui admin.');
        }

        try {
            $receipt = DB::transaction(function () use ($request, $idsf, $salesDate, $requestedOutlet) {
                $products = collect($request->products)
                    ->groupBy('iddenom')
                    ->map(fn ($rows, $iddenom) => [
                        'iddenom' => $iddenom,
                        'qty' => (int) $rows->sum(fn ($row) => (int) $row['qty']),
                    ])
                    ->values();

                $denoms = DB::table('denom')
                    ->whereIn('iddenom', $products->pluck('iddenom'))
                    ->get(['iddenom', 'denom', 'harga_jual'])
                    ->keyBy('iddenom');

                $totalQty = $products->sum('qty');
                $totalAmount = $products->sum(function ($product) use ($denoms) {
                    return $product['qty'] * (int) ($denoms[$product['iddenom']]->harga_jual ?? 0);
                });

                $receipt = MobileSalesReceipt::create([
                    'receipt_number' => $this->generateReceiptNumber(),
                    'tgl' => $salesDate,
                    'id_outlet' => $requestedOutlet,
                    'idtap' => session('idtap'),
                    'idsf' => $idsf,
                    'total_qty' => $totalQty,
                    'total_amount' => $totalAmount,
                ]);

                foreach ($products as $product) {
                    $iddenom = $product['iddenom'];
                    $qty = $product['qty'];

                    // 🔒 Lock & cek stok SF
                    $stockSf = DB::table('stockawalsf')
                        ->where('idsf', $idsf)
                        ->where('iddenom', $iddenom)
                        ->lockForUpdate()
                        ->value('stock');

                    // Ambil nama denom untuk pesan error yang jelas
                    $denomData = DB::table('denom')->where('iddenom', $iddenom)->first();
                    $denomName = $denomData->denom ?? $iddenom;
                    if (($stockSf ?? 0) < $qty) {
                        throw new \Exception("Stok {$denomName} tidak cukup! Sisa stok: " . number_format($stockSf ?? 0) . ", dibutuhkan: " . number_format($qty));
                    }

                    // ✅ Simpan data penjualan
                    MobilePenjualan::create([
                        'receipt_id' => $receipt->id,
                        'tgl' => $salesDate,
                        'id_outlet' => $requestedOutlet,
                        'idtap' => session('idtap'),
                        'idsf' => $idsf,
                        'iddenom' => $iddenom,
                        'qty' => $qty,
                        'unit_price' => (int) $denoms[$iddenom]->harga_jual > 0
                            ? (int) $denoms[$iddenom]->harga_jual
                            : null,
                        'keterangan' => $request->keterangan,
                        'status' => 'draft_wa',
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                    ]);
                }

                return $receipt;
            });

            return redirect()
                ->route('mobile.receipt.show', $receipt)
                ->with('success', 'Transaksi tersimpan. Kirimkan e-Bon ke WhatsApp owner untuk meneruskan transaksi ke admin.');

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function history(Request $request)
    {
        $idsf = session('mobile_sf_id');
        $filter_date = $request->get('filter_date');

        $applyDateFilter = function ($query, string $column) use ($filter_date) {
            if (! $filter_date) {
                return $query;
            }

            if (str_contains($filter_date, ' to ')) {
                [$dateFrom, $dateTo] = array_map('trim', explode(' to ', $filter_date, 2));

                return $query->whereBetween($column, [$dateFrom, $dateTo]);
            }

            return $query->whereDate($column, $filter_date);
        };

        // The first level is deliberately grouped by date. Outlet and product
        // details are fetched only after the user opens a date summary.
        $historyQuery = MobilePenjualan::query()
            ->where('mobile_penjualan.idsf', $idsf)
            ->select(
                'mobile_penjualan.tgl',
                DB::raw('COUNT(DISTINCT mobile_penjualan.id_outlet) as outlet_count'),
                DB::raw("SUM(CASE WHEN mobile_penjualan.status NOT IN ('draft_wa', 'rejected') THEN mobile_penjualan.qty ELSE 0 END) as total_qty"),
                DB::raw("SUM(CASE WHEN mobile_penjualan.status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw("SUM(CASE WHEN mobile_penjualan.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
                DB::raw("SUM(CASE WHEN mobile_penjualan.status = 'pending' THEN 1 ELSE 0 END) as pending_count"),
                DB::raw("SUM(CASE WHEN mobile_penjualan.status = 'draft_wa' THEN 1 ELSE 0 END) as draft_count")
            )
            ->groupBy('mobile_penjualan.tgl');

        $applyDateFilter($historyQuery, 'mobile_penjualan.tgl');

        $history = $historyQuery
            ->orderByDesc('mobile_penjualan.tgl')
            ->paginate(10)
            ->appends(['filter_date' => $filter_date]);

        $historyDates = $history->getCollection()
            ->pluck('tgl')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        $historyDenoms = collect();
        if ($historyDates !== []) {
            $historyDenoms = MobilePenjualan::query()
                ->join('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
                ->where('mobile_penjualan.idsf', $idsf)
                ->whereIn('mobile_penjualan.tgl', $historyDates)
                ->whereNotIn('mobile_penjualan.status', ['draft_wa', 'rejected'])
                ->select(
                    'mobile_penjualan.tgl',
                    'mobile_penjualan.iddenom',
                    'denom.denom',
                    'denom.group_name',
                    'denom.kategori_inject',
                    DB::raw('SUM(mobile_penjualan.qty) as total_qty')
                )
                ->groupBy(
                    'mobile_penjualan.tgl',
                    'mobile_penjualan.iddenom',
                    'denom.denom',
                    'denom.group_name',
                    'denom.kategori_inject'
                )
                ->orderBy('denom.denom')
                ->get()
                ->map(function ($item) {
                    $injectCategory = strtoupper(trim((string) $item->kategori_inject));
                    $groupName = strtoupper(trim((string) $item->group_name));
                    $item->category = $injectCategory === 'SA' || $groupName === 'SA' ? 'SA' : 'PV';

                    return $item;
                })
                ->groupBy(fn ($item) => Carbon::parse($item->tgl)->toDateString());
        }

        // Fetch MasukSF History (Stock Received by SF)
        $masuk_query = DB::table('masuksf')
            ->where('idsf', $idsf)
            ->join('denom', 'masuksf.iddenom', '=', 'denom.iddenom')
            ->select('masuksf.*', 'denom.denom');

        $applyDateFilter($masuk_query, 'masuksf.tgl');
        $masuksf_history = $masuk_query->orderBy('masuksf.tgl', 'desc')
            ->limit(20)
            ->get();

        // Fetch KeluarSF History (Sales inputted by Web Admin)
        $keluar_query = DB::table('keluarsf')
            ->where('idsf', $idsf)
            ->join('denom', 'keluarsf.iddenom', '=', 'denom.iddenom')
            ->select('keluarsf.*', 'denom.denom');

        $applyDateFilter($keluar_query, 'keluarsf.tgl');
        $keluarsf_history = $keluar_query->orderBy('keluarsf.tgl', 'desc')
            ->limit(20)
            ->get();

        return view('mobile.history', compact('history', 'historyDenoms', 'filter_date', 'masuksf_history', 'keluarsf_history'));
    }

    public function edit($id_outlet, $tgl)
    {
        $idsf = session('mobile_sf_id');
        $idtap = session('idtap');
        
        $sales = MobilePenjualan::where('mobile_penjualan.idsf', $idsf)
            ->where('mobile_penjualan.id_outlet', $id_outlet)
            ->where('mobile_penjualan.tgl', $tgl)
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->leftJoin('appsdumais', 'mobile_penjualan.id_outlet', '=', 'appsdumais.id_outlet')
            ->select(
                'mobile_penjualan.*',
                'denom.denom',
                'appsdumais.nama_outlet'
            )
            ->get();
            
        if ($sales->isEmpty()) {
            abort(404);
        }

        if ($sales->whereNotNull('receipt_id')->isNotEmpty()) {
            return redirect()->route('mobile.history')->with('error', 'Transaksi dengan e-Bon tidak dapat diubah setelah disimpan.');
        }

        // Cek jika sudah ada yang di-approve/reject
        $processedCount = $sales->where('status', '!=', 'pending')->count();
        if ($processedCount > 0) {
            return redirect()->route('mobile.history')->with('error', 'Kunjungan ini sudah diverifikasi oleh admin dan tidak dapat diubah lagi.');
        }

        $outletName = $sales->first()->nama_outlet ?? 'Unknown';
        $totalQty = $sales->sum('qty');

        $denoms = DB::table('denom')
            ->join('stockawalsf', function($join) use ($idsf) {
                $join->on('denom.iddenom', '=', 'stockawalsf.iddenom')
                     ->where('stockawalsf.idsf', '=', $idsf);
            })
            ->where('stockawalsf.stock', '>', 0)
            ->select('denom.iddenom', 'denom.denom', 'denom.group_name', DB::raw('stockawalsf.stock as stock_qty'))
            ->get();
        
        return view('mobile.edit', compact('sales', 'id_outlet', 'tgl', 'denoms', 'idtap', 'outletName', 'totalQty'));
    }

    public function update(Request $request, $id_outlet, $tgl)
    {
        $idsf = session('mobile_sf_id');
        $idtap = session('idtap');
        
        $request->validate([
            'products' => 'required|array',
            'products.*.iddenom' => 'required',
            'products.*.qty' => 'required|numeric|min:1',
            'keterangan' => 'nullable|string',
        ]);

        // Cek lagi status di server
        $existingProcessed = MobilePenjualan::where('idsf', $idsf)
            ->where('id_outlet', $id_outlet)
            ->where('tgl', $tgl)
            ->where('status', '!=', 'pending')
            ->count();
            
        if ($existingProcessed > 0) {
            return redirect()->route('mobile.history')->with('error', 'Update gagal! Kunjungan sudah diverifikasi oleh admin.');
        }

        $hasReceipt = MobilePenjualan::where('idsf', $idsf)
            ->where('id_outlet', $id_outlet)
            ->where('tgl', $tgl)
            ->whereNotNull('receipt_id')
            ->exists();
        if ($hasReceipt) {
            return redirect()->route('mobile.history')->with('error', 'Transaksi dengan e-Bon tidak dapat diubah setelah disimpan.');
        }

        DB::transaction(function () use ($request, $idsf, $idtap, $id_outlet, $tgl) {
            $keptIds = [];

            foreach ($request->products as $productData) {
                if (isset($productData['id'])) {
                    $keptIds[] = $productData['id'];

                    MobilePenjualan::where('id', $productData['id'])
                        ->where('idsf', $idsf)
                        ->where('id_outlet', $id_outlet)
                        ->where('tgl', $tgl)
                        ->where('status', 'pending')
                        ->update([
                            'iddenom' => $productData['iddenom'],
                            'qty' => $productData['qty'],
                            'keterangan' => $request->input('keterangan'),
                        ]);
                } else {
                    $newSale = MobilePenjualan::create([
                        'tgl' => $tgl,
                        'id_outlet' => $id_outlet,
                        'idtap' => $idtap,
                        'idsf' => $idsf,
                        'iddenom' => $productData['iddenom'],
                        'qty' => $productData['qty'],
                        'status' => 'pending',
                        'keterangan' => $request->input('keterangan') ?: 'Tambahan via edit'
                    ]);
                    $keptIds[] = $newSale->id;
                }
            }

            MobilePenjualan::where('idsf', $idsf)
                ->where('id_outlet', $id_outlet)
                ->where('tgl', $tgl)
                ->where('status', 'pending')
                ->when(!empty($keptIds), function ($query) use ($keptIds) {
                    $query->whereNotIn('id', $keptIds);
                })
                ->delete();
        });

        return redirect()->route('mobile.history')->with('success', 'Data kunjungan berhasil diperbarui!');
    }

    public function stock()
    {
        $idsf = session('mobile_sf_id');

        $stocks = DB::table('stockawalsf')
            ->where('stockawalsf.idsf', $idsf)
            ->where('stockawalsf.stock', '>', 0)
            ->leftJoin('denom', 'stockawalsf.iddenom', '=', 'denom.iddenom')
            ->select('denom.iddenom', 'denom.denom', 'denom.group_name', 'stockawalsf.stock')
            ->orderBy('denom.group_name')
            ->orderBy('denom.denom')
            ->get();

        return view('mobile.stock', compact('stocks'));
    }

    public function getVisitDetails(Request $request)
    {
        $idsf = session('mobile_sf_id');
        $validated = $request->validate([
            'tgl' => ['required', 'date_format:Y-m-d'],
        ]);
        $tgl = $validated['tgl'];

        $sales = MobilePenjualan::where('mobile_penjualan.idsf', $idsf)
            ->where('mobile_penjualan.tgl', $tgl)
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->leftJoin('appsdumais', 'mobile_penjualan.id_outlet', '=', 'appsdumais.id_outlet')
            ->select(
                'mobile_penjualan.*',
                'denom.denom',
                'appsdumais.nama_outlet'
            )
            ->orderBy('appsdumais.nama_outlet')
            ->orderBy('mobile_penjualan.id')
            ->get();

        if ($sales->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        return response()->json([
            'success' => true,
            'date' => Carbon::parse($tgl)->locale('id')->translatedFormat('d M Y'),
            'outlets' => $sales
                ->groupBy('id_outlet')
                ->map(function ($outletSales, $idOutlet) {
                    $draftReceipt = $outletSales
                        ->first(fn ($sale) => $sale->status === 'draft_wa' && $sale->receipt_id);

                    return [
                        'id_outlet' => $idOutlet,
                        'nama_outlet' => $outletSales->first()->nama_outlet ?: $idOutlet,
                        'total_qty' => (int) $outletSales
                            ->whereNotIn('status', ['draft_wa', 'rejected'])
                            ->sum('qty'),
                        'receipt_url' => $draftReceipt
                            ? route('mobile.receipt.show', $draftReceipt->receipt_id)
                            : null,
                        'items' => $outletSales->map(fn ($sale) => [
                            'produk' => $sale->denom ?: $sale->iddenom,
                            'qty' => (int) $sale->qty,
                            'status' => $sale->status,
                        ])->values(),
                    ];
                })
                ->values(),
        ]);
    }

    public function showReceipt(MobileSalesReceipt $receipt)
    {
        abort_unless($receipt->idsf === session('mobile_sf_id'), 403);

        $outlet = DB::table('appsdumais')
            ->where('id_outlet', $receipt->id_outlet)
            ->first(['id_outlet', 'nama_outlet']);

        $items = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->where('m.receipt_id', $receipt->id)
            ->orderBy('m.id')
            ->get([
                'm.id',
                'm.iddenom',
                'm.qty',
                'm.status',
                'd.denom',
            ]);

        return view('mobile.receipt', compact('receipt', 'outlet', 'items'));
    }

    public function receiptImage(MobileSalesReceipt $receipt)
    {
        abort_unless($receipt->idsf === session('mobile_sf_id'), 403);
        abort_unless(extension_loaded('gd'), 503, 'Generator nota JPEG belum tersedia.');

        $outletName = DB::table('appsdumais')
            ->where('id_outlet', $receipt->id_outlet)
            ->value('nama_outlet') ?? $receipt->id_outlet;
        $items = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->where('m.receipt_id', $receipt->id)
            ->orderBy('m.id')
            ->get(['d.denom', 'm.qty']);

        $jpeg = $this->renderThermalReceiptJpeg($receipt, $outletName, $items);
        $filename = preg_replace('/[^A-Za-z0-9_-]/', '-', $receipt->receipt_number).'.jpg';

        return response($jpeg, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function prepareReceiptWhatsapp(Request $request, MobileSalesReceipt $receipt)
    {
        abort_unless($receipt->idsf === session('mobile_sf_id'), 403);

        if ($receipt->whatsapp_shared_at) {
            return response()->json([
                'success' => false,
                'message' => 'e-Bon transaksi ini sudah dikonfirmasi terkirim.',
            ], 422);
        }

        $validated = $request->validate([
            'owner_whatsapp' => ['required', 'string', 'max:25'],
        ]);

        $whatsapp = $this->normalizeWhatsappNumber($validated['owner_whatsapp']);
        if (! $whatsapp) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp tidak valid. Gunakan format 08xx atau 628xx.',
            ], 422);
        }

        $items = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->where('m.receipt_id', $receipt->id)
            ->get([
                'm.id',
                'm.qty',
                DB::raw('COALESCE(m.unit_price, d.harga_jual, 0) as unit_price'),
                'd.denom',
            ]);
        DB::transaction(function () use ($receipt, $items, $whatsapp) {
            foreach ($items as $item) {
                if ((int) $item->unit_price <= 0) {
                    continue;
                }

                MobilePenjualan::query()
                    ->whereKey($item->id)
                    ->whereNull('unit_price')
                    ->update(['unit_price' => (int) $item->unit_price]);
            }

            $receipt->update([
                'owner_whatsapp' => $whatsapp,
                'total_qty' => $items->sum(fn ($item) => (int) $item->qty),
                'total_amount' => $items->sum(fn ($item) => (int) $item->qty * max(0, (int) $item->unit_price)),
                'whatsapp_opened_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'whatsapp_url' => 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode($this->buildReceiptMessage($receipt->fresh())),
            'image_url' => route('mobile.receipt.image', $receipt),
            'image_filename' => preg_replace('/[^A-Za-z0-9_-]/', '-', $receipt->receipt_number).'.jpg',
            'share_text' => $this->buildReceiptMessage($receipt->fresh()),
            'owner_whatsapp' => $whatsapp,
        ]);
    }

    public function confirmReceiptWhatsapp(MobileSalesReceipt $receipt)
    {
        abort_unless($receipt->idsf === session('mobile_sf_id'), 403);

        if (! $receipt->owner_whatsapp || ! $receipt->whatsapp_opened_at) {
            return back()->with('error', 'Buka WhatsApp dan kirim e-Bon ke owner terlebih dahulu.');
        }
        try {
            DB::transaction(function () use ($receipt) {
                $lockedReceipt = MobileSalesReceipt::query()->lockForUpdate()->findOrFail($receipt->id);

                if ($lockedReceipt->whatsapp_shared_at) {
                    return;
                }

                $sales = MobilePenjualan::query()
                    ->where('receipt_id', $lockedReceipt->id)
                    ->where('status', 'draft_wa')
                    ->lockForUpdate()
                    ->get();

                if ($sales->isEmpty()) {
                    throw new \RuntimeException('Item penjualan tidak ditemukan atau sudah diproses.');
                }

                foreach ($sales as $sale) {
                    $stock = DB::table('stockawalsf')
                        ->where('idsf', $sale->idsf)
                        ->where('iddenom', $sale->iddenom)
                        ->lockForUpdate()
                        ->first();

                    if (! $stock || (int) $stock->stock < (int) $sale->qty) {
                        $denomName = DB::table('denom')->where('iddenom', $sale->iddenom)->value('denom') ?: $sale->iddenom;
                        throw new \RuntimeException(
                            "Stok {$denomName} tidak cukup. Sisa " . number_format((int) ($stock->stock ?? 0)) .
                            ', dibutuhkan ' . number_format((int) $sale->qty) . '.'
                        );
                    }

                    DB::table('stockawalsf')
                        ->where('idsf', $sale->idsf)
                        ->where('iddenom', $sale->iddenom)
                        ->decrement('stock', (int) $sale->qty);

                    $sale->update([
                        'status' => 'pending',
                        'stock_reserved_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $lockedReceipt->update(['whatsapp_shared_at' => now()]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('mobile.history')
            ->with('success', 'e-Bon dikonfirmasi terkirim. Stok sudah diamankan dan transaksi masuk ke admin ' . $receipt->idtap . ' untuk validasi.');
    }

    private function generateReceiptNumber(): string
    {
        do {
            $number = 'SUR-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (MobileSalesReceipt::query()->where('receipt_number', $number)->exists());

        return $number;
    }

    private function normalizeWhatsappNumber(string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', $number);

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return preg_match('/^62[0-9]{8,13}$/', $digits) ? $digits : null;
    }

    private function buildReceiptMessage(MobileSalesReceipt $receipt): string
    {
        $outletName = DB::table('appsdumais')
            ->where('id_outlet', $receipt->id_outlet)
            ->value('nama_outlet') ?? $receipt->id_outlet;
        $lines = [
            '*NOTA PENJUALAN SURYA*',
            'Outlet: '.$outletName.' ('.$receipt->id_outlet.')',
            'No: '.$receipt->receipt_number,
            'Total: '.number_format($receipt->total_qty, 0, ',', '.').' pcs',
            'Nota JPEG terlampir.',
        ];

        return implode("\n", $lines);
    }

    private function renderThermalReceiptJpeg(MobileSalesReceipt $receipt, string $outletName, $items): string
    {
        $width = 360;
        $font = 5;
        $lineHeight = 22;
        $maxChars = 34;
        $separator = str_repeat('-', $maxChars);
        $doubleSeparator = str_repeat('=', $maxChars);
        $salesName = session('mobile_sf_name') ?: $receipt->idsf;
        $ascii = fn ($value) => Str::ascii(trim((string) $value));
        $wrap = fn ($value, $length = null) => explode("\n", wordwrap($ascii($value), $length ?? $maxChars, "\n", true));

        $lines = [
            ['text' => 'SURYA', 'align' => 'center'],
            ['text' => 'NOTA PENJUALAN', 'align' => 'center'],
            ['text' => $doubleSeparator],
        ];
        foreach ($wrap($outletName) as $nameLine) {
            $lines[] = ['text' => strtoupper($nameLine), 'align' => 'center'];
        }
        $lines[] = ['text' => 'ID     : '.$ascii($receipt->id_outlet)];
        $lines[] = ['text' => 'NO BON : '.$ascii($receipt->receipt_number)];
        $lines[] = ['text' => 'TANGGAL: '.$receipt->tgl->format('d/m/Y')];
        foreach ($wrap('SALES  : '.$salesName) as $salesLine) {
            $lines[] = ['text' => $salesLine];
        }
        $lines[] = ['text' => $separator];
        $lines[] = ['text' => 'PRODUK                         QTY'];
        $lines[] = ['text' => $separator];

        foreach ($items as $index => $item) {
            foreach ($wrap(($index + 1).'. '.$item->denom, 27) as $productLine) {
                $lines[] = ['text' => $productLine];
            }
            $qtyText = number_format((int) $item->qty, 0, ',', '.').' PCS';
            $lines[] = ['text' => str_pad($qtyText, $maxChars, ' ', STR_PAD_LEFT)];
        }

        $lines[] = ['text' => $doubleSeparator];
        $totalText = 'TOTAL QTY: '.number_format((int) $receipt->total_qty, 0, ',', '.').' PCS';
        $lines[] = ['text' => str_pad($totalText, $maxChars, ' ', STR_PAD_LEFT)];
        $lines[] = ['text' => $doubleSeparator];
        $lines[] = ['text' => 'TERIMA KASIH', 'align' => 'center'];
        $lines[] = ['text' => 'Simpan nota ini sebagai bukti', 'align' => 'center'];

        $height = 36 + count($lines) * $lineHeight + 30;
        $canvas = imagecreatetruecolor($width, $height);
        $paper = imagecolorallocate($canvas, 255, 255, 252);
        $ink = imagecolorallocate($canvas, 20, 20, 20);
        $muted = imagecolorallocate($canvas, 85, 85, 85);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $paper);

        $y = 22;
        foreach ($lines as $index => $line) {
            $text = $line['text'];
            $textWidth = imagefontwidth($font) * strlen($text);
            $x = ($line['align'] ?? null) === 'center'
                ? max(12, (int) (($width - $textWidth) / 2))
                : 18;
            imagestring($canvas, $font, $x, $y, $text, $index >= count($lines) - 2 ? $muted : $ink);
            $y += $lineHeight;
        }

        $scaled = imagescale($canvas, $width * 2, $height * 2, IMG_NEAREST_NEIGHBOUR);
        imagedestroy($canvas);
        ob_start();
        imagejpeg($scaled, null, 82);
        $jpeg = (string) ob_get_clean();
        imagedestroy($scaled);

        return $jpeg;
    }
}
