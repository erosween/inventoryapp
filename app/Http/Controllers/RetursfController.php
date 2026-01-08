<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReturSFExport;

class RetursfController extends Controller
{
    public function index(Request $request){

        $idtap = session('idtap');

        $month = $request->input('bulan', date('m'));
        $year = $request->input('tahun', date('Y'));

        if($idtap == 'SBP_DUMAI'){
            $data = DB::table('retursf')
            ->join('denom','retursf.iddenom', '=','denom.iddenom')
            ->join('idsf', 'retursf.idsf','=', 'idsf.idsf')
            ->select('retursf.*', 'denom.denom','idsf.namasf')
            ->whereMonth('retursf.tgl','=', $month)
            ->get()
            ->map(function($item) {
                $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                return $item;
            });

            $denomkeluar = DB::table('retursf as f')
                            ->join('denom as d','d.iddenom','=','f.iddenom')
                            ->select('d.denom',DB::raw('sum(f.qty) as qty'))
                            ->whereMonth('f.tgl',$month)
                            ->whereYear('f.tgl', $year)
                            ->groupBy('d.denom')
                            ->get();

            $grandTotal = $denomkeluar->sum('qty');

        }else{

            $data = DB::table('retursf')
                    ->join('denom','retursf.iddenom', '=','denom.iddenom')
                    ->join('idsf', 'retursf.idsf','=', 'idsf.idsf')
                    ->select('retursf.*', 'denom.denom','idsf.namasf')
                    ->whereMonth('retursf.tgl','=', $month)
                    ->where('retursf.idtap',$idtap)
                    ->get()
                    ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });

            $denomkeluar = DB::table('retursf as f')
                        ->join('denom as d','d.iddenom','=','f.iddenom')
                        ->select('d.denom',DB::raw('sum(f.qty) as qty'))
                        ->whereMonth('f.tgl',$month)
                        ->whereYear('f.tgl', $year)
                        ->where('f.idtap',$idtap)
                        ->groupBy('d.denom')
                        ->get();

            $grandTotal = $denomkeluar->sum('qty');
        }
     
        return view('retursf',compact('idtap', 'data', 'month','year','denomkeluar','grandTotal'));

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

    public function formretursf(){

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

        $denom = DB::table('denom')
                ->select('*')
                ->get();

        return view('form/form-retursf', compact('idtap', 'data','denom'));

    }

public function retursfproses(Request $request)
{
    try {
        DB::transaction(function () use ($request) {

            $tgl      = $request->tgl;
            $idtap    = $request->idtap;
            $idsf     = $request->idsf;
            $iddenom  = $request->iddenom;
            $qty      = (int) $request->qty;
            $sn       = $request->sn;
            $ketvf    = $request->ketvf;
            $tambahket= $request->tambahket;

            // 🔒 LOCK STOK SF
            $stokSF = DB::table('stockawalsf')
                ->where('idsf', $idsf)
                ->where('iddenom', $iddenom)
                ->lockForUpdate()
                ->value('stock');

            if ($stokSF < $qty) {
                throw new \Exception('Stok SF tidak mencukupi');
            }

            // 🔒 LOCK STOK TAP
            DB::table('stockawaltap')
                ->where('idtap', $idtap)
                ->where('iddenom', $iddenom)
                ->lockForUpdate()
                ->first();

            // 📝 INSERT RETUR
            DB::table('retursf')->insert([
                'idtap'      => $idtap,
                'idsf'       => $idsf,
                'iddenom'    => $iddenom,
                'qty'        => $qty,
                'sn'         => $sn,
                'ketvf'      => $ketvf,
                'tambahket'  => $tambahket,
                'tgl'        => $tgl
            ]);

            // 🔁 UPDATE STOK
            DB::table('stockawalsf')
                ->where('idsf', $idsf)
                ->where('iddenom', $iddenom)
                ->decrement('stock', $qty);

            DB::table('stockawaltap')
                ->where('idtap', $idtap)
                ->where('iddenom', $iddenom)
                ->increment('stock', $qty);
        });

        return redirect('retursf')->with('status', 'Data berhasil ditambahkan');

    } catch (\Exception $e) {
        return redirect('form/form-retursf')->withErrors(['error' => $e->getMessage()]);
    }
}

public function delete(Request $request, $idretur)
{
    try {
        DB::transaction(function () use ($idretur) {

            $data = DB::table('retursf')
                ->where('idretur', $idretur)
                ->lockForUpdate()
                ->first();

            if (!$data) {
                throw new \Exception('Data tidak ditemukan');
            }

            // 🔒 LOCK TAP
            $stokTap = DB::table('stockawaltap')
                ->where('idtap', $data->idtap)
                ->where('iddenom', $data->iddenom)
                ->lockForUpdate()
                ->value('stock');

            if ($stokTap < $data->qty) {
                throw new \Exception('Stok TAP tidak mencukupi');
            }

            // 🔁 BALIKKAN STOK
            DB::table('stockawaltap')
                ->where('idtap', $data->idtap)
                ->where('iddenom', $data->iddenom)
                ->decrement('stock', $data->qty);

            DB::table('stockawalsf')
                ->where('idsf', $data->idsf)
                ->where('iddenom', $data->iddenom)
                ->increment('stock', $data->qty);

            DB::table('retursf')->where('idretur', $idretur)->delete();
        });

        return redirect('retursf')->with('status', 'Data berhasil dihapus');

    } catch (\Exception $e) {
        return redirect('retursf')->withErrors(['error' => $e->getMessage()]);
    }
}

    /**
     * Cek apakah stok SF mencukupi.
     */
    private function cekStok($idsf, $iddenom, $qty)
    {
        $stock = DB::table('stockawalsf')
            ->where('idsf', $idsf)
            ->where('iddenom', $iddenom)
            ->value('stock');
    
        return $stock >= $qty;
    }
    
   
    public function exportexcel(Request $request)
    {
        $idtap = session('idtap');
        $month = $request->input('bulan', date('m'));
        $year = $request->input('tahun', date('Y')); 

        if ($idtap == 'SBP_DUMAI') {
            $penjualanData = DB::table('retursf as f')
                            ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
                            ->join('idsf as i', 'f.idsf', '=', 'i.idsf')
                            ->select('f.tgl','d.denom',DB::raw('sum(f.qty) as qty'),'f.idtap','i.namasf','f.sn','f.ketvf','f.tambahket')
                            ->whereMonth('f.tgl', $month)
                            ->whereYear('f.tgl', $year)
                            ->groupBy('f.tgl','d.denom','f.idtap','i.namasf','f.sn','f.ketvf','f.tambahket')
                            ->get();
        } else {
            $penjualanData = DB::table('retursf as f')
                            ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
                            ->join('idsf as i', 'f.idsf', '=', 'i.idsf')
                            ->select('f.tgl','d.denom',DB::raw('sum(f.qty) as qty'),'f.idtap','i.namasf','f.sn','f.ketvf','f.tambahket')
                            ->whereMonth('f.tgl', $month)
                            ->whereYear('f.tgl', $year)
                            ->where('f.idtap',$idtap)
                            ->groupBy('f.tgl','d.denom','f.idtap','i.namasf','f.sn','f.ketvf','f.tambahket')
                            ->get();
        }

        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        $fileName = 'RETUR_SF_TAP_' . $idtap . '_' . $year . '_' . $monthName . '.xlsx';

        // Menggunakan Maatwebsite\Excel untuk melakukan export data
        return Excel::download(new ReturSFExport($penjualanData), $fileName);
    }



}
