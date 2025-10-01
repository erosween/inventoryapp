<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitaDumaiController extends Controller
{
    public function index()
    {
        return view('monitadumai.index');
    }

    public function search(Request $request)
    {
        $keyword = $request->input('keyword');
        $data = DB::table('appsdumais')
            ->where('idoutlet', $keyword)
            ->orWhere('namaoutlet', 'like', '%' . $keyword . '%')
            ->get();

        return response()->json($data);
    }

    public function suggest(Request $request)
    {
        $keyword = $request->input('keyword');
        $data = DB::table('appsdumais')
            ->where('namaoutlet', 'like', '%' . $keyword . '%')
            ->limit(10)
            ->pluck('namaoutlet');

        return response()->json($data);
    }
}
