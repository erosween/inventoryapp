<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index(Request $request)
    {

      /* ================= TIME RANGE ================= */
        $now = Carbon::now();
        $today = $now->day;

        $startThisMonth = $now->copy()->startOfMonth();
        $endThisMonth   = $now->copy();

        $startPrevMonth = $now->copy()->subMonth()->startOfMonth();
        $endPrevMonth   = $startPrevMonth->copy()
            ->addDays(min($today, $startPrevMonth->daysInMonth) - 1);

        $endPrevMonthFull = $startPrevMonth->copy()->endOfMonth();


        $tapMasuk = DB::table('masuksf')
            ->selectRaw('idtap, MAX(tgl) AS last_masuk')
            ->groupBy('idtap')
            ->pluck('last_masuk', 'idtap');

        $tapKeluar = DB::table('keluarsf')
            ->selectRaw('idtap, MAX(tgl) AS last_keluar')
            ->groupBy('idtap')
            ->pluck('last_keluar', 'idtap');

        $tapList = DB::table('keluarsf')
            ->select('idtap')
            ->distinct()
            ->orderBy('idtap')
            ->pluck('idtap');
  
        /* ================= KPI ================= */
        $stokSegel =
            DB::table('stockawaltap')->whereIn('iddenom', ['SEGEL','V16','V33'])->sum('stock')
            + DB::table('stockawalsf')->whereIn('iddenom', ['SEGEL','V16','V33'])->sum('stock');

        $stokInject =
            DB::table('stockawaltap')->whereNotIn('iddenom', ['SEGEL','V16','V33'])->sum('stock')
            + DB::table('stockawalsf')->whereNotIn('iddenom', ['SEGEL','V16','V33'])->sum('stock');

        $salesBulanIni = DB::table('keluarsf')
            ->whereBetween('tgl', [$startThisMonth, $endThisMonth])
            ->sum('qty');

        $salesPrev = DB::table('keluarsf')
            ->whereBetween('tgl', [$startPrevMonth, $endPrevMonth])
            ->sum('qty');

        $mom = $salesPrev > 0
            ? (($salesBulanIni - $salesPrev) / $salesPrev) * 100
            : 0;

        /* ================= MoM PER TAP ================= */
        $momTap = DB::table('keluarsf')
            ->selectRaw("
                idtap,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS curr_qty,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS prev_partial_qty,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS prev_full_qty
            ", [
                $startThisMonth, $endThisMonth,
                $startPrevMonth, $endPrevMonth,
                $startPrevMonth, $endPrevMonthFull
            ])
            ->groupBy('idtap')
            ->get()
            ->map(function ($r) {
                $r->mom = $r->prev_partial_qty > 0
                    ? (($r->curr_qty - $r->prev_partial_qty) / $r->prev_partial_qty) * 100
                    : 0;
                return $r;
            });


        /* ================= CLUSTER SUMMARY ================= */
        $clusterMap = [
            'dumai_bengkalis' => ['DUMAI','BENGKALIS','DURI','RUPAT','SEI PAKNING'],
            'rokan_hilir'     => ['BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG'],
        ];

        $momCluster = collect();

        foreach ($clusterMap as $key => $taps) {
            $curr = DB::table('keluarsf')->whereIn('idtap',$taps)
                ->whereBetween('tgl',[$startThisMonth,$endThisMonth])->sum('qty');

            $prevPartial = DB::table('keluarsf')->whereIn('idtap',$taps)
                ->whereBetween('tgl',[$startPrevMonth,$endPrevMonth])->sum('qty');

            $prevFull = DB::table('keluarsf')->whereIn('idtap',$taps)
                ->whereBetween('tgl',[$startPrevMonth,$endPrevMonthFull])->sum('qty');

            $momCluster[$key] = (object)[
                'curr_qty'=>$curr,
                'prev_partial_qty'=>$prevPartial,
                'prev_full_qty'=>$prevFull,
                'mom'=>$prevPartial>0?(($curr-$prevPartial)/$prevPartial)*100:0
            ];
        }

        /* ================= MoM PER SF ================= */
        $momSf = DB::table('keluarsf as k')
            ->join('idsf as s','k.idsf','=','s.idsf')
            ->selectRaw("
                k.idtap, k.idsf, s.namasf,
                SUM(CASE WHEN k.tgl BETWEEN ? AND ? THEN k.qty ELSE 0 END) AS curr_qty,
                SUM(CASE WHEN k.tgl BETWEEN ? AND ? THEN k.qty ELSE 0 END) AS prev_qty
            ", [$startThisMonth,$endThisMonth,$startPrevMonth,$endPrevMonth])
            ->groupBy('k.idtap','k.idsf','s.namasf')
            ->get()
            ->map(function ($r){
                $r->mom = $r->prev_qty>0?(($r->curr_qty-$r->prev_qty)/$r->prev_qty)*100:0;
                return $r;
            });

       $topGrowth = $momSf
            ->filter(fn($r) =>
                Str::startsWith(strtoupper($r->idsf), 'SF')
                && $r->mom > 0
            )
            ->sortByDesc('mom')
            ->take(5)
            ->values();


       $topDrop = $momSf
            ->filter(fn($r) =>
                Str::startsWith(strtoupper($r->idsf), 'SF')
                && $r->mom < 0
            )
            ->sortBy('mom') // paling minus di atas
            ->take(5)
            ->values();


        // collapse SF by TAP
       $sfByTap = $momSf
                ->filter(fn($r) => Str::startsWith(strtoupper($r->idsf), 'SF'))
                ->groupBy('idtap')
                ->map(function ($rows) {
                    return $rows->sortBy('mom')->values();
                });


        /* ================= MOM DAILY ================= */
        $momDaily = DB::table('keluarsf')
            ->selectRaw("
                DAY(tgl) AS day,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS curr_qty,
                SUM(CASE WHEN tgl BETWEEN ? AND ? THEN qty ELSE 0 END) AS prev_qty
            ", [$startThisMonth,$endThisMonth,$startPrevMonth,$endPrevMonth])
            ->groupBy(DB::raw('DAY(tgl)'))
            ->orderBy('day')
            ->get();


       /* ================= CHART SALES ================= */
        $mode = $request->query('mode', 'daily');
        if ($mode === 'monthly') {

            // ===== BULANAN (6 BULAN TERAKHIR) =====
            $chartSales = DB::table('keluarsf')
                ->selectRaw("
                    DATE_FORMAT(tgl, '%Y-%m') AS label,
                    SUM(qty) AS total
                ")
                ->where('tgl', '>=', $now->copy()->subMonths(5)->startOfMonth())
                ->groupBy(DB::raw("DATE_FORMAT(tgl, '%Y-%m')"))
                ->orderBy(DB::raw("DATE_FORMAT(tgl, '%Y-%m')"))
                ->get();

        } else {

            // ===== HARIAN (BULAN BERJALAN) =====
            $chartSales = DB::table('keluarsf')
                ->selectRaw("
                    DATE(tgl) AS label,
                    SUM(qty) AS total
                ")
                ->whereBetween('tgl', [$startThisMonth, $endThisMonth])
                ->groupBy(DB::raw('DATE(tgl)'))
                ->orderBy('label')
                ->get();
        }


        /* ================= CHART INJECT ================= */
        $chartInject = DB::table('injectvf')
            ->selectRaw("
                DATE(tgl) AS label,
                SUM(CASE WHEN idtap IN ('DUMAI','DURI','BENGKALIS','SEI PAKNING','RUPAT') THEN qty ELSE 0 END) AS dumai,
                SUM(CASE WHEN idtap IN ('BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG') THEN qty ELSE 0 END) AS rohil
            ")
            ->whereMonth('tgl',$now->month)
            ->whereYear('tgl',$now->year)
            ->groupBy(DB::raw('DATE(tgl)'))
            ->orderBy('label')
            ->get();

        $salesTap = DB::table('keluarsf')
            ->selectRaw('idtap, SUM(qty) AS qty')
            ->whereMonth('tgl',$now->month)
            ->whereYear('tgl',$now->year)
            ->groupBy('idtap')
            ->orderByDesc('qty')
            ->get();

        $topProduk = DB::table('keluarsf as k')
            ->join('denom as d','k.iddenom','=','d.iddenom')
            ->selectRaw('d.denom, SUM(k.qty) AS qty')
            ->whereMonth('k.tgl',$now->month)
            ->whereYear('k.tgl',$now->year)
            ->groupBy('d.denom')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();



     return view('home', compact('mode',
    'stokSegel','stokInject','salesBulanIni','mom',
    'chartSales','chartInject','salesTap','topProduk',
    'momTap','momCluster','momSf','topGrowth','topDrop','momDaily','tapMasuk','tapKeluar','tapList','sfByTap'));

    }
}
