<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BOExport;

class BOController extends Controller
{
    public function index(Request $request)
    {
        $idtap = session('idtap');
        $month = $request->input('bulan', date('m'));
        $year = $request->input('tahun', date('y'));
        $kategoritap = ['DUMAI','DURI','BENGKALIS','SEI PAKNING','RUPAT','BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG'];
        $kategoribo = ['BO DUMAI','BO BENGKALIS','BO DURI','BO BAGAN BATU','BO BAGAN SIAPI-API'];

        if($idtap =='SBP_DUMAI'){
            $data= DB::table('keluar as k')
                    ->join('denom as d', 'd.iddenom', '=','k.iddenom')
                    ->select('k.*', 'd.denom')
                    ->whereNotIn('k.pengirim',$kategoritap)
                    ->whereMonth('k.tgl',$month)
                    ->whereYear('k.tgl',$year)
                    ->get()
                    ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });

            // $denombo = DB::table('keluar as k')
            //             ->join('denom as d','d.iddenom', '=','k.iddenom')
            //             ->select('d.denom',DB::raw('sum(k.qty) as qty'))
            //             ->whereMonth('k.tgl', $month)
            //             ->whereYear('k.tgl', $year)
            //             ->groupBy('d.denom')
            //             ->get();

            // $grandTotal = $denombo->sum('qty');

        }else{

            $data= DB::table('keluar as k')
                    ->join('denom as d', 'd.iddenom', '=','k.iddenom')
                    ->select('k.*', 'd.denom')
                    ->where('k.idtap',$idtap)
                    ->whereNotIn('k.pengirim',$kategoritap)
                    ->whereMonth('k.tgl',$month)
                    ->whereYear('k.tgl',$year)
                    ->get()
                    ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });

            // $denombo = DB::table('keluar as k')
            //         ->join('denom as d','d.iddenom', '=','k.iddenom')
            //         ->select('d.denom',DB::raw('sum(k.qty) as qty'))
            //         ->whereMonth('k.tgl', $month)
            //         ->whereYear('k.tgl', $year)
            //         ->whereNotIn('k.pengirim',$kategoritap)
            //         ->where('k.pengirim',$kategoribo)
            //         ->groupBy('d.denom')
            //         ->get();

            // $grandTotal = $denombo->sum('qty');


                }
        return view ('BO', compact('data','idtap','month','year'));
  
    }

    public function keluarboform(){

        $idtap = session('idtap');

        if($idtap == 'SBP_DUMAI'){
            
            $data = DB::table('kategori_bo')
                    ->select("*")
                    ->get();
            
            return view('form/formkeluarbo', compact('data','idtap'));
                    
        }else{

            $data = DB::table('kategori_bo')
                    ->select("*")
                    ->where('idtap', $idtap)
                    ->get();
            
            return view('form/formkeluarbo', compact('data','idtap'));
        }
    }

    public function proseskeluarboform(Request $request)
    
    {
        $idtap = session('idtap');
        $pengirim = $request->input('pengirim');
        $penerima = $request->input('penerima');
        $qty = $request->input('qty');
        $iddenom = $request->input('iddenom');
        $sn = $request->input('sn');
        $tambahanket = $request->input('tambahanket');

        //cekstok bo sekarang
        $eksstokbo = DB::table('stockawalsf')
                ->select ('stock')
                ->where('idsf',$pengirim)
                ->where('iddenom', $iddenom)
                ->first();


        if($eksstokbo->stock < $qty){

            return redirect('form/formkeluarbo')->withErrors(['error' => 'Stok BO Tidak Mencukupi!']);

        }else{

        //input ke stok keluar

        DB::table('keluar')
            ->insert([
                'iddenom' => $request -> iddenom,
                'pengirim' => $request -> pengirim,
                'penerima' => $request -> penerima,
                'qty' => $request -> qty,
                'tgl' => $request -> tgl,
                'sn' => $request -> sn,
                'tambahanket' => $request -> tambahanket,
                'idtap' => $request -> penerima,
                'status' => 0,
            ]);
        
        //cekstok bo sekarang
        $eksstokbo = DB::table('stockawalsf')
                    ->select ('stock')
                    ->where('idsf', $pengirim)
                    ->where('iddenom', $iddenom)
                    ->first();

        //cek stok tap sekarang
        $eksstoktap = DB::table('stockawaltap')
                    ->select('stock')
                    ->where('idtap',$penerima)
                    ->where('iddenom', $iddenom)
                    ->first();

        //stok baru
        $newstokbo = $eksstokbo -> stock - $qty;
        $newstoktap = $eksstoktap -> stock + $qty;

        //update stok
        DB::table('stockawalsf')
            ->where('idsf', $pengirim)
            ->where('iddenom', $iddenom)
            ->update([
                'stock' => $newstokbo
            ]);
        
        DB::table('stockawaltap')
            ->where('idtap', $penerima)
            ->where('iddenom', $iddenom)
            ->update([
                'stock' => $newstoktap
            ]);
            
        return redirect('BO')->with('status','Data Berhasil Ditambahkan!');
    
        }
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
                        echo "<option value=''> -- Pilih --</option>";
                        echo "<option value='$tap->idtap'> $tap->idtap</option>";
                    }
        }else{

            $tapnya = DB::table('kategori_bo')
                        ->where('idtap', $idtapsession)
                        ->get();
    
                        foreach ($tapnya as $tap){
                            echo "<option value=''> -- Pilih --</option>";
                            echo "<option value='$tap->idtap'> $tap->idtap</option>";
                        }
        }
    }

    public function delete(Request $request, $idkeluar){

        $iddenom = $request->input('iddenom');
        $pengirim = $request->input('pengirim');
        $penerima = $request->input('penerima');
        $qty = $request->input('qty');
        $kategori = $request->input('kategori');

            $eksstoktap = DB::table('stockawaltap')
                            ->select('stock')
                            ->where('idtap', $penerima)
                            ->where('iddenom', $iddenom)
                            ->first();

            if($eksstoktap->stock < $qty){

                return redirect('BO')->withErrors(['error' => 'Stock Tap Tidak Mencukupi!']);

            }else{
                //cek stok sekarang bo
                $eksstoktap = DB::table('stockawaltap')
                            ->select('stock')
                            ->where('idtap', $penerima)
                            ->where('iddenom', $iddenom)
                            ->first();

                $eksstokbo = DB::table('stockawalsf')
                        ->select('stock')   
                        ->where('idsf',$pengirim)
                        ->where('iddenom',$iddenom)
                        ->first();

                //update stock terbaru

                $newstok = $eksstoktap -> stock - $qty;
                $newstokbo = $eksstokbo -> stock + $qty;
                
                DB::table('stockawaltap')
                    ->where('idtap', $penerima)
                    ->where('iddenom', $iddenom)
                    ->update([
                        'stock' => $newstok
                    ]);
                
                DB::table('stockawalsf')
                    ->where('idsf', $pengirim)
                    ->where('iddenom', $iddenom)
                    ->update([
                        'stock' => $newstokbo
                    ]);

                }
            
        //del dari table keluar
        DB::table('keluar')
            ->where('idkeluar', $idkeluar)
            ->delete();

            return redirect('keluar')->with('status','Data Berhasil Dihapus!');

        }

        public function exportexcel(Request $request)
        {
            $idtap = session('idtap');
            $month = $request->input('bulan', date('m'));
            $year = $request->input('tahun', date('Y')); 
            $kategoritap = ['DUMAI','DURI','BENGKALIS','SEI PAKNING','RUPAT','BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG'];
            $kategoribo = ['BO DUMAI','BO BENGKALIS','BO DURI','BO BAGAN BATU','BO BAGAN SIAPI-API'];

            if ($idtap == 'SBP_DUMAI') {
                $penjualanData = DB::table('keluar as m')
                                ->join('denom as d', 'd.iddenom', 'm.iddenom')
                                ->select('m.tgl','m.pengirim','m.penerima','d.denom',DB::raw('SUM(m.qty) as qty'))
                                ->whereMonth('m.tgl', $month)
                                ->whereYear('m.tgl', $year)
                                ->whereNotIn('m.pengirim', $kategoritap)
                                ->groupBy('m.tgl','m.pengirim','m.penerima','d.denom')
                                ->get();
            } else {
                $penjualanData = DB::table('keluar as m')
                                ->join('denom as d', 'd.iddenom', 'm.iddenom')
                                ->select('m.tgl','m.pengirim','m.penerima','d.denom',DB::raw('SUM(m.qty) as qty'))
                                ->whereMonth('m.tgl', $month)
                                ->whereYear('m.tgl', $year)
                                ->whereNotIn('m.pengirim', $kategoritap)
                                ->whereIn('m.pengirim', $kategoribo)
                                ->groupBy('m.tgl','m.pengirim','m.penerima','d.denom')
                                ->get();
            }

            $monthName = date('F', mktime(0, 0, 0, $month, 1));

            $fileName = 'RETUR_BO_' . $idtap . '_' . $year . '_' . $monthName . '.xlsx';

            // Menggunakan Maatwebsite\Excel untuk melakukan export data
            return Excel::download(new BOExport($penjualanData), $fileName);
        }




}
