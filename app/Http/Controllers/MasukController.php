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
            return datatables()->of(collect([]))->make(true);
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
                'k.idkeluar',
                'k.tgl',
                'd.denom',
                'k.qty',
                'k.idtap',
                'k.penerima',
                'k.sn',
                'k.status',
                'k.iddenom'
            )
            ->whereBetween('k.tgl', [$start, $end])
            ->whereNotIn('k.pengirim', $kategoribo);

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('k.penerima', $idtap);
        }

    
        return datatables()
        ->of($query)
        ->editColumn('tgl', fn($r) => Carbon::parse($r->tgl)->format('Y-m-d'))
        ->addColumn('action', function ($row) {
            if ($row->status == 1) {
                // Hanya penerima yang boleh approve
                if (session('idtap') === $row->penerima || session('idtap') === 'SBP_DUMAI') {
                    return '
                    <form action="'.url('masuk/'.$row->idkeluar).'" method="POST" class="d-inline">
                        '.csrf_field().'
                        <button type="submit" class="btn btn-success btn-sm font-weight-bold" style="border-radius:20px;">
                            <i class="fas fa-check-circle mr-1"></i> TERIMA
                        </button>
                    </form>';
                }
            }
            return '<span class="text-muted small">No Action</span>';
        })
        ->rawColumns(['action'])
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
        DB::transaction(function () use ($idkeluar) {
            // Tarik data asli dari DB untuk keamanan
            $data = DB::table('keluar')
                ->where('idkeluar', $idkeluar)
                ->lockForUpdate()
                ->first();

            if (!$data) {
                throw new \Exception('Data tidak ditemukan');
            }

            if ($data->status == 0) {
                throw new \Exception('Data sudah pernah disetujui');
            }

            // cek stok pengirim
            $stok = DB::table('stockawaltap')
                ->where('idtap', $data->idtap) // pengirim adalah idtap di tabel keluar
                ->where('iddenom', $data->iddenom)
                ->lockForUpdate()
                ->value('stock');

            if ($stok < $data->qty) {
                throw new \Exception('Stok Tap Pengirim Tidak Mencukupi');
            }

            // kurangi pengirim
            DB::table('stockawaltap')
                ->where('idtap', $data->idtap)
                ->where('iddenom', $data->iddenom)
                ->decrement('stock', $data->qty);

            // tambah penerima
            DB::table('stockawaltap')
                ->where('idtap', $data->penerima)
                ->where('iddenom', $data->iddenom)
                ->increment('stock', $data->qty);

            // approve
            DB::table('keluar')
                ->where('idkeluar', $idkeluar)
                ->update(['status' => 0]);
        });

        return back()->with('success', 'Stock berhasil diterima');
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
