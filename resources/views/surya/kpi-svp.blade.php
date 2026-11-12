{{--
    Tabel, editor, dan matriks KPI SVP. Kalkulasi tampilan ada di surya-kpi-svp.js, penyimpanan di
    KpiSvpController.
--}}@extends('surya.layouts.workspace')

@section('title', 'KPI SVP')
@section('active-page', 'kpi-svp')
@section('description', 'Kinerja Mobile dan Household per wilayah.')
@section('content-class', 'kpi-svp')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/surya-kpi-svp.css') }}?v={{ filemtime(public_path('css/surya-kpi-svp.css')) }}">
@endpush

@section('content')
    @php
        $canEdit = auth()->user()?->isSuperAdmin();
        $regions = [
            'dumai' => [
                'name' => 'Dumai Bengkalis',
                'revenueWeight' => 55,
                'mobile' => [[45323067411, 43722137149], [39543468069, 40403719256], [70.7, 65.5]],
                'household' => [[123978840, 70216252], [559, 379], [97.6, 96.2]],
            ],
            'rokan' => [
                'name' => 'Rokan Hilir',
                'revenueWeight' => 70,
                'mobile' => [[30875691884, 31141644474], [27029674000, 28947140183], [72.9, 71.7]],
                'household' => [[35169339, 17672365], [161, 108], [97.4, 97.1]],
            ],
        ];
        $segments = [
            'mobile' => ['label' => 'Mobile', 'icon' => 'fa-mobile-screen-button', 'metrics' => [
                ['Revenue All', 'Rp'], ['Revenue Broadband', 'Rp'], ['Market Share', '%'],
            ]],
            'household' => ['label' => 'Household', 'icon' => 'fa-house-signal', 'metrics' => [
                ['Revenue New Sales', 'Rp'], ['PS', ''], ['C3MR', '%'],
            ]],
        ];
    @endphp

    <div class="svp-toolbar">
        <label class="svp-period"><i class="fa-regular fa-calendar" aria-hidden="true"></i> Periode
            <input type="month" id="svp-period" value="{{ now('Asia/Jakarta')->format('Y-m') }}" min="0002-01" max="9999-12" data-entries-url="{{ route('surya.kpi-svp.entries') }}" required>
        </label>
        <span id="svp-status" role="status"></span>
    </div>

    <div class="svp-mode" role="group" aria-label="Tampilan KPI">
        <button type="button" data-svp-mode="mtd" aria-pressed="true">MTD</button>
        <button type="button" data-svp-mode="projection" aria-pressed="false">Proyeksi</button>
        <button type="button" data-svp-mode="gold" aria-pressed="false">Simulasi Gold</button>
    </div>

    <section class="svp-overview" aria-label="Ringkasan wilayah">
        @foreach($regions as $regionId => $region)
            <button type="button" class="svp-region" data-svp-region="{{ $regionId }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-controls="svp-detail-{{ $regionId }}">
                <span class="svp-region-top"><strong>{{ $region['name'] }}</strong><span data-region-class="{{ $regionId }}" class="svp-class">—</span></span>
                <span class="svp-region-scores">
                    @foreach($segments as $segmentId => $segment)
                        <span><small><i class="fas {{ $segment['icon'] }}" aria-hidden="true"></i> {{ $segment['label'] }}</small><b data-summary-score="{{ $regionId }}-{{ $segmentId }}">—</b><span data-summary-class="{{ $regionId }}-{{ $segmentId }}" class="svp-class">—</span></span>
                    @endforeach
                </span>
            </button>
        @endforeach
    </section>

    @foreach($regions as $regionId => $region)
        <section id="svp-detail-{{ $regionId }}" data-svp-panel="{{ $regionId }}" @if(!$loop->first) hidden @endif aria-labelledby="svp-title-{{ $regionId }}">
            <div class="svp-detail-heading">
                <h2 id="svp-title-{{ $regionId }}">Detail KPI · {{ $region['name'] }}</h2>
            </div>
            <p class="svp-cutoff" data-svp-cutoff aria-live="polite"></p>
            <button type="button" class="svp-edit-button" data-svp-reset hidden>Reset simulasi</button>
            <article class="svp-matrix-card" data-svp-matrix="{{ $regionId }}">
                <div class="svp-matrix-result">
                    <h3>Matriks kelas gabungan</h3>
                    <div class="svp-matrix-scores"><span>Mobile <strong data-matrix-mobile></strong></span><span>Household <strong data-matrix-household></strong></span></div>
                    <div class="svp-matrix-final">Kelas akhir cluster <strong class="svp-class" data-matrix-class aria-live="polite"></strong></div>
                    <p>Kelas akhir mengikuti skor yang lebih rendah.</p>
                </div>
                <figure class="svp-matrix-figure">
                    <div class="svp-matrix-plot">
                        <span class="svp-axis-title-y">Skor Mobile</span>
                        <div class="svp-axis-y" aria-hidden="true">@foreach(['3.0', '2.6', '2.2', '1.8', '1.4', '1.0'] as $tick)<span>{{ $tick }}</span>@endforeach</div>
                        <div class="svp-matrix-grid" aria-hidden="true">
                            @php($classes = ['Black', 'Bronze', 'Silver', 'Gold', 'Platinum'])
                            @foreach(range(4, 0) as $mobileBand)
                                @foreach(range(0, 4) as $householdBand)
                                    @php($class = $classes[min($mobileBand, $householdBand)])
                                    <span class="svp-matrix-cell" data-class="{{ strtolower($class) }}">{{ $mobileBand === $householdBand ? $class : '' }}</span>
                                @endforeach
                            @endforeach
                            <span class="svp-matrix-point" data-matrix-point></span>
                        </div>
                        <div class="svp-axis-x" aria-hidden="true">@foreach(['1.0', '1.4', '1.8', '2.2', '2.6', '3.0'] as $tick)<span>{{ $tick }}</span>@endforeach</div>
                        <figcaption>Skor Household</figcaption>
                    </div>
                </figure>
            </article>
            <div class="svp-segments">
                @foreach($segments as $segmentId => $segment)
                    <article class="svp-segment" data-svp-segment="{{ $regionId }}-{{ $segmentId }}">
                        <header>
                            <div class="svp-segment-title"><span class="svp-icon"><i class="fas {{ $segment['icon'] }}" aria-hidden="true"></i></span><div><h3>{{ $segment['label'] }}</h3></div></div>
                            <div class="svp-result"><small>Skor akhir</small><strong data-segment-score></strong></div>
                        </header>
                        <div class="svp-table-wrap" tabindex="0" aria-label="Tabel KPI {{ $segment['label'] }}">
                            <table>
                                <thead><tr><th scope="col">Parameter</th><th scope="col">Target</th><th scope="col" data-svp-actual-heading>Aktual MTD</th><th scope="col">Tanggal Update</th><th scope="col">Capaian</th><th scope="col">Gap</th><th scope="col">Skor</th><th scope="col">Kelas hasil</th>@if($canEdit)<th scope="col" data-svp-action>Aksi</th>@endif</tr></thead>
                                <tbody>
                                    @foreach($segment['metrics'] as [$label, $unit])
                                        <tr data-svp-metric data-unit="{{ $unit }}">
                                            @php($formId = "svp-{$regionId}-{$segmentId}-{$loop->index}")
                                            <th scope="row">{{ $label }}</th>
                                            @foreach(['target' => 'Target', 'actual' => 'Aktual', 'actual-date' => 'Tanggal Update'] as $field => $fieldLabel)
                                                <td>
                                                    <span data-{{ $field }}></span>
                                                    @if($field === 'actual')
                                                        <input class="svp-inline-input" data-svp-simulation hidden type="number" min="0" step="any"
                                                            aria-label="Simulasi {{ $label }} ({{ $unit === '%' ? 'persen' : ($unit ?: 'unit') }})">
                                                    @endif
                                                    @if($canEdit)
                                                        <input class="svp-inline-input" hidden form="{{ $formId }}" name="{{ $field }}"
                                                            aria-label="{{ $fieldLabel }} {{ $label }}"
                                                            @if($field === 'actual-date') type="date" @else type="number" min="0" step="0.0001" @endif>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td><div class="svp-ach"><span data-ach></span><progress max="100" aria-label="Capaian {{ $label }}"></progress></div></td>
                                            <td data-gap></td><td data-score></td>
                                            @if($loop->first)
                                                <td class="svp-result-cell" rowspan="{{ count($segment['metrics']) }}" data-segment-class></td>
                                            @endif
                                            @if($canEdit)
                                                <td data-svp-action>
                                                    <button type="button" class="svp-edit-button" data-svp-edit hidden aria-label="Edit {{ $label }}"><i class="fas fa-pen" aria-hidden="true"></i> Edit</button>
                                                    <form id="{{ $formId }}" class="svp-inline-form" hidden data-region="{{ $regionId }}" data-segment="{{ $segmentId }}" data-metric="{{ $loop->index }}">
                                                        <button type="submit" class="svp-primary">Simpan</button>
                                                        <button type="button" class="svp-edit-button" data-svp-cancel>Batal</button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <footer>
                            @if($segmentId === 'mobile')
                                <span>Skor revenue <b data-revenue-score></b> <small>(Revenue All × 40% + Broadband × 60%)</small></span>
                                <span>Skor revenue × <b>{{ $region['revenueWeight'] }}%</b></span>
                                <span>Skor Market Share × <b>{{ 100 - $region['revenueWeight'] }}%</b></span>
                            @else
                                <span>Skor Revenue New Sales &amp; PS <b data-new-sales-ps-score></b> <small>(Revenue New Sales × 60% + PS × 40%)</small></span>
                                <span>Skor grup Revenue New Sales &amp; PS × <b>55%</b></span>
                                <span>Skor C3MR × <b>45%</b></span>
                            @endif
                        </footer>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach

    <script type="application/json" id="svp-demo-data">@json($regions)</script>
@endsection

@push('scripts')
    <script src="{{ asset('js/surya-kpi-svp.js') }}?v={{ filemtime(public_path('js/surya-kpi-svp.js')) }}" defer></script>
@endpush
