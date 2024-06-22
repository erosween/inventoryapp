<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasukSFExport;

class SfmasukController extends Controller
{
    public function index(Request $request){

        $idtap = session('idtap');

        $month = $request->input('bulan', date('m'));
        $year = $request->input('tahun', date('Y'));

        if($idtap == 'SBP_DUMAI'){
            $data = DB::table('masuksf')
                    ->join('denom','masuksf.iddenom', '=','denom.iddenom')
                    ->join('idsf', 'masuksf.idsf','=', 'idsf.idsf')
                    ->select('masuksf.*', 'denom.denom','idsf.namasf')
                    ->whereMonth('masuksf.tgl',$month)
                    ->whereYear('masuksf.tgl',$year)
                    ->get()
                    ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });

            $denomkeluar = DB::table('masuksf as f')
                    ->join('denom as d','d.iddenom','=','f.iddenom')
                    ->select('d.denom',DB::raw('sum(f.qty) as qty'))
                    ->whereMonth('f.tgl',$month)
                    ->whereYear('f.tgl', $year)
                    ->groupBy('d.denom')
                    ->get();

        }else{


        $data = DB::table('masuksf')
                ->join('denom','masuksf.iddenom', '=','denom.iddenom')
                ->join('idsf', 'masuksf.idsf','=', 'idsf.idsf')
                ->select('masuksf.*', 'denom.denom','idsf.namasf')
                ->whereMonth('masuksf.tgl','=', $month)
                ->whereYear('masuksf.tgl',$year)
                ->Where('masuksf.idtap',$idtap)
                ->get()
                ->map(function($item) {
                    $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                    return $item;
                });
        
        $denomkeluar = DB::table('masuksf as f')
                ->join('denom as d','d.iddenom','=','f.iddenom')
                ->select('d.denom',DB::raw('sum(f.qty) as qty'))
                ->whereMonth('f.tgl',$month)
                ->whereYear('f.tgl', $year)
                ->where('f.idtap', $idtap)
                ->groupBy('d.denom')
                ->get();

            }

        $grandTotal = $denomkeluar->sum('qty');

        return view('sf-masuk',compact('idtap', 'data', 'month','denomkeluar','year','grandTotal'));
    }

    public function formmasuksf(){

        $idtap = session('idtap');

        $denom = DB::table('denom')
                ->select('*')
                ->get();
        

        if($idtap == 'SBP_DUMAI'){

            $data = DB::table('kodetap')
            ->select('*')
            ->get();

        }else{

            $data = DB::table('kodetap')
            ->select('*')
            ->where('idtap', $idtap)
            ->get();
        }
       
        return view('form/form-sfmasuk',compact('data','idtap','denom'));

    }

    public function getSf(Request $request){

        $idtaps = $request -> idtap;

        $idtapsession = session('idtap');

        if($idtapsession == 'SBP_DUMAI'){
        
            $tapnya = DB::table('idsf')
                    ->select('*')
                    ->where('idtap', $idtaps)
                    ->get();

                    foreach ($tapnya as $tap){
                        echo "<option value='$tap->idsf'> $tap->namasf</option>";
                    }
        }else{

            $tapnya = DB::table('idsf')
                        ->where('idtap', $idtapsession)
                        ->get();
    
                        foreach ($tapnya as $tap){
                            echo "<option value='$tap->idsf'> $tap->namasf</option>";
                        }
        }

    }

    public function masuksfproses(Request $request){

        $idtap = $request -> input('idtap');
        $idsf = $request -> input('idsf');
        $iddenom = $request -> input('iddenom');
        $qty = $request -> input('qty');
        $sn = $request -> input('sn');
        $tgl = $request -> input('tgl');

        //validasi
        $stap = DB::table('stockawaltap')
                ->select('stock')
                ->where('idtap', $idtap)
                ->where('iddenom', $iddenom)
                ->first();

        if($stap ->stock < $qty){

            return redirect('form/form-sfmasuk')->withErrors(['error' => 'Stok Tap Tidak Mencukupi!']);

        }else{

            //cek stok tap
            $stap = DB::table('stockawaltap')
                    ->select('stock')
                    ->where('idtap', $idtap)
                    ->where('iddenom', $iddenom)
                    ->first();

            $ssf = DB::table('stockawalsf')
                    ->select('stock')
                    ->where('idsf', $idsf)
                    ->where('iddenom',$iddenom)
                    ->first();

            //kalkulasi
            $ntap = $stap -> stock - $qty;
            $nsf = $ssf -> stock + $qty;

            //update ke stok terbaru

            DB::table('stockawaltap')
                ->where('idtap', $idtap)
                ->where('iddenom', $iddenom)
                ->update([
                    'stock' => $ntap
                ]);

            DB::table('stockawalsf')
                ->where('idsf', $idsf)
                ->where('iddenom', $iddenom)
                ->update([
                    'stock' => $nsf
                ]);

            //update ke tbale sf masuk

            DB::table('masuksf')
                ->insert([
                    'idtap' => $idtap,
                    'idsf' => $idsf,
                    'iddenom' => $iddenom,
                    'qty' => $qty,
                    'sn' => $sn,
                    'tgl' => $tgl

                ]);
            
            return redirect('sf-masuk')->with('status','Data Berhasil Ditambahkan');
        }
    }

    public function delete(Request $request, $idmasuk)
    {
        $idtap = $request->input('idtap');
        $idsf = $request->input('idsf');
        $iddenom = $request->input('iddenom');
        $qty = $request->input('qty');
        
        //validasi stok 

        $ssf = DB::table('stockawalsf')
            ->select('stock')
            ->where('idsf', $idsf)
            ->where('iddenom', $iddenom)
            ->first();

        if($ssf -> stock < $qty){
            
            return redirect('sf-masuk')->withErrors(['error' => 'Stok SF Tidak Mencukupi']);

        }else{

            $ssf = DB::table('stockawalsf')
                ->select('stock')
                ->where('idsf', $idsf)
                ->where('iddenom', $iddenom)
                ->first();
            
            $stap = DB::table('stockawaltap')
                ->select('stock')
                ->where('idtap', $idtap)
                ->where('iddenom', $iddenom)
                ->first();

            //kalkulasi
            $nsf = $ssf->stock - $qty;
            $ntap = $stap -> stock + $qty;

            //update ke table
            DB::table('stockawalsf')
                ->where('idsf', $idsf)
                ->where('iddenom', $iddenom)
                ->update([
                    'stock' => $nsf
                ]);

            DB::table('stockawaltap')
                    ->where('idtap', $idtap)
                    ->where('iddenom', $iddenom)
                    ->update([
                        'stock' => $ntap
                    ]);
            
            DB::table('masuksf')
                ->where('idmasuk', $idmasuk)
                ->delete();

            return redirect('sf-masuk')->with('status','Data Berhasil Dihapus!');
        }
    }


    public function exportexcel(Request $request)
    {
        $idtap = session('idtap');
        $month = $request->input('bulan', date('m'));
        $year = $request->input('tahun', date('Y')); 

        if ($idtap == 'SBP_DUMAI') {
            $penjualanData = DB::table('masuksf as f')
                            ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
                            ->join('idsf as i', 'f.idsf', '=', 'i.idsf')
                            ->select('f.tgl','d.denom',DB::raw('sum(f.qty) as qty'),'f.idtap','i.namasf','f.sn')
                            ->whereMonth('f.tgl', $month)
                            ->whereYear('f.tgl', $year)
                            ->groupBy('f.tgl','d.denom','f.idtap','i.namasf','f.sn')
                            ->get();
        } else {
            $penjualanData = DB::table('masuksf as f')
                            ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
                            ->join('idsf as i', 'f.idsf', '=', 'i.idsf')
                            ->select('f.tgl','d.denom',DB::raw('sum(f.qty) as qty'),'f.idtap','i.namasf','f.sn')
                            ->whereMonth('f.tgl', $month)
                            ->whereYear('f.tgl', $year)
                            ->where('f.idtap',$idtap)
                            ->groupBy('f.tgl','d.denom','f.idtap','i.namasf','f.sn')
                            ->get();
        }

        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        $fileName = 'STOK_MASUK_SF_' . $idtap . '_' . $year . '_' . $monthName . '.xlsx';

        // Menggunakan Maatwebsite\Excel untuk melakukan export data
        return Excel::download(new MasukSFExport($penjualanData), $fileName);
    }


}
