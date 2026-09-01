<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FormInjectbyuController extends Controller
{   
    public function index(Request $request)
    {
        $idtap = session('idtap');

        $denom = DB::table('denom')
                    ->select('iddenom', 'denom')
                    ->where('kategori_inject','BYU')
                    ->where('iddenom', '!=', 'V33')
                    ->get();

        if($idtap == 'SBP_DUMAI'){

            $data = DB::table('kodetap')
                    ->select('*')
                    ->get();

                    $n = DB::table('keluar')
                    ->select(DB::raw('sum(status) as qty'))
                    ->get();
    
                $notif = $n->sum('qty');
    
        }else{
            $data = DB::table('kodetap')
                    ->select('*')
                    ->where('idtap',$idtap)
                    ->get();
        }
        return view('form/forminjectbyu',compact('data','idtap','denom'));

    }

    public function injectProses(Request $request)
    {
        $items = $request->input('items');
        if (! is_array($items)) {
            $items = [[
                'iddenom' => $request->input('iddenom'),
                'qty' => $request->input('qty'),
                'sn' => $request->input('sn'),
            ]];
        }

        $payload = [
            'idtap' => $request->input('idtap'),
            'tgl' => $request->input('tgl'),
            'items' => array_values($items),
        ];
        Validator::make($payload, [
            'idtap' => ['required', 'string', 'exists:kodetap,idtap'],
            'tgl' => ['required', 'date', 'before_or_equal:today'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.iddenom' => ['required', 'string'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.sn' => ['required', 'string', 'max:1000'],
        ], [
            'items.required' => 'Tambahkan minimal satu denom.',
            'items.*.iddenom.required' => 'Denom wajib dipilih.',
            'items.*.qty.min' => 'Quantity minimal 1.',
            'items.*.sn.required' => 'SN wajib diisi pada setiap denom.',
        ])->validate();

        $sessionTap = (string) session('idtap');
        if ($sessionTap !== 'SBP_DUMAI' && $payload['idtap'] !== $sessionTap) {
            abort(403, 'TAP tidak sesuai dengan akses pengguna.');
        }

        $allowedDenoms = DB::table('denom')
            ->where('kategori_inject', 'BYU')
            ->where('iddenom', '!=', 'V33')
            ->pluck('iddenom')
            ->map(fn ($id) => (string) $id)
            ->flip();

        foreach ($payload['items'] as $item) {
            if (! $allowedDenoms->has((string) $item['iddenom'])) {
                throw ValidationException::withMessages([
                    'items' => 'Terdapat denom yang tidak valid untuk Inject PV By.U.',
                ]);
            }
        }

        DB::transaction(function () use ($payload) {
            $idtap = $payload['idtap'];
            $totalQty = collect($payload['items'])->sum(fn ($item) => (int) $item['qty']);
            $stokSegel = DB::table('stockawaltap')
                ->where('idtap', $idtap)
                ->where('iddenom', 'V33')
                ->lockForUpdate()
                ->value('stock');

            if ($stokSegel === null) {
                throw ValidationException::withMessages(['items' => 'Stok segel By.U TAP tidak ditemukan.']);
            }
            if ((int) $stokSegel < $totalQty) {
                throw ValidationException::withMessages([
                    'items' => "Total quantity ({$totalQty}) melebihi stok segel By.U tersedia ({$stokSegel}).",
                ]);
            }

            $totalsByDenom = collect($payload['items'])
                ->groupBy('iddenom')
                ->map(fn ($rows) => $rows->sum(fn ($row) => (int) $row['qty']));
            $existingDestinations = DB::table('stockawaltap')
                ->where('idtap', $idtap)
                ->whereIn('iddenom', $totalsByDenom->keys())
                ->lockForUpdate()
                ->pluck('iddenom');
            if ($existingDestinations->count() !== $totalsByDenom->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu stok denom tujuan belum tersedia pada TAP.',
                ]);
            }

            DB::table('injectvf')->insert(collect($payload['items'])->map(fn ($item) => [
                'idtap' => $idtap,
                'iddenom' => $item['iddenom'],
                'qty' => (int) $item['qty'],
                'sn' => $item['sn'],
                'tgl' => $payload['tgl'],
                'kategori' => 'V33',
            ])->all());

            DB::table('stockawaltap')
                ->where('idtap', $idtap)
                ->where('iddenom', 'V33')
                ->decrement('stock', $totalQty);

            foreach ($totalsByDenom as $iddenom => $qty) {
                DB::table('stockawaltap')
                    ->where('idtap', $idtap)
                    ->where('iddenom', $iddenom)
                    ->increment('stock', $qty);
            }
        });

        return redirect('injectvf')->with('status', count($payload['items']) . ' denom By.U berhasil di-inject dan stok diperbarui.');
    }


public function getStockSegelTap(Request $request)
{
    $stock = DB::table('stockawaltap')
        ->where('idtap', $request->idtap)
        ->where('iddenom', 'V33') // BYU
        ->value('stock') ?? 0;

    return response()->json(['stock' => $stock]);
}

    
}
