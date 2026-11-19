<?php

namespace App\Http\Controllers;

use App\Helpers\TapFilter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasukSFExport;
use App\Helpers\AuditLogger;

class SfmasukController extends Controller
{
    /* =========================
       VIEW
    ========================= */
    public function index()
    {
        return view('sf-masuk');
    }

    /* =========================
       DATATABLE SERVER SIDE
    ========================= */
    public function data(Request $request)
{
    $idtap = session('idtap');

    // ===== DATE RANGE (AMAN) =====
    if ($request->filled('daterange')) {
        [$start, $end] = explode(' - ', $request->daterange);
    } else {
        $start = now()->startOfMonth()->format('Y-m-d');
        $end   = now()->endOfMonth()->format('Y-m-d');
    }

    $query = DB::table('masuksf as f')
        ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
        ->join('idsf as i', 'i.idsf', '=', 'f.idsf')
        ->select(
            'f.idmasuk',
            'f.tgl',
            'd.denom',
            'f.qty',
            'f.idtap',
            'i.namasf',
            DB::raw("SUBSTRING_INDEX(f.sn, '-', 1) AS sn_awal"),
            DB::raw("CASE WHEN LOCATE('-', f.sn) > 0 THEN SUBSTRING_INDEX(f.sn, '-', -1) ELSE f.sn END AS sn_akhir")
        )
        ->whereBetween('f.tgl', [
            $start . ' 00:00:00',
            $end   . ' 23:59:59'
        ]);

    // ===== FILTER TAP (ANTI NULL) =====
    TapFilter::apply($query, 'f.idtap');

    return DataTables::of($query)
        ->editColumn('tgl', fn ($r) =>
            Carbon::parse($r->tgl)->format('d-m-Y')
        )
        ->editColumn('qty', fn ($r) =>
            number_format($r->qty)
        )
        ->addColumn('action', function ($row) {
            $btnEdit = '
                <a href="'.url('sf-masuk/edit/'.$row->idmasuk).'" class="btn btn-link text-primary p-0 action-edit-icon" title="Edit" aria-label="Edit">
                    <i class="fas fa-edit fa-lg" aria-hidden="true"></i>
                </a>
            ';

            if (!auth()->user()->hasClusterAdminAccess()) {
                return '<div class="d-flex align-items-center justify-content-center">' . $btnEdit . '</div>';
            }

            $btnDelete = '
                <form action="'.url('sf-masuk/'.$row->idmasuk).'" 
                    method="POST" 
                    class="form-delete d-inline">
                    '.csrf_field().'
                    <button type="submit" class="btn btn-link text-danger p-0" title="Delete" style="text-decoration:none;">
                        <i class="fas fa-trash-alt fa-lg"></i>
                    </button>
                </form>
            ';

            return '<div class="d-flex align-items-center justify-content-center">' . $btnEdit . $btnDelete . '</div>';
        })

        ->rawColumns(['action'])
        ->orderColumn('tgl', 'f.tgl $1')
        ->make(true);
}


    
    public function formmasuksf(){

        $idtap = session('idtap');

        $denom = DB::table('denom')
                ->select('*')
                ->get();
        $bulkDenomMetadata = $denom->map(fn ($row) => [
            'id' => $row->iddenom,
            'name' => $row->denom,
            'product' => $row->product,
            'product_group' => $row->product_group,
            'kategori_inject' => $row->kategori_inject,
            'group_name' => $row->group_name,
        ])->values();
        

        if($idtap == 'SBP_DUMAI'){

            $data = DB::table('kodetap')
            ->select('*')
            ->get();

        }else{

            $data = DB::table('kodetap')
            ->select('*')
            ->where('idtap', $idtap)
            ->get();
        }
       
        return view('form/form-sfmasuk',compact('data','idtap','denom','bulkDenomMetadata'));

    }

