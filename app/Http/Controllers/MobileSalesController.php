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
        $search = $request->get('q');
        $namasf = session('mobile_sf_name');

        $outlets = DB::table('appsdumais')
            ->where('sf', 'LIKE', '%' . $namasf . '%')
            ->where(function($query) use ($search) {
                $query->where('nama_outlet', 'LIKE', "%{$search}%")
                      ->orWhere('id_outlet', 'LIKE', "%{$search}%");
            })
            ->limit(20)
            ->get(['id_outlet', 'nama_outlet']);

        return response()->json($outlets);
    }

    public function index()
    {
        $idsf = session('mobile_sf_id');
        $namasf = session('mobile_sf_name');
        
        $latest = MobilePenjualan::where('idsf', $idsf)
            ->where('status', '!=', 'draft_wa')
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select('mobile_penjualan.*', 'denom.denom', DB::raw('COALESCE(mobile_penjualan.unit_price, denom.harga_jual, 0) as harga_jual'))
            ->orderBy('mobile_penjualan.created_at', 'desc')
            ->limit(5)
            ->get();

        // Calculate Stats
        $today_sales = MobilePenjualan::where('idsf', $idsf)
            ->whereDate('tgl', date('Y-m-d'))
            ->where('status', '!=', 'draft_wa')
            ->sum('qty');

        $month_sales = MobilePenjualan::where('idsf', $idsf)
            ->whereMonth('tgl', date('m'))
            ->whereYear('tgl', date('Y'))
            ->where('status', '!=', 'draft_wa')
            ->sum('qty');

        // Calculate Setoran (Revenue)
        $today_setoran = MobilePenjualan::where('mobile_penjualan.idsf', $idsf)
            ->whereDate('mobile_penjualan.tgl', date('Y-m-d'))
            ->where('mobile_penjualan.status', '!=', 'draft_wa')
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->sum(DB::raw('mobile_penjualan.qty * COALESCE(mobile_penjualan.unit_price, denom.harga_jual, 0)'));

        $month_setoran = MobilePenjualan::where('mobile_penjualan.idsf', $idsf)
            ->whereMonth('mobile_penjualan.tgl', date('m'))
            ->whereYear('mobile_penjualan.tgl', date('Y'))
            ->where('mobile_penjualan.status', '!=', 'draft_wa')
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->sum(DB::raw('mobile_penjualan.qty * COALESCE(mobile_penjualan.unit_price, denom.harga_jual, 0)'));

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
        $today_name = $days[date('l')];

        $pjp_list = DB::table('appsdumais')
            ->where('sf', 'LIKE', '%' . $namasf . '%')
            ->where('pjp', $today_name)
            ->get();

        // Check which ones are visited today
        $visited_outlets = MobilePenjualan::where('idsf', $idsf)
            ->whereDate('tgl', date('Y-m-d'))
            ->where('status', '!=', 'draft_wa')
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
                ->whereDate('visited_at', date('Y-m-d'))
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

        return view('mobile.index', compact('latest', 'today_sales', 'month_sales', 'today_setoran', 'month_setoran', 'pjp_list', 'non_pjp_list', 'today_name', 'activeVisit'));
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
                ->where('sf', 'LIKE', '%' . $namasf . '%')
                ->first(['id_outlet', 'nama_outlet']);
        }
        
        $denoms = DB::table('denom')
            ->leftJoin('stockawalsf', function($join) use ($idsf) {
                $join->on('denom.iddenom', '=', 'stockawalsf.iddenom')
                     ->where('stockawalsf.idsf', '=', $idsf);
            })
            ->select('denom.*', DB::raw('COALESCE(stockawalsf.stock, 0) as stock_qty'))
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

                $unpricedProduct = $products->first(function ($product) use ($denoms) {
                    return ! isset($denoms[$product['iddenom']]) || (int) $denoms[$product['iddenom']]->harga_jual <= 0;
                });
                if ($unpricedProduct) {
                    $denomName = $denoms[$unpricedProduct['iddenom']]->denom ?? $unpricedProduct['iddenom'];
                    throw new \Exception("Harga jual {$denomName} belum diatur oleh admin. Transaksi belum dapat dibuat.");
                }

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
                        'unit_price' => (int) $denoms[$iddenom]->harga_jual,
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
        
        $query = MobilePenjualan::where('mobile_penjualan.idsf', $idsf)
            ->leftJoin('appsdumais', 'mobile_penjualan.id_outlet', '=', 'appsdumais.id_outlet')
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select(
                'mobile_penjualan.id_outlet', 
                'mobile_penjualan.tgl', 
                'appsdumais.nama_outlet',
                DB::raw('count(*) as item_count'), 
                DB::raw('sum(mobile_penjualan.qty) as total_qty'), 
                DB::raw('sum(CASE WHEN mobile_penjualan.status = "rejected" THEN 0 ELSE mobile_penjualan.qty * COALESCE(mobile_penjualan.unit_price, denom.harga_jual, 0) END) as total_setoran'),
                DB::raw('max(mobile_penjualan.id) as last_id'),
                DB::raw('max(mobile_penjualan.receipt_id) as receipt_id'),
                DB::raw('SUM(CASE WHEN mobile_penjualan.status = "approved" THEN 1 ELSE 0 END) as approved_count'),
                DB::raw('SUM(CASE WHEN mobile_penjualan.status = "rejected" THEN 1 ELSE 0 END) as rejected_count'),
                DB::raw('SUM(CASE WHEN mobile_penjualan.status = "pending" THEN 1 ELSE 0 END) as pending_count'),
                DB::raw('SUM(CASE WHEN mobile_penjualan.status = "draft_wa" THEN 1 ELSE 0 END) as draft_count')
            )
            ->groupBy('mobile_penjualan.id_outlet', 'mobile_penjualan.tgl', 'appsdumais.nama_outlet');
            
        if ($filter_date) {
            if (strpos($filter_date, ' to ') !== false) {
                $dates = explode(' to ', $filter_date);
                $query->whereBetween('mobile_penjualan.tgl', [$dates[0] . ' 00:00:00', $dates[1] . ' 23:59:59']);
            } else {
                $query->whereDate('mobile_penjualan.tgl', $filter_date);
            }
        }

        $history = $query->orderBy('mobile_penjualan.tgl', 'desc')
            ->paginate(10);
            
        $history->appends(['filter_date' => $filter_date]);

        // Fetch MasukSF History (Stock Received by SF)
        $masuk_query = DB::table('masuksf')
            ->where('idsf', $idsf)
            ->join('denom', 'masuksf.iddenom', '=', 'denom.iddenom')
            ->select('masuksf.*', 'denom.denom');

        if ($filter_date) {
            if (strpos($filter_date, ' to ') !== false) {
                $dates = explode(' to ', $filter_date);
                $masuk_query->whereBetween('masuksf.tgl', [$dates[0] . ' 00:00:00', $dates[1] . ' 23:59:59']);
            } else {
                $masuk_query->whereDate('masuksf.tgl', $filter_date);
            }
        }
        $masuksf_history = $masuk_query->orderBy('masuksf.tgl', 'desc')
            ->limit(20)
            ->get();

        // Fetch KeluarSF History (Sales inputted by Web Admin)
        $keluar_query = DB::table('keluarsf')
            ->where('idsf', $idsf)
            ->join('denom', 'keluarsf.iddenom', '=', 'denom.iddenom')
            ->select('keluarsf.*', 'denom.denom');

        if ($filter_date) {
            if (strpos($filter_date, ' to ') !== false) {
                $dates = explode(' to ', $filter_date);
                $keluar_query->whereBetween('keluarsf.tgl', [$dates[0] . ' 00:00:00', $dates[1] . ' 23:59:59']);
            } else {
                $keluar_query->whereDate('keluarsf.tgl', $filter_date);
            }
        }
        $keluarsf_history = $keluar_query->orderBy('keluarsf.tgl', 'desc')
            ->limit(20)
            ->get();

        return view('mobile.history', compact('history', 'filter_date', 'masuksf_history', 'keluarsf_history'));
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
                DB::raw('COALESCE(mobile_penjualan.unit_price, denom.harga_jual, 0) as harga_jual'),
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
        $grandTotal = $sales->sum(function($s) { return $s->qty * ($s->harga_jual ?? 0); });

        $denoms = DB::table('denom')
            ->leftJoin('stockawalsf', function($join) use ($idsf) {
                $join->on('denom.iddenom', '=', 'stockawalsf.iddenom')
                     ->where('stockawalsf.idsf', '=', $idsf);
            })
            ->select('denom.*', DB::raw('COALESCE(stockawalsf.stock, 0) as stock_qty'))
            ->get();
        
        return view('mobile.edit', compact('sales', 'id_outlet', 'tgl', 'denoms', 'idtap', 'outletName', 'totalQty', 'grandTotal'));
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
            ->leftJoin('denom', 'stockawalsf.iddenom', '=', 'denom.iddenom')
            ->select('denom.iddenom', 'denom.denom', 'denom.group_name', 'denom.harga_jual', 'stockawalsf.stock')
            ->orderBy('denom.group_name')
            ->orderBy('denom.denom')
            ->get();

        $total_value = $stocks->sum(function($item) {
            return $item->stock * ($item->harga_jual ?? 0);
        });

        return view('mobile.stock', compact('stocks', 'total_value'));
    }

    public function getVisitDetails(Request $request)
    {
        $idsf = session('mobile_sf_id');
        $id_outlet = $request->id_outlet;
        $tgl = $request->tgl;

        $sales = MobilePenjualan::where('mobile_penjualan.idsf', $idsf)
            ->where('mobile_penjualan.id_outlet', $id_outlet)
            ->where('mobile_penjualan.tgl', $tgl)
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select(
                'mobile_penjualan.*',
                'denom.denom',
                DB::raw('COALESCE(mobile_penjualan.unit_price, denom.harga_jual, 0) as harga_jual')
            )
            ->get();

        if ($sales->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        return response()->json([
            'success' => true,
            'items' => $sales->map(function($s) {
                return [
                    'produk' => $s->denom,
                    'qty' => $s->qty,
                    'harga' => $s->harga_jual ?? 0,
                    'total' => $s->qty * ($s->harga_jual ?? 0),
                    'status' => $s->status,
                ];
            })
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
                DB::raw('COALESCE(m.unit_price, d.harga_jual, 0) as harga_jual'),
            ]);

        return view('mobile.receipt', compact('receipt', 'outlet', 'items'));
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

        $pricedItems = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->where('m.receipt_id', $receipt->id)
            ->get([
                'm.id',
                'm.qty',
                DB::raw('COALESCE(m.unit_price, d.harga_jual, 0) as unit_price'),
                'd.denom',
            ]);
        $unpricedItem = $pricedItems->first(fn ($item) => (int) $item->unit_price <= 0);
        if ($unpricedItem) {
            return response()->json([
                'success' => false,
                'message' => 'Harga jual ' . $unpricedItem->denom . ' belum diatur admin. e-Bon belum dapat dikirim.',
            ], 422);
        }

        DB::transaction(function () use ($receipt, $pricedItems, $whatsapp) {
            foreach ($pricedItems as $item) {
                MobilePenjualan::query()
                    ->whereKey($item->id)
                    ->whereNull('unit_price')
                    ->update(['unit_price' => (int) $item->unit_price]);
            }

            $receipt->update([
                'owner_whatsapp' => $whatsapp,
                'total_qty' => $pricedItems->sum(fn ($item) => (int) $item->qty),
                'total_amount' => $pricedItems->sum(fn ($item) => (int) $item->qty * (int) $item->unit_price),
                'whatsapp_opened_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'whatsapp_url' => 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode($this->buildReceiptMessage($receipt->fresh())),
            'owner_whatsapp' => $whatsapp,
        ]);
    }

    public function confirmReceiptWhatsapp(MobileSalesReceipt $receipt)
    {
        abort_unless($receipt->idsf === session('mobile_sf_id'), 403);

        if (! $receipt->owner_whatsapp || ! $receipt->whatsapp_opened_at) {
            return back()->with('error', 'Buka WhatsApp dan kirim e-Bon ke owner terlebih dahulu.');
        }
        if ((int) $receipt->total_amount <= 0) {
            return back()->with('error', 'Nilai e-Bon belum valid. Pastikan seluruh harga jual sudah diatur lalu buka WhatsApp ulang.');
        }

        DB::transaction(function () use ($receipt) {
            $lockedReceipt = MobileSalesReceipt::query()->lockForUpdate()->findOrFail($receipt->id);

            if ($lockedReceipt->whatsapp_shared_at) {
                return;
            }

            $lockedReceipt->update(['whatsapp_shared_at' => now()]);
            MobilePenjualan::query()
                ->where('receipt_id', $lockedReceipt->id)
                ->where('status', 'draft_wa')
                ->update([
                    'status' => 'pending',
                    'updated_at' => now(),
                ]);
        });

        return redirect()
            ->route('mobile.history')
            ->with('success', 'e-Bon dikonfirmasi terkirim. Transaksi sudah masuk ke admin ' . $receipt->idtap . ' untuk validasi uang.');
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
        $salesName = session('mobile_sf_name') ?: $receipt->idsf;
        $items = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->where('m.receipt_id', $receipt->id)
            ->orderBy('m.id')
            ->get(['d.denom', 'm.qty', DB::raw('COALESCE(m.unit_price, d.harga_jual, 0) as harga_jual')]);

        $lines = [
            '*e-BON SURYA*',
            'No: ' . $receipt->receipt_number,
            'Tanggal: ' . $receipt->tgl->format('d/m/Y'),
            'Outlet: ' . $outletName . ' (' . $receipt->id_outlet . ')',
            'Sales: ' . $salesName,
            '',
            '*Rincian Belanja*',
        ];

        foreach ($items as $index => $item) {
            $subtotal = (int) $item->qty * (int) $item->harga_jual;
            $lines[] = ($index + 1) . '. ' . $item->denom;
            $lines[] = '   ' . number_format($item->qty, 0, ',', '.') . ' x Rp ' . number_format($item->harga_jual, 0, ',', '.') . ' = Rp ' . number_format($subtotal, 0, ',', '.');
        }

        $lines[] = '';
        $lines[] = '*Total ' . number_format($receipt->total_qty, 0, ',', '.') . ' pcs*';
        $lines[] = '*GRAND TOTAL: Rp ' . number_format($receipt->total_amount, 0, ',', '.') . '*';
        $lines[] = '';
        $lines[] = 'Terima kasih telah berbelanja.';

        return implode("\n", $lines);
    }
}
