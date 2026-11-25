<?php

namespace App\Http\Controllers;

use App\Helpers\TapFilter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BOExport;
use App\Helpers\AuditLogger;

// Retur BO. Form dan riwayat transaksi memakai view bo serta form/formkeluarbo. Operasi perubahan
// retur ada di controller ini.
class BOController extends Controller
{
    /* =========================
       VIEW
    ========================= */
    public function index()
    {
        return view('bo');
    }

    /* =========================
       DATATABLE SERVER SIDE
    ========================= */
    public function data(Request $request)
    {
        $idtap = session('idtap');

        if ($request->filled('daterange')) {
            [$start, $end] = explode(' - ', $request->daterange);
        } else {
            $start = now()->startOfMonth()->format('Y-m-d');
            $end   = now()->endOfMonth()->format('Y-m-d');
        }

        $kategoritap = [
            'DUMAI','DURI','BENGKALIS','SEI PAKNING',
            'RUPAT','BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG'
        ];

        $query = DB::table('keluar as k')
            ->join('denom as d', 'd.iddenom', '=', 'k.iddenom')
            ->select(
                'k.idkeluar',
                'k.tgl',
                'd.denom',
                'k.qty',
                'k.pengirim',
                'k.penerima',
                'k.sn',
                'k.tambahanket'
            )
            ->whereNotIn('k.pengirim', $kategoritap)
            ->whereBetween('k.tgl', [
                $start.' 00:00:00',
                $end.' 23:59:59'
            ]);

        TapFilter::apply($query, 'k.idtap');

        return DataTables::of($query)
            ->editColumn('tgl', fn($r) => Carbon::parse($r->tgl)->format('d-m-Y'))
            ->editColumn('qty', fn($r) => number_format($r->qty))
            ->addColumn('action', function ($row) {
                $btnEdit = '
                    <a href="'.url('bo/edit/'.$row->idkeluar).'" class="btn btn-link text-primary p-0 action-edit-icon" title="Edit" aria-label="Edit">
                        <i class="fas fa-edit fa-lg" aria-hidden="true"></i>
                    </a>
                ';

                if (!auth()->user()->hasClusterAdminAccess()) {
                    return '<div class="d-flex align-items-center justify-content-center">' . $btnEdit . '</div>';
                }

                $btnDelete = '
                    <form action="'.url('bo/delete/'.$row->idkeluar).'"
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
            ->orderColumn('tgl', 'k.tgl $1')
            ->make(true);
    }

    /* =========================
       FORM INPUT BO
    ========================= */
    public function keluarboform()
    {
        $idtap = session('idtap');

        $data = DB::table('kategori_bo')
            ->when($idtap !== 'SBP_DUMAI', fn($q) => $q->where('idtap', $idtap))
            ->get();

        $packages = $this->returnablePackages();
        $productByDenom = collect($packages)->mapWithKeys(fn ($label, $id) => [
            $id => in_array($id, ['SP6', 'SP7', 'SP1', 'SP9'], true) ? 'SA' : 'PV',
        ]);

        return view('form/formkeluarbo', compact('data', 'idtap', 'packages', 'productByDenom'));
    }

    // get Tap
public function getTap(Request $request)
{
    $bo = $request->idtap; // ini nama BO (namabo)
    $sessionTap = session('idtap');

    $query = DB::table('kategori_bo');

    if ($sessionTap === 'SBP_DUMAI') {
        $query->where('namabo', $bo);
    } else {
        $query->where('idtap', $sessionTap);
    }

    $taps = $query->get();

    $html = '<option value="">-- Pilih TAP --</option>';

    foreach ($taps as $tap) {
        $html .= '<option value="'.$tap->idtap.'">'.$tap->idtap.'</option>';
    }

    return response($html);
}

    public function getBoStock(Request $request)
    {
        $validated = $request->validate([
            'pengirim' => ['required', 'string'],
            'iddenom' => ['required', 'string', 'in:'.implode(',', array_keys($this->returnablePackages()))],
        ]);

        $sessionTap = session('idtap');
        $allowedBo = DB::table('kategori_bo')
            ->when($sessionTap !== 'SBP_DUMAI', fn ($query) => $query->where('idtap', $sessionTap))
            ->where('namabo', $validated['pengirim'])
            ->exists();

        abort_unless($allowedBo, 403);

        $stock = DB::table('stockawalsf')
            ->where('idsf', $validated['pengirim'])
            ->where('iddenom', $validated['iddenom'])
            ->value('stock');

        return response()->json(['stock' => max(0, (int) ($stock ?? 0))]);
    }



    /* =========================
       STORE (ANTI DOUBLE SUBMIT)
    ========================= */
    public function proseskeluarboform(Request $request)
{
    $data = $this->validatedReturnBatchPayload($request);

    return DB::transaction(function () use ($data) {

        $requestedByDenom = collect($data['items'])
            ->groupBy('iddenom')
            ->map(fn ($items) => $items->sum('qty'))
            ->sortKeys();

        foreach ($requestedByDenom as $iddenom => $requestedQty) {
            $stokBo = DB::table('stockawalsf')
                ->where('idsf', $data['pengirim'])
                ->where('iddenom', $iddenom)
                ->lockForUpdate()
                ->value('stock');

            if ($stokBo === null || (int) $stokBo < $requestedQty) {
                $denomName = $this->returnablePackages()[$iddenom] ?? $iddenom;
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => "Stok BO {$denomName} tidak mencukupi. Diminta {$requestedQty}, tersedia ".(int) ($stokBo ?? 0).'.',
                ]);
            }
        }

        foreach ($data['items'] as $item) {
            $newId = DB::table('keluar')->insertGetId([
                'iddenom' => $item['iddenom'],
                'pengirim' => $data['pengirim'],
                'penerima' => $data['penerima'],
                'qty' => $item['qty'],
                'tgl' => $data['tgl'],
                'sn' => $item['sn'],
                'tambahanket' => $item['tambahanket'] ?? null,
                'idtap' => $data['penerima'],
                'status' => 0,
            ]);

            AuditLogger::log('INSERT', 'BO / Retur', $newId, null, $data + $item);
        }

        foreach ($requestedByDenom as $iddenom => $requestedQty) {
            DB::table('stockawalsf')
                ->where('idsf', $data['pengirim'])
                ->where('iddenom', $iddenom)
                ->decrement('stock', $requestedQty);

            $this->incrementTapStock($data['penerima'], $iddenom, (int) $requestedQty);
        }

        return redirect('bo')->with('success', count($data['items']).' baris denom berhasil ditambahkan');
    });
}


