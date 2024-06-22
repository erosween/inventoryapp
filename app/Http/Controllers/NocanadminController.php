<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NocanadminController extends Controller
{

    public function index(Request $request)
    {

        $idtap = session('idtap');
        // Ambil nilai filter dari request
        $cluster = $request->input('cluster');

        // Simpan nilai filter ke dalam session
        if ($cluster) {
            session(['cluster' => $cluster]);
        } else {
            $cluster = session('cluster');
        }

        $data = DB::table('nocan')
            ->select("*")
            ->where('cluster', $cluster)
            ->where('status', '!=', 'ready')
            ->get();

        return view('nocanadmin', compact('data', 'cluster', 'idtap'));
    }

    public function edit(Request $request, $id)
    {

        $harga = $request->input('harga');
        $tanggal = $request->input('tanggal');
        $tap = $request->input('tap');
        $penjual = $request->input('penjual');
        $status = $request->input('status');
        $outlet = $request->input('outlet');

        DB::table('nocan')
            ->where('id', $id)
            ->update([
                'tanggal' => $tanggal,
                'harga' => $harga,
                'tap' => $tap,
                'booked' => $penjual,
                'status' => $status,
                'outlet' => $outlet,
            ]);
        return redirect('nocanadmin')->with('status', 'Data Berhasil Diperbaharui!');
    }

    public function reset($id)
    {

        DB::table('nocan')
            ->where('id', $id)
            ->update([
                'tanggal' => null,
                'tap' => null,
                'booked' => null,
                'outlet' => null,
                'status' => "READY",
            ]);
        return redirect('nocanadmin')->with('status', 'Data Berhasil Diperbaharui!');
    }
}
