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
            <div><strong>{{ number_format($summary->matched_qty) }}</strong><span>qty SN cocok</span></div>
            <div><strong>{{ number_format($summary->mismatch_qty) }}</strong><span>qty SN belum cocok</span></div>
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
            @php($summaryLevel = str_contains($heading, 'TAP') ? 'tap' : 'sf')
            <section id="summary-{{ $summaryLevel }}" class="pt-card pt-summary-section">
                <h2>{{ $heading }}</h2>
                @php($summaryColumns = [$summaryLevel => $summaryLevel === 'tap' ? 'TAP' : 'Sales Force'])
                @if($summaryLevel === 'sf') @php($summaryColumns['tap'] = 'TAP') @endif
                @php($summaryColumns += ['outlet_count' => 'Total outlet', 'matched_outlets' => 'Outlet sesuai', 'mismatch_outlets' => 'Outlet perlu dicek', 'qty' => 'Qty nota', 'matched_qty' => 'Qty SN cocok', 'mismatch_qty' => 'Qty SN belum cocok'])
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
                    @if($summaryLevel === 'tap')
                        @foreach($clusterSummaries as $clusterSummary)
                            <tr class="pt-cluster-summary-row {{ $clusterSummary->label === 'Grand Total' ? 'pt-grand-total-row' : '' }}"><td><strong>{{ $clusterSummary->label }}</strong></td><td>{{ number_format($clusterSummary->outlet_count) }}</td><td>{{ number_format($clusterSummary->matched_outlets) }}</td><td>{{ number_format($clusterSummary->mismatch_outlets) }}</td><td>{{ number_format($clusterSummary->qty) }}</td><td>{{ number_format($clusterSummary->matched_qty) }}</td><td>{{ number_format($clusterSummary->mismatch_qty) }}</td></tr>
                        @endforeach
                    @endif
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
                            <div><span>Qty SN cocok</span><strong>{{ number_format($group->matched_qty) }}</strong></div>
                            <div><span>Qty SN belum cocok</span><strong>{{ number_format($group->mismatch_qty) }}</strong></div>
                        </article>
                    @empty
                        <p class="pt-empty mb-0">Tidak ada ringkasan yang cocok dengan filter kolom ini.</p>
                    @endforelse
                    @if($summaryLevel === 'tap')
                        @foreach($clusterSummaries as $clusterSummary)
                            <article class="pt-summary-mobile-card pt-cluster-summary-row {{ $clusterSummary->label === 'Grand Total' ? 'pt-grand-total-row' : '' }}"><h3>{{ $clusterSummary->label }}</h3><div><span>Total outlet</span><strong>{{ number_format($clusterSummary->outlet_count) }}</strong></div><div><span>Outlet sesuai</span><strong>{{ number_format($clusterSummary->matched_outlets) }}</strong></div><div><span>Outlet perlu dicek</span><strong>{{ number_format($clusterSummary->mismatch_outlets) }}</strong></div><div><span>Qty nota</span><strong>{{ number_format($clusterSummary->qty) }}</strong></div><div><span>Qty SN cocok</span><strong>{{ number_format($clusterSummary->matched_qty) }}</strong></div><div><span>Qty SN belum cocok</span><strong>{{ number_format($clusterSummary->mismatch_qty) }}</strong></div></article>
                        @endforeach
                    @endif
                </div>
            </section>
        @endforeach
        @endif

        <section id="sold-outlet-results" class="pt-card">
            <div class="pt-result-head"><div><h2>{{ ($summaryTarget['summary_status'] ?? '') === 'matched' ? 'Outlet sesuai' : (($summaryTarget['summary_status'] ?? '') === 'mismatch' ? 'Outlet perlu dicek' : 'Rincian per outlet') }}</h2><span class="pt-muted">{{ number_format($outlets->count()) }} outlet{{ !empty($summaryTarget['summary_status']) ? ($summaryTarget['summary_status'] === 'matched' ? ' sesuai' : ' perlu dicek') : '' }}{{ !empty($summaryTarget['summary_key']) && $summaryTarget['summary_key'] !== '__NULL__' ? ' · '.$summaryTarget['summary_key'] : '' }} · bandingkan qty nota dengan SN Digipos yang cocok di outlet yang sama.</span></div>@if(!empty($summaryTarget['summary_status']))<a class="pt-button" href="{{ route('surya.product-tracking.sold', request()->except(['summary_level','summary_key','summary_status'])) }}">Kembali</a>@endif</div>
            <div class="pt-table-wrap"><table class="pt-table detail-table">
                <thead><tr>
                    @foreach(['nama_outlet' => 'Outlet', 'id_outlet' => 'ID Outlet', 'kecamatan' => 'Kecamatan', 'tap' => 'TAP', 'namasf' => 'Sales Force', 'qty' => 'Qty nota', 'matched_qty' => 'Qty SN cocok', 'mismatch_qty' => 'Qty SN belum cocok', 'status' => 'Status'] as $column => $label)
                        <th><div class="tf-column-heading"><span>{{ $label }}@if(($tableOptions['sort'] ?? '') === $column)<i class="fas fa-arrow-{{ ($tableOptions['direction'] ?? 'asc') === 'desc' ? 'down' : 'up' }} tf-sort-indicator"></i>@endif</span><button type="button" class="tf-column-filter {{ array_key_exists($column, $tableOptions['columns'] ?? []) || array_key_exists($column, $tableOptions['exclude'] ?? []) ? 'is-active' : '' }}" data-column="{{ $column }}" data-label="{{ $label }}" aria-label="Filter dan urutkan {{ $label }}" aria-expanded="false"><i class="fas fa-filter"></i></button></div></th>
                    @endforeach
                    @if(($summaryTarget['summary_status'] ?? '') !== 'matched')<th>SN</th>@endif
                </tr></thead>
                <tbody>
                @forelse($outlets as $outlet)
                    <tr><td>{{ $outlet->nama_outlet ?: 'Nama outlet tidak tersedia' }}</td><td>{{ $outlet->id_outlet }}</td><td>{{ $outlet->kecamatan ?: '–' }}</td><td>{{ $outlet->tap }}</td><td>{{ $outlet->namasf ?: ($outlet->idsf ?: '—') }}</td><td>{{ number_format($outlet->qty) }}</td><td>{{ number_format($outlet->matched_qty) }}</td><td>{{ number_format($outlet->mismatch_qty) }}</td><td>@if((int) $outlet->mismatch_qty === 0)<span class="pt-chip good">Sesuai</span>@else<span class="pt-chip bad">Perlu dicek</span>@endif</td>@if(($summaryTarget['summary_status'] ?? '') !== 'matched')<td><button type="button" class="pt-button pt-view-outlet-sn" data-id-outlet="{{ $outlet->id_outlet }}" aria-expanded="false"><i class="fas fa-barcode"></i> Lihat SN</button></td>@endif</tr>
                    @if(($summaryTarget['summary_status'] ?? '') !== 'matched')<tr class="pt-outlet-sn-row" hidden><td colspan="10"><div class="pt-outlet-sn-panel" data-outlet-sn-panel><span class="pt-muted">Klik “Lihat SN” untuk memuat data.</span></div></td></tr>@endif
                @empty
                    <tr><td colspan="{{ ($summaryTarget['summary_status'] ?? '') === 'matched' ? 9 : 10 }}" class="pt-empty">Tidak ada outlet untuk pilihan status ini pada filter tanggal dan transaksi yang dipilih.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="pt-outlet-mobile-list">
                @forelse($outlets as $outlet)
                    <article class="pt-outlet-mobile-card">
                        <div class="pt-outlet-mobile-head"><div><h3>{{ $outlet->nama_outlet ?: 'Nama outlet tidak tersedia' }}</h3><span>{{ $outlet->id_outlet }} · {{ $outlet->tap }}</span></div>@if((int) $outlet->mismatch_qty === 0)<span class="pt-chip good">Sesuai</span>@else<span class="pt-chip bad">Perlu dicek</span>@endif</div>
                        <dl><div><dt>Kecamatan</dt><dd>{{ $outlet->kecamatan ?: '–' }}</dd></div><div><dt>Sales Force</dt><dd>{{ $outlet->namasf ?: ($outlet->idsf ?: '—') }}</dd></div><div><dt>Qty nota</dt><dd>{{ number_format($outlet->qty) }}</dd></div><div><dt>Qty SN cocok</dt><dd>{{ number_format($outlet->matched_qty) }}</dd></div><div><dt>Qty SN belum cocok</dt><dd>{{ number_format($outlet->mismatch_qty) }}</dd></div></dl>
                        @if(($summaryTarget['summary_status'] ?? '') !== 'matched')
                            <button type="button" class="pt-button pt-view-outlet-sn" data-id-outlet="{{ $outlet->id_outlet }}" aria-expanded="false"><i class="fas fa-barcode"></i> Lihat SN</button>
                            <div class="pt-outlet-sn-panel pt-outlet-mobile-sn-panel" data-outlet-sn-panel hidden><span class="pt-muted">Klik “Lihat SN” untuk memuat data.</span></div>
                        @endif
                    </article>
                @empty
                    <p class="pt-empty mb-0">Tidak ada outlet untuk pilihan status ini pada filter tanggal dan transaksi yang dipilih.</p>
                @endforelse
            </div>
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
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.pt-view-outlet-sn');
        const moreButton = event.target.closest('.pt-load-more-outlet-sn');
        if (!button && !moreButton) return;
        const mobileCard = button?.closest('.pt-outlet-mobile-card');
        const detailRow = button ? button.closest('tr')?.nextElementSibling : moreButton.closest('.pt-outlet-sn-panel')?.closest('tr');
        const panel = mobileCard?.querySelector('[data-outlet-sn-panel]') || detailRow?.querySelector('[data-outlet-sn-panel]') || moreButton?.closest('[data-outlet-sn-panel]');
        if (!panel) return;
        const buttonOriginal = button?.innerHTML;
        if (button) {
            const isOpen = mobileCard ? !panel.hidden : !detailRow.hidden;
            if (isOpen) { if (mobileCard) panel.hidden = true; else detailRow.hidden = true; button.setAttribute('aria-expanded', 'false'); return; }
            if (mobileCard) panel.hidden = false; else detailRow.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            if (panel.dataset.loaded === 'true') return;
            button.disabled = true;
            button.classList.add('is-loading');
            button.innerHTML = '<span class="pt-spinner" aria-hidden="true"></span><span>Memuat SN…</span>';
        }
        const page = moreButton ? Number(panel.dataset.nextPage || 2) : 1;
        if (moreButton) { moreButton.disabled = true; moreButton.innerHTML = '<span class="pt-spinner" aria-hidden="true"></span> Memuat…'; }
        else panel.innerHTML = '<span class="pt-spinner" aria-hidden="true"></span> Memuat SN…';
        const outletId = button?.dataset.idOutlet || panel.dataset.idOutlet;
        panel.dataset.idOutlet = outletId;
        const params = new URLSearchParams({id_outlet: outletId, date: @json($filters['date']), tab: @json($activeTab), page: String(page)});
        if (@json($filters['tap'])) params.set('tap', @json($filters['tap']));
        if (@json($filters['sf'])) params.set('sf', @json($filters['sf']));
        try {
            const response = await fetch(@json(route('surya.product-tracking.sold.outlet-serials')) + '?' + params.toString(), {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Gagal memuat SN outlet.');
            const data = await response.json();
            let tableWrap = panel.querySelector('.pt-outlet-sn-table-wrap');
            let table = tableWrap?.querySelector('.pt-outlet-sn-table');
            if (!table) { table = document.createElement('table'); table.className = 'pt-outlet-sn-table'; table.innerHTML = '<thead><tr><th>Denom</th><th>SN awal</th><th>SN akhir</th><th>Qty</th></tr></thead><tbody></tbody>'; }
            if (!tableWrap) { tableWrap = document.createElement('div'); tableWrap.className = 'pt-outlet-sn-table-wrap'; tableWrap.append(table); }
            const tbody = table.querySelector('tbody');
            for (const item of data.rows) {
                const row = document.createElement('tr');
                for (const [index, value] of [item.denom, item.start, item.end, new Intl.NumberFormat('id-ID').format(item.qty)].entries()) { const cell = document.createElement('td'); if (index === 1 || index === 2) { const copy = document.createElement('button'); copy.type = 'button'; copy.className = 'pt-copy-sn'; copy.dataset.copySn = value; copy.setAttribute('aria-label', `Salin SN ${value}`); copy.textContent = value; cell.append(copy); } else cell.textContent = value; row.append(cell); }
                tbody.append(row);
            }
            if (!data.rows.length && page === 1) panel.textContent = 'Tidak ada SN yang belum cocok untuk outlet ini.';
            else if (data.rows.length) {
                if (!panel.querySelector('.pt-outlet-sn-search')) {
                    const searchForm = document.createElement('form');
                    searchForm.className = 'pt-outlet-sn-search';
                    searchForm.dataset.idOutlet = outletId;
                    searchForm.innerHTML = '<label>Cari SN di outlet ini</label><div><input type="search" name="sn" inputmode="numeric" pattern="[0-9]{9,24}" maxlength="24" placeholder="Masukkan SN" required><button class="pt-button primary" type="submit"><i class="fas fa-magnifying-glass"></i> Cek SN</button></div><p class="pt-outlet-sn-search-result" role="status" aria-live="polite" hidden></p>';
                    panel.replaceChildren(searchForm, tableWrap);
                } else if (!tableWrap.isConnected) panel.append(tableWrap);
                panel.querySelector('.pt-load-more-outlet-sn')?.remove();
                if (data.has_more) { const loadMore = document.createElement('button'); loadMore.type = 'button'; loadMore.className = 'pt-button pt-load-more-outlet-sn'; loadMore.textContent = 'Muat 50 rentang berikutnya'; panel.append(loadMore); panel.dataset.nextPage = String(page + 1); }
            }
            panel.dataset.loaded = 'true';
        } catch (error) { if (moreButton) { moreButton.disabled = false; moreButton.textContent = 'Coba muat lagi'; } else panel.textContent = error.message || 'Gagal memuat SN outlet.'; }
        finally { if (button) { button.disabled = false; button.classList.remove('is-loading'); button.innerHTML = buttonOriginal; } }
    });
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('.pt-outlet-sn-search');
        if (!form) return;
        event.preventDefault();
        const input = form.querySelector('[name="sn"]');
        const button = form.querySelector('button[type="submit"]');
        const output = form.querySelector('.pt-outlet-sn-search-result');
        const sn = input.value.trim();
        if (!/^[0-9]{9,24}$/.test(sn)) { input.reportValidity(); return; }
        button.disabled = true;
        button.innerHTML = '<span class="pt-spinner" aria-hidden="true"></span> Mencari…';
        output.hidden = false;
        output.textContent = 'Memeriksa SN di Nota dan Digipos…';
        const params = new URLSearchParams({id_outlet: form.dataset.idOutlet, sn, date: @json($filters['date']), tab: @json($activeTab)});
        try {
            const response = await fetch(@json(route('surya.product-tracking.sold.outlet-sn-lookup')) + '?' + params.toString(), {headers: {'Accept': 'application/json'}});
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Pencarian SN gagal.');
            output.replaceChildren();
            const status = document.createElement('strong');
            status.textContent = data.nota.length && data.digipos.length ? `SN ${sn} ditemukan di ST Nota dan Digipos.` : data.nota.length ? `SN ${sn} ada di ST Nota, belum ditemukan di Digipos.` : data.digipos.length ? `SN ${sn} ada di Digipos, belum ditemukan di ST Nota.` : `SN ${sn} tidak ditemukan di outlet ini pada tanggal terpilih.`;
            output.append(status);
            for (const [title, rows, kind] of [['ST Nota', data.nota, 'nota'], ['ST Digipos', data.digipos, 'digipos']]) {
                const section = document.createElement('section');
                const heading = document.createElement('h4'); heading.textContent = title; section.append(heading);
                if (!rows.length) { const empty = document.createElement('p'); empty.textContent = 'Tidak ditemukan.'; section.append(empty); }
                for (const row of rows) {
                    const line = document.createElement('p');
                    const formattedDate = new Intl.DateTimeFormat('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}).format(new Date(`${row.date}T00:00:00`));
                    line.textContent = kind === 'nota' ? `${formattedDate} · ${row.denom}` : `${formattedDate} · ${row.outlet} (${row.id_outlet}) · ${row.product} · SN ${row.start}–${row.end}`;
                    section.append(line);
                }
                output.append(section);
            }
        } catch (error) { output.textContent = error.message || 'Pencarian SN gagal.'; }
        finally { button.disabled = false; button.innerHTML = '<i class="fas fa-magnifying-glass"></i> Cek SN'; }
    });
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.pt-copy-sn');
        if (!button) return;
        const sn = button.dataset.copySn;
        try {
            if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(sn);
            else {
                const helper = document.createElement('textarea'); helper.value = sn; helper.setAttribute('readonly', ''); helper.style.position = 'fixed'; helper.style.opacity = '0'; document.body.append(helper); helper.select();
                const copied = document.execCommand('copy'); helper.remove(); if (!copied) throw new Error('copy-failed');
            }
            button.classList.add('is-copied'); button.setAttribute('aria-label', `SN ${sn} tersalin`); button.dataset.originalText ||= button.textContent; button.textContent = 'Tersalin ✓';
            window.setTimeout(() => { button.textContent = button.dataset.originalText; button.classList.remove('is-copied'); button.setAttribute('aria-label', `Salin SN ${sn}`); }, 1400);
        } catch { button.setAttribute('aria-label', `Gagal menyalin SN ${sn}`); }
    });
    document.addEventListener('click', (event) => {
        const link = event.target.closest('.pt-page a[href]');
        if (!link || link.target === '_blank' || link.hasAttribute('download') || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const target = new URL(link.href, location.href);
        if (target.origin !== location.origin || link.classList.contains('pt-navigating')) return;
        link.classList.add('pt-navigating');
        link.setAttribute('aria-busy', 'true');
        const spinner = document.createElement('span'); spinner.className = 'pt-spinner pt-link-spinner'; spinner.setAttribute('aria-hidden', 'true');
        link.prepend(spinner);
    });
    </script>
    <script>
    document.querySelector('.pt-sold-filter')?.addEventListener('submit', (event) => {
        const button = event.submitter;
        const form = event.currentTarget;
        if (button?.formAction?.includes('/download')) {
            const original = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="pt-spinner" aria-hidden="true"></span> Menyiapkan CSV…';
            window.setTimeout(() => { button.disabled = false; button.innerHTML = original; }, 4000);
            return;
        }
        if (!button?.classList.contains('pt-apply-filter')) return;
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
