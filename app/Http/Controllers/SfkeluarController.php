<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\KeluarSFExport;

class SfkeluarController extends Controller
{
    public function index(Request $request)
    {
        $idtap = session('idtap');
        $month = $request->input('bulan', date('m'));
        $year = $request->input('tahun', date('Y'));

        if ($idtap == 'SBP_DUMAI') {

            $data = DB::table('keluarsf as f')
                ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
                ->join('idsf as i', 'i.idsf', '=', 'f.idsf')
                ->select('f.*', 'd.*', 'i.*')
                ->whereMonth('f.tgl', $month)
                ->whereYear('f.tgl', $year)
                ->get()
                ->map(function ($item) {
                    $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                    return $item;
                });

            $denomkeluar = DB::table('keluarsf as f')
                ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
                ->select('d.denom', DB::raw('sum(f.qty) as qty'))
                ->whereMonth('f.tgl', $month)
                ->whereYear('f.tgl', $year)
                ->groupBy('d.denom')
                ->get();

            $grandTotal = $denomkeluar->sum('qty');
        } else {
            $data = DB::table('keluarsf as f')
                ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
                ->join('idsf as i', 'i.idsf', '=', 'f.idsf')
                ->select('f.*', 'd.*', 'i.*')
                ->whereMonth('f.tgl', $month)
                ->whereYear('f.tgl', $year)
                ->where('f.idtap', $idtap)
                ->get()
                ->map(function ($item) {
                    $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
                    return $item;
                });

            $denomkeluar = DB::table('keluarsf as f')
                ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
                ->select('d.denom', DB::raw('sum(f.qty) as qty'))
                ->where('idtap', $idtap)
                ->whereMonth('f.tgl', $month)
                ->whereYear('f.tgl', $year)
                ->groupBy('d.denom')
                ->get();

            $grandTotal = $denomkeluar->sum('qty');
        }

        return view('sf-keluar', compact('idtap', 'data', 'month', 'year', 'denomkeluar', 'grandTotal'));
    }

    public function formkeluarsf()
    {
        $idtap = session('idtap');

        $denom = DB::table('denom')
            ->select('*')
            ->get();


        if ($idtap == 'SBP_DUMAI') {

            $data = DB::table('kodetap')
                ->select('*')
                ->get();
        } else {

            $data = DB::table('kodetap')
                ->select('*')
                ->where('idtap', $idtap)
                ->get();
        }

        return view('form/form-sfkeluar', compact('data', 'idtap', 'denom'));
    }

    public function getSf(Request $request)
    {

        $idtaps = $request->idtap;

        $idtapsession = session('idtap');

        if ($idtapsession == 'SBP_DUMAI') {

            $tapnya = DB::table('idsf')
                ->select('*')
                ->where('idtap', $idtaps)
                ->get();

            foreach ($tapnya as $tap) {
                echo "option value=''> --Pilih SF-- </option>";
                echo "<option value='$tap->idsf'> $tap->namasf</option>";
            }
        } else {

            $tapnya = DB::table('idsf')
                ->where('idtap', $idtapsession)
                ->get();

            foreach ($tapnya as $tap) {
                echo "option value=''> --Pilih SF-- </option>";
                echo "<option value='$tap->idsf'> $tap->namasf</option>";
            }
        }
    }

    public function keluarsfproses(Request $request)
{
    $iddenom = $request->input('iddenom');
    $idsf = $request->input('idsf');
    $qty = $request->input('qty');
    $tgl = $request->input('tgl');
    $idtap = $request->input('idtap');
    $tambahanket = $request->input('tambahanket');

    // Validasi stok cukup
    if (!$this->cekStok($idsf, $iddenom, $qty)) {
        return redirect('form/form-sfkeluar')->withErrors(['error' => 'Stock SF Tidak Mencukupi']);
    }

    // Update stok dan simpan data keluarsf
    $this->updateStok($idtap, $idsf, $iddenom, -$qty);

    DB::table('keluarsf')->insert([
        'iddenom' => $iddenom,
        'idsf' => $idsf,
        'qty' => $qty,
        'tgl' => $tgl,
        'idtap' => $idtap,
        'tambahanket' => $tambahanket
    ]);

    return redirect('sf-keluar')->with('status', 'Data Berhasil Ditambahkan!');
}

public function delete(Request $request, $idkeluar)
{
    $iddenom = $request->input('iddenom');
    $idsf = $request->input('idsf');
    $idtap = $request->input('idtap');
    $qty = $request->input('qty');

    // Update stok dan hapus data keluarsf
    $this->updateStok($idtap, $idsf, $iddenom, $qty);

    DB::table('keluarsf')->where('idkeluar', $idkeluar)->delete();

    return redirect('sf-keluar')->with('status', 'Data Berhasil DIhapus!');
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

/**
 * Update stok di stockawalsf dan stockawalall.
 */
private function updateStok($idtap, $idsf, $iddenom, $qty)
{
    // Ambil stok eksisting
    $ssf = DB::table('stockawalsf')->where('idsf', $idsf)->where('iddenom', $iddenom)->first();
    $sall = DB::table('stockawalall')->where('idtap', $idtap)->where('iddenom', $iddenom)->first();

    // Hitung stok baru
    $newStockSF = $ssf->stock - $qty;
    $newStockTAP = $sall->stock + $qty;

    // Update stok
    DB::table('stockawalsf')->where('idsf', $idsf)->where('iddenom', $iddenom)->update(['stock' => $newStockSF]);
    DB::table('stockawalall')->where('idtap', $idtap)->where('iddenom', $iddenom)->update(['stock' => $newStockTAP]);
}


    public function exportexcel(Request $request)
    {
        $idtap = session('idtap');
        $month = $request->input('bulan', date('m'));
        $year = $request->input('tahun', date('Y'));

        if ($idtap == 'SBP_DUMAI') {
            $penjualanData = DB::table('keluarsf as f')
                ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
                ->join('idsf as i', 'f.idsf', '=', 'i.idsf')
                ->select('f.tgl', 'd.denom', DB::raw('sum(f.qty) as qty'), 'f.idtap', 'i.namasf', 'f.tambahanket')
                ->whereMonth('f.tgl', $month)
                ->whereYear('f.tgl', $year)
                ->groupBy('f.tgl', 'd.denom', 'f.idtap', 'i.namasf', 'f.tambahanket')
                ->get();
        } else {
            $penjualanData = DB::table('keluarsf as f')
                ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
                ->join('idsf as i', 'f.idsf', '=', 'i.idsf')
                ->select('f.tgl', 'd.denom', DB::raw('sum(f.qty) as qty'), 'f.idtap', 'i.namasf', 'f.tambahanket')
                ->whereMonth('f.tgl', $month)
                ->whereYear('f.tgl', $year)
                ->where('f.idtap', $idtap)
                ->groupBy('f.tgl', 'd.denom', 'f.idtap', 'i.namasf', 'f.tambahanket')
                ->get();
        }

        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        $fileName = 'PENJUALAN_SF_TAP_' . $idtap . '_' . $year . '_' . $monthName . '.xlsx';

        // Menggunakan Maatwebsite\Excel untuk melakukan export data
        return Excel::download(new KeluarSFExport($penjualanData), $fileName);
    }
}
