<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReturSFExport;
use Carbon\Carbon;

class ReturSfController extends Controller
{
    /* ===============================
       VIEW
    =============================== */
    public function index()
    {
        return view('retursf');
    }

    /* ===============================
       DATATABLE
    =============================== */
    public function data(Request $request)
{
    $idtap = session('idtap');

    if (!$request->daterange) {
        return datatables()->of(collect([]))->make(true);
    }

    [$start, $end] = explode(' - ', $request->daterange);

    $query = DB::table('retursf as r')
        ->join('denom as d', 'r.iddenom', '=', 'd.iddenom')
        ->join('idsf as s', 'r.idsf', '=', 's.idsf')
        ->select(
            'r.idretur',
            'r.tgl',
            'd.denom',
            'r.qty',
            'r.idtap',
            's.namasf',
            'r.sn',
            'r.ketvf',
            'r.tambahket',
            'r.iddenom',
            'r.idsf'
        )
        ->whereBetween('r.tgl', [$start, $end]);

    if ($idtap !== 'SBP_DUMAI') {
        $query->where('r.idtap', $idtap);
    }

     return datatables()
    ->of($query)
    ->editColumn('tgl', fn($r) => Carbon::parse($r->tgl)->format('Y-m-d'))

    // 🔎 search denom (join table)
    ->filterColumn('denom', function ($q, $keyword) {
        $q->whereRaw("LOWER(d.denom) LIKE ?", ["%".strtolower($keyword)."%"]);
    })

    // 🔎 search nama SF (join table)
    ->filterColumn('namasf', function ($q, $keyword) {
        $q->whereRaw("LOWER(s.namasf) LIKE ?", ["%".strtolower($keyword)."%"]);
    })

    ->addColumn('action', function ($row) {
        if (auth()->user()->username !== 'admin_cluster') {
            return '';
        }

        return '
            <form action="'.url('retursf/'.$row->idretur).'" 
                method="POST" 
                class="form-delete d-inline">
                '.csrf_field().'
                <button type="submit" class="btn btn-link text-danger p-0" title="Delete">
                    <i class="fas fa-trash-alt fa-lg"></i>
                </button>
            </form>
        ';
    })
    ->rawColumns(['action'])
    ->make(true);

}


    /* ===============================
       FORM
    =============================== */
    public function form()
    {
        $idtap = session('idtap');

        $tap = DB::table('kodetap')
            ->when($idtap !== 'SBP_DUMAI', fn ($q) => $q->where('idtap', $idtap))
            ->get();

        $denom = DB::table('denom')->get();

        return view('form.form-retursf', compact('tap','denom','idtap'));
    }

