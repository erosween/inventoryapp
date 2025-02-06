<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InjectExport;

class InjectController extends Controller
{
    public function index(Request $request)
    {
        $idtap = session('idtap');
        $month = $request->input('bulan', date('m'));
        $year = $request->input('tahun', date('Y'));

        if($idtap == 'SBP_DUMAI'){
            $data = DB::table('injectvf')
                    ->join('denom', 'injectvf.iddenom', '=', 'denom.iddenom')
                    ->select('injectvf.*','denom.denom')
                    ->whereMonth('injectvf.tgl', '=', $month)
                    ->whereYear('injectvf.tgl', '=', $year)
                    ->orderBy('injectvf.tgl')
                    ->get()
                    ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });

            $totalinject = DB::table('injectvf as f')
                        ->join('denom as d', 'd.iddenom','=','f.iddenom')
                        ->select('d.denom',DB::raw('sum(f.qty) as qty'))
                        ->whereMonth('f.tgl',$month)
                        ->whereYear('f.tgl',$year)
                        ->groupby('d.denom')
                        ->get();
            
            $grandTotal = $totalinject->sum('qty');


        }else{

            $data = DB::table('injectvf')
                    ->join('denom', 'injectvf.iddenom', '=', 'denom.iddenom')
                    ->select('injectvf.*','denom.denom')
                    ->whereMonth('injectvf.tgl', '=', $month)
                    ->whereYear('injectvf.tgl', '=', $year)
                    ->where('injectvf.idtap',$idtap)
                    ->get()
                    ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });

            $totalinject = DB::table('injectvf as f')
                        ->join('denom as d', 'd.iddenom','=','f.iddenom')
                        ->select('d.denom',DB::raw('sum(f.qty) as qty'))
                        ->where('f.idtap',$idtap)
                        ->whereMonth('f.tgl',$month)
                        ->whereYear('f.tgl',$year)
                        ->groupby('d.denom')
                        ->get();
            
            $grandTotal = $totalinject->sum('qty');  
                        
        }
        return view('injectvf',compact('data','month','idtap','totalinject','grandTotal'));
    }

    public function delete(Request $request, $idinject)
{
    $idtap = $request->input('idtap');
    $iddenom = $request->input('iddenom');
    $qty = $request->input('qty');
    $kategori = $request->input('kategori');

    // Update stok denom dan segel
    $this->updateStok($idtap, $iddenom, $kategori, $qty);

    // Hapus data dari tabel inject
    DB::table('injectvf')->where('idinject', $idinject)->delete();

    return redirect('injectvf')->with('status', 'Data Berhasil Dihapus!');
}

/**
 * Update stok denom dan segel.
 */
private function updateStok($idtap, $iddenom, $kategori, $qty)
{
    // Cek stok denom
    $stokdenom = $this->getStok($idtap, $iddenom);
    $stoksegel = $this->getStok($idtap, $kategori);

    // Hitung stok baru
    $newStokDenomAll = $stokdenom['all'] - $qty;
    $newStokDenomTap = $stokdenom['tap'] - $qty;
    $newStokSegelAll = $stoksegel['all'] + $qty;
    $newStokSegelTap = $stoksegel['tap'] + $qty;

    // Update stok denom dan segel
    $this->modifyStok($idtap, $iddenom, $newStokDenomAll, $newStokDenomTap);
    $this->modifyStok($idtap, $kategori, $newStokSegelAll, $newStokSegelTap);
}

/**
 * Get stok untuk all dan tap.
 */
private function getStok($idtap, $iddenom)
{
    $stokAll = DB::table('stockawalall')
        ->where('idtap', $idtap)
        ->where('iddenom', $iddenom)
        ->value('stock');

    $stokTap = DB::table('stockawaltap')
        ->where('idtap', $idtap)
        ->where('iddenom', $iddenom)
        ->value('stock');

    return [
        'all' => $stokAll,
        'tap' => $stokTap
    ];
}

/**
 * Modify stok di stockawalall dan stockawaltap.
 */
private function modifyStok($idtap, $iddenom, $newStokAll, $newStokTap)
{
    DB::table('stockawalall')
        ->where('idtap', $idtap)
        ->where('iddenom', $iddenom)
        ->update(['stock' => $newStokAll]);

    DB::table('stockawaltap')
        ->where('idtap', $idtap)
        ->where('iddenom', $iddenom)
        ->update(['stock' => $newStokTap]);
}


    public function exportexcel(Request $request)
    {
        $idtap = session('idtap');
        $month = $request->input('bulan', date('m'));
        $year = $request->input('tahun', date('Y')); 

        if($idtap == 'SBP_DUMAI'){
            $penjualanData = DB::table('injectvf as f')
                            ->join('denom as d', 'd.iddenom','=','f.iddenom')
                            ->select('f.tgl','f.sn','f.idtap','d.denom',DB::raw('sum(qty) as qty'))
                            ->whereMonth('f.tgl',$month)
                            ->whereYear('f.tgl',$year)
                            ->groupBy('f.tgl','f.sn','f.idtap','d.denom')
                            ->get();

        }else{
          $penjualanData = DB::table('injectvf as f')
                            ->join('denom as d', 'd.iddenom','=','f.iddenom')
                            ->select('f.tgl','f.sn','f.idtap','d.denom',DB::raw('sum(qty) as qty'))
                            ->where('f.idtap',$idtap)
                            ->whereMonth('f.tgl',$month)
                            ->whereYear('f.tgl',$year)
                            ->groupBy('f.tgl','f.sn','f.idtap','d.denom')
                            ->get();
        }

        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        $fileName = 'Inject_' . $idtap . '_' . $year . '_' . $monthName . '.xlsx';

        // Menggunakan Maatwebsite\Excel untuk melakukan export data
        return Excel::download(new InjectExport($penjualanData), $fileName);
    }


        
    }


