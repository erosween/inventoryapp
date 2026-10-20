<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MobilePenjualan;
use App\Models\MobileSalesReceipt;
use App\Models\SuryaVisit;
use App\Services\OptimizedImageStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class MobileSalesController extends Controller
{
    public function __construct(private readonly OptimizedImageStorage $imageStorage)
    {
    }
    public function searchOutlet(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $namasf = trim((string) session('mobile_sf_name'));
        $idsf = trim((string) session('mobile_sf_id'));

        $outlets = $this->mobileOutletQuery()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('nama_outlet', 'LIKE', "%{$search}%")
                        ->orWhere('id_outlet', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('nama_outlet')
            ->limit(30)
            ->get(['id_outlet', 'nama_outlet']);

        return response()->json($outlets);
    }

    public function index()
    {
        $idsf = session('mobile_sf_id');
        $namasf = trim((string) session('mobile_sf_name'));
        $idsf = trim((string) session('mobile_sf_id'));
        $today = now();
        $todayDate = $today->toDateString();
        
        $latest = MobilePenjualan::where('idsf', $idsf)
            ->whereNotIn('status', ['draft_wa', 'rejected'])
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->select('mobile_penjualan.*', 'denom.denom')
            ->orderBy('mobile_penjualan.created_at', 'desc')
            ->limit(5)
            ->get();

        $salesBase = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->where('m.idsf', $idsf)
            ->whereNotIn('m.status', ['draft_wa', 'rejected']);
        $totalsSelect = <<<'SQL'
COALESCE(SUM(CASE WHEN UPPER(COALESCE(d.kategori_inject, '')) = 'SA' OR UPPER(COALESCE(d.group_name, '')) = 'SA' THEN m.qty ELSE 0 END), 0) AS sa,
COALESCE(SUM(CASE WHEN UPPER(COALESCE(d.kategori_inject, '')) = 'SA' OR UPPER(COALESCE(d.group_name, '')) = 'SA' THEN 0 ELSE m.qty END), 0) AS pv
SQL;

        $todayTotals = (clone $salesBase)
            ->whereDate('m.tgl', $todayDate)
            ->selectRaw($totalsSelect)
            ->first();
        $monthTotals = (clone $salesBase)
            ->whereMonth('m.tgl', $today->month)
            ->whereYear('m.tgl', $today->year)
            ->selectRaw($totalsSelect)
            ->first();

        // PJP List Logic
        $days = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];
        $today_name = $days[$today->format('l')];

        $pjp_list = DB::table('appsdumais')
            ->where(function ($sfQuery) use ($namasf, $idsf) {
                $sfQuery->whereRaw('UPPER(TRIM(sf)) = ?', [mb_strtoupper($namasf)])
                    ->orWhereRaw('UPPER(TRIM(sf)) = ?', [mb_strtoupper($idsf)]);
            })
            ->whereRaw('UPPER(TRIM(pjp)) = ?', [mb_strtoupper($today_name)])
            ->orderBy('nama_outlet')
            ->get();

        // Load today's sales and visits in bulk. This avoids one visit query per
        // PJP card when many Sales Force users open the dashboard together.
        $salesVisitedOutlets = MobilePenjualan::where('idsf', $idsf)
            ->whereDate('tgl', $todayDate)
            ->whereNotIn('status', ['draft_wa', 'rejected'])
            ->pluck('id_outlet')
            ->unique()
            ->toArray();

        $todayVisits = DB::table('surya_visits')
            ->where('idsf', $idsf)
            ->whereDate('visited_at', $todayDate)
            ->orderByDesc('visited_at')
            ->get(['id_outlet', 'gps_status'])
            ->unique('id_outlet')
            ->keyBy(fn ($visit) => (string) $visit->id_outlet);

        $visited_outlets = collect($salesVisitedOutlets)
            ->merge($todayVisits->keys())
            ->filter()
            ->unique()
            ->values()
            ->all();

        $activeVisit = SuryaVisit::query()
            ->inProgressForSalesForce($idsf)
            ->latest('visited_at')
            ->first();

        if ($activeVisit) {
            $activeVisit->outlet_name = DB::table('appsdumais')
                ->where('id_outlet', $activeVisit->id_outlet)
                ->value('nama_outlet') ?? $activeVisit->id_outlet;
        }

        foreach($pjp_list as $pjp) {
            $pjp->is_visited = in_array($pjp->id_outlet, $visited_outlets);
            $visit = $todayVisits->get((string) $pjp->id_outlet);
            $pjp->photo_status = $visit->gps_status ?? null;
        }

        // Calculate Extra Call visits (outlets visited outside the day's PJP schedule).
        $pjp_outlet_ids = $pjp_list->pluck('id_outlet')->toArray();
        $non_pjp_visited_ids = array_diff($visited_outlets, $pjp_outlet_ids);
        
        $non_pjp_list = DB::table('appsdumais')
            ->whereIn('id_outlet', $non_pjp_visited_ids)
            ->get();

        return response()
            ->view('mobile.index', compact('latest', 'todayTotals', 'monthTotals', 'pjp_list', 'non_pjp_list', 'today_name', 'activeVisit'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function mobileOutletQuery()
    {
        $name = mb_strtoupper(trim((string) session('mobile_sf_name')));
        $idsf = mb_strtoupper(trim((string) session('mobile_sf_id')));
        $tap = mb_strtoupper(trim((string) session('idtap')));

        // Helper accounts work across every outlet in their TAP. They are not
        // limited to the outlet owner recorded in appsdumais.sf.
        if ($this->isHelperAccount()) {
            return DB::table('appsdumais')
                ->whereRaw('UPPER(TRIM(tap)) = ?', [$tap]);
        }

        $sfQuery = DB::table('appsdumais')->where(function ($query) use ($name, $idsf) {
            $query->whereRaw('UPPER(TRIM(sf)) = ?', [$name])
                ->orWhereRaw('UPPER(TRIM(sf)) = ?', [$idsf]);
        });

        // Regular SF accounts stay scoped to their assigned outlet list. If
        // no assignment exists, preserve the legacy TAP fallback.
        if ($sfQuery->exists()) {
            return $sfQuery;
        }

        return DB::table('appsdumais')->whereRaw('UPPER(TRIM(tap)) = ?', [$tap]);
    }

    public function form(Request $request)
    {
        $idtap = session('idtap');
        $idsf = session('mobile_sf_id');
        $pre_id_outlet = $request->query('id_outlet');

        $activeVisit = SuryaVisit::query()
            ->inProgressForSalesForce($idsf)
            ->latest('visited_at')
            ->first();

        if ($activeVisit && strtoupper((string) $pre_id_outlet) !== strtoupper($activeVisit->id_outlet)) {
            return redirect()
                ->route('mobile.visit.menu', ['id_outlet' => $activeVisit->id_outlet])
                ->with('error', 'Selesaikan penjualan outlet yang sedang berlangsung sebelum membuka outlet lain.');
        }

        $draftReceipt = $pre_id_outlet ? MobileSalesReceipt::query()
            ->where('idsf', $idsf)
            ->where('id_outlet', strtoupper($pre_id_outlet))
            ->whereDate('tgl', now()->toDateString())
            ->whereNull('whatsapp_shared_at')
            ->latest('id')
            ->first() : null;

        if ($draftReceipt) {
            return redirect()
                ->route('mobile.receipt.show', $draftReceipt)
                ->with('error', 'Selesaikan konfirmasi transaksi yang sudah tersimpan terlebih dahulu.');
        }

        $selected_outlet = null;
        if ($pre_id_outlet) {
            $selected_outlet = $this->mobileOutletQuery()
                ->where('id_outlet', strtoupper($pre_id_outlet))
                ->first(['id_outlet', 'nama_outlet']);

            if (! $selected_outlet) {
                $selected_outlet = DB::table('surya_visits')
                    ->where('idsf', $idsf)
                    ->where('id_outlet', strtoupper($pre_id_outlet))
                    ->latest('visited_at')
                    ->first(['id_outlet', 'outlet_name as nama_outlet']);
                if (! $selected_outlet) {
                    return redirect()->route('mobile.index')->with('error', 'Outlet tidak terdaftar untuk Sales Force yang sedang login.');
                }
            }

            $this->grantSalesFormAccess($idsf, strtoupper((string) $selected_outlet->id_outlet));
        }
        
        $stockOwnerIdsf = $idsf;
        if ($this->isHelperAccount() && $selected_outlet) {
            $stockOwnerIdsf = $this->resolveOutletOwnerIdsf((string) $selected_outlet->id_outlet);
            if (! $stockOwnerIdsf) {
                return redirect()
                    ->route('mobile.visit.menu', ['id_outlet' => $selected_outlet->id_outlet])
                    ->with('error', 'SF pemilik outlet belum terhubung. Hubungi admin sebelum input penjualan.');
            }
        }

        $reserved = DB::table('mobile_penjualan')
            ->select('iddenom', DB::raw('SUM(qty) as reserved_qty'))
            ->where(function ($query) use ($stockOwnerIdsf) {
                $query->where('owner_idsf', $stockOwnerIdsf)
                    ->orWhere(function ($legacy) use ($stockOwnerIdsf) {
                        $legacy->whereNull('owner_idsf')->where('idsf', $stockOwnerIdsf);
                    });
            })
            // Draft WhatsApp rows are not stock reservations. They are only
            // temporary client-side submissions until the receipt is sent.
            ->where('status', 'pending')
            ->groupBy('iddenom');

        $denoms = DB::table('denom')
            ->join('stockawalsf', function($join) use ($stockOwnerIdsf) {
                $join->on('denom.iddenom', '=', 'stockawalsf.iddenom')
                     ->where('stockawalsf.idsf', '=', $stockOwnerIdsf);
            })
            ->leftJoinSub($reserved, 'reserved_sales', function ($join) {
                $join->on('reserved_sales.iddenom', '=', 'stockawalsf.iddenom');
            })
            ->whereRaw('(stockawalsf.stock - COALESCE(reserved_sales.reserved_qty, 0)) > 0')
            ->select('denom.iddenom', 'denom.denom', 'denom.group_name', 'denom.product_group', DB::raw('(stockawalsf.stock - COALESCE(reserved_sales.reserved_qty, 0)) as stock_qty'))
            ->get()
            ->sortByDesc(fn($item) => strtoupper((string) ($item->product_group ?? 'PV')) === 'SP' ? 1 : 0);

        return view('mobile.form', compact('denoms', 'idtap', 'idsf', 'pre_id_outlet', 'selected_outlet'));
    }

    public function store(Request $request)
    {
        $products = collect($request->input('products', []))
            ->filter(fn ($row) => filled($row['iddenom'] ?? null) || filled($row['qty'] ?? null))
            ->values()
            ->all();
        $request->merge(['products' => $products]);

        $request->validate([
            'id_outlet' => 'required',
            'products' => 'nullable|array',
            'products.*.iddenom' => 'required|distinct|exists:denom,iddenom',
            'products.*.qty' => 'required|integer|min:1',
            'consignment_enabled' => 'nullable|boolean',
            'consignment_numbers' => 'nullable|string|max:10000',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $consignmentNumbers = [];
        if ($request->boolean('consignment_enabled')) {
            [$consignmentNumbers, $invalidNumbers, $duplicateNumbers] = $this->parseConsignmentNumbers(
                $request->input('consignment_numbers')
            );

            if ($consignmentNumbers === []) {
                return back()->withInput()->withErrors([
                    'consignment_numbers' => 'Masukkan minimal satu nomor perdana SA.',
                ]);
            }
            if ($invalidNumbers !== []) {
                return back()->withInput()->withErrors([
                    'consignment_numbers' => 'Format nomor tidak valid: '.implode(', ', array_slice($invalidNumbers, 0, 5)).'.',
                ]);
            }
            if ($duplicateNumbers !== []) {
                return back()->withInput()->withErrors([
                    'consignment_numbers' => 'Nomor duplikat pada isian: '.implode(', ', array_slice($duplicateNumbers, 0, 5)).'.',
                ]);
            }

            $registered = DB::table('surya_sa_consignment_numbers as c')
                ->leftJoin('appsdumais as o', 'o.id_outlet', '=', 'c.id_outlet')
                ->whereIn('c.serial_number', $consignmentNumbers)
                ->get(['c.serial_number', 'c.id_outlet', 'o.nama_outlet']);
            // Existing numbers are allowed: they will be moved to the outlet
            // currently selected in this transaction.
        }

        if ($products === [] && $consignmentNumbers === []) {
            return back()->withInput()->withErrors([
                'products' => 'Pilih minimal satu produk atau isi nomor konsinyasi SA.',
            ]);
        }

        $idsf = session('mobile_sf_id');
        $salesDate = now()->toDateString();
        $requestedOutlet = strtoupper($request->id_outlet);

        $isAssignedOutlet = $this->mobileOutletQuery()
            ->where('id_outlet', $requestedOutlet)
            ->exists();
        $selectedVisit = DB::table('surya_visits')->where('idsf', $idsf)->where('id_outlet', $requestedOutlet)->whereDate('visited_at', $salesDate)->latest('visited_at')->first();
        $isNonDigipos = $selectedVisit && $selectedVisit->visit_type === 'non_digipos';
        if (! $isAssignedOutlet && ! $selectedVisit) {
            return back()->withInput()->with('error', 'Outlet tidak terdaftar pada Sales Force ini.');
        }

        $activeVisit = SuryaVisit::query()
            ->inProgressForSalesForce($idsf, $salesDate)
            ->latest('visited_at')
            ->first();

        if ($activeVisit && strtoupper($activeVisit->id_outlet) !== $requestedOutlet) {
            return redirect()
                ->route('mobile.visit.menu', ['id_outlet' => $activeVisit->id_outlet])
                ->with('error', 'Penjualan outlet yang sedang berlangsung harus diselesaikan terlebih dahulu.');
        }

        $draftReceipt = MobileSalesReceipt::query()
            ->where('idsf', $idsf)
            ->where('id_outlet', $requestedOutlet)
            ->whereDate('tgl', $salesDate)
            ->whereNull('whatsapp_shared_at')
            ->latest('id')
            ->first();

        if ($draftReceipt) {
            return redirect()
                ->route('mobile.receipt.show', $draftReceipt)
                ->with('error', 'Transaksi sudah tersimpan. Periksa detail lalu konfirmasi submit penjualan.');
        }

        $approvedVisit = $this->hasSalesFormAccess($idsf, $requestedOutlet, $salesDate)
            || DB::table('surya_visits')
            ->where('idsf', $idsf)
            ->where('id_outlet', $requestedOutlet)
            ->whereDate('visited_at', $salesDate)
            ->whereIn('gps_status', ['valid', 'approved'])
            ->whereNotNull('photo_front_path')
            ->whereNotNull('photo_inside_path')
            ->exists();
        if (! $approvedVisit && ! $isNonDigipos) {
            $pendingVisit = DB::table('surya_visits')
                ->where('idsf', $idsf)
                ->where('id_outlet', $requestedOutlet)
                ->whereDate('visited_at', $salesDate)
                ->where('gps_status', 'pending_admin')
                ->whereNotNull('photo_front_path')
                ->whereNotNull('photo_inside_path')
                ->where('visited_at', '<=', now('Asia/Jakarta')->subSeconds(15))
                ->latest('visited_at')
                ->first();
            if ($pendingVisit && DB::table('surya_visits')->where('id', $pendingVisit->id)->where('gps_status', 'pending_admin')->update(['gps_status' => 'approved'])) {
                DB::table('appsdumais')->where('id_outlet', $requestedOutlet)->update([
                    'latitude' => $pendingVisit->latitude,
                    'longitude' => $pendingVisit->longitude,
                ]);
                $approvedVisit = true;
            }
        }
        if (! $approvedVisit && ! $isNonDigipos) {
            return back()->withInput()->with('error', 'Lengkapi foto tampak depan dan foto etalase, lalu pastikan lokasi valid atau sudah disetujui admin.');
        }

        try {
            $ownerIdsf = $this->resolveOutletOwnerIdsf($requestedOutlet);
            if ($this->isHelperAccount() && ! $ownerIdsf) {
                return back()->withInput()->with('error', 'SF pemilik outlet belum terhubung. Hubungi admin sebelum input penjualan.');
            }
            $ownerIdsf = $ownerIdsf ?: $idsf;

            $receipt = DB::transaction(function () use ($request, $idsf, $ownerIdsf, $salesDate, $requestedOutlet, $consignmentNumbers) {
                $products = collect($request->products)
                    ->groupBy('iddenom')
                    ->map(fn ($rows, $iddenom) => [
                        'iddenom' => $iddenom,
                        'qty' => (int) $rows->sum(fn ($row) => (int) $row['qty']),
                    ])
                    ->values();

                $denoms = DB::table('denom')
                    ->whereIn('iddenom', $products->pluck('iddenom'))
                    ->get(['iddenom', 'denom', 'harga_jual'])
                    ->keyBy('iddenom');

                $totalQty = $products->sum('qty');
                $totalAmount = $products->sum(function ($product) use ($denoms) {
                    return $product['qty'] * (int) ($denoms[$product['iddenom']]->harga_jual ?? 0);
                });

                // Prevent a repeated mobile submit from creating a second
                // identical receipt for the same outlet/date. A different
                // product or quantity is still allowed as a legitimate
                // "Input Lagi" transaction.
                $requestedSignature = $products
                    ->mapWithKeys(fn ($product) => [(string) $product['iddenom'] => (int) $product['qty']])
                    ->sortKeys()
                    ->toJson();
                $existingReceipts = DB::table('mobile_sales_receipts as r')
                    ->join('mobile_penjualan as m', 'm.receipt_id', '=', 'r.id')
                    ->where('r.idsf', $idsf)
                    ->where('r.id_outlet', $requestedOutlet)
                    ->whereDate('r.tgl', $salesDate)
                    ->whereIn('m.status', ['draft_wa', 'pending', 'approved'])
                    ->groupBy('r.id')
                    ->get(['r.id']);
                foreach ($existingReceipts as $existingReceipt) {
                    $existingSignature = DB::table('mobile_penjualan')
                        ->where('receipt_id', $existingReceipt->id)
                        ->whereIn('status', ['draft_wa', 'pending', 'approved'])
                        ->get(['iddenom', 'qty'])
                        ->mapWithKeys(fn ($sale) => [(string) $sale->iddenom => (int) $sale->qty])
                        ->sortKeys()
                        ->toJson();
                    if ($existingSignature === $requestedSignature) {
                        throw new \RuntimeException('Penjualan dengan produk dan qty yang sama untuk outlet ini hari ini sudah pernah disubmit.');
                    }
                }

                $receipt = MobileSalesReceipt::create([
                    'receipt_number' => $this->generateReceiptNumber(),
                    'tgl' => $salesDate,
                    'id_outlet' => $requestedOutlet,
                    'idtap' => session('idtap'),
                    'idsf' => $idsf,
                    'total_qty' => $totalQty,
                    'total_amount' => $totalAmount,
                ]);

                foreach ($products as $product) {
                    $iddenom = $product['iddenom'];
                    $qty = $product['qty'];

                    // 🔒 Lock & cek stok SF
                    $stockSf = DB::table('stockawalsf')
                        ->where('idsf', $ownerIdsf)
                        ->where('iddenom', $iddenom)
                        ->lockForUpdate()
                        ->value('stock');

                    // Ambil nama denom untuk pesan error yang jelas
                    $denomData = DB::table('denom')->where('iddenom', $iddenom)->first();
                    $denomName = $denomData->denom ?? $iddenom;
                    $reservedQty = (int) DB::table('mobile_penjualan')
                        ->where(function ($query) use ($ownerIdsf) {
                            $query->where('owner_idsf', $ownerIdsf)
                                ->orWhere(function ($legacy) use ($ownerIdsf) {
                                    $legacy->whereNull('owner_idsf')->where('idsf', $ownerIdsf);
                                });
                        })
                        ->where('iddenom', $iddenom)
                        // Keep submit validation consistent with the form stock.
                        // A draft WhatsApp receipt has not reserved stock yet.
                        ->where('status', 'pending')
                        ->sum('qty');
                    $availableStock = max(0, (int) ($stockSf ?? 0) - $reservedQty);
                    if ($availableStock < $qty) {
                        throw new \Exception("Stok {$denomName} tidak cukup! Stok tersedia di mobile: " . number_format($availableStock) . ", dibutuhkan: " . number_format($qty));
                    }

                    // ✅ Simpan data penjualan
                    MobilePenjualan::create([
                        'receipt_id' => $receipt->id,
                        'tgl' => $salesDate,
                        'id_outlet' => $requestedOutlet,
                        'idtap' => session('idtap'),
                        'idsf' => $idsf,
                        'owner_idsf' => $ownerIdsf,
                        'iddenom' => $iddenom,
                        'qty' => $qty,
                        'unit_price' => (int) $denoms[$iddenom]->harga_jual > 0
                            ? (int) $denoms[$iddenom]->harga_jual
                            : null,
                        'keterangan' => $request->keterangan,
                        'status' => 'draft_wa',
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                    ]);
                }

                if ($consignmentNumbers !== []) {
                    $timestamp = now();
                    $existingConsignments = DB::table('surya_sa_consignment_numbers')
                        ->whereIn('serial_number', $consignmentNumbers)
                        ->where('id_outlet', '<>', $requestedOutlet)
                        ->whereNotNull('id_outlet')
                        ->get(['serial_number', 'id_outlet']);
                    foreach ($existingConsignments as $existing) {
                        DB::table('surya_sa_consignment_movements')->insert([
                            'serial_number' => $existing->serial_number,
                            'from_id_outlet' => $existing->id_outlet,
                            'to_id_outlet' => $requestedOutlet,
                            'receipt_id' => $receipt->id,
                            'idtap' => session('idtap'),
                            'idsf' => $idsf,
                            'moved_at' => $timestamp,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ]);
                    }
                    DB::table('surya_sa_consignment_numbers')
                        ->whereIn('serial_number', $consignmentNumbers)
                        ->update([
                            'receipt_id' => $receipt->id,
                            'reported_at' => $timestamp,
                            'id_outlet' => $requestedOutlet,
                            'idtap' => session('idtap'),
                            'idsf' => $idsf,
                            'status' => 'draft',
                            'notes' => $request->keterangan,
                            'updated_at' => $timestamp,
                        ]);
                    DB::table('surya_sa_consignment_numbers')->insertOrIgnore(
                        collect($consignmentNumbers)->map(fn (string $number) => [
                            'receipt_id' => $receipt->id,
                            'reported_at' => $timestamp,
                            'id_outlet' => $requestedOutlet,
                            'idtap' => session('idtap'),
                            'idsf' => $idsf,
                            'serial_number' => $number,
                            'status' => 'draft',
                            'notes' => $request->keterangan,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ])->all()
                    );
                }

                return $receipt;
            });

            return redirect()
                ->route('mobile.receipt.show', $receipt)
                ->with('success', 'Detail transaksi sudah disiapkan. Periksa kembali sebelum submit penjualan.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    private function isHelperAccount(): bool
    {
        return str_starts_with(
            mb_strtoupper(trim((string) session('mobile_sf_id'))),
            'HELPER'
        );
    }

    private function resolveOutletOwnerIdsf(string $idOutlet): ?string
    {
        $visitOwner = DB::table('surya_visits')
            ->where('idsf', session('mobile_sf_id'))
            ->where('id_outlet', $idOutlet)
            ->whereDate('visited_at', now()->toDateString())
            ->latest('visited_at')
            ->value('stock_owner_idsf');
        if (filled($visitOwner)) {
            return (string) $visitOwner;
        }

        $ownerName = DB::table('appsdumais')
            ->where('id_outlet', $idOutlet)
            ->value('sf');
        if (! filled($ownerName)) {
            return null;
        }

        $normalizedOwner = mb_strtoupper(trim((string) $ownerName));
        $owner = DB::table('idsf')
            ->get(['idsf', 'namasf'])
            ->first(fn ($salesForce) => mb_strtoupper(trim((string) $salesForce->namasf)) === $normalizedOwner);

        return $owner ? (string) $owner->idsf : null;
    }

    public function checkConsignmentNumbers(Request $request)
    {
        $validated = $request->validate([
            'numbers' => ['required', 'array', 'max:100'],
            'numbers.*' => ['required', 'string', 'regex:/^\d{8,20}$/'],
            'id_outlet' => ['nullable', 'string', 'max:100'],
        ]);

        $numbers = collect($validated['numbers'])->unique()->values();
        $registered = DB::table('surya_sa_consignment_numbers as c')
            ->leftJoin('appsdumais as o', 'o.id_outlet', '=', 'c.id_outlet')
            ->whereIn('c.serial_number', $numbers)
            ->get(['c.serial_number', 'c.id_outlet', 'c.status', 'o.nama_outlet'])
            ->keyBy('serial_number');

        $targetOutlet = $validated['id_outlet'] ?? null;

        return response()->json([
            'results' => $numbers->map(function ($number) use ($registered) {
                $match = $registered->get($number);

                return [
                    'number' => $number,
                    'available' => ! $match || ($targetOutlet && $match->id_outlet !== $targetOutlet),
                    'will_move' => (bool) ($targetOutlet && $match && $match->id_outlet !== $targetOutlet),
                    'id_outlet' => $match->id_outlet ?? null,
                    'nama_outlet' => $match->nama_outlet ?? null,
                ];
            })->values(),
        ]);
    }

    public function history(Request $request)
    {
        $idsf = session('mobile_sf_id');
        $filter_date = $request->get('filter_date');

        $applyDateFilter = function ($query, string $column) use ($filter_date) {
            if (! $filter_date) {
                return $query;
            }

            if (str_contains($filter_date, ' to ')) {
                [$dateFrom, $dateTo] = array_map('trim', explode(' to ', $filter_date, 2));

                return $query->whereBetween($column, [$dateFrom, $dateTo]);
            }

            return $query->whereDate($column, $filter_date);
        };

        // The first level is deliberately grouped by date. Outlet and product
        // details are fetched only after the user opens a date summary.
        $historyQuery = MobilePenjualan::query()
            ->where('mobile_penjualan.idsf', $idsf)
            ->whereNotIn('mobile_penjualan.status', ['draft_wa', 'rejected'])
            ->select(
                'mobile_penjualan.tgl',
                DB::raw('COUNT(DISTINCT mobile_penjualan.id_outlet) as outlet_count'),
                DB::raw("SUM(CASE WHEN mobile_penjualan.status NOT IN ('draft_wa', 'rejected') THEN mobile_penjualan.qty ELSE 0 END) as total_qty"),
                DB::raw("SUM(CASE WHEN mobile_penjualan.status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw("SUM(CASE WHEN mobile_penjualan.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
                DB::raw("SUM(CASE WHEN mobile_penjualan.status = 'pending' THEN 1 ELSE 0 END) as pending_count"),
                DB::raw("SUM(CASE WHEN mobile_penjualan.status = 'draft_wa' THEN 1 ELSE 0 END) as draft_count")
            )
            ->groupBy('mobile_penjualan.tgl');

        $applyDateFilter($historyQuery, 'mobile_penjualan.tgl');

        $history = $historyQuery
            ->orderByDesc('mobile_penjualan.tgl')
            ->paginate(10)
            ->appends(['filter_date' => $filter_date]);

        $historyDates = $history->getCollection()
            ->pluck('tgl')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        $historyDenoms = collect();
        if ($historyDates !== []) {
            $historyDenoms = MobilePenjualan::query()
                ->join('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
                ->where('mobile_penjualan.idsf', $idsf)
                ->whereIn('mobile_penjualan.tgl', $historyDates)
                ->whereNotIn('mobile_penjualan.status', ['draft_wa', 'rejected'])
                ->select(
                    'mobile_penjualan.tgl',
                    'mobile_penjualan.iddenom',
                    'denom.denom',
                    'denom.group_name',
                    'denom.kategori_inject',
                    DB::raw('SUM(mobile_penjualan.qty) as total_qty')
                )
                ->groupBy(
                    'mobile_penjualan.tgl',
                    'mobile_penjualan.iddenom',
                    'denom.denom',
                    'denom.group_name',
                    'denom.kategori_inject'
                )
                ->orderBy('denom.denom')
                ->get()
                ->map(function ($item) {
                    $injectCategory = strtoupper(trim((string) $item->kategori_inject));
                    $groupName = strtoupper(trim((string) $item->group_name));
                    $item->category = $injectCategory === 'SA' || $groupName === 'SA' ? 'SA' : 'PV';

                    return $item;
                })
                ->groupBy(fn ($item) => Carbon::parse($item->tgl)->toDateString());
        }

        // Fetch MasukSF History (Stock Received by SF)
        $masuk_query = DB::table('masuksf')
            ->where('idsf', $idsf)
            ->join('denom', 'masuksf.iddenom', '=', 'denom.iddenom')
            ->select('masuksf.*', 'denom.denom');

        $applyDateFilter($masuk_query, 'masuksf.tgl');
        $masuksf_history = $masuk_query->orderBy('masuksf.tgl', 'desc')
            ->limit(20)
            ->get();

        // Fetch KeluarSF History (Sales inputted by Web Admin)
        $keluar_query = DB::table('keluarsf')
            ->where('idsf', $idsf)
            ->join('denom', 'keluarsf.iddenom', '=', 'denom.iddenom')
            ->select('keluarsf.*', 'denom.denom');

        $applyDateFilter($keluar_query, 'keluarsf.tgl');
        $keluarsf_history = $keluar_query->orderBy('keluarsf.tgl', 'desc')
            ->limit(20)
            ->get();

        return view('mobile.history', compact('history', 'historyDenoms', 'filter_date', 'masuksf_history', 'keluarsf_history'));
    }

    public function edit($id_outlet, $tgl)
    {
        $idsf = session('mobile_sf_id');
        $idtap = session('idtap');
        
        $sales = MobilePenjualan::where('mobile_penjualan.idsf', $idsf)
            ->where('mobile_penjualan.id_outlet', $id_outlet)
            ->where('mobile_penjualan.tgl', $tgl)
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->leftJoin('appsdumais', 'mobile_penjualan.id_outlet', '=', 'appsdumais.id_outlet')
            ->select(
                'mobile_penjualan.*',
                'denom.denom',
                'appsdumais.nama_outlet'
            )
            ->get();
            
        if ($sales->isEmpty()) {
            abort(404);
        }

        if ($sales->whereNotNull('receipt_id')->isNotEmpty()) {
            return redirect()->route('mobile.history')->with('error', 'Transaksi dengan e-Bon tidak dapat diubah setelah disimpan.');
        }

        // Cek jika sudah ada yang di-approve/reject
        $processedCount = $sales->where('status', '!=', 'pending')->count();
        if ($processedCount > 0) {
            return redirect()->route('mobile.history')->with('error', 'Kunjungan ini sudah diverifikasi oleh admin dan tidak dapat diubah lagi.');
        }

        $outletName = $sales->first()->nama_outlet ?? 'Unknown';
        $totalQty = $sales->sum('qty');

        $denoms = DB::table('denom')
            ->join('stockawalsf', function($join) use ($idsf) {
                $join->on('denom.iddenom', '=', 'stockawalsf.iddenom')
                     ->where('stockawalsf.idsf', '=', $idsf);
            })
            ->where('stockawalsf.stock', '>', 0)
            ->select('denom.iddenom', 'denom.denom', 'denom.group_name', DB::raw('stockawalsf.stock as stock_qty'))
            ->get();
        
        return view('mobile.edit', compact('sales', 'id_outlet', 'tgl', 'denoms', 'idtap', 'outletName', 'totalQty'));
    }

    public function update(Request $request, $id_outlet, $tgl)
    {
        $idsf = session('mobile_sf_id');
        $idtap = session('idtap');
        
        $request->validate([
            'products' => 'required|array',
            'products.*.iddenom' => 'required|distinct',
            'products.*.qty' => 'required|numeric|min:1',
            'keterangan' => 'nullable|string',
        ]);

        // Cek lagi status di server
        $existingProcessed = MobilePenjualan::where('idsf', $idsf)
            ->where('id_outlet', $id_outlet)
            ->where('tgl', $tgl)
            ->where('status', '!=', 'pending')
            ->count();
            
        if ($existingProcessed > 0) {
            return redirect()->route('mobile.history')->with('error', 'Update gagal! Kunjungan sudah diverifikasi oleh admin.');
        }

        $hasReceipt = MobilePenjualan::where('idsf', $idsf)
            ->where('id_outlet', $id_outlet)
            ->where('tgl', $tgl)
            ->whereNotNull('receipt_id')
            ->exists();
        if ($hasReceipt) {
            return redirect()->route('mobile.history')->with('error', 'Transaksi dengan e-Bon tidak dapat diubah setelah disimpan.');
        }

        DB::transaction(function () use ($request, $idsf, $idtap, $id_outlet, $tgl) {
            $keptIds = [];

            foreach ($request->products as $productData) {
                if (isset($productData['id'])) {
                    $keptIds[] = $productData['id'];

                    MobilePenjualan::where('id', $productData['id'])
                        ->where('idsf', $idsf)
                        ->where('id_outlet', $id_outlet)
                        ->where('tgl', $tgl)
                        ->where('status', 'pending')
                        ->update([
                            'iddenom' => $productData['iddenom'],
                            'qty' => $productData['qty'],
                            'keterangan' => $request->input('keterangan'),
                        ]);
                } else {
                    $newSale = MobilePenjualan::create([
                        'tgl' => $tgl,
                        'id_outlet' => $id_outlet,
                        'idtap' => $idtap,
                        'idsf' => $idsf,
                        'iddenom' => $productData['iddenom'],
                        'qty' => $productData['qty'],
                        'status' => 'pending',
                        'keterangan' => $request->input('keterangan') ?: 'Tambahan via edit'
                    ]);
                    $keptIds[] = $newSale->id;
                }
            }

            MobilePenjualan::where('idsf', $idsf)
                ->where('id_outlet', $id_outlet)
                ->where('tgl', $tgl)
                ->where('status', 'pending')
                ->when(!empty($keptIds), function ($query) use ($keptIds) {
                    $query->whereNotIn('id', $keptIds);
                })
                ->delete();
        });

        return redirect()->route('mobile.history')->with('success', 'Data kunjungan berhasil diperbarui!');
    }

    public function stock()
    {
        $idsf = session('mobile_sf_id');

        $reserved = DB::table('mobile_penjualan')
            ->select('idsf', 'iddenom', DB::raw('SUM(qty) as reserved_qty'))
            ->where('idsf', $idsf)
            // Only pending sales reserve stock. Existing draft_wa rows are
            // immediately available again in the mobile stock view.
            ->where('status', 'pending')
            ->groupBy('idsf', 'iddenom');

        $stocks = DB::table('stockawalsf')
            ->where('stockawalsf.idsf', $idsf)
            ->leftJoin('denom', 'stockawalsf.iddenom', '=', 'denom.iddenom')
            ->leftJoinSub($reserved, 'reserved_sales', function ($join) {
                $join->on('reserved_sales.idsf', '=', 'stockawalsf.idsf')
                    ->on('reserved_sales.iddenom', '=', 'stockawalsf.iddenom');
            })
            ->whereRaw('(stockawalsf.stock - COALESCE(reserved_sales.reserved_qty, 0)) > 0')
            ->select('denom.iddenom', 'denom.denom', 'denom.group_name', DB::raw('(stockawalsf.stock - COALESCE(reserved_sales.reserved_qty, 0)) as stock'))
            ->orderBy('denom.group_name')
            ->orderBy('denom.denom')
            ->get();

        return view('mobile.stock', compact('stocks'));
    }

    public function getVisitDetails(Request $request)
    {
        $idsf = session('mobile_sf_id');
        $validated = $request->validate([
            'tgl' => ['required', 'date_format:Y-m-d'],
        ]);
        $tgl = $validated['tgl'];

        $sales = MobilePenjualan::where('mobile_penjualan.idsf', $idsf)
            ->where('mobile_penjualan.tgl', $tgl)
            ->whereNotIn('mobile_penjualan.status', ['draft_wa', 'rejected'])
            ->leftJoin('denom', 'mobile_penjualan.iddenom', '=', 'denom.iddenom')
            ->leftJoin('appsdumais', 'mobile_penjualan.id_outlet', '=', 'appsdumais.id_outlet')
            ->select(
                'mobile_penjualan.*',
                'denom.denom',
                'appsdumais.nama_outlet'
            )
            ->orderBy('appsdumais.nama_outlet')
            ->orderBy('mobile_penjualan.id')
            ->get();

        $consignments = DB::table('surya_sa_consignment_numbers as c')
            ->leftJoin('appsdumais as o', 'o.id_outlet', '=', 'c.id_outlet')
            ->where('c.idsf', $idsf)
            ->whereDate('c.reported_at', $tgl)
            ->where('c.status', 'consigned')
            ->orderBy('c.id')
            ->get(['c.id_outlet', 'c.serial_number', 'o.nama_outlet']);

        if ($sales->isEmpty() && $consignments->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $salesByOutlet = $sales->groupBy('id_outlet');
        $consignmentsByOutlet = $consignments->groupBy('id_outlet');
        $outletIds = $salesByOutlet->keys()->merge($consignmentsByOutlet->keys())->unique();

        return response()->json([
            'success' => true,
            'date' => Carbon::parse($tgl)->locale('id')->translatedFormat('d M Y'),
            'outlets' => $outletIds
                ->map(function ($idOutlet) use ($salesByOutlet, $consignmentsByOutlet) {
                    $outletSales = $salesByOutlet->get($idOutlet, collect());
                    $outletConsignments = $consignmentsByOutlet->get($idOutlet, collect());
                    $draftReceipt = $outletSales
                        ->first(fn ($sale) => $sale->status === 'draft_wa' && $sale->receipt_id);
                    $firstSale = $outletSales->first();
                    $firstConsignment = $outletConsignments->first();

                    return [
                        'id_outlet' => $idOutlet,
                        'nama_outlet' => ($firstSale->nama_outlet ?? $firstConsignment->nama_outlet ?? null) ?: $idOutlet,
                        'total_qty' => (int) $outletSales
                            ->whereNotIn('status', ['draft_wa', 'rejected'])
                            ->sum('qty'),
                        'receipt_url' => $draftReceipt
                            ? route('mobile.receipt.show', $draftReceipt->receipt_id)
                            : null,
                        'items' => $outletSales->map(fn ($sale) => [
                            'produk' => $sale->denom ?: $sale->iddenom,
                            'qty' => (int) $sale->qty,
                            'status' => $sale->status,
                        ])->values(),
                        'consignment_qty' => $outletConsignments->count(),
                        'consignment_numbers' => $outletConsignments->pluck('serial_number')->filter()->values(),
                    ];
                })
                ->values(),
        ]);
    }

    public function showReceipt(MobileSalesReceipt $receipt)
    {
        abort_unless($receipt->idsf === session('mobile_sf_id'), 403);

        $outlet = DB::table('appsdumais')
            ->where('id_outlet', $receipt->id_outlet)
            ->first(['id_outlet', 'nama_outlet']);

        $items = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->where('m.receipt_id', $receipt->id)
            ->orderBy('m.id')
            ->get([
                'm.id',
                'm.iddenom',
                'm.qty',
                'm.status',
                'd.denom',
            ]);

        $consignments = DB::table('surya_sa_consignment_numbers')
            ->where('receipt_id', $receipt->id)
            ->orderBy('id')
            ->pluck('serial_number');

        return view('mobile.receipt', compact('receipt', 'outlet', 'items', 'consignments'));
    }

    public function receiptImage(MobileSalesReceipt $receipt)
    {
        abort_unless($receipt->idsf === session('mobile_sf_id'), 403);
        abort_unless(extension_loaded('gd'), 503, 'Generator nota JPEG belum tersedia.');

        $outletName = DB::table('appsdumais')
            ->where('id_outlet', $receipt->id_outlet)
            ->value('nama_outlet') ?? $receipt->id_outlet;
        $items = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->where('m.receipt_id', $receipt->id)
            ->orderBy('m.id')
            ->get(['d.denom', 'm.qty']);

        $jpeg = $this->renderThermalReceiptJpeg($receipt, $outletName, $items);
        $filename = preg_replace('/[^A-Za-z0-9_-]/', '-', $receipt->receipt_number).'.jpg';

        return response($jpeg, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function prepareReceiptWhatsapp(Request $request, MobileSalesReceipt $receipt)
    {
        abort_unless($receipt->idsf === session('mobile_sf_id'), 403);

        if ($receipt->whatsapp_shared_at) {
            return response()->json([
                'success' => false,
                'message' => 'e-Bon transaksi ini sudah dikonfirmasi terkirim.',
            ], 422);
        }

        $validated = $request->validate([
            'owner_whatsapp' => ['required', 'string', 'max:25'],
        ]);

        $whatsapp = $this->normalizeWhatsappNumber($validated['owner_whatsapp']);
        if (! $whatsapp) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp tidak valid. Gunakan format 08xx atau 628xx.',
            ], 422);
        }

        $items = DB::table('mobile_penjualan as m')
            ->join('denom as d', 'm.iddenom', '=', 'd.iddenom')
            ->where('m.receipt_id', $receipt->id)
            ->get([
                'm.id',
                'm.qty',
                DB::raw('COALESCE(m.unit_price, d.harga_jual, 0) as unit_price'),
                'd.denom',
            ]);
        DB::transaction(function () use ($receipt, $items, $whatsapp) {
            foreach ($items as $item) {
                if ((int) $item->unit_price <= 0) {
                    continue;
                }

                MobilePenjualan::query()
                    ->whereKey($item->id)
                    ->whereNull('unit_price')
                    ->update(['unit_price' => (int) $item->unit_price]);
            }

            $receipt->update([
                'owner_whatsapp' => $whatsapp,
                'total_qty' => $items->sum(fn ($item) => (int) $item->qty),
                'total_amount' => $items->sum(fn ($item) => (int) $item->qty * max(0, (int) $item->unit_price)),
                'whatsapp_opened_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'whatsapp_url' => 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode($this->buildReceiptMessage($receipt->fresh())),
            'image_url' => route('mobile.receipt.image', $receipt),
            'image_filename' => preg_replace('/[^A-Za-z0-9_-]/', '-', $receipt->receipt_number).'.jpg',
            'share_text' => $this->buildReceiptMessage($receipt->fresh()),
            'owner_whatsapp' => $whatsapp,
        ]);
    }

    public function confirmReceiptWhatsapp(Request $request, MobileSalesReceipt $receipt)
    {
        abort_unless($receipt->idsf === session('mobile_sf_id'), 403);

        $request->validate([
            'receipt_photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ]);

        $receiptPhoto = $this->imageStorage->store($request->file('receipt_photo'), 'surya-sales/receipts')['path'];

        try {
            DB::transaction(function () use ($receipt, $receiptPhoto) {
                $lockedReceipt = MobileSalesReceipt::query()->lockForUpdate()->findOrFail($receipt->id);

                if ($lockedReceipt->whatsapp_shared_at) {
                    return;
                }

                $sales = MobilePenjualan::query()
                    ->where('receipt_id', $lockedReceipt->id)
                    ->where('status', 'draft_wa')
                    ->lockForUpdate()
                    ->get();

                $consignments = DB::table('surya_sa_consignment_numbers')
                    ->where('receipt_id', $lockedReceipt->id)
                    ->where('status', 'draft')
                    ->lockForUpdate()
                    ->get();

                if ($sales->isEmpty() && $consignments->isEmpty()) {
                    throw new \RuntimeException('Item penjualan tidak ditemukan atau sudah diproses.');
                }

                foreach ($sales as $sale) {
                    $stockOwnerIdsf = $sale->owner_idsf ?: $sale->idsf;
                    $stock = DB::table('stockawalsf')
                        ->where('idsf', $stockOwnerIdsf)
                        ->where('iddenom', $sale->iddenom)
                        ->lockForUpdate()
                        ->first();

                    $reservedQty = (int) DB::table('mobile_penjualan')
                        ->where(function ($query) use ($stockOwnerIdsf) {
                            $query->where('owner_idsf', $stockOwnerIdsf)
                                ->orWhere(function ($legacy) use ($stockOwnerIdsf) {
                                    $legacy->whereNull('owner_idsf')->where('idsf', $stockOwnerIdsf);
                                });
                        })
                        ->where('iddenom', $sale->iddenom)
                        ->where('status', 'pending')
                        ->sum('qty');
                    $availableStock = max(0, (int) ($stock->stock ?? 0) - $reservedQty);
                    if (! $stock || $availableStock < (int) $sale->qty) {
                        $denomName = DB::table('denom')->where('iddenom', $sale->iddenom)->value('denom') ?: $sale->iddenom;
                        throw new \RuntimeException(
                            "Stok {$denomName} tidak cukup. Stok tersedia di mobile " . number_format($availableStock) .
                            ', dibutuhkan ' . number_format((int) $sale->qty) . '.'
                        );
                    }

                    $sale->update([
                        'status' => 'pending',
                        'stock_reserved_at' => null,
                        'updated_at' => now(),
                    ]);
                }

                DB::table('surya_sa_consignment_numbers')
                    ->where('receipt_id', $lockedReceipt->id)
                    ->where('status', 'draft')
                    ->update([
                        'status' => 'consigned',
                        'updated_at' => now(),
                    ]);

                $lockedReceipt->update(['receipt_photo_path' => $receiptPhoto, 'whatsapp_shared_at' => now()]);

                // Submit penjualan adalah checkout resmi outlet. Menutup visit
                // di transaksi yang sama membuat outlet berikutnya langsung
                // terbuka dan tetap menyimpan jejak waktu checkout.
                SuryaVisit::query()
                    ->where('idsf', $lockedReceipt->idsf)
                    ->where('id_outlet', $lockedReceipt->id_outlet)
                    ->whereDate('visited_at', $lockedReceipt->tgl)
                    ->whereNull('checkout_at')
                    ->update([
                        'checkout_at' => now(),
                        'checkout_reason' => 'penjualan_selesai',
                        'checkout_notes' => 'Checkout otomatis setelah submit penjualan.',
                    ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete([$receiptPhoto, OptimizedImageStorage::thumbnailPathFor($receiptPhoto)]);
            return back()->with('error', $e->getMessage());
        }

        $hasSales = MobilePenjualan::query()->where('receipt_id', $receipt->id)->exists();

        return redirect()
            ->route('mobile.history')
            ->with('success', $hasSales
                ? 'Penjualan berhasil disubmit. Stok sudah diamankan dan transaksi masuk ke admin ' . $receipt->idtap . ' untuk validasi.'
                : 'Konsinyasi berhasil dicatat dan nomor perdana sudah terhubung ke outlet.');
    }

    private function generateReceiptNumber(): string
    {
        do {
            $number = 'SUR-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (MobileSalesReceipt::query()->where('receipt_number', $number)->exists());

        return $number;
    }

    private function grantSalesFormAccess(string $idsf, string $idOutlet): void
    {
        $visit = DB::table('surya_visits')
            ->where('idsf', $idsf)
            ->where('id_outlet', $idOutlet)
            ->whereDate('visited_at', now()->toDateString())
            ->whereIn('gps_status', ['valid', 'approved'])
            ->whereNotNull('photo_front_path')
            ->whereNotNull('photo_inside_path')
            ->latest('visited_at')
            ->first(['id']);

        if (! $visit) {
            return;
        }

        session()->put('mobile_sales_visit_access', [
            'visit_id' => (int) $visit->id,
            'idsf' => $idsf,
            'id_outlet' => $idOutlet,
            'sales_date' => now()->toDateString(),
        ]);
    }

    private function hasSalesFormAccess(string $idsf, string $idOutlet, string $salesDate): bool
    {
        $access = session('mobile_sales_visit_access');
        if (! is_array($access)
            || (string) ($access['idsf'] ?? '') !== $idsf
            || (string) ($access['id_outlet'] ?? '') !== $idOutlet
            || (string) ($access['sales_date'] ?? '') !== $salesDate
            || empty($access['visit_id'])) {
            return false;
        }

        return DB::table('surya_visits')
            ->where('id', $access['visit_id'])
            ->where('idsf', $idsf)
            ->where('id_outlet', $idOutlet)
            ->whereDate('visited_at', $salesDate)
            ->exists();
    }

    private function normalizeWhatsappNumber(string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', $number);

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return preg_match('/^62[0-9]{8,13}$/', $digits) ? $digits : null;
    }

    private function parseConsignmentNumbers(?string $rawNumbers): array
    {
        $numbers = [];
        $invalid = [];
        $duplicates = [];

        foreach (preg_split('/[\r\n,;]+/', (string) $rawNumbers) ?: [] as $rawNumber) {
            $rawNumber = trim($rawNumber);
            if ($rawNumber === '') {
                continue;
            }

            $candidate = preg_replace('/[\s().-]+/', '', $rawNumber);
            if (! preg_match('/^\+?\d+$/', (string) $candidate)) {
                $invalid[] = $rawNumber;
                continue;
            }

            $number = ltrim((string) $candidate, '+');
            if (str_starts_with($number, '0062')) {
                $number = substr($number, 2);
            } elseif (str_starts_with($number, '0')) {
                $number = '62'.substr($number, 1);
            }

            if (! preg_match('/^\d{8,20}$/', $number)) {
                $invalid[] = $rawNumber;
                continue;
            }
            if (in_array($number, $numbers, true)) {
                $duplicates[] = $number;
                continue;
            }

            $numbers[] = $number;
        }

        return [$numbers, array_values(array_unique($invalid)), array_values(array_unique($duplicates))];
    }

    private function buildReceiptMessage(MobileSalesReceipt $receipt): string
    {
        $outletName = DB::table('appsdumais')
            ->where('id_outlet', $receipt->id_outlet)
            ->value('nama_outlet') ?? $receipt->id_outlet;
        $lines = [
            '*NOTA PENJUALAN SURYA*',
            'Outlet: '.$outletName.' ('.$receipt->id_outlet.')',
            'No: '.$receipt->receipt_number,
            'Total: '.number_format($receipt->total_qty, 0, ',', '.').' pcs',
            'Nota JPEG terlampir.',
        ];

        return implode("\n", $lines);
    }

    private function renderThermalReceiptJpeg(MobileSalesReceipt $receipt, string $outletName, $items): string
    {
        $width = 360;
        $font = 5;
        $lineHeight = 22;
        $maxChars = 34;
        $separator = str_repeat('-', $maxChars);
        $doubleSeparator = str_repeat('=', $maxChars);
        $salesName = session('mobile_sf_name') ?: $receipt->idsf;
        $ascii = fn ($value) => Str::ascii(trim((string) $value));
        $wrap = fn ($value, $length = null) => explode("\n", wordwrap($ascii($value), $length ?? $maxChars, "\n", true));

        $lines = [
            ['text' => 'SURYA', 'align' => 'center'],
            ['text' => 'NOTA PENJUALAN', 'align' => 'center'],
            ['text' => $doubleSeparator],
        ];
        foreach ($wrap($outletName) as $nameLine) {
            $lines[] = ['text' => strtoupper($nameLine), 'align' => 'center'];
        }
        $lines[] = ['text' => 'ID     : '.$ascii($receipt->id_outlet)];
        $lines[] = ['text' => 'NO BON : '.$ascii($receipt->receipt_number)];
        $lines[] = ['text' => 'TANGGAL: '.$receipt->tgl->format('d/m/Y')];
        foreach ($wrap('SALES  : '.$salesName) as $salesLine) {
            $lines[] = ['text' => $salesLine];
        }
        $lines[] = ['text' => $separator];
        $lines[] = ['text' => 'PRODUK                         QTY'];
        $lines[] = ['text' => $separator];

        foreach ($items as $index => $item) {
            foreach ($wrap(($index + 1).'. '.$item->denom, 27) as $productLine) {
                $lines[] = ['text' => $productLine];
            }
            $qtyText = number_format((int) $item->qty, 0, ',', '.').' PCS';
            $lines[] = ['text' => str_pad($qtyText, $maxChars, ' ', STR_PAD_LEFT)];
        }

        $lines[] = ['text' => $doubleSeparator];
        $totalText = 'TOTAL QTY: '.number_format((int) $receipt->total_qty, 0, ',', '.').' PCS';
        $lines[] = ['text' => str_pad($totalText, $maxChars, ' ', STR_PAD_LEFT)];
        $lines[] = ['text' => $doubleSeparator];
        $lines[] = ['text' => 'TERIMA KASIH', 'align' => 'center'];
        $lines[] = ['text' => 'Simpan nota ini sebagai bukti', 'align' => 'center'];

        $height = 36 + count($lines) * $lineHeight + 30;
        $canvas = imagecreatetruecolor($width, $height);
        $paper = imagecolorallocate($canvas, 255, 255, 252);
        $ink = imagecolorallocate($canvas, 20, 20, 20);
        $muted = imagecolorallocate($canvas, 85, 85, 85);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $paper);

        $y = 22;
        foreach ($lines as $index => $line) {
            $text = $line['text'];
            $textWidth = imagefontwidth($font) * strlen($text);
            $x = ($line['align'] ?? null) === 'center'
                ? max(12, (int) (($width - $textWidth) / 2))
                : 18;
            imagestring($canvas, $font, $x, $y, $text, $index >= count($lines) - 2 ? $muted : $ink);
            $y += $lineHeight;
        }

        $scaled = imagescale($canvas, $width * 2, $height * 2, IMG_NEAREST_NEIGHBOUR);
        imagedestroy($canvas);
        ob_start();
        imagejpeg($scaled, null, 82);
        $jpeg = (string) ob_get_clean();
        imagedestroy($scaled);

        return $jpeg;
    }
}
