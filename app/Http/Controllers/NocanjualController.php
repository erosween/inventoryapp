<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NocanjualController extends Controller
{

    public function index(Request $request)
    {
        $cluster = $request->input('cluster', session('cluster'));

        session(['cluster' => $cluster]);

        if ($cluster) {
            $data = DB::table('nocan')
                ->select("*")
                ->where('cluster', $cluster)
                ->where('status', 'sold')
                ->get();
        } else {
            $data = DB::table('nocan')
                ->select("*")
                ->where('cluster', $cluster)
                ->where('status', 'sold')
                ->get();
        }

        return view('jual', compact('data', 'cluster'));
    }

    public function form(Request $request)
    {
        $data = DB::table('nocan')
            ->select("*")
            ->where('status', 'ready')
            ->get();

        return view('form/form-nocan', compact('data'));
    }

    public function nocanproses(Request $request)
    {
        $tgl = $request->input('tgl');
        $cluster = $request->input('cluster');
        $tap = $request->input('tap');
        $penjual = $request->input('penjual');
        $nomor = $request->input('nomor');
        $harga = $request->input('harga');
        $status = $request->input('status');

        // Query untuk mengecek apakah nomor ada dengan status 'ready'
        $ready = DB::table('nocan')
            ->where('nomor', $nomor)
            ->where('status', 'ready')
            ->first();

        if ($ready) {
            DB::table('nocan')
                ->where('nomor', $nomor)
                ->update([
                    'tanggal' => $tgl,
                    'tap' => $tap,
                    'booked' => $penjual,
                    'harga' => $harga,
                    'status' => $status
                ]);
            return redirect('nocan')->with('status', 'Data Berhasil Ditambahkan!');
        } else {
            return redirect('/form/form-nocan')->withErrors(['error' => 'Gagal!, Nomor Sudah ada yang booking!']);
        }
    }
}