    /* =========================
       EDIT
    ========================= */
    public function edit($id)
    {
        $idtap_session = session('idtap');
        $edit = DB::table('keluar')->where('idkeluar', $id)->first();
        
        if (!$edit) return redirect('bo')->with('error', 'Data tidak ditemukan');

        $data = DB::table('kategori_bo')
            ->when($idtap_session !== 'SBP_DUMAI', fn($q) => $q->where('idtap', $idtap_session))
            ->get();

        $packages = $this->returnablePackages();
        $productByDenom = collect($packages)->mapWithKeys(fn ($label, $id) => [
            $id => in_array($id, ['SP6', 'SP7', 'SP1', 'SP9'], true) ? 'SA' : 'PV',
        ]);

        $serialParts = preg_split('/\s*(?:-|–|—)\s*/u', trim((string) ($edit->sn ?? '')), 2) ?: [];
        $snStart = $serialParts[0] ?? '';
        $snEnd = $serialParts[1] ?? '';
        $serialMode = trim((string) ($edit->sn ?? '')) === '' ? 'broken' : 'range';

        return view('form.form-edit-bo', compact('edit', 'data', 'idtap_session', 'packages', 'productByDenom', 'snStart', 'snEnd', 'serialMode'));
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validatedReturnPayload($request);

        DB::transaction(function () use ($validated, $id) {
            $oldData = DB::table('keluar')->where('idkeluar', $id)->lockForUpdate()->first();
            
            // Rollback stok lama
            DB::table('stockawalsf')->where('idsf', $oldData->pengirim)->where('iddenom', $oldData->iddenom)->increment('stock', $oldData->qty);
            DB::table('stockawaltap')->where('idtap', $oldData->penerima)->where('iddenom', $oldData->iddenom)->decrement('stock', $oldData->qty);

            // Terapkan stok baru
            $stokBo = DB::table('stockawalsf')->where('idsf', $validated['pengirim'])->where('iddenom', $validated['iddenom'])->lockForUpdate()->value('stock');

            if ($stokBo === null || $stokBo < $validated['qty']) {
                throw new \Exception('Stok BO tidak mencukupi untuk update ini');
            }

            // Kurangi BO, Tambah TAP
            DB::table('stockawalsf')->where('idsf', $validated['pengirim'])->where('iddenom', $validated['iddenom'])->decrement('stock', $validated['qty']);
            $this->incrementTapStock($validated['penerima'], $validated['iddenom'], (int) $validated['qty']);

            // Update record
            DB::table('keluar')->where('idkeluar', $id)->update([
                'iddenom' => $validated['iddenom'],
                'pengirim' => $validated['pengirim'],
                'penerima' => $validated['penerima'],
                'qty' => $validated['qty'],
                'tgl' => $validated['tgl'],
                'sn' => $validated['sn'] ?? null,
                'tambahanket' => $validated['tambahanket'] ?? null,
                'idtap' => $validated['penerima']
            ]);

            // 📝 LOG
            AuditLogger::log('UPDATE', 'BO / Retur', $id, (array)$oldData, $validated);
        });

        return redirect('bo')->with('success', 'Data berhasil diperbarui');
    }

