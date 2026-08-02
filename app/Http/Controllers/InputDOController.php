<?php

namespace App\Http\Controllers;

use App\Helpers\TapFilter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DOExport;
use App\Helpers\AuditLogger;

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

                if ($row->sf_stock < $row->qty) {
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
            $newId = DB::table('masuk')->insertGetId([
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

            // 📝 LOG
            AuditLogger::log('INSERT', 'Input DO', $newId, null, $request->all());

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
       EDIT DO
    ===================================================== */
    public function edit($id)
    {
        $idtap_session = session('idtap');
        $edit = DB::table('masuk')->where('idmasuk', $id)->first();
        
        if (!$edit) return redirect()->route('do.index')->with('error', 'Data tidak ditemukan');

        $data = DB::table('kategori_bo')
            ->when($idtap_session !== 'SBP_DUMAI', fn($q) => $q->where('idtap', $idtap_session))
            ->get();

        $denom = DB::table('denom')->get();

        return view('form.form-edit-DO', compact('edit', 'data', 'denom', 'idtap_session'));
    }

    public function update(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $oldData = DB::table('masuk')->where('idmasuk', $id)->lockForUpdate()->first();
            
            // 1. Rollback stok lama dari SF
            DB::table('stockawalsf')
                ->where('idsf', $oldData->penerima)
                ->where('iddenom', $oldData->iddenom)
                ->decrement('stock', $oldData->qty);

            // 2. Cek stok SF sekarang (untuk keamanan agar tidak minus setelah rollback)
            // Sebenarnya rollback di sini adalah mengurangi stok SF (karena DO adalah barang masuk).
            // Jadi kita harus pastikan stok SF mencukupi untuk dikurangi (dibatalkan masuknya).
            $currentSfStock = DB::table('stockawalsf')
                ->where('idsf', $oldData->penerima)
                ->where('iddenom', $oldData->iddenom)
                ->lockForUpdate()
                ->value('stock');

            if ($currentSfStock < 0) {
                 throw new \Exception('Gagal update: Stok SF akan menjadi negatif jika data lama dibatalkan');
            }

            // 3. Tambahkan stok baru ke SF baru
            DB::table('stockawalsf')
                ->where('idsf', $request->penerima)
                ->where('iddenom', $request->kategorisegel)
                ->increment('stock', $request->qty);

            // 4. Update record
            DB::table('masuk')->where('idmasuk', $id)->update([
                'iddenom'        => $request->kategorisegel,
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
