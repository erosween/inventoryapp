<?php

namespace App\Http\Controllers;

use App\Support\IntertapTransferKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\StockProduct;

class FormKeluartapController extends Controller
{
    public function index(){
        $idtap = session('idtap');

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

        $tappenerima=DB::table('kodetap')
                    ->select('*')
                    ->where('idtap','<>',$idtap)
                    ->get();

        $denom = DB::table('denom')
                ->select('*')
                ->orderByRaw("CASE iddenom WHEN 'SP6' THEN 0 WHEN 'SP7' THEN 1 WHEN 'SEGEL' THEN 2 WHEN 'V33' THEN 3 ELSE 10 END")
                ->orderBy('denom')
                ->get()
                ->unique('iddenom')
                ->values();

        $denomOptions = $denom->map(fn ($row) => [
            'id' => $row->iddenom,
            'name' => $row->denom,
            'product' => $row->product,
            'product_group' => $row->product_group,
            'kategori_inject' => $row->kategori_inject,
            'group_name' => $row->group_name,
        ])->values();

        return view('form/formkeluartap', compact('data', 'idtap', 'denom', 'tappenerima', 'denomOptions'));
    }

// get stock
public function getStockTapPengirim(Request $request)
{
    $request->validate([
        'idtap'   => 'required',
        'iddenom' => 'required'
    ]);

    $stock = DB::table('stockawaltap')
        ->where('idtap', $request->idtap)
        ->where('iddenom', $request->iddenom)
        ->value('stock') ?? 0;

    return response()->json([
        'stock' => (int) $stock
    ]);
}



// Catat pengiriman dari TAP asal. TAP tujuan mengonfirmasi penerimaan lewat MasukController.
public function proseskeluartapform(Request $request)
{
    $items = $request->input('items');
    if (!is_array($items)) {
        $items = [[
            'iddenom' => $request->iddenom,
            'qty' => $request->qty,
            'sn' => $request->sn,
            'sn_start' => $request->sn_start,
            'sn_end' => $request->sn_end,
            'serial_mode' => $request->serial_mode ?? 'range',
        ]];
        $request->merge(['items' => $items]);
    }
    $incomingItems = $request->input('items', []);
    foreach ($incomingItems as &$item) {
        if (! is_array($item)) continue;
        if (empty($item['serial_mode'])) $item['serial_mode'] = 'range';
        if (empty($item['sn_start']) && ! empty($item['sn'])) {
            $parts = explode('-', trim((string) $item['sn']), 2);
            $item['sn_start'] = $parts[0];
            $item['sn_end'] = $parts[1] ?? '';
        }
    }
    unset($item);
    $request->merge(['items' => $incomingItems]);

    $validated = $request->validate([
        'tgl' => 'required|date',
        'pengirim' => 'required|exists:kodetap,idtap',
        'penerima' => 'required|different:pengirim|exists:kodetap,idtap',
        'tambahket' => 'nullable|string|max:500',
        'items' => 'required|array|min:1',
        'items.*.iddenom' => 'required|exists:denom,iddenom',
        'items.*.qty' => 'nullable|integer|min:1|max:2000000',
        'items.*.sn' => 'nullable|string|max:255',
        'items.*.sn_start' => 'nullable|string|max:24',
        'items.*.sn_end' => 'nullable|string|max:24',
        'items.*.serial_mode' => 'required|in:range,broken',
    ]);

    $denomMetadata = DB::table('denom')
        ->whereIn('iddenom', collect($validated['items'])->pluck('iddenom')->unique())
        ->get(['iddenom', 'product', 'product_group', 'kategori_inject', 'group_name'])
        ->keyBy('iddenom');
    $serialRanges = [];
    foreach ($validated['items'] as $index => &$item) {
        if ($item['serial_mode'] === 'broken') {
            $qty = (int) ($item['qty'] ?? 0);
            if ($qty < 1 || $qty > 2_000_000) {
                throw ValidationException::withMessages(["items.{$index}.qty" => 'Qty SN pecah harus antara 1 dan 2.000.000.']);
            }
            $item['sn'] = '';
            continue;
        }
        $denom = $denomMetadata->get($item['iddenom']);
        $isSa = $denom ? StockProduct::isSa($denom) : false;
        $start = trim((string) ($item['sn_start'] ?? ''));
        $end = trim((string) ($item['sn_end'] ?? ''));
        $pattern = $isSa ? '/^0\\d{15}$/' : '/^\\d{12}$/';
        if (!preg_match($pattern, $start) || ($end !== '' && !preg_match($pattern, $end))) {
            throw ValidationException::withMessages(["items.{$index}.sn_start" => $isSa ? 'SN SA harus 16 digit dan diawali 0.' : 'SN PV harus tepat 12 digit.']);
        }
        if ($end === '') $end = $start;
        $qty = (int) $end - (int) $start + 1;
        if ($qty < 1 || $qty > 2_000_000) {
            throw ValidationException::withMessages(["items.{$index}.sn_end" => 'SN akhir harus sama atau lebih besar dari SN awal; maksimal 2.000.000 SN.']);
        }
        if (isset($item['qty']) && (int) $item['qty'] !== $qty) {
            throw ValidationException::withMessages(["items.{$index}.sn_end" => 'Qty harus sesuai jumlah SN dalam rentang.']);
        }
        $item['qty'] = $qty;
        $item['sn'] = $start === $end ? $start : $start.'-'.$end;
        foreach ($serialRanges as $range) {
            if (strlen($start) === strlen($range['start']) && $start <= $range['end'] && $end >= $range['start']) {
                throw ValidationException::withMessages(['items' => 'Rentang SN bertabrakan dengan baris lain. Pastikan setiap SN hanya diinput satu kali.']);
            }
        }
        $serialRanges[] = ['start' => $start, 'end' => $end];
    }
    unset($item);
    usort($validated['items'], fn ($a, $b) => strcmp($a['iddenom'], $b['iddenom']));

    if (session('idtap') !== 'SBP_DUMAI' && $validated['pengirim'] !== session('idtap')) {
        abort(403);
    }

    DB::transaction(function () use ($validated) {
        $requestedByDenom = collect($validated['items'])
            ->groupBy('iddenom')
            ->map(fn ($items) => $items->sum('qty'));

        foreach ($requestedByDenom as $iddenom => $requestedQty) {
            $stock = DB::table('stockawaltap')
                ->where('idtap', $validated['pengirim'])
                ->where('iddenom', $iddenom)
                ->lockForUpdate()
                ->value('stock') ?? 0;

            if ($stock < $requestedQty) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => "Total qty {$iddenom} tidak mencukupi. Diminta: {$requestedQty}, tersedia: {$stock}.",
                ]);
            }
        }