    public function getSf(Request $request)
    {
        $idtapsession = session('idtap');
        // Prioritaskan idtap dari request (untuk edit) atau fallback ke session
        $idtap = $request->idtap ?: $idtapsession;

        $sf = \App\Support\SfVisibility::visible(DB::table('idsf'))
            ->where('idtap', $idtap)
            ->orderBy('namasf')
            ->get();

        return response()->json($sf);
    }


    /* =========================
       AJAX GET STOCK
    ========================= */
    public function getStockTap(Request $request)
    {
        $stock = DB::table('stockawaltap')
            ->where('idtap', $request->idtap)
            ->where('iddenom', $request->iddenom)
            ->value('stock') ?? 0;

        return response()->json([
            'stock' => $stock
        ]);
    }

    public function getAllStockTap(Request $request)
    {
        $request->validate([
            'idtap' => 'required',
        ]);

        $stocks = DB::table('stockawaltap')
            ->where('idtap', $request->idtap)
            ->pluck('stock', 'iddenom');

        return response()->json($stocks);
    }

// Pindahkan stok TAP ke saldo SF. Jurnal masuk SF dan perubahan kedua saldo ditulis dalam transaksi
// yang sama.
public function masuksfproses(Request $request)
{
    $items = $request->input('items');
    if (!is_array($items)) {
        $request->merge(['items' => [[
            'iddenom' => $request->iddenom,
            'qty' => $request->qty,
            'sn_start' => $request->sn_start ?: $request->sn,
            'sn_end' => $request->sn_end,
        ]]]);
    }

    $incomingItems = $request->input('items');
    foreach ($incomingItems as $key => $item) {
        if (! is_array($item)) continue;
        $incomingItems[$key]['serial_mode'] = $item['serial_mode'] ?? 'range';
    }
    $request->merge(['items' => $incomingItems]);

    $validated = $request->validate([
        'tgl' => 'required|date|before_or_equal:today',
        'idtap' => 'required|exists:kodetap,idtap',
        'idsf' => 'required|exists:idsf,idsf',
        'items' => 'required|array|min:1',
        'items.*.iddenom' => 'required|exists:denom,iddenom',
        'items.*.qty' => 'nullable|integer|min:1',
        'items.*.serial_mode' => 'required|in:range',
        'items.*.sn_start' => 'required|string|max:24',
        'items.*.sn_end' => 'nullable|string|max:24',
    ]);

    $denomMetadata = DB::table('denom')
        ->whereIn('iddenom', collect($validated['items'])->pluck('iddenom')->unique())
        ->get(['iddenom', 'product', 'product_group', 'kategori_inject', 'group_name'])
        ->keyBy('iddenom');
    foreach ($validated['items'] as $index => &$item) {
        $denom = $denomMetadata->get($item['iddenom']);
        $denomId = strtoupper(trim((string) ($denom->iddenom ?? '')));
        $masterProduct = strtoupper(trim((string) ($denom->product ?? '')));
        $isStarterPack = in_array($masterProduct, ['PV', 'SA'], true)
            ? $masterProduct === 'SA'
            : (in_array(strtoupper(trim((string) ($denom->product_group ?? ''))), ['SA', 'SP'], true)
                || strtoupper(trim((string) ($denom->kategori_inject ?? ''))) === 'SA'
                || strtoupper(trim((string) ($denom->group_name ?? ''))) === 'SA'
                || str_starts_with($denomId, 'SP'));
        $start = (string) ($item['sn_start'] ?? '');
        $end = (string) ($item['sn_end'] ?? '');
        $pattern = $isStarterPack ? '/^0\\d{15}$/' : '/^\\d{12}$/';
        if (! preg_match($pattern, $start) || ($end !== '' && ! preg_match($pattern, $end))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                "items.{$index}.sn_start" => $isStarterPack
                    ? 'SN perdana wajib 16 digit dan diawali 0.'
                    : 'SN voucher wajib tepat 12 digit.',
            ]);
        }

