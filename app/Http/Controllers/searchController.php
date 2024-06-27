<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class searchController extends Controller
{
    public function index()
    {
        return view('search-view');
    }

    public function search(Request $request)
    {
        $search = $request->input('search');
        $results = DB::table('nocan')
                   ->where('nomor', 'LIKE', "%{$search}%")
                   ->where('status', 'ready')
                   ->where('cluster', 'dumai bengkalis')
                   ->paginate(12);
                   
        return view('pilihnocan', compact('results'));
    }
}
