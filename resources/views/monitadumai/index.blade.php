<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SURYA Performance Hub</title>
    @include('surya.partials.sidebar-boot')
    <script>
        (() => {
            const allowedTabs = new Set(['outlet', 'visit-detail']);
            const queryTab = new URLSearchParams(window.location.search).get('panel');
            const hashTab = window.location.hash.replace('#', '');
            const tab = allowedTabs.has(hashTab) ? hashTab : (allowedTabs.has(queryTab) ? queryTab : 'outlet');
            const style = document.createElement('style');
            style.id = 'dashboard-boot-panel-style';
            style.textContent = `.dashboard-panel.active{display:none!important}#dashboard-panel-${tab}{display:block!important}`;
            document.head.appendChild(style);
        })();
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('static/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/@fontsource-variable/geist@5.2.8/index.css">
    <link rel="stylesheet" href="{{ asset('css/surya-sidebar.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Leaflet Maps CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <!-- Telegram WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        /* RESET */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Geist Variable", Geist, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            background: #eef2f6;
            color: #20293a;
            font-family: "Inter", Arial, sans-serif;
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
            max-width: min(1720px, calc(100vw - 48px));
            margin: 15px auto;
            padding: 10px;
            padding-bottom: 90px;
        }

        .leader-dashboard {
            background:
                linear-gradient(135deg, rgba(20, 27, 43, 0.98), rgba(62, 31, 44, 0.94)),
                radial-gradient(circle at 18% 12%, rgba(255, 255, 255, 0.16), transparent 34%);
            color: #fff;
            border-radius: 30px;
            padding: 26px;
            margin-bottom: 22px;
            box-shadow: 0 28px 80px rgba(20, 27, 43, 0.26);
            overflow: hidden;
            position: relative;
        }

        .leader-dashboard::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
            background-size: 34px 34px;
            pointer-events: none;
        }

        .leader-dashboard>* {
            position: relative;
            z-index: 1;
        }

        .leader-top {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
            align-items: stretch;
        }

        .leader-eyebrow {
            color: #ffb4a6;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .leader-title {
            font-size: 32px;
            line-height: 1.1;
            margin-bottom: 12px;
            font-weight: 900;
        }

        .leader-subtitle {
            color: rgba(255, 255, 255, 0.74);
            font-size: 14px;
            line-height: 1.7;
            max-width: 780px;
        }

        .leader-intro {
            max-width: none;
        }

        .leader-top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .leader-pill,
        .leader-logout {
            min-height: 38px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 13px;
            font-size: 11px;
            font-weight: 900;
            text-decoration: none;
        }

        .leader-pill {
            color: rgba(255, 255, 255, 0.76);
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.14);
        }

        .leader-logout {
            color: #20293a;
            background: #fff;
        }

        .leader-kpis {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 0;
            max-width: none;
        }

        .leader-kpi {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 18px;
            padding: 14px;
            backdrop-filter: blur(10px);
            min-width: 0;
            overflow: hidden;
        }

        .leader-kpi small {
            display: block;
            color: rgba(255, 255, 255, 0.62);
            font-weight: 800;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .leader-kpi strong {
            display: block;
            font-size: clamp(18px, 1.6vw, 24px);
            line-height: 1;
            white-space: nowrap;
            letter-spacing: 0;
        }

        .leader-kpi span {
            display: block;
            color: rgba(255, 255, 255, 0.66);
            font-size: 11px;
            margin-top: 8px;
            line-height: 1.35;
        }

        .coverage-kpi {
            padding: 18px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.13), rgba(255, 255, 255, 0.075));
            border-color: rgba(255, 255, 255, 0.16);
            border-radius: 20px;
        }

        .coverage-kpi small {
            margin-bottom: 3px;
            color: rgba(255, 255, 255, 0.72);
            letter-spacing: 0;
        }

        .coverage-region-title {
            color: #fff;
            font-size: 15px;
            font-weight: 900;
            margin-bottom: 3px;
        }

        .coverage-region-sub {
            color: rgba(255, 255, 255, 0.58);
            font-size: 10px;
            font-weight: 900;
            margin-bottom: 13px;
        }

        .coverage-gauges {
            display: grid;
            grid-template-columns: repeat(3, minmax(150px, 1fr));
            gap: 16px;
        }

        .coverage-gauge-card {
            min-width: 0;
            border-radius: 15px;
            background: rgba(15, 23, 42, 0.26);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 10px;
            text-align: center;
        }

        .coverage-gauge {
            --gauge-color: #7c3aed;
            --gauge-percent: 0;
            width: min(100%, 174px);
            height: auto;
            margin: 0 auto 7px;
            display: block;
            overflow: visible;
        }

        .coverage-gauge-value {
            fill: #fff;
            stroke: rgba(15, 23, 42, 0.7);
            stroke-width: 3px;
            paint-order: stroke;
            font-size: 17px;
            font-weight: 900;
            line-height: 1;
            dominant-baseline: middle;
            text-anchor: middle;
        }

        .coverage-gauge-track {
            fill: none;
            stroke-width: 18;
            stroke-linecap: butt;
        }

        .coverage-gauge-low {
            stroke: #dc0000;
        }

        .coverage-gauge-mid {
            stroke: #f05a00;
        }

        .coverage-gauge-high {
            stroke: #f59e0b;
        }

        .coverage-gauge-target {
            stroke: var(--gauge-color);
            filter: drop-shadow(0 6px 10px rgba(0, 0, 0, 0.24));
        }

        .coverage-gauge-needle {
            stroke: #ffd11a;
            stroke-width: 5;
            stroke-linecap: round;
            transition: x2 0.2s ease, y2 0.2s ease;
        }

        .coverage-gauge-pin {
            fill: #ffd11a;
            stroke: rgba(255, 255, 255, 0.78);
            stroke-width: 2;
        }

        .coverage-meta {
            color: rgba(255, 255, 255, 0.62);
            font-size: 9px;
            font-weight: 900;
            line-height: 1.35;
        }

        .coverage-label {
            color: rgba(255, 255, 255, 0.76);
            font-size: 11px;
            font-weight: 900;
            margin-bottom: 6px;
        }

        .coverage-number {
            color: #fff;
            font-size: 15px;
            font-weight: 900;
            white-space: nowrap;
            margin-bottom: 3px;
        }

        .territory-panel {
            background: rgba(255, 255, 255, 0.96);
            color: #20293a;
            border-radius: 24px;
            padding: 14px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
            border: 1px solid rgba(226, 232, 240, 0.84);
            margin-top: 34px;
        }

        #leader-map {
            height: clamp(560px, 70vh, 820px);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(32, 41, 58, 0.08);
            margin-bottom: 12px;
        }

        .map-frame {
            position: relative;
        }

        .map-zoom-tools {
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 700;
            display: grid;
            gap: 8px;
        }

        .map-zoom-btn {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.96);
            color: #20293a;
            box-shadow: 0 10px 24px rgba(32, 41, 58, 0.18);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
        }

        .map-toolbar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 7px;
            margin-bottom: 8px;
        }

        .map-toggle {
            min-height: 34px;
            border: 1px solid #e3e7ec;
            border-radius: 14px;
            background: #f6f7f9;
            color: #475467;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 11px;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.18s ease;
        }

        .map-toggle.active {
            background: #d10000;
            color: #fff;
            border-color: #d10000;
            box-shadow: 0 10px 24px rgba(209, 0, 0, 0.22);
        }

        .fb-share-toggle {
            display: none;
            width: fit-content;
            grid-template-columns: repeat(2, minmax(132px, 1fr));
            gap: 5px;
            padding: 4px;
            margin: 0 auto 8px;
            border: 1px solid #e3e7ec;
            border-radius: 14px;
            background: #f1f4f8;
        }

        .fb-share-toggle.is-visible {
            display: inline-grid;
        }

        .fb-share-btn {
            min-height: 32px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #667085;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            cursor: pointer;
        }

        .fb-share-btn.active {
            background: #20293a;
            color: #fff;
            box-shadow: 0 8px 18px rgba(32, 41, 58, 0.18);
        }

        .map-mode-note {
            min-height: 32px;
            border-radius: 14px;
            background: #fff7ed;
            color: #9a3412;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.35;
            margin-bottom: 8px;
        }

        .map-thresholds {
            display: flex;
            justify-content: center;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .threshold-field {
            min-width: 170px;
            border-radius: 14px;
            background: #f6f7f9;
            padding: 7px 10px;
            display: inline-flex;
            justify-content: center;
            gap: 12px;
            align-items: center;
        }

        .threshold-field label {
            color: #667085;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .threshold-field input {
            width: 66px;
            height: 30px;
            border: 1px solid #d8dee8;
            border-radius: 10px;
            background: #fff;
            color: #20293a;
            padding: 0 8px;
            font-weight: 900;
            text-align: center;
            outline: none;
        }

        .map-legend-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 7px;
            margin-bottom: 8px;
        }

        .map-legend-item {
            min-height: 30px;
            border-radius: 12px;
            background: #f6f7f9;
            color: #475467;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 9px;
            font-size: 10px;
            font-weight: 900;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            display: inline-block;
            flex: 0 0 auto;
        }

        .legend-dot.has-pv {
            background: #7c3aed;
        }

        .legend-dot.has-sa {
            background: #0b5cab;
        }

        .legend-dot.has-cvm,
        .legend-dot.mom-up {
            background: #0f9d58;
        }

        .legend-dot.mom-down {
            background: #f97316;
        }

        .legend-dot.no-st {
            background: #fff;
            border: 3px solid #ef4444;
        }

        .district-label {
            border: 0;
            background: transparent;
        }

        .district-label span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 82px;
            min-height: 26px;
            padding: 5px 9px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.46);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.36);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.10);
            font-size: 9px;
            font-weight: 900;
            text-align: center;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s ease;
            letter-spacing: 0;
        }

        .district-label span:hover,
        .district-label.active span {
            background: rgba(15, 23, 42, 0.9);
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.22);
            border-color: rgba(255, 255, 255, 0.7);
        }

        .competition-label {
            border: 0;
            background: transparent;
        }

        .competition-label span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 96px;
            min-height: 28px;
            padding: 5px 10px;
            border-radius: 999px;
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.84);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.22);
            font-size: 9px;
            font-weight: 900;
            text-align: center;
            white-space: nowrap;
            letter-spacing: 0;
        }

        .competition-area-label {
            border: 0;
            background: transparent;
        }

        .competition-area-label span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            min-width: 122px;
            min-height: 28px;
            padding: 5px 9px;
            border-radius: 999px;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.72);
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.24);
            font-size: 8px;
            font-weight: 900;
            text-align: center;
            white-space: nowrap;
            letter-spacing: 0;
            backdrop-filter: blur(8px);
        }

        .competition-area-label .operator-logo {
            width: 20px;
            height: 20px;
            min-width: 20px;
            min-height: 20px;
            padding: 0;
            border-radius: 7px;
            background: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.64), inset 0 0 0 1px rgba(15, 23, 42, 0.08);
            backdrop-filter: none;
            flex: 0 0 auto;
            overflow: hidden;
        }

        .competition-area-label .operator-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .competition-area-label .competition-label-text {
            display: inline-block;
            min-width: 0;
            min-height: 0;
            max-width: 136px;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            backdrop-filter: none;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .leader-map-tooltip {
            background: rgba(15, 23, 42, 0.92);
            color: #fff;
            border: 0;
            border-radius: 12px;
            padding: 9px 10px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.26);
            font-size: 11px;
            font-weight: 800;
        }

        .leader-map-tooltip::before {
            display: none;
        }

        .leader-popup-title {
            font-size: 15px;
            font-weight: 900;
            color: #20293a;
            margin-bottom: 2px;
        }

        .leader-popup-meta {
            color: #344054;
            font-size: 12px;
            line-height: 1.45;
            margin-bottom: 8px;
        }

        .leader-popup-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 210px;
            overflow: hidden;
            border-radius: 10px;
            border: 1px solid #edf0f4;
        }

        .leader-popup-table th,
        .leader-popup-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #edf0f4;
            font-size: 11px;
            line-height: 1.2;
        }

        .leader-popup-table th {
            background: #f6f7f9;
            color: #667085;
            font-weight: 900;
            text-transform: uppercase;
            text-align: right;
        }

        .leader-popup-table th:first-child,
        .leader-popup-table td:first-child {
            text-align: left;
        }

        .leader-popup-table td {
            color: #20293a;
            font-weight: 800;
            text-align: right;
        }

        .leader-popup-table .mom-up {
            color: #0f9d58;
        }

        .leader-popup-table .mom-down {
            color: #d10000;
        }

        .leader-popup-table .mom-flat {
            color: #667085;
        }

        .leader-popup-table tr:last-child td {
            border-bottom: 0;
        }

        .territory-legend {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .tap-active-table {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #edf0f4;
            margin-top: 10px;
        }

        .tap-active-scroll {
            max-height: 72vh;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            position: relative;
        }

        .table-view-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            background: #fff;
            border-bottom: 1px solid #edf0f4;
        }

        .table-view-title {
            color: #20293a;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .table-view-toggle {
            display: inline-grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
            padding: 4px;
            border-radius: 12px;
            background: #f1f4f8;
            border: 1px solid #e2e8f0;
        }

        .table-view-btn {
            min-width: 96px;
            min-height: 30px;
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: #667085;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            cursor: pointer;
        }

        .table-view-btn.active {
            color: #fff;
            background: #20293a;
            box-shadow: 0 8px 18px rgba(32, 41, 58, 0.18);
        }

        .tap-active-table table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
            min-width: 980px;
        }

        .tap-active-table th,
        .tap-active-table td {
            padding: 10px 9px;
            border-bottom: 1px solid #edf0f4;
            font-size: 10px;
            color: #20293a;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .tap-active-table th {
            background: #f6f7f9;
            color: #667085;
            text-transform: uppercase;
            font-weight: 900;
            text-align: center;
        }

        .tap-active-table thead {
            position: sticky;
            top: 0;
            z-index: 8;
            background: #f6f7f9;
            box-shadow: 0 2px 0 #edf0f4;
        }

        .tap-active-table thead th:first-child,
        .tap-active-table td:first-child {
            position: sticky;
            left: 0;
            z-index: 3;
            background: #fff;
            box-shadow: 1px 0 0 #edf0f4;
            text-align: left;
            font-weight: 900;
            width: 16%;
        }

        .tap-active-table thead th:first-child {
            z-index: 10;
            background: #f6f7f9;
        }

        .active-table-sticky-spacer {
            color: transparent;
        }

        .tap-active-table td {
            text-align: right;
            font-weight: 800;
        }

        .tap-active-table .mom-up {
            color: #0f9d58;
        }

        .tap-active-table .mom-down {
            color: #d10000;
        }

        .tap-active-table .mom-flat {
            color: #667085;
        }

        .tap-active-table tr:last-child td {
            border-bottom: 0;
        }

        .tap-active-table .active-group-row td {
            background: #20293a;
            color: #fff;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0;
            text-transform: uppercase;
            text-align: right;
            padding: 11px 14px;
            border-bottom: 0;
        }

        .tap-active-table .active-group-row td:first-child {
            left: 0;
            z-index: 3;
            background: #20293a;
            box-shadow: 1px 0 0 rgba(255, 255, 255, 0.08);
            text-align: left;
        }

        .tap-active-table .active-group-row .mom-up,
        .tap-active-table .active-group-row .mom-down,
        .tap-active-table .active-group-row .mom-flat {
            color: #fff;
        }

        .territory-chip {
            border-radius: 14px;
            padding: 10px;
            background: #f6f7f9;
        }

        .territory-chip small {
            display: block;
            color: #7c8794;
            font-size: 10px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .territory-chip strong {
            font-size: 12px;
            color: #20293a;
        }

        .leader-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 18px;
        }

        .leader-section {
            background: #fff;
            border-radius: 24px;
            padding: 18px;
            box-shadow: 0 14px 36px rgba(32, 41, 58, 0.08);
            color: #20293a;
        }

        .leader-section h2 {
            font-size: 15px;
            margin-bottom: 14px;
        }

        .insight-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 16px;
        }

        .insight-box {
            background: rgba(255, 255, 255, 0.96);
            color: #20293a;
            border-radius: 20px;
            padding: 16px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
        }

        .insight-box small {
            display: block;
            color: #7c8794;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 7px;
        }

        .insight-box strong {
            display: block;
            font-size: 20px;
            margin-bottom: 7px;
        }

        .insight-box span {
            color: #657180;
            font-size: 11px;
            line-height: 1.5;
        }

        .mix-row {
            display: grid;
            grid-template-columns: 86px 1fr 82px;
            gap: 10px;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f2f5;
        }

        .mix-row:last-child {
            border-bottom: 0;
        }

        .mix-label {
            font-size: 12px;
            font-weight: 900;
        }

        .mix-track {
            height: 10px;
            border-radius: 999px;
            background: #eef1f5;
            overflow: hidden;
        }

        .mix-track span {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #d10000, #ff8a80);
        }

        .mix-value {
            text-align: right;
            color: #20293a;
            font-weight: 900;
            font-size: 12px;
        }

        .leader-action {
            display: grid;
            grid-template-columns: 38px 1fr;
            gap: 12px;
            align-items: start;
            padding: 13px 0;
            border-bottom: 1px solid #f0f2f5;
        }

        .leader-action:last-child {
            border-bottom: 0;
        }

        .leader-action-icon {
            width: 38px;
            height: 38px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .leader-action.danger .leader-action-icon {
            background: #d10000;
        }

        .leader-action.warning .leader-action-icon {
            background: #f59e0b;
        }

        .leader-action.success .leader-action-icon {
            background: #0f9d58;
        }

        .leader-action strong {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .leader-action p {
            color: #657180;
            font-size: 12px;
            line-height: 1.5;
        }

        .tap-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f2f5;
        }

        .tap-row:last-child {
            border-bottom: 0;
        }

        .tap-name {
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 7px;
        }

        .tap-bar {
            width: 100%;
            height: 8px;
            background: #eef1f5;
            border-radius: 999px;
            overflow: hidden;
        }

        .tap-bar span {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #d10000, #f59e0b);
        }

        .tap-meta {
            font-size: 11px;
            color: #7c8794;
            margin-top: 6px;
        }

        .tap-score {
            text-align: right;
            font-weight: 900;
            color: #d10000;
            white-space: nowrap;
        }

        .search-panel-title {
            margin: 22px 0 12px;
            color: #20293a;
            font-size: 15px;
            font-weight: 900;
        }

        .leader-lock {
            background: #fff;
            border-radius: 24px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 14px 36px rgba(32, 41, 58, 0.08);
            display: grid;
            grid-template-columns: 46px 1fr auto;
            gap: 14px;
            align-items: center;
            border: 1px solid #f0f2f5;
        }

        .leader-lock-icon {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: #fff1f1;
            color: #d10000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .leader-lock h2 {
            font-size: 15px;
            color: #20293a;
            margin-bottom: 4px;
        }

        .leader-lock p {
            font-size: 12px;
            color: #657180;
            line-height: 1.5;
        }

        .leader-login-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #fff;
            background: #d10000;
            text-decoration: none;
            border-radius: 14px;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .leader-inline-login {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) auto;
            gap: 8px;
            min-width: 360px;
        }

        .leader-inline-login input {
            min-width: 0;
            padding: 11px 13px;
            border: 1px solid #dfe3e8;
            border-radius: 12px;
            outline: none;
            font-size: 13px;
        }

        .leader-inline-login input:focus {
            border-color: #d10000;
            box-shadow: 0 0 0 3px rgba(209, 0, 0, 0.09);
        }

        .leader-inline-login .leader-login-btn {
            border: 0;
            cursor: pointer;
        }

        .leader-login-feedback {
            grid-column: 1 / -1;
            color: #d10000;
            font-size: 11px;
            font-weight: 700;
        }

        .dashboard-menu {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 18px;
            padding: 7px;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 5px 18px rgba(31, 45, 61, 0.08);
        }

        .dashboard-menu[hidden] {
            display: none;
        }

        .dashboard-menu-button {
            padding: 12px 16px;
            border: 0;
            border-radius: 12px;
            background: transparent;
            color: #657180;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .dashboard-menu-button.active {
            background: linear-gradient(135deg, #d10000, #f43f3f);
            color: #fff;
            box-shadow: 0 4px 12px rgba(209, 0, 0, 0.22);
        }

        .dashboard-panel {
            display: none;
        }

        .dashboard-panel.active {
            display: block;
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
            position: relative;
            padding: 12px 14px;
            padding-right: 42px;
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

        .history-item-remove {
            position: absolute;
            top: 50%;
            right: 10px;
            width: 24px;
            height: 24px;
            transform: translateY(-50%);
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            font-size: 13px;
        }
        .history-item-remove:hover { background: #fee2e2; color: #b91c1c; }

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
        #dashboard-panel-outlet { min-width: 0; overflow: visible; }
        #map {
            position: relative;
            width: 100%;
            height: clamp(460px, calc(100vh - 250px), 760px);
            min-height: 460px;
            overflow: hidden;
            border-radius: 18px;
            margin-bottom: 20px;
            border: 2px solid #d10000;
            box-shadow: 0 8px 20px rgba(220, 0, 0, 0.1);
            z-index: 1;
        }

        #map.map-search-expanded {
            min-height: min(760px, calc(100vh - 140px));
            height: min(760px, calc(100vh - 140px));
        }

        body.map-fullscreen-open {
            overflow: hidden;
        }

        #map.map-fullscreen-active {
            position: fixed !important;
            inset: 0;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
            border: 0;
            border-radius: 0;
            margin: 0;
            z-index: 99999;
        }

        /* Navigasi tetap bisa dipanggil ketika peta sedang fullscreen. */
        body.map-fullscreen-open #sidebar-toggle {
            position: fixed;
            top: 14px;
            left: 14px;
            z-index: 100002;
            box-shadow: 0 4px 14px rgba(0,0,0,.22);
        }
        body.map-fullscreen-open .enterprise-sidebar { z-index: 100001; }

        .map-outlet-detail-panel {
            position: absolute;
            top: 18px;
            right: 18px;
            bottom: 18px;
            width: min(560px, calc(100vw - 36px));
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(209, 0, 0, 0.12);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 18px 55px rgba(18, 28, 45, 0.28);
            z-index: 1200;
        }

        .map-outlet-detail-panel.is-open { display: flex; }
        #map .map-outlet-detail-panel { z-index: 1200; }

        .map-detail-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 20px;
            border-bottom: 1px solid #edf0f3;
            background: linear-gradient(135deg, #fff 0%, #fff6f6 100%);
        }

        .map-detail-header h3 {
            margin: 0 0 4px;
            color: #202b3c;
            font-size: 18px;
            line-height: 1.25;
        }

        .map-detail-header p {
            margin: 0;
            color: #7a8492;
            font-size: 12px;
            font-weight: 700;
        }

        .map-detail-close {
            flex: 0 0 36px;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 10px;
            background: #fff;
            color: #d10000;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(18, 28, 45, 0.1);
        }

        .map-detail-body {
            flex: 1;
            min-height: 0;
            overflow: auto;
            padding: 16px;
            overscroll-behavior: contain;
        }

        .map-detail-profile {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 14px;
        }

        .map-detail-profile-item {
            min-width: 0;
            padding: 10px;
            border-radius: 11px;
            background: #f7f8fa;
        }

        .map-detail-profile-item small {
            display: block;
            margin-bottom: 3px;
            color: #8a929e;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .map-detail-profile-item strong {
            display: block;
            overflow: hidden;
            color: #263244;
            font-size: 12px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .map-detail-table-title {
            margin: 2px 0 10px;
            color: #d10000;
            font-size: 15px;
            font-weight: 800;
        }

        .map-detail-table-wrap {
            overflow-x: auto;
            border: 1px solid #edf0f3;
            border-radius: 12px;
        }

        .map-detail-table {
            width: 100%;
            min-width: 520px;
            border-collapse: collapse;
            background: #fff;
        }

        .map-detail-table th,
        .map-detail-table td {
            padding: 11px 9px;
            border-bottom: 1px solid #f0f2f4;
            font-size: 11px;
            text-align: right;
            white-space: nowrap;
        }

        .map-detail-table th {
            background: #f8f9fa;
            color: #7b8490;
            font-size: 9px;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .map-detail-table th:first-child,
        .map-detail-table td:first-child {
            color: #d10000;
            font-weight: 800;
            text-align: left;
        }

        .map-detail-table tbody tr:last-child td { border-bottom: 0; }

        .map-detail-loading,
        .map-detail-error {
            padding: 50px 20px;
            color: #7a8492;
            text-align: center;
        }

        .map-detail-error { color: #d10000; }

        @media (max-width: 767px) {
            .map-outlet-detail-panel {
                top: auto;
                right: 0;
                bottom: 0;
                left: 0;
                width: 100%;
                max-height: 64dvh;
                border-right: 0;
                border-bottom: 0;
                border-left: 0;
                border-radius: 20px 20px 0 0;
            }

            .map-detail-header { padding: 14px 16px; }
            .map-detail-body { padding: 12px; }
        }

        .outlet-marker-icon {
            background: transparent;
            border: 0;
        }

        .outlet-marker-pin {
            position: relative;
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border: 2px solid #fff;
            border-radius: 50% 50% 50% 8px;
            background: #e30613;
            color: #fff;
            font-size: 13px;
            box-shadow: 0 2px 7px rgba(0, 0, 0, 0.35);
            transform: rotate(-45deg);
        }

        .outlet-marker-pin i {
            transform: rotate(45deg);
        }

        .outlet-marker-selected .outlet-marker-pin {
            width: 38px;
            height: 38px;
            background: #ffc107;
            color: #4b3900;
            font-size: 16px;
            border-width: 3px;
            box-shadow: 0 3px 12px rgba(255, 193, 7, 0.65);
        }
        .outlet-marker-visited .outlet-marker-pin {
            background: #16a34a;
            box-shadow: 0 2px 8px rgba(22, 163, 74, 0.48);
        }
        .outlet-marker-selected.outlet-marker-visited .outlet-marker-pin {
            background: #16a34a;
            color: #fff;
            box-shadow: 0 3px 12px rgba(22, 163, 74, 0.62);
        }
        .outlet-marker-photo {
            width: 36px; height: 36px; overflow: hidden; border: 3px solid #fff; border-radius: 11px;
            background: #fff; box-shadow: 0 3px 10px rgba(15,23,42,.38); position: relative;
        }
        .outlet-marker-photo img { width: 100%; height: 100%; display: block; object-fit: cover; }
        .outlet-marker-photo::after {
            content: "✓"; position: absolute; right: -1px; bottom: -1px; width: 14px; height: 14px;
            display: grid; place-items: center; border: 2px solid #fff; border-radius: 50%;
            background: #16a34a; color: #fff; font-size: 8px; font-weight: 900;
        }
        .outlet-marker-selected .outlet-marker-photo { width: 46px; height: 46px; border-color: #fbbf24; box-shadow: 0 4px 16px rgba(245,158,11,.6); }

        .map-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 4px 8px 12px;
        }

        .map-heading h3 {
            font-size: 18px;
            color: #202b3c;
        }

        .map-count {
            padding: 6px 11px;
            border-radius: 999px;
            background: #fff0f0;
            color: #d10000;
            font-size: 12px;
            font-weight: 800;
        }

        .map-fullscreen-button,
        .map-locate-button {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 8px;
            background: #fff;
            color: #202b3c;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.35);
        }

        .map-locate-button {
            color: #0d6efd;
        }

        .outlet-distance {
            margin-top: 7px;
            padding: 6px 8px;
            border-radius: 7px;
            background: #eef5ff;
            color: #0d6efd;
            font-size: 12px;
            font-weight: 800;
            text-align: center;
        }

        .outlet-metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5px;
            margin-top: 8px;
        }

        .outlet-metric {
            padding: 6px 4px;
            border-radius: 7px;
            background: #f6f7f9;
            color: #202b3c;
            text-align: center;
        }

        .outlet-metric small {
            display: block;
            margin-bottom: 2px;
            color: #78808c;
            font-size: 9px;
            font-weight: 800;
        }

        .outlet-metric strong {
            font-size: 13px;
        }

        .outlet-navigation-link {
            display: block;
            margin-top: 6px;
            padding: 7px 12px;
            border-radius: 6px;
            background: #28a745;
            color: #fff !important;
            font-size: 12px;
            font-weight: 800;
            text-align: center;
            text-decoration: none;
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
            body {
                background: #1f1d2b;
            }

            .header {
                padding: 10px 8px;
            }

            .container {
                max-width: calc(100vw - 10px);
                margin: 6px auto;
                padding: 0;
                padding-bottom: 72px;
            }

            .leader-dashboard {
                padding: 10px;
                border-radius: 18px;
                margin-bottom: 12px;
            }

            .leader-top,
            .leader-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .leader-title {
                font-size: 22px;
            }

            .leader-subtitle {
                font-size: 12px;
                line-height: 1.55;
            }

            .leader-top-actions {
                margin-bottom: 14px;
            }

            .leader-kpis {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .coverage-kpi {
                padding: 12px;
            }

            .coverage-gauges {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .coverage-gauge {
                width: 132px;
                height: 74px;
            }

            .territory-panel {
                border-radius: 18px;
                padding: 10px;
                margin-top: 18px;
            }

            .map-toolbar {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }

            .map-toggle {
                min-height: 38px;
                border-radius: 12px;
                gap: 6px;
                font-size: 11px;
            }

            .map-mode-note {
                min-height: 0;
                align-items: flex-start;
                padding: 9px 10px;
                font-size: 11px;
                line-height: 1.45;
                margin-bottom: 10px;
            }

            .map-thresholds {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 6px;
                margin-bottom: 10px;
            }

            .fb-share-toggle {
                width: 100%;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .threshold-field {
                min-width: 0;
                padding: 8px 6px;
                border-radius: 12px;
                display: grid;
                justify-items: center;
                gap: 6px;
            }

            .threshold-field input {
                width: 58px;
                height: 32px;
            }

            .threshold-field label {
                font-size: 9px;
            }

            .map-legend-row {
                grid-template-columns: 1fr;
                gap: 6px;
                margin-bottom: 10px;
            }

            .map-legend-item {
                min-height: 32px;
                padding: 7px 10px;
                font-size: 10px;
            }

            #leader-map {
                height: 430px;
                border-radius: 16px;
            }

            .insight-strip {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .territory-legend {
                grid-template-columns: 1fr;
            }

            .table-view-toolbar {
                align-items: stretch;
                flex-direction: column;
                padding: 10px;
            }

            .table-view-toggle {
                width: 100%;
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .table-view-btn {
                min-width: 0;
                min-height: 32px;
                font-size: 9px;
            }

            .tap-active-scroll {
                max-height: 62vh;
            }

            .tap-active-table table {
                min-width: 860px;
            }

            .tap-active-table th,
            .tap-active-table td {
                padding: 9px 8px;
                font-size: 9px;
            }

            .tap-active-table thead th:first-child,
            .tap-active-table td:first-child {
                width: 150px;
            }

            .mix-row {
                grid-template-columns: 72px 1fr 72px;
            }

            .leader-lock {
                grid-template-columns: 46px 1fr;
            }

            .leader-login-btn {
                grid-column: 1 / -1;
                width: 100%;
            }

            .leader-inline-login {
                grid-column: 1 / -1;
                grid-template-columns: 1fr;
                min-width: 0;
                width: 100%;
            }

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

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .modal-content {
            background: #fff;
            width: 90%;
            max-width: 500px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            animation: modalPop 0.3s ease-out;
        }

        @keyframes modalPop {
            from {
                transform: scale(0.8);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 15px 20px;
            background: linear-gradient(90deg, #d10000, #ff4d4d);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h3 {
            font-size: 16px;
        }

        .modal-close {
            cursor: pointer;
            font-size: 20px;
            opacity: 0.8;
            transition: 0.2s;
        }

        .modal-close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }

        /* CUSTOM BUTTONS */
        .btn-detail {
            padding: 4px 10px;
            background: #d10000;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 11px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
        }

        .btn-detail:hover {
            background: #a80000;
            transform: scale(1.05);
        }

        /* MONITA ENTERPRISE SHELL */
        :root {
            --monita-red: #0f766e;
            --monita-ink: #101828;
            --monita-muted: #667085;
            --monita-line: #e4e7ec;
            --monita-surface: #ffffff;
            --sidebar-width: 252px;
        }

        body { background: #f4f6f8; }
        .app-shell { min-height: 100vh; width: 100%; }
        .enterprise-sidebar {
            position: fixed; inset: 0 auto 0 0; z-index: 1100;
            width: var(--sidebar-width); padding: 24px 16px;
            background: #102a33; color: #fff;
            display: flex; flex-direction: column;
            border-right: 1px solid rgba(255,255,255,.08);
            transition: transform .22s ease;
        }
        .sidebar-brand { display: flex; align-items: center; gap: 12px; padding: 0 8px 26px; }
        .sidebar-logo {
            width: 42px; height: 42px; border-radius: 13px; display: grid; place-items: center;
            background: var(--monita-red); box-shadow: 0 9px 24px rgba(215,25,32,.3);
        }
        .sidebar-brand strong { display: block; font-size: 17px; letter-spacing: -.3px; }
        .sidebar-brand small { display: block; margin-top: 3px; color: #98a2b3; font-size: 10px; letter-spacing: 1.2px; }
        .sidebar-label { padding: 0 12px 9px; color: #667085; font-size: 10px; font-weight: 800; letter-spacing: 1.25px; }
        .sidebar-nav { display: grid; gap: 5px; }
        .sidebar-link {
            width: 100%; min-height: 46px; padding: 0 13px; border: 0; border-radius: 11px;
            display: flex; align-items: center; gap: 12px; cursor: pointer;
            background: transparent; color: #98a2b3; font-size: 13px; font-weight: 700; text-align: left;
        }
        .sidebar-link i { width: 20px; text-align: center; font-size: 15px; }
        .sidebar-link:hover { background: rgba(255,255,255,.06); color: #fff; }
        .sidebar-link.active { background: var(--monita-red); color: #fff; box-shadow: 0 8px 20px rgba(215,25,32,.22); }
        .sidebar-foot { margin-top: auto; padding: 14px 8px 2px; border-top: 1px solid rgba(255,255,255,.09); }
        .sidebar-logout { display:flex; align-items:center; gap:10px; margin-top:12px; padding:11px 14px; border-radius:10px; color:#fff; text-decoration:none; font-weight:700; font-size:13px; background:rgba(255,255,255,.08); }
        .sidebar-logout:hover { background:rgba(255,255,255,.16); }
        .sidebar-close { display:none; margin-left:auto; width:34px; height:34px; border:0; border-radius:9px; background:rgba(255,255,255,.12); color:#fff; cursor:pointer; }
        .sidebar-user { display: flex; align-items: center; gap: 10px; }
        .sidebar-avatar { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: #344054; }
        .sidebar-user strong { display: block; font-size: 12px; }
        .sidebar-user small { color: #667085; font-size: 10px; }
        .app-main { width: calc(100% - var(--sidebar-width)); min-width: 0; min-height: 100vh; margin-left: var(--sidebar-width); transition: width .22s ease, margin-left .22s ease; }
        .app-main { overflow-x: hidden; }
        .app-shell.sidebar-collapsed .enterprise-sidebar { transform: translateX(-100%); }
        .app-shell.sidebar-collapsed .app-main { width: 100%; margin-left: 0; }
        .header {
            position: sticky; top: 0; z-index: 1000; min-height: 72px; padding: 0 28px;
            justify-content: space-between; background: rgba(255,255,255,.94); color: var(--monita-ink);
            border-bottom: 1px solid var(--monita-line); box-shadow: none; backdrop-filter: blur(14px);
        }
        .header-copy h1 { text-align: left; font-size: 17px; letter-spacing: -.35px; }
        .header-copy p { margin-top: 4px; color: var(--monita-muted); font-size: 11px; }
        .header-actions { display: flex; align-items: center; gap: 10px; }
        .header-status { display: inline-flex; align-items: center; gap: 7px; padding: 8px 11px; border: 1px solid var(--monita-line); border-radius: 10px; color: #475467; font-size: 11px; font-weight: 700; background: #fff; }
        .status-dot { width: 7px; height: 7px; border-radius: 50%; background: #12b76a; box-shadow: 0 0 0 3px #d1fadf; }
        .mobile-menu { display: grid; place-items: center; width: 38px; height: 38px; border: 1px solid var(--monita-line); border-radius: 10px; background: #fff; cursor: pointer; }
        .container { width: 100%; max-width: 1600px; min-width: 0; margin: 0 auto; padding: 26px 28px 90px; }
        .dashboard-menu { grid-template-columns: repeat(2, max-content); justify-content: start; box-shadow: none; border: 1px solid var(--monita-line); width: max-content; }
        .dashboard-menu-button.active { background: #101828; box-shadow: none; }

        .module-page { display: grid; grid-template-columns: minmax(0, 1fr); min-width: 0; gap: 18px; }
        .module-hero {
            display: flex; justify-content: space-between; gap: 20px; align-items: flex-end;
            padding: 26px; border: 1px solid var(--monita-line); border-radius: 18px; background: #fff;
        }
        .module-hero > * { min-width: 0; }
        .module-kicker { color: var(--monita-red); font-size: 10px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; }
        .module-hero h2 { margin-top: 8px; color: var(--monita-ink); font-size: 26px; letter-spacing: -.7px; }
        .module-hero p { max-width: 670px; margin-top: 9px; color: var(--monita-muted); font-size: 13px; line-height: 1.65; }
        .coming-badge { white-space: nowrap; padding: 9px 12px; border-radius: 9px; background: #fff4f4; color: var(--monita-red); font-size: 11px; font-weight: 800; }
        .analytics-toolbar { display: grid; grid-template-columns: 1.2fr repeat(3, 1fr); gap: 12px; padding: 16px; border: 1px solid var(--monita-line); border-radius: 16px; background: #fff; }
        .filter-field label { display: block; margin-bottom: 7px; color: #667085; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .7px; }
        .filter-field select { width: 100%; height: 42px; padding: 0 12px; border: 1px solid #d0d5dd; border-radius: 9px; background: #fff; color: #344054; font: inherit; font-size: 12px; font-weight: 700; }
        .module-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .metric-card { padding: 18px; border: 1px solid var(--monita-line); border-radius: 15px; background: #fff; }
        .metric-card small { color: #667085; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .7px; }
        .metric-card strong { display: block; margin-top: 12px; color: #101828; font-size: 25px; letter-spacing: -.6px; }
        .metric-card span { display: block; margin-top: 7px; color: #98a2b3; font-size: 10px; }
        .placeholder-chart { min-height: 290px; padding: 20px; border: 1px solid var(--monita-line); border-radius: 16px; background: #fff; }
        .placeholder-chart-head { display: flex; justify-content: space-between; align-items: center; }
        .placeholder-chart h3 { font-size: 14px; color: #101828; }
        .period-pills { display: flex; gap: 5px; }
        .period-pills span { padding: 6px 9px; border-radius: 7px; background: #f2f4f7; color: #667085; font-size: 9px; font-weight: 800; }
        .period-pills span.active { background: #101828; color: #fff; }
        .chart-empty { min-height: 210px; display: grid; place-items: center; text-align: center; color: #98a2b3; }
        .chart-empty i { display: block; margin-bottom: 12px; color: #d0d5dd; font-size: 28px; }
        .chart-empty strong { display: block; color: #667085; font-size: 12px; }
        .chart-empty p { margin-top: 6px; font-size: 10px; }
        .pv-monitor-card {
            border: 1px solid #d9dee8;
            border-radius: 16px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }
        .pv-monitor-toolbar {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            padding: 14px 16px;
            border-bottom: 1px solid #e6eaf1;
            background: linear-gradient(180deg, #fff, #f8fafc);
        }
        .pv-monitor-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            min-width: 0;
        }
        .pv-tabs {
            display: flex;
            gap: 4px;
            padding: 4px;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            background: #f1f5f9;
        }
        .pv-tab {
            min-height: 32px;
            padding: 0 14px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #64748b;
            font-size: 10px;
            font-weight: 850;
            cursor: pointer;
        }
        .pv-tab.active {
            background: #0f172a;
            color: #fff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .18);
        }
        .pv-category-tabs {
            background: #fff;
        }
        .pv-category-tab.active { background: #101828; color: #fff; }
        .pv-period-note {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            text-align: right;
            line-height: 1.55;
        }
        .visit-detail-hero { align-items: flex-end; flex-wrap: wrap; }
        .visit-detail-hero > div:first-child { flex: 1 1 520px; }
        .visit-date-filter { flex: 0 1 390px; min-width: 280px; max-width: 100%; }
        .visit-date-filter label { display: block; margin-bottom: 7px; color: #64748b; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .visit-date-filter > div { display: flex; gap: 8px; }
        .visit-date-filter input, .visit-date-filter button { min-height: 44px; border-radius: 10px; border: 1px solid #d8dee8; font: inherit; font-weight: 700; }
        .visit-date-filter input { flex: 1 1 170px; min-width: 0; padding: 0 13px; background: #fff; color: #111827; color-scheme: light; }
        .visit-date-filter button { flex: 0 0 auto; padding: 0 16px; border-color: #c9001d; background: #c9001d; color: #fff; cursor: pointer; white-space: nowrap; }
        .visit-summary-strip { display: flex; flex-wrap: wrap; gap: 0; margin: 18px 0; padding: 4px 0; border-top: 1px solid var(--monita-line); border-bottom: 1px solid var(--monita-line); background: #fff; }
        .visit-summary-strip > div { flex: 1 1 120px; min-width: 120px; padding: 12px 15px; border-right: 1px solid var(--monita-line); }
        .visit-summary-strip > div:last-child { border-right: 0; }
        .visit-summary-strip small { display: block; color: #64748b; font-size: 9px; font-weight: 800; letter-spacing: .06em; }
        .visit-summary-strip strong { display: block; margin-top: 7px; color: #111827; font-size: 24px; }
        .visit-detail-card { min-width: 0; max-width: 100%; overflow: hidden; border: 1px solid var(--monita-line); border-radius: 16px; background: #fff; box-shadow: 0 10px 28px rgba(15,23,42,.05); }
        .visit-detail-table-wrap { width: 100%; max-width: 100%; overflow: auto; overscroll-behavior-inline: contain; }
        .visit-detail-table { width: 100%; min-width: 980px; border-collapse: collapse; }
        .visit-detail-table th { padding: 14px 16px; background: #f8fafc; color: #64748b; font-size: 10px; font-weight: 800; letter-spacing: .04em; text-align: left; text-transform: uppercase; white-space: nowrap; }
        .visit-detail-table th:nth-child(n+5), .visit-detail-table td:nth-child(n+5) { text-align: center; }
        .visit-detail-table th small { display: block; margin-top: 2px; color: #94a3b8; font-size: 8px; }
        .visit-detail-table td { padding: 15px 16px; border-top: 1px solid #edf0f4; color: #334155; font-size: 12px; }
        .visit-detail-table tbody tr:hover { background: #fffafa; }
        .visit-code { color: #b0001b; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 800; }
        .cluster-chip { display: inline-flex; padding: 5px 8px; border-radius: 999px; background: #fef2f2; color: #b91c1c; font-size: 9px; font-weight: 800; }
        .metric-number { display: inline-grid; min-width: 42px; min-height: 30px; place-items: center; border-radius: 9px; font-weight: 900; }
        .metric-number.actual { background: #eff6ff; color: #1d4ed8; }
        button.metric-number { border: 0; cursor: pointer; }
        button.metric-number:hover { box-shadow: 0 0 0 3px #dbeafe; }
        .metric-number.effective { background: #ecfdf5; color: #047857; }
        .metric-number.non-pjp { background: #f5f3ff; color: #6d28d9; }
        .metric-number.non-buying { background: #fef2f2; color: #b91c1c; }
        .metric-number.pv { background: #fff7ed; color: #c2410c; }
        .metric-number.sa { background: #fdf2f8; color: #be185d; }
        .visit-percent { display: grid; gap: 5px; min-width: 105px; }
        .visit-percent-value { color: #1d4ed8; font-weight: 900; }
        .visit-progress { height: 6px; overflow: hidden; border-radius: 999px; background: #e5e7eb; }
        .visit-progress > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #2563eb, #60a5fa); }
        .visit-table-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 18px 0 10px; }
        .visit-table-tools input, .visit-table-tools select { min-height: 38px; padding: 0 11px; border: 1px solid #d8dee8; border-radius: 9px; background: #fff; color: #334155; font: inherit; font-size: 12px; }
        .visit-table-tools input { flex: 1 1 220px; }
        .visit-table-tools select { flex: 0 1 170px; }
        .visit-summary-table th[data-sort] { cursor: pointer; user-select: none; }
        .visit-summary-table th[data-sort]::after { content: ' ↕'; color: #94a3b8; font-size: 11px; }
        .visit-summary-table th[data-sort].sort-active { color: #c8102e; background: #fff7f8; }
        .visit-summary-table th[data-sort].sort-active[data-direction="asc"]::after { content: ' ↑'; color: #c8102e; }
        .visit-summary-table th[data-sort].sort-active[data-direction="desc"]::after { content: ' ↓'; color: #c8102e; }
        .visit-filter-empty { display: none; }
        .visit-empty { padding: 54px !important; text-align: center !important; color: #94a3b8 !important; }
        .visit-empty i, .visit-empty strong, .visit-empty span { display: block; }
        .visit-empty i { margin-bottom: 10px; font-size: 24px; }
        .visit-empty strong { color: #475569; font-size: 15px; }
        .visit-empty span { margin-top: 4px; }
        .visit-module-tabs { display: flex; width: 100%; min-width: 0; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; padding: 5px; border: 1px solid var(--monita-line); border-radius: 12px; background: #fff; }
        .visit-module-tab { min-height: 38px; padding: 0 15px; border: 0; border-radius: 9px; background: transparent; color: #64748b; font-weight: 800; cursor: pointer; }
        .visit-module-tab.active { background: #c9001d; color: #fff; box-shadow: 0 7px 16px rgba(201,0,29,.18); }
        .notice { margin-bottom: 14px; padding: 12px 14px; border: 1px solid #bbf7d0; border-radius: 11px; background: #f0fdf4; color: #166534; font-size: 12px; font-weight: 700; }
        .visit-subpanel[hidden] { display: none; }
        .visit-photo-thumb { width: 68px; height: 50px; object-fit: cover; border-radius: 9px; box-shadow: 0 0 0 1px #e2e8f0; }
        .visit-action-buttons { display: flex; align-items: center; gap: 7px; }
        .visit-approve-btn, .visit-reject-btn { min-height: 34px; padding: 0 11px; border-radius: 9px; font-size: 10px; font-weight: 800; cursor: pointer; white-space: nowrap; }
        .visit-approve-btn { border: 0; background: #15935d; color: #fff; }
        .visit-reject-btn { border: 1px solid #fecaca; background: #fff; color: #b91c1c; }
        .visit-modal[hidden] { display: none; }
        .visit-modal { position: fixed; inset: 0; z-index: 1200; display: grid; place-items: center; padding: 24px; background: rgba(15,23,42,.62); }
        .visit-modal-card { width: min(980px,100%); max-height: calc(100vh - 48px); overflow: hidden; border-radius: 18px; background: #fff; box-shadow: 0 30px 80px rgba(15,23,42,.3); }
        .visit-modal-head { padding: 17px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; }
        .visit-modal-head h3 { margin: 0; font-size: 17px; }
        .visit-modal-close { width: 36px; height: 36px; border: 0; border-radius: 10px; background: #f1f5f9; cursor: pointer; }
        .visit-modal-body { max-height: calc(100vh - 130px); overflow: auto; padding: 14px 20px 22px; }
        .visit-refresh-button { margin-left: auto; min-height: 40px; padding: 0 15px; border: 1px solid #fecaca; border-radius: 11px; display: inline-flex; align-items: center; gap: 8px; color: #b91c1c; background: #fff7f7; font-size: 12px; font-weight: 800; text-decoration: none; }
        .visit-refresh-button:hover { background: #fee2e2; color: #991b1b; }
        .visit-status-actions { display: flex; margin-left: auto; flex-wrap: wrap; gap: 7px; }
        .visit-status-actions .visit-refresh-button { margin-left: 0; }
        .visit-outlet-row { display: grid; grid-template-columns: 72px minmax(180px,1fr) 150px repeat(3,90px); gap: 12px; align-items: center; padding: 12px 0; border-bottom: 1px solid #edf0f4; }
        .visit-outlet-row strong,.visit-outlet-row small { display: block; }
        .visit-outlet-row small { margin-top: 3px; color: #64748b; }
        .visit-outlet-metric { text-align: center; }
        .visit-outlet-metric strong { font-size: 16px; }
        .visit-outlet-metric button { width: 100%; padding: 7px 5px; border: 1px solid #e2e8f0; border-radius: 9px; background: #f8fafc; color: #111827; cursor: pointer; font: inherit; font-weight: 900; }
        .visit-outlet-metric button:hover { border-color: #fca5a5; background: #fff7f7; }
        .visit-outlet-detail { grid-column: 1 / -1; display: none; padding: 12px 14px; border-radius: 10px; background: #f8fafc; color: #334155; }
        .visit-outlet-detail.active { display: block; }
        .visit-outlet-detail ul { margin: 0; padding-left: 18px; }
        .visit-outlet-detail li + li { margin-top: 5px; }
        .approval-visit-table th:nth-child(-n+4), .approval-visit-table td:nth-child(-n+4),
        .visit-summary-table th:nth-child(-n+4), .visit-summary-table td:nth-child(-n+4) { text-align: left !important; }

        .outlet-branding-photo { display: block; width: 100%; height: 112px; margin: 8px 0; overflow: hidden; border-radius: 10px; background: #eef2f7; }
        .outlet-branding-photo img { width: 100%; height: 100%; object-fit: cover; }
        .map-detail-branding { margin-bottom: 14px; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 13px; background: #f8fafc; }
        .map-detail-branding a { display: block; }
        .map-detail-branding img { display: block; width: 100%; max-height: 250px; object-fit: cover; }
        .map-detail-branding-copy { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; }
        .map-detail-branding-copy strong { display: block; color: #111827; font-size: 11px; }
        .map-detail-branding-copy small { color: #64748b; font-size: 9px; }
        .map-detail-branding-empty { display: grid; min-height: 100px; place-items: center; color: #94a3b8; font-size: 11px; text-align: center; }
        .pv-table-scroll {
            max-height: 640px;
            overflow: auto;
            background: #f8fafc;
            scrollbar-color: #94a3b8 #e2e8f0;
            scrollbar-width: thin;
        }
        .pv-table-scroll::-webkit-scrollbar { width: 10px; height: 10px; }
        .pv-table-scroll::-webkit-scrollbar-track { background: #e2e8f0; }
        .pv-table-scroll::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 999px; border: 2px solid #e2e8f0; }
        .pv-table {
            width: max-content;
            min-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
            color: #334155;
            font-size: 11px;
            font-variant-numeric: tabular-nums;
        }
        .pv-table th,
        .pv-table td {
            height: 36px;
            padding: 6px 9px;
            border-right: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            text-align: right;
            white-space: nowrap;
            vertical-align: middle;
        }
        .pv-table thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            height: 34px;
            background: #f8fafc;
            color: #475569;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .02em;
            text-align: center;
            text-transform: uppercase;
        }
        .pv-table thead tr:nth-child(2) th {
            top: 34px;
            height: 32px;
            background: #fff;
            color: #64748b;
            font-size: 9px;
        }
        .pv-table .pv-identity {
            position: sticky;
            left: 0;
            z-index: 4;
            width: 240px;
            min-width: 240px;
            max-width: 240px;
            background: #fff;
            text-align: left;
            box-shadow: 8px 0 16px rgba(15, 23, 42, .06);
        }
        .pv-table .pv-metric-col { width: 68px; min-width: 68px; max-width: 68px; }
        .pv-table thead .pv-identity {
            z-index: 8;
            background: #f8fafc;
        }
        .pv-table .pv-grand-group { background: #eef2ff; color: #3730a3; }
        .pv-table .pv-contribution-group { background: #f5f3ff; color: #5b21b6; }
        .pv-table thead tr:nth-child(2) .pv-grand-col { background: #f5f7ff; color: #4338ca; }
        .pv-table thead tr:nth-child(2) .pv-contribution-col { background: #faf5ff; color: #6d28d9; }
        .pv-table thead tr:first-child .pv-identity { border-bottom-color: transparent; }
        .pv-table thead tr:nth-child(2) .pv-header-spacer { background: #f8fafc; border-top: 0; }
        .pv-table tbody td {
            background: #fff;
            color: #334155;
            font-size: 12px;
            font-weight: 650;
        }
        .pv-table tbody .pv-grand-col {
            background: #f8fafc;
            color: #0f172a;
            font-weight: 850;
        }
        .pv-table tbody .pv-contribution-col {
            background: #fcfbff;
            color: #5b21b6;
            font-size: 11px;
            font-weight: 800;
        }
        .pv-table tbody tr:hover td { background: #f8fafc; }
        .pv-table tbody tr:hover .pv-identity { background: #f8fafc; }
        .pv-name {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #0f172a;
            font-size: 11px;
            font-weight: 850;
            line-height: 1.25;
        }
        .pv-sub {
            display: block;
            margin-top: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #94a3b8;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .pv-mom {
            display: inline-flex;
            min-width: 48px;
            height: 24px;
            align-items: center;
            justify-content: center;
            padding: 0 7px;
            border-radius: 7px;
            text-align: center;
            font-size: 11px;
            font-weight: 850;
        }
        .pv-mom.up { background: #dcfce7; color: #047857; }
        .pv-mom.down { background: #fee2e2; color: #b91c1c; }
        .pv-mom.flat { background: #f1f5f9; color: #64748b; }
        .pv-empty-row td { padding: 42px; color: #98a2b3; text-align: center; }
        .pv-sort {
            width: 100%;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            font-weight: inherit;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .pv-sort.left { justify-content: flex-start; }
        .pv-sort i { opacity: .42; font-size: 8px; }
        .pv-sort.active i { opacity: 1; color: #0f172a; }
        .pv-table tbody tr.pv-group-row td {
            height: 40px;
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 900;
            box-shadow: inset 0 1px 0 #cbd5e1;
        }
        .pv-table tbody tr.pv-group-row .pv-identity {
            background: #e8eef7 !important;
            color: #0f172a;
            border-left: 4px solid #0f172a;
        }
        .pv-table tbody tr.pv-group-row .pv-name {
            color: #0f172a;
            font-size: 12px;
            letter-spacing: .02em;
        }
        .pv-table tbody tr.pv-group-row .pv-sub {
            color: #64748b;
        }
        .pv-table tbody tr.pv-child-row .pv-name {
            position: relative;
            padding-left: 17px;
            color: #1e293b;
            font-weight: 800;
        }
        .pv-table tbody tr.pv-child-row .pv-name::before {
            content: "";
            position: absolute;
            left: 3px;
            top: 50%;
            width: 7px;
            height: 1px;
            background: #94a3b8;
        }
        .pv-cluster-footer td {
            height: 40px;
            background: #0f172a;
            color: #fff;
            font-weight: 900;
            border-color: rgba(255, 255, 255, .12);
        }
        .pv-cluster-footer .pv-identity {
            background: #0b1220 !important;
            box-shadow: 8px 0 16px rgba(15, 23, 42, .16);
        }
        .pv-cluster-footer .pv-name {
            color: #fff;
            font-size: 10px;
        }
        .pv-cluster-footer .pv-sub {
            color: #cbd5e1;
        }
        .pv-cluster-footer .pv-grand-col,
        .pv-cluster-footer .pv-contribution-col {
            background: #111827;
            color: #fff;
        }

        /* SURYA PERFORMANCE HUB THEME */
        :root {
            --monita-red: #d10b20;
            --monita-ink: #111827;
            --monita-muted: #64748b;
            --monita-line: #e2e8f0;
            --monita-soft: #f1f5f9;
            --monita-surface: #ffffff;
            --sidebar-width: 236px;
        }

        body { background: #edf2f7; color: var(--monita-ink); }
        .enterprise-sidebar {
            padding: 18px 12px;
            background: linear-gradient(180deg, #3a000a 0%, #970017 52%, #d10b20 100%);
            color: #fff;
            border-right: 0;
        }
        .sidebar-brand { padding: 0 8px 22px; }
        .sidebar-logo {
            width: 36px; height: 36px; border-radius: 9px;
            background: transparent; color: #fff; box-shadow: none; overflow: hidden;
        }
        .sidebar-logo img { width: 100%; height: 100%; object-fit: cover; }
        .sidebar-brand strong { font-size: 15px; }
        .sidebar-brand small, .sidebar-user small { color: rgba(255,255,255,.62); }
        .sidebar-label { color: rgba(255,255,255,.5); font-size: 9px; }
        .sidebar-nav { gap: 3px; }
        .sidebar-link {
            min-height: 40px; border-radius: 8px; color: rgba(255,255,255,.78);
            font-size: 12px; font-weight: 600; text-decoration: none;
        }
        .sidebar-link:hover { background: rgba(255,255,255,.12); color: #fff; }
        .sidebar-link.active {
            background: #fff; color: #b5001c; box-shadow: 0 8px 18px rgba(38,0,5,.18);
        }
        .sidebar-link.active i { color: #b5001c; }
        .sidebar-foot { border-top-color: rgba(255,255,255,.18); }
        .sidebar-avatar { background: rgba(255,255,255,.14); color: #fff; border-radius: 8px; }

        .header {
            min-height: 64px; padding: 0 24px; background: rgba(255,255,255,.94);
            border-bottom-color: var(--monita-line); backdrop-filter: blur(12px);
        }
        .header-copy h1 { font-size: 15px; font-weight: 650; letter-spacing: -.2px; }
        .header-copy p { color: #a1a1aa; font-size: 10px; }
        .header-status, .mobile-menu {
            min-height: 34px; border-radius: 8px; border-color: var(--monita-line);
            color: #52525b; box-shadow: none;
        }
        .container { max-width: 1540px; padding: 24px 28px 80px; }
        .dashboard-menu {
            padding: 3px; border-radius: 9px; background: #f4f4f5;
            border: 0; box-shadow: none;
        }
        .dashboard-menu-button { min-height: 36px; border-radius: 7px; font-size: 11px; }
        .dashboard-menu-button.active {
            background: #fff; color: #18181b;
            box-shadow: 0 1px 2px rgba(0,0,0,.08), 0 0 0 1px rgba(24,24,27,.04);
        }

        .leader-dashboard {
            margin-top: 18px; margin-bottom: 18px; padding: 0;
            background: transparent; color: var(--monita-ink);
            border-radius: 0; box-shadow: none; overflow: visible;
        }
        .leader-dashboard::before { display: none; }
        .leader-intro {
            padding: 22px; background: #fff; border: 1px solid var(--monita-line);
            border-radius: 14px;
        }
        .leader-eyebrow { color: #71717a; font-size: 9px; letter-spacing: 1px; margin-bottom: 8px; }
        .leader-title { max-width: 860px; font-size: clamp(22px, 2vw, 28px); letter-spacing: -.7px; }
        .leader-subtitle { color: #71717a; font-size: 12px; line-height: 1.65; }
        .leader-top-actions { margin-top: 14px; margin-bottom: 20px; }
        .leader-pill, .leader-logout {
            min-height: 32px; padding: 0 10px; border-radius: 7px; font-size: 10px;
        }
        .leader-pill { color: #52525b; background: #fafafa; border: 1px solid var(--monita-line); }
        .leader-logout { color: #52525b; background: #fff; border: 1px solid var(--monita-line); }
        .leader-kpis { gap: 12px; }
        .leader-kpi, .coverage-kpi {
            padding: 16px; background: #fafafa; border: 1px solid var(--monita-line);
            border-radius: 12px; backdrop-filter: none;
        }
        .leader-kpi small, .coverage-kpi small, .coverage-region-sub,
        .coverage-label, .coverage-meta { color: #71717a; }
        .coverage-region-title, .coverage-number { color: #18181b; }
        .coverage-gauge-card {
            border-radius: 10px; background: #fff; border-color: var(--monita-line);
        }
        .coverage-gauge-value { fill: #18181b; stroke: #fff; }

        .territory-panel, .leader-section, .insight-box, .module-hero,
        .analytics-toolbar, .metric-card, .placeholder-chart, .pv-monitor-card {
            border: 1px solid var(--monita-line); border-radius: 14px;
            background: #fff; box-shadow: none;
        }
        .territory-panel { margin-top: 16px; padding: 14px; }
        .leader-grid { gap: 14px; margin-top: 14px; }
        .leader-section { border-radius: 14px; }
        .insight-strip { gap: 10px; margin-top: 14px; }
        .insight-box { border-radius: 12px; box-shadow: none; }
        .territory-chip, .map-toggle, .threshold-field, .map-legend-item {
            background: #fafafa; border: 1px solid var(--monita-line); border-radius: 8px;
        }
        .map-toggle.active, .fb-share-btn.active {
            background: #18181b; border-color: #18181b; color: #fff; box-shadow: none;
        }
        .map-zoom-btn { border: 1px solid var(--monita-line); border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        #leader-map, #map { border-radius: 11px; border-color: var(--monita-line); }
        .input-wrapper input, .radius-select {
            border: 1px solid #d4d4d8; border-radius: 9px; box-shadow: none;
        }
        .input-wrapper input:focus, .radius-select:focus, .radius-select:hover {
            border-color: #a1a1aa; box-shadow: 0 0 0 3px rgba(24,24,27,.08); transform: none;
        }
        .btn-search, .btn-scan, .btn-detail { border-radius: 8px; box-shadow: none; }
        .module-page { gap: 14px; }
        .module-hero { padding: 22px; }
        .module-hero h2 { font-size: 24px; }
        .coming-badge { background: #f4f4f5; color: #52525b; }
        .filter-field select { border-radius: 8px; }
        .pv-table thead th, .pv-table thead .pv-identity,
        .pv-table thead tr:nth-child(2) .pv-header-spacer { background: #f8fafc; }
        .pv-table thead tr:nth-child(2) th { background: #fff; }
        .pv-table .pv-grand-group { background: #eef2ff; color: #3730a3; }

        @media (max-width: 960px) {
            .sidebar-close { display:grid; place-items:center; }
            .enterprise-sidebar { transform: translateX(-100%); transition: transform .25s ease; }
            .app-shell.sidebar-open .enterprise-sidebar { transform: translateX(0); }
            .app-main { width: 100%; margin-left: 0; }
            .mobile-menu { display: grid; place-items: center; }
            .header { padding: 0 18px; }
            .header-copy { flex: 1; margin-left: 12px; }
            .container { padding: 18px 16px 80px; }
            .analytics-toolbar, .module-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 620px) {
            .header-status { display: none; }
            .analytics-toolbar, .module-grid { grid-template-columns: 1fr; }
            .module-hero { align-items: flex-start; flex-direction: column; }
            .dashboard-menu { width: 100%; grid-template-columns: repeat(2, 1fr); }
            .visit-date-filter { width: 100%; min-width: 0; }
            .visit-summary-strip { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .visit-outlet-row { grid-template-columns: 60px 1fr repeat(2,70px); }
            .visit-outlet-row > :nth-child(3), .visit-outlet-row > :nth-child(6) { grid-column: 2 / -1; text-align: left; }
        }
        @media (max-width: 900px) {
            .visit-detail-hero { align-items: stretch; flex-direction: column; }
            .visit-date-filter { width: 100%; min-width: 0; }
            .visit-summary-strip { grid-template-columns: repeat(2, minmax(0,1fr)); }
        }
        @media (max-width: 520px) {
            .visit-date-filter > div { flex-wrap: wrap; }
            .visit-date-filter button { width: 100%; justify-content: center; }
            .visit-refresh-button { width: 100%; margin-left: 0; justify-content: center; }
            .visit-status-actions { width: 100%; margin-left: 0; }
        }
    </style>
</head>

<body>
    <div class="app-shell" id="app-shell">
    @include('surya.partials.sidebar', ['activePage' => 'dashboard'])
    <div class="app-main">
    <header class="header">
        <button class="mobile-menu" id="sidebar-toggle" type="button" aria-label="Tampilkan atau sembunyikan menu"><i class="fas fa-bars"></i></button>
        <div class="header-copy"><h1>Monitoring & Performance</h1><p>Cluster Dumai, Bengkalis & Rokan Hilir</p></div>
        <div class="header-actions"><div class="header-status"><span class="status-dot"></span> Data terhubung</div></div>
    </header>

    <main class="container">
        <?php
            $monitoringUpdateDate = $dashboard['monitoringUpdateDate'] ?? null;
            $clusterCoverage = $dashboard['clusterCoverage']['groups'] ?? [];
            $summary = $dashboard['summary'] ?? [];
        ?>
        @php
            $summary = $dashboard['summary'] ?? [];
            $areas = $dashboard['areas'] ?? [];
            $riskTaps = $dashboard['riskTaps'] ?? [];
            $growthTaps = $dashboard['growthTaps'] ?? [];
            $leaderActions = $dashboard['leaderActions'] ?? [];
            $clusterCoverage = $dashboard['clusterCoverage']['groups'] ?? [];
            $monitoringUpdateDate = $dashboard['monitoringUpdateDate'] ?? null;
            $visitDetails = $dashboard['visitDetails'] ?? [];
            $approvalVisits = $dashboard['approvalVisits'] ?? [];
            $emptyMonitoring = [
                'categories' => [],
                'categoryValidities' => [],
                'validityHeaders' => [],
                'validities' => [],
                'rows' => [],
                'clusterSummary' => [],
                'period' => [],
            ];
            $performanceModules = [
                'cvm' => [
                    'kicker' => 'CVM Monitoring',
                    'title' => 'CVM Performance',
                    'description' => 'Perbandingan performansi CVM, COMSAK, INSAK, SUPER SERU, dan HOT PROMO dari outlet_performance.',
                    'monitoring' => $dashboard['cvmMonitoring'] ?? $emptyMonitoring,
                    'empty' => 'Belum ada data CVM pada periode ini.',
                    'default_dimension' => 'sf',
                    'show_dimension_tabs' => false,
                    'show_category_tabs' => false,
                    'show_contribution' => false,
                ],
                'pv' => [
                    'kicker' => 'PV Monitoring',
                    'title' => 'Physical Voucher Performance',
                    'description' => 'Perbandingan performansi voucher dari outlet_performance berdasarkan validity, dengan pencapaian MTD terhadap periode M-1.',
                    'monitoring' => $dashboard['pvMonitoring'] ?? $emptyMonitoring,
                    'empty' => 'Belum ada data PV pada periode ini.',
                    'default_dimension' => 'tap',
                    'show_dimension_tabs' => true,
                    'show_category_tabs' => true,
                    'show_contribution' => true,
                ],
                'sa' => [
                    'kicker' => 'SA Monitoring',
                    'title' => 'Starterpack Performance',
                    'description' => 'Perbandingan performansi perdana SA dan BYU dari outlet_performance berdasarkan pencapaian MTD terhadap periode M-1.',
                    'monitoring' => $dashboard['saMonitoring'] ?? $emptyMonitoring,
                    'empty' => 'Belum ada data SA pada periode ini.',
                    'default_dimension' => 'tap',
                    'show_dimension_tabs' => true,
                    'show_category_tabs' => false,
                    'show_contribution' => false,
                ],
            ];
            $coverageGroupKeys = ['dumai_bengkalis', 'rokan_hilir'];
            $coverageMetrics = [
                ['key' => 'cvm', 'title' => 'CVM', 'color' => '#0f9d58'],
                ['key' => 'pv', 'title' => 'PV', 'color' => '#7c3aed'],
                ['key' => 'sa', 'title' => 'SA', 'color' => '#0b5cab'],
            ];
            $fmt = fn($value) => number_format((float) ($value ?? 0), 0, ',', '.');
            $pct = fn($value) => number_format((float) ($value ?? 0), 1, ',', '.') . '%';
            $share = fn($value, $pjp) => ((float) ($pjp ?? 0)) > 0
                ? number_format((((float) ($value ?? 0)) / ((float) $pjp)) * 100, 1, ',', '.') . '%'
                : '0,0%';
            $shareRaw = fn($value, $pjp) => ((float) ($pjp ?? 0)) > 0
                ? min(100, max(0, (((float) ($value ?? 0)) / ((float) $pjp)) * 100))
                : 0;
            $mixTotal = max(
                1,
                (float) (($summary['st_sa'] ?? 0) + ($summary['st_pv'] ?? 0) + ($summary['trx_cvm'] ?? 0)),
            );
        @endphp

        @if ($showLeaderDashboard)
            <div class="dashboard-panel" id="dashboard-panel-leader">
            <section class="leader-dashboard">
                <div class="leader-top">
                    <div class="leader-intro">
                        <div class="leader-eyebrow">Leader command center</div>
                        <h2 class="leader-title">Analisa Outlet Cluster Dumai Bengkalis dan Cluster Rokan Hilir</h2>
                        <p class="leader-subtitle">
                            Dashboard ini mengonsolidasikan penyebaran outlet aktif CVM, PV dan SA.
                            <br>Analisa disajikan untuk mendukung prioritas kunjungan, evaluasi produktivitas wilayah,
                            dan
                            pengambilan keputusan leader secara terukur.
                        </p>
                        <div class="leader-kpis">
                            @foreach ($clusterCoverage as $groupIndex => $group)
                                <div class="leader-kpi coverage-kpi">
                                    <small>Coverage PJP</small>
                                    <div class="coverage-region-title">{{ $group['label'] }}</div>
                                    <div class="coverage-region-sub">{{ $fmt($group['pjp'] ?? 0) }} outlet PJP</div>
                                    <div class="coverage-gauges">
                                        @foreach ($coverageMetrics as $metric)
                                            @php
                                                $metricValue = $group[$metric['key']] ?? 0;
                                                $metricShare = $shareRaw($metricValue, $group['pjp'] ?? 0);
                                                $needleRadians = pi() - ($metricShare / 100) * pi();
                                                $needleX = 100 + cos($needleRadians) * 56;
                                                $needleY = 90 - sin($needleRadians) * 56;
                                            @endphp
                                            <div class="coverage-gauge-card" data-coverage-row
                                                data-coverage-group="{{ $coverageGroupKeys[$groupIndex] ?? $groupIndex }}"
                                                data-coverage-metric="{{ $metric['key'] }}">
                                                <div class="coverage-label">{{ $metric['title'] }}</div>
                                                <svg class="coverage-gauge" viewBox="0 0 200 112"
                                                    style="--gauge-color: {{ $metric['color'] }};" role="img"
                                                    aria-label="{{ $metric['title'] }} {{ $share($metricValue, $group['pjp'] ?? 0) }}">
                                                    <path class="coverage-gauge-track coverage-gauge-low"
                                                        d="M 25 90 A 75 75 0 0 1 47 37"></path>
                                                    <path class="coverage-gauge-track coverage-gauge-mid"
                                                        d="M 47 37 A 75 75 0 0 1 100 15"></path>
                                                    <path class="coverage-gauge-track coverage-gauge-high"
                                                        d="M 100 15 A 75 75 0 0 1 153 37"></path>
                                                    <path class="coverage-gauge-track coverage-gauge-target"
                                                        d="M 153 37 A 75 75 0 0 1 175 90"></path>
                                                    <line class="coverage-gauge-needle" x1="100" y1="90"
                                                        x2="{{ number_format($needleX, 2, '.', '') }}"
                                                        y2="{{ number_format($needleY, 2, '.', '') }}"></line>
                                                    <circle class="coverage-gauge-pin" cx="100" cy="90"
                                                        r="8"></circle>
                                                    <text class="coverage-gauge-value" data-coverage-percent x="100"
                                                        y="76">{{ $share($metricValue, $group['pjp'] ?? 0) }}</text>
                                                </svg>
                                                <div class="coverage-number" data-coverage-value>
                                                    {{ $fmt($metricValue) }}</div>
                                                <div class="coverage-meta" data-coverage-meta>
                                                    dari {{ $fmt($group['pjp'] ?? 0) }} PJP
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="territory-panel">
                    <div class="map-toolbar" aria-label="Mode peta analisa">
                        <button type="button" class="map-toggle active" data-map-mode="trx_cvm">
                            <i class="fas fa-chart-simple"></i>CVM
                        </button>
                        <button type="button" class="map-toggle" data-map-mode="st_pv">
                            <i class="fas fa-bolt"></i>PV
                        </button>
                        <button type="button" class="map-toggle" data-map-mode="st_sa">
                            <i class="fas fa-sim-card"></i>SA
                        </button>
                        <button type="button" class="map-toggle" data-map-mode="competition">
                            <i class="fas fa-chart-pie"></i> FB Share
                        </button>
                    </div>
                    <div class="fb-share-toggle" id="fb-share-toggle" aria-label="Mode FB Share">
                        <button type="button" class="fb-share-btn active"
                            data-fb-share-view="dominant">Dominan</button>
                        <button type="button" class="fb-share-btn" data-fb-share-view="closest">Closest
                            competitor</button>
                    </div>
                    <div class="map-mode-note" id="map-mode-note">
                        <i class="fas fa-circle-info"></i>
                        <span>Mode TRX CVM memperbesar titik outlet dengan kontribusi CVM tinggi.</span>
                    </div>
                    <div class="map-thresholds" aria-label="Minimum transaksi outlet aktif">
                        <div class="threshold-field">
                            <label for="threshold-cvm">Min CVM</label>
                            <input id="threshold-cvm" data-threshold="trx_cvm" type="number" min="0"
                                step="1" value="11">
                        </div>
                        <div class="threshold-field">
                            <label for="threshold-pv">Min PV</label>
                            <input id="threshold-pv" data-threshold="st_pv" type="number" min="0"
                                step="1" value="40">
                        </div>
                        <div class="threshold-field">
                            <label for="threshold-sa">Min SA</label>
                            <input id="threshold-sa" data-threshold="st_sa" type="number" min="0"
                                step="1" value="5">
                        </div>
                    </div>
                    <div class="map-legend-row">
                        <div class="map-legend-item" id="legend-has-st">
                            <span class="legend-dot mom-up"></span>
                            <span id="legend-good-status">Capai min & MoM positif/stabil</span>
                        </div>
                        <div class="map-legend-item">
                            <span class="legend-dot mom-down"></span>
                            <span>Capai min tapi MoM minus</span>
                        </div>
                        <div class="map-legend-item">
                            <span class="legend-dot no-st"></span>
                            <span id="legend-below-threshold">Di bawah min</span>
                        </div>
                    </div>
                    <div class="map-frame">
                        <div id="leader-map"></div>
                        <div class="map-zoom-tools" aria-label="Kontrol zoom peta">
                            <button type="button" class="map-zoom-btn" data-map-zoom="in" title="Zoom in">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" class="map-zoom-btn" data-map-zoom="out" title="Zoom out">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="map-zoom-btn" data-map-zoom="fit" title="Reset area">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    <div class="tap-active-table">
                        <div class="table-view-toolbar">
                            <div class="table-view-title">
                                Monitoring outlet
                                aktif{{ $monitoringUpdateDate ? ' (' . $monitoringUpdateDate . ')' : '' }}
                            </div>
                            <div class="table-view-toggle" aria-label="Pilih agregasi tabel">
                                <button type="button" class="table-view-btn active"
                                    data-table-view="tap">TAP</button>
                                <button type="button" class="table-view-btn"
                                    data-table-view="kecamatan">Kecamatan</button>
                                <button type="button" class="table-view-btn" data-table-view="sales_force">Sales
                                    Force</button>
                            </div>
                        </div>
                        <div class="tap-active-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th id="active-table-dimension">TAP</th>
                                        <th colspan="3">Outlet Aktif CVM</th>
                                        <th colspan="3">Outlet Aktif PV</th>
                                        <th colspan="3">Outlet Aktif SA</th>
                                    </tr>
                                    <tr>
                                        <th class="active-table-sticky-spacer" aria-hidden="true"></th>
                                        <th>M</th>
                                        <th>M-1</th>
                                        <th>MoM</th>
                                        <th>M</th>
                                        <th>M-1</th>
                                        <th>MoM</th>
                                        <th>M</th>
                                        <th>M-1</th>
                                        <th>MoM</th>
                                    </tr>
                                </thead>
                                <tbody id="tap-active-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="insight-strip">
                    <div class="insight-box">
                        <small>TRX CVM</small>
                        <strong>{{ $fmt($summary['trx_cvm'] ?? 0) }}</strong>
                        <span>Layer hijau di peta memperlihatkan kantong CVM terkuat.</span>
                    </div>
                    <div class="insight-box">
                        <small>ST PV</small>
                        <strong>{{ $fmt($summary['st_pv'] ?? 0) }}</strong>
                        <span>{{ $pct((($summary['st_pv'] ?? 0) / $mixTotal) * 100) }} dari total aktivitas.</span>
                    </div>
                    <div class="insight-box">
                        <small>ST SA</small>
                        <strong>{{ $fmt($summary['st_sa'] ?? 0) }}</strong>
                        <span>{{ $pct((($summary['st_sa'] ?? 0) / $mixTotal) * 100) }} dari total aktivitas.</span>
                    </div>
                </div>

                <div class="leader-grid">
                    <div class="leader-section">
                        <h2>Call to action leader</h2>
                        @foreach ($leaderActions as $action)
                            <div class="leader-action {{ $action['tone'] }}">
                                <div class="leader-action-icon">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <div>
                                    <strong>{{ $action['title'] }}</strong>
                                    <p>{{ $action['body'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="leader-section">
                        <h2>Mix aktivitas bulan ini</h2>
                        @foreach ([['label' => 'CVM', 'value' => $summary['trx_cvm'] ?? 0], ['label' => 'ST PV', 'value' => $summary['st_pv'] ?? 0], ['label' => 'ST SA', 'value' => $summary['st_sa'] ?? 0]] as $mix)
                            <div class="mix-row">
                                <div class="mix-label">{{ $mix['label'] }}</div>
                                <div class="mix-track">
                                    <span
                                        style="width: {{ max(3, min(100, ((float) $mix['value'] / $mixTotal) * 100)) }}%;"></span>
                                </div>
                                <div class="mix-value">{{ $fmt($mix['value']) }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="leader-section">
                        <h2>TAP perlu perhatian</h2>
                        @forelse($riskTaps as $tap)
                            <div class="tap-row">
                                <div>
                                    <div class="tap-name">{{ $tap['tap'] }}</div>
                                    <div class="tap-bar">
                                        <span
                                            style="width: {{ max(4, min(100, (float) $tap['productivity'])) }}%;"></span>
                                    </div>
                                    <div class="tap-meta">{{ $fmt($tap['productive_outlets']) }} dari
                                        {{ $fmt($tap['outlets']) }} outlet produktif</div>
                                </div>
                                <div class="tap-score">
                                    {{ $pct($tap['mom']) }}
                                </div>
                            </div>
                        @empty
                            <p style="color:#657180; font-size:12px;">Data TAP belum tersedia.</p>
                        @endforelse
                    </div>

                    <div class="leader-section">
                        <h2>Momentum terbaik</h2>
                        @forelse($growthTaps as $tap)
                            <div class="tap-row">
                                <div>
                                    <div class="tap-name">{{ $tap['tap'] }}</div>
                                    <div class="tap-bar">
                                        <span
                                            style="width: {{ max(4, min(100, abs((float) $tap['mom']))) }}%; background: linear-gradient(90deg, #0f9d58, #6fdc8c);"></span>
                                    </div>
                                    <div class="tap-meta">{{ $fmt($tap['current']) }} aktivitas bulan ini</div>
                                </div>
                                <div class="tap-score" style="color:#0f9d58;">
                                    {{ $pct($tap['mom']) }}
                                </div>
                            </div>
                        @empty
                            <p style="color:#657180; font-size:12px;">Data momentum belum tersedia.</p>
                        @endforelse
                    </div>
                </div>
            </section>
            </div>

            <div class="dashboard-panel {{ request('panel') === 'visit-detail' ? '' : 'active' }}" id="dashboard-panel-outlet">

        <h2 class="search-panel-title">Cari dan bedah outlet</h2>
        <!-- Search Box -->
        <div class="search-box">
            <div class="input-wrapper">
                <input type="text" id="keyword" placeholder="Ketik ID atau Nama Outlet..."
                    onkeyup="handleKeyUp(event)" onkeydown="handleKeyDown(event)" onfocus="showHistory()">
                <ul id="suggestions" class="suggestions-box" style="display:none;"></ul>
                <div id="history" class="history-box"></div>
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

        <div class="map-heading">
            <h3><i class="fas fa-map-marked-alt"></i> Semua Outlet</h3>
            <span class="map-count" id="map-outlet-count">Memuat...</span>
        </div>
        <div id="map"></div>
        <div id="empty-state" class="empty-state" style="display:none;"></div>
            </div>

            @foreach($performanceModules as $monitoringKey => $module)
                @php
                    $monitoring = $module['monitoring'] ?? $emptyMonitoring;
                    $monitoringValidities = $monitoring['validities'] ?? [];
                    $monitoringValidityHeaders = $monitoring['validityHeaders'] ?? [];
                    $monitoringCategories = $monitoring['categories'] ?? [];
                    $defaultCategory = $monitoringCategories[0]['key'] ?? 'all';
                    $defaultDimension = $module['default_dimension'] ?? 'tap';
                    $showDimensionTabs = $module['show_dimension_tabs'] ?? true;
                    $showCategoryTabs = ($module['show_category_tabs'] ?? true) && count($monitoringCategories) > 1;
                    $showContribution = $module['show_contribution'] ?? true;
                    $monitoringColspan = 1 + (count($monitoringValidities) * 3) + 3 + ($showContribution ? count($monitoringValidities) : 0);
                @endphp
            <div class="dashboard-panel" id="dashboard-panel-{{ $monitoringKey }}">
                <section class="module-page">
                    <div class="module-hero">
                        <div><div class="module-kicker">{{ $module['kicker'] }}</div><h2>{{ $module['title'] }}</h2><p>{{ $module['description'] }}</p></div>
                        <div class="coming-badge"><i class="fas fa-circle-check"></i> Live dari outlet_performance</div>
                    </div>
                    <div class="pv-monitor-card" data-monitoring-key="{{ $monitoringKey }}" data-default-dimension="{{ $defaultDimension }}">
                        <div class="pv-monitor-toolbar">
                            <div class="pv-monitor-actions">
                                @if($showDimensionTabs)
                                    <div class="pv-tabs" aria-label="Level monitoring {{ strtoupper($monitoringKey) }}">
                                        <button type="button" class="pv-tab {{ $defaultDimension === 'tap' ? 'active' : '' }}" data-pv-dimension="tap">TAP</button>
                                        <button type="button" class="pv-tab {{ $defaultDimension === 'kecamatan' ? 'active' : '' }}" data-pv-dimension="kecamatan">KECAMATAN</button>
                                    </div>
                                @endif
                                @if($showCategoryTabs)
                                    <div class="pv-tabs pv-category-tabs" aria-label="Kategori {{ strtoupper($monitoringKey) }}">
                                        @foreach($monitoringCategories as $category)
                                            <button type="button" class="pv-tab pv-category-tab {{ $loop->first ? 'active' : '' }}" data-pv-category="{{ $category['key'] }}">{{ $category['label'] }}</button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="pv-period-note">M-1: {{ $monitoring['period']['m1'] ?? '-' }}<br>MTD: {{ $monitoring['period']['mtd'] ?? '-' }}</div>
                        </div>
                        <div class="pv-table-scroll">
                            <table class="pv-table">
                                <colgroup>
                                    <col style="width:240px">
                                    @foreach($monitoringValidities as $validity)
                                        <col data-pv-validity-col="{{ $validity }}" style="width:68px">
                                        <col data-pv-validity-col="{{ $validity }}" style="width:68px">
                                        <col data-pv-validity-col="{{ $validity }}" style="width:68px">
                                    @endforeach
                                    <col style="width:74px">
                                    <col style="width:74px">
                                    <col style="width:74px">
                                    @if($showContribution)
                                        @foreach($monitoringValidities as $validity)
                                            <col data-pv-contribution-col="{{ $validity }}" style="width:72px">
                                        @endforeach
                                    @endif
                                </colgroup>
                                <thead>
                                    <tr><th class="pv-identity"><button class="pv-sort left" type="button" data-pv-sort="name"><span data-pv-dimension-heading>TAP / Nama SF</span><i class="fas fa-sort"></i></button></th>
                                        @foreach($monitoringValidities as $validity)
                                            @php
                                                $header = $monitoringValidityHeaders[$validity]
                                                    ?? ['label' => $validity, 'short_label' => $validity];
                                            @endphp
                                            <th colspan="3" data-pv-validity-group="{{ $validity }}" data-label="{{ $header['label'] }}" data-short-label="{{ $header['short_label'] }}">VALIDITY {{ $header['label'] }}</th>
                                        @endforeach
                                        <th colspan="3" class="pv-grand-group">GRAND TOTAL</th>
                                        @if($showContribution)
                                            <th colspan="{{ count($monitoringValidities) }}" class="pv-contribution-group" data-pv-contribution-group>KONTRIBUSI TERHADAP TOTAL MTD</th>
                                        @endif
                                    </tr>
                                    <tr><th class="pv-identity pv-header-spacer" aria-hidden="true"></th>@foreach($monitoringValidities as $validity)<th class="pv-metric-col" data-pv-validity-cell="{{ $validity }}"><button class="pv-sort" type="button" data-pv-sort="metric" data-validity="{{ $validity }}" data-metric="m1">M-1<i class="fas fa-sort"></i></button></th><th class="pv-metric-col" data-pv-validity-cell="{{ $validity }}"><button class="pv-sort" type="button" data-pv-sort="metric" data-validity="{{ $validity }}" data-metric="mtd">MTD<i class="fas fa-sort"></i></button></th><th class="pv-metric-col" data-pv-validity-cell="{{ $validity }}"><button class="pv-sort" type="button" data-pv-sort="metric" data-validity="{{ $validity }}" data-metric="mom">%MoM<i class="fas fa-sort"></i></button></th>@endforeach
                                        <th class="pv-metric-col pv-grand-col"><button class="pv-sort" type="button" data-pv-sort="metric" data-validity="grand_total" data-metric="m1">M-1<i class="fas fa-sort"></i></button></th><th class="pv-metric-col pv-grand-col"><button class="pv-sort" type="button" data-pv-sort="metric" data-validity="grand_total" data-metric="mtd">MTD<i class="fas fa-sort"></i></button></th><th class="pv-metric-col pv-grand-col"><button class="pv-sort" type="button" data-pv-sort="metric" data-validity="grand_total" data-metric="mom">%MoM<i class="fas fa-sort"></i></button></th>
                                        @if($showContribution)
                                            @foreach($monitoringValidities as $validity)
                                                @php
                                                    $header = $monitoringValidityHeaders[$validity]
                                                        ?? ['label' => $validity, 'short_label' => $validity];
                                                @endphp
                                                <th class="pv-contribution-col" data-pv-contribution-header="{{ $validity }}"><button class="pv-sort" type="button" data-pv-sort="metric" data-validity="{{ $validity }}" data-metric="contribution">{{ $header['label'] }}<i class="fas fa-sort"></i></button></th>
                                            @endforeach
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($monitoring['rows'] as $rowIndex => $row)
                                        @php
                                            $isPvGroup = ($row['row_type'] ?? 'detail') === 'group';
                                        @endphp
                                        <tr class="{{ $isPvGroup ? 'pv-group-row' : (($row['dimension'] ?? '') === 'tap' ? 'pv-child-row' : '') }}" data-pv-row data-dimension="{{ $row['dimension'] }}" data-row-index="{{ $rowIndex }}" data-row-type="{{ $row['row_type'] ?? 'detail' }}" data-parent-tap="{{ $row['parent_tap'] ?? $row['tap'] ?? '' }}" data-cluster-order="{{ $row['cluster_order'] ?? 99 }}" @if($row['dimension'] !== $defaultDimension) hidden @endif>
                                            <td class="pv-identity">
                                                <span class="pv-name">{{ $row['name'] }}</span>
                                                @if($isPvGroup)
                                                    <span class="pv-sub">Total TAP</span>
                                                @endif
                                            </td>
                                            @foreach($monitoringValidities as $validity)
                                                @php
                                                    $metric = $row['values'][$defaultCategory][$validity]
                                                        ?? ['m1' => 0, 'mtd' => 0, 'mom' => 0];
                                                @endphp
                                                <td data-pv-metric="m1" data-validity="{{ $validity }}" data-pv-validity-cell="{{ $validity }}">{{ $fmt($metric['m1']) }}</td>
                                                <td data-pv-metric="mtd" data-validity="{{ $validity }}" data-pv-validity-cell="{{ $validity }}">{{ $fmt($metric['mtd']) }}</td>
                                                <td data-pv-metric="mom" data-validity="{{ $validity }}" data-pv-validity-cell="{{ $validity }}"><span class="pv-mom {{ $metric['mom'] > 0 ? 'up' : ($metric['mom'] < 0 ? 'down' : 'flat') }}">{{ number_format($metric['mom'], 1, ',', '.') }}%</span></td>
                                            @endforeach
                                            @php
                                                $grandMetric = $row['values'][$defaultCategory]['grand_total']
                                                    ?? ['m1' => 0, 'mtd' => 0, 'mom' => 0];
                                            @endphp
                                            <td class="pv-grand-col" data-pv-metric="m1" data-validity="grand_total">{{ $fmt($grandMetric['m1']) }}</td>
                                            <td class="pv-grand-col" data-pv-metric="mtd" data-validity="grand_total">{{ $fmt($grandMetric['mtd']) }}</td>
                                            <td class="pv-grand-col" data-pv-metric="mom" data-validity="grand_total"><span class="pv-mom {{ $grandMetric['mom'] > 0 ? 'up' : ($grandMetric['mom'] < 0 ? 'down' : 'flat') }}">{{ number_format($grandMetric['mom'], 1, ',', '.') }}%</span></td>
                                            @if($showContribution)
                                                @foreach($monitoringValidities as $validity)
                                                    @php
                                                        $contribution = $grandMetric['mtd'] > 0
                                                            ? (($row['values'][$defaultCategory][$validity]['mtd'] ?? 0) / $grandMetric['mtd']) * 100
                                                            : 0;
                                                    @endphp
                                                    <td class="pv-contribution-col" data-pv-metric="contribution" data-validity="{{ $validity }}" data-pv-contribution-cell="{{ $validity }}">{{ number_format($contribution, 1, ',', '.') }}%</td>
                                                @endforeach
                                            @endif
                                        </tr>
                                    @empty
                                        <tr class="pv-empty-row"><td colspan="{{ $monitoringColspan }}">{{ $module['empty'] }}</td></tr>
                                    @endforelse
                                </tbody>
                                @if(!empty($monitoring['clusterSummary']))
                                    <tfoot class="pv-cluster-footer">
                                    @foreach($monitoring['clusterSummary'] as $clusterIndex => $cluster)
                                        @php
                                            $clusterGrand = $cluster['values'][$defaultCategory]['grand_total']
                                                ?? ['m1' => 0, 'mtd' => 0, 'mom' => 0];
                                        @endphp
                                        <tr data-pv-cluster-row data-cluster-index="{{ $clusterIndex }}">
                                            <td class="pv-identity">
                                                <span class="pv-name">CLUSTER {{ strtoupper($cluster['label']) }}</span>
                                            </td>
                                            @foreach($monitoringValidities as $validity)
                                                @php
                                                    $clusterMetric = $cluster['values'][$defaultCategory][$validity]
                                                        ?? ['m1' => 0, 'mtd' => 0, 'mom' => 0];
                                                @endphp
                                                <td data-pv-footer-metric="m1" data-validity="{{ $validity }}" data-pv-validity-cell="{{ $validity }}">{{ $fmt($clusterMetric['m1'] ?? 0) }}</td>
                                                <td data-pv-footer-metric="mtd" data-validity="{{ $validity }}" data-pv-validity-cell="{{ $validity }}">{{ $fmt($clusterMetric['mtd'] ?? 0) }}</td>
                                                <td data-pv-footer-metric="mom" data-validity="{{ $validity }}" data-pv-validity-cell="{{ $validity }}"><span class="pv-mom {{ ($clusterMetric['mom'] ?? 0) > 0 ? 'up' : (($clusterMetric['mom'] ?? 0) < 0 ? 'down' : 'flat') }}">{{ number_format($clusterMetric['mom'] ?? 0, 1, ',', '.') }}%</span></td>
                                            @endforeach
                                            <td class="pv-grand-col" data-pv-footer-metric="m1" data-validity="grand_total">{{ $fmt($clusterGrand['m1'] ?? 0) }}</td>
                                            <td class="pv-grand-col" data-pv-footer-metric="mtd" data-validity="grand_total">{{ $fmt($clusterGrand['mtd'] ?? 0) }}</td>
                                            <td class="pv-grand-col" data-pv-footer-metric="mom" data-validity="grand_total"><span class="pv-mom {{ ($clusterGrand['mom'] ?? 0) > 0 ? 'up' : (($clusterGrand['mom'] ?? 0) < 0 ? 'down' : 'flat') }}">{{ number_format($clusterGrand['mom'] ?? 0, 1, ',', '.') }}%</span></td>
                                            @if($showContribution)
                                                @foreach($monitoringValidities as $validity)
                                                    @php
                                                        $clusterMetric = $cluster['values'][$defaultCategory][$validity] ?? ['mtd' => 0];
                                                        $clusterContribution = ($clusterGrand['mtd'] ?? 0) > 0
                                                            ? (($clusterMetric['mtd'] ?? 0) / ($clusterGrand['mtd'] ?? 1)) * 100
                                                            : 0;
                                                    @endphp
                                                    <td class="pv-contribution-col" data-pv-footer-metric="contribution" data-validity="{{ $validity }}" data-pv-contribution-cell="{{ $validity }}">{{ number_format($clusterContribution, 1, ',', '.') }}%</td>
                                                @endforeach
                                            @endif
                                        </tr>
                                    @endforeach
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </section>
            </div>
            @endforeach

            <div class="dashboard-panel {{ request('panel') === 'visit-detail' ? 'active' : '' }}" id="dashboard-panel-visit-detail">
                <section class="module-page visit-detail-page">
                    <div class="module-hero visit-detail-hero">
                        <div>
                            <div class="module-kicker">SURYA FIELD OPERATIONS</div>
                            <h2>Detail Visit Sales Force</h2>
                            <p>Rekap pelaksanaan PJP, rincian outlet, foto branding, serta approval visit dalam satu modul.</p>
                        </div>
                        <form class="visit-date-filter" method="GET" action="{{ route('surya.dashboard') }}#visit-detail">
                            <input type="hidden" name="panel" value="visit-detail">
                            <input type="hidden" name="visit_tab" id="visit-tab-filter" value="{{ request('visit_tab') === 'approval' ? 'approval' : 'summary' }}">
                            <label for="visit-date">Tanggal Visit</label>
                            <div><input id="visit-date" type="date" name="visit_date" value="{{ $visitDate }}"><button type="submit"><i class="fas fa-filter"></i> Tampilkan</button></div>
                        </form>
                    </div>
                    @if(session('success'))<div class="notice"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>@endif
                    @php
                        $activeVisitTab = request('visit_tab') === 'approval' ? 'approval' : 'summary';
                    @endphp
                    <div class="visit-module-tabs" role="tablist">
                        <button type="button" class="visit-module-tab {{ $activeVisitTab === 'summary' ? 'active' : '' }}" data-visit-subtab="summary"><i class="fas fa-chart-column"></i> Rekap Visit</button>
                        <button type="button" class="visit-module-tab {{ $activeVisitTab === 'approval' ? 'active' : '' }}" data-visit-subtab="approval"><i class="fas fa-camera"></i> Approval Visit</button>
                        <div class="visit-status-actions"><a class="visit-refresh-button" data-force-refresh href="{{ route('surya.dashboard', ['panel' => 'visit-detail', 'visit_date' => $visitDate, 'visit_tab' => $activeVisitTab]) }}#visit-detail"><i class="fas fa-rotate"></i> Muat Ulang Status</a><a class="visit-refresh-button" href="{{ route('surya.visits.export', ['visit_date' => $visitDate]) }}"><i class="fas fa-file-excel"></i> Download Excel</a></div>
                    </div>
                    <div class="visit-subpanel" data-visit-subpanel="summary" @if($activeVisitTab !== 'summary') hidden @endif>
                    <div class="visit-summary-strip">
                        @php
                            $visitRows = collect($visitDetails);
                            $totalPjp = (int) $visitRows->sum('pjp_total');
                            $totalActualCall = (int) $visitRows->sum('actual_call');
                            $visitPercentage = $totalPjp > 0 ? ($totalActualCall / $totalPjp) * 100 : 0;
                        @endphp
                        <div><small>SALES FORCE AKTIF</small><strong>{{ number_format(count($visitDetails), 0, ',', '.') }}</strong></div>
                        <div><small>TOTAL PJP</small><strong>{{ number_format($totalPjp, 0, ',', '.') }}</strong></div>
                        <div><small>ACTUAL CALL</small><strong>{{ number_format($totalActualCall, 0, ',', '.') }}</strong></div>
                        <div><small>% VISIT</small><strong>{{ number_format($visitPercentage, 1, ',', '.') }}%</strong></div>
                        <div><small>EFFECTIVE CALL</small><strong>{{ number_format(collect($visitDetails)->sum('effective_call'), 0, ',', '.') }}</strong></div>
                        <div><small>EXTRA CALL</small><strong>{{ number_format(collect($visitDetails)->sum('non_pjp'), 0, ',', '.') }}</strong></div>
                        <div><small>OUTLET NON BELANJA</small><strong>{{ number_format(collect($visitDetails)->sum('non_buying'), 0, ',', '.') }}</strong></div>
                        <div><small>TOTAL PV</small><strong>{{ number_format(collect($visitDetails)->sum('total_pv'), 0, ',', '.') }}</strong></div>
                        <div><small>TOTAL SA</small><strong>{{ number_format(collect($visitDetails)->sum('total_sa'), 0, ',', '.') }}</strong></div>
                    </div>
                    <div class="visit-table-tools" data-visit-tools>
                        <input type="search" data-visit-search placeholder="Cari SF, kode, TAP, atau cluster..." aria-label="Cari rekap visit">
                        <select data-visit-tap aria-label="Filter TAP"><option value="">Semua TAP</option>@foreach(collect($visitDetails)->pluck('tap')->filter()->unique()->sort() as $tap)<option value="{{ $tap }}">{{ $tap }}</option>@endforeach</select>
                        <select data-visit-cluster aria-label="Filter cluster"><option value="">Semua Cluster</option>@foreach(collect($visitDetails)->pluck('cluster')->filter()->unique()->sort() as $cluster)<option value="{{ $cluster }}">{{ $cluster }}</option>@endforeach</select>
                        <select data-visit-sort aria-label="Urutkan"><option value="">Urutkan default</option><option value="actual-desc">Actual Call terbesar</option><option value="visitPercent-desc">% Visit terbesar</option><option value="pjp-desc">Total PJP terbesar</option><option value="pv-desc">PV terbesar</option><option value="effective-desc">Effective Call terbesar</option><option value="nonBuying-desc">Non Belanja terbesar</option></select>
                    </div>
                    <div class="visit-detail-card">
                        <div class="visit-detail-table-wrap">
                            <table class="visit-detail-table visit-summary-table" style="min-width:1450px">
                                <thead><tr><th data-sort="sfcode">SF Code</th><th data-sort="name">Nama SF</th><th data-sort="tap">TAP</th><th data-sort="cluster">Cluster</th><th data-sort="pjp">Total PJP</th><th data-sort="actual">Actual Call</th><th data-sort="visitPercent">% Visit</th><th data-sort="effective">Effective Call</th><th data-sort="nonPjp">Extra Call</th><th data-sort="nonBuying">Non Belanja</th><th data-sort="pv">Total PV</th><th data-sort="sa">Total SA</th></tr></thead>
                                <tbody>
                                    @forelse($visitDetails as $row)
                                        <tr data-visit-summary-row data-sfcode="{{ $row['sfcode'] }}" data-name="{{ $row['name'] }}" data-tap="{{ $row['tap'] }}" data-cluster="{{ $row['cluster'] }}" data-pjp="{{ $row['pjp_total'] }}" data-actual="{{ $row['actual_call'] }}" data-visit-percent="{{ $row['visit_percentage'] }}" data-effective="{{ $row['effective_call'] }}" data-non-pjp="{{ $row['non_pjp'] }}" data-non-buying="{{ $row['non_buying'] }}" data-pv="{{ $row['total_pv'] }}" data-sa="{{ $row['total_sa'] }}">
                                            <td><span class="visit-code">{{ $row['sfcode'] }}</span></td>
                                            <td><strong>{{ $row['name'] }}</strong></td>
                                            <td>{{ $row['tap'] }}</td>
                                            <td><span class="cluster-chip">{{ $row['cluster'] }}</span></td>
                                            <td><span class="metric-number">{{ number_format($row['pjp_total'], 0, ',', '.') }}</span></td>
                                            <td><button type="button" class="metric-number actual" data-visit-row="{{ $loop->index }}" data-visit-scope="actual">{{ number_format($row['actual_call'], 0, ',', '.') }}</button></td>
                                            <td><div class="visit-percent"><span class="visit-percent-value">{{ number_format($row['visit_percentage'], 1, ',', '.') }}%</span><span class="visit-progress"><span style="width: {{ min(100, max(0, $row['visit_percentage'])) }}%"></span></span></div></td>
                                            <td><button type="button" class="metric-number effective" data-visit-row="{{ $loop->index }}" data-visit-scope="effective">{{ number_format($row['effective_call'], 0, ',', '.') }}</button></td>
                                            <td><button type="button" class="metric-number non-pjp" data-visit-row="{{ $loop->index }}" data-visit-scope="non-pjp">{{ number_format($row['non_pjp'], 0, ',', '.') }}</button></td>
                                            <td><button type="button" class="metric-number non-buying" data-visit-row="{{ $loop->index }}" data-visit-scope="non-buying">{{ number_format($row['non_buying'], 0, ',', '.') }}</button></td>
                                            <td><span class="metric-number pv">{{ number_format($row['total_pv'], 0, ',', '.') }}</span></td>
                                            <td><span class="metric-number sa">{{ number_format($row['total_sa'], 0, ',', '.') }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="12" class="visit-empty"><i class="fas fa-calendar-xmark"></i><strong>Belum ada aktivitas visit</strong><span>Tidak ada check-in Sales Force pada tanggal ini.</span></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    </div>
                    <div class="visit-subpanel" data-visit-subpanel="approval" @if($activeVisitTab !== 'approval') hidden @endif>
                        <div class="visit-detail-card"><div class="visit-detail-table-wrap"><table class="visit-detail-table approval-visit-table" style="min-width:1360px"><thead><tr><th>Outlet</th><th>TAP</th><th>Sales Force</th><th>Visit</th><th>Status</th><th>Jarak</th><th>Notes</th><th>Tampak Depan</th><th>Foto Etalase</th><th>Foto Nota Bon</th><th>Aksi</th></tr></thead><tbody>
                        @forelse($approvalVisits as $visit)
                            <tr>
                                <td><strong>{{ $visit->nama_outlet ?? $visit->id_outlet }}</strong><small style="display:block;color:#94a3b8">{{ $visit->id_outlet }}</small></td>
                                <td>{{ $visit->idtap }}</td><td>{{ $visit->namasf ?? $visit->idsf }}</td>
                                <td>{{ \Carbon\Carbon::parse($visit->visited_at)->format('d M Y H:i') }}</td>
                                <td><span class="cluster-chip">{{ strtoupper(str_replace('_',' ',$visit->gps_status)) }}</span></td>
                                <td>{{ $visit->distance_meters !== null ? number_format($visit->distance_meters,0).' m' : '—' }}</td><td>{{ $visit->notes ?: '—' }}</td>
                                <td>@if($visit->front_photo_url)<a href="{{ $visit->front_photo_url }}" target="_blank"><img class="visit-photo-thumb" src="{{ $visit->front_thumbnail_url }}" loading="lazy" alt="Tampak depan"></a>@else — @endif</td>
                                <td>@if($visit->inside_photo_url)<a href="{{ $visit->inside_photo_url }}" target="_blank"><img class="visit-photo-thumb" src="{{ $visit->inside_thumbnail_url }}" loading="lazy" alt="Foto etalase"></a>@else — @endif</td>
                                <td>@forelse($visit->receipt_photos ?? [] as $index => $photo)<a href="{{ $photo['url'] }}" target="_blank" title="Foto nota bon {{ $index + 1 }}"><img class="visit-photo-thumb" src="{{ $photo['thumbnail_url'] }}" loading="lazy" alt="Foto nota bon {{ $index + 1 }}"></a>@empty — @endforelse</td>
                                <td>@if($visit->gps_status === 'pending_admin')<div class="visit-action-buttons"><form method="POST" action="{{ route('surya.visits.approve',$visit->id) }}">@csrf<input type="hidden" name="return_to" value="visit-detail"><input type="hidden" name="visit_date" value="{{ $visitDate }}"><input type="hidden" name="visit_tab" value="approval"><button class="visit-approve-btn">Setujui Koordinat</button></form><form method="POST" action="{{ route('surya.visits.reject',$visit->id) }}" class="reject-photo-form">@csrf<input type="hidden" name="return_to" value="visit-detail"><input type="hidden" name="visit_date" value="{{ $visitDate }}"><input type="hidden" name="visit_tab" value="approval"><button type="submit" class="visit-reject-btn">Tolak Foto</button></form></div>@else — @endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="visit-empty"><strong>Belum ada visit pada tanggal ini</strong></td></tr>
                        @endforelse
                        </tbody></table></div></div>
                    </div>
                </section>
            </div>

            @foreach([
                'ngrs' => ['NGRS', 'Network Growth & Revenue Score', 'fa-tower-broadcast'],
                'digipos' => ['DIGIPOS PACKAGE', 'Digital Package Performance', 'fa-box-open'],
            ] as $moduleKey => $module)
                <div class="dashboard-panel" id="dashboard-panel-{{ $moduleKey }}">
                    <section class="module-page">
                        <div class="module-hero">
                            <div><div class="module-kicker">Performance module</div><h2>{{ $module[0] }}</h2><p>{{ $module[1] }} untuk membaca tren, pencapaian, dan area prioritas secara daily, bulanan, hingga tahunan dari level Sales Force sampai Cluster.</p></div>
                            <div class="coming-badge"><i class="fas fa-database"></i> Database siap dihubungkan</div>
                        </div>
                        <div class="analytics-toolbar">
                            <div class="filter-field"><label>Level Analisis</label><select><option>Cluster</option><option>City</option><option>Kecamatan</option><option>TAP</option><option>Sales Force</option></select></div>
                            <div class="filter-field"><label>Wilayah</label><select><option>Semua Wilayah</option></select></div>
                            <div class="filter-field"><label>Periode</label><select><option>Daily</option><option selected>Bulanan</option><option>Tahunan</option></select></div>
                            <div class="filter-field"><label>Tanggal Data</label><select><option>Periode terbaru</option></select></div>
                        </div>
                        <div class="module-grid">
                            <div class="metric-card"><small>Total Performance</small><strong>—</strong><span>Menunggu sumber data {{ $module[0] }}</span></div>
                            <div class="metric-card"><small>Achievement</small><strong>—%</strong><span>Actual dibanding target</span></div>
                            <div class="metric-card"><small>Growth MoM</small><strong>—%</strong><span>Perubahan dari bulan lalu</span></div>
                            <div class="metric-card"><small>Best Territory</small><strong>—</strong><span>Wilayah dengan performa terbaik</span></div>
                        </div>
                        <div class="placeholder-chart">
                            <div class="placeholder-chart-head"><h3>Tren Performa {{ $module[0] }}</h3><div class="period-pills"><span>Daily</span><span class="active">Monthly</span><span>Yearly</span></div></div>
                            <div class="chart-empty"><div><i class="fas {{ $module[2] }}"></i><strong>Area visualisasi sudah disiapkan</strong><p>Grafik akan otomatis aktif setelah database {{ $module[0] }} terhubung.</p></div></div>
                        </div>
                    </section>
                </div>
            @endforeach
        @else
            <section class="leader-lock">
                <div class="leader-lock-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div>
                    <h2>Dashboard SURYA terkunci</h2>
                    <p>Dashboard leader dan data outlet hanya dapat diakses setelah login internal.</p>
                </div>
                <form class="leader-inline-login" action="{{ route('surya.leader.login.post') }}" method="POST">
                    @csrf
                    <input type="password" name="access_code" aria-label="Kode akses dashboard"
                        placeholder="Masukkan kode akses" autocomplete="current-password" required>
                    <button type="submit" class="leader-login-btn">
                        <i class="fas fa-right-to-bracket"></i> Login Dashboard
                    </button>
                    @if (session('error'))
                        <div class="leader-login-feedback">{{ session('error') }}</div>
                    @elseif ($errors->has('access_code'))
                        <div class="leader-login-feedback">{{ $errors->first('access_code') }}</div>
                    @endif
                </form>
            </section>
        @endif
    </main>
    </div>
    </div>

    <div class="fab" id="fab-top" onclick="window.scrollTo({top:0, behavior:'smooth'})">
        <i class="fas fa-arrow-up"></i>
    </div>

    <footer class="footer" style="text-align:center; padding:10px; color:#aaa; font-size:12px;">
        <p>© 2025 Made by Rby</p>
    </footer>

    <!-- MODAL PERFORMANCE -->
    <div id="performance-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Detail Performance PV</h3>
                <span class="modal-close" onclick="closePerformanceModal()">&times;</span>
            </div>
            <div class="modal-body" id="modal-body">
                <div style="text-align:center; padding:20px;">
                    <i class="fas fa-circle-notch fa-spin" style="font-size:24px; color:#d10000;"></i>
                    <p style="margin-top:10px; color:#666;">Loading data performance...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet Maps JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <script>
        let map;
        let leaderMap;
        let leaderOutletLayer;
        let leaderAreaLayer;
        let leaderDistrictLayer;
        let leaderCompetitionLayer;
        let leaderCompetitionBoundaryCollection = null;
        let leaderCompetitionBoundaryIndex = null;
        let leaderCompetitionBoundaryPromise = null;
        let leaderBounds = [];
        let activeLeaderMapMode = 'trx_cvm';
        let activeFbShareView = 'dominant';
        let activeTableView = 'tap';
        let focusedDistricts = new Set();
        let leaderOutletMarkers = [];
        let leaderThresholds = {
            st_sa: 5,
            st_pv: 40,
            trx_cvm: 11
        };
        let markers = [];
        let outletClusterLayer = null;
        let outletVisitedLayer = null;
        let outletMarkers = new Map();
        const outletDetailsCache = new Map();
        const outletDetailRequests = new Map();
        let selectedOutletMarker = null;
        let pendingOutletFocus = null;
        let userLocationMarker = null;
        let userLocationLatLng = null;
        let history = JSON.parse(localStorage.getItem('monita_history') || '[]');
        let selectedIndex = -1;
        let suggestionTimer = null;
        let suggestionAbortController = null;
        const suggestionCache = new Map();
        const leaderAreas = @json($showLeaderDashboard ? $areas : []);
        const leaderOutletPoints = @json($showLeaderDashboard ? $dashboard['outletPoints'] ?? [] : []);
        const leaderCoveragePoints = @json($showLeaderDashboard ? $dashboard['coveragePoints'] ?? [] : []);
        const leaderCompetitionPoints = @json($showLeaderDashboard ? $dashboard['competitionPoints'] ?? [] : []);
        const performanceMonitorings = @json($showLeaderDashboard ? collect($performanceModules)->mapWithKeys(fn ($module, $key) => [$key => $module['monitoring'] ?? $emptyMonitoring])->all() : []);
        const leaderCompetitionBoundaryUrl = '/static/geo/monita-kecamatan-boundaries.geojson';

        function initLeaderMap() {
            const mapEl = document.getElementById('leader-map');
            if (!mapEl || leaderMap) return;

            leaderMap = L.map('leader-map', {
                zoomControl: false,
                attributionControl: false,
                scrollWheelZoom: false
            }).setView([1.75, 101.05], 8);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(leaderMap);

            leaderOutletLayer = L.layerGroup().addTo(leaderMap);
            leaderAreaLayer = L.layerGroup().addTo(leaderMap);
            leaderDistrictLayer = L.layerGroup().addTo(leaderMap);
            leaderCompetitionLayer = L.layerGroup().addTo(leaderMap);
            loadLeaderCompetitionBoundaries().then(() => {
                if (activeLeaderMapMode === 'competition') {
                    renderLeaderCompetitionLayer();
                }
            });

            const fallbackCenters = {
                'Kota Dumai': [1.667, 101.447],
                'Kabupaten Rokan Hilir': [2.15, 100.85],
                'Kabupaten Bengkalis': [1.45, 101.75]
            };

            const colors = ['#d10000', '#0f9d58', '#007bff'];
            leaderBounds = [];
            renderLeaderOutletLayer(activeLeaderMapMode);

            if (leaderBounds.length) {
                leaderMap.fitBounds(leaderBounds, {
                    padding: [28, 28],
                    maxZoom: 10
                });
            }
        }

        function leaderMapStyle(outlet, mode) {
            const stSa = Number(outlet.st_sa || 0);
            const stPv = Number(outlet.st_pv || 0);
            const trxCvm = Number(outlet.trx_cvm || 0);
            const stSaM1 = Number(outlet.st_sa_m1 || 0);
            const stPvM1 = Number(outlet.st_pv_m1 || 0);
            const trxCvmM1 = Number(outlet.trx_cvm_m1 || 0);
            const hasSa = stSa >= leaderThresholds.st_sa;
            const hasPv = stPv >= leaderThresholds.st_pv;
            const hasCvm = trxCvm >= leaderThresholds.trx_cvm;

            if (mode === 'all') {
                const hasValue = hasCvm || hasPv || hasSa;
                const hasNegativeMom = (hasCvm && trxCvm < trxCvmM1) || (hasPv && stPv < stPvM1) || (hasSa && stSa <
                    stSaM1);
                const statusColor = leaderStatusColor(hasValue, hasNegativeMom);

                return {
                    color: statusColor.fill,
                    borderColor: statusColor.border,
                    radius: hasValue ? Math.max(5.8, Math.min(22, 5 + Math.sqrt(trxCvm + stPv + stSa) / 6)) : 5.8,
                    opacity: statusColor.opacity,
                    weight: hasValue ? 1 : 3,
                    label: 'ALL',
                    hasValue
                };
            }

            if (mode === 'st_sa') {
                const statusColor = leaderStatusColor(hasSa, stSa < stSaM1);
                return {
                    color: statusColor.fill,
                    borderColor: statusColor.border,
                    radius: hasSa ? Math.max(5.2, Math.min(20, 5 + Math.sqrt(stSa) / 5.2)) : 5.8,
                    opacity: statusColor.opacity,
                    weight: hasSa ? 1 : 3,
                    label: 'ST SA',
                    hasValue: hasSa
                };
            }

            if (mode === 'st_pv') {
                const statusColor = leaderStatusColor(hasPv, stPv < stPvM1);
                return {
                    color: statusColor.fill,
                    borderColor: statusColor.border,
                    radius: hasPv ? Math.max(5.2, Math.min(20, 5 + Math.sqrt(stPv) / 5.2)) : 5.8,
                    opacity: statusColor.opacity,
                    weight: hasPv ? 1 : 3,
                    label: 'ST PV',
                    hasValue: hasPv
                };
            }

            if (mode === 'trx_cvm') {
                const statusColor = leaderStatusColor(hasCvm, trxCvm < trxCvmM1);
                return {
                    color: statusColor.fill,
                    borderColor: statusColor.border,
                    radius: hasCvm ? Math.max(5.2, Math.min(20, 5 + Math.sqrt(trxCvm) / 3.8)) : 5.8,
                    opacity: statusColor.opacity,
                    weight: hasCvm ? 1 : 3,
                    label: 'TRX CVM',
                    hasValue: hasCvm
                };
            }

            const activityColors = {
                hot: '#0f9d58',
                cold: '#98a2b3',
                unmapped: '#f59e0b'
            };

            return {
                color: activityColors[outlet.status] || '#98a2b3',
                borderColor: '#ffffff',
                radius: outlet.status === 'hot' ? 4.8 : 3.6,
                opacity: outlet.status === 'cold' ? 0.5 : 0.82,
                weight: 1,
                label: 'Aktivitas',
                hasValue: outlet.status === 'hot'
            };
        }

        function leaderStatusColor(hasValue, hasNegativeMom) {
            if (!hasValue) {
                return {
                    fill: '#ef4444',
                    border: '#ef4444',
                    opacity: 0.08
                };
            }

            if (hasNegativeMom) {
                return {
                    fill: '#f97316',
                    border: '#ffffff',
                    opacity: 0.9
                };
            }

            return {
                fill: '#0f9d58',
                border: '#ffffff',
                opacity: 0.9
            };
        }

        function leaderFormatNumber(value) {
            return Number(value || 0).toLocaleString('id-ID');
        }

        function leaderFormatMom(current, previous) {
            const currentValue = Number(current || 0);
            const previousValue = Number(previous || 0);
            const mom = previousValue > 0 ? ((currentValue / previousValue) - 1) * 100 : 0;
            const tone = mom > 0 ? 'mom-up' : (mom < 0 ? 'mom-down' : 'mom-flat');
            const text = `${mom.toLocaleString('id-ID', {
                minimumFractionDigits: 1,
                maximumFractionDigits: 1
            })}%`;

            return `<span class="${tone}">${text}</span>`;
        }

        function renderLeaderOutletLayer(mode, options = {}) {
            if (!leaderOutletLayer) return;
            leaderOutletLayer.clearLayers();
            if (leaderDistrictLayer) leaderDistrictLayer.clearLayers();
            if (leaderCompetitionLayer) leaderCompetitionLayer.clearLayers();
            leaderBounds = [];
            leaderOutletMarkers = [];

            if (mode === 'competition') {
                renderLeaderCompetitionLayer(options);
                updateLeaderMapNote(mode);
                updateLeaderMapLegend(mode);
                return;
            }

            const districts = {};

            leaderOutletPoints.forEach((outlet) => {
                const lat = Number(outlet.latitude);
                const lng = Number(outlet.longitude);
                if (!lat || !lng) return;

                leaderBounds.push([lat, lng]);
                const districtName = outlet.kecamatan || 'KECAMATAN BELUM ADA';
                if (!districts[districtName]) {
                    districts[districtName] = {
                        lat: 0,
                        lng: 0,
                        total: 0,
                        pjp: 0,
                        activePjp: 0
                    };
                }
                const isPjp = isPjpOutlet(outlet);

                const style = leaderMapStyle(outlet, mode);
                districts[districtName].lat += lat;
                districts[districtName].lng += lng;
                districts[districtName].total += 1;
                districts[districtName].pjp += isPjp ? 1 : 0;
                districts[districtName].activePjp += isPjp && style.hasValue ? 1 : 0;

                const popupRows = [
                    ['ST SA', outlet.st_sa_m1, outlet.st_sa],
                    ['ST PV', outlet.st_pv_m1, outlet.st_pv],
                    ['TRX CVM', outlet.trx_cvm_m1, outlet.trx_cvm],
                ].map(([label, previous, current]) => `
                    <tr>
                        <td>${label}</td>
                        <td>${leaderFormatNumber(previous)}</td>
                        <td>${leaderFormatNumber(current)}</td>
                        <td>${leaderFormatMom(current, previous)}</td>
                    </tr>
                `).join('');

                const marker = L.circleMarker([lat, lng], {
                    radius: style.radius,
                    color: style.borderColor,
                    weight: style.weight,
                    fillColor: style.color,
                    fillOpacity: style.opacity
                }).addTo(leaderOutletLayer).bindPopup(`
                    <div class="leader-popup-title">${outlet.nama_outlet}</div>
                    <div class="leader-popup-meta">
                        ${outlet.id_outlet}<br>
                        Mode: ${style.label}<br>
                        Kecamatan: ${districtName}<br>
                        TAP: ${outlet.tap}<br>
                        SF: ${outlet.sf}
                    </div>
                    <table class="leader-popup-table">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>M-1</th>
                                <th>M</th>
                                <th>%MoM</th>
                            </tr>
                        </thead>
                        <tbody>${popupRows}</tbody>
                    </table>
                `);

                marker.__district = districtName;
                marker.__style = style;
                marker.__isActive = style.hasValue;
                leaderOutletMarkers.push(marker);
            });

            renderLeaderDistrictLabels(districts, mode);
            updateLeaderMapNote(mode);
            updateLeaderMapLegend(mode);
            updateTapActiveTable();
            applyDistrictFocus();
        }

        function leaderCompetitionDisplay(point) {
            const operators = Array.isArray(point.operators) ? point.operators : [];
            const winner = {
                key: point.winner_key || '',
                operator: point.winner_operator || 'N/A',
                share: Number(point.winner_share || 0),
                mom: Number(point.winner_mom || 0),
                color: point.winner_color || '#20293a'
            };

            if (activeFbShareView === 'closest') {
                const competitor = operators
                    .filter((operator) => String(operator.key || '').toLowerCase() !== 'tsel')
                    .sort((a, b) => Number(b.share || 0) - Number(a.share || 0))[0];

                if (competitor) {
                    return {
                        viewLabel: 'Closest competitor',
                        key: competitor.key || '',
                        operator: competitor.label || 'N/A',
                        share: Number(competitor.share || 0),
                        mom: Number(competitor.mom || 0),
                        color: competitor.color || '#20293a',
                        winner
                    };
                }
            }

            return {
                viewLabel: 'Dominan',
                ...winner,
                winner
            };
        }

        function leaderShortLabel(value, maxLength = 18) {
            const text = String(value || '').trim().toUpperCase();
            return text.length > maxLength ? `${text.slice(0, maxLength - 3)}...` : text;
        }

        function leaderOperatorLogoPath(display) {
            const key = String(display.key || '').trim().toLowerCase();
            const label = String(display.operator || '').trim().toUpperCase();
            const logos = {
                tsel: '/static/images/operators/telkomsel.svg',
                telkomsel: '/static/images/operators/telkomsel.svg',
                xl: '/static/images/operators/xl.svg',
                axis: '/static/images/operators/axis.svg',
                isat: '/static/images/operators/indosat.svg',
                indosat: '/static/images/operators/indosat.svg',
                tri: '/static/images/operators/tri.svg',
                '3': '/static/images/operators/tri.svg',
                sfren: '/static/images/operators/smartfren.svg',
                smartfren: '/static/images/operators/smartfren.svg',
                istri: '/static/images/operators/indosat.svg',
                xlsf: '/static/images/operators/xl.svg'
            };

            return logos[key] || logos[label.toLowerCase()] || '';
        }

        function leaderCompetitionLabelHtml(point, display, shareText) {
            const district = leaderShortLabel(point.kecamatan, 18);
            const logoPath = leaderOperatorLogoPath(display);
            const operatorName = String(display.operator || 'Operator').trim();
            const fallbackText = operatorName.slice(0, 2).toUpperCase();

            return `
                <span style="background:${display.color}; --operator-color:${display.color};">
                    <span class="operator-logo">
                        ${logoPath
                            ? `<img src="${logoPath}" alt="${operatorName}">`
                            : fallbackText}
                    </span>
                    <span class="competition-label-text">${district} ${shareText}%</span>
                </span>
            `;
        }

        function leaderCompetitionAreaRadius(point) {
            const outletCount = Number(point.outlet_count || 0);
            return Math.max(1800, Math.min(7800, 1600 + (Math.sqrt(outletCount) * 520)));
        }

        function leaderBoundaryNormalize(value) {
            return String(value || '')
                .toUpperCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^A-Z0-9]+/g, ' ')
                .trim();
        }

        function leaderBoundaryKey(kecamatan, tap) {
            return `${leaderBoundaryNormalize(kecamatan)}|${leaderBoundaryNormalize(tap)}`;
        }

        function loadLeaderCompetitionBoundaries() {
            if (leaderCompetitionBoundaryCollection) {
                return Promise.resolve(leaderCompetitionBoundaryCollection);
            }

            if (!leaderCompetitionBoundaryPromise) {
                leaderCompetitionBoundaryPromise = fetch(leaderCompetitionBoundaryUrl)
                    .then((response) => response.ok ? response.json() : null)
                    .then((geojson) => {
                        if (!geojson || !Array.isArray(geojson.features)) return null;

                        leaderCompetitionBoundaryCollection = geojson;
                        leaderCompetitionBoundaryIndex = new Map();
                        geojson.features.forEach((feature) => {
                            const properties = feature.properties || {};
                            const key = properties.key || leaderBoundaryKey(properties.kecamatan, properties
                                .tap);
                            if (key) {
                                leaderCompetitionBoundaryIndex.set(key, feature);
                            }
                        });

                        return geojson;
                    })
                    .catch(() => null);
            }

            return leaderCompetitionBoundaryPromise;
        }

        function leaderCompetitionBoundaryFeature(point) {
            if (!leaderCompetitionBoundaryIndex) return null;

            return leaderCompetitionBoundaryIndex.get(leaderBoundaryKey(point.kecamatan, point.tap)) || null;
        }

        function renderLeaderCompetitionLayer(options = {}) {
            if (!leaderCompetitionLayer) return;

            leaderCompetitionLayer.clearLayers();
            leaderBounds = [];

            if (!leaderCompetitionBoundaryCollection) {
                loadLeaderCompetitionBoundaries().then(() => {
                    if (activeLeaderMapMode === 'competition') {
                        renderLeaderCompetitionLayer(options);
                    }
                });
            }

            leaderCompetitionPoints.forEach((point) => {
                const lat = Number(point.latitude);
                const lng = Number(point.longitude);

                if (!lat || !lng) return;

                const display = leaderCompetitionDisplay(point);
                const share = Number(display.share || 0);
                const color = display.color || '#20293a';
                const shareText = share.toLocaleString('id-ID', {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1
                });
                const momText = Number(display.mom || 0).toLocaleString('id-ID', {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1
                });
                const winnerShareText = Number(display.winner.share || 0).toLocaleString('id-ID', {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1
                });
                const topOperators = (point.operators || []).slice(0, 4).map((operator) => {
                    const mom = Number(operator.mom || 0);
                    const momText = `${mom >= 0 ? '+' : ''}${mom.toLocaleString('id-ID', {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: 1
                    })}%`;

                    return `${operator.label}: ${Number(operator.share || 0).toLocaleString('id-ID', {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: 1
                    })}% (${momText})`;
                }).join('<br>');
                const popupHtml = `
                    <b>${point.kecamatan}</b><br>
                    TAP: ${point.tap}<br>
                    Cluster: ${point.cluster}<br>
                    Outlet PJP diplot: ${Number(point.outlet_count || 0).toLocaleString('id-ID')}<br>
                    Mode: <b>${display.viewLabel}</b><br>
                    Operator: <b>${display.operator}</b><br>
                    Share: ${shareText}%<br>
                    MoM: ${momText}%<br>
                    Dominan: ${display.winner.operator} ${winnerShareText}%<br><br>
                    ${topOperators}
                `;
                const boundaryFeature = leaderCompetitionBoundaryFeature(point);
                const fillOpacity = Math.max(0.26, Math.min(0.56, share / 145));
                leaderBounds.push([lat, lng]);

                if (boundaryFeature) {
                    const boundaryLayer = L.geoJSON(boundaryFeature, {
                        style: {
                            color: '#ffffff',
                            weight: 1.5,
                            opacity: 0.82,
                            fillColor: color,
                            fillOpacity
                        }
                    }).addTo(leaderCompetitionLayer).bindPopup(popupHtml);

                    boundaryLayer.eachLayer((layer) => {
                        layer.on({
                            mouseover: () => layer.setStyle({
                                weight: 2.8,
                                opacity: 0.95,
                                fillOpacity: Math.min(0.68, fillOpacity + 0.12)
                            }),
                            mouseout: () => layer.setStyle({
                                weight: 1.5,
                                opacity: 0.82,
                                fillOpacity
                            })
                        });
                    });
                } else {
                    L.circle([lat, lng], {
                        radius: leaderCompetitionAreaRadius(point),
                        color: '#ffffff',
                        weight: 1.3,
                        opacity: 0.7,
                        fillColor: color,
                        fillOpacity: Math.max(0.18, Math.min(0.42, share / 210))
                    }).addTo(leaderCompetitionLayer).bindPopup(popupHtml);
                }

                L.marker([lat, lng], {
                    interactive: true,
                    icon: L.divIcon({
                        className: 'competition-area-label',
                        html: leaderCompetitionLabelHtml(point, display, shareText),
                        iconSize: [154, 30],
                        iconAnchor: [77, 15]
                    })
                }).addTo(leaderCompetitionLayer).bindPopup(popupHtml);
            });

            renderCompetitionSaOverlay();

            if (leaderBounds.length && !options.preserveMapView) {
                leaderMap.fitBounds(leaderBounds, {
                    padding: [28, 28],
                    maxZoom: 10
                });
            }
        }

        function renderCompetitionSaOverlay() {
            if (!leaderOutletLayer) return;

            leaderOutletPoints.forEach((outlet) => {
                if (!isPjpOutlet(outlet)) return;

                const lat = Number(outlet.latitude);
                const lng = Number(outlet.longitude);
                if (!lat || !lng) return;

                const stSa = Number(outlet.st_sa || 0);
                const stSaM1 = Number(outlet.st_sa_m1 || 0);
                const hasSa = stSa >= leaderThresholds.st_sa;
                const statusColor = leaderStatusColor(hasSa, stSa < stSaM1);
                const popupRows = `
                    <tr>
                        <td>ST SA</td>
                        <td>${leaderFormatNumber(stSaM1)}</td>
                        <td>${leaderFormatNumber(stSa)}</td>
                        <td>${leaderFormatMom(stSa, stSaM1)}</td>
                    </tr>
                `;

                L.circleMarker([lat, lng], {
                    radius: hasSa ? Math.max(3.8, Math.min(8.5, 3.8 + Math.sqrt(stSa) / 5.5)) : 4.6,
                    color: statusColor.border,
                    weight: hasSa ? 1.3 : 2.6,
                    fillColor: statusColor.fill,
                    fillOpacity: hasSa ? 0.84 : 0.04,
                    opacity: hasSa ? 0.96 : 0.8
                }).addTo(leaderOutletLayer).bindPopup(`
                    <div class="leader-popup-title">${outlet.nama_outlet}</div>
                    <div class="leader-popup-meta">
                        ${outlet.id_outlet}<br>
                        Mode: FB Share + ST SA<br>
                        Kecamatan: ${outlet.kecamatan}<br>
                        TAP: ${outlet.tap}<br>
                        SF: ${outlet.sf}
                    </div>
                    <table class="leader-popup-table">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>M-1</th>
                                <th>M</th>
                                <th>%MoM</th>
                            </tr>
                        </thead>
                        <tbody>${popupRows}</tbody>
                    </table>
                `);
            });
        }

        function isPjpOutlet(outlet) {
            const sf = String(outlet.sf || '').trim().toUpperCase();
            return sf !== '' && sf !== 'UNMAPPING';
        }

        function renderLeaderDistrictLabels(districts, mode) {
            if (!leaderDistrictLayer) return;
            leaderDistrictLayer.clearLayers();

            Object.entries(districts)
                .filter(([, district]) => district.pjp > 0)
                .sort((a, b) => b[1].pjp - a[1].pjp)
                .forEach(([name, district]) => {
                    const lat = district.lat / district.total;
                    const lng = district.lng / district.total;
                    const pct = Math.round((district.activePjp / Math.max(1, district.pjp)) * 100);
                    const label = name.length > 16 ? `${name.slice(0, 16)}...` : name;
                    const isFocused = focusedDistricts.has(name);

                    const marker = L.marker([lat, lng], {
                        interactive: true,
                        icon: L.divIcon({
                            className: `district-label${isFocused ? ' active' : ''}`,
                            html: `<span>${label} ${pct}%</span>`,
                            iconSize: [112, 28],
                            iconAnchor: [56, 14]
                        })
                    }).addTo(leaderDistrictLayer);

                    marker.bindTooltip(`
                        <b>${name}</b><br>
                        Total outlet: ${Number(district.total).toLocaleString('id-ID')}<br>
                        Outlet PJP: ${Number(district.pjp).toLocaleString('id-ID')}<br>
                        Aktif mode ini: ${Number(district.activePjp).toLocaleString('id-ID')} (${pct}%)
                    `, {
                        className: 'leader-map-tooltip',
                        direction: 'top',
                        opacity: 1,
                        sticky: true
                    });

                    marker.on('click', () => {
                        if (focusedDistricts.has(name)) {
                            focusedDistricts.delete(name);
                        } else {
                            focusedDistricts.add(name);
                        }
                        applyDistrictFocus();
                        renderLeaderDistrictLabels(districts, mode);
                    });
                });
        }

        function applyDistrictFocus() {
            leaderOutletMarkers.forEach((marker) => {
                const base = marker.__style || {};
                const hasFocus = focusedDistricts.size > 0;
                const isTarget = !hasFocus || focusedDistricts.has(marker.__district);
                const activeBoost = marker.__isActive ? 1 : 0.58;
                const fillOpacity = isTarget ? Math.max(base.opacity || 0.1, activeBoost) : 0.05;
                const opacity = isTarget ? 1 : 0.18;
                const radius = isTarget ? (base.radius || 5.8) + (hasFocus ? 1.6 : 0) : Math.max(3, (base.radius ||
                    5.8) - 1.8);

                marker.setStyle({
                    fillOpacity,
                    opacity,
                    radius,
                    weight: isTarget ? Math.max(base.weight || 1, hasFocus ? 2 : base.weight || 1) : 1
                });

                const path = marker.getElement && marker.getElement();
                if (path) {
                    path.classList.remove('outlet-focus-pulse');
                }
            });
        }

        function updateLeaderMapNote(mode) {
            const note = document.getElementById('map-mode-note');
            if (!note) return;

            const labels = {
                activity: 'Mode aktif menampilkan outlet produktif, cold outlet, dan unmapping.',
                competition: activeFbShareView === 'closest' ?
                    'Mode FB Share menampilkan closest competitor non-TSEL per kecamatan. Titik di peta menunjukkan sebaran outlet ST SA.' :
                    'Mode FB Share menampilkan operator dominan per kecamatan. Titik di peta menunjukkan sebaran outlet ST SA.',
                st_sa: `Hijau berarti ST SA minimal ${leaderThresholds.st_sa} dan MoM positif/stabil. Orange berarti capai min tapi MoM minus. Merah berarti belum capai min SA.`,
                st_pv: `Hijau berarti ST PV minimal ${leaderThresholds.st_pv} dan MoM positif/stabil. Orange berarti capai min tapi MoM minus. Merah berarti belum capai min PV.`,
                trx_cvm: `Hijau berarti TRX CVM minimal ${leaderThresholds.trx_cvm} dan MoM positif/stabil. Orange berarti capai min tapi MoM minus. Merah berarti belum capai min CVM.`
            };

            note.querySelector('span').textContent = labels[mode] || labels.activity;
        }

        function updateLeaderMapLegend(mode) {
            const legend = document.getElementById('legend-has-st');
            const goodStatus = document.getElementById('legend-good-status');
            const belowThreshold = document.getElementById('legend-below-threshold');
            if (!legend) return;

            const legendItems = document.querySelectorAll('.map-legend-item');
            const dot = legendItems[0]?.querySelector('.legend-dot');
            const secondDot = legendItems[1]?.querySelector('.legend-dot');
            const thirdDot = legendItems[2]?.querySelector('.legend-dot');
            if (dot) {
                dot.className = 'legend-dot mom-up';
                dot.style.background = '';
            }
            if (secondDot) {
                secondDot.className = 'legend-dot mom-down';
                secondDot.style.background = '';
            }
            if (thirdDot) {
                thirdDot.className = 'legend-dot no-st';
                thirdDot.style.background = '';
            }

            const thresholdBox = document.querySelector('.map-thresholds');
            if (thresholdBox) {
                thresholdBox.style.display = '';
                thresholdBox.querySelectorAll('.threshold-field').forEach((field) => {
                    const input = field.querySelector('[data-threshold]');
                    field.style.display = mode === 'competition' && input?.dataset.threshold !== 'st_sa' ? 'none' :
                        '';
                });
            }
            const fbShareToggle = document.getElementById('fb-share-toggle');
            if (fbShareToggle) fbShareToggle.classList.toggle('is-visible', mode === 'competition');

            if (mode === 'competition') {
                if (dot) dot.style.background = 'linear-gradient(90deg, #e30613, #0057ff, #f5c400)';
                if (secondDot) {
                    secondDot.className = 'legend-dot';
                    secondDot.style.background = 'linear-gradient(90deg, #0f9d58 0 50%, #f97316 50% 100%)';
                }
                if (goodStatus) goodStatus.textContent = activeFbShareView === 'closest' ?
                    'Area = closest competitor FB Share' :
                    'Area = operator dominan FB Share';
                if (belowThreshold) belowThreshold.textContent = `Ring merah = ST SA < ${leaderThresholds.st_sa}`;
                const secondLegend = document.querySelectorAll('.map-legend-item span:last-child')[1];
                if (secondLegend) secondLegend.textContent = `Titik hijau/oranye = ST SA >= ${leaderThresholds.st_sa}`;
                return;
            }

            const secondLegend = document.querySelectorAll('.map-legend-item span:last-child')[1];
            if (secondLegend) secondLegend.textContent = 'Capai min tapi MoM minus';

            if (mode === 'all') {
                if (goodStatus) goodStatus.textContent = 'Capai min & MoM positif/stabil';
                if (belowThreshold) belowThreshold.textContent = 'Belum capai min apa pun';
            } else if (mode === 'st_sa') {
                if (goodStatus) goodStatus.textContent = `ST SA >= ${leaderThresholds.st_sa} & MoM positif/stabil`;
                if (belowThreshold) belowThreshold.textContent = `ST SA < ${leaderThresholds.st_sa}`;
            } else if (mode === 'trx_cvm') {
                if (goodStatus) goodStatus.textContent = `TRX CVM >= ${leaderThresholds.trx_cvm} & MoM positif/stabil`;
                if (belowThreshold) belowThreshold.textContent = `TRX CVM < ${leaderThresholds.trx_cvm}`;
            } else {
                if (goodStatus) goodStatus.textContent = `ST PV >= ${leaderThresholds.st_pv} & MoM positif/stabil`;
                if (belowThreshold) belowThreshold.textContent = `ST PV < ${leaderThresholds.st_pv}`;
            }
        }

        function updateTapActiveTable() {
            const tableBody = document.getElementById('tap-active-body');
            if (!tableBody) return;

            const rows = {};
            const dimensionHeader = document.getElementById('active-table-dimension');
            const isDistrictView = activeTableView === 'kecamatan';
            const isSalesForceView = activeTableView === 'sales_force';
            if (dimensionHeader) {
                dimensionHeader.textContent = isDistrictView ? 'Kecamatan' : (isSalesForceView ? 'Sales Force' : 'TAP');
            }

            leaderCoveragePoints.forEach((outlet) => {
                const tap = outlet.tap || 'TAP BELUM ADA';
                const cluster = leaderClusterLabelForTap(tap);
                const group = isSalesForceView ? tap : cluster;
                const label = isDistrictView ?
                    (outlet.kecamatan || 'KECAMATAN BELUM ADA') :
                    (isSalesForceView ? (outlet.sf || 'SF BELUM ADA') : tap);
                const key = `${group}::${label}`;

                if (!rows[key]) {
                    rows[key] = {
                        label,
                        group,
                        groupOrder: leaderActiveTableGroupOrder(group, activeTableView),
                        sa: 0,
                        saM1: 0,
                        pv: 0,
                        pvM1: 0,
                        cvm: 0,
                        cvmM1: 0
                    };
                }

                rows[key].sa += Number(outlet.st_sa || 0) >= leaderThresholds.st_sa ? 1 : 0;
                rows[key].saM1 += Number(outlet.st_sa_m1 || 0) >= leaderThresholds.st_sa ? 1 : 0;
                rows[key].pv += Number(outlet.st_pv || 0) >= leaderThresholds.st_pv ? 1 : 0;
                rows[key].pvM1 += Number(outlet.st_pv_m1 || 0) >= leaderThresholds.st_pv ? 1 : 0;
                rows[key].cvm += Number(outlet.trx_cvm || 0) >= leaderThresholds.trx_cvm ? 1 : 0;
                rows[key].cvmM1 += Number(outlet.trx_cvm_m1 || 0) >= leaderThresholds.trx_cvm ? 1 : 0;
            });

            const sortedRows = Object.values(rows).sort((a, b) => {
                if (a.groupOrder !== b.groupOrder) return a.groupOrder - b.groupOrder;
                if (a.group !== b.group) return a.group.localeCompare(b.group);
                return a.label.localeCompare(b.label);
            });
            const groupSummaries = buildActiveTableGroupSummaries(sortedRows);

            let lastGroup = null;
            tableBody.innerHTML = sortedRows.map((row) => {
                const needsGroupHeader = row.group !== lastGroup;
                lastGroup = row.group;

                return `${needsGroupHeader ? renderActiveTableGroupRow(row.group, groupSummaries[row.group]) : ''}${renderActiveTableDataRow(row)}`;
            }).join('');
        }

        function buildActiveTableGroupSummaries(rows) {
            return rows.reduce((summaries, row) => {
                if (!row.group) return summaries;

                if (!summaries[row.group]) {
                    summaries[row.group] = {
                        cvm: 0,
                        cvmM1: 0,
                        pv: 0,
                        pvM1: 0,
                        sa: 0,
                        saM1: 0
                    };
                }

                summaries[row.group].cvm += Number(row.cvm || 0);
                summaries[row.group].cvmM1 += Number(row.cvmM1 || 0);
                summaries[row.group].pv += Number(row.pv || 0);
                summaries[row.group].pvM1 += Number(row.pvM1 || 0);
                summaries[row.group].sa += Number(row.sa || 0);
                summaries[row.group].saM1 += Number(row.saM1 || 0);

                return summaries;
            }, {});
        }

        function leaderClusterLabelForTap(tap) {
            const normalizedTap = String(tap || '').trim().toUpperCase();
            const dumaiBengkalisTaps = ['DUMAI', 'DURI', 'BENGKALIS', 'RUPAT', 'SEI PAKNING'];
            const rokanHilirTaps = ['BAGAN BATU', 'BAGAN SIAPI-API', 'UJUNG TANJUNG'];

            if (dumaiBengkalisTaps.includes(normalizedTap)) return 'Dumai Bengkalis';
            if (rokanHilirTaps.includes(normalizedTap)) return 'Rokan Hilir';
            return 'Cluster Lain';
        }

        function leaderActiveTableGroupOrder(group, view) {
            const clusterOrders = {
                'Dumai Bengkalis': 1,
                'Rokan Hilir': 2,
                'Cluster Lain': 3
            };

            if (view === 'tap' || view === 'kecamatan') {
                return clusterOrders[group] || 99;
            }

            const tapOrders = {
                'BAGAN BATU': 1,
                'BAGAN SIAPI-API': 2,
                'BENGKALIS': 3,
                'DUMAI': 4,
                'DURI': 5,
                'RUPAT': 6,
                'SEI PAKNING': 7,
                'UJUNG TANJUNG': 8
            };

            return tapOrders[String(group || '').trim().toUpperCase()] || 99;
        }

        function renderActiveTableGroupRow(group, summary = {}) {
            return `
                <tr class="active-group-row">
                    <td>${group}</td>
                    <td>${Number(summary.cvm || 0).toLocaleString('id-ID')}</td>
                    <td>${Number(summary.cvmM1 || 0).toLocaleString('id-ID')}</td>
                    <td class="${momClass(summary.cvm || 0, summary.cvmM1 || 0)}">${formatMom(summary.cvm || 0, summary.cvmM1 || 0)}</td>
                    <td>${Number(summary.pv || 0).toLocaleString('id-ID')}</td>
                    <td>${Number(summary.pvM1 || 0).toLocaleString('id-ID')}</td>
                    <td class="${momClass(summary.pv || 0, summary.pvM1 || 0)}">${formatMom(summary.pv || 0, summary.pvM1 || 0)}</td>
                    <td>${Number(summary.sa || 0).toLocaleString('id-ID')}</td>
                    <td>${Number(summary.saM1 || 0).toLocaleString('id-ID')}</td>
                    <td class="${momClass(summary.sa || 0, summary.saM1 || 0)}">${formatMom(summary.sa || 0, summary.saM1 || 0)}</td>
                </tr>
            `;
        }

        function renderActiveTableDataRow(row) {
            return `
                <tr>
                    <td>${row.label}</td>
                    <td>${Number(row.cvm).toLocaleString('id-ID')}</td>
                    <td>${Number(row.cvmM1).toLocaleString('id-ID')}</td>
                    <td class="${momClass(row.cvm, row.cvmM1)}">${formatMom(row.cvm, row.cvmM1)}</td>
                    <td>${Number(row.pv).toLocaleString('id-ID')}</td>
                    <td>${Number(row.pvM1).toLocaleString('id-ID')}</td>
                    <td class="${momClass(row.pv, row.pvM1)}">${formatMom(row.pv, row.pvM1)}</td>
                    <td>${Number(row.sa).toLocaleString('id-ID')}</td>
                    <td>${Number(row.saM1).toLocaleString('id-ID')}</td>
                    <td class="${momClass(row.sa, row.saM1)}">${formatMom(row.sa, row.saM1)}</td>
                </tr>
            `;
        }

        function bindActiveTableViewToggles() {
            document.querySelectorAll('[data-table-view]').forEach((button) => {
                button.addEventListener('click', () => {
                    activeTableView = button.dataset.tableView || 'tap';
                    document.querySelectorAll('[data-table-view]').forEach((item) => {
                        item.classList.toggle('active', item === button);
                    });
                    updateTapActiveTable();
                });
            });
        }

        function updateLeaderCoverageBadges() {
            const groupLabels = {
                dumai_bengkalis: 'Dumai Bengkalis',
                rokan_hilir: 'Rokan Hilir'
            };
            const metricMap = {
                sa: {
                    field: 'st_sa',
                    threshold: 'st_sa'
                },
                pv: {
                    field: 'st_pv',
                    threshold: 'st_pv'
                },
                cvm: {
                    field: 'trx_cvm',
                    threshold: 'trx_cvm'
                }
            };
            const rows = {};

            Object.keys(groupLabels).forEach((group) => {
                rows[group] = {
                    pjp: 0,
                    sa: 0,
                    pv: 0,
                    cvm: 0
                };
            });

            leaderCoveragePoints.forEach((point) => {
                if (!rows[point.group]) return;

                rows[point.group].pjp += 1;
                Object.entries(metricMap).forEach(([metric, config]) => {
                    rows[point.group][metric] += Number(point[config.field] || 0) >= leaderThresholds[config
                        .threshold] ? 1 : 0;
                });
            });

            document.querySelectorAll('[data-coverage-row]').forEach((row) => {
                const group = row.dataset.coverageGroup;
                const metric = row.dataset.coverageMetric;
                const data = rows[group] || {
                    pjp: 0,
                    [metric]: 0
                };
                const value = Number(data[metric] || 0);
                const pjp = Number(data.pjp || 0);
                const percent = pjp > 0 ? (value / pjp) * 100 : 0;
                const percentText = `${percent.toLocaleString('id-ID', {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1
                })}%`;

                row.querySelector('[data-coverage-value]').textContent = value.toLocaleString('id-ID');
                row.querySelector('[data-coverage-meta]').textContent = `dari ${pjp.toLocaleString('id-ID')} PJP`;
                row.querySelector('[data-coverage-percent]').textContent = percentText;
                const clampedPercent = Math.max(0, Math.min(100, percent));
                const gauge = row.querySelector('.coverage-gauge');
                const needle = row.querySelector('.coverage-gauge-needle');
                const point = leaderGaugeNeedlePoint(clampedPercent);
                needle.setAttribute('x2', point.x.toFixed(2));
                needle.setAttribute('y2', point.y.toFixed(2));
                gauge.setAttribute('aria-label', `${metric.toUpperCase()} ${percentText}`);
            });
        }

        function leaderGaugeNeedlePoint(percent) {
            const radians = Math.PI - ((percent / 100) * Math.PI);

            return {
                x: 100 + (Math.cos(radians) * 56),
                y: 90 - (Math.sin(radians) * 56)
            };
        }

        function formatMom(current, previous) {
            if (!previous) return current > 0 ? '100%' : '0,0%';
            return `${(((current / previous) - 1) * 100).toLocaleString('id-ID', {
                minimumFractionDigits: 1,
                maximumFractionDigits: 1
            })}%`;
        }

        function momClass(current, previous) {
            if (!previous) return current > 0 ? 'mom-up' : 'mom-flat';
            if (current > previous) return 'mom-up';
            if (current < previous) return 'mom-down';
            return 'mom-flat';
        }

        function bindLeaderThresholds() {
            document.querySelectorAll('[data-threshold]').forEach((input) => {
                const key = input.dataset.threshold;
                leaderThresholds[key] = Math.max(0, Number(input.value || 0));

                input.addEventListener('input', () => {
                    leaderThresholds[key] = Math.max(0, Number(input.value || 0));
                    renderLeaderOutletLayer(activeLeaderMapMode, {
                        preserveMapView: true
                    });
                    updateLeaderCoverageBadges();
                });
            });
            updateLeaderCoverageBadges();
        }

        function bindLeaderMapZoom() {
            document.querySelectorAll('[data-map-zoom]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!leaderMap) return;

                    const action = button.dataset.mapZoom;
                    if (action === 'in') {
                        leaderMap.zoomIn();
                    } else if (action === 'out') {
                        leaderMap.zoomOut();
                    } else if (action === 'fit' && leaderBounds.length) {
                        leaderMap.fitBounds(leaderBounds, {
                            padding: [28, 28],
                            maxZoom: 10
                        });
                    }
                });
            });
        }

        function bindLeaderMapToggles() {
            document.querySelectorAll('[data-map-mode]').forEach((button) => {
                button.addEventListener('click', () => {
                    activeLeaderMapMode = button.dataset.mapMode || 'activity';
                    document.querySelectorAll('[data-map-mode]').forEach((item) => {
                        item.classList.toggle('active', item === button);
                    });
                    renderLeaderOutletLayer(activeLeaderMapMode, {
                        preserveMapView: true
                    });
                });
            });
        }

        function bindFbShareToggles() {
            document.querySelectorAll('[data-fb-share-view]').forEach((button) => {
                button.addEventListener('click', () => {
                    activeFbShareView = button.dataset.fbShareView || 'dominant';
                    document.querySelectorAll('[data-fb-share-view]').forEach((item) => {
                        item.classList.toggle('active', item === button);
                    });

                    if (activeLeaderMapMode === 'competition') {
                        renderLeaderOutletLayer(activeLeaderMapMode, {
                            preserveMapView: true
                        });
                    }
                });
            });
        }

        function collapseSuryaSidebar() {
            const shell = document.getElementById('app-shell');
            if (!shell) return;

            if (window.innerWidth <= 960) shell.classList.remove('sidebar-open');
        }

        function bindDashboardMenu() {
            const buttons = document.querySelectorAll('[data-dashboard-tab]');
            if (!buttons.length) return;

            const availableTabs = new Set(Array.from(buttons).map((button) => button.dataset.dashboardTab));
            const activateTab = (requestedTab) => {
                const tab = availableTabs.has(requestedTab) ? requestedTab : 'outlet';

                if (requestedTab !== tab) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('panel', tab);
                    url.hash = tab;
                    window.history.replaceState(null, '', url);
                }

                buttons.forEach((item) => {
                    const itemTab = item.dataset.dashboardTab;
                    const isSidebar = item.classList.contains('sidebar-link');
                    const active = itemTab === tab;
                    item.classList.toggle('active', active);
                    item.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                document.querySelectorAll('.dashboard-panel').forEach((panel) => {
                    panel.classList.toggle('active', panel.id === `dashboard-panel-${tab}`);
                });
                document.getElementById('dashboard-boot-panel-style')?.remove();

                if (tab === 'outlet') {
                    if (!map) loadAllOutlets();
                    else {
                        [0, 120, 360, 700].forEach((delay) => setTimeout(() => map.invalidateSize({ pan: false, animate: false }), delay));
                    }
                } else if (leaderMap) {
                    setTimeout(() => leaderMap.invalidateSize(), 100);
                }

                collapseSuryaSidebar();
            };

            buttons.forEach((button) => {
                button.addEventListener('click', (event) => {
                    const tab = button.dataset.dashboardTab;
                    if (button.matches('a[href^="#"]')) event.preventDefault();
                    if (tab) {
                        const url = new URL(window.location.href);
                        if (tab === 'leader') url.searchParams.delete('panel');
                        else url.searchParams.set('panel', tab);
                        url.hash = tab;
                        if (url.href !== window.location.href) window.history.pushState(null, '', url);
                    }
                    activateTab(tab);
                });
            });

            window.addEventListener('hashchange', () => activateTab(window.location.hash.replace('#', '')));
            window.addEventListener('popstate', () => activateTab(window.location.hash.replace('#', '')));
            activateTab(
                window.location.hash.replace('#', '') ||
                new URLSearchParams(window.location.search).get('panel') ||
                'outlet'
            );
        }

        function bindSidebarToggle() {
            const shell = document.getElementById('app-shell');
            const toggle = document.getElementById('sidebar-toggle');
            if (!shell || !toggle) return;
            const close = document.getElementById('sidebar-close');

            const isMobile = window.innerWidth <= 960;
            const collapsed = isMobile || localStorage.getItem('surya-sidebar-collapsed') === 'true';
            shell.classList.toggle('sidebar-collapsed', collapsed && !isMobile);
            document.documentElement.classList.remove('surya-sidebar-precollapsed');
            shell.classList.remove('sidebar-open');
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

            toggle.addEventListener('click', () => {
                if (window.innerWidth <= 960) {
                    shell.classList.toggle('sidebar-open');
                    return;
                }
                const isCollapsed = shell.classList.toggle('sidebar-collapsed');
                try { localStorage.setItem('surya-sidebar-collapsed', isCollapsed ? 'true' : 'false'); } catch (error) {}
                toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                if (map) setTimeout(() => map.invalidateSize(), 240);
                if (leaderMap) setTimeout(() => leaderMap.invalidateSize(), 240);
            });
            close?.addEventListener('click', () => {
                shell.classList.remove('sidebar-open');
            });
        }

        function bindPerformanceMonitoringTables() {
            document.querySelectorAll('[data-monitoring-key]').forEach((root) => bindPerformanceMonitoring(root));
        }

        function bindPerformanceMonitoring(root) {
            const monitoringKey = root.dataset.monitoringKey;
            const monitoring = performanceMonitorings[monitoringKey] || {};
            const rowsData = monitoring.rows || [];
            const clusterSummaryData = monitoring.clusterSummary || [];
            const categoryValidities = monitoring.categoryValidities || {};
            const validityHeaders = monitoring.validityHeaders || {};
            const categories = monitoring.categories || [];
            const fallbackCategory = categories[0]?.key || Object.keys(categoryValidities)[0] || 'all';
            let activeDimension = root.dataset.defaultDimension || 'tap';
            let activeCategory = root.querySelector('[data-pv-category].active')?.dataset.pvCategory || fallbackCategory;
            let sortState = { key: 'name', direction: 'asc', validity: null, metric: null };

            const scoped = (selector) => root.querySelectorAll(selector);
            const rowSource = (row) => rowsData[Number(row.dataset.rowIndex)] || {};
            const dimensionLabels = {
                tap: 'TAP / Nama SF',
                kecamatan: 'Kecamatan',
                sf: 'Nama SF',
            };
            const visibleValiditySet = () => new Set(categoryValidities[activeCategory] || categoryValidities[fallbackCategory] || []);
            const formatNumber = (value) => Number(value || 0).toLocaleString('id-ID');
            const formatPercent = (value) => `${Number(value || 0).toLocaleString('id-ID', {minimumFractionDigits: 1, maximumFractionDigits: 1})}%`;
            const momMarkup = (value) => {
                const state = value > 0 ? 'up' : (value < 0 ? 'down' : 'flat');
                return `<span class="pv-mom ${state}">${formatPercent(value)}</span>`;
            };
            const resetSortIconState = () => {
                scoped('[data-pv-sort]').forEach((item) => {
                    const icon = item.querySelector('i');
                    item.classList.remove('active');
                    if (icon) icon.className = 'fas fa-sort';
                });
            };
            const ensureVisibleSort = () => {
                const visibleValidities = visibleValiditySet();
                if (sortState.validity && sortState.validity !== 'grand_total' && !visibleValidities.has(sortState.validity)) {
                    sortState = { key: 'name', direction: 'asc', validity: null, metric: null };
                    resetSortIconState();
                }
            };
            const updateColumnVisibility = () => {
                const visibleValidities = visibleValiditySet();
                scoped('[data-pv-validity-col]').forEach((column) => {
                    column.style.display = visibleValidities.has(column.dataset.pvValidityCol) ? '' : 'none';
                });
                scoped('[data-pv-contribution-col]').forEach((column) => {
                    column.style.display = visibleValidities.has(column.dataset.pvContributionCol) ? '' : 'none';
                });
                scoped('[data-pv-validity-group]').forEach((cell) => {
                    const validity = cell.dataset.pvValidityGroup;
                    const visible = visibleValidities.has(validity);
                    cell.hidden = !visible;
                    if (visible) {
                        const header = validityHeaders[validity] || {};
                        const label = activeCategory === 'all' ? (header.label || validity) : (header.short_label || header.label || validity);
                        cell.textContent = `VALIDITY ${label}`;
                    }
                });
                scoped('[data-pv-validity-cell]').forEach((cell) => {
                    cell.hidden = !visibleValidities.has(cell.dataset.pvValidityCell);
                });
                scoped('[data-pv-contribution-header], [data-pv-contribution-cell]').forEach((cell) => {
                    const validity = cell.dataset.pvContributionHeader || cell.dataset.pvContributionCell;
                    cell.hidden = !visibleValidities.has(validity);
                });
                const contributionGroup = root.querySelector('[data-pv-contribution-group]');
                if (contributionGroup) {
                    contributionGroup.colSpan = Math.max(visibleValidities.size, 1);
                    contributionGroup.hidden = visibleValidities.size === 0;
                }
            };
            const compareSources = (left, right) => {
                let leftValue;
                let rightValue;
                if (sortState.key === 'metric') {
                    if (sortState.metric === 'contribution') {
                        const leftTotal = Number(left?.values?.[activeCategory]?.grand_total?.mtd ?? 0);
                        const rightTotal = Number(right?.values?.[activeCategory]?.grand_total?.mtd ?? 0);
                        leftValue = leftTotal > 0 ? Number(left?.values?.[activeCategory]?.[sortState.validity]?.mtd ?? 0) / leftTotal : 0;
                        rightValue = rightTotal > 0 ? Number(right?.values?.[activeCategory]?.[sortState.validity]?.mtd ?? 0) / rightTotal : 0;
                    } else {
                        leftValue = Number(left?.values?.[activeCategory]?.[sortState.validity]?.[sortState.metric] ?? 0);
                        rightValue = Number(right?.values?.[activeCategory]?.[sortState.validity]?.[sortState.metric] ?? 0);
                    }
                } else {
                    leftValue = String(left?.[sortState.key] ?? '').toLocaleLowerCase('id-ID');
                    rightValue = String(right?.[sortState.key] ?? '').toLocaleLowerCase('id-ID');
                }

                const comparison = typeof leftValue === 'number'
                    ? leftValue - rightValue
                    : leftValue.localeCompare(rightValue, 'id-ID', { numeric: true });

                return sortState.direction === 'asc' ? comparison : -comparison;
            };

            const sortRows = (rows) => rows.sort((a, b) => {
                const comparison = compareSources(rowSource(a), rowSource(b));
                if (comparison !== 0) return comparison;

                return String(rowSource(a)?.name ?? '').localeCompare(String(rowSource(b)?.name ?? ''), 'id-ID', { numeric: true });
            });

            const sortVisibleRows = () => {
                const tbody = root.querySelector('.pv-table tbody');
                if (!tbody) return;
                const rows = Array.from(tbody.querySelectorAll(`[data-pv-row][data-dimension="${activeDimension}"]`));

                if (activeDimension === 'tap') {
                    const groupRows = rows.filter((row) => row.dataset.rowType === 'group');
                    const childrenByTap = new Map();
                    rows.filter((row) => row.dataset.rowType !== 'group').forEach((row) => {
                        const parentTap = row.dataset.parentTap || rowSource(row)?.tap || '';
                        if (!childrenByTap.has(parentTap)) childrenByTap.set(parentTap, []);
                        childrenByTap.get(parentTap).push(row);
                    });

                    groupRows
                        .sort((a, b) => {
                            const left = rowSource(a);
                            const right = rowSource(b);
                            const clusterDiff = Number(left?.cluster_order ?? 99) - Number(right?.cluster_order ?? 99);
                            if (clusterDiff !== 0) return clusterDiff;
                            const tapOrderDiff = Number(left?.tap_order ?? 999) - Number(right?.tap_order ?? 999);
                            if (tapOrderDiff !== 0) return tapOrderDiff;

                            return String(left?.name ?? '').localeCompare(String(right?.name ?? ''), 'id-ID', { numeric: true });
                        })
                        .forEach((groupRow) => {
                            tbody.appendChild(groupRow);
                            sortRows(childrenByTap.get(rowSource(groupRow)?.tap || '') || []).forEach((row) => tbody.appendChild(row));
                        });

                    return;
                }

                sortRows(rows).forEach((row) => tbody.appendChild(row));
            };

            const render = () => {
                ensureVisibleSort();
                updateColumnVisibility();
                scoped('[data-pv-row]').forEach((row) => {
                    row.hidden = row.dataset.dimension !== activeDimension;
                    if (row.hidden) return;
                    const source = rowsData[Number(row.dataset.rowIndex)];
                    if (!source) return;

                    row.querySelectorAll('[data-pv-metric]').forEach((cell) => {
                        const metric = cell.dataset.pvMetric;
                        const validity = cell.dataset.validity;
                        let value = source.values?.[activeCategory]?.[validity]?.[metric] ?? 0;
                        if (metric === 'contribution') {
                            const totalMtd = Number(source.values?.[activeCategory]?.grand_total?.mtd ?? 0);
                            const validityMtd = Number(source.values?.[activeCategory]?.[validity]?.mtd ?? 0);
                            value = totalMtd > 0 ? (validityMtd / totalMtd) * 100 : 0;
                            cell.textContent = formatPercent(value);
                        } else if (metric === 'mom') {
                            cell.innerHTML = momMarkup(value);
                        } else {
                            cell.textContent = formatNumber(value);
                        }
                    });
                });
                scoped('[data-pv-cluster-row]').forEach((row) => {
                    const summary = clusterSummaryData[Number(row.dataset.clusterIndex)] || {};
                    row.querySelectorAll('[data-pv-footer-metric]').forEach((cell) => {
                        const metric = cell.dataset.pvFooterMetric;
                        const validity = cell.dataset.validity;
                        let value = summary.values?.[activeCategory]?.[validity]?.[metric] ?? 0;
                        if (metric === 'contribution') {
                            const totalMtd = Number(summary.values?.[activeCategory]?.grand_total?.mtd ?? 0);
                            const validityMtd = Number(summary.values?.[activeCategory]?.[validity]?.mtd ?? 0);
                            value = totalMtd > 0 ? (validityMtd / totalMtd) * 100 : 0;
                            cell.textContent = formatPercent(value);
                        } else if (metric === 'mom') {
                            cell.innerHTML = momMarkup(value);
                        } else {
                            cell.textContent = formatNumber(value);
                        }
                    });
                });
                const heading = root.querySelector('[data-pv-dimension-heading]');
                if (heading) heading.textContent = dimensionLabels[activeDimension] || 'Nama';
                sortVisibleRows();
            };

            scoped('[data-pv-dimension]').forEach((button) => button.addEventListener('click', () => {
                activeDimension = button.dataset.pvDimension;
                scoped('[data-pv-dimension]').forEach((item) => item.classList.toggle('active', item === button));
                render();
            }));
            scoped('[data-pv-category]').forEach((button) => button.addEventListener('click', () => {
                activeCategory = button.dataset.pvCategory || fallbackCategory;
                scoped('[data-pv-category]').forEach((item) => item.classList.toggle('active', item === button));
                render();
            }));
            scoped('[data-pv-sort]').forEach((button) => button.addEventListener('click', () => {
                const key = button.dataset.pvSort;
                const isSame = sortState.key === key && sortState.validity === (button.dataset.validity || null) && sortState.metric === (button.dataset.metric || null);
                sortState = {
                    key,
                    validity: button.dataset.validity || null,
                    metric: button.dataset.metric || null,
                    direction: isSame && sortState.direction === 'asc' ? 'desc' : 'asc'
                };
                scoped('[data-pv-sort]').forEach((item) => {
                    const icon = item.querySelector('i');
                    item.classList.toggle('active', item === button);
                    if (icon) icon.className = item === button
                        ? `fas fa-sort-${sortState.direction === 'asc' ? 'up' : 'down'}`
                        : 'fas fa-sort';
                });
                sortVisibleRows();
            }));
            render();
        }

        window.addEventListener('load', () => {
            bindLeaderThresholds();
            initLeaderMap();
            bindLeaderMapToggles();
            bindFbShareToggles();
            bindLeaderMapZoom();
            bindActiveTableViewToggles();
            bindSidebarToggle();
            bindDashboardMenu();
            bindPerformanceMonitoringTables();
        });

        window.addEventListener('resize', () => {
            if (map) map.invalidateSize({ pan: false, animate: false });
            if (leaderMap) leaderMap.invalidateSize({ pan: false, animate: false });
        });

        function handleKeyUp(e) {
            const val = e.target.value;
            // Ignore arrow keys, enter, escape in keyup to prevent double execution
            if (["ArrowUp", "ArrowDown", "Enter", "Escape"].includes(e.key)) return;

            hideHistory();
            window.clearTimeout(suggestionTimer);
            suggestionTimer = window.setTimeout(() => showSuggestions(val), 280);
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
                    item.scrollIntoView({
                        block: 'nearest'
                    });
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
            const skeleton = document.getElementById('skeleton-loader');

            if (!keyword) return;

            // Start Loading State
            if (resultDiv) resultDiv.innerHTML = '';
            if (statsContainer) statsContainer.style.display = 'none';
            if (detailTable) detailTable.style.display = 'none';
            emptyState.style.display = 'none';
            // Kartu skeleton ini berasal dari detail outlet lama. Alur sekarang
            // langsung memusatkan peta dan membuka panel detail marker.
            skeleton.style.display = 'none';

            try {
                const response = await fetch(`/surya/search?keyword=${encodeURIComponent(keyword)}`);
                const data = await response.json();

                skeleton.style.display = 'none';

                if (data.length === 0) {
                    if (resultDiv) resultDiv.innerHTML =
                        '<div class="card" style="text-align:center; color:red;">Outlet tidak ditemukan</div>';
                    emptyState.style.display = 'block';
                    return;
                }

                const outlet = data[0];
                saveToHistory(outlet.id_outlet, outlet.nama_outlet);
                focusOutletOnMap(outlet);

                // Detail outlet cukup dibuka dari popup peta. Jangan render kartu/detail
                // panjang di bawah peta dan langsung perluas area peta saat pencarian.
                expandOutletMapView();
                document.getElementById('keyword').value = '';
                document.getElementById('suggestions').style.display = 'none';
                return;

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
                window.scrollTo({
                    top: statsContainer.offsetTop - 10,
                    behavior: 'smooth'
                });

            } catch (err) {
                skeleton.style.display = 'none';
                if (resultDiv) resultDiv.innerHTML = '<div class="card" style="color:red;">Error koneksi ke server.</div>';
            }
            document.getElementById('keyword').value = '';
            document.getElementById('suggestions').style.display = 'none';
        }

        function renderRincian(outlet) {
            const tableBody = document.getElementById('table-body');

            const formatDate = (dateStr) => {
                if (!dateStr || dateStr === '-') return '-';
                try {
                    const date = new Date(dateStr);
                    if (isNaN(date)) return dateStr;
                    const months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
                    return `${date.getDate()}-${months[date.getMonth()]}`;
                } catch (e) {
                    return dateStr;
                }
            };

            const parameters = [{
                    name: "TRX DIGIPOS",
                    fm1: outlet.fm1_digipos,
                    m1: outlet.m1_digipos,
                    mtd: outlet.m_digipos,
                    mom: outlet.mom_digipos,
                    update: outlet.tgl_pack
                },
                {
                    name: "SUPER SERU",
                    fm1: outlet.fm1_super,
                    m1: outlet.m1_super,
                    mtd: outlet.m_super,
                    mom: outlet.mom_super,
                    update: outlet.tgl_pack
                },
                {
                    name: "HOT PROMO",
                    fm1: outlet.fm1_hot,
                    m1: outlet.m1_hot,
                    mtd: outlet.m_hot,
                    mom: outlet.mom_hot,
                    update: outlet.tgl_pack
                },
                {
                    name: "COMSAK",
                    fm1: outlet.fm1_comsak,
                    m1: outlet.m1_comsak,
                    mtd: outlet.m_comsak,
                    mom: outlet.mom_comsak,
                    update: outlet.tgl_pack
                },
                {
                    name: "ST SA",
                    fm1: outlet.fm1_stsa,
                    m1: outlet.m1_stsa,
                    mtd: outlet.m_stsa,
                    mom: outlet.mom_stsa,
                    update: outlet.tgl_sa
                },
                {
                    name: "ST PV",
                    fm1: outlet.fm1_stpv,
                    m1: outlet.m1_stpv,
                    mtd: outlet.m_stpv,
                    mom: outlet.mom_stpv,
                    update: outlet.tgl_pv,
                    showDetail: true
                },
                {
                    name: "SO SA",
                    fm1: outlet.fm1_sosa,
                    m1: outlet.m1_sosa,
                    mtd: outlet.m_sosa,
                    mom: outlet.mom_sosa,
                    update: outlet.tgl_sa
                },
                {
                    name: "SO PV",
                    fm1: outlet.fm1_sopv,
                    m1: outlet.m1_sopv,
                    mtd: outlet.m_sopv,
                    mom: outlet.mom_sopv,
                    update: outlet.tgl_pv
                },
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
                        <td>
                            ${param.name}
                            ${param.showDetail ? ` <button class="btn-detail" onclick="showPerformanceDetail('${outlet.id_outlet}', '${outlet.nama_outlet}')"><i class="fas fa-chart-line"></i> Detail</button>` : ''}
                        </td>
                        <td>${Number(param.fm1 || 0).toLocaleString()}</td>
                        <td>${Number(param.m1 || 0).toLocaleString()}</td>
                        <td>${Number(param.mtd || 0).toLocaleString()}</td>
                        <td><div class="mom-indicator ${momClass}">${icon} ${param.mom || "0.0%"}</div></td>
                        <td>${formatDate(param.update)}</td>
                    </tr>
                `;
            });
        }

        async function loadAllOutlets() {
            const countEl = document.getElementById('map-outlet-count');

            try {
                const response = await fetch('/surya/outlets');
                if (!response.ok) throw new Error('Gagal memuat outlet');

                const outlets = await response.json();
                initMap(outlets);
                const visitedCount = outlets.filter(outlet => outlet.has_front_photo).length;
                countEl.textContent = `${outlets.length.toLocaleString()} outlet · ${visitedCount.toLocaleString()} sudah visit`;
            } catch (error) {
                countEl.textContent = 'Gagal memuat';
            }
        }

        function initMap(outlets) {
            map = L.map('map').setView([1.75, 101.05], 8);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            outletClusterLayer = L.markerClusterGroup({
                chunkedLoading: true,
                chunkInterval: 80,
                chunkDelay: 16,
                disableClusteringAtZoom: 16,
                maxClusterRadius: 55,
                removeOutsideVisibleBounds: true,
                showCoverageOnHover: false,
                spiderfyOnMaxZoom: true,
                chunkProgress: (processed, total) => {
                    if (processed === total && pendingOutletFocus) {
                        focusOutletOnMap(pendingOutletFocus);
                    }
                }
            }).addTo(map);
            // Semua outlet masuk cluster. Menampilkan ratusan thumbnail sekaligus
            // membuat browser lambat dan peta terasa belum selesai memuat.
            outletVisitedLayer = L.layerGroup();

            // Fit the map from raw coordinates first. Marker construction is
            // deliberately scheduled in small batches so tiles and controls
            // become usable before thousands of marker objects are created.
            const bounds = outlets
                .map((outlet) => [Number(outlet.latitude), Number(outlet.longitude)])
                .filter(([lat, lng]) => Number.isFinite(lat) && Number.isFinite(lng) && lat && lng);

            if (bounds.length) map.fitBounds(bounds, { padding: [24, 24] });
            addFullscreenControl();
            showUserLocation();
            queueOutletMarkers(outlets);

            // Leaflet may initialize while this panel is still transitioning from
            // hidden to visible. Recalculate the viewport after the layout settles
            // so tiles and markers never leave a gray/empty section.
            const refreshMapLayout = () => {
                if (map) map.invalidateSize({ pan: false, animate: false });
            };
            requestAnimationFrame(() => {
                refreshMapLayout();
                requestAnimationFrame(refreshMapLayout);
            });
            [150, 400, 800].forEach((delay) => setTimeout(refreshMapLayout, delay));
            if (window.ResizeObserver) {
                const observer = new ResizeObserver(refreshMapLayout);
                observer.observe(document.getElementById('map'));
                map._suryaResizeObserver = observer;
            }

        }

        function queueOutletMarkers(outlets) {
            let cursor = 0;
            const batchSize = 140;

            const renderBatch = () => {
                const batch = [];
                const end = Math.min(cursor + batchSize, outlets.length);
                for (; cursor < end; cursor++) {
                    const marker = addOutletMarker(outlets[cursor], false);
                    if (marker) batch.push(marker);
                }

                if (batch.length) outletClusterLayer?.addLayers(batch);

                if (cursor < outlets.length) {
                    (window.requestIdleCallback || window.requestAnimationFrame)(renderBatch);
                } else if (pendingOutletFocus) {
                    focusOutletOnMap(pendingOutletFocus);
                }
            };

            renderBatch();
        }

        function createOutletIcon(outlet = null, selected = false) {
            const visited = Boolean(outlet?.has_front_photo || outlet?.front_photo_visited_at || outlet?.front_thumbnail_url);
            const stateClass = visited ? ' outlet-marker-visited' : '';
            const content = '<div class="outlet-marker-pin"><i class="fas fa-store"></i></div>';
            return L.divIcon({
                className: `outlet-marker-icon${selected ? ' outlet-marker-selected' : ''}${stateClass}`,
                html: content,
                iconSize: selected ? [38, 42] : [30, 34],
                iconAnchor: selected ? [19, 40] : [15, 32],
                popupAnchor: selected ? [0, -38] : [0, -30]
            });
        }

        function addOutletMarker(outlet, addToCluster = true) {
            const outletId = String(outlet.id_outlet);
            if (outletMarkers.has(outletId)) return outletMarkers.get(outletId);

            const lat = Number(outlet.latitude);
            const lon = Number(outlet.longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lon) || !lat || !lon) return null;

            const marker = L.marker([lat, lon], {
                icon: createOutletIcon(outlet)
            });

            marker.outletData = outlet;
            marker.bindPopup(() => buildOutletPopup(marker.outletData, marker.getLatLng()));
            marker.on('popupopen', () => loadOutletDetailsForMarker(marker));

            markers.push(marker);
            outletMarkers.set(outletId, marker);
            if (addToCluster) {
                outletClusterLayer?.addLayer(marker);
            }
            return marker;
        }

        async function loadOutletDetailsForMarker(marker) {
            const outletId = String(marker.outletData?.id_outlet || '');
            if (!outletId) return;

            if (outletDetailsCache.has(outletId)) {
                applyOutletDetailsToMarker(marker, outletDetailsCache.get(outletId));
                return;
            }

            if (!outletDetailRequests.has(outletId)) {
                const request = fetch('/surya/search?keyword=' + encodeURIComponent(outletId), {
                    headers: { 'Accept': 'application/json' }
                })
                    .then((response) => {
                        if (!response.ok) throw new Error('HTTP ' + response.status);
                        return response.json();
                    })
                    .then((data) => {
                        const detail = Array.isArray(data) ? data[0] : null;
                        if (!detail) throw new Error('Outlet tidak ditemukan');
                        outletDetailsCache.set(outletId, detail);
                        return detail;
                    })
                    .finally(() => outletDetailRequests.delete(outletId));

                outletDetailRequests.set(outletId, request);
            }

            try {
                const detail = await outletDetailRequests.get(outletId);
                applyOutletDetailsToMarker(marker, detail);
            } catch (error) {
                // The lightweight popup remains usable even if the optional
                // detail/photo request fails.
                if (marker.isPopupOpen()) marker.setPopupContent(buildOutletPopup(marker.outletData, marker.getLatLng()));
            }
        }

        function applyOutletDetailsToMarker(marker, detail) {
            marker.outletData = { ...marker.outletData, ...detail };
            if (selectedOutletMarker === marker) {
                marker.setIcon(createOutletIcon(marker.outletData, true));
            }
            if (marker.isPopupOpen()) {
                marker.setPopupContent(buildOutletPopup(marker.outletData, marker.getLatLng()));
            }
        }

        function buildOutletPopup(outlet, outletLatLng) {
            const destination = `${outletLatLng.lat},${outletLatLng.lng}`;
            const cvm = Number(outlet.m_cvm || 0).toLocaleString('id-ID');
            const sa = Number(outlet.m_stsa || 0).toLocaleString('id-ID');
            const pv = Number(outlet.m_stpv || 0).toLocaleString('id-ID');
            let distanceHtml = '<div class="outlet-distance"><i class="fas fa-location-crosshairs"></i> Aktifkan lokasi untuk melihat jarak</div>';

            if (userLocationLatLng) {
                const distanceMeters = userLocationLatLng.distanceTo(outletLatLng);
                const distanceLabel = distanceMeters < 1000
                    ? `${Math.round(distanceMeters)} m`
                    : `${(distanceMeters / 1000).toFixed(2)} km`;
                distanceHtml = `<div class="outlet-distance"><i class="fas fa-route"></i> Jarak dari kamu: ${distanceLabel}</div>`;
            }

            const photoHtml = outlet.front_thumbnail_url
                ? `<a class="outlet-branding-photo" href="${escapeMapDetailHtml(outlet.front_photo_url || outlet.front_thumbnail_url)}" target="_blank" rel="noopener noreferrer"><img src="${escapeMapDetailHtml(outlet.front_thumbnail_url)}" alt="Tampak luar ${escapeMapDetailHtml(outlet.nama_outlet)}" loading="lazy"></a><small style="display:block;margin:0 0 7px;color:#64748b">Foto visit: ${mapDetailDate(outlet.front_photo_visited_at)}</small>`
                : '<div style="padding:7px 0;color:#64748b;font-size:11px">Memuat foto dan rincian outlet...</div>';

            return `
                <b>${outlet.nama_outlet}</b><br>
                ${outlet.id_outlet} • ${outlet.tap}
                ${photoHtml}
                <div class="outlet-metrics">
                    <div class="outlet-metric"><small>CVM TRX</small><strong>${cvm}</strong></div>
                    <div class="outlet-metric"><small>ST SA</small><strong>${sa}</strong></div>
                    <div class="outlet-metric"><small>ST PV</small><strong>${pv}</strong></div>
                </div>
                ${distanceHtml}
                <a class="outlet-navigation-link" href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destination)}" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-diamond-turn-right"></i> Go to Outlet
                </a>
                <button onclick="openMapOutletDetail('${outlet.id_outlet}')" style="margin-top:6px; background:#d10000; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; width:100%;">Lihat Detail</button>
            `;
        }

        function escapeMapDetailHtml(value) {
            return String(value ?? '-')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function ensureMapOutletDetailPanel() {
            let panel = document.getElementById('map-outlet-detail-panel');
            if (panel) return panel;

            panel = document.createElement('aside');
            panel.id = 'map-outlet-detail-panel';
            panel.className = 'map-outlet-detail-panel';
            panel.setAttribute('aria-live', 'polite');
            panel.innerHTML =
                '<div class="map-detail-header">' +
                    '<div><h3 id="map-detail-title">Detail Outlet</h3>' +
                    '<p id="map-detail-subtitle">Rincian parameter outlet</p></div>' +
                    '<button type="button" class="map-detail-close" onclick="closeMapOutletDetail()" aria-label="Tutup detail outlet">' +
                        '<i class="fas fa-times"></i>' +
                    '</button>' +
                '</div>' +
                '<div class="map-detail-body" id="map-detail-body"></div>';
            map.getContainer().appendChild(panel);
            L.DomEvent.disableClickPropagation(panel);
            L.DomEvent.disableScrollPropagation(panel);
            return panel;
        }

        function closeMapOutletDetail() {
            document.getElementById('map-outlet-detail-panel')?.classList.remove('is-open');
        }

        function mapDetailDate(dateStr) {
            if (!dateStr || dateStr === '-') return '-';
            const date = new Date(dateStr);
            if (Number.isNaN(date.getTime())) return escapeMapDetailHtml(dateStr);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return date.getDate() + '-' + months[date.getMonth()];
        }

        function mapDetailMom(value) {
            if (value === null || value === undefined || value === '') return '<span class="mom-indicator mom-neutral">–</span>';
            const raw = String(value ?? '0').replace('%', '');
            const numeric = Number.parseFloat(raw) || 0;
            const cssClass = numeric > 0 ? 'mom-positive' : (numeric < 0 ? 'mom-negative' : 'mom-neutral');
            const icon = numeric > 0 ? '↑' : (numeric < 0 ? '↓' : '−');
            const label = String(value ?? '0').includes('%') ? value : String(value ?? 0) + '%';
            return '<span class="mom-indicator ' + cssClass + '" style="padding:3px 6px; font-size:9px;">' +
                icon + ' ' + escapeMapDetailHtml(label) + '</span>';
        }

        function renderMapOutletDetail(outlet) {
            const parameters = [
                ['CVM', outlet.fm1_digipos, outlet.m1_digipos, outlet.m_digipos, outlet.mom_digipos, outlet.tgl_pack],
                ['SUPER SERU', outlet.fm1_super, outlet.m1_super, outlet.m_super, outlet.mom_super, outlet.tgl_pack],
                ['HOT PROMO', outlet.fm1_hot, outlet.m1_hot, outlet.m_hot, outlet.mom_hot, outlet.tgl_pack],
                ['COMSAK', outlet.fm1_comsak, outlet.m1_comsak, outlet.m_comsak, outlet.mom_comsak, outlet.tgl_pack],
                ['ST SA', outlet.fm1_stsa, outlet.m1_stsa, outlet.m_stsa, outlet.mom_stsa, outlet.tgl_sa],
                ['ST PV', outlet.fm1_stpv, outlet.m1_stpv, outlet.m_stpv, outlet.mom_stpv, outlet.tgl_pv],
                ['BIOMETRIK', outlet.fm1_bio, outlet.m1_bio, outlet.m_bio, outlet.mom_bio, outlet.tgl_bio]
            ];
            const number = value => Number(value || 0).toLocaleString('id-ID');
            const rows = parameters.map(parameter =>
                '<tr>' +
                    '<td>' + escapeMapDetailHtml(parameter[0]) + '</td>' +
                    '<td>' + number(parameter[1]) + '</td>' +
                    '<td>' + number(parameter[2]) + '</td>' +
                    '<td>' + number(parameter[3]) + '</td>' +
                    '<td>' + mapDetailMom(parameter[4]) + '</td>' +
                    '<td>' + mapDetailDate(parameter[5]) + '</td>' +
                '</tr>'
            ).join('');
            const outletId = escapeMapDetailHtml(outlet.id_outlet);
            const outletSf = escapeMapDetailHtml(outlet.sf);
            const outletTap = escapeMapDetailHtml(outlet.tap);
            const brandingPhoto = outlet.front_thumbnail_url
                ? '<div class="map-detail-branding">' +
                    '<a href="' + escapeMapDetailHtml(outlet.front_photo_url || outlet.front_thumbnail_url) + '" target="_blank" rel="noopener noreferrer">' +
                        '<img src="' + escapeMapDetailHtml(outlet.front_thumbnail_url) + '" alt="Foto branding tampak luar outlet" loading="lazy">' +
                    '</a>' +
                    '<div class="map-detail-branding-copy"><div><strong>Branding / Tampak Luar Terbaru</strong><small>Visit ' + mapDetailDate(outlet.front_photo_visited_at) + '</small></div><i class="fas fa-up-right-from-square"></i></div>' +
                  '</div>'
                : '<div class="map-detail-branding map-detail-branding-empty"><div><i class="fas fa-image"></i><br>Belum ada foto tampak luar dari check-in SF.</div></div>';
            const receiptPhotos = Array.isArray(outlet.receipt_photos) ? outlet.receipt_photos : [];
            const receiptPhoto = receiptPhotos.length
                ? receiptPhotos.map((photo, index) => '<div class="map-detail-branding map-detail-receipt">' +
                    '<a href="' + escapeMapDetailHtml(photo.url || photo.thumbnail_url) + '" target="_blank" rel="noopener noreferrer">' +
                        '<img src="' + escapeMapDetailHtml(photo.thumbnail_url || photo.url) + '" alt="Foto nota bon outlet ' + (index + 1) + '" loading="lazy">' +
                    '</a>' +
                    '<div class="map-detail-branding-copy"><div><strong>Foto Nota Bon ' + (index + 1) + '</strong><small>Transaksi ' + mapDetailDate(photo.date) + '</small></div><i class="fas fa-up-right-from-square"></i></div>' +
                  '</div>').join('')
                : '<div class="map-detail-branding map-detail-branding-empty"><div><i class="fas fa-receipt"></i><br>Belum ada foto nota bon.</div></div>';

            document.getElementById('map-detail-title').textContent = outlet.nama_outlet || 'Detail Outlet';
            document.getElementById('map-detail-subtitle').textContent =
                (outlet.id_outlet || '-') + ' • ' + (outlet.tap || '-');
            document.getElementById('map-detail-body').innerHTML =
                brandingPhoto +
                receiptPhoto +
                '<div class="map-detail-profile">' +
                    '<div class="map-detail-profile-item"><small>ID Outlet</small><strong title="' + outletId + '">' + outletId + '</strong></div>' +
                    '<div class="map-detail-profile-item"><small>Sales Force</small><strong title="' + outletSf + '">' + outletSf + '</strong></div>' +
                    '<div class="map-detail-profile-item"><small>TAP</small><strong title="' + outletTap + '">' + outletTap + '</strong></div>' +
                '</div>' +
                '<div class="map-detail-table-title">Rincian Parameter</div>' +
                '<div class="map-detail-table-wrap">' +
                    '<table class="map-detail-table">' +
                        '<thead><tr><th>Parameter</th><th>FM-1</th><th>M-1</th><th>MTD</th><th>MoM</th><th>Update</th></tr></thead>' +
                        '<tbody>' + rows + '</tbody>' +
                    '</table>' +
                '</div>';
        }

        async function openMapOutletDetail(id) {
            const mapElement = document.getElementById('map');
            if (!mapElement.classList.contains('map-fullscreen-active')) {
                selectFromMap(id);
                return;
            }

            const panel = ensureMapOutletDetailPanel();
            panel.classList.add('is-open');
            document.getElementById('map-detail-title').textContent = 'Memuat detail outlet...';
            document.getElementById('map-detail-subtitle').textContent = id;
            document.getElementById('map-detail-body').innerHTML =
                '<div class="map-detail-loading">' +
                    '<i class="fas fa-circle-notch fa-spin" style="font-size:24px; color:#d10000;"></i>' +
                    '<p style="margin-top:10px;">Mengambil rincian parameter...</p>' +
                '</div>';
            map.closePopup();

            try {
                const response = await fetch('/surya/search?keyword=' + encodeURIComponent(id));
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const data = await response.json();
                if (!Array.isArray(data) || !data.length) throw new Error('Outlet tidak ditemukan');

                renderMapOutletDetail(data[0]);
                const horizontalShift = window.innerWidth > 767 ? panel.offsetWidth / 2 : 0;
                const verticalShift = window.innerWidth <= 767 ? panel.offsetHeight / 3 : 0;
                map.panBy([horizontalShift, verticalShift], { animate: true });
            } catch (error) {
                document.getElementById('map-detail-title').textContent = 'Detail Outlet';
                document.getElementById('map-detail-body').innerHTML =
                    '<div class="map-detail-error">Rincian outlet gagal dimuat. Silakan coba lagi.</div>';
            }
        }

        function focusOutletOnMap(outlet) {
            const outletId = String(typeof outlet === 'object' ? outlet.id_outlet : outlet);
            let marker = outletMarkers.get(outletId);

            if (!map) {
                pendingOutletFocus = outlet;
                return;
            }

            if (!marker && typeof outlet === 'object') {
                marker = addOutletMarker(outlet);
                if (marker) {
                    document.getElementById('map-outlet-count').textContent = `${markers.length.toLocaleString()} outlet`;
                }
            }

            if (!marker) {
                return;
            }

            if (selectedOutletMarker && selectedOutletMarker !== marker) {
                selectedOutletMarker.setIcon(createOutletIcon(selectedOutletMarker.outletData));
                selectedOutletMarker.setZIndexOffset(0);
            }

            marker.setIcon(createOutletIcon(marker.outletData, true));
            marker.setZIndexOffset(1000);

            if (outletClusterLayer && outletClusterLayer.hasLayer(marker)) {
                outletClusterLayer.zoomToShowLayer(marker, () => {
                    map.setView(marker.getLatLng(), 17, { animate: true });
                    marker.openPopup();
                });
            } else {
                map.setView(marker.getLatLng(), 17, { animate: true });
                marker.openPopup();
            }

            selectedOutletMarker = marker;
            pendingOutletFocus = null;
        }

        function expandOutletMapView() {
            const mapElement = document.getElementById('map');
            if (!mapElement) return;
            const shell = document.getElementById('app-shell');
            if (window.innerWidth > 960 && shell && !shell.classList.contains('sidebar-collapsed')) {
                shell.dataset.mapAutoCollapsed = '1';
                shell.classList.add('sidebar-collapsed');
                document.getElementById('sidebar-toggle')?.setAttribute('aria-expanded', 'false');
            }
            // Gunakan mode yang sama persis dengan tombol ikon kotak di kanan atas.
            if (!mapElement.classList.contains('map-fullscreen-active')) {
                toggleMapFullscreen();
            }
        }

        function showUserLocation(focusAfter = false) {
            if (!navigator.geolocation) {
                if (focusAfter) alert('GPS tidak tersedia di browser ini.');
                return;
            }

            const locateIcon = document.querySelector('.map-locate-button i');
            if (focusAfter && locateIcon) locateIcon.className = 'fas fa-circle-notch fa-spin';

            navigator.geolocation.getCurrentPosition((position) => {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                userLocationLatLng = L.latLng(lat, lon);

                if (userLocationMarker) {
                    userLocationMarker.setLatLng(userLocationLatLng);
                } else {
                    userLocationMarker = L.circleMarker(userLocationLatLng, {
                        radius: 10,
                        color: '#fff',
                        weight: 3,
                        fillColor: '#0d6efd',
                        fillOpacity: 1
                    }).addTo(map);
                }

                userLocationMarker.bindPopup(
                    `<b><i class="fas fa-location-dot"></i> Posisi kamu</b><br><small>Akurasi ±${Math.round(position.coords.accuracy)} meter</small>`
                );

                if (selectedOutletMarker?.outletData) {
                    selectedOutletMarker.setPopupContent(
                        buildOutletPopup(selectedOutletMarker.outletData, selectedOutletMarker.getLatLng())
                    );
                }

                if (focusAfter) {
                    map.setView(userLocationLatLng, 17, { animate: true });
                    userLocationMarker.openPopup();
                }

                if (locateIcon) locateIcon.className = 'fas fa-location-crosshairs';
            }, (error) => {
                if (locateIcon) locateIcon.className = 'fas fa-location-crosshairs';
                if (focusAfter) {
                    alert(`Lokasi tidak dapat diakses: ${error.message}`);
                }
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 30000
            });
        }

        function addFullscreenControl() {
            const FullscreenControl = L.Control.extend({
                options: { position: 'topright' },
                onAdd() {
                    const button = L.DomUtil.create('button', 'map-fullscreen-button');
                    button.type = 'button';
                    button.title = 'Tampilkan peta layar penuh';
                    button.setAttribute('aria-label', 'Tampilkan peta layar penuh');
                    button.innerHTML = '<i class="fas fa-expand"></i>';
                    L.DomEvent.disableClickPropagation(button);
                    L.DomEvent.on(button, 'click', toggleMapFullscreen);
                    return button;
                }
            });

            const LocateControl = L.Control.extend({
                options: { position: 'topright' },
                onAdd() {
                    const button = L.DomUtil.create('button', 'map-locate-button');
                    button.type = 'button';
                    button.title = 'Tampilkan posisi saya';
                    button.setAttribute('aria-label', 'Tampilkan posisi saya');
                    button.innerHTML = '<i class="fas fa-location-crosshairs"></i>';
                    L.DomEvent.disableClickPropagation(button);
                    L.DomEvent.on(button, 'click', () => showUserLocation(true));
                    return button;
                }
            });

            map.addControl(new FullscreenControl());
            map.addControl(new LocateControl());
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && document.getElementById('map').classList.contains('map-fullscreen-active')) {
                    const detailPanel = document.getElementById('map-outlet-detail-panel');
                    if (detailPanel?.classList.contains('is-open')) {
                        closeMapOutletDetail();
                        return;
                    }
                    toggleMapFullscreen();
                }
            });
        }

        function toggleMapFullscreen() {
            const mapElement = document.getElementById('map');
            const isFullscreen = mapElement.classList.toggle('map-fullscreen-active');
            document.body.classList.toggle('map-fullscreen-open', isFullscreen);
            if (!isFullscreen) {
                closeMapOutletDetail();
                const shell = document.getElementById('app-shell');
                if (shell?.dataset.mapAutoCollapsed === '1') {
                    shell.classList.remove('sidebar-collapsed');
                    delete shell.dataset.mapAutoCollapsed;
                    document.getElementById('sidebar-toggle')?.setAttribute('aria-expanded', 'true');
                }
            }

            const icon = mapElement.querySelector('.map-fullscreen-button i');
            if (icon) icon.className = isFullscreen ? 'fas fa-compress' : 'fas fa-expand';

            const button = mapElement.querySelector('.map-fullscreen-button');
            if (button) {
                const label = isFullscreen ? 'Tutup layar penuh' : 'Tampilkan peta layar penuh';
                button.title = label;
                button.setAttribute('aria-label', label);
            }

            setTimeout(() => map.invalidateSize(), 100);
        }

        function selectFromMap(id) {
            document.getElementById('keyword').value = id;
            searchOutlet();
        }

        async function showSuggestions(q) {
            const box = document.getElementById('suggestions');
            if (q.length < 2) {
                box.style.display = 'none';
                return;
            }

            if (suggestionAbortController) suggestionAbortController.abort();
            suggestionAbortController = new AbortController();

            if (suggestionCache.has(q)) {
                renderSuggestions(suggestionCache.get(q), box);
                return;
            }

            try {
                const res = await fetch(`/surya/suggest?keyword=${encodeURIComponent(q)}`, {
                    signal: suggestionAbortController.signal,
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                suggestionCache.set(q, data);
                renderSuggestions(data, box);
            } catch (e) {
                if (e.name !== 'AbortError') box.style.display = 'none';
            }
        }

        function renderSuggestions(data, box) {
            box.innerHTML = '';
            selectedIndex = -1;

            if (data.length > 0) {
                data.forEach(item => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <b>${escapeMapDetailHtml(item.nama_outlet)}</b>
                        <small>${escapeMapDetailHtml(item.id_outlet)} • ${escapeMapDetailHtml(item.sf)} • ${escapeMapDetailHtml(item.tap)}</small>
                    `;
                    li.onclick = () => {
                        document.getElementById('keyword').value = item.id_outlet;
                        box.style.display = 'none';
                        searchOutlet();
                    };
                    box.appendChild(li);
                });
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
            }
        }

        function saveToHistory(id, name) {
            history = history.filter(h => h.id !== id);
            history.unshift({
                id,
                name
            });
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
                d.innerHTML = `<i class="fas fa-clock-rotate-left"></i> <span>${escapeMapDetailHtml(h.name)}</span><button type="button" class="history-item-remove" aria-label="Hapus riwayat ${escapeMapDetailHtml(h.name)}"><i class="fas fa-xmark"></i></button>`;
                d.onclick = () => {
                    document.getElementById('keyword').value = h.id;
                    searchOutlet();
                    hb.style.display = 'none';
                };
                d.querySelector('.history-item-remove').onclick = (event) => {
                    event.stopPropagation();
                    history = history.filter(item => item.id !== h.id);
                    localStorage.setItem('monita_history', JSON.stringify(history));
                    if (history.length === 0) {
                        hb.style.display = 'none';
                    } else {
                        showHistory();
                    }
                };
                hb.appendChild(d);
            });
            hb.style.display = 'block';
        }

        function hideHistory() {
            setTimeout(() => document.getElementById('history').style.display = 'none', 250);
        }

        // MODAL PERFORMANCE LOGIC
        async function showPerformanceDetail(id, name) {
            const modal = document.getElementById('performance-modal');
            const body = document.getElementById('modal-body');
            const title = document.getElementById('modal-title');

            title.innerHTML = `Performance PV: ${name}`;
            body.innerHTML = `
                <div style="text-align:center; padding:20px;">
                    <i class="fas fa-circle-notch fa-spin" style="font-size:24px; color:#d10000;"></i>
                    <p style="margin-top:10px; color:#666;">Mengambil data perdenom...</p>
                </div>
            `;
            modal.style.display = 'flex';

            try {
                const response = await fetch(`/surya/performance?id_outlet=${id}`);
                const data = await response.json();

                if (!data) {
                    body.innerHTML =
                        '<div style="text-align:center; padding:20px; color:#d10000;">Data performance tidak ditemukan.</div>';
                    return;
                }

                const denoms = [{
                        label: "1D",
                        m1: data['1d_m1'],
                        mtd: data['1d_m'],
                        mom: data['1d_mom']
                    },
                    {
                        label: "2D",
                        m1: data['2d_m1'],
                        mtd: data['2d_m'],
                        mom: data['2d_mom']
                    },
                    {
                        label: "3D",
                        m1: data['3d_m1'],
                        mtd: data['3d_m'],
                        mom: data['3d_mom']
                    },
                    {
                        label: "5D",
                        m1: data['5d_m1'],
                        mtd: data['5d_m'],
                        mom: data['5d_mom']
                    },
                    {
                        label: "7D",
                        m1: data['7d_m1'],
                        mtd: data['7d_m'],
                        mom: data['7d_mom']
                    },
                    {
                        label: "28D",
                        m1: data['28d_m1'],
                        mtd: data['28d_m'],
                        mom: data['28d_mom']
                    },
                    {
                        label: "30D",
                        m1: data['30d_m1'],
                        mtd: data['30d_m'],
                        mom: data['30d_mom']
                    },
                    {
                        label: "TOTAL PV",
                        m1: data['total_m1'],
                        mtd: data['total_m'],
                        mom: data['total_mom'],
                        isTotal: true
                    },
                ];

                let html = `
                    <div class="table-responsive">
                        <table style="min-width: unset; width: 100%;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="text-align:left; color:#d10000;">Denom</th>
                                    <th>M-1</th>
                                    <th>MTD</th>
                                    <th>MoM</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                denoms.forEach(d => {
                    const momVal = parseFloat((d.mom || "0").toString().replace('%', ''));
                    let momClass = "mom-neutral";
                    let icon = '';

                    if (momVal > 0) {
                        momClass = "mom-positive";
                        icon = '↑';
                    } else if (momVal < 0) {
                        momClass = "mom-negative";
                        icon = '↓';
                    }

                    html += `
                        <tr style="${d.isTotal ? 'font-weight:bold; background:#fff5f5;' : ''}">
                            <td style="text-align:left; font-size:12px;">${d.label}</td>
                            <td style="font-size:12px;">${Number(d.m1 || 0).toLocaleString()}</td>
                            <td style="font-size:12px;">${Number(d.mtd || 0).toLocaleString()}</td>
                            <td><span class="mom-indicator ${momClass}" style="padding:2px 6px; font-size:10px;">${icon} ${d.mom || "0%"}</span></td>
                        </tr>
                    `;
                });

                const formatDate = (dateStr) => {
                    if (!dateStr || dateStr === '-') return '-';
                    try {
                        const date = new Date(dateStr);
                        if (isNaN(date)) return dateStr;
                        const months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov",
                            "Des"
                        ];
                        return `${date.getDate()}-${months[date.getMonth()]}`;
                    } catch (e) {
                        return dateStr;
                    }
                };

                html += `
                            </tbody>
                        </table>
                    </div>
                    <p style="font-size:10px; color:#aaa; margin-top:15px; text-align:center;">Last Update: ${formatDate(data.tgl_update)}</p>
                `;
                body.innerHTML = html;

            } catch (err) {
                body.innerHTML =
                    '<div style="text-align:center; padding:20px; color:#d10000;">Gagal memuat data. Silakan coba lagi.</div>';
            }
        }

        function closePerformanceModal() {
            document.getElementById('performance-modal').style.display = 'none';
        }

        // Close on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('performance-modal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        window.onscroll = () => {
            const fab = document.getElementById('fab-top');
            if (document.documentElement.scrollTop > 200) fab.style.display = 'flex';
            else fab.style.display = 'none';
        };
    </script>
    <div class="visit-modal" id="visit-outlet-modal" hidden aria-hidden="true">
        <div class="visit-modal-card" role="dialog" aria-modal="true" aria-labelledby="visit-outlet-title">
            <div class="visit-modal-head"><h3 id="visit-outlet-title">Rincian Actual Call</h3><button type="button" class="visit-modal-close" aria-label="Tutup"><i class="fas fa-times"></i></button></div>
            <div class="visit-modal-body" id="visit-outlet-body"></div>
        </div>
    </div>
    <script>
        (() => {
            const visitRows = @json($visitDetails);
            const modal = document.getElementById('visit-outlet-modal');
            const body = document.getElementById('visit-outlet-body');
            const title = document.getElementById('visit-outlet-title');
            const esc = value => String(value ?? '—').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
            const formatDate = value => value ? new Intl.DateTimeFormat('id-ID', {dateStyle:'medium',timeStyle:'short'}).format(new Date(value)) : '—';
            const formatDuration = minutes => {
                if (minutes === null || minutes === undefined) return 'Belum checkout';
                const value = Number(minutes || 0);
                return value >= 60 ? `${Math.floor(value / 60)} jam ${value % 60} menit` : `${value} menit`;
            };

            document.querySelectorAll('[data-visit-subtab]').forEach(button => button.addEventListener('click', () => {
                const selectedTab = button.dataset.visitSubtab;
                document.querySelectorAll('[data-visit-subtab]').forEach(item => item.classList.toggle('active', item === button));
                document.querySelectorAll('[data-visit-subpanel]').forEach(panel => panel.hidden = panel.dataset.visitSubpanel !== selectedTab);

                const filterInput = document.getElementById('visit-tab-filter');
                if (filterInput) filterInput.value = selectedTab;

                const url = new URL(window.location.href);
                url.searchParams.set('panel', 'visit-detail');
                url.searchParams.set('visit_tab', selectedTab);
                url.hash = 'visit-detail';
                window.history.replaceState(null, '', url);

                const refresh = document.querySelector('.visit-refresh-button');
                if (refresh) {
                    const refreshUrl = new URL(refresh.href);
                    refreshUrl.searchParams.set('visit_tab', selectedTab);
                    refresh.href = refreshUrl.toString();
                }
            }));

            document.querySelector('[data-force-refresh]')?.addEventListener('click', event => {
                event.preventDefault();
                const url = new URL(event.currentTarget.href);
                url.searchParams.set('_refresh', Date.now().toString());
                window.location.assign(url.toString());
            });

            document.querySelectorAll('[data-visit-row]').forEach(button => button.addEventListener('click', () => {
                const rowIndex = Number(button.dataset.visitRow);
                const row = visitRows[rowIndex];
                if (!row) return;
                const scope = button.dataset.visitScope || 'actual';
                const labels = {'actual':'Actual Call','effective':'Effective Call','non-pjp':'Extra Call','non-buying':'Outlet Non Belanja'};
                const outlets = row.outlets.filter(outlet => {
                    if (scope === 'effective') return outlet.effective;
                    if (scope === 'non-pjp') return !outlet.is_pjp;
                    if (scope === 'non-buying') return !outlet.effective;
                    return true;
                });
                collapseSuryaSidebar();
                title.textContent = `${labels[scope] || 'Detail Visit'} · ${row.name}`;
                body.innerHTML = outlets.map((outlet) => {
                    const outletIndex = row.outlets.indexOf(outlet);
                    const photo = outlet.front_thumbnail_url
                        ? `<a href="${esc(outlet.front_photo_url || outlet.front_thumbnail_url)}" target="_blank" rel="noopener"><img class="visit-photo-thumb" src="${esc(outlet.front_thumbnail_url)}" alt="Foto tampak luar"></a>`
                        : '<div class="visit-photo-thumb" style="display:grid;place-items:center;background:#f1f5f9;color:#94a3b8"><i class="fas fa-image"></i></div>';
                    const activity = outlet.effective ? '<span class="cluster-chip" style="color:#047857;background:#ecfdf5">BELANJA</span>' : '<span class="cluster-chip">TIDAK BELANJA</span>';
                    return `<div class="visit-outlet-row">${photo}<div><strong>${esc(outlet.outlet_name)}</strong><small>${esc(outlet.id_outlet)} · ${outlet.is_pjp ? 'PJP' : 'EXTRA CALL'}</small></div><div><strong>Masuk ${formatDate(outlet.visited_at)}</strong><small>Keluar ${formatDate(outlet.checkout_at)}</small><small>Durasi ${esc(formatDuration(outlet.duration_minutes))}</small><small>${activity}</small></div><div class="visit-outlet-metric"><small>PV</small><button type="button" data-outlet-metric="pv" data-row-index="${rowIndex}" data-outlet-index="${outletIndex}">${Number(outlet.total_pv || 0).toLocaleString('id-ID')}</button></div><div class="visit-outlet-metric"><small>SA</small><button type="button" data-outlet-metric="sa" data-row-index="${rowIndex}" data-outlet-index="${outletIndex}">${Number(outlet.total_sa || 0).toLocaleString('id-ID')}</button></div><div class="visit-outlet-metric"><small>Konsinyasi</small><button type="button" data-outlet-metric="consignment" data-row-index="${rowIndex}" data-outlet-index="${outletIndex}">${Number(outlet.consignment_qty || 0).toLocaleString('id-ID')}</button></div><div class="visit-outlet-detail" data-outlet-detail="${rowIndex}-${outletIndex}"></div></div>`;
                }).join('') || '<div class="visit-empty"><strong>Belum ada rincian outlet.</strong></div>';
                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
            }));

            body.addEventListener('click', event => {
                const button = event.target.closest('[data-outlet-metric]');
                if (!button) return;
                const row = visitRows[Number(button.dataset.rowIndex)];
                const outlet = row?.outlets?.[Number(button.dataset.outletIndex)];
                const detail = body.querySelector(`[data-outlet-detail="${button.dataset.rowIndex}-${button.dataset.outletIndex}"]`);
                if (!outlet || !detail) return;
                const type = button.dataset.outletMetric;
                let heading = 'Detail';
                let values = [];
                if (type === 'pv') { heading = 'Detail Penjualan PV'; values = outlet.pv_items || []; }
                if (type === 'sa') { heading = 'Detail Penjualan SA'; values = outlet.sa_items || []; }
                if (type === 'consignment') { heading = 'Nomor Konsinyasi'; values = (outlet.consignment_numbers || []).map(number => ({name:number, qty:null})); }
                detail.innerHTML = `<strong>${heading}</strong>${values.length ? `<ul>${values.map(item => `<li>${esc(item.name)}${item.qty === null ? '' : ` — ${Number(item.qty).toLocaleString('id-ID')} PCS`}</li>`).join('')}</ul>` : '<small>Tidak ada data.</small>'}`;
                detail.classList.toggle('active', true);
            });

            const close = () => { modal.hidden = true; modal.setAttribute('aria-hidden', 'true'); };
            modal.querySelector('.visit-modal-close').addEventListener('click', close);
            modal.addEventListener('click', event => { if (event.target === modal) close(); });
            document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) close(); });
        })();

        document.querySelectorAll('.reject-photo-form').forEach((form) => form.addEventListener('submit', (event) => {
            event.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Tolak foto visit?',
                text: 'Sales Force harus mengambil ulang foto tampak depan dan foto etalase.',
                showCancelButton: true,
                confirmButtonText: 'Ya, Tolak Foto',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d71920',
                cancelButtonColor: '#64748b',
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        }));

        // Approval is handled by the server-side poller below. Prevent double
        // clicks while the request is in flight, which also avoids stale-row
        // submissions when the list is refreshed.
        document.querySelectorAll('.visit-action-buttons form').forEach((form) => {
            form.addEventListener('submit', () => {
                form.querySelectorAll('button').forEach((button) => {
                    button.disabled = true;
                    button.textContent = 'Memproses...';
                });
            });
        });

        @if(request('panel') === 'visit-detail' && request('visit_tab') === 'approval')
        setInterval(() => {
            fetch(@json(route('surya.visits.auto-approve-pending')) + '?_=' + Date.now(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            }).then(response => response.ok ? response.json() : null)
              .then(result => {
                  if (result?.approved > 0) window.location.reload();
              })
              .catch(() => {});
        }, 5000);
        @endif

        // Satu controller untuk pencarian, filter, dropdown sort, dan sort header.
        (() => {
            const tools = document.querySelector('[data-visit-tools]');
            const table = document.querySelector('.visit-summary-table');
            const body = table?.tBodies?.[0];
            if (!tools || !table || !body) return;

            const rows = Array.from(body.querySelectorAll('tr[data-visit-summary-row]'));
            const originalIndex = new Map(rows.map((row, index) => [row, index]));
            const search = tools.querySelector('[data-visit-search]');
            const tap = tools.querySelector('[data-visit-tap]');
            const cluster = tools.querySelector('[data-visit-cluster]');
            const sortSelect = tools.querySelector('[data-visit-sort]');
            const headers = Array.from(table.querySelectorAll('th[data-sort]'));
            const numericFields = new Set(['pjp', 'actual', 'visitPercent', 'effective', 'nonPjp', 'nonBuying', 'pv', 'sa']);
            const collator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });
            let sortKey = '';
            let sortDirection = 'asc';

            const setHeaderState = () => headers.forEach(header => {
                const active = header.dataset.sort === sortKey;
                header.classList.toggle('sort-active', active);
                header.dataset.direction = active ? sortDirection : '';
                header.setAttribute('aria-sort', active ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none');
            });

            const compareRows = (a, b) => {
                if (!sortKey) return originalIndex.get(a) - originalIndex.get(b);
                const av = a.dataset[sortKey] ?? '';
                const bv = b.dataset[sortKey] ?? '';
                const result = numericFields.has(sortKey)
                    ? Number(av || 0) - Number(bv || 0)
                    : collator.compare(String(av), String(bv));
                return (sortDirection === 'desc' ? -result : result) || originalIndex.get(a) - originalIndex.get(b);
            };

            const apply = () => {
                const term = String(search?.value || '').trim().toLocaleLowerCase('id');
                const tapValue = String(tap?.value || '').toLocaleLowerCase('id');
                const clusterValue = String(cluster?.value || '').toLocaleLowerCase('id');
                const visible = rows.filter(row => {
                    const text = row.textContent.toLocaleLowerCase('id');
                    return (!term || text.includes(term))
                        && (!tapValue || String(row.dataset.tap || '').toLocaleLowerCase('id') === tapValue)
                        && (!clusterValue || String(row.dataset.cluster || '').toLocaleLowerCase('id') === clusterValue);
                }).sort(compareRows);
                const visibleSet = new Set(visible);
                visible.forEach(row => { row.hidden = false; row.style.display = ''; body.appendChild(row); });
                rows.filter(row => !visibleSet.has(row)).forEach(row => { row.hidden = true; row.style.display = 'none'; body.appendChild(row); });
                setHeaderState();
            };

            [search, tap, cluster].forEach(control => {
                control?.addEventListener('input', apply);
                control?.addEventListener('change', apply);
            });
            sortSelect?.addEventListener('change', () => {
                const parts = String(sortSelect.value || '').split('-');
                sortKey = parts[0] || '';
                sortDirection = parts[1] || 'asc';
                apply();
            });
            headers.forEach(header => header.addEventListener('click', () => {
                const nextKey = header.dataset.sort;
                sortDirection = sortKey === nextKey && sortDirection === 'asc' ? 'desc' : 'asc';
                sortKey = nextKey;
                const matchingOption = Array.from(sortSelect?.options || []).find(option => option.value === `${sortKey}-${sortDirection}`);
                if (sortSelect) sortSelect.value = matchingOption ? matchingOption.value : '';
                apply();
            }));
            setHeaderState();
        })();
    </script>
</body>

</html>
