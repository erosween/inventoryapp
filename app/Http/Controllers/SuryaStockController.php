<?php

namespace App\Http\Controllers;

use App\Imports\SuryaTargetImport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SuryaStockController extends Controller
{
    private array $clusters = [
        'Dumai Bengkalis' => ['DUMAI', 'BENGKALIS', 'DURI', 'RUPAT', 'SEI PAKNING'],
        'Rokan Hilir' => ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'],
    ];

    public function stock(Request $request)
    {
        // Stock on Hand and achievement currently cover PV Reguler only.
        $family = strtoupper((string) $request->query('family', 'PV')) === 'SA' ? 'SA' : 'PV';
        $today = now();
        $daysLeft = max(1, collect(range($today->day, $today->daysInMonth))->filter(fn ($day) => ! $today->copy()->day($day)->isWeekend())->count());
        $sfRows = DB::table('idsf')->where('idsf', 'like', 'SF%')->select('idsf', 'namasf', 'idtap')->orderBy('idtap')->orderBy('namasf')->get();
        $targets = DB::table('surya_sf_targets')->where('product_family', $family)->get()->groupBy('idsf');
        $denoms = DB::table('denom')->where(function ($q) use ($family) {
            if ($family === 'SA') $q->where('kategori_inject', 'SA');
            else $q->whereNotIn('kategori_inject', ['BYU', 'SA'])->where('group_name', '!=', 'LAINNYA');
        })->get(['iddenom', 'denom', 'group_name']);
        $denomKeys = $denoms->groupBy(fn ($d) => $family === 'PV' ? strtoupper($d->group_name) : strtoupper(preg_replace('/^SA\s+/i', '', $d->denom)))->map(fn ($rows) => $rows->pluck('iddenom')->values());
        $stock = DB::table('stockawalsf')->whereIn('iddenom', $denoms->pluck('iddenom'))->get()->groupBy('idsf');
        $sales = DB::table('keluarsf')->whereBetween('tgl', [$today->copy()->startOfMonth(), $today->copy()->endOfDay()])->whereIn('iddenom', $denoms->pluck('iddenom'))->get(['idsf', 'iddenom', 'qty']);
        $mobileSales = DB::table('mobile_penjualan')->where('status', 'approved')->whereBetween('tgl', [$today->copy()->startOfMonth(), $today->copy()->endOfDay()])->whereIn('iddenom', $denoms->pluck('iddenom'))->get(['idsf', 'iddenom', 'qty']);
        $sales = $sales->concat($mobileSales)->groupBy('idsf');

        $rows = $sfRows->map(function ($sf) use ($targets, $denomKeys, $stock, $sales, $daysLeft, $family) {
            $targetRows = $targets->get($sf->idsf, collect())->keyBy('validity');
            $stockRows = $stock->get($sf->idsf, collect())->keyBy('iddenom');
            $salesRows = $sales->get($sf->idsf, collect())->groupBy('iddenom')->map(fn ($r) => $r->sum('qty'));
            $items = $targetRows->map(function ($target, $validity) use ($denomKeys, $stockRows, $salesRows, $daysLeft) {
                $iddenoms = $denomKeys->get($validity, collect());
                $actual = (int) $salesRows->only($iddenoms->all())->sum();
                $daily = (int) ceil(max(0, $target->target_qty - $actual) / $daysLeft);
                $onHand = (int) $stockRows->only($iddenoms->all())->sum('stock');
                return ['validity' => $validity, 'target' => (int) $target->target_qty, 'daily' => $daily, 'actual' => $actual, 'stock' => $onHand, 'recommendation' => max(0, $daily - $onHand), 'status' => $onHand >= $daily ? 'Stock Aman' : 'Stock Kurang'];
            })->values();
            return ['idsf' => $sf->idsf, 'namasf' => $sf->namasf, 'idtap' => $sf->idtap, 'cluster' => $this->clusterFor($sf->idtap), 'items' => $items];
        })->filter(fn ($row) => $row['items']->isNotEmpty());

        return view('surya.stock-on-hand', compact('family', 'rows', 'daysLeft'));
    }

    public function targets()
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $targets = DB::table('surya_sf_targets')->join('idsf', 'idsf.idsf', '=', 'surya_sf_targets.idsf')->select('surya_sf_targets.*', 'idsf.namasf', 'idsf.idtap')->orderBy('idsf.namasf')->orderBy('product_family')->orderBy('validity')->get();
        $sfs = DB::table('idsf')->where('idsf', 'like', 'SF%')->select('idsf', 'namasf', 'idtap')->orderBy('idtap')->orderBy('namasf')->get();
        return view('surya.input-target', compact('targets', 'sfs'));
    }

    public function upload(Request $request)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $request->validate(['target_file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
        Excel::import(new SuryaTargetImport, $request->file('target_file'));
        return back()->with('success', 'Target berhasil di-upload.');
    }

    public function update(Request $request, int $target)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $data = $request->validate(['target_qty' => ['required', 'integer', 'min:0']]);
        $query = DB::table('surya_sf_targets')->where('id', $target);
        abort_unless($query->exists(), 404);
        $query->update(['target_qty' => $data['target_qty'], 'updated_at' => now()]);
        return back()->with('success', 'Target diperbarui.');
    }

    private function clusterFor(string $tap): string
    {
        foreach ($this->clusters as $name => $taps) if (in_array($tap, $taps, true)) return $name;
        return '—';
    }
}
