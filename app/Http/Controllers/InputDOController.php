<?php

namespace App\Http\Controllers;

use App\Helpers\TapFilter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DOExport;
use App\Helpers\AuditLogger;
use App\Support\DenomGrouping;
use Illuminate\Validation\Rule;

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
            ->leftJoin('stockawaltap as tap_stock', function ($join) {
                $join->on('m.idtap', '=', 'tap_stock.idtap')
                    ->on('m.iddenom', '=', 'tap_stock.iddenom');
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
                DB::raw('CASE WHEN '.DenomGrouping::legacyDoAtTapSql('m').' THEN COALESCE(tap_stock.stock, 0) ELSE COALESCE(sf.stock, 0) END as available_stock')
            )
            ->where('m.pengirim', 'DO')
            ->whereBetween('m.tgl', [$start . ' 00:00:00', $end . ' 23:59:59']);

        TapFilter::apply($query, 'm.idtappenerima');

        return datatables()
            ->of($query)
            ->addColumn('action', function ($row) {
                $btnEdit = '
                    <a href="' . url('DO/edit/' . $row->idmasuk) . '" class="btn btn-link text-primary p-0 action-edit-icon" title="Edit" aria-label="Edit">
                        <i class="fas fa-edit fa-lg" aria-hidden="true"></i>
                    </a>
                ';

                // kalau stok sudah dipakai → disable delete (tapi edit tetap boleh kalau mau ganti info non-stok seperti nomor DO/Week?)
                // Sebenarnya kalau Qty diganti juga harus hati-hati. 
                // Untuk sekarang kita tampilkan tombol Delete sesuai logic stok.

                if ($row->available_stock < $row->qty) {
                    $btnDelete = '<span class="badge badge-secondary">USED</span>';
                } else {
                    $btnDelete = '
                    <form action="' . url('DO/' . $row->idmasuk) . '" 
                        method="POST" 
                        class="form-delete d-inline">
                        '.csrf_field().'
                        <button type="submit" class="btn btn-link text-danger p-0" title="Delete">
                            <i class="fas fa-trash-alt fa-lg"></i>
                        </button>
                    </form>';
                }

                return '<div class="d-flex align-items-center justify-content-center">' . $btnEdit . $btnDelete . '</div>';
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

        $denom = DB::table('denom')->where('allow_do', true)->whereIn('iddenom', DenomGrouping::DO_CODES)->orderBy('iddenom')->get();
        return view('form.formDO', compact('data', 'idtap', 'denom'));
    }

    /* =====================================================
       SIMPAN DO (MASUK KE BO)
    ===================================================== */
    public function masukProses(Request $request)
    {
        $this->validateDo($request);

        DB::transaction(function () use ($request) {

            // INSERT DO
            $newId = DB::table('masuk')->insertGetId([
                'iddenom'        => $request->kategorisegel,
                'pengirim'       => 'DO',
                'do_received_at_bo' => true,
                'penerima'       => $request->penerima, // idsf
                'qty'            => $request->qty,
                'sn'             => $request->sn,
                'nomor_do'       => $request->nomordo,
                'week'           => $request->week,
                'tgl'            => $request->tgl,
                'idtappenerima'  => $request->idtap,
                'idtap'          => $request->idtap
            ]);

            // 📝 LOG
            AuditLogger::log('INSERT', 'Input DO', $newId, null, $request->all());

            $this->incrementBoStock($request->penerima, $request->kategorisegel, (int) $request->qty);
        });

        return redirect()
            ->route('do.index')
            ->with('success', 'DO berhasil ditambahkan');
    }

    /* =====================================================
       EDIT DO
    ===================================================== */
    public function edit($id)
    {
        $idtap_session = session('idtap');
        $edit = $this->doQuery($id)->first();
        
        if (!$edit) return redirect()->route('do.index')->with('error', 'Data tidak ditemukan');

        $data = DB::table('kategori_bo')
            ->when($idtap_session !== 'SBP_DUMAI', fn($q) => $q->where('idtap', $idtap_session))
            ->get();

        $denom = DB::table('denom')->where('allow_do', true)->whereIn('iddenom', DenomGrouping::DO_CODES)->get();

        return view('form.form-edit-DO', compact('edit', 'data', 'denom', 'idtap_session'));
    }

    public function update(Request $request, $id)
    {
        $this->validateDo($request);
        DB::transaction(function () use ($request, $id) {
            $oldData = $this->doQuery($id)->lockForUpdate()->first();
            abort_unless($oldData, 404);

            $stock = $this->stockForDo($oldData);
            $atTap = $this->wasReceivedAtTap($oldData);
            $sameStock = $oldData->iddenom === $request->kategorisegel
                && ($atTap ? $oldData->idtap === $request->idtap : $oldData->penerima === $request->penerima);

            $available = (int) (clone $stock)->lockForUpdate()->value('stock');
            $qtyToRemove = $sameStock ? max(0, $oldData->qty - $request->qty) : (int) $oldData->qty;
            if ($available < $qtyToRemove) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'qty' => 'Stok penerimaan lama tidak mencukupi untuk koreksi DO.',
                ]);
            }

            if ($qtyToRemove > 0) {
                (clone $stock)->decrement('stock', $qtyToRemove);
            }

            $qtyToAdd = $sameStock ? max(0, $request->qty - $oldData->qty) : (int) $request->qty;
            if ($qtyToAdd > 0) {
                if ($atTap && $sameStock) {
                    $this->incrementTapStock($request->idtap, $request->kategorisegel, $qtyToAdd);
                } else {
                    $this->incrementBoStock($request->penerima, $request->kategorisegel, $qtyToAdd);
                }
            }

            DB::table('masuk')->where('idmasuk', $id)->update([
                'iddenom'        => $request->kategorisegel,
                'do_received_at_bo' => $atTap && $sameStock ? null : true,
                'penerima'       => $request->penerima,
                'qty'            => $request->qty,
                'sn'             => $request->sn,
                'nomor_do'       => $request->nomordo,
                'week'           => $request->week,
                'tgl'            => $request->tgl,
                'idtappenerima'  => $request->idtap,
                'idtap'          => $request->idtap
            ]);

            // 📝 LOG
            AuditLogger::log('UPDATE', 'Input DO', $id, (array)$oldData, $request->all());
        });

        return redirect()->route('do.index')->with('success', 'Data DO berhasil diperbarui');
    }

    /* =====================================================
       DELETE DO (ANTI MINUS)
    ===================================================== */
    public function delete(Request $request, $idmasuk)
    {
        try {
            DB::transaction(function () use ($idmasuk) {

                $data = $this->doQuery($idmasuk)->lockForUpdate()->first();
                if (!$data) {
                    throw new \Exception('Data DO tidak ditemukan');
                }

                $stock = $this->stockForDo($data);
                $available = (int) (clone $stock)->lockForUpdate()->value('stock');
                if ($available < $data->qty) {
                    throw new \Exception('DO sudah digunakan, tidak bisa dihapus');
                }

                $stock->decrement('stock', $data->qty);

                // hapus DO
                DB::table('masuk')
                    ->where('idmasuk', $idmasuk)
                    ->delete();

                // 📝 LOG
                AuditLogger::log('DELETE', 'Input DO', $idmasuk, (array)$data);
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

    private function doQuery($id)
    {
        $query = DB::table('masuk')->where('idmasuk', $id)->where('pengirim', 'DO');
        TapFilter::apply($query, 'idtappenerima');

        return $query;
    }

    private function wasReceivedAtTap(object $do): bool
    {
        return $do->do_received_at_bo === null
            && in_array(strtoupper($do->iddenom), DenomGrouping::LEGACY_DIRECT_DO_CODES, true);
    }

    private function stockForDo(object $do)
    {
        return ($this->wasReceivedAtTap($do)
            ? DB::table('stockawaltap')->where('idtap', $do->idtap)
            : DB::table('stockawalsf')->where('idsf', $do->penerima))
            ->where('iddenom', $do->iddenom);
    }

    private function incrementBoStock(string $bo, string $denom, int $qty): void
    {
        DB::table('stockawalsf')->insertOrIgnore(['idsf' => $bo, 'iddenom' => $denom, 'stock' => 0]);
        DB::table('stockawalsf')->where('idsf', $bo)->where('iddenom', $denom)->increment('stock', $qty);
    }

    private function validateDo(Request $request): void
    {
        $request->validate([
            'kategorisegel' => ['required', Rule::in(DenomGrouping::DO_CODES), Rule::exists('denom', 'iddenom')->where('allow_do', true)],
            'penerima' => ['required', Rule::exists('kategori_bo', 'namabo')->where('idtap', $request->idtap)],
            'qty' => 'required|integer|min:1', 'tgl' => 'required|date|before_or_equal:today',
            'nomordo' => 'required|string|max:255', 'week' => 'required|string|max:25',
            'idtap' => 'required|exists:kodetap,idtap',
        ]);
        abort_unless(session('idtap') === 'SBP_DUMAI' || $request->idtap === session('idtap'), 403);
    }

    private function incrementTapStock(string $idtap, string $iddenom, int $qty): void
    {
        $updated = DB::table('stockawaltap')->where('idtap', $idtap)->where('iddenom', $iddenom)->increment('stock', $qty);
        if ($updated === 0) {
            DB::table('stockawaltap')->insert(['idtap' => $idtap, 'iddenom' => $iddenom, 'stock' => $qty]);
        }
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
            ->whereBetween('m.tgl', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->groupBy('m.tgl', 'm.nomor_do', 'm.sn', 'm.idtappenerima', 'd.denom');

        TapFilter::apply($query, 'm.idtappenerima');

        $filename = 'DO_MASUK_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new DOExport($query->get()), $filename);
    }
}
