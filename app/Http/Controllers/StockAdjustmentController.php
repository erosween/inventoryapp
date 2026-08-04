<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(auth()->check() && auth()->user()->isSuperAdmin(), 403);

            return $next($request);
        });
    }

    public function index()
    {
        $taps = DB::table('kodetap')->orderBy('idtap')->pluck('idtap');
        $sales = DB::table('idsf')
            ->orderBy('idtap')
            ->orderBy('namasf')
            ->get(['idsf', 'namasf', 'idtap']);
        $denoms = DB::table('denom')->orderBy('group_name')->orderBy('denom')->get();

        return view('stock-adjustment.index', compact('taps', 'sales', 'denoms'));
    }

    public function current(Request $request)
    {
        $validated = $request->validate([
            'target_type' => 'required|in:tap,sales',
            'target_id' => 'required|string',
        ]);

        [$table, $targetColumn] = $this->resolveTarget(
            $validated['target_type'],
            $validated['target_id']
        );

        $stocks = DB::table('denom as d')
            ->leftJoin($table . ' as s', function ($join) use ($targetColumn, $validated) {
                $join->on('s.iddenom', '=', 'd.iddenom')
                    ->where('s.' . $targetColumn, '=', $validated['target_id']);
            })
            ->select('d.iddenom', DB::raw('COALESCE(s.stock, 0) as stock'))
            ->orderBy('d.group_name')
            ->orderBy('d.denom')
            ->pluck('stock', 'd.iddenom')
            ->map(fn ($stock) => (int) $stock);

        return response()->json(['stocks' => $stocks]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'target_type' => 'required|in:tap,sales',
            'target_id' => 'required|string',
            'stocks' => 'required|array|min:1',
            'stocks.*' => 'nullable|integer|min:0|max:2147483647',
            'reason' => 'required|string|min:5|max:500',
        ]);

        $stocks = collect($validated['stocks'])
            ->filter(fn ($stock) => $stock !== null && $stock !== '')
            ->map(fn ($stock) => (int) $stock);

        if ($stocks->isEmpty()) {
            return back()->withErrors(['stocks' => 'Masukkan minimal satu qty baru.'])->withInput();
        }

        $validDenoms = DB::table('denom')->whereIn('iddenom', $stocks->keys())->pluck('iddenom');
        if ($validDenoms->count() !== $stocks->count()) {
            return back()->withErrors(['stocks' => 'Terdapat denom yang tidak valid.'])->withInput();
        }

        [$table, $targetColumn, $targetName, $idtap] = $this->resolveTarget(
            $validated['target_type'],
            $validated['target_id']
        );

        $changedCount = DB::transaction(function () use ($validated, $stocks, $table, $targetColumn, $targetName, $idtap) {
            $changedCount = 0;

            foreach ($stocks as $iddenom => $newStock) {
                $existing = DB::table($table)
                    ->where($targetColumn, $validated['target_id'])
                    ->where('iddenom', $iddenom)
                    ->lockForUpdate()
                    ->first();

                $oldStock = (int) ($existing->stock ?? 0);
                if ($oldStock === $newStock) {
                    continue;
                }

                if ($existing) {
                    DB::table($table)
                        ->where($targetColumn, $validated['target_id'])
                        ->where('iddenom', $iddenom)
                        ->update(['stock' => $newStock]);
                } else {
                    DB::table($table)->insert([
                        $targetColumn => $validated['target_id'],
                        'iddenom' => $iddenom,
                        'stock' => $newStock,
                    ]);
                }

                AuditLogger::log(
                    'STOCK ADJUSTMENT',
                    $validated['target_type'] === 'tap' ? 'Stok Gudang TAP' : 'Stok Petugas',
                    $validated['target_id'] . ':' . $iddenom,
                    [
                        'target' => $targetName,
                        'idtap' => $idtap,
                        'iddenom' => $iddenom,
                        'stock' => $oldStock,
                    ],
                    [
                        'target' => $targetName,
                        'idtap' => $idtap,
                        'iddenom' => $iddenom,
                        'stock' => $newStock,
                        'reason' => $validated['reason'],
                    ]
                );
                $changedCount++;
            }

            return $changedCount;
        });

        return back()->with('status', $changedCount > 0
            ? $changedCount . ' denom berhasil diperbarui dan dicatat pada Audit Trail.'
            : 'Tidak ada perubahan stok.');
    }

    private function resolveTarget(string $type, string $targetId): array
    {
        if ($type === 'tap') {
            abort_unless(DB::table('kodetap')->where('idtap', $targetId)->exists(), 422, 'TAP tidak valid.');

            return ['stockawaltap', 'idtap', $targetId, $targetId];
        }

        $sales = DB::table('idsf')->where('idsf', $targetId)->first();
        abort_unless($sales, 422, 'Petugas tidak valid.');

        return ['stockawalsf', 'idsf', $sales->namasf, $sales->idtap];
    }
}
