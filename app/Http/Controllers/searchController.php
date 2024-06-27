<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class searchController extends Controller
{
    public function search(Request $request)
{
    $search = $request->input('search');

    $results = DB::table('nocan')
                ->where('nomor', 'LIKE', "%{$search}%")
                ->where('status', 'ready')
                ->where('cluster', 'dumai bengkalis')
                ->paginate(12);

    $found = $results->contains(function($result) use ($search) {
        return strpos($result->nomor, $search) !== false;
    });

    $message = !$found ? "Maaf, Nomor yang Anda Cari Belum Tersedia. Silakan Masukkan Pilihan Lain." : null;

    return view('search', compact('results', 'message'));
}


}
