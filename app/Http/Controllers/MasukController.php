<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasukExport;

class MasukController extends Controller
{
    /* =====================================================
       VIEW
    ===================================================== */
    public function index()
    {
        $idtap = session('idtap');

        $kategoribo = [
            'BO DUMAI',
            'BO DURI',
            'BO BENGKALIS',
            'BO BAGAN BATU',
            'BO BAGAN SIAPI-API'
        ];

        // hitung pending approval
        $unapprovedCount = DB::table('keluar')
            ->when($idtap !== 'SBP_DUMAI', function ($q) use ($idtap) {
                $q->where('penerima', $idtap);
            })
            ->where('status', 1)
            ->whereNotIn('pengirim', $kategoribo)
            ->count();

        return view('masuk', compact('unapprovedCount'));
    }

    /* =====================================================
       DATATABLE (DATE RANGE)
    ===================================================== */
    public function data(Request $request)
    {
        $idtap = session('idtap');

        if (!$request->daterange) {
            return response()->json([
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0
            ]);
        }

        [$start, $end] = explode(' - ', $request->daterange);

        $kategoribo = [
            'BO DUMAI',
            'BO DURI',
            'BO BENGKALIS',
            'BO BAGAN BATU',
            'BO BAGAN SIAPI-API'
        ];

        $query = DB::table('keluar as k')
            ->join('denom as d', 'd.iddenom', '=', 'k.iddenom')
            ->select(
                'k.tgl',
                'd.denom',
                'k.qty',
                'k.idtap',
                'k.penerima',
                'k.sn',
                'k.status'
            )
            ->whereBetween('k.tgl', [$start, $end])
            ->whereNotIn('k.pengirim', $kategoribo);

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('k.penerima', $idtap);
        }

    
        return datatables()
        ->of($query)
        ->editColumn('tgl', fn($r) => Carbon::parse($r->tgl)->format('Y-m-d'))
        ->filterColumn('denom', function ($q, $keyword) {
            $q->whereRaw("LOWER(d.denom) LIKE ?", ["%".strtolower($keyword)."%"]);
        })
        ->make(true);
    }

    /* =====================================================
       SUMMARY MODAL (PER DENOM)
    ===================================================== */
    public function summary(Request $request)
    {
        $idtap = session('idtap');

        if (!$request->daterange) return [];

        [$start, $end] = explode(' - ', $request->daterange);

        $kategoribo = [
            'BO DUMAI',
            'BO DURI',
            'BO BENGKALIS',
            'BO BAGAN BATU',
            'BO BAGAN SIAPI-API'
        ];

        $query = DB::table('keluar as k')
            ->join('denom as d', 'd.iddenom', '=', 'k.iddenom')
            ->select('d.denom', DB::raw('SUM(k.qty) as qty'))
            ->whereBetween('k.tgl', [$start, $end])
            ->whereNotIn('k.pengirim', $kategoribo)
            ->groupBy('d.denom');

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('k.penerima', $idtap);
        }

        return $query->get();
    }

    /* =====================================================
       TERIMA BARANG (APPROVAL)
    ===================================================== */
    public function masuk(Request $request, $idkeluar)
    {
        DB::transaction(function () use ($request, $idkeluar) {

            // cek stok pengirim
            $stok = DB::table('stockawaltap')
                ->where('idtap', $request->pengirim)
                ->where('iddenom', $request->iddenom)
                ->lockForUpdate()
                ->value('stock');

            if ($stok < $request->qty) {
                throw new \Exception('Stok Tap Pengirim Tidak Mencukupi');
            }

            // kurangi pengirim
            DB::table('stockawaltap')
                ->where('idtap', $request->pengirim)
                ->where('iddenom', $request->iddenom)
                ->decrement('stock', $request->qty);

            // tambah penerima
            DB::table('stockawaltap')
                ->where('idtap', $request->penerima)
                ->where('iddenom', $request->iddenom)
                ->increment('stock', $request->qty);

            // approve
            DB::table('keluar')
                ->where('idkeluar', $idkeluar)
                ->update(['status' => 0]);
        });

        return back()->with('status', 'Stock berhasil diterima');
    }

    /* =====================================================
       EXPORT (DATE RANGE)
    ===================================================== */
    public function exportexcel(Request $request)
    {
        $idtap = session('idtap');
        [$start, $end] = explode(' - ', $request->daterange);

        $kategoribo = [
            'BO DUMAI',
            'BO DURI',
            'BO BENGKALIS',
            'BO BAGAN BATU',
            'BO BAGAN SIAPI-API'
        ];

        $query = DB::table('keluar as k')
            ->join('denom as d', 'd.iddenom', '=', 'k.iddenom')
            ->select(
                'k.tgl',
                'k.sn',
                'k.idtap',
                'k.penerima',
                'd.denom',
                DB::raw('SUM(k.qty) as qty')
            )
            ->whereBetween('k.tgl', [$start, $end])
            ->whereNotIn('k.pengirim', $kategoribo)
            ->groupBy('k.tgl', 'k.sn', 'k.idtap', 'k.penerima', 'd.denom');

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('k.penerima', $idtap);
        }

        $filename = 'STOK_MASUK_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new MasukExport($query->get()), $filename);
    }
}
