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

    public function pendingStor()
    {
        return view('pending-stor');
    }

    public function pendingStorData(Request $request)
    {
        $query = DB::table('keluarsf as f')
            ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
            ->join('idsf as i', 'i.idsf', '=', 'f.idsf')
            ->where('f.payment_status', 'pending')
            ->select('f.idkeluar', 'f.tgl', 'd.denom', 'f.qty', 'f.idtap', 'i.namasf', 'f.id_outlet', 'f.tambahanket', 'f.sn_awal', 'f.sn_akhir', 'f.serial_mode', 'f.payment_status');

        if (! $this->isGlobalAdmin()) TapFilter::apply($query, 'f.idtap');
        if ($request->filled('daterange')) {
            [$start, $end] = explode(' - ', $request->daterange, 2);
            $query->whereBetween('f.tgl', [$start.' 00:00:00', $end.' 23:59:59']);
        }

        return DataTables::of($query)
            ->editColumn('qty', fn ($row) => number_format($row->qty))
            ->addColumn('action', function ($row) {
                $qty = max(1, (int) $row->qty);
                $escape = fn ($value) => e((string) ($value ?? ''));
                $paid = '<button type="button" class="btn btn-sm btn-success js-open-pending-payment"'
                    .' data-action="'.$escape(route('pending-stor.paid', $row->idkeluar)).'"'
                    .' data-qty="'.$qty.'"'
                    .' data-sn-start="'.$escape($row->sn_awal).'"'
                    .' data-sn-end="'.$escape($row->sn_akhir).'"'
                    .' data-serial-mode="'.$escape($row->serial_mode ?? '').'">Sudah Bayar</button>';
                if (! auth()->user()->hasClusterAdminAccess()) return '<div class="d-flex flex-wrap align-items-center">'.$paid.'</div>';
                $delete = '<form method="POST" action="'.route('pending-stor.delete', $row->idkeluar).'" class="d-inline" onsubmit="return confirm(\'Hapus transaksi Pending Stor ini?\')">'.csrf_field().'<button class="btn btn-sm btn-danger" type="submit">Hapus</button></form>';
                return '<div class="d-flex flex-wrap align-items-center" style="gap:.4rem">'.$paid.$delete.'</div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function markPendingStorPaid(Request $request, int $id)
    {
        $validated = $request->validate([
            'paid_qty' => ['required', 'integer', 'min:1', 'max:2000000'],
            'paid_sn_start' => ['nullable', 'string', 'max:24'],
            'paid_sn_end' => ['nullable', 'string', 'max:24'],
        ]);

        try {
            $payment = DB::transaction(function () use ($validated, $id) {
                $sale = DB::table('keluarsf')->where('idkeluar', $id)->lockForUpdate()->first();
                abort_unless($sale && $sale->payment_status === 'pending', 404, 'Transaksi Pending Stor tidak ditemukan.');
                if (! $this->isGlobalAdmin()) abort_unless((string) $sale->idtap === (string) session('idtap'), 403);

                $paidQty = (int) $validated['paid_qty'];
                $saleQty = (int) $sale->qty;
                if ($paidQty > $saleQty) {
                    throw ValidationException::withMessages(['paid_qty' => 'Qty dibayar tidak boleh melebihi sisa konsinyasi ('.$saleQty.').']);
                }

                $stock = DB::table('stockawalsf')->where('idsf', $sale->idsf)->where('iddenom', $sale->iddenom)->lockForUpdate()->value('stock');
                if ($stock === null || (int) $stock < $paidQty) {
                    throw ValidationException::withMessages(['stock' => 'Stok SF tidak cukup untuk menandai transaksi ini sudah dibayar.']);
                }

                $paidAt = now('Asia/Jakarta')->toDateTimeString();
                DB::table('stockawalsf')->where('idsf', $sale->idsf)->where('iddenom', $sale->iddenom)->decrement('stock', $paidQty);

                if ($paidQty === $saleQty) {
                    if (! empty($sale->sn_awal) && ! empty($sale->sn_akhir) && ($sale->serial_mode ?? null) !== 'broken'
                        && ((string) ($validated['paid_sn_start'] ?? '') !== (string) $sale->sn_awal
                            || (string) ($validated['paid_sn_end'] ?? '') !== (string) $sale->sn_akhir)) {
                        throw ValidationException::withMessages(['paid_sn_start' => 'Untuk melunasi seluruh qty, SN awal dan akhir harus mencakup seluruh rentang konsinyasi.']);
                    }
                    DB::table('keluarsf')->where('idkeluar', $id)->update([
                        'payment_status' => 'paid',
                        'paid_at' => $paidAt,
                    ]);
                    AuditLogger::log('PAYMENT', 'Pending Stor SF', $id, (array) $sale, [
                        'payment_status' => 'paid', 'paid_qty' => $paidQty, 'paid_at' => $paidAt,
                    ]);

                    return ['paid' => $paidQty, 'remaining' => 0, 'paid_id' => $id];
                }

                $paidStart = null;
                $paidEnd = null;
                $hasRange = ! empty($sale->sn_awal) && ! empty($sale->sn_akhir);
                $isBroken = ($sale->serial_mode ?? null) === 'broken';
                $remainingRanges = [];

                if ($hasRange && ! $isBroken) {
                    $start = (string) $sale->sn_awal;
                    $end = (string) $sale->sn_akhir;
                    $paidStart = trim((string) ($validated['paid_sn_start'] ?? ''));
                    $paidEnd = trim((string) ($validated['paid_sn_end'] ?? ''));
                    $startDigits = ltrim($start, '0');
                    $endDigits = ltrim($end, '0');
                    $paidStartDigits = ltrim($paidStart, '0');
                    $paidEndDigits = ltrim($paidEnd, '0');
                    if (! ctype_digit($start) || ! ctype_digit($end) || ! ctype_digit($paidStart) || ! ctype_digit($paidEnd)
                        || strlen($start) !== strlen($end) || strlen($paidStart) !== strlen($start) || strlen($paidEnd) !== strlen($start)
                        || strlen($startDigits) > 15 || strlen($endDigits) > 15 || strlen($paidStartDigits) > 15 || strlen($paidEndDigits) > 15) {
                        throw ValidationException::withMessages(['paid_qty' => 'Rentang SN tidak valid; pembayaran sebagian dibatalkan agar nomor seri tidak salah.']);
                    }
                    $startNumber = (int) ($startDigits === '' ? '0' : $startDigits);
                    $endNumber = (int) ($endDigits === '' ? '0' : $endDigits);
                    $paidStartNumber = (int) ($paidStartDigits === '' ? '0' : $paidStartDigits);
                    $paidEndNumber = (int) ($paidEndDigits === '' ? '0' : $paidEndDigits);
                    if ($endNumber < $startNumber || ($endNumber - $startNumber + 1) !== $saleQty
                        || $paidStartNumber < $startNumber || $paidEndNumber > $endNumber || $paidEndNumber < $paidStartNumber
                        || ($paidEndNumber - $paidStartNumber + 1) !== $paidQty) {
                        throw ValidationException::withMessages(['paid_qty' => 'Jumlah SN pada rentang tidak cocok dengan qty konsinyasi; periksa data SN sebelum pembayaran sebagian.']);
                    }

                    if ($paidStartNumber > $startNumber) {
                        $remainingRanges[] = [
                            str_pad((string) $startNumber, strlen($start), '0', STR_PAD_LEFT),
                            str_pad((string) ($paidStartNumber - 1), strlen($start), '0', STR_PAD_LEFT),
                        ];
                    }
                    if ($paidEndNumber < $endNumber) {
                        $remainingRanges[] = [
                            str_pad((string) ($paidEndNumber + 1), strlen($start), '0', STR_PAD_LEFT),
                            str_pad((string) $endNumber, strlen($start), '0', STR_PAD_LEFT),
                        ];
                    }
                } elseif ($isBroken) {
                    $paidStart = $sale->sn_awal;
                    $paidEnd = $sale->sn_akhir;
                }

                $paidRecord = (array) $sale;
                unset($paidRecord['idkeluar']);
                $paidRecord['sn_awal'] = $paidStart;
                $paidRecord['sn_akhir'] = $paidEnd;
                $paidRecord['qty'] = $paidQty;
                $paidRecord['payment_status'] = 'paid';
                $paidRecord['paid_at'] = $paidAt;
                $paidId = DB::table('keluarsf')->insertGetId($paidRecord);

                $remainingQty = $saleQty - $paidQty;
                $firstRemaining = $remainingRanges[0] ?? null;
                DB::table('keluarsf')->where('idkeluar', $id)->update([
                    'qty' => $firstRemaining ? ((int) $firstRemaining[1] - (int) $firstRemaining[0] + 1) : ($hasRange && ! $isBroken ? 0 : $remainingQty),
                    'sn_awal' => $firstRemaining[0] ?? $sale->sn_awal,
                    'sn_akhir' => $firstRemaining[1] ?? $sale->sn_akhir,
                    'payment_status' => 'pending',
                    'paid_at' => null,
                ]);

                if ($hasRange && ! $isBroken && count($remainingRanges) === 2) {
                    $secondRemaining = $remainingRanges[1];
                    $secondRecord = (array) $sale;
                    unset($secondRecord['idkeluar']);
                    $secondRecord['qty'] = (int) $secondRemaining[1] - (int) $secondRemaining[0] + 1;
                    $secondRecord['sn_awal'] = $secondRemaining[0];
                    $secondRecord['sn_akhir'] = $secondRemaining[1];
                    $secondRecord['payment_status'] = 'pending';
                    $secondRecord['paid_at'] = null;
                    DB::table('keluarsf')->insert($secondRecord);
                }

                AuditLogger::log('PAYMENT', 'Pending Stor SF', $id, (array) $sale, [
                    'payment_status' => 'partial',
                    'paid_qty' => $paidQty,
                    'paid_record_id' => $paidId,
                    'paid_sn_awal' => $paidStart,
                    'paid_sn_akhir' => $paidEnd,
                    'remaining_qty' => $remainingQty,
                    'remaining_ranges' => $remainingRanges,
                    'paid_at' => $paidAt,
                ]);

                return ['paid' => $paidQty, 'remaining' => $remainingQty, 'paid_id' => $paidId];
            });
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        $message = 'Pembayaran '.$payment['paid'].' qty dicatat dan stok SF berhasil dipotong.';
        if ($payment['remaining'] > 0) $message .= ' Sisa '.$payment['remaining'].' qty tetap Pending Stor.';
        return back()->with('success', $message);
    }

    public function deletePendingStor(Request $request, int $id)
    {
        abort_unless(auth()->user()->hasClusterAdminAccess(), 403, 'Hanya Admin Super dan Admin Cluster yang boleh menghapus.');
        DB::transaction(function () use ($id) {
            $sale = DB::table('keluarsf')->where('idkeluar', $id)->lockForUpdate()->first();
            abort_unless($sale && $sale->payment_status === 'pending', 404, 'Transaksi Pending Stor tidak ditemukan.');
            if (! $this->isGlobalAdmin()) abort_unless((string) $sale->idtap === (string) session('idtap'), 403);
            DB::table('keluarsf')->where('idkeluar', $id)->delete();
            AuditLogger::log('DELETE', 'Pending Stor SF', $id, (array) $sale);
        });

        return back()->with('success', 'Transaksi Pending Stor dihapus.');
    }

    /* =========================
       SERVER SIDE DATATABLE
    ========================= */
    public function data(Request $request)
{
    $idtap     = session('idtap');
    $daterange = $request->daterange;

    // Keep the view and export on the same posted-journal-first sales source.
    $journal = \App\Support\SalesSource::legacy()->select([
        'idkeluar', 'tgl', 'iddenom', 'qty', 'idtap', 'idsf', 'id_outlet', 'tambahanket', 'sn_awal', 'sn_akhir', 'payment_status', 'paid_at',
    ]);
    $unpostedMobile = \App\Support\SalesSource::mobile()->select([
        DB::raw('-id AS idkeluar'), 'tgl', 'iddenom', 'qty', 'idtap',
        DB::raw('COALESCE(owner_idsf,idsf) AS idsf'), 'id_outlet',
        DB::raw("'Mobile approved' AS tambahanket"),
        DB::raw("(SELECT GROUP_CONCAT(serial_start ORDER BY id SEPARATOR ', ') FROM mobile_sales_serials WHERE mobile_penjualan_id = mobile_penjualan.id AND mobile_sales_serials.iddenom = mobile_penjualan.iddenom) AS sn_awal"),
        DB::raw("(SELECT GROUP_CONCAT(serial_end ORDER BY id SEPARATOR ', ') FROM mobile_sales_serials WHERE mobile_penjualan_id = mobile_penjualan.id AND mobile_sales_serials.iddenom = mobile_penjualan.iddenom) AS sn_akhir"),
        DB::raw("'paid' AS payment_status"), DB::raw('NULL AS paid_at'),
    ]);
    $query = DB::query()->fromSub($journal->unionAll($unpostedMobile), 'f')
        ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
        ->join('idsf as i', 'i.idsf', '=', 'f.idsf')
        ->select(
            'f.idkeluar',
            'f.tgl',
            'd.denom',
            'f.qty',
            'f.idtap',
            'i.namasf',
            'f.id_outlet',
            'f.tambahanket',
            'f.sn_awal',
            'f.sn_akhir',
            'f.payment_status',
            'f.paid_at'
        );

    if (! $this->isGlobalAdmin()) TapFilter::apply($query, 'f.idtap');

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
        ->filterColumn('f.sn_awal', function ($query, $keyword) {
            $query->where('f.sn_awal', 'like', '%'.$keyword.'%');
        })
        ->filterColumn('f.sn_akhir', function ($query, $keyword) {
            $query->where('f.sn_akhir', 'like', '%'.$keyword.'%');
        })
        ->editColumn('qty', fn ($r) => number_format($r->qty))
        ->editColumn('payment_status', fn ($r) => match ($r->payment_status) {
            'pending' => 'Pending Stor',
            'paid' => 'Sudah Bayar',
            default => 'Cash',
        })
        ->addColumn('action', function ($row) {
            if ((int) $row->idkeluar < 0) return '<span class="text-muted">Mobile approved</span>';
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
        $bulkDenomMetadata = $denom->map(fn ($row) => [
            'id' => $row->iddenom,
            'name' => $row->denom,
            'product' => $row->product,
            'product_group' => $row->product_group,
            'kategori_inject' => $row->kategori_inject,
            'group_name' => $row->group_name,
        ])->values();

        $data = DB::table('kodetap')
            ->when(! $this->isGlobalAdmin() && $idtap !== 'SBP_DUMAI', function ($q) use ($idtap) {
                $q->where('idtap', $idtap);
            })
            ->get();

        return view('form/form-sfkeluar', compact('data', 'idtap', 'denom', 'bulkDenomMetadata'));
    }

    /* =========================
       AJAX GET SF
    ========================= */
    public function getSf(Request $request)
    {
        $idtap = $this->requestedTap($request);

        $sfData = \App\Support\SfVisibility::visible(DB::table('idsf'))
            ->where('idtap', $idtap)
            ->orderBy('namasf')
            ->get();

        return response()->json($sfData);
    }

    public function getOutlets(Request $request)
    {
        $idtap = $this->requestedTap($request);

        $query = DB::table('appsdumais')
            ->whereRaw('UPPER(TRIM(tap)) = ?', [mb_strtoupper(trim($idtap))])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->input('q'));
                $q->where(function ($sub) use ($term) {
                    $sub->where('id_outlet', 'like', "%{$term}%")
                        ->orWhere('nama_outlet', 'like', "%{$term}%");
                });
            })
            ->orderBy('nama_outlet');

        return response()->json($query->get(['id_outlet', 'nama_outlet']));
    }

    private function isGlobalAdmin(): bool
    {
        return auth()->user()?->hasClusterAdminAccess() ?? false;
    }

    private function requestedTap(Request $request): string
    {
        $requestedTap = trim((string) $request->input('idtap'));
        abort_if($requestedTap === '', 422, 'TAP wajib dipilih.');

        if (! $this->isGlobalAdmin()) {
            $sessionTap = trim((string) session('idtap'));
            abort_unless(strcasecmp($requestedTap, $sessionTap) === 0, 403, 'TAP tidak sesuai dengan akses admin.');

            return $sessionTap;
        }

        abort_unless(DB::table('kodetap')->where('idtap', $requestedTap)->exists(), 422, 'TAP tidak valid.');

        return $requestedTap;
    }

    /* =========================
       PROSES KELUAR SF
    ========================= */
    // Input manual SF Keluar. Satu formulir bisa berisi beberapa denom untuk sales dan outlet yang sama.
    public function keluarsfproses(Request $request)
{
    $items = $request->input('items');
    if (!is_array($items)) {
        $request->merge(['items' => [[
            'iddenom' => $request->iddenom,
            'qty' => $request->qty,
            'tambahanket' => $request->tambahanket,
            'serial_mode' => 'range',
            'identifier_type' => 'sn',
            'sn_start' => $request->sn_start,
            'sn_end' => $request->sn_end,
        ]]]);
    }
    $incomingItems = $request->input('items');
    if (is_array($incomingItems)) {
        foreach ($incomingItems as $key => $item) {
            if (! is_array($item)) continue;
            if (empty($item['serial_mode'])) $item['serial_mode'] = 'range';
            if (empty($item['identifier_type'])) $item['identifier_type'] = 'sn';
            $incomingItems[$key] = $item;
        }
        $request->merge(['items' => $incomingItems]);
    }

    $validated = $request->validate([
        'tgl' => 'required|date|before_or_equal:today',
        'idtap' => 'required|exists:kodetap,idtap',
        'idsf' => 'required|exists:idsf,idsf',
        'id_outlet' => [
            'required_if:payment_type,pending',
            'nullable',
            Rule::exists('appsdumais', 'id_outlet')->where(
                fn ($query) => $query->whereRaw('UPPER(TRIM(tap)) = ?', [mb_strtoupper(trim((string) $request->idtap))])
            ),
        ],
        'payment_type' => 'required|in:cash,pending',
        'items' => 'required|array|min:1',
        'items.*.iddenom' => 'required|exists:denom,iddenom',
            'items.*.qty' => 'required|integer|min:1|max:2000000',
            'items.*.serial_mode' => 'required|in:range,broken',
        'items.*.identifier_type' => 'required|in:sn',
            'items.*.sn_start' => 'nullable|string|max:24',
        'items.*.sn_end' => 'nullable|string|max:24',
        'items.*.tambahanket' => 'nullable|string|max:255',
    ]);
    // Rows removed in the browser can leave sparse item keys (0, 2, 4, ...).
    // Normalize before the indexed overlap checks and persistence loops.
    $validated['items'] = array_values($validated['items']);
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
        if ($item['serial_mode'] === 'broken') {
            $item['sn_start'] = '';
            $item['sn_end'] = '';
            $item['identifier_type'] = 'sn';
            continue;
        }
        $start = (string) ($item['sn_start'] ?? '');
        $end = (string) ($item['sn_end'] ?? '');
        $snPattern = $isStarterPack ? '/^0\\d{15}$/' : '/^\\d{12}$/';
        if (! preg_match($snPattern, $start) || ($end !== '' && ! preg_match($snPattern, $end))) {
            throw ValidationException::withMessages([
                "items.{$index}.sn_start" => $isStarterPack
                    ? 'SN perdana wajib 16 digit dan diawali 0.'
                    : 'SN voucher wajib tepat 12 digit.',
            ]);
        }

        // A blank end serial means one item. For a range, calculate quantity
        // from the serial endpoints and ignore any client supplied quantity.
        if ($end === '') $end = $start;
        $rangeQty = (int) ltrim($end, '0') - (int) ltrim($start, '0') + 1;
        if ($rangeQty < 1 || $rangeQty > 2_000_000) {
            throw ValidationException::withMessages([
                "items.{$index}.sn_end" => 'SN akhir harus sama dengan atau lebih besar dari SN awal; maksimal 2.000.000 SN.',
            ]);
        }

        if (isset($item['qty']) && (int) $item['qty'] !== $rangeQty) {
            throw ValidationException::withMessages([
                "items.{$index}.sn_end" => 'Qty harus sama dengan jumlah SN di dalam rentang; periksa kembali SN akhir.',
            ]);
        }
        $item['sn_start'] = $start;
        $item['sn_end'] = $end;
        $item['qty'] = $rangeQty;
    }
    unset($item);

    for ($leftIndex = 0; $leftIndex < count($validated['items']); $leftIndex++) {
        for ($rightIndex = $leftIndex + 1; $rightIndex < count($validated['items']); $rightIndex++) {
            $left = $validated['items'][$leftIndex];
            $right = $validated['items'][$rightIndex];
            if ($left['serial_mode'] === 'broken' || $right['serial_mode'] === 'broken') continue;
            $overlaps = strlen($left['sn_start']) === strlen($right['sn_start'])
                && $left['sn_start'] <= $right['sn_end']
                && $left['sn_end'] >= $right['sn_start'];
            if ($overlaps) {
                throw ValidationException::withMessages([
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

    $ownership = app(\App\Services\SalesStockOwnership::class);
    foreach ($validated['items'] as $index => $item) {
        if ($item['serial_mode'] === 'broken') continue;
        if (! $ownership->ownsRange((string) $validated['idsf'], (string) $item['iddenom'], (string) $item['sn_start'], (string) $item['sn_end'])) {
            throw ValidationException::withMessages([
                "items.{$index}.sn_start" => "SN/rentang {$item['sn_start']}–{$item['sn_end']} tidak seluruhnya tercatat sebagai stok SF ini di Stock SF (SN).",
            ]);
        }
    }

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

            $pendingQty = $this->pendingStorQty((string) $validated['idsf'], (string) $iddenom);
            $pendingQtyAtDate = $this->pendingStorQty((string) $validated['idsf'], (string) $iddenom, null, $validated['tgl']);
            $availableNow = max(0, (int) $stockSf - $pendingQty);

            $historicalStock = $this->sfStockAtDate(
                $validated['idsf'],
                $iddenom,
                $validated['tgl'],
                (int) $stockSf
            );

            $availableAtDate = max(0, $historicalStock - $pendingQtyAtDate);
            $availableStock = min($availableNow, $availableAtDate);
            if ($availableStock < $requestedQty) {
                $denomName = DB::table('denom')
                    ->where('iddenom', $iddenom)
                    ->value('denom') ?? $iddenom;

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => "Total qty {$denomName} tidak mencukupi. Diminta: {$requestedQty}, stok fisik saat ini: {$stockSf}, Pending Stor seluruh tanggal: {$pendingQty}, Pending Stor sampai tanggal transaksi: {$pendingQtyAtDate}, tersedia: {$availableStock}.",
                ]);
            }
        }

        foreach ($validated['items'] as $item) {
            if ($validated['payment_type'] === 'cash') {
                DB::table('stockawalsf')
                    ->where('idsf', $validated['idsf'])
                    ->where('iddenom', $item['iddenom'])
                    ->decrement('stock', $item['qty']);
            }

            $newId = DB::table('keluarsf')->insertGetId([
                'idtap' => $validated['idtap'],
                'idsf' => $validated['idsf'],
                'id_outlet' => $validated['id_outlet'] ?? null,
                'iddenom' => $item['iddenom'],
                'qty' => $item['qty'],
                'tgl' => $validated['tgl'],
                'tambahanket' => $item['tambahanket'] ?? null,
                'sn_awal' => $item['sn_start'],
                'sn_akhir' => $item['sn_end'],
                'serial_mode' => $item['serial_mode'],
                'identifier_type' => $item['identifier_type'],
                'payment_status' => $validated['payment_type'] === 'cash' ? 'cash' : 'pending',
                'paid_at' => $validated['payment_type'] === 'cash' ? now('Asia/Jakarta') : null,
            ]);

            AuditLogger::log('INSERT', 'Stok Keluar SF (Bulk)', $newId, null, $item + [
                'idtap' => $validated['idtap'],
                'idsf' => $validated['idsf'],
                'tgl' => $validated['tgl'],
            ]);
        }
    });

    return redirect('sf-keluar')->with('success', count($validated['items']) . ' denom berhasil ditambahkan'.($validated['payment_type'] === 'pending' ? ' sebagai Pending Stor; stok fisik belum dipotong dan qty sudah dialokasikan dari stok tersedia.' : '.'));
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

        $stock = (int) (DB::table('stockawalsf')
            ->where('iddenom', $request->iddenom)
            ->where('idsf', $request->idsf)
            ->value('stock') ?? 0);
        $pending = $this->pendingStorQty((string) $request->idsf, (string) $request->iddenom);

        return response()->json([
            'stock' => max(0, $stock - $pending),
            'stock_physical' => $stock,
            'pending_qty' => $pending,
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

        $pendingByDenom = DB::table('keluarsf')
            ->where('idsf', $request->idsf)
            ->where('payment_status', 'pending')
            ->groupBy('iddenom')
            ->select('iddenom', DB::raw('SUM(qty) AS qty'))
            ->pluck('qty', 'iddenom');

        return response()->json($stocks->map(fn ($stock, $iddenom) => max(
            0,
            (int) $stock - (int) ($pendingByDenom[$iddenom] ?? 0)
        )));
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

        if (($data->payment_status ?? 'cash') !== 'pending') {
            DB::table('stockawalsf')
                ->where('idsf', $data->idsf)
                ->where('iddenom', $data->iddenom)
                ->increment('stock', $data->qty);
        }

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
                if (($data->payment_status ?? 'cash') !== 'pending') {
                    DB::table('stockawalsf')
                        ->where('idsf', $data->idsf)
                        ->where('iddenom', $data->iddenom)
                        ->increment('stock', $data->qty);
                }

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
        ->when(! $this->isGlobalAdmin() && $idtap !== 'SBP_DUMAI', function ($q) use ($idtap) {
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
        'id_outlet' => [
            'nullable',
            Rule::exists('appsdumais', 'id_outlet')->where(
                fn ($query) => $query->whereRaw('UPPER(TRIM(tap)) = ?', [mb_strtoupper(trim((string) $request->idtap))])
            ),
        ],
        'iddenom' => 'required|exists:denom,iddenom',
        'qty' => 'required|integer|min:1',
        'tambahanket' => 'nullable|string|max:255',
        'sn_awal' => 'nullable|string|max:24',
        'sn_akhir' => 'nullable|string|max:24',
    ]);

    $start = trim((string) ($validated['sn_awal'] ?? ''));
    $end = trim((string) ($validated['sn_akhir'] ?? ''));
    if ($start !== '') {
        if ($end === '') $end = $start;
        $denom = DB::table('denom')->where('iddenom', $validated['iddenom'])
            ->first(['iddenom', 'product', 'product_group', 'kategori_inject', 'group_name']);
        $denomId = strtoupper(trim((string) ($denom->iddenom ?? '')));
        $product = strtoupper(trim((string) ($denom->product ?? '')));
        $isSa = in_array($product, ['PV', 'SA'], true) ? $product === 'SA'
            : (in_array(strtoupper(trim((string) ($denom->product_group ?? ''))), ['SA', 'SP'], true)
                || strtoupper(trim((string) ($denom->kategori_inject ?? ''))) === 'SA'
                || strtoupper(trim((string) ($denom->group_name ?? ''))) === 'SA'
                || str_starts_with($denomId, 'SP'));
        $pattern = $isSa ? '/^0\d{15}$/' : '/^\d{12}$/';
        if (!preg_match($pattern, $start) || !preg_match($pattern, $end) || strlen($start) !== strlen($end)) {
            throw ValidationException::withMessages(['sn_awal' => $isSa ? 'SN SA wajib 16 digit dan diawali 0.' : 'SN PV wajib tepat 12 digit.']);
        }
        $rangeQty = (int) ltrim($end, '0') - (int) ltrim($start, '0') + 1;
        if ($rangeQty < 1 || $rangeQty > 2_000_000 || $rangeQty !== (int) $validated['qty']) {
            throw ValidationException::withMessages(['sn_akhir' => 'Qty harus sama dengan jumlah SN dalam rentang.']);
        }
        if (! app(\App\Services\SalesStockOwnership::class)->ownsRange((string) $validated['idsf'], (string) $validated['iddenom'], $start, $end)) {
            throw ValidationException::withMessages(['sn_awal' => "SN/rentang {$start}–{$end} tidak seluruhnya tercatat sebagai stok SF ini di Stock SF (SN)."]);
        }
    }

    DB::transaction(function () use ($validated, $id) {
        
        $newIdtap      = $validated['idtap'];
        $newIdsf       = $validated['idsf'];
        $newIddenom    = $validated['iddenom'];
        $newQty        = (int) $validated['qty'];
        $newTgl        = $validated['tgl'];
        $newKet        = $validated['tambahanket'] ?? null;
        $newOutlet     = $validated['id_outlet'] ?? null;

        // 1. Lock Data Lama
        $old = DB::table('keluarsf')->where('idkeluar', $id)->lockForUpdate()->first();
        if (!$old) throw new \Exception('Data transaksi tidak ditemukan');

        $stockWasPosted = ($old->payment_status ?? 'cash') !== 'pending';
        if ($stockWasPosted) {
            DB::table('stockawalsf')
                ->where('idsf', $old->idsf)
                ->where('iddenom', $old->iddenom)
                ->increment('stock', $old->qty);
        }

        $currentStock = (int) (DB::table('stockawalsf')
            ->where('idsf', $newIdsf)
            ->where('iddenom', $newIddenom)
            ->lockForUpdate()
            ->value('stock') ?? 0);
        $pendingQuery = DB::table('keluarsf')
            ->where('idsf', $newIdsf)
            ->where('iddenom', $newIddenom)
            ->where('payment_status', 'pending');
        if (($old->payment_status ?? 'cash') === 'pending' && $old->idsf === $newIdsf && $old->iddenom === $newIddenom) {
            $pendingQuery->where('idkeluar', '!=', $old->idkeluar);
        }
        $pendingQty = (int) $pendingQuery->sum('qty');
        $pendingQtyAtDate = (int) (clone $pendingQuery)->whereDate('tgl', '<=', $newTgl)->sum('qty');
        $historicalStock = $this->sfStockAtDate($newIdsf, $newIddenom, $newTgl, $currentStock, (int) $old->idkeluar);
        $availableStock = min(
            max(0, $currentStock - $pendingQty),
            max(0, $historicalStock - $pendingQtyAtDate)
        );
        if ($availableStock < $newQty) {
            throw ValidationException::withMessages([
                'qty' => "Quantity tidak mencukupi. Stok fisik saat ini: {$currentStock}, Pending Stor seluruh tanggal: {$pendingQty}, Pending Stor sampai tanggal transaksi: {$pendingQtyAtDate}, tersedia: {$availableStock}.",
            ]);
        }

        if ($stockWasPosted) {
            DB::table('stockawalsf')
                ->where('idsf', $newIdsf)
                ->where('iddenom', $newIddenom)
                ->decrement('stock', $newQty);
        }

            // 4. Update Transaksi
            DB::table('keluarsf')
                ->where('idkeluar', $id)
                ->update([
                    'idtap'       => $newIdtap,
                    'idsf'        => $newIdsf,
                    'iddenom'     => $newIddenom,
                    'qty'         => $newQty,
                    'tgl'         => $newTgl,
                    'id_outlet'   => $newOutlet,
                    'tambahanket' => $newKet,
                    'sn_awal'     => $validated['sn_awal'] ?? null,
                    'sn_akhir'    => $validated['sn_akhir'] ?? null,
                ]);

            // 📝 LOG
            AuditLogger::log('UPDATE', 'Stok Keluar SF', $id, (array)$old, $validated);
    });

    return redirect('sf-keluar')->with('success', 'Data Berhasil Diupdate!');
}

    private function pendingStorQty(string $idsf, string $iddenom, ?int $excludedId = null, ?string $throughDate = null): int
    {
        return (int) DB::table('keluarsf')
            ->where('idsf', $idsf)
            ->where('iddenom', $iddenom)
            ->where('payment_status', 'pending')
            ->when($excludedId !== null, fn ($query) => $query->where('idkeluar', '!=', $excludedId))
            ->when($throughDate !== null, fn ($query) => $query->whereDate('tgl', '<=', $throughDate))
            ->sum('qty');
    }

    /**
     * Saldo SF pada akhir tanggal transaksi. Saldo materialized saat ini
     * dikembalikan ke masa lalu dengan membalik seluruh pergerakan setelah
     * tanggal tersebut, sehingga transaksi backdate tidak dapat memakai stok
     * yang baru diterima pada tanggal berikutnya.
     */
    // Saldo SF pada tanggal transaksi dipakai untuk validasi pengeluaran bertanggal mundur.
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
            ->whereRaw('NOT '.\App\Support\DenomGrouping::legacyDoAtTapSql('masuk'))
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
            ->where(function ($query) use ($date) {
                $query->where(function ($cash) use ($date) {
                    $cash->where(function ($status) {
                        $status->whereNull('payment_status')->orWhere('payment_status', 'cash');
                    })->whereDate('tgl', '>', $date);
                })->orWhere(function ($paid) use ($date) {
                    $paid->where('payment_status', 'paid')->whereDate('paid_at', '>', $date);
                });
            });
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

    // Samakan sumber export dengan dashboard/view: transaksi admin SF Keluar
    // ditambah transaksi mobile yang sudah approved.
    $adminSales = \App\Support\SalesSource::legacy()
        ->select(['tgl', 'idtap', 'idsf', 'iddenom', 'qty', 'tambahanket', 'id_outlet']);
    $mobileSales = \App\Support\SalesSource::mobile()
        ->where('status', 'approved')
        ->select([
            'tgl', 'idtap', DB::raw('COALESCE(owner_idsf,idsf) AS idsf'), 'iddenom', 'qty',
            DB::raw("'Mobile approved' as tambahanket"), 'id_outlet',
        ]);

    $query = DB::query()->fromSub(
            $adminSales->unionAll($mobileSales),
            'f'
        )
        ->join('denom as d', 'd.iddenom', '=', 'f.iddenom')
        ->join('idsf as i', 'f.idsf', '=', 'i.idsf')
        ->select(
            'f.tgl',
            'd.denom',
            DB::raw('SUM(f.qty) as qty'),
            'f.idtap',
            'i.namasf',
            'f.id_outlet',
            'f.tambahanket', 'd.product', 'd.product_variant'
        )
        ->groupBy(
            'f.tgl',
            'd.denom',
            'f.idtap',
            'i.namasf',
            'f.id_outlet',
            'f.tambahanket', 'd.product', 'd.product_variant'
        );

    /* ===============================
       FILTER TAP
    =============================== */
    if (! $this->isGlobalAdmin()) TapFilter::apply($query, 'f.idtap');

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
