<?php

namespace App\Http\Controllers;

use App\Helpers\TapFilter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\KeluarSFExport;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\AuditLogger;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

    class SfkeluarController extends Controller
{
    /* =========================
       VIEW
    ========================= */
    public function index()
    {
        return view('sf-keluar');
    }

    /* =========================
       SERVER SIDE DATATABLE
    ========================= */
    public function data(Request $request)
{
    $idtap     = session('idtap');
    $daterange = $request->daterange;

    $query = DB::table('keluarsf as f')
        ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
        ->join('idsf as i', 'i.idsf', '=', 'f.idsf')
        ->select(
            'f.idkeluar',
            'f.tgl',
            'd.denom',
            'f.qty',
            'f.idtap',
            'i.namasf',
            'f.tambahanket'
        );

    TapFilter::apply($query, 'f.idtap');

    // 🔥 DEFAULT: BULAN BERJALAN
    if ($daterange) {
        [$start, $end] = explode(' - ', $daterange);
    } else {
        $start = now()->startOfMonth()->toDateString();
        $end   = now()->endOfMonth()->toDateString();
    }

    $query->whereBetween('f.tgl', [
        $start . ' 00:00:00',
        $end   . ' 23:59:59'
    ]);

    return DataTables::of($query)
        ->editColumn('qty', fn ($r) => number_format($r->qty))
        ->addColumn('action', function ($row) {
            $btnEdit = '
                <a href="'.route('sf-keluar.edit', $row->idkeluar).'"
                   class="btn btn-link text-primary p-0 action-edit-icon"
                   title="Edit" aria-label="Edit">
                    <i class="fas fa-edit fa-lg" aria-hidden="true"></i>
                </a>';

            if (!auth()->user()->hasClusterAdminAccess()) {
                return $btnEdit;
            }

            return '
                <div class="d-flex align-items-center gap-2">
                    ' . $btnEdit . '
                    <form action="'.url('sf-keluar/'.$row->idkeluar).'"
                        method="POST"
                        class="form-delete d-inline">
                        '.csrf_field().'
                        <button type="submit" class="btn btn-link text-danger p-1" title="Delete">
                            <i class="fas fa-trash-alt fa-lg"></i>
                        </button>
                    </form>
                </div>
            ';
        })
        ->rawColumns(['action'])
        ->make(true);
}


    
    /* =========================
       FORM
    ========================= */
    public function formkeluarsf()
    {
        $idtap = session('idtap');

        $denom = DB::table('denom')->get();

        $data = DB::table('kodetap')
            ->when($idtap !== 'SBP_DUMAI', function ($q) use ($idtap) {
                $q->where('idtap', $idtap);
            })
            ->get();

        return view('form/form-sfkeluar', compact('data', 'idtap', 'denom'));
    }

    /* =========================
       AJAX GET SF
    ========================= */
    public function getSf(Request $request)
    {
        $idtapsession = session('idtap');
        // Jika admin, bebas pakai idtap apa aja. 
        // Jika user TAP, prioritaskan idtap request (untuk edit) atau fallback ke session.
        $idtap = ($idtapsession === 'SBP_DUMAI') ? ($request->idtap ?: $idtapsession) : ($request->idtap ?: $idtapsession);

        $sfData = DB::table('idsf')
            ->where('idtap', $idtap)
            ->orderBy('namasf')
            ->get();

        return response()->json($sfData);
    }

    /* =========================
       PROSES KELUAR SF
    ========================= */
    public function keluarsfproses(Request $request)
{
    $items = $request->input('items');
    if (!is_array($items)) {
        $request->merge(['items' => [[
            'iddenom' => $request->iddenom,
            'qty' => $request->qty,
            'tambahanket' => $request->tambahanket,
        ]]]);
    }

    $validated = $request->validate([
        'tgl' => 'required|date|before_or_equal:today',
        'idtap' => 'required|exists:kodetap,idtap',
        'idsf' => 'required|exists:idsf,idsf',
        'items' => 'required|array|min:1',
        'items.*.iddenom' => 'required|exists:denom,iddenom',
        'items.*.qty' => 'required|integer|min:1',
        'items.*.tambahanket' => 'nullable|string|max:255',
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

            $historicalStock = $this->sfStockAtDate(
                $validated['idsf'],
                $iddenom,
                $validated['tgl'],
                (int) $stockSf
            );

            $availableStock = min((int) $stockSf, $historicalStock);
            if ($availableStock < $requestedQty) {
                $denomName = DB::table('denom')
                    ->where('iddenom', $iddenom)
                    ->value('denom') ?? $iddenom;

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => "Total qty {$denomName} tidak mencukupi. Diminta: {$requestedQty}, tersedia pada tanggal {$validated['tgl']}: {$historicalStock}, stok saat ini: {$stockSf}.",
                ]);
            }
        }

