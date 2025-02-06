<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DOExport;

class InputDOController extends Controller
{
    public function index(Request $request)
    {
        $idtap = session('idtap');
        $month = $request->input('bulan',date('m'));
        $year = $request->input('tahun',date('Y'));

        if($idtap == 'SBP_DUMAI'){
            $data = DB::table('masuk')
                    ->join('denom', 'masuk.iddenom', '=', 'denom.iddenom')
                    ->select('masuk.*','denom.denom')
                    ->whereMonth('masuk.tgl', '=', $month)
                    ->whereYear('masuk.tgl', '=', $year)
                    ->where('masuk.pengirim', '=', 'DO')
                    ->orderBy('masuk.tgl')
                    ->get()
                    ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });

            $totalstock = DB::table('masuk')
                        ->select(DB::raw('sum(qty) as qty'))
                        ->whereMonth('tgl', $month)
                        ->whereYear('tgl', $year)
                        ->where('pengirim','DO')
                        ->get();

            $totalQty = $totalstock[0]->qty;

            return view('DO',compact('data','month','idtap','totalQty'));

        }else{
            $data = DB::table('masuk')
                    ->join('denom', 'masuk.iddenom', '=', 'denom.iddenom')
                    ->select('masuk.*','denom.denom')
                    ->whereMonth('masuk.tgl', $month)
                    ->where('masuk.pengirim','DO')
                    ->where('masuk.idtap', $idtap)
                    ->orderBy('masuk.tgl')
                    ->get()
                    ->map(function($item) {
                        $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                        return $item;
                    });

            $totalstock = DB::table('masuk')
                    ->select(DB::raw('sum(qty) as qty'))
                    ->where('idtap',$idtap)
                    ->where('pengirim','DO')
                    ->whereMonth('tgl', $month)
                    ->whereYear('tgl', $year)
                    ->get();

            $totalQty = $totalstock[0]->qty;

            return view('DO',compact('data','month','idtap','totalQty'));


        }
    }

    // view form DO
    public function formDO()
    {   
        $idtap =session('idtap');

        if($idtap == 'SBP_DUMAI'){
            $data = DB::table('kategori_bo')
                    ->get();
    
            return view('form/formDO',compact('data','idtap'));

        }else{

            $data = DB::table('kategori_bo')
                    ->where('idtap',$idtap)
                    ->get();
    
            return view('form/formDO',compact('data','idtap'));
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
                        echo "<option value=''> --Pilih Tap--</option>";
                        echo "<option value='$tap->idtap'> $tap->idtap</option>";
                    }
        }else{

            $tapnya = DB::table('kategori_bo')
                        ->where('idtap', $idtapsession)
                        ->get();
    
                        foreach ($tapnya as $tap){
                            echo "<option value=''> --Pilih Tap--</option>";
                            echo "<option value='$tap->idtap'> $tap->idtap</option>";
                        }
        }
    }
    
    public function masukProses(Request $request)
    {
        // Simpan data masuk
        DB::table('masuk')->insert([
            'iddenom' => $request->kategorisegel,
            'pengirim' => $request->pengirim,
            'penerima' => $request->penerima,
            'qty' => $request->qty,
            'sn' => $request->sn,
            'nomor_do' => $request->nomordo,
            'week' => $request->week,
            'tgl' => $request->tgl,
            'idtappenerima' => $request->tappenerima,
            'idtap' => $request->tappenerima
        ]);
    
        // Update stok penerima
        $this->updateStok($request->tappenerima, $request->kategorisegel, $request->penerima, $request->qty);
    
        return redirect('DO')->with('status', 'Data Berhasil Ditambahkan!');
    }
    
    public function delete(Request $request, $idmasuk)
    {
        $qty = $request->input('qty');
        $idtap = $request->input('idtappenerima');
        $idsf = $request->input('penerima');
        $iddenom = $request->input('iddenom');
    
        // Cek stok cukup
        if ($this->cekStok($idsf, $iddenom, $qty) === false) {
            return redirect('DO')->withErrors(['error' => 'Stok tidak mencukupi!']);
        }
    
        // Update stok setelah penghapusan
        $this->updateStok($idtap, $iddenom, $idsf, -$qty);
        
        // Hapus data masuk
        DB::table('masuk')->where('idmasuk', $idmasuk)->delete();
    
        return redirect('DO')->with('status', 'Data Berhasil Dihapus!');
    }
    
    /**
     * Update stok untuk penerima dan pengirim.
     */
    private function updateStok($idtap, $iddenom, $idsf, $qty)
    {
        // Update stok di stockawalall dan stockawalsf
        $this->modifyStok($idtap, $iddenom, $qty);
        $this->modifyStok($idsf, $iddenom, $qty);
    }
    
    /**
     * Cek apakah stok mencukupi.
     */
    private function cekStok($idsf, $iddenom, $qty)
    {
        $stok = DB::table('stockawalsf')
            ->where('idsf', $idsf)
            ->where('iddenom', $iddenom)
            ->value('stock');
    
        return $stok >= $qty;
    }
    
    /**
     * Modify stok di stockawalall dan stockawalsf.
     */
    private function modifyStok($idtap, $iddenom, $qty)
    {
        // Ambil stok eksisting
        $existingStockAll = DB::table('stockawalall')
            ->where('idtap', $idtap)
            ->where('iddenom', $iddenom)
            ->value('stock');
    
        $existingStockSf = DB::table('stockawalsf')
            ->where('idsf', $idtap)
            ->where('iddenom', $iddenom)
            ->value('stock');
    
        // Update stok baru
        $newStockAll = $existingStockAll + $qty;
        $newStockSf = $existingStockSf + $qty;
    
        // Update stok
        DB::table('stockawalall')
            ->where('idtap', $idtap)
            ->where('iddenom', $iddenom)
            ->update(['stock' => $newStockAll]);
    
        DB::table('stockawalsf')
            ->where('idsf', $idtap)
            ->where('iddenom', $iddenom)
            ->update(['stock' => $newStockSf]);
    }
    


public function exportexcel(Request $request)
{
    $idtap = session('idtap');
    $month = $request->input('bulan', date('m'));
    $year = $request->input('tahun', date('Y')); 

    if ($idtap == 'SBP_DUMAI') {
        $penjualanData = DB::table('masuk')
                        ->select('tgl','nomor_do','sn','idtappenerima','iddenom',DB::raw('SUM(qty) as qty'))
                        ->whereMonth('tgl', $month)
                        ->whereYear('tgl', $year)
                        ->where('pengirim', 'DO')
                        ->groupBy('tgl','nomor_do','sn','idtappenerima','iddenom')
                        ->get();
    } else {
        $penjualanData = DB::table('masuk')
                        ->select('tgl','nomor_do','sn','idtappenerima','iddenom',DB::raw('SUM(qty) as qty'))
                        ->whereMonth('tgl', $month)
                        ->whereYear('tgl', $year)
                        ->where('pengirim', 'DO')
                        ->where('idtappenerima',$idtap)
                        ->groupBy('tgl','nomor_do','sn','idtappenerima','iddenom')
                        ->get();
    }

    $monthName = date('F', mktime(0, 0, 0, $month, 1));

    $fileName = 'DO_MASUK_' . $idtap . '_' . $year . '_' . $monthName . '.xlsx';

    // Menggunakan Maatwebsite\Excel untuk melakukan export data
    return Excel::download(new DOexport($penjualanData), $fileName);
}






}

    




