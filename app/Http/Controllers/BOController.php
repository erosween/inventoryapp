<?php

namespace App\Http\Controllers;

use App\Helpers\TapFilter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BOExport;
use App\Helpers\AuditLogger;

class BOController extends Controller
{
    /* =========================
       VIEW
    ========================= */
    public function index()
    {
        return view('bo');
    }

    /* =========================
       DATATABLE SERVER SIDE
    ========================= */
    public function data(Request $request)
    {
        $idtap = session('idtap');

        if ($request->filled('daterange')) {
            [$start, $end] = explode(' - ', $request->daterange);
        } else {
            $start = now()->startOfMonth()->format('Y-m-d');
            $end   = now()->endOfMonth()->format('Y-m-d');
        }

        $kategoritap = [
            'DUMAI','DURI','BENGKALIS','SEI PAKNING',
            'RUPAT','BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG'
        ];

        $query = DB::table('keluar as k')
            ->join('denom as d', 'd.iddenom', '=', 'k.iddenom')
            ->select(
                'k.idkeluar',
                'k.tgl',
                'd.denom',
                'k.qty',
                'k.pengirim',
                'k.penerima',
                'k.sn',
                'k.tambahanket'
            )
            ->whereNotIn('k.pengirim', $kategoritap)
            ->whereBetween('k.tgl', [
                $start.' 00:00:00',
                $end.' 23:59:59'
            ]);

        TapFilter::apply($query, 'k.idtap');

        return DataTables::of($query)
            ->editColumn('tgl', fn($r) => Carbon::parse($r->tgl)->format('d-m-Y'))
            ->editColumn('qty', fn($r) => number_format($r->qty))
            ->addColumn('action', function ($row) {
                $btnEdit = '
                    <a href="'.url('bo/edit/'.$row->idkeluar).'" class="btn btn-link text-primary p-0 mr-2" title="Edit">
                        <i class="fas fa-edit fa-lg"></i>
                    </a>
                ';

                if (!auth()->user()->hasClusterAdminAccess()) {
                    return '<div class="d-flex align-items-center justify-content-center">' . $btnEdit . '</div>';
                }

                $btnDelete = '
                    <form action="'.url('bo/delete/'.$row->idkeluar).'"
                          method="POST"
                          class="form-delete d-inline">
                        '.csrf_field().'
                        <button type="submit" class="btn btn-link text-danger p-0" title="Delete">
                            <i class="fas fa-trash-alt fa-lg"></i>
                        </button>
                    </form>
                ';

                return '<div class="d-flex align-items-center justify-content-center">' . $btnEdit . $btnDelete . '</div>';
            })
            ->rawColumns(['action'])
            ->orderColumn('tgl', 'k.tgl $1')
            ->make(true);
    }

    /* =========================
       FORM INPUT BO
    ========================= */
    public function keluarboform()
    {
        $idtap = session('idtap');

        $data = DB::table('kategori_bo')
            ->when($idtap !== 'SBP_DUMAI', fn($q) => $q->where('idtap', $idtap))
            ->get();

        return view('form/formkeluarbo', compact('data','idtap'));
    }

    // get Tap
public function getTap(Request $request)
{
    $bo = $request->idtap; // ini nama BO (namabo)
    $sessionTap = session('idtap');

    $query = DB::table('kategori_bo');

    if ($sessionTap === 'SBP_DUMAI') {
        $query->where('namabo', $bo);
    } else {
        $query->where('idtap', $sessionTap);
    }

    $taps = $query->get();

    $html = '<option value="">-- Pilih TAP --</option>';

    foreach ($taps as $tap) {
        $html .= '<option value="'.$tap->idtap.'">'.$tap->idtap.'</option>';
    }

    return response($html);
}



    /* =========================
       STORE (ANTI DOUBLE SUBMIT)
    ========================= */
    public function proseskeluarboform(Request $request)
{
    $data = $request->only([
        'pengirim','penerima','qty','iddenom','sn','tambahanket','tgl'
    ]);

    return DB::transaction(function () use ($data) {

        $stokBo = DB::table('stockawalsf')
            ->where('idsf', $data['pengirim'])
            ->where('iddenom', $data['iddenom'])
            ->lockForUpdate()
            ->value('stock');

        if ($stokBo === null) {
            return back()->with('error', 'Stok BO belum terdaftar');
        }

        if ($stokBo < $data['qty']) {
            return back()->with('error', 'Stok BO tidak mencukupi');
        }

        $newId = DB::table('keluar')->insertGetId([
            'iddenom' => $data['iddenom'],
            'pengirim' => $data['pengirim'],
            'penerima' => $data['penerima'],
            'qty' => $data['qty'],
            'tgl' => $data['tgl'],
            'sn' => $data['sn'],
            'tambahanket' => $data['tambahanket'],
            'idtap' => $data['penerima'],
            'status' => 0
        ]);

        // 📝 LOG
        AuditLogger::log('INSERT', 'BO / Retur', $newId, null, $data);

        DB::table('stockawalsf')
            ->where('idsf', $data['pengirim'])
            ->where('iddenom', $data['iddenom'])
            ->decrement('stock', $data['qty']);

        DB::table('stockawaltap')
            ->where('idtap', $data['penerima'])
            ->where('iddenom', $data['iddenom'])
            ->increment('stock', $data['qty']);

        return redirect('bo')->with('success', 'Data berhasil ditambahkan');
    });
}


