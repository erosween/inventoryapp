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
            ->leftJoin('outlet_performance', 'appsdumais.id_outlet', '=', 'outlet_performance.id_outlet')
            ->select(
                'appsdumais.*',
                'outlet_performance.total_m as m_stpv',
                'outlet_performance.total_m1 as m1_stpv',
                'outlet_performance.total_mom as mom_stpv',
                'outlet_performance.tgl_update as tgl_pv'
            )
            ->where('appsdumais.id_outlet', $keyword)
            ->orWhere('appsdumais.nama_outlet', 'like', '%' . $keyword . '%')
            ->get();

        return response()->json($data);
    }

    public function suggest(Request $request)
    {
        $keyword = $request->input('keyword');
        $data = DB::table('appsdumais')
            ->where('nama_outlet', 'like', '%' . $keyword . '%')
            ->orWhere('id_outlet', 'like', '%' . $keyword . '%')
            ->select('nama_outlet', 'sf', 'tap')
            ->limit(10)
            ->get();

        return response()->json($data);
    }

    public function nearby(Request $request)
    {
        $lat = $request->input('latitude');
        $long = $request->input('longitude');
        $radius = $request->input('radius', 0.3);

        if (!$lat || !$long) {
            return response()->json(['error' => 'Latitude and longitude are required'], 400);
        }

        // Optimization: Bounding Box filter to use indices and reduce candidate set
        // 1 degree of latitude is approximately 111 km
        $lat_delta = $radius / 111.0;
        // 1 degree of longitude is approximately 111 km * cos(latitude)
        $lon_delta = $radius / (111.0 * cos(deg2rad($lat)));

        // Haversine formula to find outlets within preferred radius
        $data = DB::table('appsdumais')
            ->whereBetween('latitude', [$lat - $lat_delta, $lat + $lat_delta])
            ->whereBetween('longitude', [$long - $lon_delta, $long + $lon_delta])
            ->leftJoin('outlet_performance', 'appsdumais.id_outlet', '=', 'outlet_performance.id_outlet')
            ->select(
                'appsdumais.*',
                'outlet_performance.total_m as m_stpv',
                'outlet_performance.total_m1 as m1_stpv',
                'outlet_performance.total_mom as mom_stpv',
                'outlet_performance.tgl_update as tgl_pv'
            )
            ->selectRaw(
                '( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
                [$lat, $long, $lat]
            )
            ->where('sf', '!=', 'UNMAPPING')
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->limit(50)
            ->get();

        return response()->json($data);
    }

    public function performance(Request $request)
    {
        $id_outlet = $request->input('id_outlet');
        $data = DB::table('outlet_performance')
            ->where('id_outlet', $id_outlet)
            ->first();

        return response()->json($data);
    }
}
