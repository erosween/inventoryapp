<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\KeluarExport;

class KeluarController extends Controller
{
    public function index(Request $request)
    {
        $idtap = session('idtap');
        $month = $request->input('bulan', date('m'));
        $year = $request->input('tahun', date('Y'));
        $kategoribo = ['BO DUMAI','BO BENGKALIS','BO BAGAN BATU','BO BAGAN SIAPI-API','BO DURI'];
        
        if($idtap == 'SBP_DUMAI'){
            $data = DB::table('keluar as k')
                    ->join('denom as d','k.iddenom', '=','d.iddenom')
                    ->whereMonth('k.tgl',$month)
                    ->whereYear('k.tgl',$year)
                    ->whereNotIn('k.pengirim',$kategoribo)
                    ->get()
                    ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });
        
            // untuk total perdenom keluar
            $denomkeluar = DB::table('keluar as k')
                        ->join('denom as d', 'd.iddenom','=','k.iddenom')
                        ->select('d.denom',DB::raw('sum(k.qty) as qty'))
                        ->whereMonth('k.tgl', $month)
                        ->whereYear('k.tgl', $year)
                        ->whereNotIn('k.pengirim', $kategoribo)
                        ->groupBy('d.denom')
                        ->get();    
                        
            $grandTotal = $denomkeluar->sum('qty');

        }else{
            $data = DB::table('keluar as k')
                    ->join('denom as d','k.iddenom', '=','d.iddenom')
                    ->whereMonth('k.tgl',$month)
                    ->whereYear('k.tgl',$year)
                    ->where('k.pengirim',$idtap)
                    ->whereNotIn('k.pengirim',$kategoribo)
                    ->get()
                    ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });
        
            // untuk total perdenom keluar
            $denomkeluar = DB::table('keluar as k')
                    ->join('denom as d', 'd.iddenom','=','k.iddenom')
                    ->select('d.denom',DB::raw('sum(k.qty) as qty'))
                    ->whereMonth('k.tgl', $month)
                    ->whereYear('k.tgl', $year)
                    ->whereNotIn('k.pengirim', $kategoribo)
                    ->where('k.pengirim', $idtap)
                    ->groupBy('d.denom')
                    ->get();   

            $grandTotal = $denomkeluar->sum('qty');        
        }
        return view('keluar', compact('data','idtap','month','kategoribo','denomkeluar','grandTotal'));
    }

    public function getTap(Request $request)
    {

        $idtaps = $request -> idtap;

        $idtapsession = session('idtap');

        if($idtapsession == 'SBP_DUMAI'){
        
            $tapnya = DB::table('kategori_bo')
                    ->select('*')
                    ->where('namabo', $idtaps)
                    ->get();

                    foreach ($tapnya as $tap){
                        echo "<option value='$tap->idtap'> $tap->idtap</option>";
                    }
        }else{

            $tapnya = DB::table('kategori_bo')
                        ->where('idtap', $idtapsession)
                        ->get();
    
                        foreach ($tapnya as $tap){
                            echo "<option value='$tap->idtap'> $tap->idtap</option>";
                        }
        }
    }

        public function exportexcel(Request $request)
        {
            $idtap = session('idtap');
            $month = $request->input('bulan', date('m'));
            $year = $request->input('tahun', date('Y')); 
            $kategoribo = ['BO DUMAI', 'BO DURI', 'BO BENGKALIS', 'BO BAGAN BATU', 'BO BAGAN SIAPI-API'];

            if ($idtap == 'SBP_DUMAI') {
                $penjualanData = DB::table('keluar as m')
                                ->join('denom as d', 'd.iddenom', 'm.iddenom')
                                ->select('m.tgl','m.sn','m.pengirim','m.penerima','d.denom',DB::raw('SUM(m.qty) as qty'))
                                ->whereMonth('m.tgl', $month)
                                ->whereYear('m.tgl', $year)
                                ->whereNotIn('m.pengirim', $kategoribo)
                                ->groupBy('m.tgl','m.sn','m.pengirim','m.penerima','d.denom')
                                ->get();
            } else {
                $penjualanData = DB::table('keluar as m')
                                ->join('denom as d', 'd.iddenom', 'm.iddenom')
                                ->select('m.tgl','m.sn','m.pengirim','m.penerima','d.denom',DB::raw('SUM(m.qty) as qty'))
                                ->whereMonth('m.tgl', $month)
                                ->whereYear('m.tgl', $year)
                                ->whereNotIn('m.pengirim', $kategoribo)
                                ->where('m.pengirim', $idtap)
                                ->groupBy('m.tgl','m.sn','m.pengirim','m.penerima','d.denom')
                                ->get();
            }

            $monthName = date('F', mktime(0, 0, 0, $month, 1));

            $fileName = 'STOK_KELUAR_' . $idtap . '_' . $year . '_' . $monthName . '.xlsx';

            // Menggunakan Maatwebsite\Excel untuk melakukan export data
            return Excel::download(new KeluarExport($penjualanData), $fileName);
        }


    
}

