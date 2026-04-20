<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\KeluarExport;

class KeluarController extends Controller
{
    /* ===============================
       VIEW
    =============================== */
    public function index()
    {
        return view('keluar');
    }

    /* ===============================
       DATATABLE
    =============================== */
    public function data(Request $request)
    {
        $idtap = session('idtap');
        $kategoribo = ['BO DUMAI','BO BENGKALIS','BO BAGAN BATU','BO BAGAN SIAPI-API','BO DURI'];

        if (!$request->daterange) {
            return datatables()->of(collect([]))->make(true);
        }

        [$start, $end] = explode(' - ', $request->daterange);

        $query = DB::table('keluar as k')
            ->join('denom as d','k.iddenom','=','d.iddenom')
            ->select(
                'k.idkeluar',
                'k.tgl',
                'd.denom',
                'k.qty',
                'k.pengirim',
                'k.penerima',
                'k.sn',
                'k.tambahanket',
                'k.status'
            )
            ->whereBetween('k.tgl', [$start, $end])
            ->whereNotIn('k.pengirim', $kategoribo);

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('k.pengirim', $idtap);
        }

        return datatables()
            ->of($query)
            ->editColumn('qty', fn($r) => number_format($r->qty))
            ->addColumn('status_label', function ($r) {
                return $r->status == 0
                    ? '<span class="badge badge-success">Approved</span>'
                    : '<span class="badge badge-warning">Wait for Approval</span>';
            })
            ->rawColumns(['status_label'])
            ->make(true);

    }

    /* ===============================
       EXPORT
    =============================== */
    public function exportexcel(Request $request)
    {
        [$start, $end] = explode(' - ', $request->daterange);
        $idtap = session('idtap');

        $query = DB::table('keluar as k')
            ->join('denom as d','k.iddenom','=','d.iddenom')
            ->select(
                'k.tgl',
                'k.sn',
                'k.pengirim',
                'k.penerima',
                'd.denom',
                DB::raw('SUM(k.qty) as qty')
            )
            ->whereBetween('k.tgl', [$start, $end])
            ->groupBy('k.tgl','k.sn','k.pengirim','k.penerima','d.denom');

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('k.pengirim', $idtap);
        }

        return Excel::download(
            new KeluarExport($query->get()),
            'STOK_KELUAR_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
