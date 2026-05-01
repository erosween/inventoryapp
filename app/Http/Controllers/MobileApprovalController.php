<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MobilePenjualan;
use App\Helpers\AuditLogger;
use Yajra\DataTables\Facades\DataTables;

class MobileApprovalController extends Controller
{
    public function index()
    {
        $idtap = session('idtap');
        $today = now()->toDateString();

        // 1. Total Pending Count
        $pendingQuery = MobilePenjualan::where('status', 'pending');
        if ($idtap !== 'SBP_DUMAI') $pendingQuery->where('idtap', $idtap);
        $total_pending_count = $pendingQuery->count();

        // 2. Total Pending Amount
        $pendingAmountQuery = MobilePenjualan::where('mobile_penjualan.status', 'pending')
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select(DB::raw('SUM(mobile_penjualan.qty * COALESCE(denom.harga_jual, 0)) as total'));
        if ($idtap !== 'SBP_DUMAI') $pendingAmountQuery->where('mobile_penjualan.idtap', $idtap);
        $total_pending_amount = $pendingAmountQuery->first()->total ?? 0;

        // 3. Today Approved Amount
        $approvedTodayQuery = MobilePenjualan::where('mobile_penjualan.status', 'approved')
            ->whereDate('mobile_penjualan.updated_at', $today)
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select(DB::raw('SUM(mobile_penjualan.qty * COALESCE(denom.harga_jual, 0)) as total'));
        if ($idtap !== 'SBP_DUMAI') $approvedTodayQuery->where('mobile_penjualan.idtap', $idtap);
        $today_approved_amount = $approvedTodayQuery->first()->total ?? 0;

        // 4. Top SF Today (Suggestion)
        $topSfQuery = MobilePenjualan::where('mobile_penjualan.status', 'approved')
            ->whereDate('mobile_penjualan.updated_at', $today)
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->join('idsf', 'mobile_penjualan.idsf', '=', 'idsf.idsf')
            ->select('idsf.namasf', DB::raw('SUM(mobile_penjualan.qty * COALESCE(denom.harga_jual, 0)) as total'))
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
            'sf_list'
        ));
    }

    public function getStats(Request $request)
    {
        $idtap = session('idtap');
        $idsf = $request->idsf;
        $today = now()->toDateString();

        // 1. Pending Count
        $pendingQuery = MobilePenjualan::where('status', 'pending');
        if ($idtap !== 'SBP_DUMAI') $pendingQuery->where('idtap', $idtap);
        if ($idsf) $pendingQuery->where('idsf', $idsf);
        $count = $pendingQuery->count();

        // 2. Pending Amount
        $pendingAmountQuery = MobilePenjualan::where('mobile_penjualan.status', 'pending')
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select(DB::raw('SUM(mobile_penjualan.qty * COALESCE(denom.harga_jual, 0)) as total'));
        if ($idtap !== 'SBP_DUMAI') $pendingAmountQuery->where('mobile_penjualan.idtap', $idtap);
        if ($idsf) $pendingAmountQuery->where('mobile_penjualan.idsf', $idsf);
        $pending_amount = $pendingAmountQuery->first()->total ?? 0;

        // 3. Approved Today
        $approvedTodayQuery = MobilePenjualan::where('mobile_penjualan.status', 'approved')
            ->whereDate('mobile_penjualan.updated_at', $today)
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select(DB::raw('SUM(mobile_penjualan.qty * COALESCE(denom.harga_jual, 0)) as total'));
        if ($idtap !== 'SBP_DUMAI') $approvedTodayQuery->where('mobile_penjualan.idtap', $idtap);
        if ($idsf) $approvedTodayQuery->where('mobile_penjualan.idsf', $idsf);
        $approved_amount = $approvedTodayQuery->first()->total ?? 0;

        return response()->json([
            'count' => number_format($count) . ' Data',
            'pending_amount' => 'Rp ' . number_format($pending_amount, 0, ',', '.'),
            'approved_amount' => 'Rp ' . number_format($approved_amount, 0, ',', '.')
        ]);
    }

    public function getSfDetails(Request $request)
    {
        $idtap_session = session('idtap');
        $idsf = $request->idsf;
        $status = $request->status;
        $idtap_filter = $request->idtap;
        $jenis = $request->jenis;

        $query = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->join('idsf as s', 'm.idsf', '=', 's.idsf')
            ->leftJoin('appsdumais as o', 'm.id_outlet', '=', 'o.id_outlet')
            ->select(
                'm.id',
                'm.tgl',
                'm.status',
                'o.nama_outlet',
                'd.denom',
                'm.qty',
                'd.harga_jual',
                DB::raw('(m.qty * COALESCE(d.harga_jual, 0)) as total_setoran')
            )
            ->where('m.idsf', $idsf);

        if ($idtap_session !== 'SBP_DUMAI') $query->where('m.idtap', $idtap_session);
        if ($idtap_filter) $query->where('m.idtap', $idtap_filter);
        if ($status) $query->where('m.status', $status);
        
        if ($jenis == 'SA') {
            $query->where('d.kategori_inject', 'SA');
        } elseif ($jenis == 'VOUCHER FISIK') {
            $query->where(function($q) {
                $q->where('d.kategori_inject', '!=', 'SA')->orWhereNull('d.kategori_inject');
            });
        }

        $items = $query->get();
        $total_amount = $items->sum('total_setoran');

        return response()->json([
            'sf_name' => DB::table('idsf')->where('idsf', $idsf)->value('namasf'),
            'total_amount' => 'Rp ' . number_format($total_amount, 0, ',', '.'),
            'count' => $items->count(),
            'status' => $status,
            'idtap' => $idtap_filter,
            'jenis' => $jenis,
            'items' => $items->map(function($item) {
                return [
                    'id' => $item->id,
                    'tgl' => date('d/m/Y', strtotime($item->tgl)),
                    'status' => $item->status,
                    'outlet' => $item->nama_outlet ?? '-',
                    'produk' => $item->denom,
                    'qty' => number_format($item->qty),
                    'total' => 'Rp ' . number_format($item->total_setoran, 0, ',', '.')
                ];
            })
        ]);
    }

    public function data(Request $request)
    {
        $idtap = session('idtap');
        $idsf = $request->idsf;
        $status = $request->status;
        
        $query = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->join('idsf as s', 'm.idsf', '=', 's.idsf')
            ->select(
                'm.idsf',
                's.namasf',
                'm.idtap',
                'm.status',
                DB::raw("CASE WHEN d.kategori_inject = 'SA' THEN 'SA' ELSE 'VOUCHER FISIK' END as jenis_paket"),
                DB::raw('COUNT(m.id) as trans_count'),
                DB::raw('SUM(m.qty) as total_qty'),
                DB::raw('SUM(m.qty * COALESCE(d.harga_jual, 0)) as total_rupiah')
            )
            ->groupBy('m.idsf', 's.namasf', 'm.idtap', 'm.status', 'jenis_paket');

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
                        Tinjau Detail <i class="fas fa-chevron-right ms-1"></i>
                    </button>
                ';
            })
            ->rawColumns(['namasf', 'status', 'action'])
            ->make(true);
    }

    public function approve(Request $request)
    {
        $id = $request->id;
        
        try {
            DB::transaction(function () use ($id) {
                $sale = DB::table('mobile_penjualan')->where('id', $id)->lockForUpdate()->first();
                
                if (!$sale) throw new \Exception('Data tidak ditemukan');
                if ($sale->status !== 'pending') throw new \Exception('Data sudah diproses');

                $denom = DB::table('denom')->where('iddenom', $sale->iddenom)->first();
                $isVirtual = ($denom->group_name ?? '') === 'SA' || str_starts_with($sale->iddenom, 'SA_');

                if (!$isVirtual) {
                    // Potong Stok SF
                    $stockSf = DB::table('stockawalsf')
                        ->where('idsf', $sale->idsf)
                        ->where('iddenom', $sale->iddenom)
                        ->lockForUpdate()
                        ->first();

                    if (!$stockSf || $stockSf->stock < $sale->qty) {
                        $denomName = $denom->denom ?? $sale->iddenom;
                        throw new \Exception("Stok SF untuk {$denomName} tidak mencukupi. Sisa: " . ($stockSf->stock ?? 0));
                    }

                    DB::table('stockawalsf')
                        ->where('idsf', $sale->idsf)
                        ->where('iddenom', $sale->iddenom)
                        ->decrement('stock', $sale->qty);
                        
                    // Insert to keluarsf for historical consistency
                    DB::table('keluarsf')->insert([
                        'idtap' => $sale->idtap,
                        'idsf' => $sale->idsf,
                        'iddenom' => $sale->iddenom,
                        'qty' => $sale->qty,
                        'tgl' => $sale->tgl,
                        'tambahanket' => 'Mobile Approval ID: ' . $sale->id
                    ]);
                }

                DB::table('mobile_penjualan')->where('id', $id)->update([
                    'status' => 'approved',
                    'updated_at' => now()
                ]);

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
            'success' => true, 
            'message' => "Selesai! {$results['success']} Berhasil, {$results['failed']} Gagal.",
            'details' => $results
        ]);
    }

    public function bulkReject(Request $request)
    {
        $ids = $request->ids;
        if (!$ids || !is_array($ids)) return response()->json(['success' => false, 'message' => 'Pilih data terlebih dahulu']);

        DB::table('mobile_penjualan')->whereIn('id', $ids)->where('status', 'pending')->update([
            'status' => 'rejected',
            'updated_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => count($ids) . ' data berhasil ditolak.']);
    }

    public function reject(Request $request)
    {
        $id = $request->id;
        
        try {
            DB::table('mobile_penjualan')->where('id', $id)->update([
                'status' => 'rejected',
                'updated_at' => now()
            ]);
            
            return response()->json(['success' => true, 'message' => 'Penjualan berhasil ditolak.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
