<?php

namespace App\Http\Controllers;

use App\Helpers\TapFilter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RusakExport;
use Carbon\Carbon;
use App\Helpers\AuditLogger;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VrusakController extends Controller
{
    /* ===============================
       VIEW
    =============================== */
    public function index()
    {
        return view('vrusak');
    }

    /* ===============================
       DATATABLE SERVER SIDE
    =============================== */
    public function data(Request $request)
    {
        $idtap = session('idtap');

        if (!$request->daterange || !str_contains($request->daterange, ' - ')) {
            return DataTables::of(collect([]))->make(true);
        }

        [$start, $end] = explode(' - ', $request->daterange);

        $query = DB::table('returvfrusak as r')
            ->join('denom as d', 'r.iddenom', '=', 'd.iddenom')
            ->select(
                'r.idrusak',
                'r.tgl',
                'd.denom',
                'r.qty',
                'r.idtap',
                'r.sn',
                'r.ketvf',
                'r.ketlain',
                'r.iddenom'
            )
            ->whereBetween('r.tgl', [$start . ' 00:00:00', $end . ' 23:59:59']);

        TapFilter::apply($query, 'r.idtap');

        return DataTables::of($query)
            ->filterColumn('denom', function($query, $keyword) {
                $query->where('d.denom', 'LIKE', "%{$keyword}%");
            })
            ->editColumn('tgl', fn ($r) => date('d-m-Y', strtotime($r->tgl)))
            ->editColumn('qty', fn ($r) => number_format($r->qty))
            ->addColumn('action', function ($r) {
                $btnEdit = '
                    <a href="'.url('vrusak/edit/'.$r->idrusak).'" class="btn btn-link text-primary p-0 action-edit-icon" title="Edit" aria-label="Edit">
                        <i class="fas fa-edit fa-lg" aria-hidden="true"></i>
                    </a>
                ';

                if (!auth()->user()->hasClusterAdminAccess()) {
                    return '<div class="d-flex align-items-center justify-content-center">' . $btnEdit . '</div>';
                }

                $btnDelete = '
                <form action="'.url('vrusak/'.$r->idrusak).'" 
                      method="POST" 
                      class="form-delete d-inline">
                    '.csrf_field().'
                    <input type="hidden" name="idtap" value="'.$r->idtap.'">
                    <input type="hidden" name="iddenom" value="'.$r->iddenom.'">
                    <input type="hidden" name="qty" value="'.$r->qty.'">
                    <button class="btn btn-link text-danger p-0" title="Delete">
                        <i class="fas fa-trash-alt fa-lg"></i>
                    </button>
                </form>';

                return '<div class="d-flex align-items-center justify-content-center">' . $btnEdit . $btnDelete . '</div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /* ===============================
       FORM INPUT
    =============================== */
    public function vrusak()
    {
        $idtap = session('idtap');

        $tap = DB::table('kodetap')
            ->when($idtap !== 'SBP_DUMAI', fn ($q) => $q->where('idtap', $idtap))
            ->get();

        $denom = DB::table('denom')->get();

        return view('form.form-vrusak', compact('tap', 'denom', 'idtap'));
    }

    public function stockTap(Request $request)
    {
        $validated = $request->validate([
            'idtap' => ['required', 'string', Rule::exists('kodetap', 'idtap')],
        ]);

        if (session('idtap') !== 'SBP_DUMAI' && $validated['idtap'] !== session('idtap')) {
            abort(403, 'TAP tidak sesuai dengan akses pengguna.');
        }

        return response()->json(
            DB::table('stockawaltap')
                ->where('idtap', $validated['idtap'])
                ->pluck('stock', 'iddenom')
                ->map(fn ($stock) => (int) $stock)
        );
    }

    /* ===============================
       SIMPAN
    =============================== */
    public function vrusakproses(Request $request)
    {
        // Tetap menerima payload form lama agar endpoint tidak berubah secara mendadak.
        if (!is_array($request->input('items'))) {
            $request->merge(['items' => [[
                'iddenom' => $request->iddenom,
                'qty' => $request->qty,
                'sn' => $request->sn,
                'ketvf' => $request->ketvf,
                'tambahanket' => $request->tambahanket,
            ]]]);
        }

        $validated = $request->validate([
            'tgl' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:' . now()->subMonth()->toDateString()],
            'pengirim' => ['required', 'string', Rule::exists('kodetap', 'idtap')],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.iddenom' => ['required', 'string', Rule::exists('denom', 'iddenom')],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.sn' => ['required', 'string', 'max:255'],
            'items.*.ketvf' => ['required', Rule::in(['RUSAK', 'MATI'])],
            'items.*.tambahanket' => ['required', 'string', 'max:500'],
        ], [
            'tgl.after_or_equal' => 'Tanggal maksimal satu bulan ke belakang.',
            'tgl.before_or_equal' => 'Tanggal tidak boleh melewati hari ini.',
            'items.*.tambahanket.required' => 'Keterangan tambahan wajib diisi pada setiap baris.',
        ]);

        if (session('idtap') !== 'SBP_DUMAI' && $validated['pengirim'] !== session('idtap')) {
            abort(403, 'TAP pengirim tidak sesuai dengan akses pengguna.');
        }

        // Urutan tetap mencegah deadlock saat beberapa transaksi massal berjalan bersamaan.
        usort($validated['items'], fn ($a, $b) => strcmp($a['iddenom'], $b['iddenom']));

        DB::transaction(function () use ($validated) {
            $requestedByDenom = collect($validated['items'])
                ->groupBy('iddenom')
                ->map(fn ($items) => $items->sum('qty'));

            foreach ($requestedByDenom as $iddenom => $requestedQty) {
                $stock = DB::table('stockawaltap')
                    ->where('idtap', $validated['pengirim'])
                    ->where('iddenom', $iddenom)
                    ->lockForUpdate()
                    ->value('stock') ?? 0;

                if ($stock < $requestedQty) {
                    $denomName = DB::table('denom')->where('iddenom', $iddenom)->value('denom') ?? $iddenom;
                    throw ValidationException::withMessages([
                        'items' => "Stok denom {$denomName} ({$iddenom}) tidak mencukupi. Diminta: {$requestedQty}, tersedia: {$stock}.",
                    ]);
                }
            }

            foreach ($validated['items'] as $item) {
                $newId = DB::table('returvfrusak')->insertGetId([
                    'idtap' => $validated['pengirim'],
                    'tgl' => $validated['tgl'],
                    'iddenom' => $item['iddenom'],
                    'qty' => $item['qty'],
                    'sn' => $item['sn'],
                    'ketvf' => $item['ketvf'],
                    'ketlain' => $item['tambahanket'],
                ]);

                DB::table('stockawaltap')
                    ->where('idtap', $validated['pengirim'])
                    ->where('iddenom', $item['iddenom'])
                    ->decrement('stock', $item['qty']);

                AuditLogger::log('INSERT', 'Voucher Rusak', $newId, null, [
                    'tgl' => $validated['tgl'],
                    'pengirim' => $validated['pengirim'],
                    ...$item,
                ]);
            }
        });

        return redirect('vrusak')->with(
            'success',
            count($validated['items']) . ' data voucher rusak berhasil disimpan dan stok TAP dikurangi.'
        );
    }

    /* ===============================
       EDIT
    =============================== */
    public function edit($id)
    {
        $idtap_session = session('idtap');
        $data = DB::table('returvfrusak')->where('idrusak', $id)->first();
        
        if (!$data) return redirect('vrusak')->with('error', 'Data tidak ditemukan');

        $tap = DB::table('kodetap')
            ->when($idtap_session !== 'SBP_DUMAI', fn ($q) => $q->where('idtap', $idtap_session))
            ->get();

        $denom = DB::table('denom')->get();

        return view('form.form-edit-vrusak', compact('data', 'tap', 'denom', 'idtap_session'));
    }

    public function update(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $oldData = DB::table('returvfrusak')->where('idrusak', $id)->lockForUpdate()->first();
            
            // 1. Kembalikan stok lama ke TAP
            DB::table('stockawaltap')
                ->where('idtap', $oldData->idtap)
                ->where('iddenom', $oldData->iddenom)
                ->increment('stock', $oldData->qty);

            // 2. Cek stok TAP untuk data baru
            $currentStock = DB::table('stockawaltap')
                ->where('idtap', $request->idtap)
                ->where('iddenom', $request->iddenom)
                ->lockForUpdate()
                ->value('stock');

            if ($currentStock < $request->qty) {
                throw new \Exception('Stok TAP tidak mencukupi untuk update ini');
            }

            // 3. Kurangi stok TAP dengan qty baru
            DB::table('stockawaltap')
                ->where('idtap', $request->idtap)
                ->where('iddenom', $request->iddenom)
                ->decrement('stock', $request->qty);

            // 4. Update record
            DB::table('returvfrusak')->where('idrusak', $id)->update([
                'idtap'   => $request->idtap,
                'tgl'     => $request->tgl,
                'iddenom' => $request->iddenom,
                'qty'     => $request->qty,
                'sn'      => $request->sn,
                'ketvf'   => $request->ketvf,
                'ketlain' => $request->tambahanket,
            ]);

            // 📝 LOG
            AuditLogger::log('UPDATE', 'Voucher Rusak', $id, (array)$oldData, $request->all());
        });

        return redirect('vrusak')->with('success', 'Data berhasil diperbarui');
    }
    public function delete(Request $request, $idrusak)
    {
        if (!auth()->user()->hasClusterAdminAccess()) {
            return back()->with('error', 'Akses ditolak. Hanya Admin Cluster yang boleh menghapus data.');
        }

        DB::transaction(function () use ($request, $idrusak) {
            
            $oldData = DB::table('returvfrusak')->where('idrusak', $idrusak)->first();

            DB::table('returvfrusak')
                ->where('idrusak', $idrusak)
                ->delete();

            // 📝 LOG
            AuditLogger::log('DELETE', 'Voucher Rusak', $idrusak, (array)$oldData);

            DB::table('stockawaltap')
                ->where('idtap', $request->idtap)
                ->where('iddenom', $request->iddenom)
                ->increment('stock', $request->qty);
        });

        return back()->with('success', 'Data berhasil dihapus');
    }

    /* ===============================
       EXPORT
    =============================== */
    public function exportexcel(Request $request)
    {
        [$start, $end] = explode(' - ', $request->daterange);
        $idtap = session('idtap');

        $query = DB::table('returvfrusak as r')
            ->join('denom as d', 'r.iddenom', '=', 'd.iddenom')
            ->select(
                'r.tgl',
                'd.denom',
                'r.qty',
                'r.idtap',
                'r.sn',
                'r.ketvf',
                'r.ketlain'
            )
            ->whereBetween('r.tgl', [$start . ' 00:00:00', $end . ' 23:59:59']);

        TapFilter::apply($query, 'r.idtap');

        return Excel::download(
            new RusakExport($query->get()),
            'VOUCHER_RUSAK_'.now()->format('Ymd_His').'.xlsx'
        );
    }
}
