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
        if (! $outlet) {
            $outlet = SuryaVisit::query()
                ->where('idsf', session('mobile_sf_id'))
                ->where('id_outlet', $request->id_outlet)
                ->latest('visited_at')
                ->first(['id_outlet', 'outlet_name as nama_outlet']);
        }
        if ($request->filled('id_outlet')) {
            abort_unless($outlet, 404, 'Outlet tidak terdaftar pada Sales Force ini.');
        }

        $visit = $outlet ? SuryaVisit::query()
            ->where('idsf', session('mobile_sf_id'))
            ->where('id_outlet', $outlet->id_outlet)
            ->whereDate('visited_at', now()->toDateString())
            ->latest('visited_at')
            ->first() : null;
        $this->autoApproveExpiredVisit($visit);
        $visit?->refresh();
        $photoDone = $visit
            && $visit->gps_status !== 'rejected'
            && filled($visit->photo_front_path)
            && filled($visit->photo_inside_path);
        $needsStockOwner = $visit
            && $this->isHelperAccount()
            && in_array($visit->visit_type, ['digipos_unmapped', 'non_digipos'], true)
            && blank($visit->stock_owner_idsf);
        $stockOwners = $needsStockOwner
            ? DB::table('idsf')->where('idtap', session('idtap'))->where('idsf', 'not like', 'HELPER%')->orderBy('namasf')->get(['idsf', 'namasf'])
            : collect();
        $canSell = ! $needsStockOwner
            && $photoDone
            && ($visit->visit_type === 'non_digipos' || in_array($visit->gps_status, ['valid', 'approved'], true));

        return view('mobile.visit-menu', compact('outlet', 'visit', 'photoDone', 'canSell', 'needsStockOwner', 'stockOwners'));
    }

    public function create(Request $request)
    {
        if ($redirect = $this->redirectToActiveVisitWhenDifferent($request->input('id_outlet'))) {
            return $redirect;
        }

        if ($redirect = $this->redirectWhenPhotosAlreadyCompleted($request->input('id_outlet'))) {
            return $redirect;
        }

        $mode = $request->input('mode', $request->filled('id_outlet') ? 'pjp' : 'extra_call');
        abort_unless(in_array($mode, ['pjp', 'extra_call', 'digipos_unmapped', 'non_digipos'], true), 404);
        $outletQuery = $mode === 'digipos_unmapped'
            ? $this->tapOutlets()->whereRaw("UPPER(TRIM(COALESCE(sf, ''))) = 'UNMAPPING'")
            : $this->assignedOutlets()->whereRaw("UPPER(TRIM(COALESCE(sf, ''))) <> 'UNMAPPING'");
        $outlets = $outletQuery
            ->orderBy('nama_outlet')
            ->get(['id_outlet', 'nama_outlet', 'latitude', 'longitude']);
        $stockOwners = $this->isHelperAccount() && in_array($mode, ['digipos_unmapped', 'non_digipos'], true)
            ? DB::table('idsf')->where('idtap', session('idtap'))->where('idsf', 'not like', 'HELPER%')->orderBy('namasf')->get(['idsf', 'namasf'])
            : collect();

        return view('mobile.visit', compact('outlets', 'mode', 'stockOwners'));
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

        $this->autoApproveExpiredVisit($visit);
        $visit->refresh();

        $photoDone = $visit->gps_status !== 'rejected'
            && filled($visit->photo_front_path)
            && filled($visit->photo_inside_path);
        $canSell = $photoDone && ($visit->visit_type === 'non_digipos' || in_array($visit->gps_status, ['valid', 'approved'], true));

        return response()->json([
            'gps_status' => $visit->gps_status,
            'photo_done' => $photoDone,
            'can_sell' => $canSell,
            'message' => match (true) {
                $canSell => 'Visit sudah disetujui. Input penjualan telah dibuka.',
                $visit->gps_status === 'rejected' => 'Foto visit ditolak admin. Silakan ambil ulang foto depan dan foto dalam.',
                default => 'Belum ada persetujuan admin. Silakan cek kembali beberapa saat lagi.',
            },
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'id_outlet' => ['required', 'string'],
            'checkout_reason' => ['required', 'in:outlet_tutup,pemilik_tidak_ada,menolak_visit,lainnya'],
            'checkout_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $visit = SuryaVisit::query()
            ->where('idsf', session('mobile_sf_id'))
            ->where('id_outlet', $validated['id_outlet'])
            ->whereDate('visited_at', now()->toDateString())
            ->whereNull('checkout_at')
            ->latest('visited_at')
            ->firstOrFail();

        $visit->update([
            'checkout_at' => now(),
            'checkout_reason' => $validated['checkout_reason'],
            'checkout_notes' => $validated['checkout_notes'] ?? null,
        ]);

        return redirect()
            ->route('mobile.index')
            ->with('success', 'Visit ditutup tanpa penjualan dan tetap dihitung sebagai Actual Call.');
    }

    public function assignStockOwner(Request $request)
    {
        abort_unless($this->isHelperAccount(), 403);
        $validated = $request->validate([
            'id_outlet' => ['required', 'string'],
            'stock_owner_idsf' => ['required', 'string', 'max:25'],
        ]);

        $validOwner = DB::table('idsf')
            ->where('idsf', $validated['stock_owner_idsf'])
            ->where('idtap', session('idtap'))
            ->where('idsf', 'not like', 'HELPER%')
            ->exists();
        abort_unless($validOwner, 422, 'Pilih SF pemilik stok yang sesuai TAP.');

        $visit = SuryaVisit::query()
            ->where('idsf', session('mobile_sf_id'))
            ->where('id_outlet', $validated['id_outlet'])
            ->whereDate('visited_at', now()->toDateString())
            ->whereNull('checkout_at')
            ->whereIn('visit_type', ['digipos_unmapped', 'non_digipos'])
            ->latest('visited_at')
            ->firstOrFail();
        $visit->update(['stock_owner_idsf' => $validated['stock_owner_idsf']]);

        return redirect()
            ->route('mobile.visit.menu', ['id_outlet' => $visit->id_outlet])
            ->with('success', 'SF pemilik stok berhasil dipilih. Input penjualan sudah dapat dilanjutkan.');
    }

    private function autoApproveExpiredVisit(?SuryaVisit $visit): void
    {
        if (! $visit
            || $visit->gps_status !== 'pending_admin'
            || ! $visit->visited_at
            || $visit->visited_at->gt(now('Asia/Jakarta')->subSeconds(15))) {
            return;
        }

        if (SuryaVisit::query()->whereKey($visit->id)->where('gps_status', 'pending_admin')->update(['gps_status' => 'approved'])) {
            DB::table('appsdumais')->where('id_outlet', $visit->id_outlet)->update([
                'latitude' => $visit->latitude,
                'longitude' => $visit->longitude,
            ]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_outlet' => ['required'],
            'visit_type' => ['required', 'in:pjp,extra_call,digipos_unmapped,non_digipos'],
            'stock_owner_idsf' => ['nullable', 'string', 'max:25'],
            'outlet_name' => ['required_if:visit_type,non_digipos', 'nullable', 'string', 'max:150'],
            'owner_name' => ['required_if:visit_type,non_digipos', 'nullable', 'string', 'max:150'],
            'phone_number' => ['required_if:visit_type,non_digipos', 'nullable', 'string', 'max:40'],
            'outlet_address' => ['required_if:visit_type,non_digipos', 'nullable', 'string', 'max:1000'],
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

        $outlet = $request->visit_type === 'non_digipos'
            ? (object) ['id_outlet' => 'NON-DIGIPOS-'.strtoupper(substr(md5(session('mobile_sf_id').'|'.$request->phone_number.'|'.microtime()), 0, 12)), 'nama_outlet' => $request->outlet_name, 'latitude' => null, 'longitude' => null]
            : ($request->visit_type === 'digipos_unmapped'
                ? $this->tapOutlets()->whereRaw("UPPER(TRIM(COALESCE(sf, ''))) = 'UNMAPPING'")->where('id_outlet', $request->id_outlet)->first()
                : $this->assignedOutlets()->whereRaw("UPPER(TRIM(COALESCE(sf, ''))) <> 'UNMAPPING'")->where('id_outlet', $request->id_outlet)->first());
        abort_unless($outlet, 404, 'Outlet tidak terdaftar pada Sales Force ini.');

        $stockOwnerIdsf = null;
        if (in_array($request->visit_type, ['digipos_unmapped', 'non_digipos'], true)) {
            $stockOwnerIdsf = $this->isHelperAccount()
                ? trim((string) $request->stock_owner_idsf)
                : (string) session('mobile_sf_id');
            abort_unless($stockOwnerIdsf !== '' && DB::table('idsf')
                ->where('idsf', $stockOwnerIdsf)
                ->where('idtap', session('idtap'))
                ->where('idsf', 'not like', 'HELPER%')
                ->exists(), 422, 'Pilih SF pemilik stok yang sesuai TAP.');
        }

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

        $gpsStatus = $request->visit_type === 'non_digipos'
            ? 'valid'
            : ($distance !== null && $distance <= 10 ? 'valid' : 'pending_admin');

        $frontPath = null;
        $insidePath = null;

        if ($this->imageStorage->isLikelyBlack($request->file('photo_front'))
            || $this->imageStorage->isLikelyBlack($request->file('photo_inside'))) {
            return back()->withInput()->withErrors([
                'photo_front' => 'Foto terlalu gelap/hitam. Silakan ambil ulang foto dengan kamera dan pastikan preview terlihat jelas.',
            ]);
        }

        try {
            $frontPath = $this->imageStorage->store($request->file('photo_front'), 'surya-visits/front')['path'];
            $insidePath = $this->imageStorage->store($request->file('photo_inside'), 'surya-visits/inside')['path'];

            $rejectedVisit = SuryaVisit::query()
                ->where('idsf', session('mobile_sf_id'))
                ->where('id_outlet', $outlet->id_outlet)
                ->whereDate('visited_at', now()->toDateString())
                ->where('gps_status', 'rejected')
                ->latest('visited_at')
                ->first();
            $oldPaths = $rejectedVisit
                ? array_filter([$rejectedVisit->photo_path, $rejectedVisit->photo_front_path, $rejectedVisit->photo_inside_path])
                : [];

            $visitData = [
                'visited_at' => now(),
                'id_outlet' => $outlet->id_outlet,
                'idtap' => session('idtap'),
                'idsf' => session('mobile_sf_id'),
                'visit_type' => $request->visit_type,
                'stock_owner_idsf' => $stockOwnerIdsf,
                'outlet_name' => $request->outlet_name ?: $outlet->nama_outlet,
                'owner_name' => $request->owner_name,
                'phone_number' => $request->phone_number,
                'outlet_address' => $request->outlet_address,
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
                'checkout_at' => null,
                'checkout_reason' => null,
                'checkout_notes' => null,
            ];

            if ($rejectedVisit) {
                $rejectedVisit->update($visitData);
                foreach (array_unique($oldPaths) as $path) {
                    Storage::disk('public')->delete([$path, OptimizedImageStorage::thumbnailPathFor($path)]);
                }
            } else {
                SuryaVisit::create($visitData);
            }
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
            ->where('gps_status', '!=', 'rejected')
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
        $name = mb_strtoupper(trim((string) session('mobile_sf_name')));
        $idsf = mb_strtoupper(trim((string) session('mobile_sf_id')));

        // Helper accounts may visit and sell at any outlet in their TAP.
        if (str_starts_with($idsf, 'HELPER')) {
            return DB::table('appsdumais')
                ->whereRaw('UPPER(TRIM(tap)) = ?', [mb_strtoupper(trim((string) session('idtap')))]);
        }

        $assigned = DB::table('appsdumais')->where(function ($query) use ($name, $idsf) {
            $query->whereRaw('UPPER(TRIM(sf)) = ?', [$name])
                ->orWhereRaw('UPPER(TRIM(sf)) = ?', [$idsf]);
        });

        if ($assigned->exists()) {
            return $assigned;
        }

        return DB::table('appsdumais')
            ->whereRaw('UPPER(TRIM(tap)) = ?', [mb_strtoupper(trim((string) session('idtap')))]);
    }

    private function tapOutlets()
    {
        return DB::table('appsdumais')
            ->whereRaw('UPPER(TRIM(tap)) = ?', [mb_strtoupper(trim((string) session('idtap')))]);
    }

    private function isHelperAccount(): bool
    {
        return str_starts_with(mb_strtoupper(trim((string) session('mobile_sf_id'))), 'HELPER');
    }
}
