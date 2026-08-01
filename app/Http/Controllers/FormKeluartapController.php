<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                ->get();

        return view('form/formkeluartap',compact('data','idtap','denom','tappenerima'));
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



public function proseskeluartapform(Request $request)
{
    $items = $request->input('items');
    if (!is_array($items)) {
        $items = [[
            'iddenom' => $request->iddenom,
            'qty' => $request->qty,
            'sn' => $request->sn,
        ]];
        $request->merge(['items' => $items]);
    }

    $validated = $request->validate([
        'tgl' => 'required|date',
        'pengirim' => 'required|exists:kodetap,idtap',
        'penerima' => 'required|different:pengirim|exists:kodetap,idtap',
        'tambahket' => 'nullable|string|max:500',
        'items' => 'required|array|min:1',
        'items.*.iddenom' => 'required|exists:denom,iddenom',
        'items.*.qty' => 'required|integer|min:1',
        'items.*.sn' => 'required|string|max:255',
    ]);
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
            DB::table('keluar')->insert([
                'iddenom' => $item['iddenom'],
                'pengirim' => $validated['pengirim'],
                'penerima' => $validated['penerima'],
                'qty' => $item['qty'],
                'tgl' => $validated['tgl'],
                'sn' => $item['sn'],
                'tambahanket' => $validated['tambahket'] ?? null,
                'idtap' => $validated['pengirim'],
                'status' => 1,
            ]);
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