    /* =========================
       DELETE (ROLLBACK STOK)
    ========================= */
    public function delete($idkeluar)
    {
        if (!auth()->user()->hasClusterAdminAccess()) {
            return redirect('bo')->with('error', 'Akses ditolak. Hanya Admin Cluster yang boleh menghapus data.');
        }

        try {
            DB::transaction(function () use ($idkeluar) {

                $data = DB::table('keluar')
                    ->where('idkeluar', $idkeluar)
                    ->lockForUpdate()
                    ->first();

                if (!$data) throw new \Exception('Data tidak ditemukan');

                $stokTap = DB::table('stockawaltap')
                    ->where('idtap', $data->penerima)
                    ->where('iddenom', $data->iddenom)
                    ->lockForUpdate()
                    ->value('stock');

                if ($stokTap < $data->qty) {
                    throw new \Exception('Stok TAP tidak mencukupi');
                }

                DB::table('stockawaltap')
                    ->where('idtap', $data->penerima)
                    ->where('iddenom', $data->iddenom)
                    ->decrement('stock', $data->qty);

                DB::table('stockawalsf')
                    ->where('idsf', $data->pengirim)
                    ->where('iddenom', $data->iddenom)
                    ->increment('stock', $data->qty);

                // 📝 LOG
                AuditLogger::log('DELETE', 'BO / Retur', $idkeluar, (array)$data);

                DB::table('keluar')->where('idkeluar', $idkeluar)->delete();
            });

            return redirect('bo')->with('success', 'Data berhasil dihapus');

        } catch (\Exception $e) {
            return redirect('bo')->with('error', $e->getMessage());
        }
    }


    public function exportexcel(Request $request)
{
    $idtap = session('idtap');

    if ($request->filled('daterange')) {
        [$start, $end] = explode(' - ', $request->daterange);
    } else {
        $start = now()->startOfMonth()->format('Y-m-d');
        $end   = now()->endOfMonth()->format('Y-m-d');
    }

    $kategoritap = [
        'DUMAI','DURI','BENGKALIS','SEI PAKNING',
        'RUPAT','BAGAN BATU','BAGAN SIAPI-API','UJUNG TANJUNG'
    ];

    $query = DB::table('keluar as m')
        ->join('denom as d', 'd.iddenom', 'm.iddenom')
        ->select(
            'm.tgl',
            'm.pengirim',
            'm.penerima',
            'd.denom',
            DB::raw('SUM(m.qty) as qty')
        )
        ->whereNotIn('m.pengirim', $kategoritap)
        ->whereBetween('m.tgl', [
            $start.' 00:00:00',
            $end.' 23:59:59'
        ])
        ->groupBy('m.tgl','m.pengirim','m.penerima','d.denom');

    TapFilter::apply($query, 'm.idtap');

    return Excel::download(
        new BOExport($query->get()),
        "RETUR_BO_{$start}_sd_{$end}.xlsx"
    );
}

    private function returnablePackages(): array
    {
        return [
            'SEGEL' => 'PV SEGEL',
            'V33' => 'PV BYU SEGEL',
            'SP6' => 'PERDANA SEGEL SIMPATI',
            'SP7' => 'PERDANA SEGEL BYU',
            'SP1' => 'SIMPATI 3GB',
            'SP9' => 'SIMPATI 15GB',
        ];
    }

