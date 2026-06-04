<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MobilePenjualan;
use Illuminate\Support\Facades\DB;
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
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select('mobile_penjualan.*', 'denom.denom', 'denom.harga_jual')
            ->orderBy('mobile_penjualan.created_at', 'desc')
            ->limit(5)
            ->get();

        // Calculate Stats
        $today_sales = MobilePenjualan::where('idsf', $idsf)
            ->whereDate('tgl', date('Y-m-d'))
            ->sum('qty');

        $month_sales = MobilePenjualan::where('idsf', $idsf)
            ->whereMonth('tgl', date('m'))
            ->whereYear('tgl', date('Y'))
            ->sum('qty');

        // Calculate Setoran (Revenue)
        $today_setoran = MobilePenjualan::where('mobile_penjualan.idsf', $idsf)
            ->whereDate('mobile_penjualan.tgl', date('Y-m-d'))
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->sum(DB::raw('mobile_penjualan.qty * COALESCE(denom.harga_jual, 0)'));

        $month_setoran = MobilePenjualan::where('mobile_penjualan.idsf', $idsf)
            ->whereMonth('mobile_penjualan.tgl', date('m'))
            ->whereYear('mobile_penjualan.tgl', date('Y'))
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->sum(DB::raw('mobile_penjualan.qty * COALESCE(denom.harga_jual, 0)'));

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
            ->pluck('id_outlet')
            ->unique()
            ->toArray();

        foreach($pjp_list as $pjp) {
            $pjp->is_visited = in_array($pjp->id_outlet, $visited_outlets);
        }

        // Calculate Non-PJP Visits
        $pjp_outlet_ids = $pjp_list->pluck('id_outlet')->toArray();
        $non_pjp_visited_ids = array_diff($visited_outlets, $pjp_outlet_ids);
        
        $non_pjp_list = DB::table('appsdumais')
            ->whereIn('id_outlet', $non_pjp_visited_ids)
            ->get();

        return view('mobile.index', compact('latest', 'today_sales', 'month_sales', 'today_setoran', 'month_setoran', 'pjp_list', 'non_pjp_list', 'today_name'));
    }

    public function form(Request $request)
    {
        $idtap = session('idtap');
        $idsf = session('mobile_sf_id');
        $namasf = session('mobile_sf_name');
        $pre_id_outlet = $request->query('id_outlet');

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
            ->sortByDesc(function($item) {
                $name = strtoupper($item->denom);
                return (str_contains($name, 'SA SIMPATI 3GB') || str_contains($name, 'SA BYU 3GB')) ? 1 : 0;
            });

        return view('mobile.form', compact('denoms', 'idtap', 'idsf', 'pre_id_outlet', 'selected_outlet'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_outlet' => 'required',
            'products' => 'required|array',
            'products.*.iddenom' => 'required',
            'products.*.qty' => 'required|numeric|min:1',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $idsf = session('mobile_sf_id');
        $salesDate = now()->toDateString();

        try {
            DB::transaction(function () use ($request, $idsf, $salesDate) {
                foreach ($request->products as $product) {
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
                    $isVirtual = ($denomData->group_name ?? '') === 'SA' || str_starts_with($iddenom, 'SA_');

                    if (!$isVirtual && ($stockSf ?? 0) < $qty) {
                        throw new \Exception("Stok {$denomName} tidak cukup! Sisa stok: " . number_format($stockSf ?? 0) . ", dibutuhkan: " . number_format($qty));
                    }

                    // ✅ Simpan data penjualan
                    MobilePenjualan::create([
                        'tgl' => $salesDate,
                        'id_outlet' => strtoupper($request->id_outlet),
                        'idtap' => session('idtap'),
                        'idsf' => $idsf,
                        'iddenom' => $iddenom,
                        'qty' => $qty,
                        'keterangan' => $request->keterangan,
                        'status' => 'pending',
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                    ]);
                }
            });

            return redirect()->route('mobile.history')->with('success', 'Data penjualan berhasil disimpan!');

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
                DB::raw('sum(CASE WHEN mobile_penjualan.status = "rejected" THEN 0 ELSE mobile_penjualan.qty * COALESCE(denom.harga_jual, 0) END) as total_setoran'),
                DB::raw('max(mobile_penjualan.id) as last_id'),
                DB::raw('SUM(CASE WHEN mobile_penjualan.status = "approved" THEN 1 ELSE 0 END) as approved_count'),
                DB::raw('SUM(CASE WHEN mobile_penjualan.status = "rejected" THEN 1 ELSE 0 END) as rejected_count'),
                DB::raw('SUM(CASE WHEN mobile_penjualan.status = "pending" THEN 1 ELSE 0 END) as pending_count')
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
                'denom.harga_jual',
                'appsdumais.nama_outlet'
            )
            ->get();
            
        if ($sales->isEmpty()) {
            abort(404);
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
                'denom.harga_jual'
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
}
