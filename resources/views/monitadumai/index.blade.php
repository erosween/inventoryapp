<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Outlet</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('static/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Leaflet Maps CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Telegram WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        /* RESET */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Roboto", sans-serif;
        }

        body {
            background: #f4f6f9;
            color: #333;
            font-family: 'Roboto', 'Open Sans', Arial, sans-serif;
            overflow-x: hidden;
        }

        /* HEADER */
        .header {
            background: linear-gradient(90deg, #d10000, #ff4d4d);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px 10px;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .header h1 {
            font-size: 1.4rem;
            text-align: center;
        }

        /* CONTAINER */
        .container {
            max-width: 600px;
            margin: 15px auto;
            padding: 10px;
            padding-bottom: 90px;
        }

        /* SEARCH BOX */
        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            /* MOBILE FRIENDLY */
        }

        .input-wrapper {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 18px;
            font-size: 16px;
            border: 2px solid #d10000;
            border-radius: 14px;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(209, 0, 0, 0.08);
        }

        .input-wrapper input:focus {
            outline: none;
            box-shadow: 0 4px 15px rgba(209, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .action-wrapper {
            display: flex;
            gap: 10px;
        }

        .radius-select {
            padding: 14px 10px;
            font-size: 14px;
            font-weight: bold;
            border: 2px solid #28a745;
            border-radius: 14px;
            background: #fff;
            color: #28a745;
            outline: none;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.08);
            transition: all 0.3s;
        }

        .radius-select:focus,
        .radius-select:hover {
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.15);
            transform: translateY(-2px);
        }

        .btn-search,
        .btn-scan {
            padding: 14px 20px;
            font-size: 15px;
            font-weight: bold;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-search {
            background: #d10000;
            color: #fff;
        }

        .btn-search:hover {
            background: #a80000;
            transform: translateY(-2px);
        }

        .btn-scan {
            background: #28a745;
            color: #fff;
        }

        .btn-scan:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        /* SKELETON */
        @keyframes pulse {
            0% {
                opacity: 0.6;
            }

            50% {
                opacity: 1;
            }

            100% {
                opacity: 0.6;
            }
        }

        .skeleton {
            background: #e0e0e0;
            border-radius: 14px;
            animation: pulse 1.5s infinite ease-in-out;
        }

        .skeleton-stats {
            height: 90px;
            margin-bottom: 12px;
        }

        .skeleton-card {
            height: 140px;
            margin-bottom: 12px;
        }

        /* STATS CARD */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-card {
            padding: 16px;
            border-radius: 18px;
            color: #fff;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.12);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card p {
            font-size: 13px;
            opacity: 0.85;
            margin-bottom: 4px;
            font-weight: bold;
        }

        .stat-card h2 {
            font-size: 22px;
            margin-bottom: 4px;
        }

        .stat-card span {
            font-size: 11px;
            background: rgba(0, 0, 0, 0.15);
            padding: 3px 10px;
            border-radius: 15px;
        }

        .red {
            background: linear-gradient(135deg, #d10000, #ff4c4c);
        }

        .blue {
            background: linear-gradient(135deg, #007bff, #3498db);
        }

        .green {
            background: linear-gradient(135deg, #28a745, #44cc44);
        }

        .orange {
            background: linear-gradient(135deg, #fd7e14, #ff9f43);
        }

        /* NEARBY RESULTS */
        .nearby-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px;
            background: #fff;
            border-radius: 18px;
            margin-bottom: 14px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
            cursor: pointer;
            transition: all 0.3s;
            border-left: 6px solid transparent;
        }

        .nearby-item:hover {
            transform: translateX(8px);
            background: #fff;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
            border-left-color: #d10000;
        }

        .nearby-info h4 {
            color: #d10000;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .nearby-info p {
            font-size: 12px;
            color: #666;
        }

        .distance-tag {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
        }

        /* TABLE - MOBILE FRIENDLY */
        .table-container {
            margin-top: 15px;
            background: #fff;
            border-radius: 18px;
            padding: 5px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table-header {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .table-header h2 {
            color: #d10000;
            font-size: 18px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            /* ALLOW SCROLL */
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 480px;
            /* ENSURE READABILITY */
        }

        table thead th {
            position: sticky;
            top: 0;
            background: #fcfcfc;
            z-index: 10;
            box-shadow: 0 1px 0 #f0f0f0;
        }

        /* Fixed First Column (Freeze Pane) */
        table th:first-child,
        table td:first-child {
            position: sticky;
            left: 0;
            z-index: 5;
            background: #fff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
        }

        /* Intersection sticky header and first column */
        table thead th:first-child {
            z-index: 15;
            background: #fcfcfc;
        }

        table thead {
            background: #fcfcfc;
            border-bottom: 2px solid #f0f0f0;
        }

        table th {
            padding: 12px 10px;
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
        }

        table td {
            padding: 15px 10px;
            font-size: 13px;
            color: #444;
            border-bottom: 1px solid #f9f9f9;
        }

        table td:first-child {
            font-weight: bold;
            color: #d10000;
            text-align: left;
            white-space: nowrap;
        }

        table td:nth-child(n+2) {
            text-align: right;
        }

        /* ALIGN NUMBERS RIGHT */

        .mom-indicator {
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .mom-positive {
            background: #e6f7e9;
            color: #28a745;
        }

        .mom-negative {
            background: #fdf2f2;
            color: #d10000;
        }

        .mom-neutral {
            background: #f5f5f5;
            color: #888;
        }

        .suggestions-box {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border-radius: 14px;
            max-height: 250px;
            overflow-y: auto;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            z-index: 2000;
            margin-top: 5px;
            padding: 0;
            list-style: none;
            /* REMOVE BULLETS */
        }

        .suggestions-box li {
            padding: 14px 18px;
            border-bottom: 1px solid #f9f9f9;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }

        .suggestions-box li small {
            display: block;
            color: #888;
            font-size: 11px;
            margin-top: 3px;
        }

        /* ACTIONS */
        .history-box {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border-radius: 14px;
            margin-top: 8px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            padding: 10px;
            display: none;
        }

        .history-item {
            padding: 12px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.2s;
        }

        .history-item:hover {
            background: #fdf2f2;
            color: #d10000;
        }

        .suggestions-box li:hover,
        .suggestions-box li.active {
            background: #fdf2f2;
            color: #d10000;
        }

        .suggestions-box li.active {
            border-left: 4px solid #d10000;
            padding-left: 14px;
        }

        /* MAP */
        #map {
            width: 100%;
            height: 250px;
            border-radius: 18px;
            margin-bottom: 20px;
            border: 2px solid #d10000;
            box-shadow: 0 8px 20px rgba(220, 0, 0, 0.1);
            z-index: 1;
        }

        .empty-state {
            text-align: center;
            margin: 40px auto;
        }

        .empty-img {
            max-width: 180px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(209, 0, 0, 0.1);
        }

        /* FAB */
        .fab {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #d10000;
            color: #fff;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(209, 0, 0, 0.35);
            z-index: 1000;
            transition: all 0.3s;
        }

        /* RESPONSIVE BREAKPOINTS */
        @media (max-width: 520px) {
            .search-box {
                flex-direction: column;
            }

            .input-wrapper,
            .action-wrapper {
                width: 100%;
            }

            .action-wrapper {
                flex-direction: row;
            }

            .radius-select {
                flex: 1;
            }

            .btn-scan {
                flex: 2;
            }

            .btn-scan span {
                display: inline;
            }

            .stats-container {
                grid-template-columns: 1fr 1fr;
            }

            .header h1 {
                font-size: 1.1rem;
            }

            .stat-card h2 {
                font-size: 18px;
            }
        }

        @media (max-width: 360px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
        }

        /* OUTLET PROFILE CARD */
        .outlet-profile-card {
            background: #fff;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            border-top: 5px solid #d10000;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #eee;
        }

        .profile-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #d10000, #ff4d4d);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 24px;
            box-shadow: 0 4px 10px rgba(209, 0, 0, 0.2);
        }

        .profile-title h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 4px;
            font-weight: 800;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #e6f7e9;
            color: #28a745;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .profile-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .detail-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f9f9f9;
            padding: 12px;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .detail-box:hover {
            background: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .detail-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .detail-info small {
            display: block;
            color: #888;
            font-size: 11px;
            margin-bottom: 2px;
        }

        .detail-info strong {
            display: block;
            color: #333;
            font-size: 13px;
        }

        @media (max-width: 520px) {
            .profile-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <h1>Monita Dumai (Monitoring Outlet Aktif)</h1>
    </header>

    <main class="container">
        <!-- Search Box -->
        <div class="search-box">
            <div class="input-wrapper">
                <input type="text" id="keyword" placeholder="Ketik ID atau Nama Outlet..." onkeyup="handleKeyUp(event)"
                    onkeydown="handleKeyDown(event)" onfocus="showHistory()">
                <ul id="suggestions" class="suggestions-box" style="display:none;"></ul>
                <div id="history" class="history-box"></div>
            </div>
            <div class="action-wrapper">
                <select id="scan-radius" class="radius-select">
                    <option value="0.3">300 m</option>
                    <option value="0.5">500 m</option>
                    <option value="1">1 km</option>
                    <option value="2">2 km</option>
                    <option value="5">5 km</option>
                </select>
                <button class="btn-scan" onclick="scanNearby()">
                    <i class="fas fa-location-crosshairs"></i> <span>Nearest</span>
                </button>
            </div>
        </div>

        <!-- Skeleton (Hidden by Default) -->
        <div id="skeleton-loader" style="display:none;">
            <div class="stats-container">
                <div class="skeleton skeleton-stats"></div>
                <div class="skeleton skeleton-stats"></div>
                <div class="skeleton skeleton-stats"></div>
                <div class="skeleton skeleton-stats"></div>
            </div>
            <div class="skeleton skeleton-card"></div>
        </div>

        <div id="map" style="display:none;"></div>
        <div class="stats-container" id="stats-container" style="display:none;"></div>
        <div id="result" class="result-container"></div>

        <!-- Empty State -->
        <div id="empty-state" class="empty-state">
            <img src="/static/images/shinchan.gif" alt="Shinchan Dance" class="empty-img">
            <p style="margin-top:15px; color:#aaa; font-size:13px;">Silakan cari outlet atau pakai scan lokasi</p>
        </div>

        <!-- Rincian Parameter Card -->
        <div class="table-container" id="detail-table" style="display:none;">
            <div class="table-header">
                <h2>Rincian Parameter</h2>
            </div>
            <div class="table-responsive">
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
        </div>
    </main>

    <div class="fab" id="fab-top" onclick="window.scrollTo({top:0, behavior:'smooth'})">
        <i class="fas fa-arrow-up"></i>
    </div>

    <footer class="footer" style="text-align:center; padding:10px; color:#aaa; font-size:12px;">
        <p>© 2025 Made by Rby</p>
    </footer>

    <!-- Leaflet Maps JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let map;
        let markers = [];
        let history = JSON.parse(localStorage.getItem('monita_history') || '[]');
        let selectedIndex = -1;

        function handleKeyUp(e) {
            const val = e.target.value;
            // Ignore arrow keys, enter, escape in keyup to prevent double execution
            if (["ArrowUp", "ArrowDown", "Enter", "Escape"].includes(e.key)) return;

            hideHistory();
            showSuggestions(val);
        }

        function handleKeyDown(e) {
            const box = document.getElementById('suggestions');
            const items = box.querySelectorAll('li');

            if (box.style.display === 'none' || items.length === 0) return;

            if (e.key === "ArrowDown") {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                updateSelection(items);
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                updateSelection(items);
            } else if (e.key === "Enter") {
                if (selectedIndex > -1) {
                    e.preventDefault();
                    items[selectedIndex].click();
                } else {
                    // IF NO ITEM SELECTED, ATTEMPT TO SEARCH THE RAW INPUT
                    searchOutlet();
                }
            } else if (e.key === "Escape") {
                box.style.display = 'none';
            }
        }

        function updateSelection(items) {
            items.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.classList.add('active');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('active');
                }
            });
        }

        // --- COUNTER ANIMATION ---
        function animateValue(element, start, end, duration) {
            if (isNaN(end)) {
                element.innerHTML = end;
                return;
            }
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const value = Math.floor(progress * (end - start) + start);
                element.innerHTML = value.toLocaleString();
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        async function searchOutlet() {
            const keyword = document.getElementById('keyword').value.trim();
            const resultDiv = document.getElementById('result');
            const statsContainer = document.getElementById('stats-container');
            const detailTable = document.getElementById('detail-table');
            const emptyState = document.getElementById('empty-state');
            const mapContainer = document.getElementById('map');
            const skeleton = document.getElementById('skeleton-loader');

            if (!keyword) return;

            // Start Loading State
            resultDiv.innerHTML = '';
            statsContainer.style.display = 'none';
            detailTable.style.display = 'none';
            emptyState.style.display = 'none';
            mapContainer.style.display = 'none';
            skeleton.style.display = 'block';

            try {
                const response = await fetch(`/monitadumai/search?keyword=${encodeURIComponent(keyword)}`);
                const data = await response.json();

                skeleton.style.display = 'none';

                if (data.length === 0) {
                    resultDiv.innerHTML = '<div class="card" style="text-align:center; color:red;">Outlet tidak ditemukan</div>';
                    emptyState.style.display = 'block';
                    return;
                }

                const outlet = data[0];
                saveToHistory(outlet.id_outlet, outlet.nama_outlet);

                resultDiv.innerHTML = `
                    <div class="outlet-profile-card">
                        <div class="profile-header">
                            <div class="profile-icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="profile-title">
                                <h3>${outlet.nama_outlet}</h3>
                                <span class="status-badge"><i class="fas fa-check-circle"></i> Active Outlet</span>
                            </div>
                        </div>
                        <div class="profile-details">
                            <div class="detail-box">
                                <div class="detail-icon" style="background:#ffe5e5; color:#d10000;">
                                    <i class="fas fa-id-card"></i>
                                </div>
                                <div class="detail-info">
                                    <small>ID Outlet</small>
                                    <strong>${outlet.id_outlet}</strong>
                                </div>
                            </div>
                            <div class="detail-box">
                                <div class="detail-icon" style="background:#e5f0ff; color:#007bff;">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="detail-info">
                                    <small>Sales Force (SF)</small>
                                    <strong>${outlet.sf}</strong>
                                </div>
                            </div>
                            <div class="detail-box">
                                <div class="detail-icon" style="background:#e8f7ec; color:#28a745;">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="detail-info">
                                    <small>TAP</small>
                                    <strong>${outlet.tap}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                statsContainer.innerHTML = `
                    <div class="stat-card red"><p>ST SA</p><h2 id="count-sa">0</h2><span>${outlet.mom_stsa}</span></div>
                    <div class="stat-card blue"><p>ST PV</p><h2 id="count-pv">0</h2><span>${outlet.mom_stpv}</span></div>
                    <div class="stat-card green"><p>CVM</p><h2 id="count-cvm">0</h2><span>${outlet.mom_cvm}</span></div>
                    <div class="stat-card orange"><p>DIGITAL</p><h2 id="count-dig">0</h2><span>${outlet.mom_digital}</span></div>
                `;
                statsContainer.style.display = 'grid';

                animateValue(document.getElementById('count-sa'), 0, parseInt(outlet.m_stsa), 800);
                animateValue(document.getElementById('count-pv'), 0, parseInt(outlet.m_stpv), 1000);
                animateValue(document.getElementById('count-cvm'), 0, parseInt(outlet.m_cvm), 1200);
                animateValue(document.getElementById('count-dig'), 0, parseInt(outlet.m_digital), 1400);

                renderRincian(outlet);
                detailTable.style.display = 'block';
                window.scrollTo({ top: statsContainer.offsetTop - 10, behavior: 'smooth' });

            } catch (err) {
                skeleton.style.display = 'none';
                resultDiv.innerHTML = '<div class="card" style="color:red;">Error koneksi ke server.</div>';
            }
            document.getElementById('keyword').value = '';
            document.getElementById('suggestions').style.display = 'none';
        }

        function renderRincian(outlet) {
            const tableBody = document.getElementById('table-body');
            const parameters = [
                { name: "TRX DIGIPOS", m1: outlet.m1_digipos, mtd: outlet.m_digipos, mom: outlet.mom_digipos, update: outlet.tgl_pack },
                { name: "SUPER SERU", m1: outlet.m1_super, mtd: outlet.m_super, mom: outlet.mom_super, update: outlet.tgl_pack },
                { name: "HOT PROMO", m1: outlet.m1_hot, mtd: outlet.m_hot, mom: outlet.mom_hot, update: outlet.tgl_pack },
                { name: "COMSAK", m1: outlet.m1_comsak, mtd: outlet.m_comsak, mom: outlet.mom_comsak, update: outlet.tgl_pack },
                { name: "SO SA", m1: outlet.m1_sosa, mtd: outlet.m_sosa, mom: outlet.mom_sosa, update: outlet.tgl_sa },
                { name: "SO PV", m1: outlet.m1_sopv, mtd: outlet.m_sopv, mom: outlet.mom_sopv, update: outlet.tgl_pv },
            ];

            tableBody.innerHTML = '';
            parameters.forEach(param => {
                const momStr = (param.mom || "0").toString().replace('%', '');
                const momNumeric = parseFloat(momStr);

                let momClass = "mom-neutral";
                let icon = '<i class="fas fa-minus"></i>';

                if (momNumeric > 0) {
                    momClass = "mom-positive";
                    icon = '<i class="fas fa-arrow-up"></i>';
                } else if (momNumeric < 0) {
                    momClass = "mom-negative";
                    icon = '<i class="fas fa-arrow-down"></i>';
                }

                tableBody.innerHTML += `
                    <tr>
                        <td>${param.name}</td>
                        <td>${Number(param.m1 || 0).toLocaleString()}</td>
                        <td>${Number(param.mtd || 0).toLocaleString()}</td>
                        <td><div class="mom-indicator ${momClass}">${icon} ${param.mom || "0.0%"}</div></td>
                        <td>${param.update || '-'}</td>
                    </tr>
                `;
            });
        }

        async function scanNearby() {
            const resultDiv = document.getElementById('result');
            const mapContainer = document.getElementById('map');
            const emptyState = document.getElementById('empty-state');
            const statsContainer = document.getElementById('stats-container');
            const detailTable = document.getElementById('detail-table');

            if (!navigator.geolocation) return alert("Browser GPS tidak aktif!");

            resultDiv.innerHTML = '<div class="card" style="text-align:center;"><i class="fas fa-circle-notch fa-spin"></i> Getting GPS...</div>';
            emptyState.style.display = 'none';
            statsContainer.style.display = 'none';
            detailTable.style.display = 'none';
            mapContainer.style.display = 'none';

            navigator.geolocation.getCurrentPosition(async (pos) => {
                const { latitude: lat, longitude: lon } = pos.coords;
                const radiusSelect = document.getElementById('scan-radius');
                const radiusValue = radiusSelect.value;
                const radiusLabel = radiusSelect.options[radiusSelect.selectedIndex].text;

                resultDiv.innerHTML = `<div class="card" style="text-align:center;"><i class="fas fa-satellite-dish fa-spin"></i> Scanning ${radiusLabel}...</div>`;

                try {
                    const response = await fetch(`/monitadumai/nearby?latitude=${lat}&longitude=${lon}&radius=${radiusValue}`);
                    const data = await response.json();

                    if (data.length === 0) {
                        resultDiv.innerHTML = `<div class="card" style="text-align:center; color:#d10000; font-weight:bold;">Tidak ada outlet dalam radius ${radiusLabel}.</div>`;
                        emptyState.style.display = 'block';
                        return;
                    }

                    mapContainer.style.display = 'block';
                    setTimeout(() => initMap(lat, lon, data), 100);

                    resultDiv.innerHTML = `<h3 style="margin:20px 0 12px 10px; font-size:18px;"><i class="fas fa-map-marked-alt"></i> Outlet Nearby: (${data.length} Found)</h3>`;
                    data.forEach(outlet => {
                        const dist = parseFloat(outlet.distance);
                        let distColor = '#27ae60';
                        if (dist < 0.05) distColor = '#2ecc71';
                        else if (dist > 0.15) distColor = '#f39c12';

                        const item = document.createElement('div');
                        item.className = 'nearby-item';
                        item.innerHTML = `
                            <div class="nearby-info">
                                <h4>${outlet.nama_outlet}</h4>
                                <p>${outlet.id_outlet} • <b>${outlet.sf}</b> • ${outlet.tap}</p>
                                <p style="font-size: 11px; margin-top: 5px; color: #d10000; font-weight:bold;">
                                    SA: ${Number(outlet.m_stsa).toLocaleString()} | PV: ${Number(outlet.m_stpv).toLocaleString()}
                                </p>
                            </div>
                            <div class="distance-tag" style="background:${distColor}15; color:${distColor}; border:1px solid ${distColor}30;">
                                ${dist.toFixed(2)} km
                            </div>
                        `;
                        item.onclick = () => {
                            document.getElementById('keyword').value = outlet.id_outlet;
                            searchOutlet();
                        };
                        resultDiv.appendChild(item);
                    });
                } catch (e) { resultDiv.innerHTML = '<div class="card">Server Error.</div>'; }
            }, (err) => {
                let msg = "GPS Error: " + err.message;
                if (err.code === 1) { // PERMISSION_DENIED
                    msg = `
                        <div style="text-align:center; padding:10px;">
                            <i class="fas fa-location-dot" style="font-size:30px; color:#d10000; margin-bottom:10px;"></i>
                            <h4 style="color:#d10000;">Akses Lokasi Ditolak</h4>
                            <p style="font-size:13px; color:#666; margin-top:5px;">
                                Sepertinya izin lokasi diblokir oleh Telegram/Browser.<br><br>
                                <b>Cara Mengatasi:</b><br>
                                1. Klik ikon (i) atau gembok di pojok browser.<br>
                                2. Cari "Location" dan pilih "Allow/Izinkan".<br>
                                3. Jika di App Telegram: Settings HP -> Apps -> Telegram -> Permissions -> Allow Location.
                            </p>
                            <button onclick="scanNearby()" style="margin-top:15px; background:#d10000; color:white; border:none; padding:10px 20px; border-radius:10px; cursor:pointer;">Coba Lagi</button>
                        </div>
                    `;
                }
                resultDiv.innerHTML = `<div class="card">${msg}</div>`;
            });
        }

        function initMap(lat, lon, outlets) {
            if (!map) {
                map = L.map('map').setView([lat, lon], 17);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            } else {
                map.setView([lat, lon], 17);
                markers.forEach(m => map.removeLayer(m));
                markers = [];
                map.invalidateSize();
            }
            L.circleMarker([lat, lon], { color: '#0d6efd', radius: 10, weight: 3, fillOpacity: 0.8 }).addTo(map).bindPopup("Kamu");
            outlets.forEach(o => {
                if (o.latitude && o.longitude) {
                    const m = L.marker([o.latitude, o.longitude]).addTo(map).bindPopup(`<b>${o.nama_outlet}</b><br><button onclick="selectFromMap('${o.id_outlet}')" style="margin-top:6px; background:#d10000; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; width:100%;">Pilih Outlet</button>`);
                    markers.push(m);
                }
            });
        }

        function selectFromMap(id) { document.getElementById('keyword').value = id; searchOutlet(); }

        async function showSuggestions(q) {
            const box = document.getElementById('suggestions');
            if (q.length < 2) { box.style.display = 'none'; return; }
            try {
                const res = await fetch(`/monitadumai/suggest?keyword=${q}`);
                const data = await res.json();
                box.innerHTML = '';
                selectedIndex = -1; // Reset selection

                if (data.length > 0) {
                    data.forEach(item => {
                        const li = document.createElement('li');
                        li.innerHTML = `
                            <b>${item.nama_outlet}</b>
                            <small>${item.sf} • ${item.tap}</small>
                        `;
                        li.onclick = () => {
                            document.getElementById('keyword').value = item.nama_outlet;
                            box.style.display = 'none';
                            searchOutlet();
                        };
                        box.appendChild(li);
                    });
                    box.style.display = 'block';
                } else { box.style.display = 'none'; }
            } catch (e) { }
        }

        function saveToHistory(id, name) {
            history = history.filter(h => h.id !== id);
            history.unshift({ id, name });
            if (history.length > 5) history.pop();
            localStorage.setItem('monita_history', JSON.stringify(history));
            localStorage.setItem('monita_last_search', id); // SAVE LAST SEARCH
        }

        function showHistory() {
            const hb = document.getElementById('history');
            if (history.length === 0) return;
            hb.innerHTML = '<p style="font-size:10px; color:#aaa; margin:5px 8px; font-weight:bold;">TERAKHIR DICARI</p>';
            history.forEach(h => {
                const d = document.createElement('div');
                d.className = 'history-item';
                d.innerHTML = `<i class="fas fa-clock-rotate-left"></i> <span>${h.name}</span>`;
                d.onclick = () => { document.getElementById('keyword').value = h.id; searchOutlet(); hb.style.display = 'none'; };
                hb.appendChild(d);
            });
            hb.style.display = 'block';
        }

        function hideHistory() { setTimeout(() => document.getElementById('history').style.display = 'none', 250); }

        window.onscroll = () => {
            const fab = document.getElementById('fab-top');
            if (document.documentElement.scrollTop > 200) fab.style.display = 'flex';
            else fab.style.display = 'none';
        };


    </script>
</body>

</html>