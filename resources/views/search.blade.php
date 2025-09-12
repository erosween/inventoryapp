<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Nomor Cantik</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: #fff;
            color: #333;
            font-family: 'Segoe UI', sans-serif;
        }

        nav {
            background: #ffffff;
            border-bottom: 1px solid #eee;
            padding: 16px 0;
        }

        nav .nav-link {
            color: #333;
            font-weight: 500;
            margin: 0 10px;
        }

        nav .nav-link:hover {
            color: #c19e2e;
        }

        .hero {
            text-align: center;
            padding: 50px 20px 20px;
            color: #c19e2e;
        }

        .hero h1 {
            font-size: 2rem;
            font-weight: 800;
        }

        .hero p {
            color: #666;
        }

        /* Search */
        .search-box {
            max-width: 400px;
            margin: 15px auto 25px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 10px 14px;
            background: #fff;
            color: #333;
        }

        .search-box button {
            position: absolute;
            right: 12px;
            top: 6px;
            background: none;
            border: none;
            color: #888;
            font-size: 1.2rem;
        }

        /* Category Badge */
        .category {
            display: inline-flex;
            align-items: center;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.95rem;
            color: #333;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .category .icon {
            background: #c19e2e;
            color: #fff;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            font-size: 0.9rem;
        }

        /* Grid Numbers */
        .results-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-top: 10px;
        }

        .number-card {
            background: #fafafa;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .number-card:hover {
            background: #fff;
            border-color: #c19e2e;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
        }

        .number {
            font-size: 1.2rem;
            font-weight: 700;
            color: #222;
        }

        .bonus {
            font-size: 0.8rem;
            color: #c19e2e;
            margin-top: 6px;
        }

        footer {
            margin-top: 40px;
            padding: 20px;
            background: #fff;
            text-align: center;
            font-size: 0.9rem;
            color: #666;
            border-top: 1px solid #eee;
        }

        /* Floating WhatsApp Button */
        .wa-float {
            position: fixed;
            width: 55px;
            height: 55px;
            bottom: 25px;
            right: 25px;
            background-color: #25d366;
            color: #fff;
            border-radius: 50%;
            text-align: center;
            font-size: 28px;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .wa-float:hover {
            background-color: #20bd5c;
            color: #fff;
            transform: scale(1.05);
        }

        .wa-float {
            position: fixed;
            width: 75px;
            height: 75px;
            bottom: 20px;
            right: 20px;
            background-color: #25d366;
            color: #FFF;
            border-radius: 50%;
            text-align: center;
            font-size: 36px;
            box-shadow: 2px 2px 10px #999;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 1.5s infinite, wiggle 2s infinite ease-in-out;
        }

        .wa-float i {
            font-size: 40px;
        }

        /* Animasi Denyut */
        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7);
            }

            70% {
                transform: scale(1.1);
                box-shadow: 0 0 15px 20px rgba(37, 211, 102, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(37, 211, 102, 0);
            }
        }

        /* Animasi Goyang */
        @keyframes wiggle {

            0%,
            100% {
                transform: rotate(0deg);
            }

            15% {
                transform: rotate(5deg);
            }

            30% {
                transform: rotate(-5deg);
            }

            45% {
                transform: rotate(3deg);
            }

            60% {
                transform: rotate(-3deg);
            }

            75% {
                transform: rotate(2deg);
            }
        }

        .button {
            padding: 10px 20px;
            font-weight: 600;
            font-size: 13px;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .button-back {
            background: linear-gradient(135deg, #4b5563, #374151);
            color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .button-next {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: black;
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.5);
        }

        .button:hover {
            transform: scale(1.05);
            filter: brightness(1.05);
        }

        button:focus,
        a:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.4);
            /* biru transparan */
            border-radius: 12px;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="d-flex justify-content-between align-items-center container">
        <div class="font-weight-bold text-dark">katalog Nocan Murah</div>
    </nav>

    <!-- Hero -->
    <div class="hero">
        <h1>Pilih Nomor Cantikmu</h1>
        <p>Pilihan Premium</p>
    </div>

    <!-- Search -->
    <div class="search-box">
        <form action="{{ route('search') }}" method="GET">
            <input type="text" name="search" placeholder="Ketik minimal 3 digit..." value="{{ request('search') }}">
            <button type="submit"><i class="fa fa-search"></i></button>
        </form>
    </div>

    <!-- Category -->
    <div class="container text-center">
        <div class="category">
            <div class="icon">✨</div>
            Nomor Cantik
        </div>
    </div>

    <!-- Results -->
    <div class="container">
        <div class="results-container">
            @if ($results->isNotEmpty())
                @foreach ($results as $number)
                    <div class="number-card pilih-nomor" data-nomor="0{{ $number->nomor }}">
                        <div class="number">0{{ $number->nomor }}</div>
                        <div class="bonus">+ Kuota Gratis 20GB/Bulan selama SETAHUN</div>
                    </div>
                @endforeach
            @else
                <p class="text-center w-100">⚠️ Tidak ada nomor ditemukan.</p>
            @endif
        </div>

        <!-- Custom Pagination -->
        @if (request()->has('search'))
            <div class="mt-4 d-flex justify-content-center">
                @if (!$results->onFirstPage())
                    <a href="{{ $results->appends(['search' => request('search')])->previousPageUrl() }}"
                        class="button button-back mr-2">
                        ← Back
                    </a>
                @endif

                @if ($results->hasMorePages())
                    <a href="{{ $results->appends(['search' => request('search')])->nextPageUrl() }}"
                        class="button button-next">
                        Next →
                    </a>
                @endif
            </div>
        @endif
    </div>

    <!-- Footer -->
    <footer>
        📞 +62 822 8333 1333 | 💬 Order via WhatsApp
    </footer>

    <!-- Floating WhatsApp -->
    <a id="wa-btn"
        href="https://wa.me/6282283331333?text={{ urlencode('Halo, saya tertarik dengan nomor cantik Telkomsel.') }}"
        class="wa-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Tambahin Modal di bawah sebelum </body> -->
    <div class="modal fade" id="popupNomor" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 12px; text-align: center; padding: 20px;">
                <div class="modal-header border-0">
                    <h5 class="modal-title w-100" style="font-weight:700; color:#c19e2e;">Nomor Cantik Terpilih</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        style="font-size: 1.5rem; position: absolute; right: 20px; top: 20px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="popupNomorText" style="font-size:1.2rem; font-weight:700; margin-bottom:15px;"></div>
                    <p style="color:#555; font-size:0.95rem;">
                        🎁 Gratis kuota 20GB PERDANA + 20GB/Bulan selama <b>SETAHUN</b> dan KARTU AKTIF selama 3 TAHUN
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <a id="popupWaBtn" target="_blank" class="btn btn-success"
                        style="padding:10px 25px; font-weight:600; border-radius:10px;">
                        Lanjut ke WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tambahin Bootstrap JS (kalau belum ada) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let nomorDipilih = null;

        // Klik pilih nomor → buka modal
        document.querySelectorAll('.pilih-nomor').forEach(card => {
            card.addEventListener('click', () => {
                nomorDipilih = card.getAttribute('data-nomor');

                // isi teks popup
                document.getElementById('popupNomorText').textContent = nomorDipilih;

                // set href tombol WA
                let pesan =
                    `Halo, saya tertarik dengan nomor ${nomorDipilih}. Bonus kuota 20GB PERDANA + 20GB/Bulan selama SETAHUN.`;
                document.getElementById('popupWaBtn').href =
                    `https://wa.me/6282283331333?text=${encodeURIComponent(pesan)}`;

                // buka modal
                $('#popupNomor').modal('show');
            });
        });
    </script>

    <script>
        let nomorDipilih = null;

        // Klik pilih nomor
        document.querySelectorAll('.pilih-nomor').forEach(card => {
            card.addEventListener('click', () => {
                nomorDipilih = card.getAttribute('data-nomor');

                // highlight
                document.querySelectorAll('.pilih-nomor').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
            });
        });

        // Klik WA
        document.getElementById('wa-btn').addEventListener('click', function() {
            let pesan = "";

            if (nomorDipilih) {
                pesan = `Halo, saya tertarik dengan nomor ${nomorDipilih}`;
            } else {
                pesan = "Halo, saya mau tanya tentang nomor cantik";
            }

            this.href = `https://wa.me/6282283331333?text=${encodeURIComponent(pesan)}`;
        });
    </script>



    <style>
        .pilih-nomor.active {
            border: 2px solid #c19e2e;
            background: #fff7e6;
            box-shadow: 0 4px 12px rgba(193, 158, 46, 0.3);
        }
    </style>

</body>

</html>
