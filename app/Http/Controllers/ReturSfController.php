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
        ->select(
            'r.idretur',
            'r.tgl',
            'd.denom',
            'r.qty',
            'r.idtap',
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

        return view('form.form-retursf', compact('tap','denom','idtap'));
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
            'ketvf' => 'required|in:OK,RUSAK,MATI',
            'items' => 'required|array|min:1',
            'items.*.iddenom' => 'required|exists:denom,iddenom',
            'items.*.qty' => 'nullable|integer|min:1|max:2000000',
            'items.*.serial_mode' => 'nullable|in:range,broken',
            'items.*.sn_start' => 'nullable|string|max:24',
            'items.*.sn_end' => 'nullable|string|max:24',
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

                DB::table('stockawaltap')->insertOrIgnore([
                    'idtap' => $validated['idtap'],
                    'iddenom' => $item['iddenom'],
                    'stock' => 0,
                ]);
                DB::table('stockawaltap')
                    ->where('idtap', $validated['idtap'])
                    ->where('iddenom', $item['iddenom'])
                    ->increment('stock', $item['qty']);

                AuditLogger::log('INSERT', 'Retur SF (Bulk)', $newId, null, $item + [
                    'tgl' => $validated['tgl'],
                    'idtap' => $validated['idtap'],
                    'idsf' => $validated['idsf'],
                    'ketvf' => $validated['ketvf'],
                ]);
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
        $denom = DB::table('denom')->get();

        return view('form.form-edit-retursf', compact('edit', 'tap', 'idsf', 'denom', 'idtap_session'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'tgl' => 'required|date',
            'idtap' => 'required|exists:kodetap,idtap',
            'idsf' => 'required|exists:idsf,idsf',
            'iddenom' => 'required|exists:denom,iddenom',
            'sn_start' => 'required|string|max:24',
            'sn_end' => 'nullable|string|max:24',
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

        DB::transaction(function () use ($validated, $item, $id) {
            $oldData = DB::table('retursf')->where('idretur', $id)->lockForUpdate()->first();
            if (!$oldData) abort(404);
            
            // 1. Rollback stok lama
            // retur: SF -> TAP. Rollback: TAP -> SF
            $stokTapOld = DB::table('stockawaltap')->where('idtap', $oldData->idtap)->where('iddenom', $oldData->iddenom)->lockForUpdate()->value('stock') ?? 0;
            if ((string) $oldData->idtap !== (string) $validated['idtap'] || (string) $oldData->iddenom !== (string) $item['iddenom'] || (string) $oldData->idsf !== (string) $validated['idsf']) {
                if ($stokTapOld < $oldData->qty) {
                    throw new \Exception('Stok TAP tidak mencukupi untuk membatalkan data lama');
                }
                DB::table('stockawaltap')->where('idtap', $oldData->idtap)->where('iddenom', $oldData->iddenom)->decrement('stock', $oldData->qty);
                DB::table('stockawalsf')->where('idsf', $oldData->idsf)->where('iddenom', $oldData->iddenom)->increment('stock', $oldData->qty);
            } else {
                $netReturn = $item['qty'] - (int) $oldData->qty;
                if ($netReturn > 0 && (int) (DB::table('stockawalsf')->where('idsf', $validated['idsf'])->where('iddenom', $item['iddenom'])->lockForUpdate()->value('stock') ?? 0) < $netReturn) {
                    throw new \Exception('Stok SF tidak mencukupi untuk penambahan Qty retur.');
                }
                if ($netReturn < 0 && $stokTapOld < abs($netReturn)) throw new \Exception('Stok TAP tidak mencukupi untuk mengurangi Qty retur.');
                if ($netReturn > 0) {
                    DB::table('stockawalsf')->where('idsf', $validated['idsf'])->where('iddenom', $item['iddenom'])->decrement('stock', $netReturn);
                    DB::table('stockawaltap')->where('idtap', $validated['idtap'])->where('iddenom', $item['iddenom'])->increment('stock', $netReturn);
                } elseif ($netReturn < 0) {
                    DB::table('stockawaltap')->where('idtap', $validated['idtap'])->where('iddenom', $item['iddenom'])->decrement('stock', abs($netReturn));
                    DB::table('stockawalsf')->where('idsf', $validated['idsf'])->where('iddenom', $item['iddenom'])->increment('stock', abs($netReturn));
                }
            }

            if ((string) $oldData->idtap !== (string) $validated['idtap'] || (string) $oldData->iddenom !== (string) $item['iddenom'] || (string) $oldData->idsf !== (string) $validated['idsf']) {
                DB::table('stockawalsf')->insertOrIgnore(['idsf' => $validated['idsf'], 'iddenom' => $item['iddenom'], 'stock' => 0]);
                $stokSfNew = DB::table('stockawalsf')->where('idsf', $validated['idsf'])->where('iddenom', $item['iddenom'])->lockForUpdate()->value('stock') ?? 0;
                if ($stokSfNew < $item['qty']) throw new \Exception('Stok SF tidak mencukupi untuk data baru ini');
                DB::table('stockawalsf')->where('idsf', $validated['idsf'])->where('iddenom', $item['iddenom'])->decrement('stock', $item['qty']);
                DB::table('stockawaltap')->insertOrIgnore(['idtap' => $validated['idtap'], 'iddenom' => $item['iddenom'], 'stock' => 0]);
                DB::table('stockawaltap')->where('idtap', $validated['idtap'])->where('iddenom', $item['iddenom'])->increment('stock', $item['qty']);
            }

            // 3. Update record
            DB::table('retursf')->where('idretur', $id)->update([
                'tgl' => $validated['tgl'], 'idtap' => $validated['idtap'], 'idsf' => $validated['idsf'],
                'iddenom' => $item['iddenom'], 'qty' => $item['qty'], 'sn' => $item['sn'],
                'sn_awal' => $item['sn_start'], 'sn_akhir' => $item['sn_end'],
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
            if (($item['serial_mode'] ?? 'range') === 'broken') {
                $qty = (int) ($item['qty'] ?? 0);
                if ($qty < 1 || $qty > 2_000_000) {
                    throw \Illuminate\Validation\ValidationException::withMessages(["items.{$index}.qty" => 'Qty SN pecah harus antara 1 dan 2.000.000.']);
                }
                $item['sn_start'] = '';
                $item['sn_end'] = '';
                $item['qty'] = $qty;
                $item['sn'] = '';
                continue;
            }
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
            if (!preg_match($pattern, $start) || ($end !== '' && !preg_match($pattern, $end))) {
                throw \Illuminate\Validation\ValidationException::withMessages(["items.{$index}.sn_start" => $isSa ? 'SN SA wajib 16 digit dan diawali 0.' : 'SN PV wajib tepat 12 digit.']);
            }
            if ($end === '') $end = $start;
            $qty = (int) ltrim($end, '0') - (int) ltrim($start, '0') + 1;
            if ($qty < 1 || $qty > 2_000_000) throw \Illuminate\Validation\ValidationException::withMessages(["items.{$index}.sn_end" => 'SN akhir harus lebih besar atau sama dengan SN awal; maksimal 2.000.000 SN.']);
            if (!empty($item['qty']) && (int) $item['qty'] !== $qty) throw \Illuminate\Validation\ValidationException::withMessages(["items.{$index}.sn_end" => 'Qty harus sama dengan jumlah SN di rentang.']);
            $item['sn_start'] = $start; $item['sn_end'] = $end; $item['qty'] = $qty;
            $item['sn'] = $start === $end ? $start : $start.'-'.$end;
        }
        unset($item);
        for ($i = 0; $i < count($items); $i++) for ($j = $i + 1; $j < count($items); $j++) {
            if (($items[$i]['serial_mode'] ?? 'range') === 'broken' || ($items[$j]['serial_mode'] ?? 'range') === 'broken') continue;
            if (strlen($items[$i]['sn_start']) === strlen($items[$j]['sn_start']) && $items[$i]['sn_start'] <= $items[$j]['sn_end'] && $items[$i]['sn_end'] >= $items[$j]['sn_start']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Rentang SN antar baris tidak boleh tumpang tindih.']);
            }
        }
        return $items;
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
        $stockTap = DB::table('stockawaltap')->where('idtap',$data->idtap)
            ->where('iddenom',$data->iddenom)->lockForUpdate()->value('stock');
        if ($stockTap === null || $stockTap < $data->qty) {
            $denomName = DB::table('denom')->where('iddenom',$data->iddenom)->value('denom') ?: 'Produk tidak ditemukan';
            throw \Illuminate\Validation\ValidationException::withMessages(['retur' =>
                'Retur tidak dapat dihapus: stok TAP '.$data->idtap.' untuk denom '.$denomName
                .' tersedia '.number_format($stockTap ?? 0,0,',','.')
                .', sedangkan pembatalan retur membutuhkan '.number_format($data->qty,0,',','.').'. Saldo stok tidak diubah.']);
        }
        DB::table('stockawalsf')->insertOrIgnore(['idsf'=>$data->idsf,'iddenom'=>$data->iddenom,'stock'=>0]);
        DB::table('stockawalsf')->where('idsf',$data->idsf)->where('iddenom',$data->iddenom)->lockForUpdate()->first();
        DB::table('stockawalsf')->where('idsf',$data->idsf)->where('iddenom',$data->iddenom)->increment('stock',$data->qty);
        DB::table('stockawaltap')->where('idtap',$data->idtap)->where('iddenom',$data->iddenom)->decrement('stock',$data->qty);
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
            ->select(
                'r.tgl',
                'd.denom',
                'r.qty',
                'r.idtap',
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
