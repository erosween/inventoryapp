<?php

namespace App\Http\Controllers;

use App\Helpers\TapFilter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReturSFExport;
use Carbon\Carbon;
use App\Helpers\AuditLogger;

class ReturSfController extends Controller
{
    /* ===============================
       VIEW
    =============================== */
    public function index()
    {
        return view('retursf');
    }

    /* ===============================
       DATATABLE
    =============================== */
    public function data(Request $request)
{
    $idtap = session('idtap');

    if (!$request->daterange) {
        return datatables()->of(collect([]))->make(true);
    }

    [$start, $end] = explode(' - ', $request->daterange);

    $query = DB::table('retursf as r')
        ->join('denom as d', 'r.iddenom', '=', 'd.iddenom')
        ->join('idsf as s', 'r.idsf', '=', 's.idsf')
        ->leftJoin('idsf as destination_sf', 'r.destination_idsf', '=', 'destination_sf.idsf')
        ->select(
            'r.idretur',
            'r.tgl',
            'd.denom',
            'r.qty',
            DB::raw("CASE WHEN r.return_type = 'sf' THEN CONCAT('SF: ', COALESCE(destination_sf.namasf, r.destination_idsf)) ELSE CONCAT('Gudang: ', r.idtap) END AS idtap"),
            's.namasf',
            'r.sn',
            'r.ketvf',
            'r.tambahket',
            'r.iddenom',
            'r.idsf'
        )
        ->whereBetween('r.tgl', [$start . ' 00:00:00', $end . ' 23:59:59']);

    TapFilter::apply($query, 'r.idtap');

     return datatables()
    ->of($query)
    ->editColumn('tgl', fn($r) => Carbon::parse($r->tgl)->format('Y-m-d'))

    // 🔎 search denom (join table)
    ->filterColumn('denom', function ($q, $keyword) {
        $q->whereRaw("LOWER(d.denom) LIKE ?", ["%".strtolower($keyword)."%"]);
    })

    // 🔎 search nama SF (join table)
    ->filterColumn('namasf', function ($q, $keyword) {
        $q->whereRaw("LOWER(s.namasf) LIKE ?", ["%".strtolower($keyword)."%"]);
    })

    ->addColumn('action', function ($row) {
        $btnEdit = '
            <a href="'.url('retursf/edit/'.$row->idretur).'" class="btn btn-link text-primary p-0 action-edit-icon" title="Edit" aria-label="Edit">
                <i class="fas fa-edit fa-lg" aria-hidden="true"></i>
            </a>
        ';

        if (!auth()->user()->hasClusterAdminAccess()) {
            return '<div class="d-flex align-items-center justify-content-center">' . $btnEdit . '</div>';
        }

        $btnDelete = '
            <form action="'.url('retursf/'.$row->idretur).'" 
                method="POST" 
                class="form-delete d-inline">
                '.csrf_field().'
                <button type="submit" class="btn btn-link text-danger p-0" title="Delete">
                    <i class="fas fa-trash-alt fa-lg"></i>
                </button>
            </form>
        ';

        return '<div class="d-flex align-items-center justify-content-center">' . $btnEdit . $btnDelete . '</div>';
    })
    ->rawColumns(['action'])
    ->make(true);

}


    /* ===============================
       FORM
    =============================== */
    public function form()
    {
        $idtap = session('idtap');

        $tap = DB::table('kodetap')
            ->when($idtap !== 'SBP_DUMAI', fn ($q) => $q->where('idtap', $idtap))
            ->get();

        $denom = DB::table('denom')->get();
        $salesForces = \App\Support\SfVisibility::visible(DB::table('idsf'))
            ->whereRaw("UPPER(idsf) LIKE 'SF%'")
            ->whereRaw("UPPER(TRIM(namasf)) NOT LIKE 'N/A%'")
            ->orderBy('namasf')->get(['idsf', 'namasf', 'idtap']);

        return view('form.form-retursf', compact('tap','denom','idtap','salesForces'));
    }

