<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasukExport;
use App\Helpers\AuditLogger;

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
        try {
            DB::transaction(function () use ($idkeluar) {
                // 1. Lock data keluar
                $data = DB::table('keluar')
                    ->where('idkeluar', $idkeluar)
                    ->lockForUpdate()
                    ->first();

                if (!$data) {
                    throw new \Exception('Data transaksi tidak ditemukan');
                }

                if ($data->status == 0) {
                    throw new \Exception('Data sudah pernah disetujui');
                }

                $sender = $data->idtap;
                $receiver = $data->penerima;
                $iddenom = $data->iddenom;
                $qty = $data->qty;

                \Log::info("Transfer info: Sender $sender, Receiver $receiver, Qty $qty");

                // 2. Cegah DEADLOCK dengan urutan konsisten
                $taps = [$sender, $receiver];
                sort($taps);

                foreach ($taps as $tap) {
                    // Pastikan record ada sebelum dilock (Tanpa updated_at)
                    DB::table('stockawaltap')->insertOrIgnore([
                        'idtap' => $tap,
                        'iddenom' => $iddenom,
                        'stock' => 0
                    ]);

                    DB::table('stockawaltap')
                        ->where('idtap', $tap)
                        ->where('iddenom', $iddenom)
                        ->lockForUpdate()
                        ->get();
                }

                // 3. Ambil nilai stok pengirim terbaru setelah dilock
                $stokPengirim = DB::table('stockawaltap')
                    ->where('idtap', $sender)
                    ->where('iddenom', $iddenom)
                    ->value('stock');

                if ($stokPengirim < $qty) {
                    throw new \Exception("Stok TAP Pengirim ($sender) tidak mencukupi. Tersedia: " . number_format($stokPengirim));
                }

                // 4. Update stok
                DB::table('stockawaltap')
                    ->where('idtap', $sender)
                    ->where('iddenom', $iddenom)
                    ->decrement('stock', $qty);

                DB::table('stockawaltap')
                    ->where('idtap', $receiver)
                    ->where('iddenom', $iddenom)
                    ->increment('stock', $qty);

                // 5. Update status
                DB::table('keluar')
                    ->where('idkeluar', $idkeluar)
                    ->update(['status' => 0]);

                AuditLogger::log('APPROVE', 'Stok Masuk TAP', $idkeluar, ['status' => 1], ['status' => 0]);
            });

            return back()->with('success', 'Stock berhasil diterima');

        } catch (\Exception $e) {
            \Log::error("Error approving stock transfer ID $idkeluar: " . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
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
