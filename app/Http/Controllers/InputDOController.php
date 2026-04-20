<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DOExport;

class InputDOController extends Controller
{
    /* =====================================================
       INDEX (VIEW)
    ===================================================== */
    public function index()
    {
        return view('DO');
    }

    /* =====================================================
       DATATABLE DATA (DATE RANGE)
    ===================================================== */
    public function data(Request $request)
    {
        $idtap = session('idtap');

        if (!$request->daterange) {
            return response()->json([
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
            ]);
        }

        [$start, $end] = explode(' - ', $request->daterange);

        $query = DB::table('masuk as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->leftJoin('stockawalsf as sf', function ($join) {
                $join->on('m.penerima', '=', 'sf.idsf')
                     ->on('m.iddenom', '=', 'sf.iddenom');
            })
            ->select(
                'm.idmasuk',
                'm.tgl',
                'm.nomor_do',
                'm.week',
                'd.denom',
                'm.qty',
                'm.pengirim',
                'm.idtappenerima',
                'm.sn',
                'm.penerima',
                'm.iddenom',
                DB::raw('COALESCE(sf.stock, 0) as sf_stock')
            )
            ->where('m.pengirim', 'DO')
            ->whereBetween('m.tgl', [$start, $end]);

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('m.idtappenerima', $idtap);
        }

        return datatables()
            ->of($query)
            ->addColumn('action', function ($row) {

                // kalau stok sudah dipakai → disable delete
                if ($row->sf_stock < $row->qty) {
                    return '<span class="badge badge-secondary">USED</span>';
                }

                return '
                <form action="' . url('DO/' . $row->idmasuk) . '" 
                    method="POST" 
                    class="form-delete d-inline">
                    '.csrf_field().'
                    <button type="submit" class="btn btn-link text-danger p-0" title="Delete">
                        <i class="fas fa-trash-alt fa-lg"></i>
                    </button>
                </form>';
            })
            ->editColumn('tgl', function ($row) {
                return Carbon::parse($row->tgl)->format('Y-m-d');
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /* =====================================================
       FORM DO
    ===================================================== */
    public function formDO()
    {
        $idtap = session('idtap');

        $data = DB::table('kategori_bo')
            ->when($idtap !== 'SBP_DUMAI', function ($q) use ($idtap) {
                $q->where('idtap', $idtap);
            })
            ->get();

        return view('form.formDO', compact('data', 'idtap'));
    }

    /* =====================================================
       SIMPAN DO (MASUK KE SF)
    ===================================================== */
    public function masukProses(Request $request)
    {
        $request->validate([
            'kategorisegel' => 'required',
            'penerima'      => 'required', // idsf
            'qty'           => 'required|numeric|min:1',
            'tgl'           => 'required|date',
            'nomordo'       => 'required',
            'week'          => 'required',
            'idtap'         => 'required'
        ]);

        DB::transaction(function () use ($request) {

            // INSERT DO
            DB::table('masuk')->insert([
                'iddenom'        => $request->kategorisegel,
                'pengirim'       => 'DO',
                'penerima'       => $request->penerima, // idsf
                'qty'            => $request->qty,
                'sn'             => $request->sn,
                'nomor_do'       => $request->nomordo,
                'week'           => $request->week,
                'tgl'            => $request->tgl,
                'idtappenerima'  => $request->idtap,
                'idtap'          => $request->idtap
            ]);

            // TAMBAH STOK SF
            DB::table('stockawalsf')
                ->where('idsf', $request->penerima)
                ->where('iddenom', $request->kategorisegel)
                ->increment('stock', $request->qty);
        });

        return redirect()
            ->route('do.index')
            ->with('success', 'DO berhasil ditambahkan');
    }

    /* =====================================================
       DELETE DO (ANTI MINUS)
    ===================================================== */
    public function delete(Request $request, $idmasuk)
    {
        try {
            DB::transaction(function () use ($idmasuk) {

                $data = DB::table('masuk')
                    ->where('idmasuk', $idmasuk)
                    ->lockForUpdate()
                    ->first();

                if (!$data) {
                    throw new \Exception('Data DO tidak ditemukan');
                }

                // ambil stok sekarang
                $stokSekarang = DB::table('stockawalsf')
                    ->where('idsf', $data->penerima)
                    ->where('iddenom', $data->iddenom)
                    ->lockForUpdate()
                    ->value('stock');

                // ❌ kalau sudah dipakai → STOP
                if ($stokSekarang < $data->qty) {
                    throw new \Exception('DO sudah digunakan, tidak bisa dihapus');
                }

                // balikin stok
                DB::table('stockawalsf')
                    ->where('idsf', $data->penerima)
                    ->where('iddenom', $data->iddenom)
                    ->decrement('stock', $data->qty);

                // hapus DO
                DB::table('masuk')
                    ->where('idmasuk', $idmasuk)
                    ->delete();
            });

            return back()->with('success', 'DO berhasil dihapus');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

/* =====================================================
   AJAX GET TAP (BO → TAP)
===================================================== */
public function getTap(Request $request)
{
    $bo = $request->bo;
    $idtapSession = session('idtap');

    $query = DB::table('kategori_bo')
        ->where('namabo', $bo);

    // kalau bukan SBP_DUMAI → kunci ke tap session
    if ($idtapSession !== 'SBP_DUMAI') {
        $query->where('idtap', $idtapSession);
    }

    $taps = $query->get();

    $html = '<option value="">-- Pilih TAP --</option>';

    foreach ($taps as $tap) {
        $html .= '<option value="' . $tap->idtap . '">' . $tap->idtap . '</option>';
    }

    return response($html);
}



    /* =====================================================
       EXPORT EXCEL (DATE RANGE)
    ===================================================== */
    public function exportexcel(Request $request)
    {
        [$start, $end] = explode(' - ', $request->daterange);
        $idtap = session('idtap');

        $query = DB::table('masuk as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->select(
                'm.tgl',
                'm.nomor_do',
                'm.sn',
                'm.idtappenerima',
                'd.denom',
                DB::raw('SUM(m.qty) as qty')
            )
            ->where('m.pengirim', 'DO')
            ->whereBetween('m.tgl', [$start, $end])
            ->groupBy('m.tgl', 'm.nomor_do', 'm.sn', 'm.idtappenerima', 'd.denom');

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('m.idtappenerima', $idtap);
        }

        $filename = 'DO_MASUK_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new DOExport($query->get()), $filename);
    }
}