        if ($end === '') $end = $start;
        $rangeQty = (int) ltrim($end, '0') - (int) ltrim($start, '0') + 1;
        if ($rangeQty < 1 || $rangeQty > 2_000_000) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                "items.{$index}.sn_end" => 'SN akhir harus sama dengan atau lebih besar dari SN awal; maksimal 2.000.000 SN.',
            ]);
        }
        if (isset($item['qty']) && (int) $item['qty'] !== $rangeQty) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                "items.{$index}.sn_end" => 'Qty harus sama dengan jumlah SN di dalam rentang; periksa kembali SN akhir.',
            ]);
        }

        $item['sn_start'] = $start;
        $item['sn_end'] = $end;
        $item['qty'] = $rangeQty;
        $item['sn'] = $start === $end ? $start : "{$start}-{$end}";
    }
    unset($item);

    for ($leftIndex = 0; $leftIndex < count($validated['items']); $leftIndex++) {
        for ($rightIndex = $leftIndex + 1; $rightIndex < count($validated['items']); $rightIndex++) {
            $left = $validated['items'][$leftIndex];
            $right = $validated['items'][$rightIndex];
            $overlaps = strlen($left['sn_start']) === strlen($right['sn_start'])
                && $left['sn_start'] <= $right['sn_end']
                && $left['sn_end'] >= $right['sn_start'];
            if ($overlaps) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => "Rentang SN {$left['sn_start']}–{$left['sn_end']} bertabrakan dengan {$right['sn_start']}–{$right['sn_end']}. Pastikan setiap SN hanya diinput satu kali.",
                ]);
            }
        }
    }
    usort($validated['items'], fn ($a, $b) => strcmp($a['iddenom'], $b['iddenom']));

    if (session('idtap') !== 'SBP_DUMAI' && $validated['idtap'] !== session('idtap')) {
        abort(403);
    }
    abort_unless(
        \App\Support\SfVisibility::visible(DB::table('idsf'))->where('idsf', $validated['idsf'])->where('idtap', $validated['idtap'])->exists(),
        422,
        'Petugas disembunyikan atau tidak sesuai dengan TAP.'
    );

    DB::transaction(function () use ($validated) {
        $requestedByDenom = collect($validated['items'])
            ->groupBy('iddenom')
            ->map(fn ($items) => $items->sum('qty'));

        foreach ($requestedByDenom as $iddenom => $requestedQty) {
            $stockTap = DB::table('stockawaltap')
                ->where('idtap', $validated['idtap'])
                ->where('iddenom', $iddenom)
                ->lockForUpdate()
                ->value('stock') ?? 0;

            if ($stockTap < $requestedQty) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => "Total qty {$iddenom} tidak mencukupi. Diminta: {$requestedQty}, tersedia: {$stockTap}.",
                ]);
            }
        }

        foreach ($validated['items'] as $item) {
            DB::table('stockawaltap')
                ->where('idtap', $validated['idtap'])
                ->where('iddenom', $item['iddenom'])
                ->decrement('stock', $item['qty']);

            DB::table('stockawalsf')->insertOrIgnore([
                'idsf' => $validated['idsf'],
                'iddenom' => $item['iddenom'],
                'stock' => 0,
            ]);

            DB::table('stockawalsf')
                ->where('idsf', $validated['idsf'])
                ->where('iddenom', $item['iddenom'])
                ->increment('stock', $item['qty']);

            $newId = DB::table('masuksf')->insertGetId([
                'idtap' => $validated['idtap'],
                'idsf' => $validated['idsf'],
                'iddenom' => $item['iddenom'],
                'qty' => $item['qty'],
                'sn' => $item['sn'],
                'tgl' => $validated['tgl'],
            ]);

            AuditLogger::log('INSERT', 'Stok Masuk SF (Bulk)', $newId, null, $item + [
                'idtap' => $validated['idtap'],
                'idsf' => $validated['idsf'],
                'tgl' => $validated['tgl'],
            ]);
        }
    });

    return redirect('sf-masuk')->with('success', count($validated['items']) . ' denom berhasil ditambahkan');
}


    /* =========================
       DELETE
    ========================= */

