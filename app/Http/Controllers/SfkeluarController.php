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


        //validasi qty mencukupi

        $ssf = DB::table('stockawalsf')
            ->select('stock')
            ->where('iddenom', $iddenom)
            ->where('idsf', $idsf)
            ->first();

        if ($ssf->stock < $qty) {

            return redirect('form/form-sfkeluar')->withErrors(['error' => 'Stock SF Tidak Mencukupi']);
        } else {
            //cek stok sf

            $ssf = DB::table('stockawalsf')
                ->select('stock')
                ->where('iddenom', $iddenom)
                ->where('idsf', $idsf)
                ->first();

            $sall = DB::table('stockawalall')
                ->select('stock')
                ->where('idtap', $idtap)
                ->where('iddenom', $iddenom)
                ->first();

            $nsf = $ssf->stock - $qty;
            $nall = $sall->stock - $qty;

            //update

            DB::table('stockawalsf')
                ->where('iddenom', $iddenom)
                ->where('idsf', $idsf)
                ->update([
                    'stock' => $nsf
                ]);

            DB::table('stockawalall')
                ->where('iddenom', $iddenom)
                ->where('idtap', $idtap)
                ->update([
                    'stock' => $nall
                ]);

            DB::table('keluarsf')
                ->insert([
                    'iddenom' => $iddenom,
                    'idsf' => $idsf,
                    'qty' => $qty,
                    'tgl' => $tgl,
                    'idtap' => $idtap,
                    'tambahanket' => $tambahanket
                ]);

            return redirect('sf-keluar')->with('status', 'Data Berhasil Ditambahkan!');
        }
    }

    public function delete(Request $request, $idkeluar)
    {

        $iddenom = $request->input('iddenom');
        $idsf = $request->input('idsf');
        $idtap = $request->input('idtap');
        $qty = $request->input('qty');

        // cek stok awal sf dan all

        $ssf = DB::table('stockawalsf')
            ->select('stock')
            ->where('idsf', $idsf)
            ->where('iddenom', $iddenom)
            ->first();

        $sall = DB::table('stockawalall')
            ->select('stock')
            ->where('iddenom', $iddenom)
            ->where('idtap', $idtap)
            ->first();

        $nsf = $ssf->stock + $qty;
        $nall = $sall->stock + $qty;

        DB::table('stockawalsf')
            ->where('iddenom', $iddenom)
            ->where('idsf', $idsf)
            ->update([
                'stock' => $nsf
            ]);

        DB::table('stockawalall')
            ->where('iddenom', $iddenom)
            ->where('idtap', $idtap)
            ->update([
                'stock' => $nall
            ]);

        DB::table('keluarsf')
            ->where('idkeluar', $idkeluar)
            ->delete();

        return redirect('sf-keluar')->with('status', 'Data Berhasil DIhapus!');
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
