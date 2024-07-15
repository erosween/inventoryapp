<?php

namespace App\Http\Controllers;

use App\Exports\WLNocanExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class HomenocanController extends Controller
{
    public function index()
    {
        $idtap = session('idtap');
        if ($idtap == 'SB DUMAI') {
            $targets = DB::table('targetnocan')
                        ->where('cluster', 'DUMAI BENGKALIS')
                        ->get();

            // Mengambil data dari tabel nocan dan menghitung total sold dan total insentif per tap dan grade
            $nocans = DB::table('nocan')
                ->select(
                    'tap',
                    'grade',
                    DB::raw('SUM(CASE WHEN status IN ("sold", "paid") THEN 1 ELSE 0 END) as total_sold'),
                    DB::raw('SUM(CASE WHEN status IN ("sold", "paid") THEN insentif ELSE 0 END) as total_insentif')
                )
                ->whereIn('status', ['sold', 'paid'])
                ->where('outlet', "!=", null)
                ->where('outlet', "!=", 1)
                ->groupBy('tap', 'grade')
                ->get();

            // Mendefinisikan grades
            $grades = ['A', 'B', 'C', 'D'];

            // Menggabungkan data targets dan nocans berdasarkan tap dan grade
            $datas = $targets->map(function ($target) use ($nocans, $grades) {
                $targetData = [
                    'tap' => $target->tap,
                    'target' => $target->target,
                    'grades' => [],
                    'total_sold' => 0,
                    'total_insentif' => 0,
                ];

                foreach ($grades as $grade) {
                    $nocan = $nocans->first(function ($item) use ($target, $grade) {
                        return $item->tap === $target->tap && $item->grade === $grade;
                    });

                    $total_sold = $nocan ? $nocan->total_sold : 0;
                    $targetData['grades'][$grade] = $total_sold;
                    $targetData['total_sold'] += $total_sold;

                    $insentif = $nocan ? $nocan->total_insentif : 0;
                    $targetData['total_insentif'] += $insentif;
                }

                return (object) $targetData;
            });

            // Menghitung grand total
            $grandTotals = [
                'target' => $datas->sum('target'),
                'grades' => [],
                'total_sold' => $datas->sum('total_sold'),
                'total_insentif' => $datas->sum('total_insentif'),
            ];

            foreach ($grades as $grade) {
                $grandTotals['grades'][$grade] = $datas->sum(function ($item) use ($grade) {
                    return $item->grades[$grade];
                });
            }


            // start total all
            $data = DB::table('nocan')
                ->select(
                    'tap',
                    DB::raw('count(*) as total_nomor'),
                    DB::raw('SUM(CASE WHEN status = "booking" THEN 1 ELSE 0 END) as total_status_booking'),
                    DB::raw('SUM(CASE WHEN status = "sold" THEN 1 ELSE 0 END) as total_status_sold'),
                    DB::raw('SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as total_status_paid')
                )
                ->where('cluster', 'DUMAI BENGKALIS')
                ->where('status', '!=', 'ready')
                ->whereNotNull('tap')
                ->where('tap', '!=', '')
                ->groupBy('tap')
                ->get();

            
            $grandTotalBooking = $data->sum('total_status_booking');
            $grandTotalSold = $data->sum('total_status_sold');
            $grandTotalPaid = $data->sum('total_status_paid');


            // DETAIL PENJUALAN
            $datadetail = DB::table('nocan')
                        ->select(
                            'tap',
                            DB::raw('count(*) as total_nomor'),
                            DB::raw('SUM(outlet = 1) as total_karyawan'),
                            DB::raw('SUM(CASE WHEN status = "sold" or status = "paid" THEN 1 ELSE 0 END and outlet != 1 and outlet != 0) as total_penjualan'),
                            DB::raw('SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as total_status_paid')
                        )
                        ->where('cluster', 'DUMAI BENGKALIS')
                        // ->where('status', '!=', 'ready')
                        ->whereNotNull('tap')
                        ->where('tap', '!=', '')
                        ->groupBy('tap')
                        ->get();

            $grandTotalNomor = $datadetail->sum('total_nomor');
            $grandTotalKaryawan = $datadetail->sum('total_karyawan');
            $grandTotalPenjualan = $datadetail->sum('total_penjualan');

            // sales
            $dataSF = DB::table('nocan')
                ->select(
                    'booked',
                    'tap',
                    DB::raw('count(*) as total_nomor'),
                    DB::raw('SUM(CASE WHEN status = "booking" THEN 1 ELSE 0 END) as total_status_booking'),
                    DB::raw('SUM(CASE WHEN status = "sold" THEN 1 ELSE 0 END) as total_status_sold'),
                    DB::raw('SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as total_status_paid')
                )
                ->where('cluster', 'DUMAI BENGKALIS')
                ->where('status', '!=', 'ready')
                ->whereNotNull('tap')
                ->where('tap', '!=', '')
                ->groupBy('tap', 'booked')
                ->get();

            $grandTotalBookingsf = $dataSF->sum('total_status_booking');
            $grandTotalSoldsf = $dataSF->sum('total_status_sold');
            $grandTotalPaidsf = $dataSF->sum('total_status_paid');

            $sold = DB::table('nocan')
                ->where('status', 'sold')
                ->where('cluster', 'DUMAI BENGKALIS')
                ->count('nomor');

            $paid = DB::table('nocan')
                ->where('status', 'paid')
                ->where('cluster', 'DUMAI BENGKALIS')
                ->count('nomor');

            $booking = DB::table('nocan')
                ->where('status', 'booking')
                ->where('cluster', 'DUMAI BENGKALIS')
                ->count('nomor');

            $ready = DB::table('nocan')
                ->where('status', 'ready')
                ->where('cluster', 'DUMAI BENGKALIS')
                ->count('nomor');
                return view('/homenocan', ['grandTotals' => (object) $grandTotals, 'datas' => $datas, 'sold' => $sold, 'booking' => $booking, 'ready' => $ready, 'paid' => $paid], compact('idtap','datadetail', 'data', 'grandTotalBooking','grandTotalPaid', 'grandTotalNomor','grandTotalKaryawan','grandTotalSold',  'dataSF', 'grandTotalBookingsf', 'grandTotalSoldsf', 'grandTotalPaidsf','grandTotalPenjualan'));
        } else {

            $targets = DB::table('targetnocan')
                        ->where('cluster',"!=", 'DUMAI BENGKALIS')
                        ->get();

            // Mengambil data dari tabel nocan dan menghitung total sold dan total insentif per tap dan grade
            $nocans = DB::table('nocan')
                ->select(
                    'tap',
                    'grade',
                    DB::raw('SUM(CASE WHEN status IN ("sold", "paid") THEN 1 ELSE 0 END) as total_sold'),
                    DB::raw('SUM(CASE WHEN status IN ("sold", "paid") THEN insentif ELSE 0 END) as total_insentif')
                )
                ->whereIn('status', ['sold', 'paid'])
                ->where('outlet', "!=", null)
                ->where('outlet', "!=", 1)
                ->groupBy('tap', 'grade')
                ->get();

            // Mendefinisikan grades
            $grades = ['A', 'B', 'C', 'D'];

            // Menggabungkan data targets dan nocans berdasarkan tap dan grade
            $datas = $targets->map(function ($target) use ($nocans, $grades) {
                $targetData = [
                    'tap' => $target->tap,
                    'target' => $target->target,
                    'grades' => [],
                    'total_sold' => 0,
                    'total_insentif' => 0,
                ];

                foreach ($grades as $grade) {
                    $nocan = $nocans->first(function ($item) use ($target, $grade) {
                        return $item->tap === $target->tap && $item->grade === $grade;
                    });

                    $total_sold = $nocan ? $nocan->total_sold : 0;
                    $targetData['grades'][$grade] = $total_sold;
                    $targetData['total_sold'] += $total_sold;

                    $insentif = $nocan ? $nocan->total_insentif : 0;
                    $targetData['total_insentif'] += $insentif;
                }

                return (object) $targetData;
            });

            // Menghitung grand total
            $grandTotals = [
                'target' => $datas->sum('target'),
                'grades' => [],
                'total_sold' => $datas->sum('total_sold'),
                'total_insentif' => $datas->sum('total_insentif'),
            ];

            foreach ($grades as $grade) {
                $grandTotals['grades'][$grade] = $datas->sum(function ($item) use ($grade) {
                    return $item->grades[$grade];
                });
            }


            // start total all
            $data = DB::table('nocan')
                ->select(
                    'tap',
                    DB::raw('count(*) as total_nomor'),
                    DB::raw('SUM(CASE WHEN status = "booking" THEN 1 ELSE 0 END) as total_status_booking'),
                    DB::raw('SUM(CASE WHEN status = "sold" THEN 1 ELSE 0 END) as total_status_sold'),
                    DB::raw('SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as total_status_paid')
                )
                ->where('cluster',"!=", 'DUMAI BENGKALIS')
                ->where('status', '!=', 'ready')
                ->whereNotNull('tap')
                ->where('tap', '!=', '')
                ->groupBy('tap')
                ->get();

            
            $grandTotalBooking = $data->sum('total_status_booking');
            $grandTotalSold = $data->sum('total_status_sold');
            $grandTotalPaid = $data->sum('total_status_paid');


            // DETAIL PENJUALAN
            $datadetail = DB::table('nocan')
            ->leftJoin('targetnocan', 'nocan.tap', '=', 'targetnocan.tap') // Join with targetnocan table on 'tap' column
            ->select(
                'nocan.tap',
                'targetnocan.target', // Count the target values from targetnocan
                DB::raw('SUM(outlet = 1) as total_karyawan'),
                DB::raw('SUM(CASE WHEN status = "sold" or status = "paid" THEN 1 ELSE 0 END and outlet != 1 and outlet != 0) as total_penjualan'),
                DB::raw('SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as total_status_paid')
            )
            ->where('nocan.cluster', '!=', 'DUMAI BENGKALIS')
            // ->where('nocan.status', '!=', 'ready')
            ->whereNotNull('nocan.tap')
            ->where('nocan.tap', '!=', '')
            ->groupBy('nocan.tap','targetnocan.target') // Group by nocan.tap
            ->get();

            $grandTotalKaryawan = $datadetail->sum('total_karyawan');
            $grandTotalPenjualan = $datadetail->sum('total_penjualan');
            $grandTotalTarget = $datadetail->sum('target'); // Sum the total target values



            // sales
            $dataSF = DB::table('nocan')
                ->select(
                    'booked',
                    'tap',
                    DB::raw('count(*) as total_nomor'),
                    DB::raw('SUM(CASE WHEN status = "booking" THEN 1 ELSE 0 END) as total_status_booking'),
                    DB::raw('SUM(CASE WHEN status = "sold" THEN 1 ELSE 0 END) as total_status_sold'),
                    DB::raw('SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as total_status_paid')
                )
                ->where('cluster',"!=", 'DUMAI BENGKALIS')
                ->where('status', '!=', 'ready')
                ->whereNotNull('tap')
                ->where('tap', '!=', '')
                ->groupBy('tap', 'booked')
                ->get();

            $grandTotalBookingsf = $dataSF->sum('total_status_booking');
            $grandTotalSoldsf = $dataSF->sum('total_status_sold');
            $grandTotalPaidsf = $dataSF->sum('total_status_paid');

            $sold = DB::table('nocan')
                ->where('status', 'sold')
                ->where('cluster',"!=", 'DUMAI BENGKALIS')
                ->count('nomor');

            $paid = DB::table('nocan')
                ->where('status', 'paid')
                ->where('cluster',"!=", 'DUMAI BENGKALIS')
                ->count('nomor');

            $booking = DB::table('nocan')
                ->where('status', 'booking')
                ->where('cluster',"!=", 'DUMAI BENGKALIS')
                ->count('nomor');
                
            $ready = DB::table('nocan')
                ->where('status', 'ready')
                ->where('cluster',"!=", 'DUMAI BENGKALIS')
                ->count('nomor');
                return view('/homenocan', ['grandTotals' => (object) $grandTotals, 'datas' => $datas, 'sold' => $sold, 'booking' => $booking, 'ready' => $ready, 'paid' => $paid], compact('idtap','datadetail', 'data', 'grandTotalBooking', 'grandTotalTarget','grandTotalPaid','grandTotalKaryawan','grandTotalSold',  'dataSF', 'grandTotalBookingsf', 'grandTotalSoldsf', 'grandTotalPaidsf','grandTotalPenjualan'));
        }
    }

    public function exportexcel()
    {
        $idtap = session('idtap');

        if ($idtap == 'SB DUMAI') {
            $penjualanData = DB::table('nocan')
                ->select('tanggal', 'cluster', 'tap', 'nomor', 'booked', 'harga', 'status', 'outlet', 'grade', 'insentif')
                ->where('cluster', 'DUMAI BENGKALIS')
                // ->where('status', '!=', 'ready')
                ->get();
        } else {
            $penjualanData = DB::table('nocan')
                ->select('tanggal', 'cluster', 'tap', 'nomor', 'booked', 'harga', 'status', 'outlet', 'grade', 'insentif')
                ->where('cluster', '!=', 'DUMAI BENGKALIS')
                // ->where('status', '!=', 'ready')
                ->get();
        }

        $fileName = 'WHITELIST_NOCAN_' . $idtap  . '.xlsx';

        // Menggunakan Maatwebsite\Excel untuk melakukan export data
        return Excel::download(new WLNocanExport($penjualanData), $fileName);
    }
}
