<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nomor Cantik</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Gaya CSS -->
    <style>
        body {
            /* background: linear-gradient(to right, #b4f4f2, #f9fbfc); */
            /* background: #ffffff; */
            color: #f9f8f8;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        .header-image {
            text-align: center;
            margin-bottom: 30px;
        }

        .header-image img {
            max-width: 60%;
            height: auto;
            display: block;
            margin: 0 auto;
            /* Center the image horizontally */
        }

        .search-box {
            text-align: center;
            max-width: 100%;
            margin: 0 auto;
            /* Centering the search box */
            padding: 0 20px;
        }

        .search-input {
            display: inline-block;
            width: calc(100% - 8px);
            /* Adjust width as needed */
            position: relative;
        }

        .form-control {
            border-radius: 30px;
            padding: 15px 60px 15px 20px;
            /* Padding for text input */
            border: none;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            width: 100%;
        }

        .search-button {
            position: absolute;
            right: 10px;
            top: 0;
            bottom: 0;
            margin: auto;
            border: none;
            background: #bf2e2e;
            color: #fff;
            padding: 5px 15px;
            border-radius: 30px;
            cursor: pointer;
        }

        .search-button:hover {
            background: #29a37a;
        }

        .results-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .number-card {
            background: linear-gradient(to right, #291f1f, #dd2929);
            color: white;
            padding: 8px;
            border-radius: 10px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: block;
            border: none;
            /* Hapus border default dari button */
        }

        .number-card:hover {
            transform: translateY(-5px);
            background: #29a37a;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
        }

        .number-card h5 {
            margin: 0;
            font-size: 1.3em;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .number-card:hover h5 {
            text-decoration: none;
        }


        /* Responsiveness for smaller screens */
        @media (max-width: 1000px) {
            .header-image img {
                max-width: 100%;
            }

            .search-input {
                width: calc(120% - 50px);
            }

            .search-button {
                padding: 8px 12px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header-image">
            <img src="image.jpg" alt="Header Image">
        </div>
        <div class="row justify-content-center search-box">
            <div class="col-md-8">
                <form action="{{ route('search') }}" method="GET" class="search-input">
                    <input type="text" name="search" class="form-control" placeholder="Cari Nomor Cantik"
                        aria-label="Cari Nomor Cantik" aria-describedby="button-addon2">
                    <button type="submit" class="search-button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"
                            fill="currentColor">
                            <path
                                d="M22.707 21.293l-6.004-6.004A7.473 7.473 0 0 0 18 10.5c0-4.136-3.364-7.5-7.5-7.5S3 6.364 3 10.5 6.364 18 10.5 18c1.7 0 3.259-.574 4.489-1.53l6.004 6.004a.996.996 0 0 0 1.414 0l1.414-1.414a.996.996 0 0 0 0-1.414zM10.5 16c-2.485 0-4.5-2.015-4.5-4.5s2.015-4.5 4.5-4.5 4.5 2.015 4.5 4.5-2.015 4.5-4.5 4.5z" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
        <div class="row results-container">
            @if (isset($results))
                @foreach ($results as $number)
                    <div class="col-md-4 col-lg-12">
                        <button class="number-card col-md-4 col-lg-12" data-number="{{ $number->nomor }}">
                            <h5>{{ $number->nomor }}</h5>
                        </button>
                    </div>
                @endforeach
            @else
                <div class="col-md-12 text-center">
                    <p>Masukkan nomor yang ingin dicari</p>
                </div>
            @endif
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let numberCards = document.querySelectorAll('.number-card');
            numberCards.forEach(card => {
                card.addEventListener('click', function(event) {
                    event.preventDefault(); // Menghentikan perilaku default dari tautan
                    let number = this.getAttribute('data-number');
                    let message = encodeURIComponent('Halo, saya tertarik dengan nomor ' + number);
                    window.location.href = 'https://wa.me/' + 6282283331333 + '?text=' + message;
                });
            });
        });
    </script>
</body>

</html>
