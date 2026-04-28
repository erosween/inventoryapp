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
        $pre_id_outlet = $request->query('id_outlet');
        
        $denoms = DB::table('denom')
            ->leftJoin('stockawalsf', function($join) use ($idsf) {
                $join->on('denom.iddenom', '=', 'stockawalsf.iddenom')
                     ->where('stockawalsf.idsf', '=', $idsf);
            })
            ->select('denom.*', DB::raw('COALESCE(stockawalsf.stock, 0) as stock_qty'))
            ->get();

        return view('mobile.form', compact('denoms', 'idtap', 'idsf', 'pre_id_outlet'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tgl' => 'required|date',
            'id_outlet' => 'required',
            'products' => 'required|array',
            'products.*.iddenom' => 'required',
            'products.*.qty' => 'required|numeric|min:1',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $idsf = session('mobile_sf_id');

        try {
            DB::transaction(function () use ($request, $idsf) {
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
                    $denomName = DB::table('denom')->where('iddenom', $iddenom)->value('denom') ?? $iddenom;

                    if (($stockSf ?? 0) < $qty) {
                        throw new \Exception("Stok {$denomName} tidak cukup! Sisa stok: " . number_format($stockSf ?? 0) . ", dibutuhkan: " . number_format($qty));
                    }

                    // ✅ Simpan data penjualan
                    MobilePenjualan::create([
                        'tgl' => $request->tgl,
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
                DB::raw('sum(mobile_penjualan.qty * COALESCE(denom.harga_jual, 0)) as total_setoran'),
                DB::raw('max(mobile_penjualan.id) as last_id')
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
        
        $sales = MobilePenjualan::where('idsf', $idsf)
            ->where('id_outlet', $id_outlet)
            ->where('tgl', $tgl)
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select('mobile_penjualan.*', 'denom.denom', 'denom.harga_jual')
            ->get();
            
        if ($sales->isEmpty()) {
            abort(404);
        }
        
        return view('mobile.edit', compact('sales', 'id_outlet', 'tgl'));
    }

    public function update(Request $request, $id_outlet, $tgl)
    {
        $idsf = session('mobile_sf_id');
        
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required',
            'products.*.qty' => 'required|numeric|min:1',
        ]);

        DB::transaction(function () use ($request, $idsf, $id_outlet, $tgl) {
            foreach ($request->products as $productData) {
                MobilePenjualan::where('id', $productData['id'])
                    ->where('idsf', $idsf)
                    ->update([
                        'qty' => $productData['qty']
                    ]);
            }
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
}
