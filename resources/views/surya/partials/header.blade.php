<header class="surya-navbar" aria-label="Header SURYA">
    <button class="surya-menu-button" id="sidebar-toggle" data-surya-sidebar-toggle type="button"
        aria-label="Tampilkan atau sembunyikan menu" aria-controls="surya-sidebar" aria-expanded="false">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>
    <div class="surya-navbar-copy">
        <p class="surya-navbar-title">Monitoring &amp; Performance</p>
        <p class="surya-navbar-subtitle">Cluster Dumai, Bengkalis &amp; Rokan Hilir</p>
    </div>
    <div class="surya-report-context" aria-label="Tanggal workspace">
        <div><span>REPORTING WORKSPACE</span><time datetime="{{ now('Asia/Jakarta')->toDateString() }}">{{ now('Asia/Jakarta')->locale('id')->isoFormat('ddd, D MMM YYYY') }}</time></div>
    </div>
</header>
