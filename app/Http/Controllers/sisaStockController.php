<?php

namespace App\Http\Controllers;

use App\Exports\StockGudangExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;


class sisaStockController extends Controller
{
    public function index(Request $request)
    {
        $idtap = session('idtap');
        $initialMode = in_array($request->query('mode'), ['all', 'tap', 'sf'], true)
            ? $request->query('mode')
            : 'all';
        $date = $request->input('date', date('Y-m-d'));
        try {
            $date = Carbon::parse($date)->toDateString();
        } catch (\Throwable $e) {
            $date = date('Y-m-d');
        }

        $denoms = $this->stockDenoms()->get();
        $groups = $this->buildDenomGroups($denoms);

        return view('sisastock', compact('idtap', 'date', 'groups', 'initialMode'));
    }

    public function data(Request $request)
    {
        $idtap = session('idtap');
        $mode = in_array($request->input('mode'), ['all', 'tap', 'sf'], true)
            ? $request->input('mode')
            : 'all';
        $targetDateInput = $request->input('date');
        
        if ($targetDateInput) {
            try {
                // Prioritaskan format Y-m-d dari input type="date"
                $targetDate = Carbon::parse($targetDateInput)->toDateString();
            } catch (\Exception $e) {
                $targetDate = date('Y-m-d');
            }
        } else {
            $targetDate = date('Y-m-d');
        }
        
        $today = date('Y-m-d');

        // ============================================
        // 1. GET TRUE CURRENT STOCK
        // ============================================
        $tapStock = [];
        $sfStock = [];
        $sfStockByTapDenom = [];
        $gudang = DB::table('stockawaltap')->select('idtap', 'iddenom', 'stock');
        $sf = DB::table('stockawalsf as sf')
            ->join('idsf', 'sf.idsf', '=', 'idsf.idsf')
            ->select('idsf.idtap', 'idsf.idsf', 'idsf.namasf', 'sf.iddenom', 'sf.stock');

        $applyFilter = function ($q, $col = 'idtap') use ($idtap) {
            if ($idtap === 'CLUSTER_DUMAI') {
                $q->whereIn($col, ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING']);
            } elseif ($idtap === 'CLUSTER_ROHIL') {
                $q->whereIn($col, ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG']);
            } elseif ($idtap !== 'SBP_DUMAI') {
                $q->where($col, $idtap);
            }
        };

        $applyFilter($gudang);
        $applyFilter($sf, 'idsf.idtap');

        foreach ($gudang->get() as $s) {
            $key = $s->idtap . '|' . $s->iddenom;
            $tapStock[$key] = ($tapStock[$key] ?? 0) + $s->stock;
        }
        foreach ($sf->get() as $s) {
            $key = $s->idtap . '|' . $s->idsf . '|' . $s->iddenom;
            $sfStock[$key] = ($sfStock[$key] ?? 0) + $s->stock;
        }

        // Jika targetDate < Hari Ini, kita REVERSE mutasi yang terjadi setelahnya.
        if ($targetDate < $today) {
            // Kita cari transaksi yang terjadi SETELAH target date
            // Karena ini reverse:
            // - Yang tadinya "masuk/penambah" -> dikurangi (-)
            // - Yang tadinya "keluar/pengurang" -> ditambah (+)
            $afterDate = Carbon::parse($targetDate)->addDay()->toDateString();

            // REVERSE PENAMBAH -> Jadi PENGURANG (-)

            // a. Terima dari TAP Lain (status 0)
            $terimaTap = DB::table('keluar')
                ->select('penerima as idtap', 'iddenom', DB::raw('SUM(qty) as total'))
                ->where('status', 0)
                ->where('tgl', '>=', $afterDate);
            $applyFilter($terimaTap, 'penerima');
            foreach ($terimaTap->groupBy('penerima', 'iddenom')->get() as $r) {
                $key = $r->idtap . '|' . $r->iddenom;
                $tapStock[$key] = ($tapStock[$key] ?? 0) - $r->total;
            }

            // b. Inject PV (Tujuan Paket)
            $injectPv = DB::table('injectvf')
                ->select('idtap', 'iddenom', DB::raw('SUM(qty) as total'))
                ->where('tgl', '>=', $afterDate);
            $applyFilter($injectPv);
            foreach ($injectPv->groupBy('idtap', 'iddenom')->get() as $r) {
                $key = $r->idtap . '|' . $r->iddenom;
                $tapStock[$key] = ($tapStock[$key] ?? 0) - $r->total;
            }

            // c. DO Masuk
            $doMasuk = DB::table('masuk')
                ->select('idtap', 'penerima as idsf', 'iddenom', DB::raw('SUM(qty) as total'))
                ->where('pengirim', 'DO')
                ->where('tgl', '>=', $afterDate);
            $applyFilter($doMasuk);
            foreach ($doMasuk->groupBy('idtap', 'penerima', 'iddenom')->get() as $r) {
                $key = $r->idtap . '|' . $r->idsf . '|' . $r->iddenom;
                $sfStock[$key] = ($sfStock[$key] ?? 0) - $r->total;
            }

            // Distribusi TAP ke SF: kembalikan stok ke TAP dan kurangi stok SF.
            $masukSf = DB::table('masuksf')
                ->select('idtap', 'idsf', 'iddenom', DB::raw('SUM(qty) as total'))
                ->where('tgl', '>=', $afterDate);
            $applyFilter($masukSf);
            foreach ($masukSf->groupBy('idtap', 'idsf', 'iddenom')->get() as $r) {
                $tapKey = $r->idtap . '|' . $r->iddenom;
                $sfKey = $r->idtap . '|' . $r->idsf . '|' . $r->iddenom;
                $tapStock[$tapKey] = ($tapStock[$tapKey] ?? 0) + $r->total;
                $sfStock[$sfKey] = ($sfStock[$sfKey] ?? 0) - $r->total;
            }

            // Retur SF ke TAP: kurangi kembali stok TAP dan pulihkan stok SF.
            $returSf = DB::table('retursf')
                ->select('idtap', 'idsf', 'iddenom', DB::raw('SUM(qty) as total'))
                ->where('tgl', '>=', $afterDate);
            $applyFilter($returSf);
            foreach ($returSf->groupBy('idtap', 'idsf', 'iddenom')->get() as $r) {
                $tapKey = $r->idtap . '|' . $r->iddenom;
                $sfKey = $r->idtap . '|' . $r->idsf . '|' . $r->iddenom;
                $tapStock[$tapKey] = ($tapStock[$tapKey] ?? 0) - $r->total;
                $sfStock[$sfKey] = ($sfStock[$sfKey] ?? 0) + $r->total;
            }


            // REVERSE PENGURANG -> Jadi PENAMBAH (+)

            // d. Kirim ke TAP Lain
            $kirimTap = DB::table('keluar as x')
                ->join('kodetap as tap_sender', 'tap_sender.idtap', '=', 'x.pengirim')
                ->select('x.pengirim as idtap', 'x.iddenom', DB::raw('SUM(x.qty) as total'))
                ->where('x.status', 0)
                ->where('x.tgl', '>=', $afterDate);
            $applyFilter($kirimTap, 'x.pengirim');
            foreach ($kirimTap->groupBy('x.pengirim', 'x.iddenom')->get() as $r) {
                $key = $r->idtap . '|' . $r->iddenom;
                $tapStock[$key] = ($tapStock[$key] ?? 0) + $r->total;
            }

            // Retur BO memakai tabel keluar juga, tetapi pengirimnya adalah SF.
            $kirimBo = DB::table('keluar as x')
                ->join('idsf as sender', 'sender.idsf', '=', 'x.pengirim')
                ->select('sender.idtap', 'sender.idsf', 'x.iddenom', DB::raw('SUM(x.qty) as total'))
                ->where('x.status', 0)
                ->where('x.tgl', '>=', $afterDate);
            $applyFilter($kirimBo, 'sender.idtap');
            foreach ($kirimBo->groupBy('sender.idtap', 'sender.idsf', 'x.iddenom')->get() as $r) {
                $key = $r->idtap . '|' . $r->idsf . '|' . $r->iddenom;
                $sfStock[$key] = ($sfStock[$key] ?? 0) + $r->total;
            }

            // e. Inject PV (Potong Segel)
            $injectSegel = DB::table('injectvf')
                ->join('denom', 'injectvf.iddenom', '=', 'denom.iddenom')
                ->select('injectvf.idtap', 'denom.kategori_inject as iddenom', DB::raw('SUM(injectvf.qty) as total'))
                ->whereNotNull('denom.kategori_inject')
                ->where('injectvf.tgl', '>=', $afterDate);
            $applyFilter($injectSegel, 'injectvf.idtap');
            foreach ($injectSegel->groupBy('injectvf.idtap', 'denom.kategori_inject')->get() as $r) {
                $key = $r->idtap . '|' . $r->iddenom;
                $tapStock[$key] = ($tapStock[$key] ?? 0) + $r->total;
            }

            // f. Penjualan Keluar SF
            $keluarSf = DB::table('keluarsf')
                ->select('idtap', 'idsf', 'iddenom', DB::raw('SUM(qty) as total'))
                ->where('tgl', '>=', $afterDate);
            $applyFilter($keluarSf);
            foreach ($keluarSf->groupBy('idtap', 'idsf', 'iddenom')->get() as $r) {
                $key = $r->idtap . '|' . $r->idsf . '|' . $r->iddenom;
                $sfStock[$key] = ($sfStock[$key] ?? 0) + $r->total;
            }

            // g. Retur PV Rusak
            $rusak = DB::table('returvfrusak')
                ->select('idtap', 'iddenom', DB::raw('SUM(qty) as total'))
                ->where('tgl', '>=', $afterDate);
            $applyFilter($rusak);
            foreach ($rusak->groupBy('idtap', 'iddenom')->get() as $r) {
                $key = $r->idtap . '|' . $r->iddenom;
                $tapStock[$key] = ($tapStock[$key] ?? 0) + $r->total;
            }

            // h. Koreksi stok manual: kembalikan nilai baru ke nilai sebelum adjustment.
            // Adjustment memakai created_at karena tidak memiliki kolom tanggal transaksi.
            $adjustments = DB::table('logs')
                ->where('action', 'STOCK ADJUSTMENT')
                ->where('created_at', '>=', $afterDate . ' 00:00:00')
                ->orderByDesc('created_at')
                ->get(['module', 'record_id', 'old_values', 'new_values']);

            foreach ($adjustments as $adjustment) {
                $oldValues = json_decode($adjustment->old_values ?? '{}', true) ?: [];
                $newValues = json_decode($adjustment->new_values ?? '{}', true) ?: [];
                $adjustmentTap = $newValues['idtap'] ?? $oldValues['idtap'] ?? null;
                $adjustmentDenom = $newValues['iddenom'] ?? $oldValues['iddenom'] ?? null;

                if (!$adjustmentTap || !$adjustmentDenom) {
                    continue;
                }

                $oldStock = (int) ($oldValues['stock'] ?? 0);
                $newStock = (int) ($newValues['stock'] ?? 0);
                if ($adjustment->module === 'Stok Gudang TAP') {
                    $key = $adjustmentTap . '|' . $adjustmentDenom;
                    $tapStock[$key] = ($tapStock[$key] ?? 0) + ($oldStock - $newStock);
                } elseif ($adjustment->module === 'Stok Petugas') {
                    $adjustmentSf = explode(':', (string) $adjustment->record_id)[0] ?? null;
                    if (!$adjustmentSf) continue;
                    $key = $adjustmentTap . '|' . $adjustmentSf . '|' . $adjustmentDenom;
                    $sfStock[$key] = ($sfStock[$key] ?? 0) + ($oldStock - $newStock);
                }
            }
        }

        // ============================================
        // 3. GENERATE DATATABLE FORMAT
        // ============================================
        $denoms = $this->stockDenoms()->get();
        $denomGroups = $this->buildDenomGroups($denoms);
        $taps = DB::table('kodetap')
            ->when(true, fn($q) => $applyFilter($q))
            ->pluck('idtap');
        $sales = DB::table('idsf')
            ->whereIn('idtap', $taps)
            ->orderBy('idtap')
            ->orderBy('namasf')
            ->get(['idtap', 'idsf', 'namasf']);

        foreach ($sfStock as $stockKey => $stockValue) {
            [$stockTap, , $stockDenom] = explode('|', $stockKey, 3);
            $tapDenomKey = $stockTap . '|' . $stockDenom;
            $sfStockByTapDenom[$tapDenomKey] = ($sfStockByTapDenom[$tapDenomKey] ?? 0) + $stockValue;
        }

        // O(1) lookup for the All view. Previously every TAP/denom cell scanned
        // the complete Sales Force collection again.
        $sfTotal = fn (string $tap, string $denomId): int =>
            (int) ($sfStockByTapDenom[$tap . '|' . $denomId] ?? 0);

        $finalData = [];
        $rowOwners = $mode === 'sf' ? $sales : $taps->map(fn ($tap) => (object) ['idtap' => $tap]);
        foreach ($rowOwners as $owner) {
            $t = $owner->idtap;
            $row = [
                'idtap' => $t,
                'idsf' => $mode === 'sf' ? $owner->idsf : null,
                'sf_name' => $mode === 'sf' ? $owner->namasf : '',
            ];
            $grand_total = 0;
            foreach ($denoms as $d) {
                $tapKey = $t . '|' . $d->iddenom;
                $row[$d->iddenom] = match ($mode) {
                    'tap' => (int) ($tapStock[$tapKey] ?? 0),
                    'sf' => (int) ($sfStock[$t . '|' . $owner->idsf . '|' . $d->iddenom] ?? 0),
                    default => (int) ($tapStock[$tapKey] ?? 0) + $sfTotal($t, $d->iddenom),
                };
                $grand_total += $row[$d->iddenom];
            }
            foreach ($denomGroups as $category => $validityGroups) {
                foreach ($validityGroups as $groupName => $validityDenoms) {
                    $field = 'validity_' . md5($category . '|' . $groupName);
                    $row[$field] = collect($validityDenoms)->sum(
                        fn ($denom) => (int) ($row[$denom->iddenom] ?? 0)
                    );
                    if (in_array(strtoupper((string) $category), ['REGULER', 'BYU'], true)) {
                        $row['summary_' . md5($category . '|' . $groupName)] = $row[$field];
                    }
                }
            }
            $row['grand_total'] = $grand_total;
            $row['grand_total_end'] = $grand_total;
            // Mode SF doubles as the roster view. Keep newly-created SF accounts
            // visible even before they receive their first stock allocation.
            if ($mode === 'sf' || $grand_total != 0) {
                $finalData[] = $row;
            }
        }

        // Visibility is always based on the combined All balance. A denom that
        // exists globally stays visible in TAP/SF mode even when that mode is 0.
        $activeDenoms = $denoms
            ->filter(function ($denom) use ($taps, $tapStock, $sfTotal) {
                return $taps->sum(function ($tap) use ($denom, $tapStock, $sfTotal) {
                    $key = $tap . '|' . $denom->iddenom;
                    return (int) ($tapStock[$key] ?? 0) + $sfTotal($tap, $denom->iddenom);
                }) != 0;
            })
            ->pluck('iddenom')
            ->values();

        $clusterTotals = [];
        if (auth()->user()?->username === 'admin_cluster' && in_array($mode, ['all', 'tap'], true)) {
            $clusterTapMap = [
                'CLUSTER DUMAI' => ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING'],
                'CLUSTER ROHIL' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
            ];

            foreach ($clusterTapMap as $clusterName => $clusterTaps) {
                $clusterRows = collect($finalData)->whereIn('idtap', $clusterTaps);
                if ($clusterRows->isEmpty()) continue;

                $totals = [];
                foreach ($denoms as $denom) {
                    $totals[$denom->iddenom] = (int) $clusterRows->sum($denom->iddenom);
                }
                foreach ($denomGroups as $category => $validityGroups) {
                    foreach ($validityGroups as $groupName => $validityDenoms) {
                        $key = md5($category . '|' . $groupName);
                        $totals['validity_' . $key] = (int) $clusterRows->sum('validity_' . $key);
                        if (in_array(strtoupper((string) $category), ['REGULER', 'BYU'], true)) {
                            $totals['summary_' . $key] = (int) $clusterRows->sum('summary_' . $key);
                        }
                    }
                }
                $totals['grand_total'] = (int) $clusterRows->sum('grand_total');
                $totals['grand_total_end'] = (int) $clusterRows->sum('grand_total_end');
                $clusterTotals[$clusterName] = $totals;
            }
        }

        return datatables()->of(collect($finalData))
            ->with([
                'mode' => $mode,
                'active_denoms' => $activeDenoms,
                'cluster_totals' => $clusterTotals,
            ])
            ->make(true);
    }

    public function export(Request $request, string $format)
    {
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        $mode = in_array($request->query('mode'), ['all', 'tap', 'sf'], true)
            ? $request->query('mode')
            : 'all';
        $date = $request->query('date', date('Y-m-d'));

        $dataRequest = Request::create(route('sisastock.data'), 'POST', [
            'mode' => $mode,
            'date' => $date,
            'draw' => 1,
            'start' => 0,
            'length' => -1,
            'search' => ['value' => '', 'regex' => false],
        ]);
        $dataRequest->setLaravelSession($request->session());
        $payload = $this->data($dataRequest)->getData(true);
        $records = $payload['data'] ?? [];
        $activeDenoms = collect($payload['active_denoms'] ?? [])->map(fn ($id) => (string) $id)->flip();

        $denoms = $this->stockDenoms()->get();
        $groups = $this->buildDenomGroups($denoms);
        $headings = ['NO', 'TAP'];
        if ($mode === 'sf') {
            $headings[] = 'SALES FORCE';
        }

        $columns = [];
        foreach ($groups as $category => $validityGroups) {
            foreach ($validityGroups as $validity => $items) {
                $visibleItems = collect($items)->filter(
                    fn ($denom) => $activeDenoms->has((string) $denom->iddenom)
                );
                if ($visibleItems->isEmpty()) {
                    continue;
                }
                foreach ($visibleItems as $denom) {
                    $headings[] = "{$category} {$validity} {$denom->denom}";
                    $columns[] = $denom->iddenom;
                }
                $field = 'validity_' . md5($category . '|' . $validity);
                $headings[] = "TOTAL {$category} {$validity}";
                $columns[] = $field;
            }
        }

        $headings[] = 'GRAND TOTAL';
        $columns[] = 'grand_total';

        foreach (['REGULER', 'BYU'] as $category) {
            foreach ($groups[$category] ?? [] as $validity => $items) {
                $hasActiveDenom = collect($items)->contains(
                    fn ($denom) => $activeDenoms->has((string) $denom->iddenom)
                );
                if (! $hasActiveDenom) {
                    continue;
                }
                $headings[] = "TOTAL {$category} {$validity}";
                $columns[] = 'summary_' . md5($category . '|' . $validity);
            }
        }

        $headings[] = 'GRAND TOTAL';
        $columns[] = 'grand_total_end';

        $rows = collect($records)->values()->map(function (array $record, int $index) use ($mode, $columns) {
            $row = [$index + 1, $record['idtap'] ?? ''];
            if ($mode === 'sf') {
                $row[] = $record['sf_name'] ?? '';
            }
            foreach ($columns as $column) {
                $row[] = (int) ($record[$column] ?? 0);
            }
            return $row;
        })->all();

        $safeDate = Carbon::parse($date)->toDateString();
        $filename = 'Stock_Gudang_' . strtoupper($mode) . '_' . $safeDate . '.' . $format;

        return Excel::download(
            new StockGudangExport($rows, $headings),
            $filename,
            $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
        );
    }

    private function buildDenomGroups($denoms): array
    {
        $groups = ['REGULER' => [], 'BYU' => [], 'SA' => []];
        $standardOrder = ['SEGEL', '1 HARI', '2 HARI', '3 HARI', '5 HARI', '7 HARI', '14 HARI', '28 HARI', '30 HARI', 'VOICE', 'LAINNYA', 'SA'];

        foreach ($denoms as $denom) {
            $injectCategory = strtoupper(trim((string) $denom->kategori_inject));
            $category = in_array($injectCategory, ['BYU', 'SA'], true)
                ? $injectCategory
                : 'REGULER';
            $validity = (string) ($denom->group_name ?: 'LAINNYA');
            $groups[$category][$validity][] = $denom;
        }

        foreach ($groups as $category => $validityGroups) {
            $ordered = [];
            foreach ($standardOrder as $validity) {
                if (!isset($validityGroups[$validity])) continue;
                usort($validityGroups[$validity], fn ($a, $b) => strnatcasecmp($a->denom, $b->denom));
                $ordered[$validity] = $validityGroups[$validity];
                unset($validityGroups[$validity]);
            }
            foreach ($validityGroups as $validity => $items) {
                usort($items, fn ($a, $b) => strnatcasecmp($a->denom, $b->denom));
                $ordered[$validity] = $items;
            }
            $groups[$category] = $ordered;
        }

        return array_filter($groups, fn ($validityGroups) => $validityGroups !== []);
    }

    private function stockDenoms()
    {
        return DB::table('denom')
            ->whereRaw('UPPER(COALESCE(group_name, ?)) <> ?', ['', 'VOICE'])
            ->whereRaw('UPPER(COALESCE(kategori_inject, ?)) <> ?', ['', 'ROAMAX'])
            ->orderBy('iddenom');
    }
}