    /* ===============================
       SIMPAN
    =============================== */
    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {

            // LOCK STOK SF
            $stockSf = DB::table('stockawalsf')
                ->where('idsf', $request->idsf)
                ->where('iddenom', $request->iddenom)
                ->lockForUpdate()
                ->value('stock');

            if ($stockSf < $request->qty) {
                throw new \Exception('Stok SF tidak mencukupi');
            }

            DB::table('retursf')->insert([
                'tgl'        => $request->tgl,
                'idtap'      => $request->idtap,
                'idsf'       => $request->idsf,
                'iddenom'    => $request->iddenom,
                'qty'        => $request->qty,
                'sn'         => $request->sn,
                'ketvf'      => $request->ketvf,
                'tambahket'  => $request->tambahket,
            ]);

            // stok SF berkurang
            DB::table('stockawalsf')
                ->where('idsf', $request->idsf)
                ->where('iddenom', $request->iddenom)
                ->decrement('stock', $request->qty);

            // stok TAP bertambah
            DB::table('stockawaltap')
                ->where('idtap', $request->idtap)
                ->where('iddenom', $request->iddenom)
                ->increment('stock', $request->qty);
        });

        return redirect('retursf')->with('success', 'Retur SF berhasil disimpan');
    }

    /* ===============================
       DELETE
    =============================== */
    public function delete(Request $request, $idretur)
    {
        if (auth()->user()->username !== 'admin_cluster') {
            return back()->with('error', 'Akses ditolak. Hanya Admin Cluster yang boleh menghapus data.');
        }

        DB::transaction(function () use ($idretur) {

            $data = DB::table('retursf')
                ->where('idretur', $idretur)
                ->lockForUpdate()
                ->first();

            if (!$data) {
                throw new \Exception('Data tidak ditemukan');
            }

            // LOCK STOK TAP
            $stockTap = DB::table('stockawaltap')
                ->where('idtap', $data->idtap)
                ->where('iddenom', $data->iddenom)
                ->lockForUpdate()
                ->value('stock');

            if ($stockTap < $data->qty) {
                throw new \Exception('Stok TAP tidak mencukupi untuk membatalkan retur');
            }

            // balikin stok SF
            DB::table('stockawalsf')
                ->where('idsf', $data->idsf)
                ->where('iddenom', $data->iddenom)
                ->increment('stock', $data->qty);

            // kurangi stok TAP
            DB::table('stockawaltap')
                ->where('idtap', $data->idtap)
                ->where('iddenom', $data->iddenom)
                ->decrement('stock', $data->qty);

            DB::table('retursf')
                ->where('idretur', $idretur)
                ->delete();
        });

        return back()->with('success', 'Data berhasil dihapus');
    }

    /* ===============================
       EXPORT
    =============================== */
    public function exportexcel(Request $request)
    {
        [$start, $end] = explode(' - ', $request->daterange);
        $idtap = session('idtap');

        $query = DB::table('retursf as r')
            ->join('denom as d', 'r.iddenom', '=', 'd.iddenom')
            ->join('idsf as s', 'r.idsf', '=', 's.idsf')
            ->select(
                'r.tgl',
                'd.denom',
                'r.qty',
                'r.idtap',
                's.namasf',
                'r.sn',
                'r.ketvf',
                'r.tambahket'
            )
            ->whereBetween('r.tgl', [$start, $end]);

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('r.idtap', $idtap);
        }

        return Excel::download(
            new ReturSFExport($query->get()),
            'RETUR_SF_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function bulkDelete(Request $request)
    {
        if (auth()->user()->username !== 'admin_cluster') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak. Hanya Admin Cluster yang boleh menghapus data.']);
        }

        $ids = $request->ids;
        if (!$ids || !is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada data terpilih']);
        }

        try {
            DB::transaction(function () use ($ids) {
                foreach ($ids as $idretur) {
                    $data = DB::table('retursf as r')
                        ->join('denom as d', 'r.iddenom', '=', 'd.iddenom')
                        ->select('r.*', 'd.denom')
                        ->where('idretur', $idretur)
                        ->lockForUpdate()
                        ->first();

                    if (!$data) continue;

                    // 🔒 LOCK STOK TAP
                    $stockTap = DB::table('stockawaltap')
                        ->where('idtap', $data->idtap)
                        ->where('iddenom', $data->iddenom)
                        ->lockForUpdate()
                        ->value('stock');

                    if ($stockTap < $data->qty) {
                        throw new \Exception('Stok TAP untuk ' . $data->denom . ' tidak mencukupi untuk pembatalan retur ini');
                    }

                    // balikin stok SF
                    DB::table('stockawalsf')
                        ->where('idsf', $data->idsf)
                        ->where('iddenom', $data->iddenom)
                        ->increment('stock', $data->qty);

                    // kurangi stok TAP
                    DB::table('stockawaltap')
                        ->where('idtap', $data->idtap)
                        ->where('iddenom', $data->iddenom)
                        ->decrement('stock', $data->qty);

                    DB::table('retursf')
                        ->where('idretur', $idretur)
                        ->delete();
                }
            });

            return response()->json(['success' => true, 'message' => count($ids) . ' data berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getAllStockSf(Request $request)
    {
        $request->validate([
            'idsf' => 'required',
        ]);

        $stocks = DB::table('stockawalsf')
            ->where('idsf', $request->idsf)
            ->pluck('stock', 'iddenom');

        return response()->json($stocks);
    }
}
