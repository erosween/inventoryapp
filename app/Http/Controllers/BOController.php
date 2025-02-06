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


                }
        return view ('BO', compact('data','idtap','month','year','kategoribo'));
  
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
    $data = $request->only(['pengirim', 'penerima', 'qty', 'iddenom', 'sn', 'tambahanket', 'tgl']);
    
    // Cek stok BO
    if ($this->getStock('stockawalsf', 'idsf', $data['pengirim'], $data['iddenom']) < $data['qty']) {
        return redirect('form/formkeluarbo')->withErrors(['error' => 'Stok BO Tidak Mencukupi!']);
    }

    // Insert ke tabel keluar
    DB::table('keluar')->insert([
        'iddenom' => $data['iddenom'],
        'pengirim' => $data['pengirim'],
        'penerima' => $data['penerima'],
        'qty' => $data['qty'],
        'tgl' => $data['tgl'],
        'sn' => $data['sn'],
        'tambahanket' => $data['tambahanket'],
        'idtap' => $data['penerima'],
        'status' => 0,
    ]);

    // Update stok BO dan TAP
    $this->updateStock('stockawalsf', 'idsf', $data['pengirim'], $data['iddenom'], 
        $this->getStock('stockawalsf', 'idsf', $data['pengirim'], $data['iddenom']) - $data['qty']);

    $this->updateStock('stockawaltap', 'idtap', $data['penerima'], $data['iddenom'], 
        $this->getStock('stockawaltap', 'idtap', $data['penerima'], $data['iddenom']) + $data['qty']);

    return redirect('BO')->with('status', 'Data Berhasil Ditambahkan!');
}

public function delete(Request $request, $idkeluar)
{
    $data = $request->only(['pengirim', 'penerima', 'qty', 'iddenom']);

    // Validasi stok TAP mencukupi
    if ($this->getStock('stockawaltap', 'idtap', $data['penerima'], $data['iddenom']) < $data['qty']) {
        return redirect('BO')->withErrors(['error' => 'Stock Tap Tidak Mencukupi!']);
    }

    // Update stok TAP dan BO
    $this->updateStock('stockawaltap', 'idtap', $data['penerima'], $data['iddenom'], 
        $this->getStock('stockawaltap', 'idtap', $data['penerima'], $data['iddenom']) - $data['qty']);

    $this->updateStock('stockawalsf', 'idsf', $data['pengirim'], $data['iddenom'], 
        $this->getStock('stockawalsf', 'idsf', $data['pengirim'], $data['iddenom']) + $data['qty']);

    // Hapus dari tabel keluar
    DB::table('keluar')->where('idkeluar', $idkeluar)->delete();

    return redirect('keluar')->with('status', 'Data Berhasil Dihapus!');
}

// Helper function untuk mengambil stok
private function getStock($table, $idColumn, $idValue, $iddenom)
{
    return DB::table($table)
        ->where($idColumn, $idValue)
        ->where('iddenom', $iddenom)
        ->value('stock');
}

// Helper function untuk update stok
private function updateStock($table, $idColumn, $idValue, $iddenom, $newStock)
{
    DB::table($table)
        ->where($idColumn, $idValue)
        ->where('iddenom', $iddenom)
        ->update(['stock' => $newStock]);
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