        foreach ($validated['items'] as $item) {
            DB::table('stockawalsf')
                ->where('idsf', $validated['idsf'])
                ->where('iddenom', $item['iddenom'])
                ->decrement('stock', $item['qty']);

            $newId = DB::table('keluarsf')->insertGetId([
                'idtap' => $validated['idtap'],
                'idsf' => $validated['idsf'],
                'iddenom' => $item['iddenom'],
                'qty' => $item['qty'],
                'tgl' => $validated['tgl'],
                'tambahanket' => $item['tambahanket'] ?? null,
            ]);

            AuditLogger::log('INSERT', 'Stok Keluar SF (Bulk)', $newId, null, $item + [
                'idtap' => $validated['idtap'],
                'idsf' => $validated['idsf'],
                'tgl' => $validated['tgl'],
            ]);
        }
    });

    return redirect('sf-keluar')->with('success', count($validated['items']) . ' denom berhasil ditambahkan');
}


    /* =========================
   AJAX GET STOCK
========================= */
    public function getStock(Request $request)
    {
        $request->validate([
            'iddenom' => 'required',
            'idsf'    => 'required',
        ]);

        $stock = DB::table('stockawalsf')
            ->where('iddenom', $request->iddenom)
            ->where('idsf', $request->idsf)
            ->value('stock');

        return response()->json([
            'stock' => (int) ($stock ?? 0)
        ]);
    }

    /* =========================
       AJAX GET ALL STOCK
    ========================= */
    public function getAllStock(Request $request)
    {
        $request->validate([
            'idsf' => 'required',
        ]);

        $stocks = DB::table('stockawalsf')
            ->where('idsf', $request->idsf)
            ->pluck('stock', 'iddenom');

        return response()->json($stocks);
    }


   public function delete($idkeluar)
{
    if (!auth()->user()->hasClusterAdminAccess()) {
        return redirect('sf-keluar')->with('error', 'Akses ditolak. Hanya Admin Cluster yang boleh menghapus data.');
    }

    DB::transaction(function () use ($idkeluar) {

        $data = DB::table('keluarsf')
            ->where('idkeluar', $idkeluar)
            ->lockForUpdate()
            ->first();

        if (!$data) {
            throw new \Exception('Data tidak ditemukan');
        }

        $stockSf = DB::table('stockawalsf')
            ->where('idsf', $data->idsf)
            ->where('iddenom', $data->iddenom)
            ->lockForUpdate()
            ->value('stock');

        DB::table('stockawalsf')
            ->where('idsf', $data->idsf)
            ->where('iddenom', $data->iddenom)
            ->increment('stock', $data->qty);

        DB::table('keluarsf')
            ->where('idkeluar', $idkeluar)
            ->delete();

        // 📝 LOG
        AuditLogger::log('DELETE', 'Stok Keluar SF', $idkeluar, (array)$data);
    });

    return redirect('sf-keluar')->with('success', 'Data Berhasil Dihapus!');
}

/* =========================
   BULK DELETE
========================= */
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
            foreach ($ids as $idkeluar) {
                // 🔒 LOCK DATA KELUAR
                $data = DB::table('keluarsf')
                    ->where('idkeluar', $idkeluar)
                    ->lockForUpdate()
                    ->first();

                if (!$data) continue;

                // 🔒 LOCK STOK SF
                $stockSf = DB::table('stockawalsf')
                    ->where('idsf', $data->idsf)
                    ->where('iddenom', $data->iddenom)
                    ->lockForUpdate()
                    ->value('stock');

                // BALIKKAN STOK
                DB::table('stockawalsf')
                    ->where('idsf', $data->idsf)
                    ->where('iddenom', $data->iddenom)
                    ->increment('stock', $data->qty);

                DB::table('keluarsf')
                    ->where('idkeluar', $idkeluar)
                    ->delete();

                // 📝 LOG
                AuditLogger::log('DELETE (BULK)', 'Stok Keluar SF', $idkeluar, (array)$data);
            }
        });

        return response()->json(['success' => true, 'message' => count($ids) . ' data berhasil dihapus']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()]);
    }
}

