@extends('surya.layouts.workspace')
@section('title', 'Stock on Hand')
@section('active-page', 'stock-on-hand')
@section('description', 'Pantau kebutuhan stok sales berdasarkan target harian.')
@section('content-class', 'surya-stock-page')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/surya-stock.css') }}?v={{ filemtime(public_path('css/surya-stock.css')) }}">
@endpush
@section('content')
<nav class="tabs" aria-label="Produk Stock on Hand">
    <a data-stock-family="PV" class="{{ $family === 'PV' ? 'active' : '' }}" @if($family === 'PV') aria-current="page" @endif href="{{ route('surya.stock-on-hand', ['family' => 'PV', 'tap' => $selectedTap, 'q' => $search]) }}">PV REGULER</a>
    <a data-stock-family="SA" class="{{ $family === 'SA' ? 'active' : '' }}" @if($family === 'SA') aria-current="page" @endif href="{{ route('surya.stock-on-hand', ['family' => 'SA', 'tap' => $selectedTap, 'q' => $search]) }}">SA</a>
</nav>
<form class="stock-filters" method="get" action="{{ route('surya.stock-on-hand') }}">
    <input type="hidden" name="family" value="{{ $family }}">
    <input class="stock-filter-input" id="sf-search" name="q" type="search" value="{{ $search }}" aria-label="Cari sales force" placeholder="Cari SF code, nama, TAP, atau cluster...">
    <select class="stock-filter-input" id="stock-tap" name="tap" aria-label="Filter TAP">
        <option value="">Semua TAP</option>
        @foreach($taps as $tap)<option value="{{ $tap }}" @selected($selectedTap === $tap)>{{ $tap }}</option>@endforeach
    </select>
    <button class="stock-filter-button" type="submit"><i class="fas fa-magnifying-glass" aria-hidden="true"></i> Tampilkan</button>
</form>
<section class="surya-card">
    <div class="stock-toolbar">
        <p class="muted">Target harian = sisa target bulanan ÷ sisa hari kerja ({{ $daysLeft }} hari).</p>
    </div>
    <ul class="stock-status-guide" aria-label="Keterangan status stok">
        <li><span class="status low">STOCK KURANG</span><span>OH &lt; TARGET DAILY</span></li>
        <li><span class="status safe">STOCK AMAN</span><span>CUKUP 1–{{ $stockBufferDays }} HARI</span></li>
        <li><span class="status over">STOCK OVER</span><span>OH &gt; {{ $stockBufferDays }} × TARGET DAILY</span></li>
    </ul>
    <div class="table-wrap stock-table-wrap" tabindex="0" role="region" aria-label="Tabel Stock on Hand">
        <table class="stock-table">
            <thead><tr><th>Nama SF</th><th>TAP</th><th>Cluster</th><th>Validity</th><th>Target</th><th>Target Daily</th><th>Actual MTD</th><th>Stock on Hand</th><th>Rekomendasi</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                @foreach($row['items'] as $item)
                <tr class="stock-row" data-search="{{ strtolower($row['idsf'].' '.$row['namasf'].' '.$row['idtap'].' '.$row['cluster']) }}">
                    <td><strong>{{ strtoupper($row['namasf']) }}</strong></td>
                    <td>{{ strtoupper($row['idtap']) }}</td><td>{{ strtoupper($row['cluster']) }}</td>
                    <td><span class="validity-pill">{{ strtoupper($item['validity']) }}</span></td>
                    <td>{{ number_format($item['target']) }}</td><td>{{ number_format($item['daily']) }}</td>
                    <td>{{ number_format($item['actual']) }}</td><td>{{ number_format($item['stock']) }}</td>
                    <td>{{ number_format($item['recommendation']) }}</td>
                    <td class="stock-status-cell">
                        <span class="status {{ $item['status_class'] }}">{{ strtoupper($item['status']) }}</span>
                        <small class="stock-status-detail">{{ $item['status_detail'] }}</small>
                    </td>
                </tr>
                @endforeach
            @empty
                <tr><td colspan="10">Belum ada target untuk keluarga produk ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div id="sf-empty" class="empty-state" hidden>SF tidak ditemukan.</div>
    <div class="table-footer"><span id="stock-page-info" class="table-info" aria-live="polite"></span><nav class="pagination" id="stock-pagination" aria-label="Halaman stok"></nav></div>
</section>
@endsection
@push('scripts')
<script>
const search = document.getElementById('sf-search');
const tapFilter = document.getElementById('stock-tap');
const allRows = [...document.querySelectorAll('.stock-row')];
const empty = document.getElementById('sf-empty');
const pager = document.getElementById('stock-pagination');
const info = document.getElementById('stock-page-info');
const pageSize = 12;
let page = 1;

function goTo(next) {
    page = next;
    renderStock();
    document.querySelector('.stock-table-wrap')?.scrollTo({top: 0});
}
function pageButton(label, disabled, action, options = {}) {
    const button = document.createElement('button');
    button.className = 'page-link' + (options.active ? ' active' : '');
    button.textContent = label;
    button.type = 'button';
    button.disabled = disabled;
    button.setAttribute('aria-label', options.label || 'Halaman ' + label);
    if (options.active) button.setAttribute('aria-current', 'page');
    button.addEventListener('click', action);
    return button;
}
function renderStock() {
    const query = search.value.toLocaleLowerCase('id-ID').trim();
    const filtered = allRows.filter(row => !query || row.dataset.search.includes(query));
    const pages = Math.max(1, Math.ceil(filtered.length / pageSize));
    page = Math.min(page, pages);
    allRows.forEach(row => row.hidden = true);
    filtered.slice((page - 1) * pageSize, page * pageSize).forEach(row => row.hidden = false);
    empty.hidden = allRows.length === 0 || filtered.length > 0;
    info.textContent = filtered.length
        ? 'MENAMPILKAN ' + ((page - 1) * pageSize + 1) + '–' + Math.min(page * pageSize, filtered.length) + ' DARI ' + filtered.length + ' BARIS'
        : 'TIDAK ADA DATA';
    pager.replaceChildren();
    pager.hidden = pages <= 1;
    if (pages <= 1) return;
    pager.append(pageButton('‹', page === 1, () => goTo(page - 1), {label: 'Halaman sebelumnya'}));
    const visible = [...new Set([1, page - 1, page, page + 1, pages].filter(n => n >= 1 && n <= pages))].sort((a, b) => a - b);
    let previous = 0;
    visible.forEach(n => {
        if (previous && n - previous > 1) {
            const dots = document.createElement('span');
            dots.className = 'pager-dots';
            dots.textContent = '…';
            pager.append(dots);
        }
        pager.append(pageButton(String(n), false, () => goTo(n), {active: n === page}));
        previous = n;
    });
    pager.append(pageButton('›', page === pages, () => goTo(page + 1), {label: 'Halaman berikutnya'}));
}
function syncProductLinks() {
    document.querySelectorAll('[data-stock-family]').forEach(link => {
        const url = new URL(link.href);
        url.searchParams.set('tap', tapFilter.value);
        url.searchParams.set('q', search.value);
        link.href = url.toString();
    });
}
search.addEventListener('input', () => { page = 1; renderStock(); syncProductLinks(); });
tapFilter.addEventListener('change', syncProductLinks);
renderStock();
</script>
@endpush