    /* ===============================
       SIMPAN
    =============================== */
    public function store(Request $request)
    {
        if (!is_array($request->input('items'))) {
            $request->merge(['items' => [[
                'iddenom' => $request->iddenom,
                'sn_start' => $request->sn_start ?: $request->sn,
                'sn_end' => $request->sn_end,
                'tambahket' => $request->tambahket,
            ]]]);
        }

        $validated = $request->validate([
            'tgl' => 'required|date',
            'idtap' => 'required|exists:kodetap,idtap',
            'idsf' => 'required|exists:idsf,idsf',
            'return_type' => 'required|in:warehouse,sf',
            'destination_idsf' => 'required_if:return_type,sf|nullable|different:idsf|exists:idsf,idsf',
            'ketvf' => 'required|in:OK,RUSAK,MATI',
            'items' => 'required|array|min:1',
            'items.*.iddenom' => 'required|exists:denom,iddenom',
            'items.*.qty' => 'nullable|integer|min:1|max:2000000',
            'items.*.sn_start' => 'required|string|max:24',
            'items.*.sn_end' => 'required|string|max:24',
            'items.*.tambahket' => 'required|string|max:500',
        ]);
        $validated['items'] = $this->normalizeSerialRanges($validated['items']);
        usort($validated['items'], fn ($a, $b) => strcmp($a['iddenom'], $b['iddenom']));

        if (session('idtap') !== 'SBP_DUMAI' && $validated['idtap'] !== session('idtap')) {
            abort(403);
        }
        abort_unless(
            \App\Support\SfVisibility::visible(DB::table('idsf'))->where('idsf', $validated['idsf'])->whereRaw('UPPER(TRIM(idtap)) = ?', [mb_strtoupper(trim($validated['idtap']))])->exists(),
            422,
            'Petugas tidak sesuai dengan TAP.'
        );
        if ($validated['return_type'] === 'sf') {
            abort_unless(\App\Support\SfVisibility::visible(DB::table('idsf'))
                ->where('idsf', $validated['destination_idsf'])
                ->whereRaw("UPPER(idsf) LIKE 'SF%'")
                ->whereRaw("UPPER(TRIM(namasf)) NOT LIKE 'N/A%'")
                ->where('idsf', '!=', $validated['idsf'])->exists(), 422, 'Sales Force tujuan tidak valid.');
        } else {
            $validated['destination_idsf'] = null;
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

                if ($stockSf < $requestedQty) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => "Total qty {$iddenom} tidak mencukupi. Diminta: {$requestedQty}, tersedia: {$stockSf}.",
                    ]);
                }
            }

            foreach ($validated['items'] as $item) {
                $newId = DB::table('retursf')->insertGetId([
                    'tgl' => $validated['tgl'],
                    'idtap' => $validated['idtap'],
                    'idsf' => $validated['idsf'],
                    'iddenom' => $item['iddenom'],
                    'qty' => $item['qty'],
                    'sn' => $item['sn'],
                    'sn_awal' => $item['sn_start'],
                    'sn_akhir' => $item['sn_end'],
                    'ketvf' => $validated['ketvf'],
                    'tambahket' => $item['tambahket'],
                ]);

                DB::table('stockawalsf')
                    ->where('idsf', $validated['idsf'])
                    ->where('iddenom', $item['iddenom'])
                    ->decrement('stock', $item['qty']);

                if ($validated['return_type'] === 'sf') {
                    DB::table('stockawalsf')->insertOrIgnore([
                        'idsf' => $validated['destination_idsf'], 'iddenom' => $item['iddenom'], 'stock' => 0,
                    ]);
                    DB::table('stockawalsf')->where('idsf', $validated['destination_idsf'])
                        ->where('iddenom', $item['iddenom'])->increment('stock', $item['qty']);
                } else {
                    DB::table('stockawaltap')->insertOrIgnore([
                        'idtap' => $validated['idtap'], 'iddenom' => $item['iddenom'], 'stock' => 0,
                    ]);
                    DB::table('stockawaltap')->where('idtap', $validated['idtap'])
                        ->where('iddenom', $item['iddenom'])->increment('stock', $item['qty']);
                }

                AuditLogger::log('INSERT', 'Retur SF (Bulk)', $newId, null, $item + [
                    'tgl' => $validated['tgl'],
                    'idtap' => $validated['idtap'],
                    'idsf' => $validated['idsf'],
                    'return_type' => $validated['return_type'],
                    'destination_idsf' => $validated['destination_idsf'],
                    'ketvf' => $validated['ketvf'],
                ]);
                $this->syncSfStockOwnership($newId, $validated['return_type'], $validated['destination_idsf'], $validated['idtap'], $item);
            }
        });

        return redirect('retursf')->with('success', count($validated['items']) . ' denom retur SF berhasil disimpan');
    }

    /* ===============================
       EDIT
    =============================== */
    public function edit($id)
    {
        $idtap_session = session('idtap');
        $edit = DB::table('retursf')->where('idretur', $id)->first();
        
        if (!$edit) return redirect('retursf')->with('error', 'Data tidak ditemukan');

        $tap = DB::table('kodetap')
            ->when($idtap_session !== 'SBP_DUMAI', fn ($q) => $q->where('idtap', $idtap_session))
            ->get();

        $idsf = \App\Support\SfVisibility::visible(DB::table('idsf'))
            ->whereRaw('UPPER(TRIM(idtap)) = ?', [mb_strtoupper(trim((string) $edit->idtap))])
            ->orderBy('namasf')->get();
        $salesForces = \App\Support\SfVisibility::visible(DB::table('idsf'))
            ->whereRaw("UPPER(idsf) LIKE 'SF%'")->whereRaw("UPPER(TRIM(namasf)) NOT LIKE 'N/A%'")
            ->orderBy('namasf')->get(['idsf', 'namasf', 'idtap']);
        $denom = DB::table('denom')->get();

        return view('form.form-edit-retursf', compact('edit', 'tap', 'idsf', 'denom', 'idtap_session', 'salesForces'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'tgl' => 'required|date',
            'idtap' => 'required|exists:kodetap,idtap',
            'idsf' => 'required|exists:idsf,idsf',
            'return_type' => 'required|in:warehouse,sf',
            'destination_idsf' => 'required_if:return_type,sf|nullable|different:idsf|exists:idsf,idsf',
            'iddenom' => 'required|exists:denom,iddenom',
            'sn_start' => 'required|string|max:24',
            'sn_end' => 'required|string|max:24',
            'qty' => 'nullable|integer|min:1|max:2000000',
            'ketvf' => 'required|in:OK,RUSAK,MATI',
            'tambahket' => 'required|string|max:500',
        ]);
        $item = $this->normalizeSerialRanges([[
            'iddenom' => $validated['iddenom'],
            'sn_start' => $validated['sn_start'],
            'sn_end' => $validated['sn_end'] ?? '',
            'qty' => $validated['qty'] ?? null,
            'tambahket' => $validated['tambahket'],
        ]])[0];

        if (session('idtap') !== 'SBP_DUMAI' && $validated['idtap'] !== session('idtap')) abort(403);
        abort_unless(\App\Support\SfVisibility::visible(DB::table('idsf'))
            ->where('idsf', $validated['idsf'])
            ->whereRaw('UPPER(TRIM(idtap)) = ?', [mb_strtoupper(trim($validated['idtap']))])->exists(),
            422, 'Petugas tidak sesuai dengan TAP.');
        if ($validated['return_type'] === 'sf') {
            abort_unless(\App\Support\SfVisibility::visible(DB::table('idsf'))
                ->where('idsf', $validated['destination_idsf'])->where('idsf', '!=', $validated['idsf'])
                ->whereRaw("UPPER(idsf) LIKE 'SF%'")->whereRaw("UPPER(TRIM(namasf)) NOT LIKE 'N/A%'")->exists(),
                422, 'Sales Force tujuan tidak valid.');
        } else $validated['destination_idsf'] = null;

        DB::transaction(function () use ($validated, $item, $id) {
            $oldData = DB::table('retursf')->where('idretur', $id)->lockForUpdate()->first();
            if (!$oldData) abort(404);
            $this->reverseReturnBalance($oldData);
            $this->applyReturnBalance($validated['return_type'], $validated['idsf'], $validated['destination_idsf'], $validated['idtap'], $item['iddenom'], $item['qty']);
            $this->syncSfStockOwnership((int) $id, $validated['return_type'], $validated['destination_idsf'], $validated['idtap'], $item);

            // 3. Update record
            DB::table('retursf')->where('idretur', $id)->update([
                'tgl' => $validated['tgl'], 'idtap' => $validated['idtap'], 'idsf' => $validated['idsf'],
                'iddenom' => $item['iddenom'], 'qty' => $item['qty'], 'sn' => $item['sn'],
                'sn_awal' => $item['sn_start'], 'sn_akhir' => $item['sn_end'],
                'return_type' => $validated['return_type'], 'destination_idsf' => $validated['destination_idsf'],
                'ketvf' => $validated['ketvf'], 'tambahket' => $item['tambahket'],
            ]);

            // 📝 LOG
            AuditLogger::log('UPDATE', 'Retur SF', $id, (array)$oldData, $validated + ['qty' => $item['qty'], 'sn' => $item['sn']]);
        });

        return redirect('retursf')->with('success', 'Data retur berhasil diperbarui');
    }

    private function normalizeSerialRanges(array $items): array
    {
        $denoms = DB::table('denom')->whereIn('iddenom', collect($items)->pluck('iddenom')->unique())
            ->get(['iddenom', 'product', 'product_group', 'kategori_inject', 'group_name'])->keyBy('iddenom');
        foreach ($items as $index => &$item) {
            $denom = $denoms->get($item['iddenom']);
            $id = strtoupper(trim((string) ($denom->iddenom ?? '')));
            $product = strtoupper(trim((string) ($denom->product ?? '')));
            $isSa = in_array($product, ['PV', 'SA'], true) ? $product === 'SA'
                : (in_array(strtoupper(trim((string) ($denom->product_group ?? ''))), ['SA', 'SP'], true)
                    || strtoupper(trim((string) ($denom->kategori_inject ?? '')))==='SA'
                    || strtoupper(trim((string) ($denom->group_name ?? '')))==='SA' || str_starts_with($id, 'SP'));
            $start = trim((string) ($item['sn_start'] ?? ''));
            $end = trim((string) ($item['sn_end'] ?? ''));
            $pattern = $isSa ? '/^0\d{15}$/' : '/^\d{12}$/';
            if (!preg_match($pattern, $start) || !preg_match($pattern, $end)) {
                throw \Illuminate\Validation\ValidationException::withMessages(["items.{$index}.sn_start" => $isSa ? 'SN awal dan akhir SA wajib 16 digit dan diawali 0.' : 'SN awal dan akhir PV wajib tepat 12 digit.']);
            }
            $qty = (int) ltrim($end, '0') - (int) ltrim($start, '0') + 1;
            if ($qty < 1 || $qty > 2_000_000) throw \Illuminate\Validation\ValidationException::withMessages(["items.{$index}.sn_end" => 'SN akhir harus lebih besar atau sama dengan SN awal; maksimal 2.000.000 SN.']);
            if (!empty($item['qty']) && (int) $item['qty'] !== $qty) throw \Illuminate\Validation\ValidationException::withMessages(["items.{$index}.sn_end" => 'Qty harus sama dengan jumlah SN di rentang.']);
            $item['sn_start'] = $start; $item['sn_end'] = $end; $item['qty'] = $qty;
            $item['sn'] = $start === $end ? $start : $start.'-'.$end;
        }
        unset($item);
        for ($i = 0; $i < count($items); $i++) for ($j = $i + 1; $j < count($items); $j++) {
            if (strlen($items[$i]['sn_start']) === strlen($items[$j]['sn_start']) && $items[$i]['sn_start'] <= $items[$j]['sn_end'] && $items[$i]['sn_end'] >= $items[$j]['sn_start']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Rentang SN antar baris tidak boleh tumpang tindih.']);
            }
        }
        return $items;
    }

    private function reverseReturnBalance(object $return): void
    {
        if (($return->return_type ?? 'warehouse') === 'sf' && $return->destination_idsf) {
            $this->moveStockBetweenSalesForces($return->destination_idsf, $return->idsf, $return->iddenom, (int) $return->qty);
            return;
        }
        $warehouseStock = DB::table('stockawaltap')->where('idtap', $return->idtap)
            ->where('iddenom', $return->iddenom)->lockForUpdate()->value('stock') ?? 0;
        if ((int) $warehouseStock < (int) $return->qty) {
            throw \Illuminate\Validation\ValidationException::withMessages(['retur' => 'Saldo gudang tidak mencukupi untuk membatalkan retur.']);
        }
        DB::table('stockawaltap')->where('idtap', $return->idtap)->where('iddenom', $return->iddenom)->decrement('stock', $return->qty);
        DB::table('stockawalsf')->where('idsf', $return->idsf)->where('iddenom', $return->iddenom)->increment('stock', $return->qty);
    }

    private function syncSfStockOwnership(int $returnId, string $type, ?string $destinationSf, string $tap, array $item): void
    {
        $filename = 'MANUAL: RETUR SF KE SF #'.$returnId;
        $oldUploads = DB::table('product_tracking_sales_stock_uploads')->where('filename', $filename)->pluck('id');
        if ($oldUploads->isNotEmpty()) {
            DB::table('product_tracking_sales_stock_ranges')->whereIn('upload_id', $oldUploads)->delete();
            DB::table('product_tracking_sales_stock_uploads')->whereIn('id', $oldUploads)->delete();
        }
        if ($type !== 'sf' || ! $destinationSf) return;

        $sf = DB::table('idsf')->where('idsf', $destinationSf)->first(['idtap']);
        $tap = (string) ($sf->idtap ?? $tap);
        $uploadId = DB::table('product_tracking_sales_stock_uploads')->insertGetId([
            'filename' => $filename, 'row_count' => 1, 'username' => auth()->user()->username,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('product_tracking_sales_stock_ranges')->insert([
            'upload_id' => $uploadId, 'sfcode' => $destinationSf, 'tap' => $tap,
            'cluster' => $this->clusterForTap($tap), 'iddenom' => $item['iddenom'],
            'serial_start' => $item['sn_start'], 'serial_end' => $item['sn_end'], 'qty' => $item['qty'],
            'keterangan' => 'Retur SF ke SF lain #'.$returnId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function clusterForTap(string $tap): string
    {
        $tap = strtoupper(trim($tap));
        if (in_array($tap, ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING'], true)) return 'DUMAI BENGKALIS';
        if (in_array($tap, ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'], true)) return 'ROKAN HILIR';
        return '—';
    }

    private function applyReturnBalance(string $type, string $sourceSf, ?string $destinationSf, string $tap, string $iddenom, int $qty): void
    {
        if ($type === 'sf' && $destinationSf) {
            $this->moveStockBetweenSalesForces($sourceSf, $destinationSf, $iddenom, $qty);
            return;
        }
        $sourceStock = DB::table('stockawalsf')->where('idsf', $sourceSf)->where('iddenom', $iddenom)
            ->lockForUpdate()->value('stock') ?? 0;
        if ((int) $sourceStock < $qty) throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Stok SF tidak mencukupi.']);
        DB::table('stockawaltap')->insertOrIgnore(['idtap' => $tap, 'iddenom' => $iddenom, 'stock' => 0]);
        DB::table('stockawalsf')->where('idsf', $sourceSf)->where('iddenom', $iddenom)->decrement('stock', $qty);
        DB::table('stockawaltap')->where('idtap', $tap)->where('iddenom', $iddenom)->increment('stock', $qty);
    }

    private function moveStockBetweenSalesForces(string $fromSf, string $toSf, string $iddenom, int $qty): void
    {
        $sourceStock = DB::table('stockawalsf')->where('idsf', $fromSf)->where('iddenom', $iddenom)
            ->lockForUpdate()->value('stock') ?? 0;
        if ((int) $sourceStock < $qty) throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Stok SF asal tidak mencukupi.']);
        DB::table('stockawalsf')->insertOrIgnore(['idsf' => $toSf, 'iddenom' => $iddenom, 'stock' => 0]);
        DB::table('stockawalsf')->where('idsf', $fromSf)->where('iddenom', $iddenom)->decrement('stock', $qty);
        DB::table('stockawalsf')->where('idsf', $toSf)->where('iddenom', $iddenom)->increment('stock', $qty);
    }

    /* ===============================
       DELETE
    =============================== */
    public function delete(Request $request, $idretur)
    {
        if (!auth()->user()->hasClusterAdminAccess()) {
            return back()->with('error', 'Akses ditolak. Hanya Admin Cluster yang boleh menghapus data.');
        }

        try {
            $deleted = DB::transaction(fn () => $this->cancelReturn((string)$idretur, 'DELETE'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with($deleted ? 'success' : 'error', $deleted
            ? 'Data retur berhasil dihapus dan stok dikembalikan ke SF.'
            : 'Data retur sudah dihapus atau tidak ditemukan.');
    }

    /** Reverse one return while the enclosing transaction holds the journal and stock locks. */
    // Pembatalan retur mengembalikan perubahan saldo. Penghapusan tunggal dan massal memakai jalur yang
    // sama.
    private function cancelReturn(string $idretur, string $action): bool
    {
        $data = DB::table('retursf')->where('idretur',$idretur)->lockForUpdate()->first();
        if (!$data) return false;
        $this->reverseReturnBalance($data);
        $this->syncSfStockOwnership((int) $idretur, 'warehouse', null, (string) $data->idtap, []);
        DB::table('retursf')->where('idretur',$idretur)->delete();
        AuditLogger::log($action,'Retur SF',$idretur,(array)$data);
        return true;
    }

    /* ===============================
       EXPORT
    =============================== */
    public function exportexcel(Request $request)
    {
        [$start, $end] = explode(' - ', $request->daterange);
        $idtap = session('idtap');

        $query = DB::table('retursf as r')
            ->join('denom as d', 'r.iddenom', '=', 'd.iddenom')
            ->join('idsf as s', 'r.idsf', '=', 's.idsf')
            ->leftJoin('idsf as destination_sf', 'r.destination_idsf', '=', 'destination_sf.idsf')
            ->select(
                'r.tgl',
                'd.denom',
                'r.qty',
                DB::raw("CASE WHEN r.return_type = 'sf' THEN CONCAT('SF: ', COALESCE(destination_sf.namasf, r.destination_idsf)) ELSE CONCAT('Gudang: ', r.idtap) END AS idtap"),
                's.namasf',
                'r.sn',
                'r.ketvf',
                'r.tambahket'
            )
            ->whereBetween('r.tgl', [$start . ' 00:00:00', $end . ' 23:59:59']);

        TapFilter::apply($query, 'r.idtap');

        return Excel::download(
            new ReturSFExport($query->get()),
            'RETUR_SF_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function bulkDelete(Request $request)
    {
        if (!auth()->user()->hasClusterAdminAccess()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak. Hanya Admin Cluster yang boleh menghapus data.']);
        }

        $data = $request->validate(['ids'=>'required|array|min:1|max:500','ids.*'=>'required|integer|min:1']);
        $ids = array_values(array_unique(array_map('intval',$data['ids'])));
        sort($ids); // Consistent journal/stock lock order for concurrent bulk requests.
        try {
            $deleted = DB::transaction(function () use ($ids) {
                $count = 0;
                foreach ($ids as $idretur) {
                    if ($this->cancelReturn((string)$idretur,'DELETE (BULK)')) $count++;
                }
                return $count;
            });
            return response()->json(['success'=>true,'message'=>$deleted.' data retur berhasil dihapus']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    public function getAllStockSf(Request $request)
    {
        $request->validate([
            'idsf' => 'required',
        ]);

        $stocks = DB::table('stockawalsf')
            ->where('idsf', $request->idsf)
            ->pluck('stock', 'iddenom');

        return response()->json($stocks);
    }
}
