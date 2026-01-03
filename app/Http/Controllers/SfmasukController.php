<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasukSFExport;

class SfmasukController extends Controller
{
    /* =========================
       VIEW
    ========================= */
    public function index()
    {
        return view('sf-masuk');
    }

    /* =========================
       DATATABLE SERVER SIDE
    ========================= */
    public function data(Request $request)
{
    $idtap = session('idtap');

    // ===== DATE RANGE (AMAN) =====
    if ($request->filled('daterange')) {
        [$start, $end] = explode(' - ', $request->daterange);
    } else {
        $start = now()->startOfMonth()->format('Y-m-d');
        $end   = now()->endOfMonth()->format('Y-m-d');
    }

    $query = DB::table('masuksf as f')
        ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
        ->join('idsf as i', 'i.idsf', '=', 'f.idsf')
        ->select(
            'f.idmasuk',
            'f.tgl',
            'd.denom',
            'f.qty',
            'f.idtap',
            'i.namasf',
            'f.sn'
        )
        ->whereBetween('f.tgl', [
            $start . ' 00:00:00',
            $end   . ' 23:59:59'
        ]);

    // ===== FILTER TAP (ANTI NULL) =====
    if (!empty($idtap) && $idtap !== 'SBP_DUMAI') {
        $query->where('f.idtap', $idtap);
    }

    return DataTables::of($query)
        ->editColumn('tgl', fn ($r) =>
            Carbon::parse($r->tgl)->format('d-m-Y')
        )
        ->editColumn('qty', fn ($r) =>
            number_format($r->qty)
        )
        ->addColumn('action', function ($row) {
            if (session('idtap') !== 'SBP_DUMAI') {
                return '<button class="btn btn-danger btn-sm" disabled>Delete</button>';
            }

            return '
                <form action="'.url('sf-masuk/'.$row->idmasuk).'" 
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
        ->orderColumn('tgl', 'f.tgl $1')
        ->make(true);
}


    
    public function formmasuksf(){

        $idtap = session('idtap');

        $denom = DB::table('denom')
                ->select('*')
                ->get();
        

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
       
        return view('form/form-sfmasuk',compact('data','idtap','denom'));

    }

     /* =========================
       AJAX GET SF
    ========================= */
    public function getSf(Request $request)
{
    $request->validate([
        'idtap' => 'required'
    ]);

    $sf = DB::table('idsf')
        ->where('idtap', $request->idtap)
        ->orderBy('namasf')
        ->get();

    if ($sf->isEmpty()) {
        return response('<option value="">SF tidak ditemukan</option>');
    }

    $options = '<option value="">-- Pilih SF --</option>';

    foreach ($sf as $row) {
        $options .= '<option value="'.$row->idsf.'">'
                  . e($row->namasf) .
                  '</option>';
    }

    return response($options);
}


    /* =========================
       AJAX GET STOCK
    ========================= */
    public function getStockTap(Request $request)
    {
        $stock = DB::table('stockawaltap')
            ->where('idtap', $request->idtap)
            ->where('iddenom', $request->iddenom)
            ->value('stock') ?? 0;

        return response()->json([
            'stock' => $stock
        ]);
    }

public function masuksfproses(Request $request)
{
    DB::transaction(function () use ($request) {

        $idtap    = $request->idtap;
        $idsf     = $request->idsf;
        $iddenom  = $request->iddenom;
        $qty      = $request->qty;
        $sn       = $request->sn;
        $tgl      = $request->tgl;

        // LOCK stok TAP (ANTI RACE)
        $stockTap = DB::table('stockawaltap')
            ->where('idtap', $idtap)
            ->where('iddenom', $iddenom)
            ->lockForUpdate()
            ->value('stock');

        if ($stockTap < $qty) {
            throw new \Exception('Stok TAP tidak mencukupi');
        }

        // Update stok
        DB::table('stockawaltap')
            ->where('idtap', $idtap)
            ->where('iddenom', $iddenom)
            ->decrement('stock', $qty);

        DB::table('stockawalsf')
            ->where('idsf', $idsf)
            ->where('iddenom', $iddenom)
            ->increment('stock', $qty);

        // Insert data
        DB::table('masuksf')->insert([
            'idtap'   => $idtap,
            'idsf'    => $idsf,
            'iddenom' => $iddenom,
            'qty'     => $qty,
            'sn'      => $sn,
            'tgl'     => $tgl
        ]);
    });

    return redirect('sf-masuk')->with('status', 'Data Berhasil Ditambahkan');
}


// =========================
// DELETE
        

public function delete(Request $request, $idmasuk)
{
    try {
        DB::transaction(function () use ($idmasuk) {

            $data = DB::table('masuksf')
                ->where('idmasuk', $idmasuk)
                ->lockForUpdate()
                ->first();

            if (!$data) {
                throw new \Exception('Data tidak ditemukan');
            }

            $stockSf = DB::table('stockawalsf')
                ->where('idsf', $data->idsf)
                ->where('iddenom', $data->iddenom)
                ->lockForUpdate()
                ->value('stock');

            if ($stockSf < $data->qty) {
                throw new \Exception('Stok SF tidak mencukupi');
            }

            DB::table('stockawaltap')
                ->where('idtap', $data->idtap)
                ->where('iddenom', $data->iddenom)
                ->increment('stock', $data->qty);

            DB::table('stockawalsf')
                ->where('idsf', $data->idsf)
                ->where('iddenom', $data->iddenom)
                ->decrement('stock', $data->qty);

            DB::table('masuksf')->where('idmasuk', $idmasuk)->delete();
        });

        return redirect('sf-masuk')
            ->with('success', 'Data berhasil dihapus');

    } catch (\Exception $e) {

        return redirect('sf-masuk')
            ->with('error', $e->getMessage());
    }
}

    /* =========================
       EXPORT EXCEL (IKUT RANGE)
    ========================= */
    public function exportexcel(Request $request)
    {
        $idtap     = session('idtap');
        $daterange = $request->daterange;

        $query = DB::table('masuksf as f')
            ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
            ->join('idsf as i', 'f.idsf', '=', 'i.idsf')
            ->select(
                'f.tgl',
                'd.denom',
                'f.qty',
                'f.idtap',
                'i.namasf',
                'f.sn'
            );

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('f.idtap', $idtap);
        }

        if ($daterange) {
            [$start, $end] = explode(' - ', $daterange);
            $query->whereBetween('f.tgl', [
                $start.' 00:00:00',
                $end.' 23:59:59'
            ]);

            $filename = "MASUK_SF_{$start}_sd_{$end}.xlsx";
        } else {
            $filename = "MASUK_SF_".now()->format('Ym').".xlsx";
        }

        return Excel::download(
            new MasukSFExport($query->get()),
            $filename
        );
    }
}
