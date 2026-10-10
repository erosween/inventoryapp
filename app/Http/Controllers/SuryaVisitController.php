<?php

namespace App\Http\Controllers;

use App\Models\SuryaVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuryaVisitController extends Controller
{
    public function create(Request $request)
    {
        $sfName = session('mobile_sf_name');
        $outlets = DB::table('appsdumais')
            ->where('sf', 'LIKE', '%' . $sfName . '%')
            ->orderBy('nama_outlet')
            ->get(['id_outlet', 'nama_outlet', 'latitude', 'longitude']);

        return view('mobile.visit', compact('outlets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_outlet' => ['required'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'accuracy' => ['nullable', 'numeric'],
            'photo' => ['required', 'image', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $outlet = DB::table('appsdumais')->where('id_outlet', $request->id_outlet)->first();
        abort_unless($outlet, 404, 'Outlet tidak ditemukan.');
        abort_if($outlet->latitude === null || $outlet->longitude === null, 422, 'Koordinat outlet belum tersedia.');

        $distance = $this->distanceInMeters(
            (float) $request->latitude,
            (float) $request->longitude,
            (float) $outlet->latitude,
            (float) $outlet->longitude
        );
        $radius = 100;
        abort_if($distance > $radius, 422, 'Anda berada ' . number_format($distance, 0) . ' meter dari outlet. Maksimal radius ' . $radius . ' meter.');

        $path = $request->file('photo')->store('surya-visits', 'public');
        SuryaVisit::create([
            'visited_at' => now(),
            'id_outlet' => $outlet->id_outlet,
            'idtap' => session('idtap'),
            'idsf' => session('mobile_sf_id'),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
            'distance_meters' => $distance,
            'photo_path' => $path,
            'gps_status' => 'valid',
            'notes' => $request->notes,
        ]);

        return redirect()->route('mobile.index')->with('success', 'Visit outlet berhasil dicatat.');
    }

    private function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }
}
