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
            'iddenom' => 'required|string|exists:denom,iddenom',
        ]);

        [$table, $targetColumn] = $this->resolveTarget(
            $validated['target_type'],
            $validated['target_id']
        );

        $stock = DB::table($table)
            ->where($targetColumn, $validated['target_id'])
            ->where('iddenom', $validated['iddenom'])
            ->value('stock');

        return response()->json(['stock' => (int) ($stock ?? 0)]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'target_type' => 'required|in:tap,sales',
            'target_id' => 'required|string',
            'iddenom' => 'required|string|exists:denom,iddenom',
            'stock' => 'required|integer|min:0|max:2147483647',
            'reason' => 'required|string|min:5|max:500',
        ]);

        [$table, $targetColumn, $targetName, $idtap] = $this->resolveTarget(
            $validated['target_type'],
            $validated['target_id']
        );

        DB::transaction(function () use ($validated, $table, $targetColumn, $targetName, $idtap) {
            $existing = DB::table($table)
                ->where($targetColumn, $validated['target_id'])
                ->where('iddenom', $validated['iddenom'])
                ->lockForUpdate()
                ->first();

            $oldStock = (int) ($existing->stock ?? 0);

            if ($existing) {
                DB::table($table)
                    ->where($targetColumn, $validated['target_id'])
                    ->where('iddenom', $validated['iddenom'])
                    ->update(['stock' => $validated['stock']]);
            } else {
                DB::table($table)->insert([
                    $targetColumn => $validated['target_id'],
                    'iddenom' => $validated['iddenom'],
                    'stock' => $validated['stock'],
                ]);
            }

            AuditLogger::log(
                'STOCK ADJUSTMENT',
                $validated['target_type'] === 'tap' ? 'Stok Gudang TAP' : 'Stok Petugas',
                $validated['target_id'] . ':' . $validated['iddenom'],
                [
                    'target' => $targetName,
                    'idtap' => $idtap,
                    'iddenom' => $validated['iddenom'],
                    'stock' => $oldStock,
                ],
                [
                    'target' => $targetName,
                    'idtap' => $idtap,
                    'iddenom' => $validated['iddenom'],
                    'stock' => (int) $validated['stock'],
                    'reason' => $validated['reason'],
                ]
            );
        });

        return back()->with('status', 'Stok berhasil diperbarui dan dicatat pada Audit Trail.');
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
