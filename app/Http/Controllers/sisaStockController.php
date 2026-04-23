<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;


class sisaStockController extends Controller
{
    public function index(Request $request)
    {
        $idtap = session('idtap');
        $date = $request->input('date', date('Y-m-d'));

        $denoms = DB::table('denom')->orderBy('iddenom')->get();

        $groups = [];
        // Inisialisasi grup berdasarkan data di database
        foreach ($denoms as $d) {
            $groupName = $d->group_name;
            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [];
            }
            $groups[$groupName][] = $d;
        }

        // Urutkan grup sesuai urutan standar agar tampilan konsisten (Opsional tapi disarankan)
        $standardOrder = ['SEGEL', '1 HARI', '2 HARI', '3 HARI', '5 HARI', '7 HARI', '14 HARI', '28 HARI', '30 HARI', 'VOICE', 'LAINNYA'];
        $sortedGroups = [];
        foreach ($standardOrder as $so) {
            if (isset($groups[$so])) {
                $sortedGroups[$so] = $groups[$so];
                unset($groups[$so]);
            }
        }
        // Masukkan grup sisa jika ada yang tidak masuk dalam standardOrder
        foreach ($groups as $name => $items) {
            $sortedGroups[$name] = $items;
        }
        $groups = $sortedGroups;

