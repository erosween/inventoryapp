<?php

namespace App\Http\Controllers;

use App\Models\SuryaVisit;
use App\Services\OptimizedImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SuryaVisitController extends Controller
{
    public function __construct(private readonly OptimizedImageStorage $imageStorage)
    {
    }

    public function menu(Request $request)
    {
        if ($redirect = $this->redirectToActiveVisitWhenDifferent($request->input('id_outlet'))) {
            return $redirect;
        }

        $outlet = $request->filled('id_outlet')
            ? $this->assignedOutlets()->where('id_outlet', $request->id_outlet)->first(['id_outlet', 'nama_outlet'])
            : null;
        if ($request->filled('id_outlet')) {
            abort_unless($outlet, 404, 'Outlet tidak terdaftar pada Sales Force ini.');
        }

        $visit = $outlet ? SuryaVisit::query()
            ->where('idsf', session('mobile_sf_id'))
            ->where('id_outlet', $outlet->id_outlet)
            ->whereDate('visited_at', now()->toDateString())
            ->latest('visited_at')
            ->first() : null;
        $photoDone = $visit
            && filled($visit->photo_front_path)
            && filled($visit->photo_inside_path);
        $canSell = $photoDone && in_array($visit->gps_status, ['valid', 'approved'], true);

        return view('mobile.visit-menu', compact('outlet', 'visit', 'photoDone', 'canSell'));
    }

    public function create(Request $request)
    {
        if ($redirect = $this->redirectToActiveVisitWhenDifferent($request->input('id_outlet'))) {
            return $redirect;
        }

        if ($redirect = $this->redirectWhenPhotosAlreadyCompleted($request->input('id_outlet'))) {
            return $redirect;
        }

        $outlets = $this->assignedOutlets()
            ->orderBy('nama_outlet')
            ->get(['id_outlet', 'nama_outlet', 'latitude', 'longitude']);

        return view('mobile.visit', compact('outlets'));
    }

    public function status(Request $request)
    {
        $request->validate(['id_outlet' => ['required', 'string']]);

        $visit = SuryaVisit::query()
            ->where('idsf', session('mobile_sf_id'))
            ->where('id_outlet', $request->id_outlet)
            ->whereDate('visited_at', now()->toDateString())
            ->latest('visited_at')
            ->first();

        abort_unless($visit, 404, 'Visit tidak ditemukan.');

        $photoDone = filled($visit->photo_front_path) && filled($visit->photo_inside_path);
        $canSell = $photoDone && in_array($visit->gps_status, ['valid', 'approved'], true);

        return response()->json([
            'gps_status' => $visit->gps_status,
            'photo_done' => $photoDone,
            'can_sell' => $canSell,
            'message' => $canSell
                ? 'Visit sudah disetujui. Input penjualan telah dibuka.'
                : 'Belum ada persetujuan admin. Silakan cek kembali beberapa saat lagi.',
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_outlet' => ['required'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'accuracy' => ['nullable', 'numeric'],
            'photo_front' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'photo_inside' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($redirect = $this->redirectToActiveVisitWhenDifferent($request->input('id_outlet'))) {
            return $redirect;
        }

        if ($redirect = $this->redirectWhenPhotosAlreadyCompleted($request->input('id_outlet'))) {
            return $redirect;
        }

        $outlet = $this->assignedOutlets()->where('id_outlet', $request->id_outlet)->first();
        abort_unless($outlet, 404, 'Outlet tidak terdaftar pada Sales Force ini.');

        $currentLatitude = (float) $request->latitude;
        $currentLongitude = (float) $request->longitude;
        $distance = null;

        if ($outlet->latitude !== null && $outlet->longitude !== null) {
            $distance = $this->distanceInMeters(
                $currentLatitude,
                $currentLongitude,
                (float) $outlet->latitude,
                (float) $outlet->longitude
            );
        }

        $gpsStatus = $distance !== null && $distance <= 10
            ? 'valid'
            : 'pending_admin';

        $frontPath = null;
        $insidePath = null;

        try {
            $frontPath = $this->imageStorage->store($request->file('photo_front'), 'surya-visits/front')['path'];
            $insidePath = $this->imageStorage->store($request->file('photo_inside'), 'surya-visits/inside')['path'];

            SuryaVisit::create([
                'visited_at' => now(),
                'id_outlet' => $outlet->id_outlet,
                'idtap' => session('idtap'),
                'idsf' => session('mobile_sf_id'),
                'latitude' => $currentLatitude,
                'longitude' => $currentLongitude,
                'accuracy' => $request->accuracy,
                'distance_meters' => $distance,
                // Tetap isi kolom lama agar data kompatibel dengan fitur sebelumnya.
                'photo_path' => $frontPath,
                'photo_front_path' => $frontPath,
                'photo_inside_path' => $insidePath,
                'gps_status' => $gpsStatus,
                'notes' => $request->notes,
            ]);
        } catch (\Throwable $exception) {
            foreach (array_filter([$frontPath, $insidePath]) as $path) {
                Storage::disk('public')->delete([$path, OptimizedImageStorage::thumbnailPathFor($path)]);
            }
            throw $exception;
        }

        $message = $gpsStatus === 'valid'
            ? 'Visit berhasil dicatat. Lokasi sesuai dengan koordinat outlet.'
            : 'Visit berhasil dicatat. Lokasi berbeda dan menunggu persetujuan admin sebelum penjualan dapat diinput.';

        return redirect()
            ->route('mobile.visit.menu', ['id_outlet' => $outlet->id_outlet])
            ->with('success', $message);
    }

    private function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }

    private function redirectToActiveVisitWhenDifferent(?string $requestedOutlet)
    {
        $idsf = (string) session('mobile_sf_id');
        if ($idsf === '') {
            return null;
        }

        $activeVisit = SuryaVisit::query()
            ->inProgressForSalesForce($idsf)
            ->latest('visited_at')
            ->first();

        if (! $activeVisit || strtoupper((string) $requestedOutlet) === strtoupper($activeVisit->id_outlet)) {
            return null;
        }

        return redirect()
            ->route('mobile.visit.menu', ['id_outlet' => $activeVisit->id_outlet])
            ->with('error', 'Masih ada visit yang sedang berlangsung. Selesaikan input penjualannya sebelum membuka outlet lain.');
    }

    private function redirectWhenPhotosAlreadyCompleted(?string $requestedOutlet)
    {
        if (! $requestedOutlet) {
            return null;
        }

        $photoDone = SuryaVisit::query()
            ->where('idsf', session('mobile_sf_id'))
            ->where('id_outlet', $requestedOutlet)
            ->whereDate('visited_at', now()->toDateString())
            ->whereNotNull('photo_front_path')
            ->whereNotNull('photo_inside_path')
            ->exists();

        if (! $photoDone) {
            return null;
        }

        return redirect()
            ->route('mobile.visit.menu', ['id_outlet' => $requestedOutlet])
            ->with('error', 'Dua foto visit sudah tersimpan dan tidak dapat diambil ulang. Lanjutkan proses penjualan outlet ini.');
    }

    private function assignedOutlets()
    {
        return DB::table('appsdumais')->whereRaw(
            'UPPER(TRIM(sf)) = ?',
            [mb_strtoupper(trim((string) session('mobile_sf_name')))]
        );
    }
}
