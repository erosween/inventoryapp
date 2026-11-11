<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiSvpController extends Controller
{
    // Ambil periode terpilih dan bulan sebelumnya agar browser bisa mengisi target awal. Skor dan
    // proyeksi dihitung di surya-kpi-svp.js.
    public function entries(Request $request)
    {
        $input = $request->validate(['period' => ['required', 'date_format:Y-m']]);
        $month = CarbonImmutable::createFromFormat('!Y-m', $input['period']);
        $rows = DB::table('surya_kpi_svp_entries')
            ->whereIn('period', [$month->toDateString(), $month->subMonth()->toDateString()])
            ->get();

        return response()->json($rows);
    }

    // Penyimpanan khusus admin super. Satu baris KPI dikenali dari periode, wilayah, segmen, dan parameter.
    public function save(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $input = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'region' => ['required', 'in:dumai,rokan'],
            'segment' => ['required', 'in:mobile,household'],
            'metric' => ['required', 'integer', 'between:0,2'],
        ]);
        $month = CarbonImmutable::createFromFormat('!Y-m', $input['period']);
        $values = $request->validate([
            'target' => ['present', 'nullable', 'numeric', 'decimal:0,4', 'min:0', 'max:999999999999999'],
            'actual' => ['present', 'nullable', 'numeric', 'decimal:0,4', 'min:0', 'max:999999999999999'],
            'actual_date' => ['present', 'nullable', 'required_with:actual', 'date_format:Y-m-d',
                'after_or_equal:'.$month->toDateString(), 'before_or_equal:'.$month->endOfMonth()->toDateString()],
        ], ['actual_date.required_with' => 'Isi tanggal update untuk angka aktual ini.']);

        $entry = array_replace($input, ['period' => $month->toDateString()], $values);
        DB::table('surya_kpi_svp_entries')->upsert([$entry],
            ['period', 'region', 'segment', 'metric'], ['target', 'actual', 'actual_date']);

        return response()->json($entry);
    }
}
