<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\KeluarSFExport;

class SfkeluarController extends Controller
{
    /* =========================
       INDEX
    ========================= */
    public function index(Request $request)
    {
        $idtap = session('idtap');
        $month = $request->input('bulan', date('m'));
        $year  = $request->input('tahun', date('Y'));

        $query = DB::table('keluarsf as f')
            ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
            ->join('idsf as i', 'i.idsf', '=', 'f.idsf')
            ->select('f.*', 'd.*', 'i.*')
            ->whereMonth('f.tgl', $month)
            ->whereYear('f.tgl', $year);

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('f.idtap', $idtap);
        }

        $data = $query->get()->map(function ($item) {
            $item->tgl = Carbon::parse($item->tgl)->format('d-m-Y');
            return $item;
        });

        $denomkeluar = DB::table('keluarsf as f')
            ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
            ->select('d.denom', DB::raw('SUM(f.qty) as qty'))
            ->whereMonth('f.tgl', $month)
            ->whereYear('f.tgl', $year);

        if ($idtap !== 'SBP_DUMAI') {
            $denomkeluar->where('f.idtap', $idtap);
        }

        $denomkeluar = $denomkeluar
            ->groupBy('d.denom')
            ->get();

        $grandTotal = $denomkeluar->sum('qty');

        return view('sf-keluar', compact(
            'idtap',
            'data',
            'month',
            'year',
            'denomkeluar',
            'grandTotal'
        ));
    }

    /* =========================
       FORM
    ========================= */
    public function formkeluarsf()
    {
        $idtap = session('idtap');

        $denom = DB::table('denom')->get();

        $data = DB::table('kodetap')
            ->when($idtap !== 'SBP_DUMAI', function ($q) use ($idtap) {
                $q->where('idtap', $idtap);
            })
            ->get();

        return view('form/form-sfkeluar', compact('data', 'idtap', 'denom'));
    }

    /* =========================
       AJAX GET SF
    ========================= */
    public function getSf(Request $request)
    {
        $idtapsession = session('idtap');
        $idtaprequest = $request->idtap;

        $tapnya = DB::table('idsf')
            ->where('idtap', $idtapsession === 'SBP_DUMAI' ? $idtaprequest : $idtapsession)
            ->get();

        echo "<option value=''>-- Pilih SF --</option>";

        foreach ($tapnya as $tap) {
            echo "<option value='{$tap->idsf}'>{$tap->namasf}</option>";
        }
    }

    /* =========================
       PROSES KELUAR SF
    ========================= */
    public function keluarsfproses(Request $request)
    {
        $data = $request->only([
            'iddenom',
            'idsf',
            'qty',
            'tgl',
            'idtap',
            'tambahanket'
        ]);

        try {
            DB::beginTransaction();
            $t0 = microtime(true);

            Log::info('keluarsfproses:start', $data);

            $ssf = DB::table('stockawalsf')
                ->where('iddenom', $data['iddenom'])
                ->where('idsf', $data['idsf'])
                ->lockForUpdate()
                ->first();

            if (!$ssf || $ssf->stock < $data['qty']) {
                DB::rollBack();
                return redirect('form/form-sfkeluar')
                    ->withErrors(['error' => 'Stock SF Tidak Mencukupi']);
            }

            $t1 = microtime(true);
            $this->updateStock('stockawalsf', $data['iddenom'], $data['idsf'], -$data['qty']);
            $t2 = microtime(true);
            $this->updateStock('stockawalall', $data['iddenom'], $data['idtap'], -$data['qty']);
            $t3 = microtime(true);

            DB::table('keluarsf')->insert($data);
            $t4 = microtime(true);

            DB::commit();

            if (config('app.debug')) {
                Log::info('keluarsfproses:timings', [
                    'total_ms'          => round(($t4 - $t0) * 1000, 2),
                    'updateStockSF_ms'  => round(($t2 - $t1) * 1000, 2),
                    'updateStockAll_ms' => round(($t3 - $t2) * 1000, 2),
                    'insert_ms'         => round(($t4 - $t3) * 1000, 2),
                ]);
            }

            return redirect('sf-keluar')
                ->with('status', 'Data Berhasil Ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('keluarsfproses:error', [
                'message' => $e->getMessage()
            ]);

            return redirect('form/form-sfkeluar')
                ->withErrors(['error' => 'Terjadi kesalahan, silakan coba lagi']);
        }
    }

    /* =========================
       DELETE
    ========================= */
    public function delete($idkeluar)
    {
        $data = DB::table('keluarsf')->where('idkeluar', $idkeluar)->first();

        if (!$data) {
            return redirect('sf-keluar')
                ->withErrors(['error' => 'Data tidak ditemukan']);
        }

        $this->updateStock('stockawalsf', $data->iddenom, $data->idsf, $data->qty);
        $this->updateStock('stockawalall', $data->iddenom, $data->idtap, $data->qty);

        DB::table('keluarsf')->where('idkeluar', $idkeluar)->delete();

        return redirect('sf-keluar')
            ->with('status', 'Data Berhasil Dihapus!');
    }

    /* =========================
       UPDATE STOCK
    ========================= */
    private function updateStock($table, $iddenom, $id, $qty)
    {
        $column = $table === 'stockawalsf' ? 'idsf' : 'idtap';

        DB::table($table)
            ->where('iddenom', $iddenom)
            ->where($column, $id)
            ->increment('stock', $qty);
    }

    /* =========================
       EXPORT EXCEL
    ========================= */
    public function exportexcel(Request $request)
    {
        $idtap = session('idtap');
        $month = $request->input('bulan', date('m'));
        $year  = $request->input('tahun', date('Y'));

        $query = DB::table('keluarsf as f')
            ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
            ->join('idsf as i', 'f.idsf', '=', 'i.idsf')
            ->select(
                'f.tgl',
                'd.denom',
                DB::raw('SUM(f.qty) as qty'),
                'f.idtap',
                'i.namasf',
                'f.tambahanket'
            )
            ->whereMonth('f.tgl', $month)
            ->whereYear('f.tgl', $year)
            ->groupBy('f.tgl', 'd.denom', 'f.idtap', 'i.namasf', 'f.tambahanket');

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('f.idtap', $idtap);
        }

        $penjualanData = $query->get();

        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $fileName  = "PENJUALAN_SF_TAP_{$idtap}_{$year}_{$monthName}.xlsx";

        return Excel::download(
            new KeluarSFExport($penjualanData),
            $fileName
        );
    }
}
