<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>Approval Visit — SURYA</title>
    <link rel="icon" href="/assets/img/surya-logo-mobile.webp">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="{{ asset('css/surya-visits.css') }}?v={{ filemtime(public_path('css/surya-visits.css')) }}">
    @include('surya.partials.assets')
<link rel="stylesheet" href="{{ asset('css/surya-visit-photo-preview.css') }}?v={{ filemtime(public_path('css/surya-visit-photo-preview.css')) }}">
</head>

<body>
    <div class="shell" id="surya-shell" data-surya-workspace>
        @include('surya.partials.sidebar', ['activePage' => 'visit-report'])
        <div class="main surya-workspace-main">
            @include('surya.partials.header')
            <main class="content surya-content">
                @include('surya.partials.page-heading', ['title' => 'Approval Visit', 'description' => 'Periksa foto outlet dan setujui pembaruan koordinat.'])
                <section class="approval-filter-bar"><form class="filters" method="GET"><input type="date" name="date_from"
                            value="{{ $dateFrom }}" aria-label="Tanggal awal"><input type="date" name="date_to"
                            value="{{ $dateTo }}" aria-label="Tanggal akhir"><button
                            type="submit">Filter</button></form>
                </section>
                @if (session('success'))
                    <div class="notice"><i class="fas fa-circle-check"></i>&nbsp; {{ session('success') }}</div>
                @endif
                <section class="card">
                    <div class="table-wrap visits-approval-scroll">
                        <table class="visit-table">
                            <thead>
                                <tr>
                                    <th>Outlet</th>
                                    <th>TAP</th>
                                    <th>Sales Force</th>
                                    <th>Visit Terakhir</th>
                                    <th>Status GPS</th>
                                    <th>Jarak</th>
                                    <th>Dominasi Branding</th>
                                    <th>Kelayakan Branding</th>
                                    <th>Tampak Depan</th>
                                    <th>Tampak Dalam</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($visits as $visit)
                                    <tr>
                                        <td class="outlet">
                                            <strong>{{ $visit->nama_outlet ?? $visit->id_outlet }}</strong><small>{{ $visit->id_outlet }}</small>
                                        </td>
                                        <td>{{ $visit->idtap }}</td>
                                        <td>{{ $visit->namasf ?? $visit->idsf }}</td>
                                        <td>{{ \Carbon\Carbon::parse($visit->visited_at)->format('d M Y H:i') }}</td>
                                        <td><span
                                                class="badge {{ in_array($visit->gps_status, ['valid', 'approved']) ? 'ok' : 'wait' }}">{{ strtoupper(str_replace('_', ' ', $visit->gps_status)) }}</span>
                                        </td>
                                        <td>{{ $visit->distance_meters !== null ? number_format($visit->distance_meters, 0) . ' m' : '—' }}
                                        </td>
                                        <td>{{ $visit->branding_dominance ?: '—' }}</td>
                                        <td>{{ $visit->branding_quality ?: '—' }}</td>
                                        <td>
                                            @if ($visit->front_photo_url)
                                                <button type="button" class="photo-open"
                                                    data-photo-src="{{ $visit->front_photo_url }}"
                                                    data-photo-title="{{ $visit->nama_outlet ?? $visit->id_outlet }} · Tampak Depan"><img
                                                        src="{{ $visit->front_thumbnail_url }}" loading="lazy"
                                                        decoding="async" alt="Tampak depan"
                                                        class="photo-thumb"></button>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if ($visit->inside_photo_url)
                                                <button type="button" class="photo-open"
                                                    data-photo-src="{{ $visit->inside_photo_url }}"
                                                    data-photo-title="{{ $visit->nama_outlet ?? $visit->id_outlet }} · Tampak Dalam"><img
                                                        src="{{ $visit->inside_thumbnail_url }}" loading="lazy"
                                                        decoding="async" alt="Tampak dalam"
                                                        class="photo-thumb"></button>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if ($visit->gps_status === 'pending_admin')
                                                <form method="POST"
                                                    action="{{ route('surya.visits.approve', $visit->id) }}">
                                                    @csrf<button class="approve-btn">Setujui & Perbarui
                                                        Koordinat</button></form>
                                                <form method="POST" action="{{ route('surya.visits.reject', $visit->id) }}"
                                                    onsubmit="return confirm('Tolak foto visit ini? Sales Force harus mengambil ulang foto depan dan dalam.')">
                                                    @csrf<button class="approve-btn" style="margin-top:6px;background:#b91c1c">Tolak Foto</button></form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty<tr>
                                        <td colspan="11" class="empty">Belum ada data visit.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('surya.partials.pagination', ['paginator' => $visits])
                </section>
            </main>
        </div>
    </div>
    @include('surya.partials.photo-preview')
</body>

</html>
