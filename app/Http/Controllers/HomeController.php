<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->get('mode', 'daily');
        $now  = Carbon::now();

        /* ================= KPI ================= */
        $stokSegel =
            DB::table('stockawaltap')->whereIn('iddenom',['SEGEL','V16','V33'])->sum('stock')
            + DB::table('stockawalsf')->whereIn('iddenom',['SEGEL','V16','V33'])->sum('stock');

        $stokInject =
            DB::table('stockawaltap')->whereNotIn('iddenom',['SEGEL','V16','V33'])->sum('stock')
            + DB::table('stockawalsf')->whereNotIn('iddenom',['SEGEL','V16','V33'])->sum('stock');

        $salesBulanIni = DB::table('keluarsf')
            ->whereMonth('tgl',$now->month)
            ->whereYear('tgl',$now->year)
            ->sum('qty');

        $salesPrev = DB::table('keluarsf')
            ->whereMonth('tgl',$now->copy()->subMonth()->month)
            ->whereYear('tgl',$now->copy()->subMonth()->year)
            ->sum('qty');

        $mom = $salesPrev > 0 ? (($salesBulanIni - $salesPrev) / $salesPrev) * 100 : 0;

        /* ================= CHART SALES ================= */
        if ($mode === 'weekly') {

    $chartSales = DB::table('keluarsf')
        ->selectRaw("
            CONCAT('Week ', WEEK(MIN(tgl),1)) AS label,
            SUM(qty) AS total,
            MIN(tgl) AS sort_date
        ")
        ->whereMonth('tgl',$now->month)
        ->whereYear('tgl',$now->year)
        ->groupBy(DB::raw("YEAR(tgl), WEEK(tgl,1)"))
        ->orderBy('sort_date')
        ->get();


        } elseif ($mode === 'monthly') {

            $chartSales = DB::table('keluarsf')
                ->selectRaw("
                    DATE_FORMAT(MIN(tgl),'%b %Y') AS label,
                    SUM(qty) AS total,
                    MIN(tgl) AS sort_date
                ")
                ->where('tgl','>=',$now->copy()->subMonths(5)->startOfMonth())
                ->groupBy(DB::raw("YEAR(tgl), MONTH(tgl)"))
                ->orderBy('sort_date')
                ->get();

        } else {
            // DAILY (bulan berjalan)
            $chartSales = DB::table('keluarsf')
                ->selectRaw("
                    DATE_FORMAT(MIN(tgl),'%d-%b-%Y') AS label,
                    SUM(qty) AS total,
                    DATE(tgl) AS sort_date
                ")
                ->whereMonth('tgl',$now->month)
                ->whereYear('tgl',$now->year)
                ->groupBy(DB::raw("DATE(tgl)"))
                ->orderBy('sort_date')
                ->get();
        }

        /* ================= CHART INJECT ================= */
        $chartInject = DB::table('injectvf')
            ->selectRaw("
                DATE_FORMAT(MIN(tgl),'%d-%b-%Y') AS label,
                DATE(tgl) AS sort_date,
                SUM(CASE WHEN idtap IN ('DUMAI','DURI','BENGKALIS','SEI PAKNING','RUPAT') THEN qty ELSE 0 END) AS dumai,
                SUM(CASE WHEN idtap IN ('BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG') THEN qty ELSE 0 END) AS rohil
            ")
            ->whereMonth('tgl',$now->month)
            ->whereYear('tgl',$now->year)
            ->groupBy(DB::raw("DATE(tgl)"))
            ->orderBy('sort_date')
            ->get();

        /* ================= TABLE ================= */
        $salesTap = DB::table('keluarsf')
            ->select('idtap', DB::raw('SUM(qty) AS qty'))
            ->whereMonth('tgl',$now->month)
            ->whereYear('tgl',$now->year)
            ->groupBy('idtap')
            ->orderByDesc('qty')
            ->get();

        $topProduk = DB::table('keluarsf as k')
            ->join('denom as d','k.iddenom','=','d.iddenom')
            ->select('d.denom', DB::raw('SUM(k.qty) AS qty'))
            ->whereMonth('k.tgl',$now->month)
            ->whereYear('k.tgl',$now->year)
            ->groupBy('d.denom')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        return view('home', compact(
            'stokSegel','stokInject','salesBulanIni','mom',
            'chartSales','chartInject','salesTap','topProduk','mode'
        ));
    }
}
