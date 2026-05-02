<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InjectExport;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\TapFilter;

class InjectController extends Controller
{
    /* =========================
       VIEW
    ========================= */
    public function index()
    {
        return view('injectvf');
    }

    /* =========================
       DATATABLE SERVER SIDE
    ========================= */
    public function data(Request $request)
{
    if ($request->filled('daterange')) {
        [$start, $end] = explode(' - ', $request->daterange);
    } else {
        $start = now()->startOfMonth()->toDateString();
        $end   = now()->endOfMonth()->toDateString();
    }

    $query = DB::table('injectvf as f')
        ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
        ->select(
            'f.idinject',
            'f.tgl',
            'd.denom',
            'f.qty',
            'f.idtap',
            'f.sn'
        )
        ->whereBetween('f.tgl', [
        $start,
        $end
    ]);

    TapFilter::apply($query, 'f.idtap');

    return DataTables::of($query)
        ->filterColumn('denom', function ($query, $keyword) {
            $query->where('d.denom', 'like', "%{$keyword}%");
        })
        ->filterColumn('tgl', function ($query, $keyword) {
            $query->whereDate('f.tgl', $keyword);
        })
        ->editColumn('tgl', fn ($r) => Carbon::parse($r->tgl)->format('d-m-Y'))
        ->editColumn('qty', fn ($r) => number_format($r->qty))
        ->addColumn('action', function ($row) {
            if (auth()->user()->username !== 'admin_cluster') {
                return '';
            }

            return '
                <form action="'.url('injectvf/'.$row->idinject).'" 
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

    /* =========================
       DELETE
    ========================= */
    public function delete($idinject)
{
    if (auth()->user()->username !== 'admin_cluster') {
        return redirect('injectvf')->with('error', 'Akses ditolak. Hanya Admin Cluster yang boleh menghapus data.');
    }

    try {
        DB::transaction(function () use ($idinject) {

            /* =========================
               LOCK DATA INJECT
            ========================= */
            $data = DB::table('injectvf')
                ->where('idinject', $idinject)
                ->lockForUpdate()
                ->first();

            if (!$data) {
                throw new \Exception('Data inject tidak ditemukan');
            }

            /*
             |--------------------------------------------------------------------------
             | 1️⃣ KURANGI STOK PAKET (ROLLBACK)
             |--------------------------------------------------------------------------
             | inject ➜ paket naik
             | delete ➜ paket turun
             */
            $stokPaketTap = DB::table('stockawaltap')
                ->where('idtap', $data->idtap)
                ->where('iddenom', $data->iddenom)
                ->lockForUpdate()
                ->value('stock');

            if ($stokPaketTap < $data->qty) {
                throw new \Exception('Stok paket tidak mencukupi untuk rollback');
            }

            DB::table('stockawaltap')
                ->where('idtap', $data->idtap)
                ->where('iddenom', $data->iddenom)
                ->decrement('stock', $data->qty);

            /*
             |--------------------------------------------------------------------------
             | 2️⃣ TAMBAH STOK SEGEL (ROLLBACK)
             |--------------------------------------------------------------------------
             | inject ➜ segel turun
             | delete ➜ segel naik
             */
            DB::table('stockawaltap')
                ->where('idtap', $data->idtap)
                ->where('iddenom', $data->kategori)
                ->lockForUpdate()
                ->increment('stock', $data->qty);

            /*
             |--------------------------------------------------------------------------
             | 3️⃣ HAPUS DATA INJECT
             |--------------------------------------------------------------------------
             */
            DB::table('injectvf')
                ->where('idinject', $idinject)
                ->delete();
        });

        return redirect('injectvf')
            ->with('success', 'Inject berhasil dihapus & stok dikembalikan');

    } catch (\Exception $e) {
        return redirect('injectvf')
            ->with('error', $e->getMessage());
    }
}

    /* =========================
       EXPORT
    ========================= */
    public function export(Request $request)
    {
        if ($request->filled('daterange')) {
            [$start, $end] = explode(' - ', $request->daterange);
        } else {
            $start = now()->startOfMonth()->format('Y-m-d');
            $end   = now()->endOfMonth()->format('Y-m-d');
        }

        $query = DB::table('injectvf as f')
            ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
            ->select(
                'f.tgl',
                'd.denom',
                'f.qty',
                'f.idtap',
                'f.sn'
            );

        TapFilter::apply($query, 'f.idtap');

        $data = $query->whereBetween('f.tgl', [
                $start.' 00:00:00',
                $end.' 23:59:59'
            ])
            ->get();

        return Excel::download(
            new InjectExport($data),
            "INJECT_VF_{$start}_sd_{$end}.xlsx"
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
                foreach ($ids as $idinject) {
                    $data = DB::table('injectvf as r')->join('denom as d', 'r.iddenom', '=', 'd.iddenom')->select('r.*', 'd.denom')
                        ->where('r.idinject', $idinject)
                        ->lockForUpdate()
                        ->first();

                    if (!$data) continue;

                    // 1. Rollback Paket di TAP
                    $stokPaketTap = DB::table('stockawaltap')
                        ->where('idtap', $data->idtap)
                        ->where('iddenom', $data->iddenom)
                        ->lockForUpdate()
                        ->value('stock');

                    if ($stokPaketTap < $data->qty) {
                        throw new \Exception('Stok paket untuk ' . $data->denom . ' tidak mencukupi untuk rollback');
                    }

                    DB::table('stockawaltap')
                        ->where('idtap', $data->idtap)
                        ->where('iddenom', $data->iddenom)
                        ->decrement('stock', $data->qty);

                    // 2. Balikkan ke Segel di TAP
                    DB::table('stockawaltap')
                        ->where('idtap', $data->idtap)
                        ->where('iddenom', $data->kategori)
                        ->increment('stock', $data->qty);

                    DB::table('injectvf')
                        ->where('idinject', $idinject)
                        ->delete();
                }
            });

            return response()->json(['success' => true, 'message' => count($ids) . ' data berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getAllStockTap(Request $request)
    {
        $request->validate([
            'idtap' => 'required',
        ]);

        $stocks = DB::table('stockawaltap')
            ->where('idtap', $request->idtap)
            ->pluck('stock', 'iddenom');

        return response()->json($stocks);
    }
}
