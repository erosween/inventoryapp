<div class="surya-table-footer">
    <span>Menampilkan {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} dari {{ number_format($paginator->total()) }} data</span>
    @if($paginator->hasPages())
    <nav class="surya-pagination" aria-label="Halaman tabel">
        <a class="surya-page-link {{ $paginator->onFirstPage() ? 'disabled' : '' }}" aria-label="Halaman sebelumnya" @if($paginator->onFirstPage()) aria-disabled="true" tabindex="-1" @endif href="{{ $paginator->previousPageUrl() ?: '#' }}">‹</a>
        @php $previousPage = 0; @endphp
        @foreach(collect([1, $paginator->currentPage() - 1, $paginator->currentPage(), $paginator->currentPage() + 1, $paginator->lastPage()])->filter(fn($page) => $page >= 1 && $page <= $paginator->lastPage())->unique()->sort() as $page)
            @if($previousPage && $page - $previousPage > 1)<span class="surya-page-gap">…</span>@endif
            <a class="surya-page-link {{ $page === $paginator->currentPage() ? 'active' : '' }}" @if($page === $paginator->currentPage()) aria-current="page" @endif href="{{ $paginator->url($page) }}">{{ $page }}</a>
            @php $previousPage = $page; @endphp
        @endforeach
        <a class="surya-page-link {{ $paginator->hasMorePages() ? '' : 'disabled' }}" aria-label="Halaman berikutnya" @unless($paginator->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless href="{{ $paginator->nextPageUrl() ?: '#' }}">›</a>
    </nav>
    @endif
</div>
