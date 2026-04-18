<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eksklusif Nocan - Pilih Nomor Cantik Anda</title>

    <!-- Google Fonts: Montserrat for that clean, premium look -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Frameworks & Icons -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #a68b3c;
            /* Bronze/Gold XL Prioritas */
            --primary-light: #f4f1e8;
            --text-dark: #332d2b;
            --text-muted: #757575;
            --bg-body: #ffffff;
            --bg-card: #f8f9fa;
            --radius-lg: 16px;
            --radius-md: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-dark);
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        html {
            overflow-x: hidden;
        }

        /* Navbar */
        nav {
            background: #fff;
            padding: 24px 0;
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.25rem;
            color: var(--text-dark) !important;
            letter-spacing: -0.5px;
        }

        .navbar-brand span {
            color: var(--primary);
        }

        /* Hero */
        .hero {
            padding: 80px 20px 60px;
            text-align: center;
        }

        .hero h1 {
            font-weight: 800;
            font-size: 2.8rem;
            color: var(--text-dark);
            margin-bottom: 20px;
            letter-spacing: -1.5px;
            line-height: 1.1;
        }

        .hero p {
            color: var(--text-muted);
            font-weight: 400;
            font-size: 1.15rem;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Search Section */
        .search-section {
            padding-bottom: 40px;
        }

        .search-box-wrapper {
            max-width: 700px;
            margin: 0 auto;
            background: var(--bg-card);
            padding: 8px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            border: 1px solid transparent;
            transition: var(--transition);
        }

        .search-box-wrapper:focus-within {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(166, 139, 60, 0.1);
        }

        .search-box-wrapper input {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px 24px;
            font-size: 1.1rem;
            font-weight: 500;
            outline: none;
            color: var(--text-dark);
        }

        .search-box-wrapper button {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            transition: var(--transition);
        }

        .search-box-wrapper button:hover {
            background: #8e7532;
            transform: translateX(3px);
        }

        /* Filter Wrapper & Scroll Hint */
        .filter-container {
            position: relative;
            margin-bottom: 50px;
            margin-top: 20px;
        }

        .scroll-indicator-right,
        .scroll-indicator-left {
            display: none;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 50px;
            height: 100%;
            pointer-events: auto;
            cursor: pointer;
            z-index: 5;
            align-items: center;
            color: var(--primary);
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .scroll-indicator-right:hover {
            padding-right: 10px;
            color: #8e7532;
        }

        .scroll-indicator-left:hover {
            padding-left: 10px;
            color: #8e7532;
        }

        .scroll-indicator-right {
            right: 0;
            background: linear-gradient(to left, #fff 20%, transparent);
            justify-content: flex-end;
            padding-right: 15px;
            animation: pulse-arrow-right 2s infinite ease-in-out;
        }

        .scroll-indicator-left {
            left: 0;
            background: linear-gradient(to right, #fff 20%, transparent);
            justify-content: flex-start;
            padding-left: 15px;
            animation: pulse-arrow-left 2s infinite ease-in-out;
        }

        @keyframes pulse-arrow-right {

            0%,
            100% {
                opacity: 0.4;
                transform: translateY(-50%) translateX(0);
            }

            50% {
                opacity: 1;
                transform: translateY(-50%) translateX(5px);
            }
        }

        @keyframes pulse-arrow-left {

            0%,
            100% {
                opacity: 0.4;
                transform: translateY(-50%) translateX(0);
            }

            50% {
                opacity: 1;
                transform: translateY(-50%) translateX(-5px);
            }
        }

        /* Horizontal Scroll for Categories on Mobile */
        .category-filter {
            display: flex;
            justify-content: center;
            gap: 12px;
            overflow-x: auto;
            padding: 10px 0;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .category-filter::-webkit-scrollbar {
            display: none;
        }

        @media (max-width: 768px) {
            .filter-container {
                margin: 0 -15px 30px;
            }

            .scroll-indicator-right,
            .scroll-indicator-left {
                display: flex;
            }

            .category-filter {
                justify-content: flex-start;
                flex-wrap: nowrap;
                padding: 10px 15px;
            }

            .results-grid {
                grid-template-columns: 1fr;
                padding: 0 10px;
            }

            .container {
                padding-left: 15px;
                padding-right: 15px;
            }

            .hero h1 {
                font-size: 1.85rem;
                padding: 0 10px;
            }

            .search-box-wrapper {
                margin: 0 10px;
                padding: 5px;
            }

            .search-box-wrapper input {
                padding: 10px 15px;
                font-size: 0.95rem;
            }

            .search-box-wrapper button {
                padding: 10px 20px;
                font-size: 0.8rem;
            }
        }

        .cat-btn {
            background: #fff;
            border: 1px solid #e0e0e0;
            color: var(--text-dark);
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            white-space: nowrap;
            transition: var(--transition);
            text-decoration: none;
        }

        .cat-btn:hover,
        .cat-btn.active {
            border-color: var(--primary);
            background: var(--primary-light);
            color: var(--primary);
            text-decoration: none;
        }

        /* Number Cards Grid */
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .number-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            transition: var(--transition);
            cursor: pointer;
            border: 1px solid #eee;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
        }

        .number-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(166, 139, 60, 0.15);
            border-color: var(--primary);
        }

        /* Card Header Accent */
        .card-accent {
            padding: 0 16px;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 38px;
            background-size: 200% auto;
        }

        .card-accent i {
            font-size: 0.85rem;
            margin-left: 10px;
        }

        .card-accent span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            margin-top: -1px;
            /* Optical adjustment for vertical center */
        }

        /* AJAX Transitions */
        #results-area {
            transition: opacity 0.3s ease;
        }

        #results-area.loading {
            opacity: 0.3;
            pointer-events: none;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-content {
            animation: fadeInScale 0.5s ease forwards;
        }

        .accent-platinum {
            background: linear-gradient(135deg, #2c3e50, #000);
        }

        .accent-gold {
            background: linear-gradient(135deg, #a68b3c, #8e7532);
        }

        .accent-silver {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }

        /* Card Body */
        .card-content {
            padding: 30px 20px;
            text-align: center;
            background: #fff;
            flex: 1;
        }

        .number-card .num-val {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            display: block;
            margin-bottom: 12px;
            letter-spacing: -1px;
        }

        .bonus-info {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 500;
            line-height: 1.5;
            margin-top: 5px;
        }

        .card-footer-price {
            border-top: 1px solid #f0f0f0;
            padding: 15px;
            font-weight: 800;
            color: var(--primary);
            font-size: 1.1rem;
            background: #fafafa;
        }

        /* Pagination */
        .pagination-area {
            margin-top: 50px;
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .page-btn {
            background: #fff;
            border: 1px solid #e0e0e0;
            color: var(--text-dark);
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .page-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            text-decoration: none;
        }

        /* WhatsApp Floating */
        .wa-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: #25d366;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 8px 16px rgba(37, 211, 102, 0.2);
            z-index: 1000;
            transition: var(--transition);
        }

        .wa-float:hover {
            transform: scale(1.1);
            color: #fff;
        }

        /* Modal Redesign */
        .modal-content {
            border: none;
            border-radius: 24px;
            padding: 24px;
        }

        .modal-header {
            border: none;
            padding: 0;
            margin-bottom: 24px;
        }

        .modal-body {
            padding: 0;
            text-align: center;
        }

        .btn-wa-confirm {
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            width: 100%;
            padding: 16px;
            border-radius: 50px;
            border: none;
            margin-top: 24px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .btn-wa-confirm:hover {
            background: #8e7532;
            color: #fff;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 1.9rem;
            }

            .hero {
                padding: 40px 20px 30px;
            }

            .search-section {
                padding: 0 10px;
            }

            .search-box-wrapper {
                border-radius: 50px;
                padding: 6px;
            }

            .search-box-wrapper input {
                padding: 10px 16px;
                font-size: 0.95rem;
            }

            .search-box-wrapper button {
                padding: 10px 20px;
                font-size: 0.8rem;
            }

            /* Avoid container bleed issues */
            .category-filter {
                justify-content: flex-start;
                flex-wrap: nowrap;
                margin: 0 -20px 30px;
                padding: 10px 20px;
            }

            .container {
                padding-left: 20px;
                padding-right: 20px;
            }
        }
    </style>
</head>

<body>

    <nav>
        <div class="container d-flex justify-content-between align-items-center">
            <a href="#" class="navbar-brand">Katalog<span>Nocan</span></a>
            <div class="d-none d-md-block text-muted small">Eksklusif Selection</div>
        </div>
    </nav>

    <div class="container">
        <!-- Hero -->
        <div class="hero">
            <h1>Cari & Pilih Nomor Cantik Anda</h1>
            <p>Temukan nomor impian untuk personal branding Anda dengan layanan premium kami.</p>
        </div>

        <!-- Search -->
        <div class="search-section">
            <form action="{{ route('search') }}" method="GET">
                <div class="search-box-wrapper">
                    <input type="text" name="search" placeholder="Contoh: 888 atau 1234..."
                        value="{{ request('search') }}">
                    <button type="submit">CARI NOMOR</button>
                </div>
            </form>
        </div>

        <!-- Filters -->
        <div class="filter-container">
            <div class="scroll-indicator-left">
                <i class="fas fa-chevron-left"></i>
            </div>
            <div class="category-filter">
                <a href="{{ route('search', ['search' => '777']) }}"
                    class="cat-btn {{ request('search') == '777' ? 'active' : '' }}">Triple 777</a>
                <a href="{{ route('search', ['search' => '888']) }}"
                    class="cat-btn {{ request('search') == '888' ? 'active' : '' }}">Triple 888</a>
                <a href="{{ route('search', ['search' => '999']) }}"
                    class="cat-btn {{ request('search') == '999' ? 'active' : '' }}">Triple 999</a>
                <a href="{{ route('search', ['search' => '000']) }}"
                    class="cat-btn {{ request('search') == '000' ? 'active' : '' }}">Triple 000</a>
                <a href="{{ route('search', ['search' => '123']) }}"
                    class="cat-btn {{ request('search') == '123' ? 'active' : '' }}">Berurutan</a>
                <a href="{{ route('search') }}" class="cat-btn {{ !request('search') ? 'active' : '' }}">Semua Nomor</a>
            </div>
            <div class="scroll-indicator-right">
                <i class="fas fa-chevron-right"></i>
            </div>
        </div>

        <!-- Dynamic Area for AJAX -->
        <div id="results-area">
            @if (!request('search'))
                <h5 class="font-weight-bold mb-4 text-center mt-4">Rekomendasi Untuk Anda</h5>
            @endif

            <!-- Grid -->
            <div class="results-grid">
                @if ($results->isNotEmpty())
                    @foreach ($results as $number)
                        @php
                            $numStr = (string) $number->nomor;
                            $accentClass = 'accent-silver';
                            $categoryName = 'Prio Reguler';
                            $icon = 'fa-star';

                            if (preg_match('/(\d)\1\1/', $numStr, $matches)) {
                                $tripleDigit = $matches[1];
                                $accentClass = 'accent-platinum';
                                $categoryName = "Prio Platinum $tripleDigit$tripleDigit$tripleDigit";
                                $icon = 'fa-crown';
                            } elseif (substr_count($numStr, '8') >= 2 || substr_count($numStr, '9') >= 2) {
                                $accentClass = 'accent-gold';
                                $categoryName = 'Prio Gold';
                                $icon = 'fa-gem';
                            }
                        @endphp
                        <div class="number-card pilih-nomor fade-in-content" data-nomor="0{{ $number->nomor }}">
                            <div class="card-accent {{ $accentClass }}">
                                <span>{{ $categoryName }}</span>
                                <i class="fas {{ $icon }}"></i>
                            </div>
                            <div class="card-content">
                                <span class="num-val">0{{ $number->nomor }}</span>
                                <div class="bonus-info">
                                    <div>Total Kuota 260GB / Tahun</div>
                                    <div>Masa Aktif Kartu 3 Tahun</div>
                                </div>
                            </div>
                            <div class="card-footer-price">
                                Free SIM Card
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-12 py-5 text-center">
                        <p class="text-muted">Maaf, nomor tidak ditemukan. Coba hapus filter atau cari pola lain.</p>
                    </div>
                @endif
            </div>

            <!-- Pagination (hide on initial state if results are hidden) -->
            @if ($results->hasPages() && (request('search') || request('page')))
                <div class="pagination-area">
                    @if (!$results->onFirstPage())
                        <a href="{{ $results->appends(['search' => request('search')])->previousPageUrl() }}"
                            class="page-btn ajax-link">
                            Sebelumnya
                        </a>
                    @endif

                    @if ($results->hasMorePages())
                        <a href="{{ $results->appends(['search' => request('search')])->nextPageUrl() }}"
                            class="page-btn ajax-link">
                            Selanjutnya
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Footer -->
    <footer class="container text-center py-5 mt-5" style="border-top: 1px solid #eee;">
        <span class="text-muted small">© 2024 Nocan Eksklusif. All rights reserved.</span>
    </footer>

    <!-- WA Float -->
    <a href="https://wa.me/6282283331333" class="wa-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Modal -->
    <div class="modal fade" id="popupNomor" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="font-weight-bold mb-0">Rincian Nomor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-1">Nomor Terpilih</p>
                    <div id="popupNomorText" class="h2 font-weight-bold mb-4" style="color: var(--primary);"></div>

                    <div class="p-4 rounded-lg text-left" style="background: var(--bg-card);">
                        <div class="d-flex mb-2">
                            <i class="fas fa-check-circle text-success mt-1 mr-2"></i>
                            <span class="small font-weight-bold">Gratis Kuota 260GB / Tahun</span>
                        </div>
                        <div class="d-flex">
                            <i class="fas fa-check-circle text-success mt-1 mr-2"></i>
                            <span class="small font-weight-bold">Masa Aktif Kartu 3 Tahun</span>
                        </div>
                    </div>

                    <a id="popupWaBtn" target="_blank" class="btn btn-wa-confirm">
                        BELI DI WHATSAPP
                    </a>
                    <p class="small text-muted mt-3 mb-0">Hanya berlaku untuk pembelian hari ini.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Number Selection Logic (Re-bindable)
        function bindSelectionEvents() {
            document.querySelectorAll('.pilih-nomor').forEach(card => {
                card.addEventListener('click', () => {
                    const nomor = card.getAttribute('data-nomor');
                    document.getElementById('popupNomorText').textContent = nomor;

                    const pesan = `Halo, saya tertarik pesan nomor cantik ini: ${nomor}. Mohon info lanjut.`;
                    document.getElementById('popupWaBtn').href = `https://wa.me/6282283331333?text=${encodeURIComponent(pesan)}`;

                    $('#popupNomor').modal('show');
                });
            });
        }

        // Auto-scroll active category into view
        function scrollActiveIntoView() {
            const activeBtn = document.querySelector('.cat-btn.active');
            const filterContainer = document.querySelector('.category-filter');

            if (activeBtn && filterContainer) {
                const containerRect = filterContainer.getBoundingClientRect();
                const btnRect = activeBtn.getBoundingClientRect();

                if (btnRect.left < containerRect.left || btnRect.right > containerRect.right) {
                    activeBtn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                }
            }
        }

        // AJAX Navigation Logic
        async function loadContent(url, updateHistory = true) {
            const resultsArea = document.getElementById('results-area');
            const filterContainer = document.querySelector('.category-filter');

            // Add loading state
            resultsArea.classList.add('loading');

            try {
                const response = await fetch(url);
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Update Grid and Pagination
                const newArea = doc.getElementById('results-area');
                if (newArea) {
                    resultsArea.innerHTML = newArea.innerHTML;
                }

                // Update Category Buttons State
                const newFilters = doc.querySelector('.category-filter');
                if (newFilters) {
                    filterContainer.innerHTML = newFilters.innerHTML;
                }

                // Sync Search Input Value
                const newSearchInput = doc.querySelector('input[name="search"]');
                const currentSearchInput = document.querySelector('input[name="search"]');
                if (newSearchInput && currentSearchInput) {
                    currentSearchInput.value = newSearchInput.value;
                }

                // Smooth opacity effect
                resultsArea.classList.remove('loading');

                // Update URL
                if (updateHistory) {
                    window.history.pushState({ url }, '', url);
                }

                // Re-bind listeners
                bindSelectionEvents();
                scrollActiveIntoView();
                bindAjaxLinks();

            } catch (error) {
                console.error('AJAX Error:', error);
                window.location.href = url; // Fallback to full reload
            }
        }

        function bindAjaxLinks() {
            document.querySelectorAll('.cat-btn, .ajax-link').forEach(link => {
                link.onclick = (e) => {
                    e.preventDefault();
                    loadContent(link.href);
                };
            });
        }

        // Handle Scroll Indicators Visibility
        function updateScrollIndicators() {
            const filterContainer = document.querySelector('.category-filter');
            const leftIndicator = document.querySelector('.scroll-indicator-left');
            const rightIndicator = document.querySelector('.scroll-indicator-right');

            if (!filterContainer || !leftIndicator || !rightIndicator) return;

            const scrollLeft = filterContainer.scrollLeft;
            const scrollWidth = filterContainer.scrollWidth;
            const clientWidth = filterContainer.clientWidth;

            // Show left indicator if scrolled
            leftIndicator.style.opacity = scrollLeft > 20 ? '1' : '0';
            leftIndicator.style.visibility = scrollLeft > 20 ? 'visible' : 'hidden';

            // Show right indicator if more to scroll
            const atEnd = scrollLeft + clientWidth >= scrollWidth - 20;
            rightIndicator.style.opacity = atEnd ? '0' : '1';
            rightIndicator.style.visibility = atEnd ? 'hidden' : 'visible';
        }

        // Initial Initialization
        window.addEventListener('DOMContentLoaded', () => {
            bindSelectionEvents();
            scrollActiveIntoView();
            bindAjaxLinks();

            // Set up scroll indicator listeners
            const filterContainer = document.querySelector('.category-filter');
            const leftIndicator = document.querySelector('.scroll-indicator-left');
            const rightIndicator = document.querySelector('.scroll-indicator-right');

            if (filterContainer) {
                filterContainer.addEventListener('scroll', updateScrollIndicators);
                window.addEventListener('resize', updateScrollIndicators);
                setTimeout(updateScrollIndicators, 500); // Initial check

                // Make arrows clickable
                if (leftIndicator) {
                    leftIndicator.addEventListener('click', () => {
                        filterContainer.scrollBy({ left: -200, behavior: 'smooth' });
                    });
                }
                if (rightIndicator) {
                    rightIndicator.addEventListener('click', () => {
                        filterContainer.scrollBy({ left: 200, behavior: 'smooth' });
                    });
                }
            }
        });

        // Handle browser back/forward buttons
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.url) {
                loadContent(e.state.url, false);
            } else {
                window.location.reload();
            }
        });
    </script>
</body>

</html>