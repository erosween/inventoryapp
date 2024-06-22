<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeController extends Controller
{
        public function index()
        {

                $idtap = session('idtap');

                // untuk penjualan
                $bulan = Carbon::now()->format('m');
                $bulan1 = Carbon::now()->subMonths(1)->format('m');
                $bulan2 = Carbon::now()->subMonths(2)->format('m');

                $month = Carbon::now()->format('F');
                $month1 = Carbon::now()->subMonths(1)->format('F');
                $month2 = Carbon::now()->subMonths(2)->format('F');

                $year = Carbon::now()->format('Y');
                $year2023 = Carbon::now()->subYear()->format('Y');

                if ($idtap == 'SBP_DUMAI') {
                        // Daftar ID TAP yang akan diproses
                        $idTaps = ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING', 'BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'];

                        // Inisialisasi tanggal tertinggi untuk setiap TAP
                        $tanggalTertinggi = [];

                        // Mendapatkan tanggal tertinggi dari setiap TAP
                        foreach ($idTaps as $idTap) {
                                $tanggalTertinggi[$idTap] = DB::table('keluarsf')
                                        ->where('idtap', $idTap)
                                        ->whereMonth('tgl', $bulan)
                                        ->whereYear('tgl', $year)
                                        ->max('tgl');
                        }

                        // Mengambil tanggal terendah dari semua TAP
                        $tanggalTertinggiFiltered = array_filter($tanggalTertinggi); // Menghilangkan nilai NULL
                        $tanggalTerendah = !empty($tanggalTertinggiFiltered) ? min($tanggalTertinggiFiltered) : null;

                        // Jika ada tanggal terendah yang valid, maka lanjutkan
                        if ($tanggalTerendah) {
                                $tanggal = substr($tanggalTerendah, 8, 2); // Mendapatkan hari dari tanggal terendah
                                $bulan1 = $year . '-' . $bulan . '-01';
                                $eDate = $year . '-' . $bulan . '-' . $tanggal;
                        }

                        if (isset($eDate)) {
                                // Menghitung stok segel cluster
                                $segel = DB::table('stockawalall')
                                        ->whereIn('iddenom', ['SEGEL', 'V16', 'V33'])
                                        ->sum('stock');

                                // Menghitung stok inject cluster
                                $inject = DB::table('stockawalall')
                                        ->whereNotIn('iddenom', ['SEGEL', 'V16', 'V33'])
                                        ->sum('stock');

                                // Menghitung penjualan bulan ini hingga tanggal terendah
                                $sales = DB::table('keluarsf')
                                        ->whereBetween('tgl', [$bulan1, $eDate])
                                        ->sum('qty');

                                // Mengambil semua TAP
                                $tap = DB::table('kodetap')->select('idtap')->get();

                                $penjualan = [];

                                foreach ($tap as $row) {
                                        $tapId = $row->idtap;

                                        // Penjualan bulan ini sampai tanggal terendah
                                        $salesnow = DB::table('keluarsf')
                                                ->where('idtap', $tapId)
                                                ->whereBetween('tgl', [$bulan1, $eDate])
                                                ->sum('qty');

                                        // Penjualan bulan lalu sampai tanggal terendah
                                        $prevMonth = date('m', strtotime('-1 month', strtotime($bulan1)));
                                        $prevYear = date('Y', strtotime('-1 month', strtotime($bulan1)));
                                        $prevSDate = $prevYear . '-' . $prevMonth . '-01';
                                        $prevEDate = $prevYear . '-' . $prevMonth . '-' . $tanggal;

                                        $sales1 = DB::table('keluarsf')
                                                ->where('idtap', $tapId)
                                                ->whereBetween('tgl', [$prevSDate, $prevEDate])
                                                ->sum('qty');

                                        // Menambahkan data penjualan sesuai TAP
                                        $penjualan[$tapId] = [
                                                'salesnow' => $salesnow,
                                                'sales1' => $sales1,
                                        ];
                                }

                                $tglUpload = [];

                                foreach ($tap as $row) {
                                        $tapId = $row->idtap;

                                        $masuk = DB::table('masuksf')
                                                ->where('idtap', $tapId)
                                                ->max('tgl');

                                        $keluar = DB::table('keluarsf')
                                                ->where('idtap', $tapId)
                                                ->max('tgl');

                                        // Tambah tanggal sesuai TAP
                                        $tglUpload[$tapId] = [
                                                'masuk' => $masuk,
                                                'keluar' => $keluar,
                                        ];
                                }
                        }


                        //     total penjualan per denom

                        $db = ['DUMAI', 'BENGKALIS', 'DURI', 'SEI PAKNING', 'RUPAT'];
                        $denomdumai = DB::table('keluarsf as k')
                                ->join('denom as d', 'k.iddenom', '=', 'd.iddenom')
                                ->select('d.denom', DB::raw('sum(k.qty) as qty'))
                                ->whereIn('k.idtap', $db)
                                ->whereMonth('k.tgl', $bulan)
                                ->whereYear('k.tgl', $year)
                                ->groupBy('d.denom')
                                ->get();

                        $denomrohil = DB::table('keluarsf as k')
                                ->join('denom as d', 'k.iddenom', '=', 'd.iddenom')
                                ->select('d.denom', DB::raw('sum(k.qty) as qty'))
                                ->whereNotIn('k.idtap', $db)
                                ->whereMonth('k.tgl', $bulan)
                                ->whereYear('k.tgl', $year)
                                ->groupBy('d.denom')
                                ->get();

                        $grandTotaldb = $denomdumai->sum('qty');
                        $grandTotalrh = $denomrohil->sum('qty');


                        return view('home', compact('idtap', 'segel', 'inject', 'sales', 'month', 'month1', 'month2', 'penjualan', 'tglUpload', 'tanggal', 'denomdumai', 'denomrohil', 'grandTotaldb', 'grandTotalrh'));
                } else {

                        //ambil tanggal max di keluar sf

                        $maxtgl = DB::table('keluarsf')
                                ->max('tgl');
                        $tanggal = substr($maxtgl, 8, 2);

                        //start date and endtae for MOM
                        $sDate = $year . '-' . $bulan1 . '-01';
                        $eDate = $year . '-' . $bulan1 . '-' . $tanggal;

                        //cek stok segel cluster
                        $segel = DB::table('stockawalall')
                                ->where('iddenom', 'SEGEL')
                                ->ORwhere('iddenom', 'V16')
                                ->ORwhere('iddenom', 'V33')
                                ->sum('stock');

                        //cek stok inject cluster
                        $inject = DB::table('stockawalall')
                                ->where('iddenom', '<>', 'SEGEL')
                                ->where('iddenom', '<>', 'V16')
                                ->where('iddenom', '<>', 'V33')
                                ->sum('stock');

                        //penjualan
                        $sales = DB::table('keluarsf')
                                ->whereMonth('tgl', $bulan)
                                ->whereYear('tgl', $year)
                                ->sum('qty');

                        //penjualan pertap

                        $tap = DB::table('kodetap')
                                ->select('idtap')
                                ->get();



                        $segel1 = DB::table('stockawalall')
                                ->where('idtap', $idtap)
                                ->where('iddenom', 'SEGEL')
                                ->sum('stock');

                        $segel2 = DB::table('stockawalall')
                                ->where('idtap', $idtap)
                                ->where('iddenom', 'V16')
                                ->sum('stock');

                        $segel3 = DB::table('stockawalall')
                                ->where('idtap', $idtap)
                                ->where('iddenom', 'V33')
                                ->sum('stock');



                        $segel = $segel1 + $segel2 + $segel3;

                        //cek stok inject cluster
                        $inject = DB::table('stockawalall')
                                ->where('idtap', $idtap)
                                ->where('iddenom', '<>', 'SEGEL')
                                ->where('iddenom', '<>', 'V16')
                                ->where('iddenom', '<>', 'V33')
                                ->sum('stock');

                        //penjualan
                        $sales = DB::table('keluarsf')
                                ->where('idtap', $idtap)
                                ->whereMonth('tgl', $bulan)
                                ->whereYear('tgl', $year)
                                ->sum('qty');

                        //penjualan pertap
                        $tap = DB::table('kodetap')
                                ->select('idtap')
                                ->get();

                        $penjualan = [];

                        foreach ($tap as $row) {

                                $tapId = $row->idtap;

                                $salesnow = DB::table('keluarsf')
                                        ->whereMonth('tgl', $bulan)
                                        ->whereYear('tgl', $year)
                                        ->where('idtap', $tapId)
                                        ->sum('qty');

                                $sales1 = DB::table('keluarsf')
                                        ->whereBetween('tgl', [$sDate, $eDate])
                                        ->where('idtap', $tapId)
                                        ->sum('qty');

                                //menambahkAn data penjualan sesuai tap
                                $penjualan[$tapId] = [
                                        'salesnow' => $salesnow,
                                        'sales1' => $sales1,
                                ];
                        }

                        $tglUpload = [];

                        foreach ($tap as $row) {
                                $tapId = $row->idtap;

                                $masuk = DB::table('masuksf')
                                        ->where('idtap', $tapId)
                                        ->max('tgl');

                                $keluar = DB::table('keluarsf')
                                        ->where('idtap', $tapId)
                                        ->max('tgl');

                                //tambah tanggal sesuai tap
                                $tglUpload[$tapId] = [
                                        'masuk' => $masuk,
                                        'keluar' => $keluar
                                ];
                        }

                        //     total penjualan per denom
                        $salesdenom = DB::table('keluarsf as k')
                                ->join('denom as d', 'k.iddenom', '=', 'd.iddenom')
                                ->select('d.denom', DB::raw('sum(k.qty) as qty'))
                                ->where('k.idtap', $idtap)
                                ->whereMonth('k.tgl', $bulan)
                                ->whereYear('k.tgl', $year)
                                ->groupBy('d.denom')
                                ->get();

                        $grandTotal = $salesdenom->sum('qty');
                }

                return view('home', compact('idtap', 'segel', 'inject', 'sales', 'month', 'month1', 'month2', 'penjualan', 'tglUpload', 'tanggal', 'salesdenom', 'grandTotal'));
        }
}