    /* =========================
       EDIT
    ========================= */
    public function edit($id)
    {
        $idtap_session = session('idtap');
        $edit = DB::table('keluar')->where('idkeluar', $id)->first();
        
        if (!$edit) return redirect('bo')->with('error', 'Data tidak ditemukan');

        $data = DB::table('kategori_bo')
            ->when($idtap_session !== 'SBP_DUMAI', fn($q) => $q->where('idtap', $idtap_session))
            ->get();

        return view('form.form-edit-bo', compact('edit', 'data', 'idtap_session'));
    }

    public function update(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $oldData = DB::table('keluar')->where('idkeluar', $id)->lockForUpdate()->first();
            
            // Rollback stok lama
            DB::table('stockawalsf')->where('idsf', $oldData->pengirim)->where('iddenom', $oldData->iddenom)->increment('stock', $oldData->qty);
            DB::table('stockawaltap')->where('idtap', $oldData->penerima)->where('iddenom', $oldData->iddenom)->decrement('stock', $oldData->qty);

            // Terapkan stok baru
            $stokBo = DB::table('stockawalsf')->where('idsf', $request->pengirim)->where('iddenom', $request->iddenom)->lockForUpdate()->value('stock');

            if ($stokBo < $request->qty) {
                throw new \Exception('Stok BO tidak mencukupi untuk update ini');
            }

            // Kurangi BO, Tambah TAP
            DB::table('stockawalsf')->where('idsf', $request->pengirim)->where('iddenom', $request->iddenom)->decrement('stock', $request->qty);
            DB::table('stockawaltap')->where('idtap', $request->penerima)->where('iddenom', $request->iddenom)->increment('stock', $request->qty);

            // Update record
            DB::table('keluar')->where('idkeluar', $id)->update([
                'iddenom' => $request->iddenom,
                'pengirim' => $request->pengirim,
                'penerima' => $request->penerima,
                'qty' => $request->qty,
                'tgl' => $request->tgl,
                'sn' => $request->sn,
                'tambahanket' => $request->tambahanket,
                'idtap' => $request->penerima
            ]);

            // 📝 LOG
            AuditLogger::log('UPDATE', 'BO / Retur', $id, (array)$oldData, $request->all());
        });

        return redirect('bo')->with('success', 'Data berhasil diperbarui');
    }

    /* =========================
       DELETE (ROLLBACK STOK)
    ========================= */
    public function delete($idkeluar)
    {
        if (!auth()->user()->hasClusterAdminAccess()) {
            return redirect('bo')->with('error', 'Akses ditolak. Hanya Admin Cluster yang boleh menghapus data.');
        }

        try {
            DB::transaction(function () use ($idkeluar) {

                $data = DB::table('keluar')
                    ->where('idkeluar', $idkeluar)
                    ->lockForUpdate()
                    ->first();

                if (!$data) throw new \Exception('Data tidak ditemukan');

                $stokTap = DB::table('stockawaltap')
                    ->where('idtap', $data->penerima)
                    ->where('iddenom', $data->iddenom)
                    ->lockForUpdate()
                    ->value('stock');

                if ($stokTap < $data->qty) {
                    throw new \Exception('Stok TAP tidak mencukupi');
                }

                DB::table('stockawaltap')
                    ->where('idtap', $data->penerima)
                    ->where('iddenom', $data->iddenom)
                    ->decrement('stock', $data->qty);

                DB::table('stockawalsf')
                    ->where('idsf', $data->pengirim)
                    ->where('iddenom', $data->iddenom)
                    ->increment('stock', $data->qty);

                // 📝 LOG
                AuditLogger::log('DELETE', 'BO / Retur', $idkeluar, (array)$data);

                DB::table('keluar')->where('idkeluar', $idkeluar)->delete();
            });

            return redirect('bo')->with('success', 'Data berhasil dihapus');

        } catch (\Exception $e) {
            return redirect('bo')->with('error', $e->getMessage());
        }
    }


    public function exportexcel(Request $request)
{
    $idtap = session('idtap');

    if ($request->filled('daterange')) {
        [$start, $end] = explode(' - ', $request->daterange);
    } else {
        $start = now()->startOfMonth()->format('Y-m-d');
        $end   = now()->endOfMonth()->format('Y-m-d');
    }

    $kategoritap = [
        'DUMAI','DURI','BENGKALIS','SEI PAKNING',
        'RUPAT','BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG'
    ];

    $query = DB::table('keluar as m')
        ->join('denom as d', 'd.iddenom', 'm.iddenom')
        ->select(
            'm.tgl',
            'm.pengirim',
            'm.penerima',
            'd.denom',
            DB::raw('SUM(m.qty) as qty')
        )
        ->whereNotIn('m.pengirim', $kategoritap)
        ->whereBetween('m.tgl', [
            $start.' 00:00:00',
            $end.' 23:59:59'
        ])
        ->groupBy('m.tgl','m.pengirim','m.penerima','d.denom');

    TapFilter::apply($query, 'm.idtap');

    return Excel::download(
        new BOExport($query->get()),
        "RETUR_BO_{$start}_sd_{$end}.xlsx"
    );
}

}
