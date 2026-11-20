@extends('surya.layouts.workspace')

@section('title', 'Validasi ST')
@section('active-page', 'product-tracking-sold')
@section('description', 'Validasi kecocokan SN dan qty antara nota Mobile SURYA dan ST Digipos.')
@section('content-class', 'pt-page')

@push('styles')<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}"><link rel="stylesheet" href="{{ asset('css/surya-outlet-columns.css') }}?v={{ filemtime(public_path('css/surya-outlet-columns.css')) }}"><link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}"><link rel="stylesheet" href="{{ asset('css/surya-outlet-date.css') }}?v={{ filemtime(public_path('css/surya-outlet-date.css')) }}">@endpush

@section('content')
    <section class="pt-card pt-sold-intro">
        <div class="pt-sold-intro-copy">
            <span class="pt-sold-icon"><i class="fas fa-code-compare"></i></span>
            <div><h2>Ringkasan Validasi ST · {{ $activeTab === 'progress' ? 'SN Progress' : 'SN Approve' }}</h2><p class="pt-subtitle mb-0">{{ $activeTab === 'progress' ? 'Memantau SN dari transaksi Mobile SURYA yang menunggu approval.' : 'Mencocokkan SN transaksi Mobile SURYA yang disetujui dengan hasil upload ST Digipos berdasarkan outlet dan tanggal yang dipilih.' }}</p></div>
        </div>
        <div class="pt-sold-stats" aria-label="Ringkasan validasi">
            <div><strong>{{ number_format($summary->matched_outlets) }}</strong><span>outlet sesuai</span></div>
            <div><strong>{{ number_format($summary->mismatch_outlets) }}</strong><span>outlet perlu dicek</span></div>
            <div><strong>{{ number_format($summary->matched_qty) }}</strong><span>qty sesuai</span></div>
            <div><strong>{{ number_format($summary->mismatch_qty) }}</strong><span>qty belum cocok</span></div>
        </div>
    </section>

    <nav class="pt-sold-tabs" aria-label="Kategori SN">
        <a class="{{ $activeTab === 'progress' ? 'active' : '' }}" href="{{ route('surya.product-tracking.sold', array_merge(request()->query(), ['tab' => 'progress'])) }}"><i class="fas fa-hourglass-half"></i> SN Progress</a>
        <a class="{{ $activeTab === 'approve' ? 'active' : '' }}" href="{{ route('surya.product-tracking.sold', array_merge(request()->query(), ['tab' => 'approve'])) }}"><i class="fas fa-circle-check"></i> SN Approve</a>
    </nav>

    <form method="get" action="{{ route('surya.product-tracking.sold') }}" class="pt-card pt-grid pt-sold-filter">
        <div class="pt-field pt-date-field"><label for="sold-date">Tanggal ST</label><div class="pt-date-control"><input id="sold-date" class="pt-date-input" type="date" name="date" value="{{ $filters['date'] }}" max="{{ now('Asia/Jakarta')->toDateString() }}"><span class="pt-date-icon" aria-hidden="true"><i class="far fa-calendar-alt"></i></span></div></div>
        <div class="pt-field"><label for="sold-tap">TAP</label><select id="sold-tap" name="tap"><option value="">Semua TAP</option>@foreach($taps as $tap)<option value="{{ $tap }}" @selected($filters['tap']===$tap)>{{ $tap }}</option>@endforeach</select></div>
        <div class="pt-field"><label for="sold-sf">Sales Force</label><select id="sold-sf" name="sf"><option value="">Semua Sales Force</option>@foreach($sfs as $sf)<option value="{{ $sf->idsf }}" @selected($filters['sf']===$sf->idsf)>{{ $sf->namasf ?: $sf->idsf }}</option>@endforeach</select></div>
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <div class="pt-sold-actions"><button class="pt-button pt-apply-filter" type="submit"><i class="fas fa-filter"></i><span>Terapkan filter</span></button><button class="pt-button primary" type="submit" formaction="{{ route('surya.product-tracking.sold.download') }}" @disabled($summary->serial_count === 0)><i class="fas fa-download"></i> Download detail CSV</button><span class="pt-loading-status" role="status" aria-live="polite" hidden></span></div>
    </form>
    @error('download')<div class="pt-alert error">{{ $message }}</div>@enderror
    @if(!$hasScanUploads)<div class="pt-alert error">Belum ada upload ST Digipos pada tanggal ini. Qty ditampilkan sebagai belum cocok sampai file scan diunggah.</div>@endif

    @if($summary->serial_count > 0)
        @if(empty($summaryTarget['summary_status']))
        @foreach([['Ringkasan per TAP', $tapSummaries], ['Ringkasan per Sales Force', $sfSummaries]] as [$heading, $groups])
            <section class="pt-card pt-summary-section">
                <h2>{{ $heading }}</h2>
                @php($summaryLevel = str_contains($heading, 'TAP') ? 'tap' : 'sf')
                @php($summaryColumns = [$summaryLevel => $summaryLevel === 'tap' ? 'TAP' : 'Sales Force'])
                @if($summaryLevel === 'sf') @php($summaryColumns['tap'] = 'TAP') @endif
                @php($summaryColumns += ['outlet_count' => 'Total outlet', 'matched_outlets' => 'Outlet sesuai', 'mismatch_outlets' => 'Outlet perlu dicek', 'qty' => 'Qty nota', 'matched_qty' => 'Qty sesuai', 'mismatch_qty' => 'Qty belum cocok'])
                <div class="pt-table-wrap"><table class="pt-table pt-summary-table">
                    <thead><tr>
                        @foreach($summaryColumns as $column => $label)
                            @php($isSummarySorted = ($summaryTableOptions['summary_table'] ?? '') === $summaryLevel && ($summaryTableOptions['summary_sort'] ?? '') === $column)
                            <th><div class="tf-column-heading"><span>{{ $label }}@if($isSummarySorted)<i class="fas fa-arrow-{{ ($summaryTableOptions['summary_direction'] ?? 'asc') === 'desc' ? 'down' : 'up' }} tf-sort-indicator"></i>@endif</span><button type="button" class="tf-column-filter {{ array_key_exists($column, $summaryTableOptions["summary_{$summaryLevel}_columns"] ?? []) || array_key_exists($column, $summaryTableOptions["summary_{$summaryLevel}_exclude"] ?? []) ? 'is-active' : '' }}" data-column="{{ $column }}" data-label="{{ $label }}" data-summary-table="{{ $summaryLevel }}" data-selection="{{ json_encode($summaryTableOptions["summary_{$summaryLevel}_columns"] ?? new \stdClass()) }}" data-excluded="{{ json_encode($summaryTableOptions["summary_{$summaryLevel}_exclude"] ?? new \stdClass()) }}" aria-label="Filter dan urutkan {{ $label }}" aria-expanded="false"><i class="fas fa-filter"></i></button></div></th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                    @foreach($groups as $group)
                        @php($groupKey = $group->key ?? '__NULL__')
                        @php($matchedUrl = route('surya.product-tracking.sold', array_merge(request()->query(), ['summary_level' => $summaryLevel, 'summary_key' => $groupKey, 'summary_status' => 'matched'])) )
                        @php($mismatchUrl = route('surya.product-tracking.sold', array_merge(request()->query(), ['summary_level' => $summaryLevel, 'summary_key' => $groupKey, 'summary_status' => 'mismatch'])) )
                        <tr><td>{{ $group->label }}</td>@if($summaryLevel === 'sf')<td>{{ $group->tap ?: 'Tanpa TAP' }}</td>@endif<td>{{ number_format($group->outlet_count) }}</td><td><a class="pt-chip good pt-summary-link" href="{{ $matchedUrl }}" aria-label="Lihat {{ $group->matched_outlets }} outlet sesuai untuk {{ $group->label }}">{{ number_format($group->matched_outlets) }}</a></td><td><a class="pt-chip {{ $group->mismatch_outlets ? 'bad' : 'good' }} pt-summary-link" href="{{ $mismatchUrl }}" aria-label="Lihat {{ $group->mismatch_outlets }} outlet perlu dicek untuk {{ $group->label }}">{{ number_format($group->mismatch_outlets) }}</a></td><td>{{ number_format($group->qty) }}</td><td>{{ number_format($group->matched_qty) }}</td><td>{{ number_format($group->mismatch_qty) }}</td></tr>
                    @endforeach
                    </tbody>
                </table></div>
                <div class="pt-summary-mobile-tools" aria-label="Filter dan urutkan ringkasan">
                    @foreach($summaryColumns as $column => $label)
                        <button type="button" class="tf-column-filter {{ array_key_exists($column, $summaryTableOptions["summary_{$summaryLevel}_columns"] ?? []) || array_key_exists($column, $summaryTableOptions["summary_{$summaryLevel}_exclude"] ?? []) ? 'is-active' : '' }}" data-column="{{ $column }}" data-label="{{ $label }}" data-summary-table="{{ $summaryLevel }}" data-selection="{{ json_encode($summaryTableOptions["summary_{$summaryLevel}_columns"] ?? new \stdClass()) }}" data-excluded="{{ json_encode($summaryTableOptions["summary_{$summaryLevel}_exclude"] ?? new \stdClass()) }}"><span>{{ $label }}</span><i class="fas fa-filter"></i></button>
                    @endforeach
                </div>
                <div class="pt-summary-mobile">
                    @forelse($groups as $group)
                        @php($groupKey = $group->key ?? '__NULL__')
                        @php($matchedUrl = route('surya.product-tracking.sold', array_merge(request()->query(), ['summary_level' => $summaryLevel, 'summary_key' => $groupKey, 'summary_status' => 'matched'])) )
                        @php($mismatchUrl = route('surya.product-tracking.sold', array_merge(request()->query(), ['summary_level' => $summaryLevel, 'summary_key' => $groupKey, 'summary_status' => 'mismatch'])) )
                        <article class="pt-summary-mobile-card">
                            <h3>{{ $group->label }}</h3>
                            @if($summaryLevel === 'sf')<div><span>TAP</span><strong>{{ $group->tap ?: 'Tanpa TAP' }}</strong></div>@endif
                            <div><span>Total outlet</span><strong>{{ number_format($group->outlet_count) }}</strong></div>
                            <div><span>Outlet sesuai</span><a class="pt-chip good pt-summary-link" href="{{ $matchedUrl }}">{{ number_format($group->matched_outlets) }}</a></div>
                            <div><span>Outlet perlu dicek</span><a class="pt-chip {{ $group->mismatch_outlets ? 'bad' : 'good' }} pt-summary-link" href="{{ $mismatchUrl }}">{{ number_format($group->mismatch_outlets) }}</a></div>
                            <div><span>Qty nota</span><strong>{{ number_format($group->qty) }}</strong></div>
                            <div><span>Qty sesuai</span><strong>{{ number_format($group->matched_qty) }}</strong></div>
                            <div><span>Qty belum cocok</span><strong>{{ number_format($group->mismatch_qty) }}</strong></div>
                        </article>
                    @empty
                        <p class="pt-empty mb-0">Tidak ada ringkasan yang cocok dengan filter kolom ini.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
        @endif

        <section id="sold-outlet-results" class="pt-card">
            <div class="pt-result-head"><div><h2>{{ ($summaryTarget['summary_status'] ?? '') === 'matched' ? 'Outlet sesuai' : (($summaryTarget['summary_status'] ?? '') === 'mismatch' ? 'Outlet perlu dicek' : 'Rincian per outlet') }}</h2><span class="pt-muted">{{ number_format($outlets->count()) }} outlet{{ !empty($summaryTarget['summary_status']) ? ($summaryTarget['summary_status'] === 'matched' ? ' sesuai' : ' perlu dicek') : '' }}{{ !empty($summaryTarget['summary_key']) && $summaryTarget['summary_key'] !== '__NULL__' ? ' · '.$summaryTarget['summary_key'] : '' }} · bandingkan qty nota dengan SN Digipos yang cocok di outlet yang sama.</span></div>@if(!empty($summaryTarget['summary_status']))<a class="pt-button" href="{{ route('surya.product-tracking.sold', request()->except(['summary_level','summary_key','summary_status'])) }}">Kembali</a>@endif</div>
            <div class="pt-table-wrap"><table class="pt-table detail-table">
                <thead><tr>
                    @foreach(['nama_outlet' => 'Outlet', 'id_outlet' => 'ID Outlet', 'kecamatan' => 'Kecamatan', 'tap' => 'TAP', 'namasf' => 'Sales Force', 'qty' => 'Qty nota', 'matched_qty' => 'Qty sesuai', 'mismatch_qty' => 'Qty belum cocok', 'status' => 'Status'] as $column => $label)
                        <th><div class="tf-column-heading"><span>{{ $label }}@if(($tableOptions['sort'] ?? '') === $column)<i class="fas fa-arrow-{{ ($tableOptions['direction'] ?? 'asc') === 'desc' ? 'down' : 'up' }} tf-sort-indicator"></i>@endif</span><button type="button" class="tf-column-filter {{ array_key_exists($column, $tableOptions['columns'] ?? []) || array_key_exists($column, $tableOptions['exclude'] ?? []) ? 'is-active' : '' }}" data-column="{{ $column }}" data-label="{{ $label }}" aria-label="Filter dan urutkan {{ $label }}" aria-expanded="false"><i class="fas fa-filter"></i></button></div></th>
                    @endforeach
                </tr></thead>
                <tbody>
                @forelse($outlets as $outlet)
                    <tr><td>{{ $outlet->nama_outlet ?: 'Nama outlet tidak tersedia' }}</td><td>{{ $outlet->id_outlet }}</td><td>{{ $outlet->kecamatan ?: '–' }}</td><td>{{ $outlet->tap }}</td><td>{{ $outlet->namasf ?: ($outlet->idsf ?: '—') }}</td><td>{{ number_format($outlet->qty) }}</td><td>{{ number_format($outlet->matched_qty) }}</td><td>{{ number_format($outlet->mismatch_qty) }}</td><td>@if($outlet->mismatch_qty === 0)<span class="pt-chip good">Sesuai</span>@else<span class="pt-chip bad">Perlu dicek</span>@endif</td></tr>
                @empty
                    <tr><td colspan="9" class="pt-empty">Tidak ada outlet untuk pilihan status ini pada filter tanggal dan transaksi yang dipilih.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>
    @else
        <section class="pt-card"><p class="pt-empty mb-0">Belum ada SN {{ $activeTab === 'progress' ? 'menunggu approval' : 'dari transaksi yang disetujui' }} pada filter ini.</p></section>
    @endif
    <div id="tf-column-menu" class="tf-column-menu" role="dialog" aria-labelledby="tf-column-title" hidden data-standalone="true" data-values-url="{{ route('surya.product-tracking.sold.filter-values') }}" data-selection="{{ json_encode($tableOptions['columns'] ?? new \stdClass()) }}" data-excluded="{{ json_encode($tableOptions['exclude'] ?? new \stdClass()) }}">
        <div class="tf-column-menu-body"><div class="tf-column-menu-heading"><strong id="tf-column-title">Filter kolom</strong><button type="button" data-filter-close aria-label="Tutup filter">×</button></div>
            <div class="tf-sort-menu"><button type="button" data-sort-direction="asc"><i class="fas fa-arrow-up"></i> Smallest to Largest</button><button type="button" data-sort-direction="desc"><i class="fas fa-arrow-down"></i> Largest to Smallest</button><button type="button" data-sort-clear>Urutan default</button></div>
            <input class="input" type="search" data-filter-search placeholder="Cari nilai…" aria-label="Cari nilai filter"><div class="tf-column-menu-tools"><button type="button" data-filter-all>Pilih semua</button><button type="button" data-filter-none>Kosongkan pilihan</button></div><div data-filter-values class="tf-column-values"></div><p data-filter-message role="status" aria-live="polite"></p><button type="button" data-filter-more hidden>Muat lainnya</button>
        </div><div class="tf-column-menu-actions"><button type="button" class="btn btn-secondary" data-filter-reset>Hapus filter</button><button type="button" class="btn" data-filter-apply>Terapkan</button></div>
    </div>
    <script src="{{ asset('js/surya-taskforce-columns.js') }}?v={{ filemtime(public_path('js/surya-taskforce-columns.js')) }}" defer></script>
    <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}" defer></script>
    <script src="{{ asset('vendor/flatpickr/id.js') }}" defer></script>
    <script src="{{ asset('js/surya-sold-date.js') }}?v={{ filemtime(public_path('js/surya-sold-date.js')) }}" defer></script>
    <script>
    document.querySelector('.pt-sold-filter')?.addEventListener('submit', (event) => {
        const button = event.submitter;
        if (!button?.classList.contains('pt-apply-filter')) return;
        const form = event.currentTarget;
        if (form.dataset.submitting === 'true') { event.preventDefault(); return; }
        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');
        button.disabled = true;
        button.innerHTML = '<span class="pt-spinner" aria-hidden="true"></span><span>Memfilter…</span>';
        const status = form.querySelector('.pt-loading-status');
        if (status) { status.textContent = 'Sedang memuat ringkasan dan rincian SN untuk filter ini.'; status.hidden = false; }
    });
    </script>
    @if(!empty($summaryTarget['summary_status']))<script>requestAnimationFrame(() => document.getElementById('sold-outlet-results')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));</script>@endif
@endsection