    private function validatedReturnBatchPayload(Request $request): array
    {
        $data = $request->validate([
            'pengirim' => ['required', 'string'],
            'penerima' => ['required', 'string'],
            'tgl' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.iddenom' => ['required', 'string', 'in:'.implode(',', array_keys($this->returnablePackages()))],
            'items.*.serial_mode' => ['required', 'in:range,broken'],
            'items.*.sn_start' => ['nullable', 'string', 'max:16'],
            'items.*.sn_end' => ['nullable', 'string', 'max:16'],
            'items.*.qty' => ['nullable', 'integer', 'min:1', 'max:2000000'],
            'items.*.tambahanket' => ['nullable', 'string', 'max:500'],
        ]);

        $data['items'] = array_values($data['items']);
        $ranges = [];

        foreach ($data['items'] as $index => &$item) {
            if ($item['serial_mode'] === 'broken') {
                $qty = (int) ($item['qty'] ?? 0);
                if ($qty < 1 || $qty > 2_000_000) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.{$index}.qty" => 'Qty SN pecah harus antara 1 dan 2.000.000.',
                    ]);
                }
                $item['qty'] = $qty;
                $item['sn_start'] = '';
                $item['sn_end'] = '';
                $item['sn'] = '';
                continue;
            }

            $isSa = in_array($item['iddenom'], ['SP6', 'SP7', 'SP1', 'SP9'], true);
            $pattern = $isSa ? '/^0\\d{15}$/' : '/^\\d{12}$/';
            $expected = $isSa ? 'SN SA wajib 16 digit dan diawali 0.' : 'SN PV wajib tepat 12 digit.';
            $start = trim((string) ($item['sn_start'] ?? ''));
            $endInput = trim((string) ($item['sn_end'] ?? ''));
            $end = $endInput !== '' ? $endInput : $start;

            if (! preg_match($pattern, $start) || ! preg_match($pattern, $end)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "items.{$index}.sn_start" => $expected,
                ]);
            }

            $qty = (int) $end - (int) $start + 1;
            if ($qty < 1 || $qty > 2_000_000) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "items.{$index}.sn_end" => 'SN akhir harus sama dengan atau lebih besar dari SN awal; maksimal 2.000.000 SN.',
                ]);
            }
            if (isset($item['qty']) && (int) $item['qty'] !== $qty) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "items.{$index}.sn_end" => 'Qty harus sama dengan jumlah SN dalam rentang.',
                ]);
            }

            foreach ($ranges as $range) {
                if (strlen($start) === strlen($range['start']) && $start <= $range['end'] && $end >= $range['start']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => 'Rentang SN antar denom tidak boleh bertumpang tindih.',
                    ]);
                }
            }

            $ranges[] = ['start' => $start, 'end' => $end];
            $item['qty'] = $qty;
            $item['sn_start'] = $start;
            $item['sn_end'] = $end;
            $item['sn'] = $start === $end ? $start : $start.' - '.$end;
        }
        unset($item);

        return $data;
    }

    private function validatedReturnPayload(Request $request): array
    {
        $data = $request->validate([
            'pengirim' => ['required', 'string'],
            'penerima' => ['required', 'string'],
            'iddenom' => ['required', 'string', 'in:'.implode(',', array_keys($this->returnablePackages()))],
            'serial_mode' => ['required', 'in:range,broken'],
            'sn_start' => ['nullable', 'string', 'regex:/^\\d+$/', 'max:16'],
            'sn_end' => ['nullable', 'string', 'regex:/^\\d+$/', 'max:16'],
            'qty' => ['required_if:serial_mode,broken', 'nullable', 'integer', 'min:1', 'max:2000000'],
            'tambahanket' => ['nullable', 'string'],
            'tgl' => ['required', 'date'],
        ]);

        if ($data['serial_mode'] === 'broken') {
            $data['qty'] = (int) $data['qty'];
            $data['sn'] = '';
            return $data;
        }

        $isSa = in_array($data['iddenom'], ['SP6', 'SP7', 'SP1', 'SP9'], true);
        $pattern = $isSa ? '/^0\\d{15}$/' : '/^\\d{12}$/';
        $expected = $isSa ? 'SN SA wajib 16 digit dan diawali 0.' : 'SN PV wajib tepat 12 digit.';
        $start = trim((string) ($data['sn_start'] ?? ''));
        $end = trim((string) ($data['sn_end'] ?? '')) ?: $start;

        if (! preg_match($pattern, $start) || ! preg_match($pattern, $end)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['sn_start' => $expected]);
        }

        $qty = (int) $end - (int) $start + 1;
        if ($qty < 1 || $qty > 2_000_000) {
            throw \Illuminate\Validation\ValidationException::withMessages(['sn_end' => 'SN akhir harus sama dengan atau lebih besar dari SN awal; maksimal 2.000.000 SN.']);
        }

        $data['qty'] = $qty;
        $data['sn'] = $start === $end ? $start : $start.' - '.$end;
        unset($data['sn_start'], $data['sn_end']);

        return $data;
    }

    private function incrementTapStock(string $idtap, string $iddenom, int $qty): void
    {
        $updated = DB::table('stockawaltap')
            ->where('idtap', $idtap)
            ->where('iddenom', $iddenom)
            ->increment('stock', $qty);

        if ($updated === 0) {
            DB::table('stockawaltap')->insert([
                'idtap' => $idtap,
                'iddenom' => $iddenom,
                'stock' => $qty,
            ]);
        }
    }

}
