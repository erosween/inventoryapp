<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RusakExport;

class VrusakController extends Controller
{
    public function index(Request $request){

        $idtap = session('idtap');
        $month = $request->input('bulan', date('m'));
        $year = $request->input('tahun', date('Y'));

        if($idtap == 'SBP_DUMAI'){

            $data = DB::table('returvfrusak')
                    ->join('denom', 'returvfrusak.iddenom','=','denom.iddenom')
                    ->select('returvfrusak.*','denom.denom')
                    ->whereMonth('returvfrusak.tgl',$month)
                    ->whereYear('returvfrusak.tgl',$year)
                    ->get()
                    ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });
        }else{

            $data = DB::table('returvfrusak')
                    ->join('denom', 'returvfrusak.iddenom','=','denom.iddenom')
                    ->select('returvfrusak.*','denom.denom')
                    ->whereMonth('returvfrusak.tgl',$month)
                    ->whereYear('returvfrusak.tgl',$year)
                    ->where('returvfrusak.idtap', $idtap)
                    ->get() ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });
        }

        
        return view('vrusak', compact('idtap','data'));
    }


    public function vrusak(){

        $idtap = session('idtap');


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

        $denom =DB::table('denom')
                ->select('*')
                ->get();

        return view('form/form-vrusak',compact('idtap','data','denom'));

    }

    public function vrusakproses(Request $request){

        $tgl = $request->input('tgl');
        $idtap = $request->input('pengirim');
        $iddenom = $request->input('iddenom');
        $qty = $request->input('qty');
        $sn = $request->input('sn');
        $ketvf = $request->input('ketvf');
        $tambahanket = $request->input('tambahanket');

        
        $stap = DB::table('stockawaltap')
                ->select('stock')
                ->where('idtap', $idtap)
                ->where('iddenom', $iddenom)
                ->first();

        //validasi qty

        if($stap -> stock < $qty){

            return redirect('form/form-vrusak')->withErrors(['error' => 'Stock Tap Tidak Mencukupi!']);

        }else{

            //cek sstok all tap pengirim

            $stap = DB::table('stockawaltap')
                    ->select('stock')
                    ->where('idtap', $idtap)
                    ->where('iddenom', $iddenom)
                    ->first();
            
            $sall = DB::table('stockawalall')
                    ->select('stock')
                    ->where('idtap', $idtap)
                    ->where('iddenom', $iddenom)
                    ->first();

            $nstoktap = $stap->stock - $qty;
            $nstokall = $sall->stock - $qty;

            //update ke stok tebraru

            DB::table('stockawaltap')
                ->where('idtap', $idtap)
                ->where('iddenom', $iddenom)
                ->update([
                    'stock' => $nstoktap
                ]);

            DB::table('stockawalall')
                ->where('idtap', $idtap)
                ->where('iddenom', $iddenom)
                ->update([
                    'stock' => $nstokall
                ]);

            //update ke table voucher rusak
            DB::table('returvfrusak')
                ->insert([
                    'idtap' => $idtap,
                    'tgl' => $tgl,
                    'qty' =>$qty,
                    'sn' => $sn,
                    'ketvf'=> $ketvf,
                    'ketlain' => $tambahanket,
                    'iddenom' => $iddenom,
                    'tgl' => $tgl
                ]);
            
            return redirect('vrusak')->with('status','Data Berhasil Ditambahkan!');
            }
    }

    public function delete(Request $request, $idrusak){

        DB::table('returvfrusak')
            ->where('idrusak', $idrusak)
            ->delete();

        $sall = DB::table('stockawalall')
                ->select('stock')
                ->where('idtap', $request->input('idtap'))
                ->where('iddenom', $request->input('iddenom'))
                ->first();
        
        $stap =  DB::table('stockawaltap')
                ->select('stock')
                ->where('idtap', $request->input('idtap'))
                ->where('iddenom', $request->input('iddenom'))
                ->first();

        $nsall = $sall -> stock + $request->input('qty');
        $nstap = $stap -> stock + $request->input('qty');

        //update ke stok

        DB::table('stockawalall')
            ->where('idtap', $request->input('idtap'))
            ->where('iddenom', $request->input('iddenom'))
            ->update([
                'stock' => $nsall
            ]);

         DB::table('stockawaltap')
            ->where('idtap', $request->input('idtap'))
            ->where('iddenom', $request->input('iddenom'))
            ->update([
                'stock' => $nstap
            ]);

        return redirect('vrusak')->with('status', 'Data Berhasil Dihapus!');
        
    }


    public function exportexcel(Request $request)
        {
            $idtap = session('idtap');
            $month = $request->input('bulan', date('m'));
            $year = $request->input('tahun', date('Y')); 

            if ($idtap == 'SBP_DUMAI') {
                $penjualanData = DB::table('returvfrusak as f')
                                    ->join('denom as d','f.iddenom','=','d.iddenom')
                                    ->select('f.tgl','d.denom',DB::raw('sum(f.qty) as qty'),'f.idtap','f.sn','f.ketvf','f.ketlain')
                                    ->whereMonth('f.tgl', $month)
                                    ->whereYear('f.tgl', $year)
                                    ->groupBy('f.tgl','d.denom','f.idtap','f.sn','f.ketvf','f.ketlain')
                                    ->get();
            } else {
                $penjualanData = DB::table('returvfrusak as f')
                                    ->join('denom as d','f.iddenom','=','d.iddenom')
                                    ->select('f.tgl','d.denom',DB::raw('sum(f.qty) as qty'),'f.idtap','f.sn','f.ketvf','f.ketlain')
                                    ->whereMonth('f.tgl', $month)
                                    ->whereYear('f.tgl', $year)
                                    ->where('f.idtap', $idtap)
                                    ->groupBy('f.tgl','d.denom','f.idtap','f.sn','f.ketvf','f.ketlain')
                                    ->get();
            }

            $monthName = date('F', mktime(0, 0, 0, $month, 1));

            $fileName = 'VOUCHER_RUSAK_' . $idtap . '_' . $year . '_' . $monthName . '.xlsx';

            // Menggunakan Maatwebsite\Excel untuk melakukan export data
            return Excel::download(new RusakExport($penjualanData), $fileName);
        }
    
}
