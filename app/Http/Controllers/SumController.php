<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SumPenjualanExport;

class SumController extends Controller
{
    public function index(Request $request)
    {
        $idtap = session('idtap');
        $month = $request->input('bulan', date('m'));
        $year = $request->input('tahun', date('y'));
        $q = DB::table('keluarsf as k')
            ->join('denom as d', 'k.iddenom', '=', 'd.iddenom')
            ->join('idsf as i','k.idsf','=','i.idsf')
            ->select('k.tgl','k.idtap', 'd.denom' ,'i.namasf',DB::raw('SUM(k.qty) as qty'))
            ->whereMonth('k.tgl', $month)
            ->whereYear('k.tgl', $year)
            ->groupBy('k.tgl', 'k.idtap', 'i.namasf','d.denom');

        if ($idtap === 'CLUSTER_DUMAI') {
            $q->whereIn('k.idtap', ['DUMAI','BENGKALIS','DURI','RUPAT','SEI PAKNING']);
        } elseif ($idtap === 'CLUSTER_ROHIL') {
            $q->whereIn('k.idtap', ['BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG']);
        } elseif ($idtap !== 'SBP_DUMAI') {
            $q->where('k.idtap', $idtap);
        }

        $data = $q->get();
            
        foreach ($data as $row) {
            $formattedDate = date('d-m-Y', strtotime($row->tgl));
            $row->tgl = $formattedDate;
        }

        return view('sumpenjualan', compact('idtap', 'month', 'data','year'));
    }

    
public function exportexcel(Request $request)
{
    $idtap = session('idtap');
    $month = $request->input('bulan', date('m'));
    $year = $request->input('tahun', date('Y')); 

    $q = DB::table('keluarsf as f')
                    ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
                    ->join('idsf as k', 'f.idsf', '=', 'k.idsf')
                    ->select('f.tgl','f.idtap', 'k.namasf', 'd.denom', DB::raw('SUM(f.qty) as qty'))
                    ->whereMonth('f.tgl', $month)
                    ->whereYear('f.tgl', $year)
                    ->groupBy('f.tgl', 'd.denom', 'f.idtap', 'k.namasf');

    if ($idtap === 'CLUSTER_DUMAI') {
        $q->whereIn('f.idtap', ['DUMAI','BENGKALIS','DURI','RUPAT','SEI PAKNING']);
    } elseif ($idtap === 'CLUSTER_ROHIL') {
        $q->whereIn('f.idtap', ['BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG']);
    } elseif ($idtap !== 'SBP_DUMAI') {
        $q->where('f.idtap', $idtap);
    }
    
    $penjualanData = $q->get();

    $monthName = date('F', mktime(0, 0, 0, $month, 1));

    $fileName = 'Penjualan_SF_' . $idtap . '_' . $year . '_' . $monthName . '.xlsx';

    // Menggunakan Maatwebsite\Excel untuk melakukan export data
    return Excel::download(new SumPenjualanExport($penjualanData), $fileName);
}
}

    
