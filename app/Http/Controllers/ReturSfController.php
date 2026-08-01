<?php

namespace App\Http\Controllers;

use App\Helpers\TapFilter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReturSFExport;
use Carbon\Carbon;
use App\Helpers\AuditLogger;

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
        ->whereBetween('r.tgl', [$start . ' 00:00:00', $end . ' 23:59:59']);

    TapFilter::apply($query, 'r.idtap');

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
        $btnEdit = '
            <a href="'.url('retursf/edit/'.$row->idretur).'" class="btn btn-link text-primary p-0 mr-2" title="Edit">
                <i class="fas fa-edit fa-lg"></i>
            </a>
        ';

        if (!auth()->user()->hasClusterAdminAccess()) {
            return '<div class="d-flex align-items-center justify-content-center">' . $btnEdit . '</div>';
        }

        $btnDelete = '
            <form action="'.url('retursf/'.$row->idretur).'" 
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
        if (!is_array($request->input('items'))) {
            $request->merge(['items' => [[
                'iddenom' => $request->iddenom,
                'qty' => $request->qty,
                'sn' => $request->sn,
                'tambahket' => $request->tambahket,
            ]]]);
        }

        $validated = $request->validate([
            'tgl' => 'required|date',
            'idtap' => 'required|exists:kodetap,idtap',
            'idsf' => 'required|exists:idsf,idsf',
            'ketvf' => 'required|in:OK,RUSAK,MATI',
            'items' => 'required|array|min:1',
            'items.*.iddenom' => 'required|exists:denom,iddenom',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.sn' => 'required|string|max:255',
            'items.*.tambahket' => 'required|string|max:500',
        ]);
        usort($validated['items'], fn ($a, $b) => strcmp($a['iddenom'], $b['iddenom']));

        if (session('idtap') !== 'SBP_DUMAI' && $validated['idtap'] !== session('idtap')) {
            abort(403);
        }
        abort_unless(
            DB::table('idsf')->where('idsf', $validated['idsf'])->where('idtap', $validated['idtap'])->exists(),
            422,
            'Petugas tidak sesuai dengan TAP.'
        );

        DB::transaction(function () use ($validated) {
            $requestedByDenom = collect($validated['items'])
                ->groupBy('iddenom')
                ->map(fn ($items) => $items->sum('qty'));

            foreach ($requestedByDenom as $iddenom => $requestedQty) {
                $stockSf = DB::table('stockawalsf')
                    ->where('idsf', $validated['idsf'])
                    ->where('iddenom', $iddenom)
                    ->lockForUpdate()
                    ->value('stock') ?? 0;

                if ($stockSf < $requestedQty) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => "Total qty {$iddenom} tidak mencukupi. Diminta: {$requestedQty}, tersedia: {$stockSf}.",
                    ]);
                }
            }

            foreach ($validated['items'] as $item) {
                $newId = DB::table('retursf')->insertGetId([
                    'tgl' => $validated['tgl'],
                    'idtap' => $validated['idtap'],
                    'idsf' => $validated['idsf'],
                    'iddenom' => $item['iddenom'],
                    'qty' => $item['qty'],
                    'sn' => $item['sn'],
                    'ketvf' => $validated['ketvf'],
                    'tambahket' => $item['tambahket'],
                ]);

                DB::table('stockawalsf')
                    ->where('idsf', $validated['idsf'])
                    ->where('iddenom', $item['iddenom'])
                    ->decrement('stock', $item['qty']);

                DB::table('stockawaltap')->insertOrIgnore([
                    'idtap' => $validated['idtap'],
                    'iddenom' => $item['iddenom'],
                    'stock' => 0,
                ]);
                DB::table('stockawaltap')
                    ->where('idtap', $validated['idtap'])
                    ->where('iddenom', $item['iddenom'])
                    ->increment('stock', $item['qty']);

                AuditLogger::log('INSERT', 'Retur SF (Bulk)', $newId, null, $item + [
                    'tgl' => $validated['tgl'],
                    'idtap' => $validated['idtap'],
                    'idsf' => $validated['idsf'],
                    'ketvf' => $validated['ketvf'],
                ]);
            }
        });

        return redirect('retursf')->with('success', count($validated['items']) . ' denom retur SF berhasil disimpan');
    }

    /* ===============================
       EDIT
    =============================== */
    public function edit($id)
    {
        $idtap_session = session('idtap');
        $edit = DB::table('retursf')->where('idretur', $id)->first();
        
        if (!$edit) return redirect('retursf')->with('error', 'Data tidak ditemukan');

        $tap = DB::table('kodetap')
            ->when($idtap_session !== 'SBP_DUMAI', fn ($q) => $q->where('idtap', $idtap_session))
            ->get();

        $idsf = DB::table('idsf')->where('idtap', $edit->idtap)->get();
        $denom = DB::table('denom')->get();

        return view('form.form-edit-retursf', compact('edit', 'tap', 'idsf', 'denom', 'idtap_session'));
    }

    public function update(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $oldData = DB::table('retursf')->where('idretur', $id)->lockForUpdate()->first();
            
            // 1. Rollback stok lama
            // retur: SF -> TAP. Rollback: TAP -> SF
            $stokTapOld = DB::table('stockawaltap')->where('idtap', $oldData->idtap)->where('iddenom', $oldData->iddenom)->lockForUpdate()->value('stock');
            if ($stokTapOld < $oldData->qty) {
                throw new \Exception('Stok TAP tidak mencukupi untuk membatalkan data lama');
            }
            DB::table('stockawaltap')->where('idtap', $oldData->idtap)->where('iddenom', $oldData->iddenom)->decrement('stock', $oldData->qty);
            DB::table('stockawalsf')->where('idsf', $oldData->idsf)->where('iddenom', $oldData->iddenom)->increment('stock', $oldData->qty);

            // 2. Terapkan stok baru
            // retur baru: SF -> TAP
            $stokSfNew = DB::table('stockawalsf')->where('idsf', $request->idsf)->where('iddenom', $request->iddenom)->lockForUpdate()->value('stock');
            if ($stokSfNew < $request->qty) {
                throw new \Exception('Stok SF tidak mencukupi untuk data baru ini');
            }
            DB::table('stockawalsf')->where('idsf', $request->idsf)->where('iddenom', $request->iddenom)->decrement('stock', $request->qty);
            DB::table('stockawaltap')->where('idtap', $request->idtap)->where('iddenom', $request->iddenom)->increment('stock', $request->qty);

            // 3. Update record
            DB::table('retursf')->where('idretur', $id)->update([
                'tgl'        => $request->tgl,
                'idtap'      => $request->idtap,
                'idsf'       => $request->idsf,
                'iddenom'    => $request->iddenom,
                'qty'        => $request->qty,
                'sn'         => $request->sn,
                'ketvf'      => $request->ketvf,
                'tambahket'  => $request->tambahket,
            ]);

            // 📝 LOG
            AuditLogger::log('UPDATE', 'Retur SF', $id, (array)$oldData, $request->all());
        });

        return redirect('retursf')->with('success', 'Data retur berhasil diperbarui');
    }

    /* ===============================
       DELETE
    =============================== */
    public function delete(Request $request, $idretur)
    {
        if (!auth()->user()->hasClusterAdminAccess()) {
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

            // 📝 LOG
            AuditLogger::log('DELETE', 'Retur SF', $idretur, (array)$data);
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
            ->whereBetween('r.tgl', [$start . ' 00:00:00', $end . ' 23:59:59']);

        TapFilter::apply($query, 'r.idtap');

        return Excel::download(
            new ReturSFExport($query->get()),
            'RETUR_SF_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function bulkDelete(Request $request)
    {
        if (!auth()->user()->hasClusterAdminAccess()) {
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

                    // 📝 LOG
                    AuditLogger::log('DELETE (BULK)', 'Retur SF', $idretur, (array)$data);
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
