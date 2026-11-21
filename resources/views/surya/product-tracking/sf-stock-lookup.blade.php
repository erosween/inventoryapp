@extends('surya.layouts.workspace')

@section('title', 'Cek Pemilik SN')
@section('active-page', 'product-tracking-sf-stock-lookup')
@section('description', 'Cari Sales Force pemilik rentang serial number.')
@section('content-class', 'pt-page')

@push('styles')<link rel="stylesheet" href="{{ asset('css/surya-product-tracking.css') }}?v={{ filemtime(public_path('css/surya-product-tracking.css')) }}">@endpush

@section('content')
    <section class="pt-card pt-lookup-card">
        <div class="pt-lookup-heading">
            <span class="pt-lookup-icon"><i class="fas fa-user-tag"></i></span>
            <div><h2>Cek Pemilik SN</h2><p class="pt-subtitle mb-0">Cari SN untuk melihat SF dan sumber rentang stok yang mencatatnya.</p></div>
        </div>
        <form method="get" class="pt-lookup" data-loading-message="Memeriksa rentang stok SF…">
            <input type="search" name="q" value="{{ $queryValue }}" inputmode="numeric" maxlength="16" placeholder="Masukkan SN PV 12 digit atau SA 15/16 digit" aria-label="Serial Number" required>
            <button class="pt-button primary" type="submit"><i class="fas fa-magnifying-glass"></i><span>Cek SN</span></button>
            <span class="pt-loading-status" role="status" aria-live="polite" hidden></span>
        </form>
        <p class="pt-muted mt-3 mb-0">Pencarian mencakup rentang SF Masuk admin dan Upload Stok SN Sales. Hasil menunjukkan rentang terdaftar, bukan sisa stok terkini.</p>
    </section>

    @if($error)
        <section class="pt-card"><div class="pt-empty text-danger">{{ $error }}</div></section>
    @elseif($queryValue !== '')
        <section class="pt-card">
            <div class="pt-result-head">
                <div><h2>Hasil pencarian</h2><p class="pt-subtitle mb-0">SN {{ $queryValue }} · {{ $matches->count() }} catatan rentang cocok</p></div>
                @if($matches->count() > 1)<span class="pt-chip">Periksa kemungkinan duplikasi</span>@endif
            </div>
            @if($matches->isEmpty())
                <div class="pt-empty">SN ini tidak ditemukan pada rentang SF Masuk admin maupun Upload Stok SN Sales.</div>
            @else
                <div class="pt-table-wrap">
                    <table class="pt-table">
                        <thead><tr><th>SF CODE</th><th>NAMA SALES FORCE</th><th>TAP</th><th>CLUSTER</th><th>DENOM</th><th>SUMBER</th><th>RENTANG TERCATAT</th><th>TANGGAL</th></tr></thead>
                        <tbody>
                            @foreach($matches as $row)
                                <tr>
                                    <td><strong>{{ $row->sfcode }}</strong></td>
                                    <td>{{ $row->namasf ?: 'Nama SF tidak ditemukan' }}</td>
                                    <td>{{ $row->tap ?: '—' }}</td>
                                    <td>{{ $row->cluster ?: '—' }}</td>
                                    <td>{{ $row->denom ?: $row->iddenom }}</td>
                                    <td><span class="pt-chip">{{ $row->source }}</span></td>
                                    <td>{{ $row->serial_range }}</td>
                                    <td>{{ $row->date ? \Carbon\Carbon::parse($row->date)->locale('id')->translatedFormat('j M Y') : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form.pt-lookup[data-loading-message]').forEach((form) => {
        form.addEventListener('submit', () => {
            if (form.dataset.submitting === 'true') return;
            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');
            const button = form.querySelector('button[type="submit"]');
            const status = form.querySelector('.pt-loading-status');
            if (button) { button.disabled = true; button.innerHTML = '<span class="pt-spinner" aria-hidden="true"></span><span>Mencari…</span>'; }
            if (status) { status.textContent = form.dataset.loadingMessage; status.hidden = false; }
        });
    });
});
</script>
@endpush
