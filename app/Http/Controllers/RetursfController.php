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
        return datatables()->of([])->make(true);
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
        ->editColumn('qty', fn ($r) => number_format($r->qty))
        ->addColumn('action', function ($r) {
             if (session('idtap') !== 'SBP_DUMAI') {
                return '<button class="btn btn-danger btn-sm" disabled>Delete</button>';
            }
            return '
            <form action="'.url('retursf/'.$r->idretur).'" method="POST" class="form-delete d-inline">
                '.csrf_field().'
                <input type="hidden" name="idtap" value="'.$r->idtap.'">
                <input type="hidden" name="idsf" value="'.$r->idsf.'">
                <input type="hidden" name="iddenom" value="'.$r->iddenom.'">
                <input type="hidden" name="qty" value="'.$r->qty.'">
                <button class="btn btn-danger btn-sm">Delete</button>
            </form>';
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
        DB::transaction(function () use ($request, $idretur) {

            DB::table('retursf')
                ->where('idretur', $idretur)
                ->delete();

            // balikin stok
            DB::table('stockawalsf')
                ->where('idsf', $request->idsf)
                ->where('iddenom', $request->iddenom)
                ->increment('stock', $request->qty);

            DB::table('stockawaltap')
                ->where('idtap', $request->idtap)
                ->where('iddenom', $request->iddenom)
                ->decrement('stock', $request->qty);
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
}
