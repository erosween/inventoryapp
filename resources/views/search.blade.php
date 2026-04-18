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
            padding: 60px 20px 40px;
            text-align: center;
        }

        .hero h1 {
            font-weight: 800;
            font-size: 2.5rem;
            color: var(--text-dark);
            margin-bottom: 16px;
            letter-spacing: -1px;
        }

        .hero p {
            color: var(--text-muted);
            font-weight: 400;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
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

        /* Horizontal Scroll for Categories on Mobile */
        .category-filter {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 40px;
            overflow-x: auto;
            padding: 10px 0;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .category-filter::-webkit-scrollbar {
            display: none;
        }

        @media (max-width: 768px) {
            .category-filter {
                justify-content: flex-start;
                flex-wrap: nowrap;
                padding: 10px 20px;
                /* Increased padding to prevent clipping */
                margin-left: -20px;
                /* Pull to edges */
                margin-right: -200px;
                /* Allow overflow */
                margin-right: -20px;
            }

            /* Ensure container doesn't clip the shadow/lift */
            .container {
                overflow: visible !important;
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
            padding: 15px 20px;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-accent span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-right: 10px;
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
                font-size: 1.8rem;
            }

            .hero {
                padding: 40px 20px;
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

            /* Fix clipped buttons on mobile */
            .category-filter {
                justify-content: flex-start;
                flex-wrap: nowrap;
                margin: 0 -15px 30px;
                /* Pull to screen edges */
                padding: 10px 15px;
                /* Match container gutter */
            }

            .results-grid {
                grid-template-columns: 1fr;
                padding: 0 10px;
            }

            .container {
                overflow: visible !important;
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
            <a href="{{ route('search') }}" class="cat-btn {{ !request('search') ? 'active' : '' }}">Semua Ganti</a>
        </div>

        <!-- Grid -->
        <div class="results-grid">
            @if ($results->isNotEmpty())
                @foreach ($results as $number)
                    @php
                        $numStr = (string) $number->nomor;
                        $accentClass = 'accent-silver';
                        $categoryName = 'Prio Reguler';
                        $icon = 'fa-star';

                        if (preg_match('/(\d)\1\1/', $numStr)) {
                            $accentClass = 'accent-platinum';
                            $categoryName = 'Prio Platinum';
                            $icon = 'fa-crown';
                        } elseif (substr_count($numStr, '8') >= 2 || substr_count($numStr, '9') >= 2) {
                            $accentClass = 'accent-gold';
                            $categoryName = 'Prio Gold';
                            $icon = 'fa-gem';
                        }
                    @endphp
                    <div class="number-card pilih-nomor" data-nomor="0{{ $number->nomor }}">
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

        <!-- Pagination -->
        @if ($results->hasPages())
            <div class="pagination-area">
                @if (!$results->onFirstPage())
                    <a href="{{ $results->appends(['search' => request('search')])->previousPageUrl() }}" class="page-btn">
                        Sebelumnya
                    </a>
                @endif

                @if ($results->hasMorePages())
                    <a href="{{ $results->appends(['search' => request('search')])->nextPageUrl() }}" class="page-btn">
                        Selanjutnya
                    </a>
                @endif
            </div>
        @endif
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
        document.querySelectorAll('.pilih-nomor').forEach(card => {
            card.addEventListener('click', () => {
                const nomor = card.getAttribute('data-nomor');
                document.getElementById('popupNomorText').textContent = nomor;

                const pesan = `Halo, saya tertarik pesan nomor cantik ini: ${nomor}. Mohon info lanjut.`;
                document.getElementById('popupWaBtn').href = `https://wa.me/6282283331333?text=${encodeURIComponent(pesan)}`;

                $('#popupNomor').modal('show');
            });
        });
    </script>
</body>

</html>