        return view('sisastock', compact('idtap', 'date', 'groups'));
    }

    public function data(Request $request)
    {
        $idtap = session('idtap');
        $targetDateInput = $request->input('date');
        
        if ($targetDateInput) {
            try {
                // Prioritaskan format Y-m-d dari input type="date"
                $targetDate = Carbon::parse($targetDateInput)->format('Y-m-d');
            } catch (\Exception $e) {
                $targetDate = date('Y-m-d');
            }
        } else {
            $targetDate = date('Y-m-d');
        }
        
        $today = date('Y-m-d');

        // ============================================
        // 1. GET TRUE CURRENT STOCK
        // ============================================
        $stock = [];
        $gudang = DB::table('stockawaltap')->select('idtap', 'iddenom', 'stock');
        $sf = DB::table('stockawalsf as sf')
            ->join('idsf', 'sf.idsf', '=', 'idsf.idsf')
            ->select('idsf.idtap', 'sf.iddenom', 'sf.stock');

        $applyFilter = function ($q, $col = 'idtap') use ($idtap) {
            if ($idtap === 'CLUSTER_DUMAI') {
                $q->whereIn($col, ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING']);
            } elseif ($idtap === 'CLUSTER_ROHIL') {
                $q->whereIn($col, ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG']);
            } elseif ($idtap !== 'SBP_DUMAI') {
                $q->where($col, $idtap);
            }
        };

        $applyFilter($gudang);
        $applyFilter($sf, 'idsf.idtap');

        foreach ($gudang->unionAll($sf)->get() as $s) {
            $key = $s->idtap . '|' . $s->iddenom;
            $stock[$key] = ($stock[$key] ?? 0) + $s->stock;
        }

        // Jika targetDate < Hari Ini, kita REVERSE mutasi yang terjadi setelahnya.
        if ($targetDate < $today) {
            // Kita cari transaksi yang terjadi SETELAH target date
            // Karena ini reverse:
            // - Yang tadinya "masuk/penambah" -> dikurangi (-)
            // - Yang tadinya "keluar/pengurang" -> ditambah (+)
            $afterDate = Carbon::parse($targetDate)->addDay()->toDateString();

            // REVERSE PENAMBAH -> Jadi PENGURANG (-)

            // a. Terima dari TAP Lain (status 0)
            $terimaTap = DB::table('keluar')
                ->select('penerima as idtap', 'iddenom', DB::raw('SUM(qty) as total'))
                ->where('status', 0)
                ->where('tgl', '>=', $afterDate);
            $applyFilter($terimaTap, 'penerima');
            foreach ($terimaTap->groupBy('penerima', 'iddenom')->get() as $r) {
                $key = $r->idtap . '|' . $r->iddenom;
                $stock[$key] = ($stock[$key] ?? 0) - $r->total; // REVERSE!
            }

            // b. Inject PV (Tujuan Paket)
            $injectPv = DB::table('injectvf')
                ->select('idtap', 'iddenom', DB::raw('SUM(qty) as total'))
                ->where('tgl', '>=', $afterDate);
            $applyFilter($injectPv);
            foreach ($injectPv->groupBy('idtap', 'iddenom')->get() as $r) {
                $key = $r->idtap . '|' . $r->iddenom;
                $stock[$key] = ($stock[$key] ?? 0) - $r->total; // REVERSE!
            }

            // c. DO Masuk
            $doMasuk = DB::table('masuk')
                ->select('idtap', 'iddenom', DB::raw('SUM(qty) as total'))
                ->where('pengirim', 'DO')
                ->where('tgl', '>=', $afterDate);
            $applyFilter($doMasuk);
            foreach ($doMasuk->groupBy('idtap', 'iddenom')->get() as $r) {
                $key = $r->idtap . '|' . $r->iddenom;
                $stock[$key] = ($stock[$key] ?? 0) - $r->total; // REVERSE!
            }


            // REVERSE PENGURANG -> Jadi PENAMBAH (+)

            // d. Kirim ke TAP Lain
            $kirimTap = DB::table('keluar')
                ->select('pengirim as idtap', 'iddenom', DB::raw('SUM(qty) as total'))
                ->where('status', 0)
                ->where('tgl', '>=', $afterDate);
            $applyFilter($kirimTap, 'pengirim');
            foreach ($kirimTap->groupBy('pengirim', 'iddenom')->get() as $r) {
                $key = $r->idtap . '|' . $r->iddenom;
                $stock[$key] = ($stock[$key] ?? 0) + $r->total; // REVERSE!
            }

            // e. Inject PV (Potong Segel)
            $injectSegel = DB::table('injectvf')
                ->join('denom', 'injectvf.iddenom', '=', 'denom.iddenom')
                ->select('injectvf.idtap', 'denom.kategori_inject as iddenom', DB::raw('SUM(injectvf.qty) as total'))
                ->whereNotNull('denom.kategori_inject')
                ->where('injectvf.tgl', '>=', $afterDate);
            $applyFilter($injectSegel, 'injectvf.idtap');
            foreach ($injectSegel->groupBy('injectvf.idtap', 'denom.kategori_inject')->get() as $r) {
                $key = $r->idtap . '|' . $r->iddenom;
                $stock[$key] = ($stock[$key] ?? 0) + $r->total; // REVERSE!
            }

            // f. Penjualan Keluar SF
            $keluarSf = DB::table('keluarsf')
                ->select('idtap', 'iddenom', DB::raw('SUM(qty) as total'))
                ->where('tgl', '>=', $afterDate);
            $applyFilter($keluarSf);
            foreach ($keluarSf->groupBy('idtap', 'iddenom')->get() as $r) {
                $key = $r->idtap . '|' . $r->iddenom;
                $stock[$key] = ($stock[$key] ?? 0) + $r->total; // REVERSE!
            }

            // g. Retur PV Rusak
            $rusak = DB::table('returvfrusak')
                ->select('idtap', 'iddenom', DB::raw('SUM(qty) as total'))
                ->where('tgl', '>=', $afterDate);
            $applyFilter($rusak);
            foreach ($rusak->groupBy('idtap', 'iddenom')->get() as $r) {
                $key = $r->idtap . '|' . $r->iddenom;
                $stock[$key] = ($stock[$key] ?? 0) + $r->total; // REVERSE!
            }
        }

        // ============================================
        // 3. GENERATE DATATABLE FORMAT
        // ============================================
        $denoms = DB::table('denom')->orderBy('iddenom')->get();
        $taps = DB::table('kodetap')
            ->when(true, fn($q) => $applyFilter($q))
            ->pluck('idtap');

        $finalData = [];
        foreach ($taps as $t) {
            $row = ['idtap' => $t];
            $grand_total = 0;
            foreach ($denoms as $d) {
                $key = $t . '|' . $d->iddenom;
                $row[$d->iddenom] = $stock[$key] ?? 0;
                $grand_total += $row[$d->iddenom];
            }
            $row['grand_total'] = $grand_total;
            $finalData[] = $row;
        }

        return datatables()->of(collect($finalData))->make(true);
    }
}
