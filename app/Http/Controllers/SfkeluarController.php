<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\KeluarSFExport;
use Yajra\DataTables\Facades\DataTables;

    class SfkeluarController extends Controller
{
    /* =========================
       VIEW
    ========================= */
    public function index()
    {
        return view('sf-keluar');
    }

    /* =========================
       SERVER SIDE DATATABLE
    ========================= */
    public function data(Request $request)
{
    $idtap     = session('idtap');
    $daterange = $request->daterange;

    $query = DB::table('keluarsf as f')
        ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
        ->join('idsf as i', 'i.idsf', '=', 'f.idsf')
        ->select(
            'f.idkeluar',
            'f.tgl',
            'd.denom',
            'f.qty',
            'f.idtap',
            'i.namasf',
            'f.tambahanket'
        );

    if ($idtap !== 'SBP_DUMAI') {
        $query->where('f.idtap', $idtap);
    }

    // 🔥 DEFAULT: BULAN BERJALAN
    if ($daterange) {
        [$start, $end] = explode(' - ', $daterange);
    } else {
        $start = now()->startOfMonth()->toDateString();
        $end   = now()->endOfMonth()->toDateString();
    }

    $query->whereBetween('f.tgl', [
        $start . ' 00:00:00',
        $end   . ' 23:59:59'
    ]);

    return DataTables::of($query)
        // ->editColumn('tgl', fn ($r) => Carbon::parse($r->tgl)->format('d-m-Y'))
        ->editColumn('qty', fn ($r) => number_format($r->qty))
        ->addColumn('action', function ($row) {
            // selain admin → tombol mati
            if (session('idtap') !== 'SBP_DUMAI') {
                return '<button class="btn btn-danger btn-sm" disabled>Delete</button>';
            }

            // admin → boleh delete (pakai confirm JS)
            return '
                <form action="'.url('sf-keluar/'.$row->idkeluar).'"
                    method="POST"
                    class="form-delete d-inline">
                    '.csrf_field().'
                    <button type="submit" class="btn btn-danger btn-sm">
                        Delete
                    </button>
                </form>
            ';
        })
        ->rawColumns(['action'])
        ->make(true);
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
    DB::transaction(function () use ($request) {

        $idtap    = $request->idtap;
        $idsf     = $request->idsf;
        $iddenom  = $request->iddenom;
        $qty      = $request->qty;
        $tgl      = $request->tgl;
        $ket      = $request->tambahanket;

        // 🔒 LOCK stok SF (ANTI RACE)
        $stockSf = DB::table('stockawalsf')
            ->where('idsf', $idsf)
            ->where('iddenom', $iddenom)
            ->lockForUpdate()
            ->value('stock');

        if ($stockSf < $qty) {
            throw new \Exception('Stok SF tidak mencukupi');
        }

        // =====================
        // UPDATE STOK
        // =====================
        DB::table('stockawalsf')
            ->where('idsf', $idsf)
            ->where('iddenom', $iddenom)
            ->decrement('stock', $qty);

        // =====================
        // INSERT DATA
        // =====================
        DB::table('keluarsf')->insert([
            'idtap'       => $idtap,
            'idsf'        => $idsf,
            'iddenom'     => $iddenom,
            'qty'         => $qty,
            'tgl'         => $tgl,
            'tambahanket' => $ket
        ]);
    });

    return redirect('sf-keluar')->with('status', 'Data Berhasil Ditambahkan!');
}


    /* =========================
   AJAX GET STOCK
========================= */
public function getStock(Request $request)
{
    $request->validate([
        'iddenom' => 'required',
        'idsf'    => 'required',
    ]);

    $stock = DB::table('stockawalsf')
        ->where('iddenom', $request->iddenom)
        ->where('idsf', $request->idsf)
        ->value('stock');

    return response()->json([
        'stock' => (int) ($stock ?? 0)
    ]);
}


   public function delete($idkeluar)
{
    DB::transaction(function () use ($idkeluar) {

        // 🔒 LOCK DATA KELUAR
        $data = DB::table('keluarsf')
            ->where('idkeluar', $idkeluar)
            ->lockForUpdate()
            ->first();

        if (!$data) {
            throw new \Exception('Data tidak ditemukan');
        }

        // 🔒 LOCK STOK SF
        $stockSf = DB::table('stockawalsf')
            ->where('idsf', $data->idsf)
            ->where('iddenom', $data->iddenom)
            ->lockForUpdate()
            ->value('stock');

        // =====================
        // BALIKKAN STOK
        // =====================
        DB::table('stockawalsf')
            ->where('idsf', $data->idsf)
            ->where('iddenom', $data->iddenom)
            ->increment('stock', $data->qty);

        DB::table('keluarsf')
            ->where('idkeluar', $idkeluar)
            ->delete();
    });

    return redirect('sf-keluar')->with('status', 'Data Berhasil Dihapus!');
}


    /* =========================
       EXPORT EXCEL
    ========================= */
    public function exportexcel(Request $request)
{
    $idtap     = session('idtap');
    $daterange = $request->daterange;

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
        ->groupBy(
            'f.tgl',
            'd.denom',
            'f.idtap',
            'i.namasf',
            'f.tambahanket'
        );

    /* ===============================
       FILTER TAP
    =============================== */
    if ($idtap !== 'SBP_DUMAI') {
        $query->where('f.idtap', $idtap);
    }

    /* ===============================
       FILTER DATE RANGE (PRIORITY)
    =============================== */
    if ($daterange) {
        [$start, $end] = explode(' - ', $daterange);

        $query->whereBetween('f.tgl', [
            $start . ' 00:00:00',
            $end   . ' 23:59:59'
        ]);

        $filename = 'PENJUALAN_SF_' . $start . '_sd_' . $end . '.xlsx';

    } else {
        /* fallback lama (bulan berjalan) */
        $month = date('m');
        $year  = date('Y');

        $query->whereMonth('f.tgl', $month)
              ->whereYear('f.tgl', $year);

        $filename = "PENJUALAN_SF_{$year}_{$month}.xlsx";
    }

    $data = $query->get();

    return Excel::download(
        new KeluarSFExport($data),
        $filename
    );
}
}