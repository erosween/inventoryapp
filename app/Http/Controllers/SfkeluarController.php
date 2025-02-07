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
    // Ambil data dari request
    $data = $request->only(['iddenom', 'idsf', 'qty', 'tgl', 'idtap', 'tambahanket']);

    // Validasi stok SF
    $ssf = DB::table('stockawalsf')
                ->where('iddenom', $data['iddenom'])
                ->where('idsf', $data['idsf'])
                ->first();

    if (!$ssf || $ssf->stock < $data['qty']) {
        return redirect('form/form-sfkeluar')->withErrors(['error' => 'Stock SF Tidak Mencukupi']);
    }

    // Update stok SF dan All
    $this->updateStock('stockawalsf', $data['iddenom'], $data['idsf'], -$data['qty']);
    $this->updateStock('stockawalall', $data['iddenom'], $data['idtap'], -$data['qty']);

    // Insert data ke tabel keluarsf
    DB::table('keluarsf')->insert($data);

    return redirect('sf-keluar')->with('status', 'Data Berhasil Ditambahkan!');
}

public function delete(Request $request, $idkeluar)
{
    // Ambil data dari request
    $data = $request->only(['iddenom', 'idsf', 'idtap', 'qty']);

    // Update stok SF dan All
    $this->updateStock('stockawalsf', $data['iddenom'], $data['idsf'], $data['qty']);
    $this->updateStock('stockawalall', $data['iddenom'], $data['idtap'], $data['qty']);

    // Hapus data dari tabel keluarsf
    DB::table('keluarsf')->where('idkeluar', $idkeluar)->delete();

    return redirect('sf-keluar')->with('status', 'Data Berhasil Dihapus!');
}

private function updateStock($table, $iddenom, $id, $qty)
{
    $column = ($table === 'stockawalsf') ? 'idsf' : 'idtap';

    DB::table($table)
        ->where('iddenom', $iddenom)
        ->where($column, $id)
        ->update([
            'stock' => DB::raw("stock + $qty")
        ]);
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
