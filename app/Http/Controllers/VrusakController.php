<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RusakExport;
use Carbon\Carbon;

class VrusakController extends Controller
{
    /* ===============================
       VIEW
    =============================== */
    public function index()
    {
        return view('vrusak');
    }

    /* ===============================
       DATATABLE SERVER SIDE
    =============================== */
    public function data(Request $request)
    {
        $idtap = session('idtap');

        if (!$request->daterange) {
            return datatables()->of([])->make(true);
        }

        [$start, $end] = explode(' - ', $request->daterange);

        $query = DB::table('returvfrusak as r')
            ->join('denom as d', 'r.iddenom', '=', 'd.iddenom')
            ->select(
                'r.idrusak',
                'r.tgl',
                'd.denom',
                'r.qty',
                'r.idtap',
                'r.sn',
                'r.ketvf',
                'r.ketlain',
                'r.iddenom'
            )
            ->whereBetween('r.tgl', [$start, $end]);

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('r.idtap', $idtap);
        }

        return datatables()
            ->of($query)
            // ->editColumn('tgl', fn ($r) => Carbon::parse($r->tgl)->format('DD-MM-YYYY'))
            ->editColumn('qty', fn ($r) => number_format($r->qty))
            ->addColumn('action', function ($r) {
                   if (session('idtap') !== 'SBP_DUMAI') {
                return '<button class="btn btn-danger btn-sm" disabled>Delete</button>';
            }

                return '
                <form action="'.url('vrusak/'.$r->idrusak).'" 
                      method="POST" 
                      class="form-delete d-inline">
                    '.csrf_field().'
                    <input type="hidden" name="idtap" value="'.$r->idtap.'">
                    <input type="hidden" name="iddenom" value="'.$r->iddenom.'">
                    <input type="hidden" name="qty" value="'.$r->qty.'">
                    <button class="btn btn-danger btn-sm">
                        Delete
                    </button>
                </form>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /* ===============================
       FORM INPUT
    =============================== */
    public function vrusak()
    {
        $idtap = session('idtap');

        $tap = DB::table('kodetap')
            ->when($idtap !== 'SBP_DUMAI', fn ($q) => $q->where('idtap', $idtap))
            ->get();

        $denom = DB::table('denom')->get();

        return view('form.form-vrusak', compact('tap', 'denom', 'idtap'));
    }

    /* ===============================
       SIMPAN
    =============================== */
    public function vrusakproses(Request $request)
{
    DB::transaction(function () use ($request) {

        /* ===============================
           VALIDASI STOK TAP
        =============================== */
        $stok = DB::table('stockawaltap')
            ->where('idtap', $request->pengirim)
            ->where('iddenom', $request->iddenom)
            ->lockForUpdate()
            ->value('stock');

        if ($stok < $request->qty) {
            abort(400, 'Stok TAP tidak mencukupi');
        }

        /* ===============================
           INSERT DATA RUSAK
        =============================== */
        DB::table('returvfrusak')->insert([
            'idtap'   => $request->pengirim,
            'tgl'     => $request->tgl,
            'iddenom' => $request->iddenom,
            'qty'     => $request->qty,
            'sn'      => $request->sn,
            'ketvf'   => $request->ketvf,
            'ketlain' => $request->tambahanket,
        ]);

        /* ===============================
           KURANGI STOK TAP
        =============================== */
        DB::table('stockawaltap')
            ->where('idtap', $request->pengirim)
            ->where('iddenom', $request->iddenom)
            ->decrement('stock', $request->qty);
    });

    return redirect('vrusak')
        ->with('success', 'Voucher rusak berhasil disimpan & stok terupdate');
}

    /* ===============================
       DELETE
    =============================== */
    public function delete(Request $request, $idrusak)
    {
        DB::transaction(function () use ($request, $idrusak) {

            DB::table('returvfrusak')
                ->where('idrusak', $idrusak)
                ->delete();

            DB::table('stockawaltap')
                ->where('idtap', $request->idtap)
                ->where('iddenom', $request->iddenom)
                ->increment('stock', $request->qty);
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

        $query = DB::table('returvfrusak as r')
            ->join('denom as d', 'r.iddenom', '=', 'd.iddenom')
            ->select(
                'r.tgl',
                'd.denom',
                'r.qty',
                'r.idtap',
                'r.sn',
                'r.ketvf',
                'r.ketlain'
            )
            ->whereBetween('r.tgl', [$start, $end]);

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('r.idtap', $idtap);
        }

        return Excel::download(
            new RusakExport($query->get()),
            'VOUCHER_RUSAK_'.now()->format('Ymd_His').'.xlsx'
        );
    }
}
