<?php

namespace App\Http\Controllers;

use App\Helpers\TapFilter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\KeluarExport;
use App\Helpers\AuditLogger;

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
            ->whereBetween('k.tgl', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->whereNotIn('k.pengirim', $kategoribo);

        TapFilter::apply($query, 'k.pengirim');

        return datatables()
            ->of($query)
            ->editColumn('qty', fn($r) => number_format($r->qty))
            ->addColumn('status_label', function ($r) {
                if ($r->status == 0) {
                    return '<span class="badge badge-success">Approved</span>';
                }

                $status = '<span class="badge badge-warning">Wait for Approval</span>';
                if (session('idtap') === 'SBP_DUMAI' || session('idtap') === $r->pengirim) {
                    $status .= '<form action="'.route('keluar.cancel', $r->idkeluar).'" method="POST" class="d-inline ml-2 cancel-transfer-form"'
                        .' data-denom="'.e($r->denom).'" data-qty="'.e(number_format($r->qty)).'" data-penerima="'.e($r->penerima).'">'
                        .csrf_field().
                        '<button type="submit" class="btn btn-link btn-sm text-danger font-weight-bold p-0" title="Batalkan pengiriman">Cancel</button>'
                        .'</form>';
                }

                return $status;
            })
            ->rawColumns(['status_label'])
            ->make(true);

    }

    public function cancel($idkeluar)
    {
        DB::transaction(function () use ($idkeluar) {
            $transfer = DB::table('keluar')
                ->where('idkeluar', $idkeluar)
                ->lockForUpdate()
                ->first();

            if (! $transfer) {
                abort(404, 'Data pengiriman tidak ditemukan.');
            }
            if ((int) $transfer->status !== 1) {
                return abort(422, 'Pengiriman yang sudah diterima tidak dapat dibatalkan.');
            }
            if (session('idtap') !== 'SBP_DUMAI' && session('idtap') !== $transfer->pengirim) {
                abort(403, 'Hanya TAP pengirim yang dapat membatalkan pengiriman.');
            }

            AuditLogger::log('CANCEL', 'Stok Keluar TAP', $idkeluar, (array) $transfer);
            DB::table('keluar')->where('idkeluar', $idkeluar)->delete();
        });

        return redirect()->route('keluar.index')->with('success', 'Pengiriman dibatalkan. Silakan input kembali data yang benar.');
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
            ->whereBetween('k.tgl', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->groupBy('k.tgl','k.sn','k.pengirim','k.penerima','d.denom');

        TapFilter::apply($query, 'k.pengirim');

        return Excel::download(
            new KeluarExport($query->get()),
            'STOK_KELUAR_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
