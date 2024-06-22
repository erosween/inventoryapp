<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasukExport;

class MasukController extends Controller
{
    public function index(Request $request)

    {
        
        $idtap = session('idtap');
        $month = $request->input('bulan', date('m'));
        $year = $request->input('tahun', date('Y'));
        $kategoribo = ['BO DUMAI', 'BO DURI', 'BO BENGKALIS', 'BO BAGAN BATU', 'BO BAGAN SIAPI-API'];

        if($idtap == 'SBP_DUMAI'){
            $unapprovedCount = DB::table('keluar')
                    ->where('status',1)
                    ->count();

            $data = DB::table('keluar as k')
                    ->join('denom as d', 'd.iddenom', '=', 'k.iddenom')
                    ->select('k.*','d.denom')
                    ->whereMonth('k.tgl', '=', $month)
                    ->whereYear('k.tgl', '=', $year)
                    ->whereNotIn('k.pengirim', $kategoribo)
                    ->orderBy('k.tgl')
                    ->get()
                    ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });

            $totalstock = DB::table('keluar')
                        ->select(DB::raw('sum(qty) as qty'))
                        ->whereMonth('tgl', $month)
                        ->whereYear('tgl', $year)
                        ->whereNotIn('pengirim', $kategoribo)
                        ->get();

            $totalQty = $totalstock[0]->qty;


            // untuk total perdenom masuk

            $denommasuk = DB::table('keluar as k')
                        ->join('denom as d', 'd.iddenom','=','k.iddenom')
                        ->select('d.denom',DB::raw('sum(k.qty) as qty'))
                        ->whereMonth('k.tgl', $month)
                        ->whereYear('k.tgl', $year)
                        ->whereNotIn('k.pengirim', $kategoribo)
                        ->groupBy('d.denom')
                        ->get();

            $grandTotal = $denommasuk->sum('qty');
            
        }else{
            $unapprovedCount = DB::table('keluar')
                    ->where('status',1)
                    ->where('penerima',$idtap)
                    ->count();

            $data = DB::table('keluar as k')
                    ->join('denom as d', 'd.iddenom', '=', 'k.iddenom')
                    ->select('k.*','d.denom')
                    ->whereMonth('k.tgl', '=', $month)
                    ->whereYear('k.tgl', '=', $year)
                    ->whereNotIn('k.pengirim', $kategoribo)
                    ->where('k.penerima', $idtap)
                    ->orderBy('k.tgl')
                    ->get()
                    ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });


               // untuk total perdenom masuk

               $denommasuk = DB::table('keluar as k')
                            ->join('denom as d', 'd.iddenom','=','k.iddenom')
                            ->select('d.denom',DB::raw('sum(k.qty) as qty'))
                            ->whereMonth('k.tgl', $month)
                            ->whereYear('k.tgl', $year)
                            ->whereNotIn('k.pengirim', $kategoribo)
                            ->where('k.penerima', $idtap)
                            ->groupBy('d.denom')
                            ->get();
                
                $grandTotal = $denommasuk->sum('qty');            

            }
            return view('masuk',compact('data','month','idtap' ,'unapprovedCount','denommasuk','grandTotal'));
    }


    // proses terima stock
    public function masuk(Request $request, $idkeluar)
    {
        $pengirim = $request->input('pengirim');
        $penerima = $request->input('penerima');
        $iddenom = $request -> input('iddenom');
        $qty = $request -> input('qty');
        $status = $request -> input('status');

        //validasi
        $stap = DB::table('stockawaltap')
                    ->select('stock')
                    ->where('idtap', $pengirim)
                    ->where('iddenom', $iddenom)
                    ->first();

        if($stap->stock < $qty){

            return redirect('masuk')->withErrors(['error' => 'Stok Tap Pengirim Tidak Mencukupi!']);

        }else{

            //cek stok tap pengirim
            $pall = DB::table('stockawalall')
                    ->select('stock')
                    ->where('idtap', $pengirim)
                    ->where('iddenom', $iddenom)
                    ->first();

            $ptap = DB::table('stockawaltap')
                    ->select('stock')
                    ->where('idtap', $pengirim)
                    ->where('iddenom',$iddenom)
                    ->first();

            //kalkulasi
            $nall = $pall -> stock - $qty;
            $ntap = $ptap -> stock - $qty;

            // cek stok penerima
            $penall = DB::table('stockawalall')
                    ->select('stock')
                    ->where('idtap', $penerima)
                    ->where('iddenom', $iddenom)
                    ->first();

            $pentap = DB::table('stockawaltap')
                    ->select('stock')
                    ->where('idtap', $penerima)
                    ->where('iddenom',$iddenom)
                    ->first();

            //kalkulasi
            $npall = $penall -> stock + $qty;
            $nptap = $pentap -> stock + $qty;

            //update ke stok terbaru

            //pengirim

            DB::table('stockawalall')
                ->where('idtap', $pengirim)
                ->where('iddenom', $iddenom)
                ->update([
                    'stock' => $nall
                ]);

            DB::table('stockawaltap')
                ->where('idtap', $pengirim)
                ->where('iddenom', $iddenom)
                ->update([
                    'stock' => $ntap
                ]);

            // penerima
            
            DB::table('stockawalall')
            ->where('idtap', $penerima)
            ->where('iddenom', $iddenom)
            ->update([
                'stock' => $npall
            ]);

            DB::table('stockawaltap')
                ->where('idtap', $penerima)
                ->where('iddenom', $iddenom)
                ->update([
                    'stock' => $nptap
                ]);

            //update status

            DB::table('keluar')
                ->where('idkeluar', $idkeluar)
                ->update([
                    'status' => 0
                ]);
            
            return redirect('masuk')->with('status','Stock Berhasil Diterima');
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
                        ->where('m.penerima', $idtap)
                        ->groupBy('m.tgl','m.sn','m.pengirim','m.penerima','d.denom')
                        ->get();
    }

    $monthName = date('F', mktime(0, 0, 0, $month, 1));

    $fileName = 'STOK_MASUK_' . $idtap . '_' . $year . '_' . $monthName . '.xlsx';

    // Menggunakan Maatwebsite\Excel untuk melakukan export data
    return Excel::download(new MasukExport($penjualanData), $fileName);
}



}
