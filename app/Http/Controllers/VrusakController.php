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
                $btnEdit = '
                    <a href="'.url('vrusak/edit/'.$r->idrusak).'" class="btn btn-link text-primary p-0 mr-2" title="Edit">
                        <i class="fas fa-edit fa-lg"></i>
                    </a>
                ';

                if (auth()->user()->username !== 'admin_cluster') {
                    return '<div class="d-flex align-items-center justify-content-center">' . $btnEdit . '</div>';
                }

                $btnDelete = '
                <form action="'.url('vrusak/'.$r->idrusak).'" 
                      method="POST" 
                      class="form-delete d-inline">
                    '.csrf_field().'
                    <input type="hidden" name="idtap" value="'.$r->idtap.'">
                    <input type="hidden" name="iddenom" value="'.$r->iddenom.'">
                    <input type="hidden" name="qty" value="'.$r->qty.'">
                    <button class="btn btn-link text-danger p-0" title="Delete">
                        <i class="fas fa-trash-alt fa-lg"></i>
                    </button>
                </form>';

                return '<div class="d-flex align-items-center justify-content-center">' . $btnEdit . $btnDelete . '</div>';
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
       EDIT
    =============================== */
    public function edit($id)
    {
        $idtap_session = session('idtap');
        $data = DB::table('returvfrusak')->where('idrusak', $id)->first();
        
        if (!$data) return redirect('vrusak')->with('error', 'Data tidak ditemukan');

        $tap = DB::table('kodetap')
            ->when($idtap_session !== 'SBP_DUMAI', fn ($q) => $q->where('idtap', $idtap_session))
            ->get();

        $denom = DB::table('denom')->get();

        return view('form.form-edit-vrusak', compact('data', 'tap', 'denom', 'idtap_session'));
    }

    public function update(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $oldData = DB::table('returvfrusak')->where('idrusak', $id)->lockForUpdate()->first();
            
            // 1. Kembalikan stok lama ke TAP
            DB::table('stockawaltap')
                ->where('idtap', $oldData->idtap)
                ->where('iddenom', $oldData->iddenom)
                ->increment('stock', $oldData->qty);

            // 2. Cek stok TAP untuk data baru
            $currentStock = DB::table('stockawaltap')
                ->where('idtap', $request->idtap)
                ->where('iddenom', $request->iddenom)
                ->lockForUpdate()
                ->value('stock');

            if ($currentStock < $request->qty) {
                throw new \Exception('Stok TAP tidak mencukupi untuk update ini');
            }

            // 3. Kurangi stok TAP dengan qty baru
            DB::table('stockawaltap')
                ->where('idtap', $request->idtap)
                ->where('iddenom', $request->iddenom)
                ->decrement('stock', $request->qty);

            // 4. Update record
            DB::table('returvfrusak')->where('idrusak', $id)->update([
                'idtap'   => $request->idtap,
                'tgl'     => $request->tgl,
                'iddenom' => $request->iddenom,
                'qty'     => $request->qty,
                'sn'      => $request->sn,
                'ketvf'   => $request->ketvf,
                'ketlain' => $request->tambahanket,
            ]);
        });

        return redirect('vrusak')->with('success', 'Data berhasil diperbarui');
    }
    public function delete(Request $request, $idrusak)
    {
        if (auth()->user()->username !== 'admin_cluster') {
            return back()->with('error', 'Akses ditolak. Hanya Admin Cluster yang boleh menghapus data.');
        }

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
