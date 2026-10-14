<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileSuryaExtrasController extends Controller
{
    public function consignment()
    {
        return redirect()
            ->route('mobile.form')
            ->with('success', 'Konsinyasi SA sekarang tersedia langsung di form penjualan.');
    }

    public function survey()
    {
        return view('mobile.surya-form', ['type' => 'survey', 'title' => 'Survey Kompetitor', 'outlets' => $this->outlets()]);
    }

    public function network()
    {
        return view('mobile.surya-form', ['type' => 'network', 'title' => 'Kondisi Network', 'outlets' => $this->outlets()]);
    }

    public function store(Request $request, string $type)
    {
        if ($type === 'consignment') {
            return redirect()
                ->route('mobile.form')
                ->with('error', 'Masukkan konsinyasi SA bersama transaksi penjualan agar outlet dan receipt tercatat otomatis.');
        }

        $rules = ['id_outlet' => ['required'], 'notes' => ['nullable', 'string', 'max:1000']];
        if ($type === 'survey') $rules += ['operator' => ['required'], 'product' => ['nullable'], 'price' => ['nullable', 'numeric'], 'estimated_stock' => ['nullable', 'integer', 'min:0']];
        if ($type === 'network') $rules += ['operator' => ['required'], 'signal_status' => ['required'], 'data_status' => ['nullable']];
        $data = $request->validate($rules);
        $data += ['reported_at' => now(), 'idtap' => session('idtap'), 'idsf' => session('mobile_sf_id')];
        $table = match ($type) {
            'survey' => 'surya_competitor_surveys',
            'network' => 'surya_network_reports',
            default => abort(404),
        };
        DB::table($table)->insert($data);
        return redirect()->route('mobile.index')->with('success', 'Data ' . $type . ' berhasil disimpan.');
    }

    private function outlets()
    {
        return DB::table('appsdumais')->where('sf', 'LIKE', '%' . session('mobile_sf_name') . '%')->orderBy('nama_outlet')->get(['id_outlet', 'nama_outlet']);
    }
}
