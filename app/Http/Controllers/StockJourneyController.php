<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockJourneyController extends Controller
{
    public function index(Request $request)
    {
        $endDate = $this->safeDate($request->input('end_date'), now());
        $startDate = $this->safeDate($request->input('start_date'), $endDate->copy()->subDays(13));
        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        if ($startDate->diffInDays($endDate) > 90) {
            $startDate = $endDate->copy()->subDays(90);
        }

        $allowedTaps = $this->allowedTaps();
        $movementUnion = $this->movementUnion($startDate->toDateString(), $endDate->toDateString());
        $base = DB::query()
            ->fromSub($movementUnion, 'movements')
            ->leftJoin('denom as d', 'd.iddenom', '=', 'movements.iddenom')
            ->whereIn('movements.idtap', $allowedTaps);

        $selectedTap = $request->filled('idtap') && in_array($request->input('idtap'), $allowedTaps, true)
            ? $request->input('idtap')
            : null;
        if ($selectedTap) {
            $base->where(function ($query) use ($selectedTap) {
                $query->where('movements.idtap', $selectedTap)
                    ->orWhere(function ($q) use ($selectedTap) {
                        $q->where('movements.from_type', 'TAP')->where('movements.from_id', $selectedTap);
                    })->orWhere(function ($q) use ($selectedTap) {
                        $q->where('movements.to_type', 'TAP')->where('movements.to_id', $selectedTap);
                    });
            });
        }
        $selectedSf = $request->filled('idsf') ? $request->input('idsf') : null;
        if ($selectedSf) {
            $base->where(function ($query) use ($selectedSf) {
                $query->where(function ($q) use ($selectedSf) {
                    $q->where('movements.from_type', 'SF')->where('movements.from_id', $selectedSf);
                })->orWhere(function ($q) use ($selectedSf) {
                    $q->where('movements.to_type', 'SF')->where('movements.to_id', $selectedSf);
                });
            });
        }

        // The stock perspective follows the most specific location filter.
        // This avoids contradictory states such as selecting an SF while the
        // page is still calculating the "All" balance.
        $mode = $selectedSf ? 'sf' : ($selectedTap ? 'tap' : 'all');

        if ($request->filled('validity')) {
            $base->where('d.group_name', $request->input('validity'));
        }
        if ($request->filled('iddenom')) {
            $base->where('movements.iddenom', $request->input('iddenom'));
        }

        $quotedTap = $selectedTap ? DB::connection()->getPdo()->quote($selectedTap) : null;
        $quotedSf = $selectedSf ? DB::connection()->getPdo()->quote($selectedSf) : null;
        $sign = match ($mode) {
            'tap' => $selectedTap
                ? "(CASE WHEN movements.to_type = 'TAP' AND movements.to_id = {$quotedTap} THEN movements.qty ELSE 0 END - CASE WHEN movements.from_type = 'TAP' AND movements.from_id = {$quotedTap} THEN movements.qty ELSE 0 END)"
                : "(CASE WHEN movements.to_type = 'TAP' THEN movements.qty ELSE 0 END - CASE WHEN movements.from_type = 'TAP' THEN movements.qty ELSE 0 END)",
            'sf' => $selectedSf
                ? "(CASE WHEN movements.to_type = 'SF' AND movements.to_id = {$quotedSf} THEN movements.qty ELSE 0 END - CASE WHEN movements.from_type = 'SF' AND movements.from_id = {$quotedSf} THEN movements.qty ELSE 0 END)"
                : "(CASE WHEN movements.to_type = 'SF' THEN movements.qty ELSE 0 END - CASE WHEN movements.from_type = 'SF' THEN movements.qty ELSE 0 END)",
            default => $selectedTap
                ? "(CASE
                    WHEN movements.from_type = 'OUTSIDE' THEN movements.qty
                    WHEN movements.to_type = 'OUTSIDE' THEN -movements.qty
                    WHEN movements.movement_type = 'TRANSFER_TAP' AND movements.to_id = {$quotedTap} AND movements.from_id <> {$quotedTap} THEN movements.qty
                    WHEN movements.movement_type = 'TRANSFER_TAP' AND movements.from_id = {$quotedTap} AND movements.to_id <> {$quotedTap} THEN -movements.qty
                    ELSE 0 END)"
                : "(CASE WHEN movements.from_type = 'OUTSIDE' THEN movements.qty WHEN movements.to_type = 'OUTSIDE' THEN -movements.qty ELSE 0 END)",
        };

        $summary = (clone $base)->selectRaw("COUNT(*) as movement_count")
            ->selectRaw("SUM(CASE WHEN {$sign} > 0 THEN {$sign} ELSE 0 END) as stock_in")
            ->selectRaw("SUM(CASE WHEN {$sign} < 0 THEN ABS({$sign}) ELSE 0 END) as stock_out")
            ->selectRaw("SUM({$sign}) as net_movement")
            ->first();

        $currentBalance = $this->currentBalance(
            $mode,
            $allowedTaps,
            $selectedTap,
            $selectedSf,
            $request->input('validity'),
            $request->input('iddenom')
        );

        $futureNet = 0;
        if ($endDate->lt(now()->startOfDay())) {
            $futureBase = DB::query()
                ->fromSub($this->movementUnion($endDate->copy()->addDay()->toDateString(), now()->toDateString()), 'movements')
                ->leftJoin('denom as d', 'd.iddenom', '=', 'movements.iddenom')
                ->whereIn('movements.idtap', $allowedTaps);

            if ($selectedTap) {
                $futureBase->where(function ($query) use ($selectedTap) {
                    $query->where('movements.idtap', $selectedTap)
                        ->orWhere(fn ($q) => $q->where('movements.from_type', 'TAP')->where('movements.from_id', $selectedTap))
                        ->orWhere(fn ($q) => $q->where('movements.to_type', 'TAP')->where('movements.to_id', $selectedTap));
                });
            }
            if ($selectedSf) {
                $futureBase->where(function ($query) use ($selectedSf) {
                    $query->where(fn ($q) => $q->where('movements.from_type', 'SF')->where('movements.from_id', $selectedSf))
                        ->orWhere(fn ($q) => $q->where('movements.to_type', 'SF')->where('movements.to_id', $selectedSf));
                });
            }
            if ($request->filled('validity')) $futureBase->where('d.group_name', $request->input('validity'));
            if ($request->filled('iddenom')) $futureBase->where('movements.iddenom', $request->input('iddenom'));

            $futureNet = (int) ($futureBase->selectRaw("COALESCE(SUM({$sign}), 0) as net_movement")->value('net_movement') ?? 0);
        }

        $closingBalance = $currentBalance - $futureNet;
        $openingBalance = $closingBalance - (int) ($summary->net_movement ?? 0);

        $movements = (clone $base)
            ->select([
                'movements.*',
                'd.denom as denom_name',
                'd.group_name as validity',
            ])
            ->selectRaw("{$sign} as signed_qty")
            ->orderByDesc('movements.event_date')
            ->orderByDesc('movements.source_id')
            ->orderByDesc('movements.source_table')
            ->paginate(50)
            ->withQueryString();

        // The table is newest-first. Start each page from the closing balance,
        // then remove movements already displayed on previous pages.
        $previousRows = ($movements->currentPage() - 1) * $movements->perPage();
        $previousPageNet = 0;
        if ($previousRows > 0) {
            $previousMovements = (clone $base)
                ->selectRaw("{$sign} as signed_qty")
                ->orderByDesc('movements.event_date')
                ->orderByDesc('movements.source_id')
                ->orderByDesc('movements.source_table')
                ->limit($previousRows);

            $previousPageNet = (int) DB::query()
                ->fromSub($previousMovements, 'previous_movements')
                ->sum('signed_qty');
        }

        $runningBalance = $closingBalance - $previousPageNet;
        $movements->getCollection()->transform(function ($movement) use (&$runningBalance) {
            $movement->remaining_balance = $runningBalance;
            $runningBalance -= (int) $movement->signed_qty;

            return $movement;
        });

        $validities = DB::table('denom')->whereNotNull('group_name')->distinct()->orderBy('group_name')->pluck('group_name');
        $denoms = DB::table('denom')->orderBy('group_name')->orderBy('denom')->get(['iddenom', 'denom', 'group_name']);
        $sales = DB::table('idsf')->whereIn('idtap', $allowedTaps)->orderBy('idtap')->orderBy('namasf')->get(['idsf', 'namasf', 'idtap']);

        return view('stock-journey.index', [
            'mode' => $mode,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'allowedTaps' => $allowedTaps,
            'validities' => $validities,
            'denoms' => $denoms,
            'sales' => $sales,
            'salesById' => $sales->keyBy('idsf'),
            'summary' => $summary,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'movements' => $movements,
        ]);
    }

    private function movementUnion(string $startDate, string $endDate)
    {
        $do = DB::table('masuk as x')
            ->where('x.pengirim', 'DO')->whereBetween('x.tgl', [$startDate, $endDate])
            ->selectRaw("x.tgl event_date, 'DO_MASUK' movement_type, 'OUTSIDE' from_type, 'DO' from_id, 'SF' to_type, x.penerima to_id, x.idtap, x.penerima idsf, x.iddenom, x.qty, 'masuk' source_table, x.idmasuk source_id, COALESCE(x.nomor_do, x.sn, '') note");

        $tapTransfer = DB::table('keluar as x')
            ->leftJoin('idsf as sf', 'sf.idsf', '=', 'x.pengirim')
            ->where('x.status', 0)->whereBetween('x.tgl', [$startDate, $endDate])
            ->selectRaw("x.tgl event_date, CASE WHEN sf.idsf IS NULL THEN 'TRANSFER_TAP' ELSE 'RETUR_BO' END movement_type, CASE WHEN sf.idsf IS NULL THEN 'TAP' ELSE 'SF' END from_type, x.pengirim from_id, 'TAP' to_type, x.penerima to_id, COALESCE(sf.idtap, x.idtap, x.penerima) idtap, sf.idsf idsf, x.iddenom, x.qty, 'keluar' source_table, x.idkeluar source_id, COALESCE(x.tambahanket, x.sn, '') note");

        $toSf = DB::table('masuksf as x')->whereBetween('x.tgl', [$startDate, $endDate])
            ->selectRaw("x.tgl event_date, 'TAP_KE_SF' movement_type, 'TAP' from_type, x.idtap from_id, 'SF' to_type, x.idsf to_id, x.idtap, x.idsf, x.iddenom, x.qty, 'masuksf' source_table, x.idmasuk source_id, COALESCE(x.sn, '') note");

        $returSf = DB::table('retursf as x')->whereBetween('x.tgl', [$startDate, $endDate])
            ->selectRaw("x.tgl event_date, 'RETUR_SF' movement_type, 'SF' from_type, x.idsf from_id, 'TAP' to_type, x.idtap to_id, x.idtap, x.idsf, x.iddenom, x.qty, 'retursf' source_table, x.idretur source_id, COALESCE(x.tambahket, x.ketvf, x.sn, '') note");

        $outSf = DB::table('keluarsf as x')->whereBetween('x.tgl', [$startDate, $endDate])
            ->selectRaw("x.tgl event_date, 'KELUAR_SF' movement_type, 'SF' from_type, x.idsf from_id, 'OUTSIDE' to_type, 'PELANGGAN' to_id, x.idtap, x.idsf, x.iddenom, x.qty, 'keluarsf' source_table, x.idkeluar source_id, COALESCE(x.tambahanket, '') note");

        $injectOut = DB::table('injectvf as x')->whereBetween('x.tgl', [$startDate, $endDate])
            ->selectRaw("x.tgl event_date, 'KONVERSI_KELUAR' movement_type, 'TAP' from_type, x.idtap from_id, 'CONVERSION' to_type, x.idtap to_id, x.idtap, NULL idsf, COALESCE(x.kategori, 'SEGEL') iddenom, x.qty, 'injectvf' source_table, x.idinject source_id, COALESCE(x.sn, '') note");

        $injectIn = DB::table('injectvf as x')->whereBetween('x.tgl', [$startDate, $endDate])
            ->selectRaw("x.tgl event_date, 'KONVERSI_MASUK' movement_type, 'CONVERSION' from_type, x.idtap from_id, 'TAP' to_type, x.idtap to_id, x.idtap, NULL idsf, x.iddenom, x.qty, 'injectvf' source_table, x.idinject source_id, COALESCE(x.sn, '') note");

        $damaged = DB::table('returvfrusak as x')->whereBetween('x.tgl', [$startDate, $endDate])
            ->selectRaw("x.tgl event_date, 'VOUCHER_RUSAK' movement_type, 'TAP' from_type, x.idtap from_id, 'OUTSIDE' to_type, 'RUSAK' to_id, x.idtap, NULL idsf, x.iddenom, x.qty, 'returvfrusak' source_table, x.idrusak source_id, COALESCE(x.ketlain, x.ketvf, x.sn, '') note");

        $adjustmentDelta = "(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(x.new_values, '$.stock')), '0') AS SIGNED) - CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(x.old_values, '$.stock')), '0') AS SIGNED))";
        $adjustmentType = "CASE WHEN x.module = 'Stok Petugas' THEN 'SF' ELSE 'TAP' END";
        $adjustments = DB::table('logs as x')
            ->where('x.action', 'STOCK ADJUSTMENT')
            ->whereBetween(DB::raw('DATE(x.created_at)'), [$startDate, $endDate])
            ->whereRaw("{$adjustmentDelta} <> 0")
            ->selectRaw("DATE(x.created_at) event_date, 'ADJUSTMENT' movement_type,
                CASE WHEN {$adjustmentDelta} > 0 THEN 'OUTSIDE' ELSE {$adjustmentType} END from_type,
                CASE WHEN {$adjustmentDelta} > 0 THEN 'KOREKSI' ELSE SUBSTRING_INDEX(x.record_id, ':', 1) END from_id,
                CASE WHEN {$adjustmentDelta} > 0 THEN {$adjustmentType} ELSE 'OUTSIDE' END to_type,
                CASE WHEN {$adjustmentDelta} > 0 THEN SUBSTRING_INDEX(x.record_id, ':', 1) ELSE 'KOREKSI' END to_id,
                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(x.new_values, '$.idtap')), JSON_UNQUOTE(JSON_EXTRACT(x.old_values, '$.idtap'))) idtap,
                CASE WHEN x.module = 'Stok Petugas' THEN SUBSTRING_INDEX(x.record_id, ':', 1) ELSE NULL END idsf,
                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(x.new_values, '$.iddenom')), JSON_UNQUOTE(JSON_EXTRACT(x.old_values, '$.iddenom'))) iddenom,
                ABS({$adjustmentDelta}) qty, 'logs' source_table, x.id source_id,
                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(x.new_values, '$.reason')), 'Koreksi stok manual') note");

        return $do->unionAll($tapTransfer)->unionAll($toSf)->unionAll($returSf)
            ->unionAll($outSf)->unionAll($injectOut)->unionAll($injectIn)->unionAll($damaged)
            ->unionAll($adjustments);
    }

    private function allowedTaps(): array
    {
        $idtap = session('idtap');
        if ($idtap === 'CLUSTER_DUMAI') return ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING'];
        if ($idtap === 'CLUSTER_ROHIL') return ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'];
        if ($idtap !== 'SBP_DUMAI') return [$idtap];
        return DB::table('kodetap')->orderBy('idtap')->pluck('idtap')->all();
    }

    private function currentBalance(
        string $mode,
        array $allowedTaps,
        ?string $selectedTap,
        ?string $selectedSf,
        ?string $validity,
        ?string $iddenom
    ): int {
        $tapQuery = DB::table('stockawaltap as stock')
            ->join('denom as d', 'd.iddenom', '=', 'stock.iddenom')
            ->whereIn('stock.idtap', $allowedTaps);
        $sfQuery = DB::table('stockawalsf as stock')
            ->join('idsf as sf', 'sf.idsf', '=', 'stock.idsf')
            ->join('denom as d', 'd.iddenom', '=', 'stock.iddenom')
            ->whereIn('sf.idtap', $allowedTaps);

        if ($selectedTap) {
            $tapQuery->where('stock.idtap', $selectedTap);
            $sfQuery->where('sf.idtap', $selectedTap);
        }
        if ($selectedSf) $sfQuery->where('stock.idsf', $selectedSf);
        if ($validity) {
            $tapQuery->where('d.group_name', $validity);
            $sfQuery->where('d.group_name', $validity);
        }
        if ($iddenom) {
            $tapQuery->where('stock.iddenom', $iddenom);
            $sfQuery->where('stock.iddenom', $iddenom);
        }

        $tapBalance = (int) $tapQuery->sum('stock.stock');
        $sfBalance = (int) $sfQuery->sum('stock.stock');

        return match ($mode) {
            'tap' => $tapBalance,
            'sf' => $sfBalance,
            default => $selectedSf ? $sfBalance : $tapBalance + $sfBalance,
        };
    }

    private function safeDate(?string $value, Carbon $fallback): Carbon
    {
        try {
            return Carbon::parse($value ?: $fallback)->startOfDay()->min(now()->startOfDay());
        } catch (\Throwable $e) {
            return $fallback->copy()->startOfDay();
        }
    }
}
