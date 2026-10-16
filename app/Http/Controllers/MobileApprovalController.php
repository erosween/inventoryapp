<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MobilePenjualan;
use App\Helpers\AuditLogger;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class MobileApprovalController extends Controller
{
    public function index(Request $request)
    {
        $idtap = session('idtap');
        $selected_date = $this->salesDate($request);
        $selected_date_label = Carbon::parse($selected_date)->locale('id')->translatedFormat('d M Y');

        // 1. Total Pending Count
        $pendingQuery = MobilePenjualan::where('status', 'pending')->whereDate('tgl', $selected_date);
        if ($idtap !== 'SBP_DUMAI') $pendingQuery->where('idtap', $idtap);
        $total_pending_count = $pendingQuery->count();

        // 2. Total Pending Amount
        $pendingAmountQuery = MobilePenjualan::where('mobile_penjualan.status', 'pending')
            ->whereDate('mobile_penjualan.tgl', $selected_date)
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select(DB::raw('SUM(mobile_penjualan.qty * COALESCE(mobile_penjualan.unit_price, denom.harga_jual, 0)) as total'));
        if ($idtap !== 'SBP_DUMAI') $pendingAmountQuery->where('mobile_penjualan.idtap', $idtap);
        $total_pending_amount = $pendingAmountQuery->first()->total ?? 0;

        // 3. Today Approved Amount
        $approvedTodayQuery = MobilePenjualan::where('mobile_penjualan.status', 'approved')
            ->whereDate('mobile_penjualan.tgl', $selected_date)
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select(DB::raw('SUM(mobile_penjualan.qty * COALESCE(mobile_penjualan.unit_price, denom.harga_jual, 0)) as total'));
        if ($idtap !== 'SBP_DUMAI') $approvedTodayQuery->where('mobile_penjualan.idtap', $idtap);
        $today_approved_amount = $approvedTodayQuery->first()->total ?? 0;

        // 4. Top SF Today (Suggestion)
        $topSfQuery = MobilePenjualan::where('mobile_penjualan.status', 'approved')
            ->whereDate('mobile_penjualan.tgl', $selected_date)
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->join('idsf', 'mobile_penjualan.idsf', '=', 'idsf.idsf')
            ->select('idsf.namasf', DB::raw('SUM(mobile_penjualan.qty * COALESCE(mobile_penjualan.unit_price, denom.harga_jual, 0)) as total'))
            ->groupBy('idsf.namasf')
            ->orderBy('total', 'desc')
            ->limit(1);
        if ($idtap !== 'SBP_DUMAI') $topSfQuery->where('mobile_penjualan.idtap', $idtap);
        $top_sf_today = $topSfQuery->first();

        // 5. List of SFs for Focus Mode
        $sfListQuery = DB::table('idsf')->orderBy('namasf');
        if ($idtap !== 'SBP_DUMAI') $sfListQuery->where('idtap', $idtap);
        $sf_list = $sfListQuery->get();

        return view('admin.mobile-approval', compact(
            'total_pending_count', 
            'total_pending_amount', 
            'today_approved_amount', 
            'top_sf_today',
            'sf_list',
            'selected_date',
            'selected_date_label'
        ));
    }

    public function getStats(Request $request)
    {
        $idtap = session('idtap');
        $idsf = $request->idsf;
        $selectedDate = $this->salesDate($request);

        // 1. Pending Count
        $pendingQuery = MobilePenjualan::where('status', 'pending')->whereDate('tgl', $selectedDate);
        if ($idtap !== 'SBP_DUMAI') $pendingQuery->where('idtap', $idtap);
        if ($idsf) $pendingQuery->where('idsf', $idsf);
        $count = $pendingQuery->count();

        // 2. Pending Amount
        $pendingAmountQuery = MobilePenjualan::where('mobile_penjualan.status', 'pending')
            ->whereDate('mobile_penjualan.tgl', $selectedDate)
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select(DB::raw('SUM(mobile_penjualan.qty * COALESCE(mobile_penjualan.unit_price, denom.harga_jual, 0)) as total'));
        if ($idtap !== 'SBP_DUMAI') $pendingAmountQuery->where('mobile_penjualan.idtap', $idtap);
        if ($idsf) $pendingAmountQuery->where('mobile_penjualan.idsf', $idsf);
        $pending_amount = $pendingAmountQuery->first()->total ?? 0;

        // 3. Approved Today
        $approvedTodayQuery = MobilePenjualan::where('mobile_penjualan.status', 'approved')
            ->whereDate('mobile_penjualan.tgl', $selectedDate)
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select(DB::raw('SUM(mobile_penjualan.qty * COALESCE(mobile_penjualan.unit_price, denom.harga_jual, 0)) as total'));
        if ($idtap !== 'SBP_DUMAI') $approvedTodayQuery->where('mobile_penjualan.idtap', $idtap);
        if ($idsf) $approvedTodayQuery->where('mobile_penjualan.idsf', $idsf);
        $approved_amount = $approvedTodayQuery->first()->total ?? 0;

        return response()->json([
            'count' => number_format($count) . ' Data',
            'pending_amount' => 'Rp ' . number_format($pending_amount, 0, ',', '.'),
            'approved_amount' => 'Rp ' . number_format($approved_amount, 0, ',', '.'),
            'date' => $selectedDate,
            'date_label' => Carbon::parse($selectedDate)->locale('id')->translatedFormat('d M Y'),
        ]);
    }

    public function getSfDetails(Request $request)
    {
        $idtap_session = session('idtap');
        $idsf = $request->idsf;
        $status = $request->status;
        $idtap_filter = $request->idtap;
        $jenis = $request->jenis;
        $selectedDate = $this->salesDate($request);

        $query = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->join('idsf as s', 'm.idsf', '=', 's.idsf')
            ->select(
                'm.id',
                'm.receipt_id',
                'm.tgl',
                'm.status',
                'm.iddenom',
                'd.denom',
                'm.qty',
                DB::raw('COALESCE(m.unit_price, d.harga_jual, 0) as harga_jual'),
                DB::raw('(m.qty * COALESCE(m.unit_price, d.harga_jual, 0)) as total_setoran')
            )
            ->where('m.idsf', $idsf)
            ->whereDate('m.tgl', $selectedDate);

        if ($idtap_session !== 'SBP_DUMAI') $query->where('m.idtap', $idtap_session);
        if ($idtap_filter) $query->where('m.idtap', $idtap_filter);
        if ($status) $query->where('m.status', $status);
        
        if ($jenis == 'SA') {
            $query->where(function ($categoryQuery) {
                $categoryQuery->whereRaw("UPPER(COALESCE(d.kategori_inject, '')) = 'SA'")
                    ->orWhereRaw("UPPER(COALESCE(d.group_name, '')) = 'SA'");
            });
        } elseif ($jenis == 'VOUCHER FISIK') {
            $query->where(function ($categoryQuery) {
                $categoryQuery->whereRaw("UPPER(COALESCE(d.kategori_inject, '')) <> 'SA'")
                    ->whereRaw("UPPER(COALESCE(d.group_name, '')) <> 'SA'");
            });
        }

        $sales = $query->orderBy('d.denom')->orderBy('m.tgl')->get();
        $total_amount = $sales->sum('total_setoran');
        $receiptIds = $sales->pluck('receipt_id')->filter()->unique()->values();
        $consignmentRows = $receiptIds->isEmpty()
            ? collect()
            : DB::table('surya_sa_consignment_numbers as c')
                ->leftJoin('appsdumais as o', 'o.id_outlet', '=', 'c.id_outlet')
                ->whereIn('c.receipt_id', $receiptIds)
                ->where('c.idsf', $idsf)
                ->whereNotIn('c.status', ['draft'])
                ->when($idtap_session !== 'SBP_DUMAI', fn ($consignmentQuery) => $consignmentQuery->where('c.idtap', $idtap_session))
                ->when($idtap_filter, fn ($consignmentQuery) => $consignmentQuery->where('c.idtap', $idtap_filter))
                ->orderBy('o.nama_outlet')
                ->orderBy('c.serial_number')
                ->get([
                    'c.id_outlet',
                    'c.serial_number',
                    'c.status',
                    DB::raw('COALESCE(o.nama_outlet, c.id_outlet) as nama_outlet'),
                ]);
        $items = $sales->groupBy('iddenom')->map(function ($denomSales) {
            $firstDate = $denomSales->min('tgl');
            $lastDate = $denomSales->max('tgl');

            return [
                'ids' => $denomSales->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'produk' => $denomSales->first()->denom,
                'transaction_count' => $denomSales->count(),
                'date_label' => $firstDate === $lastDate
                    ? date('d/m/Y', strtotime($firstDate))
                    : date('d/m/Y', strtotime($firstDate)).' - '.date('d/m/Y', strtotime($lastDate)),
                'qty' => number_format($denomSales->sum('qty')),
                'total' => 'Rp '.number_format($denomSales->sum('total_setoran'), 0, ',', '.'),
            ];
        })->values();

        return response()->json([
            'sf_name' => DB::table('idsf')->where('idsf', $idsf)->value('namasf'),
            'total_amount' => 'Rp ' . number_format($total_amount, 0, ',', '.'),
            'count' => $items->count(),
            'transaction_count' => $sales->count(),
            'status' => $status,
            'idtap' => $idtap_filter,
            'jenis' => $jenis,
            'date' => $selectedDate,
            'items' => $items,
            'consignment' => [
                'count' => $consignmentRows->count(),
                'outlets' => $consignmentRows->groupBy('id_outlet')->map(function ($rows) {
                    return [
                        'id_outlet' => $rows->first()->id_outlet,
                        'nama_outlet' => $rows->first()->nama_outlet,
                        'numbers' => $rows->pluck('serial_number')->values()->all(),
                    ];
                })->values(),
            ],
        ]);
    }

    public function data(Request $request)
    {
        $idtap = session('idtap');
        $idsf = $request->idsf;
        $status = $request->status;
        $selectedDate = $this->salesDate($request);
        
        $query = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->join('idsf as s', 'm.idsf', '=', 's.idsf')
            ->select(
                'm.idsf',
                's.namasf',
                'm.idtap',
                'm.status',
                DB::raw("CASE WHEN UPPER(COALESCE(d.kategori_inject, '')) = 'SA' OR UPPER(COALESCE(d.group_name, '')) = 'SA' THEN 'SA' ELSE 'VOUCHER FISIK' END as jenis_paket"),
                DB::raw('COUNT(m.id) as trans_count'),
                DB::raw('SUM(m.qty) as total_qty'),
                DB::raw('SUM(m.qty * COALESCE(m.unit_price, d.harga_jual, 0)) as total_rupiah')
            )
            ->whereDate('m.tgl', $selectedDate)
            // Keep the computed package type in GROUP BY as an expression.
            // Some MySQL configurations reject SELECT aliases here, which
            // makes DataTables receive a 500 response and show only "Ajax error".
            ->groupBy(
                'm.idsf',
                's.namasf',
                'm.idtap',
                'm.status',
                DB::raw("CASE WHEN UPPER(COALESCE(d.kategori_inject, '')) = 'SA' OR UPPER(COALESCE(d.group_name, '')) = 'SA' THEN 'SA' ELSE 'VOUCHER FISIK' END")
            );

        if ($idtap !== 'SBP_DUMAI') {
            $query->where('m.idtap', $idtap);
        }

        if ($idsf) {
            $query->where('m.idsf', $idsf);
        }

        if ($status) {
            $query->where('m.status', $status);
        }

        return DataTables::of($query)
            ->filterColumn('namasf', function($query, $keyword) {
                $query->where('s.namasf', 'LIKE', "%{$keyword}%");
            })
            ->editColumn('namasf', function($row) {
                return '<a href="#" class="fw-bold text-indigo tinjau-btn" data-idsf="'.$row->idsf.'" data-namasf="'.$row->namasf.'" data-status="'.$row->status.'" data-idtap="'.$row->idtap.'" data-jenis="'.$row->jenis_paket.'">'.strtoupper($row->namasf).'</a>';
            })
            ->editColumn('total_qty', fn($r) => number_format($r->total_qty))
            ->editColumn('total_rupiah', fn($r) => 'Rp ' . number_format($r->total_rupiah, 0, ',', '.'))
            ->editColumn('status', function($r) {
                $badges = [
                    'pending' => 'badge-warning',
                    'approved' => 'badge-success',
                    'rejected' => 'badge-danger'
                ];
                $badge = $badges[$r->status] ?? 'badge-secondary';
                return '<span class="badge ' . $badge . ' badge-pill">' . strtoupper($r->status) . '</span>';
            })
            ->addColumn('action', function ($row) {
                return '
                    <button type="button" class="btn btn-indigo btn-round btn-sm tinjau-btn" 
                            data-idsf="'.$row->idsf.'" 
                            data-namasf="'.$row->namasf.'"
                            data-status="'.$row->status.'"
                            data-idtap="'.$row->idtap.'"
                            data-jenis="'.$row->jenis_paket.'">
                        Lihat Rekap <i class="fas fa-chevron-right ms-1"></i>
                    </button>
                ';
            })
            ->rawColumns(['namasf', 'status', 'action'])
            ->make(true);
    }

    private function salesDate(Request $request): string
    {
        $value = trim((string) $request->input('date', now()->toDateString()));

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);

            if ($date && $date->format('Y-m-d') === $value) {
                return $value;
            }
        } catch (\Throwable $e) {
            // Invalid filters fall back to today instead of breaking DataTables.
        }

        return now()->toDateString();
    }

    public function approve(Request $request)
    {
        $id = $request->id;
        
        try {
            DB::transaction(function () use ($id) {
                $sale = DB::table('mobile_penjualan')->where('id', $id)->lockForUpdate()->first();
                
                if (!$sale) throw new \Exception('Data tidak ditemukan');
                if ($sale->status !== 'pending') throw new \Exception('Data sudah diproses');
                if (session('idtap') !== 'SBP_DUMAI' && $sale->idtap !== session('idtap')) {
                    throw new \Exception('Transaksi ini bukan milik TAP Anda.');
                }

                // Stok fisik baru dipotong saat admin menyetujui penjualan.
                // Marker lama tetap dihormati untuk transaksi transisi agar tidak
                // terjadi pemotongan dua kali saat proses deployment berlangsung.
                if (! $sale->stock_reserved_at) {
                    $denom = DB::table('denom')->where('iddenom', $sale->iddenom)->first();
                    $stockSf = DB::table('stockawalsf')
                        ->where('idsf', $sale->idsf)
                        ->where('iddenom', $sale->iddenom)
                        ->lockForUpdate()
                        ->first();

                    if (! $stockSf || (int) $stockSf->stock < (int) $sale->qty) {
                        $denomName = $denom->denom ?? $sale->iddenom;
                        throw new \Exception("Stok SF untuk {$denomName} tidak mencukupi. Sisa: " . ($stockSf->stock ?? 0));
                    }

                    DB::table('stockawalsf')
                        ->where('idsf', $sale->idsf)
                        ->where('iddenom', $sale->iddenom)
                        ->decrement('stock', (int) $sale->qty);
                }

                DB::table('keluarsf')->insert([
                    'idtap' => $sale->idtap,
                    'idsf' => $sale->idsf,
                    'iddenom' => $sale->iddenom,
                    'qty' => $sale->qty,
                    'tgl' => $sale->tgl,
                    'tambahanket' => 'Mobile Approval ID: ' . $sale->id
                ]);

                DB::table('mobile_penjualan')->where('id', $id)->update([
                    'status' => 'approved',
                    'stock_reserved_at' => $sale->stock_reserved_at ?: now(),
                    'updated_at' => now()
                ]);

                if ($sale->receipt_id && ! DB::table('mobile_penjualan')
                    ->where('receipt_id', $sale->receipt_id)
                    ->where('status', 'pending')
                    ->exists()) {
                    DB::table('surya_sa_consignment_numbers')
                        ->where('receipt_id', $sale->receipt_id)
                        ->where('status', 'consigned')
                        ->update([
                            'status' => 'approved',
                            'updated_at' => now(),
                        ]);
                }

                AuditLogger::log('APPROVE', 'Mobile Sales', $id, (array)$sale);
            });

            return response()->json(['success' => true, 'message' => 'Berhasil disetujui!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function bulkApprove(Request $request)
    {
        $ids = $request->ids;
        if (!$ids || !is_array($ids)) return response()->json(['success' => false, 'message' => 'Pilih data terlebih dahulu']);

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($ids as $id) {
            try {
                $res = $this->approve(new Request(['id' => $id]));
                $data = $res->getData();
                if ($data->success) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "ID $id: " . $data->message;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "ID $id: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => $results['success'] > 0,
            'message' => "Selesai! {$results['success']} Berhasil, {$results['failed']} Gagal.",
            'details' => $results
        ]);
    }

    public function bulkReject(Request $request)
    {
        $ids = $request->ids;
        if (!$ids || !is_array($ids)) return response()->json(['success' => false, 'message' => 'Pilih data terlebih dahulu']);

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        foreach (array_values(array_unique($ids)) as $id) {
            try {
                $this->rejectSale((int) $id);
                $results['success']++;
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = "ID {$id}: {$e->getMessage()}";
            }
        }

        return response()->json([
            'success' => $results['success'] > 0,
            'message' => "{$results['success']} transaksi ditolak; stok fisik tidak berubah. {$results['failed']} gagal.",
            'details' => $results,
        ]);
    }

    public function reject(Request $request)
    {
        $id = $request->id;
        
        try {
            $this->rejectSale((int) $id);
            
            return response()->json(['success' => true, 'message' => 'Penjualan ditolak dan stok fisik tidak berubah.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function rejectSale(int $id): void
    {
        DB::transaction(function () use ($id) {
            $sale = DB::table('mobile_penjualan')->where('id', $id)->lockForUpdate()->first();

            if (! $sale || $sale->status !== 'pending') {
                throw new \RuntimeException('Data tidak ditemukan atau sudah diproses.');
            }
            if (session('idtap') !== 'SBP_DUMAI' && $sale->idtap !== session('idtap')) {
                throw new \RuntimeException('Transaksi ini bukan milik TAP Anda.');
            }

            if ($sale->stock_reserved_at) {
                $stock = DB::table('stockawalsf')
                    ->where('idsf', $sale->idsf)
                    ->where('iddenom', $sale->iddenom)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    DB::table('stockawalsf')
                        ->where('idsf', $sale->idsf)
                        ->where('iddenom', $sale->iddenom)
                        ->increment('stock', (int) $sale->qty);
                } else {
                    DB::table('stockawalsf')->insert([
                        'idsf' => $sale->idsf,
                        'iddenom' => $sale->iddenom,
                        'stock' => (int) $sale->qty,
                    ]);
                }
            }

            AuditLogger::log('REJECT', 'Mobile Sales', $id, (array) $sale);

            $receiptId = $sale->receipt_id;
            DB::table('mobile_penjualan')->where('id', $id)->delete();

            if ($receiptId) {
                $receiptTotals = DB::table('mobile_penjualan')
                    ->where('receipt_id', $receiptId)
                    ->selectRaw('COUNT(*) as item_count, COALESCE(SUM(qty), 0) as total_qty, COALESCE(SUM(qty * COALESCE(unit_price, 0)), 0) as total_amount')
                    ->first();

                if ((int) $receiptTotals->item_count === 0) {
                    DB::table('surya_sa_consignment_numbers')
                        ->where('receipt_id', $receiptId)
                        ->delete();
                    DB::table('mobile_sales_receipts')->where('id', $receiptId)->delete();
                } else {
                    DB::table('mobile_sales_receipts')->where('id', $receiptId)->update([
                        'total_qty' => (int) $receiptTotals->total_qty,
                        'total_amount' => (int) $receiptTotals->total_amount,
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
}
