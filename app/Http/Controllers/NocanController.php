<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NocanController extends Controller
{

    public function index()
    {

        $data = DB::table('nocan')
            ->select("*")
            ->where('status', 'ready')
            // ->where('alokasi','lama')
            ->get();

        return view('nocan', compact('data'));
    }

    public function form(Request $request)
    {

        $nomor = $request->input('nomor');
        // Query the database to fetch the price for the selected number

        $data = DB::table('nocan')
            ->select("*")
            ->where('status', 'ready')
            // ->where('alokasi','lama')
            ->get();

        return view('form/form-nocan', compact('data',));

        // abort(404);
    }

    public function nocanproses(Request $request)
    {
        $tgl = $request->input('tgl');
        $tap = $request->input('tap');
        $penjual = $request->input('penjual');
        $nomor = $request->input('nomor');
        // $harga = $request->input('harga');
        $status = $request->input('status');
        $outlet = $request->input('outlet');

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
                    // 'harga' => $harga,
                    'status' => $status,
                    'outlet' => $outlet,
                ]);
            return redirect('nocan')->with('status', 'Data Berhasil Ditambahkan!');
        } else {
            return redirect('/form/form-nocan')->withErrors(['error' => 'Gagal!, Nomor Sudah ada yang booking!']);
        }
    }
}
