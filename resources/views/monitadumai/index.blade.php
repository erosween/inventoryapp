<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Outlet</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('static/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* RESET */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Roboto", sans-serif;
        }

        body {
            background: #f9f9f9;
            color: #333;
            font-family: 'Roboto', 'Open Sans', Arial, sans-serif;
        }

        .label,
        .table {
            font-family: 'Open Sans', sans-serif;
            font-weight: 400;
        }

        /* HEADER */
        .header {
            background: linear-gradient(90deg, #d10000, #ff4d4d);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px 10px;
            font-family: 'Roboto', sans-serif;
            font-weight: 700;
        }

        .header .logo {
            height: 35px;
            margin-right: 10px;
        }

        /* CONTAINER */
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 10px;
        }

        /* SEARCH BOX */
        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .input-wrapper {
            position: relative;
            flex: 1;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 2px solid #d10000;
            border-radius: 8px;
        }

        .btn-search {
            background: #d10000;
            color: #fff;
            padding: 12px 18px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-search:hover {
            background: #a80000;
        }

        /* SUGGESTIONS */
        .suggestions-box {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
            max-height: 180px;
            overflow-y: auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .suggestions-box li {
            list-style: none;
            padding: 10px;
            cursor: pointer;
            font-size: 14px;
            border-bottom: 1px solid #eee;
        }

        .suggestions-box li:hover {
            background-color: #f5f5f5;
        }

        /* STATISTIK GRID */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .stat-card {
            padding: 12px;
            border-radius: 10px;
            color: #fff;
            text-align: center;
            font-weight: bold;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card p {
            font-size: 14px;
        }

        .stat-card h2 {
            font-size: 20px;
            margin: 4px 0;
        }

        .red {
            background: #d10000;
        }

        .blue {
            background: #007bff;
        }

        .green {
            background: #28a745;
        }

        .orange {
            background: #fd7e14;
        }

        /* HASIL PENCARIAN */
        .result-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .card {
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            color: #d10000;
            margin-bottom: 6px;
            font-size: 18px;
        }

        .card p {
            font-size: 14px;
            margin: 3px 0;
        }

        /* TABEL RINCIAN */
        .table-container {
            margin-top: 20px;
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .table-container h2 {
            margin-bottom: 10px;
            font-size: 18px;
            color: #d10000;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        thead {
            background: #d10000;
            color: #fff;
        }

        thead th {
            padding: 10px;
            text-align: center;
        }

        tbody td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .mom-positive {
            color: #28a745;
            font-weight: bold;
            background: #eaf7ea;
            border-radius: 6px;
            padding: 4px 8px;
            display: inline-block;
        }

        .mom-negative {
            color: #d10000;
            font-weight: bold;
            background: #fdeaea;
            border-radius: 6px;
            padding: 4px 8px;
            display: inline-block;
        }

        .mom-neutral {
            color: #777;
            font-weight: bold;
            background: #f2f2f2;
            border-radius: 6px;
            padding: 4px 8px;
            display: inline-block;
        }

        .mom-indicator i {
            margin-right: 4px;
        }

        /* FOOTER */
        .footer {
            text-align: center;
            font-size: 12px;
            color: #777;
            margin-top: 15px;
        }

        /* RESPONSIVE */
        @media (max-width: 480px) {
            .search-box {
                flex-direction: row;
                gap: 8px;
            }

            .btn-search {
                padding: 10px 14px;
                font-size: 14px;
            }

            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
        }

        .empty-state {
            text-align: center;
            margin: 30px 0;
            background: #f9f9f9;
            /* warna sesuai background */
            border-radius: 12px;
            padding: 16px;
        }

        .empty-img {
            width: 220px;
            border-radius: 12px;
            background: transparent;
            box-shadow: 0 4px 20px rgba(220, 0, 0, 0.06);
        }
    </style>

</head>

<body>
    <!-- Header -->
    <header class="header">
        <h1>Monita (Monitoring Outlet Aktif)</h1>
    </header>

    <!-- Main Container -->
    <main class="container">
        <!-- Search Box -->
        <div class="search-box">
            <div class="input-wrapper">
                <input type="text" id="keyword" placeholder="Masukkan ID atau Nama Outlet"
                    onkeyup="showSuggestions(this.value)">
                <ul id="suggestions" class="suggestions-box"></ul>
            </div>
            <button class="btn-search" onclick="searchOutlet()">Cari</button>
        </div>
        <!-- Statistik -->
        <div class="stats-container" id="stats-container" style="display:none;"></div>

        <!-- Hasil Pencarian -->
        <div id="result" class="result-container"></div>


        <!-- Empty State GIF Doraemon Tidur -->
        <div id="empty-state" class="empty-state">
            <img src="/static/images/shinchan.gif" alt="Shinchan Dance" class="empty-img">
        </div>

        <!-- Rincian Parameter -->
        <div class="table-container" id="detail-table" style="display:none;">
            <h2>Rincian Parameter</h2>
            <table>
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>M-1</th>
                        <th>MTD</th>
                        <th>MoM</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody id="table-body"></tbody>
            </table>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <p>© 2025 Made by Rby
        </p>
    </footer>

    <script>
        async function searchOutlet() {
            const keyword = document.getElementById('keyword').value.trim();
            const resultDiv = document.getElementById('result');
            const statsContainer = document.getElementById('stats-container');
            const detailTable = document.getElementById('detail-table');
            const tableBody = document.getElementById('table-body');
            const emptyState = document.getElementById('empty-state');

            // Reset tampilan
            resultDiv.innerHTML = '';
            statsContainer.style.display = 'none';
            detailTable.style.display = 'none';
            emptyState.style.display = 'block'; // Show empty state sebelum pencarian

            if (!keyword) {
                resultDiv.innerHTML = '<p style="color:red;">Masukkan ID Outlet/Nama Outlet</p>';
                return;
            }

            resultDiv.innerHTML = '<p>Mencari...</p>';

            try {
                const response = await fetch(`/monitadumai/search?keyword=${encodeURIComponent(keyword)}`);
                const data = await response.json();

                if (data.length === 0) {
                    resultDiv.innerHTML = '<p style="color:red;">Outlet tidak ditemukan</p>';
                    emptyState.style.display = 'block'; // Show empty state jika gagal
                    document.getElementById('keyword').value = '';
                    document.getElementById('suggestions').innerHTML = '';
                    return;
                }

                emptyState.style.display = 'none'; // Hide empty state saat hasil ditemukan

                const outlet = data[0];

                // Tampilkan info outlet
                resultDiv.innerHTML = `
            <div class="card">
                <h3>${outlet.nama_outlet}</h3>
                <p><b>ID Outlet:</b> ${outlet.id_outlet}</p>
                <p><b>Nama SF:</b> ${outlet.sf}</p>
                <p><b>TAP:</b> ${outlet.tap}</p>
            </div>
        `;

                // Tampilkan statistik
                statsContainer.innerHTML = `
            <div class="stat-card red"><p>ST SA</p><h2>${outlet.m_stsa}</h2><span>${outlet.mom_stsa}</span></div>
            <div class="stat-card blue"><p>ST PV</p><h2>${Number(outlet.m_stpv).toLocaleString()}</h2><span>${outlet.mom_stpv}</span></div>
            <div class="stat-card green"><p>CVM</p><h2>${outlet.m_comsak}</h2><span>${outlet.mom_comsak}</span></div>
            <div class="stat-card orange"><p>DIGITAL</p><h2>${outlet.m_digital}</h2><span>${outlet.mom_digital}</span></div>
        `;
                statsContainer.style.display = 'grid';

                // Tampilkan tabel rincian parameter
                const parameters = [{
                        name: "TRX DIGIPOS",
                        m1: outlet.m1_digipos,
                        mtd: outlet.m_digipos,
                        mom: outlet.mom_digipos,
                        update: outlet.tgl_pack
                    },
                    {
                        name: "SUPER SERU",
                        m1: outlet.m1_super,
                        mtd: outlet.m_super,
                        mom: outlet.mom_super,
                        update: outlet.tgl_pack
                    },
                    {
                        name: "HOT PROMO",
                        m1: outlet.m1_hot,
                        mtd: outlet.m_hot,
                        mom: outlet.mom_hot,
                        update: outlet.tgl_pack
                    },
                    {
                        name: "COMSAK",
                        m1: outlet.m1_comsak,
                        mtd: outlet.m_comsak,
                        mom: outlet.mom_comsak,
                        update: outlet.tgl_pack
                    },
                    {
                        name: "SO SA",
                        m1: outlet.m1_sosa,
                        mtd: outlet.m_sosa,
                        mom: outlet.mom_sosa,
                        update: outlet.tgl_sa
                    },
                    {
                        name: "SO PV",
                        m1: outlet.m1_sopv,
                        mtd: outlet.m_sopv,
                        mom: outlet.mom_sopv,
                        update: outlet.tgl_pv
                    },
                ];

                tableBody.innerHTML = '';
                parameters.forEach(param => {
                    const momNumeric = parseFloat(param.mom);
                    let momClass = "mom-neutral";
                    let icon = '<i class="fas fa-minus"></i>';

                    if (momNumeric > 0) {
                        momClass = "mom-positive";
                        icon = '<i class="fas fa-arrow-up"></i>';
                    } else if (momNumeric < 0) {
                        momClass = "mom-negative";
                        icon = '<i class="fas fa-arrow-down"></i>';
                    }

                    const momValue = param.mom || "0%";

                    const row = `
                <tr>
                    <td style="text-align:left; font-weight:bold;">${param.name}</td>
                    <td>${param.m1 || 0}</td>
                    <td>${param.mtd || 0}</td>
                    <td><div class="mom-indicator ${momClass}">${icon} ${momValue}</div></td>
                    <td>${param.update || 0}</td>
                </tr>
            `;
                    tableBody.innerHTML += row;
                });

                detailTable.style.display = 'block';
            } catch (err) {
                resultDiv.innerHTML = '<p style="color:red;">Error koneksi ke server</p>';
                emptyState.style.display = 'block'; // Show empty state kalau error
            }

            // Reset input dan saran
            document.getElementById('keyword').value = '';
            document.getElementById('suggestions').innerHTML = '';
        }

        async function showSuggestions(value) {
            const suggestionsBox = document.getElementById('suggestions');
            if (!value) {
                suggestionsBox.innerHTML = '';
                return;
            }
            const response = await fetch(`/monitadumai/suggest?keyword=${value}`);
            const suggestions = await response.json();

            suggestionsBox.innerHTML = '';
            suggestions.forEach(name => {
                const li = document.createElement('li');
                li.textContent = name;
                li.onclick = () => {
                    document.getElementById('keyword').value = name;
                    suggestionsBox.innerHTML = '';
                };
                suggestionsBox.appendChild(li);
            });
        }
    </script>
</body>

</html>
