<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function index()
    {
        return view('search');
    }

    public function search(Request $request)
    {
        $search = $request->input('search');

        // Query utama
        $results = DB::table('nocan')
            ->where('nomor', 'LIKE', "%{$search}%")
            ->where('status', 'ready')
            ->where('cluster', 'dumai bengkalis')
            ->paginate(12); // ✅ Per halaman 12 nomor

        // Biar parameter pencarian ikut ke pagination
        $results->appends(['search' => $search]);

        // Cek kalau data kosong
        $found = $results->count() > 0;
        $message = !$found ? "Maaf, Nomor yang Anda Cari Belum Tersedia. Silakan Masukkan Pilihan Lain." : null;

        return view('search', compact('results', 'message', 'search'));
    }
}