public function delete(Request $request, $idmasuk)
{
    if (!auth()->user()->hasClusterAdminAccess()) {
        return redirect('sf-masuk')->with('error', 'Akses ditolak. Hanya Admin Cluster yang boleh menghapus data.');
    }

    try {
        DB::transaction(function () use ($idmasuk) {

            $data = DB::table('masuksf')
                ->where('idmasuk', $idmasuk)
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

            if ($stockSf < $data->qty) {
                throw new \Exception('Stok SF tidak mencukupi');
            }

            DB::table('stockawaltap')
                ->where('idtap', $data->idtap)
                ->where('iddenom', $data->iddenom)
                ->increment('stock', $data->qty);

            DB::table('stockawalsf')
                ->where('idsf', $data->idsf)
                ->where('iddenom', $data->iddenom)
                ->decrement('stock', $data->qty);

            // 📝 LOG
            AuditLogger::log('DELETE', 'Stok Masuk SF', $idmasuk, (array)$data);

            DB::table('masuksf')->where('idmasuk', $idmasuk)->delete();
        });

        return redirect('sf-masuk')
            ->with('success', 'Data berhasil dihapus');

    } catch (\Exception $e) {

        return redirect('sf-masuk')
            ->with('error', $e->getMessage());
    }
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
            foreach ($ids as $idmasuk) {
                // 🔒 LOCK DATA MASUK
                $data = DB::table('masuksf as r')
                    ->join('denom as d', 'r.iddenom', '=', 'd.iddenom')
                    ->select('r.*', 'd.denom')
                    ->where('idmasuk', $idmasuk)
                    ->lockForUpdate()
                    ->first();

                if (!$data) continue;

                // 🔒 LOCK STOK SF
                $stockSf = DB::table('stockawalsf')
                    ->where('idsf', $data->idsf)
                    ->where('iddenom', $data->iddenom)
                    ->lockForUpdate()
                    ->value('stock');

                // Cek kecukupan stok di SF sebelum ditarik balik
                if ($stockSf < $data->qty) {
                    throw new \Exception('Stok SF untuk ' . $data->denom . ' tidak mencukupi untuk penghapusan ini');
                }

                // BALIKKAN STOK KE TAP
                DB::table('stockawaltap')
                    ->where('idtap', $data->idtap)
                    ->where('iddenom', $data->iddenom)
                    ->increment('stock', $data->qty);

                // POTONG STOK DARI SF
                DB::table('stockawalsf')
                    ->where('idsf', $data->idsf)
                    ->where('iddenom', $data->iddenom)
                    ->decrement('stock', $data->qty);

                // 📝 LOG
                AuditLogger::log('DELETE (BULK)', 'Stok Masuk SF', $idmasuk, (array)$data);

                DB::table('masuksf')->where('idmasuk', $idmasuk)->delete();
            }
        });

        return response()->json(['success' => true, 'message' => count($ids) . ' data berhasil dihapus']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()]);
    }
}

    /* =========================
       EXPORT EXCEL (IKUT RANGE)
    ========================= */
    public function exportexcel(Request $request)
    {
        $idtap     = session('idtap');
        $daterange = $request->daterange;

        $query = DB::table('masuksf as f')
            ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
            ->join('idsf as i', 'f.idsf', '=', 'i.idsf')
            ->select(
                'f.tgl',
                'd.denom',
                'f.qty',
                'f.idtap',
                'i.namasf',
                'f.sn'
            );

        TapFilter::apply($query, 'f.idtap');

        if ($daterange) {
            [$start, $end] = explode(' - ', $daterange);
            $query->whereBetween('f.tgl', [
                $start.' 00:00:00',
                $end.' 23:59:59'
            ]);

            $filename = "MASUK_SF_{$start}_sd_{$end}.xlsx";
        } else {
            $filename = "MASUK_SF_".now()->format('Ym').".xlsx";
        }

        return Excel::download(
            new MasukSFExport($query->get()),
            $filename
        );
    }

    /* =========================
       EDIT
    ========================= */
    public function editSfMasuk($id)
    {
        $idtap_session = session('idtap');

        $data = DB::table('masuksf')
            ->where('idmasuk', $id)
            ->first();

        if (!$data) {
            return redirect('sf-masuk')->with('error', 'Data tidak ditemukan');
        }

        $denom = DB::table('denom')->get();
        // Get list of SF under the same TAP
        $idsf  = DB::table('idsf')->where('idtap', $data->idtap)->get();

        if ($idtap_session == 'SBP_DUMAI') {
            $kodetap = DB::table('kodetap')->get();
        } else {
            $kodetap = DB::table('kodetap')->where('idtap', $data->idtap)->get();
        }

        return view('form/form-edit-sfmasuk', compact('data', 'denom', 'idsf', 'kodetap'));
    }

    public function updateSfMasuk(Request $request, $id)
    {
        try {
            DB::transaction(function () use ($request, $id) {
                // 1. Ambil data LAMA & LOCK
                $oldData = DB::table('masuksf')
                    ->where('idmasuk', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$oldData) throw new \Exception('Data tidak ditemukan');

                // 2. ROLLBACK STOK LAMA (SF -> TAP)
                $stockSf = DB::table('stockawalsf')
                    ->where('idsf', $oldData->idsf)
                    ->where('iddenom', $oldData->iddenom)
                    ->lockForUpdate()
                    ->value('stock');

                if ($stockSf < $oldData->qty) {
                    throw new \Exception('Gagal: Stok di SF sudah berkurang/digunakan, data tidak bisa diubah.');
                }

                DB::table('stockawalsf')
                    ->where('idsf', $oldData->idsf)
                    ->where('iddenom', $oldData->iddenom)
                    ->decrement('stock', $oldData->qty);

                DB::table('stockawaltap')
                    ->where('idtap', $oldData->idtap)
                    ->where('iddenom', $oldData->iddenom)
                    ->increment('stock', $oldData->qty);

                // 3. APPLY STOK BARU (TAP -> SF)
                $newQty     = $request->qty;
                $newIdtap   = $request->idtap;
                $newIdsf    = $request->idsf;
                $newIddenom = $request->iddenom;

                $stockTapNew = DB::table('stockawaltap')
                    ->where('idtap', $newIdtap)
                    ->where('iddenom', $newIddenom)
                    ->lockForUpdate()
                    ->value('stock');

                if ($stockTapNew < $newQty) {
                    throw new \Exception('Gagal: Stok TAP tidak mencukupi untuk kuantitas baru.');
                }

                DB::table('stockawaltap')
                    ->where('idtap', $newIdtap)
                    ->where('iddenom', $newIddenom)
                    ->decrement('stock', $newQty);

                DB::table('stockawalsf')
                    ->where('idsf', $newIdsf)
                    ->where('iddenom', $newIddenom)
                    ->increment('stock', $newQty);

                // 4. UPDATE RECORD
                DB::table('masuksf')->where('idmasuk', $id)->update([
                    'idtap'   => $newIdtap,
                    'idsf'    => $newIdsf,
                    'iddenom' => $newIddenom,
                    'qty'     => $newQty,
                    'sn'      => $request->sn,
                    'tgl'     => $request->tgl
                ]);

                // 📝 LOG
                AuditLogger::log('UPDATE', 'Stok Masuk SF', $id, (array)$oldData, $request->all());
            });

            return redirect('sf-masuk')->with('success', 'Data berhasil diperbarui');

        } catch (\Exception $e) {
            return redirect('sf-masuk')->with('error', $e->getMessage());
        }
    }
}