/* =========================
   FORM EDIT
========================= */
public function editSfKeluar($id)
{
    $idtap = session('idtap');
    
    // Lock row
    $edit = DB::table('keluarsf')->where('idkeluar', $id)->first();
    
    if (!$edit) {
        return redirect('sf-keluar')->with('error', 'Data tidak ditemukan');
    }

    if (!auth()->user()->hasClusterAdminAccess() && $edit->idtap !== $idtap) {
        abort(403, 'Transaksi ini bukan milik TAP Anda.');
    }

    $denom = DB::table('denom')->get();
    $data  = DB::table('kodetap')
        ->when($idtap !== 'SBP_DUMAI', function ($q) use ($idtap) {
            $q->where('idtap', $idtap);
        })
        ->get();

    $selectedSf = DB::table('idsf')->where('idsf', $edit->idsf)->first();

    return view('form/form-edit-sfkeluar', compact('edit', 'data', 'idtap', 'denom', 'selectedSf'));
}

/* =========================
   PROSES UPDATE (JALUR BENAR)
========================= */
public function updateSfKeluar(Request $request, $id)
{
    $allowedTapRule = auth()->user()->hasClusterAdminAccess()
        ? Rule::exists('kodetap', 'idtap')
        : Rule::in([session('idtap')]);

    $validated = $request->validate([
        'tgl' => 'required|date|before_or_equal:today',
        'idtap' => ['required', $allowedTapRule],
        'idsf' => [
            'required',
            Rule::exists('idsf', 'idsf')->where(fn ($query) => $query->where('idtap', $request->idtap)),
        ],
        'iddenom' => 'required|exists:denom,iddenom',
        'qty' => 'required|integer|min:1',
        'tambahanket' => 'nullable|string|max:255',
    ]);

    DB::transaction(function () use ($validated, $id) {
        
        $newIdtap      = $validated['idtap'];
        $newIdsf       = $validated['idsf'];
        $newIddenom    = $validated['iddenom'];
        $newQty        = (int) $validated['qty'];
        $newTgl        = $validated['tgl'];
        $newKet        = $validated['tambahanket'] ?? null;

        // 1. Lock Data Lama
        $old = DB::table('keluarsf')->where('idkeluar', $id)->lockForUpdate()->first();
        if (!$old) throw new \Exception('Data transaksi tidak ditemukan');

        // 2. Kembalikan Stok Lama
        DB::table('stockawalsf')
            ->where('idsf', $old->idsf)
            ->where('iddenom', $old->iddenom)
            ->increment('stock', $old->qty);

        // 3. Potong Stok Baru & Lock
        $currentStock = DB::table('stockawalsf')
            ->where('idsf', $newIdsf)
            ->where('iddenom', $newIddenom)
            ->lockForUpdate()
            ->value('stock');

        $historicalStock = $this->sfStockAtDate(
            $newIdsf,
            $newIddenom,
            $newTgl,
            (int) $currentStock,
            (int) $old->idkeluar
        );

        $availableStock = min((int) $currentStock, $historicalStock);
        if ($availableStock < $newQty) {
            throw ValidationException::withMessages([
                'qty' => "Quantity tidak mencukupi. Tersedia pada tanggal {$newTgl}: {$historicalStock}, stok saat ini: " . (int) $currentStock . '.',
            ]);
        }

        DB::table('stockawalsf')
            ->where('idsf', $newIdsf)
            ->where('iddenom', $newIddenom)
            ->decrement('stock', $newQty);

            // 4. Update Transaksi
            DB::table('keluarsf')
                ->where('idkeluar', $id)
                ->update([
                    'idtap'       => $newIdtap,
                    'idsf'        => $newIdsf,
                    'iddenom'     => $newIddenom,
                    'qty'         => $newQty,
                    'tgl'         => $newTgl,
                    'tambahanket' => $newKet
                ]);

            // 📝 LOG
            AuditLogger::log('UPDATE', 'Stok Keluar SF', $id, (array)$old, $validated);
    });

    return redirect('sf-keluar')->with('success', 'Data Berhasil Diupdate!');
}

    /**
     * Saldo SF pada akhir tanggal transaksi. Saldo materialized saat ini
     * dikembalikan ke masa lalu dengan membalik seluruh pergerakan setelah
     * tanggal tersebut, sehingga transaksi backdate tidak dapat memakai stok
     * yang baru diterima pada tanggal berikutnya.
     */
    private function sfStockAtDate(
        string $idsf,
        string $iddenom,
        string $date,
        int $currentStock,
        ?int $excludedKeluarId = null
    ): int {
        $futureIn = (int) DB::table('masuksf')
            ->where('idsf', $idsf)
            ->where('iddenom', $iddenom)
            ->whereDate('tgl', '>', $date)
            ->sum('qty');

        $futureIn += (int) DB::table('masuk')
            ->where('pengirim', 'DO')
            ->where('penerima', $idsf)
            ->where('iddenom', $iddenom)
            ->whereDate('tgl', '>', $date)
            ->sum('qty');

        $futureOut = (int) DB::table('retursf')
            ->where('idsf', $idsf)
            ->where('iddenom', $iddenom)
            ->whereDate('tgl', '>', $date)
            ->sum('qty');

        $futureOut += (int) DB::table('keluar')
            ->where('pengirim', $idsf)
            ->where('iddenom', $iddenom)
            ->where('status', 0)
            ->whereDate('tgl', '>', $date)
            ->sum('qty');

        $futureKeluar = DB::table('keluarsf')
            ->where('idsf', $idsf)
            ->where('iddenom', $iddenom)
            ->whereDate('tgl', '>', $date);
        if ($excludedKeluarId !== null) {
            $futureKeluar->where('idkeluar', '!=', $excludedKeluarId);
        }
        $futureOut += (int) $futureKeluar->sum('qty');

        $adjustmentDelta = (int) DB::table('logs')
            ->where('action', 'STOCK ADJUSTMENT')
            ->where('module', 'Stok Petugas')
            ->whereRaw("SUBSTRING_INDEX(record_id, ':', 1) = ?", [$idsf])
            ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(new_values, '$.iddenom')), JSON_UNQUOTE(JSON_EXTRACT(old_values, '$.iddenom'))) = ?", [$iddenom])
            ->whereDate('created_at', '>', $date)
            ->selectRaw("COALESCE(SUM(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(new_values, '$.stock')), '0') AS SIGNED) - CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(old_values, '$.stock')), '0') AS SIGNED)), 0) AS delta")
            ->value('delta');

        return $currentStock - $futureIn + $futureOut - $adjustmentDelta;
    }


    /* =========================
       EXPORT EXCEL
    ========================= */
    public function exportexcel(Request $request)
{
    $idtap     = session('idtap');
    $daterange = $request->daterange;

    $query = DB::table('keluarsf as f')
        ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
        ->join('idsf as i', 'f.idsf', '=', 'i.idsf')
        ->select(
            'f.tgl',
            'd.denom',
            DB::raw('SUM(f.qty) as qty'),
            'f.idtap',
            'i.namasf',
            'f.tambahanket'
        )
        ->groupBy(
            'f.tgl',
            'd.denom',
            'f.idtap',
            'i.namasf',
            'f.tambahanket'
        );

    /* ===============================
       FILTER TAP
    =============================== */
    TapFilter::apply($query, 'f.idtap');

    /* ===============================
       FILTER DATE RANGE (PRIORITY)
    =============================== */
    if ($daterange) {
        [$start, $end] = explode(' - ', $daterange);

        $query->whereBetween('f.tgl', [
            $start . ' 00:00:00',
            $end   . ' 23:59:59'
        ]);

        $filename = 'PENJUALAN_SF_' . $start . '_sd_' . $end . '.xlsx';

    } else {
        /* fallback lama (bulan berjalan) */
        $month = date('m');
        $year  = date('Y');

        $query->whereMonth('f.tgl', $month)
              ->whereYear('f.tgl', $year);

        $filename = "PENJUALAN_SF_{$year}_{$month}.xlsx";
    }

    $data = $query->get();

    return Excel::download(
        new KeluarSFExport($data),
        $filename
    );
}
}