        foreach ($validated['items'] as $item) {
            $denomName = DB::table('denom')->where('iddenom', $item['iddenom'])->value('denom');
            $fingerprint = IntertapTransferKey::make(
                $validated['tgl'], $validated['pengirim'], $validated['penerima'],
                $denomName, $item['qty'], $item['sn']
            );
            $duplicateTransfer = DB::table('keluar as k')
                ->join('denom as d', 'd.iddenom', '=', 'k.iddenom')
                ->where('k.tgl', $validated['tgl'])
                ->where('k.pengirim', $validated['pengirim'])
                ->where('k.penerima', $validated['penerima'])
                ->where('k.qty', $item['qty'])
                ->lockForUpdate()
                ->get(['k.tgl', 'k.pengirim', 'k.penerima', 'k.qty', 'k.sn', 'd.denom'])
                ->contains(fn ($row) => IntertapTransferKey::make(
                    $row->tgl, $row->pengirim, $row->penerima, $row->denom, $row->qty, $row->sn
                ) === $fingerprint);

            if ($duplicateTransfer) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => "Pengiriman {$denomName} dengan tanggal, qty, dan SN yang sama sudah tercatat. Data tidak disimpan ulang.",
                ]);
            }

            $inserted = DB::table('keluar')->insertOrIgnore([
                'iddenom' => $item['iddenom'],
                'pengirim' => $validated['pengirim'],
                'penerima' => $validated['penerima'],
                'qty' => $item['qty'],
                'tgl' => $validated['tgl'],
                'sn' => trim($item['sn']),
                'tambahanket' => $validated['tambahket'] ?? null,
                'idtap' => $validated['pengirim'],
                'status' => 1,
                'request_fingerprint' => $fingerprint,
                'canonical_fingerprint' => $fingerprint,
            ]);
            if ($inserted !== 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => "Pengiriman {$denomName} yang sama sedang atau sudah diproses. Data tidak disimpan ulang.",
                ]);
            }
        }
    });

    return redirect('keluar')->with('success', count($validated['items']) . ' denom menunggu approval TAP penerima');
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
}
