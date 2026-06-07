<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Outlet</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap"
        rel="stylesheet">
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
            font-family: "Plus Jakarta Sans", sans-serif;
        }

        body {
            background: #eef2f6;
            color: #20293a;
            font-family: "Plus Jakarta Sans", Arial, sans-serif;
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
    </style>
</head>

<body>
    <header class="header">
        <h1>Monita Dumai (Monitoring Outlet Aktif)</h1>
    </header>

    <main class="container">
        @php
            $summary = $dashboard['summary'] ?? [];
            $areas = $dashboard['areas'] ?? [];
            $riskTaps = $dashboard['riskTaps'] ?? [];
            $growthTaps = $dashboard['growthTaps'] ?? [];
            $leaderActions = $dashboard['leaderActions'] ?? [];
            $clusterCoverage = $dashboard['clusterCoverage']['groups'] ?? [];
            $monitoringUpdateDate = $dashboard['monitoringUpdateDate'] ?? null;
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
            <section class="leader-dashboard">
                <div class="leader-top">
                    <div class="leader-intro">
                        <div class="leader-eyebrow">Leader command center</div>
                        <h2 class="leader-title">Analisa outlet Dumai, Rohil, dan Bengkalis</h2>
                        <p class="leader-subtitle">
                            Dashboard ini mengonsolidasikan performa outlet dari appsdumais serta data ST SA dan ST PV
                            dari outlet_performance.
                            Analisa disajikan untuk mendukung prioritas kunjungan, evaluasi produktivitas wilayah, dan
                            pengambilan keputusan leader secara terukur.
                        </p>
                        <div class="leader-top-actions">
                            <div class="leader-pill"><i class="fas fa-shield-halved"></i> Akses Monita aktif</div>
                            <a href="{{ route('monita.leader.logout') }}" class="leader-logout"><i
                                    class="fas fa-arrow-right-from-bracket"></i> Logout</a>
                        </div>

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
                            <i class="fas fa-chart-simple"></i> TRX CVM
                        </button>
                        <button type="button" class="map-toggle" data-map-mode="st_pv">
                            <i class="fas fa-bolt"></i> ST PV
                        </button>
                        <button type="button" class="map-toggle" data-map-mode="st_sa">
                            <i class="fas fa-sim-card"></i> ST SA
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
        @else
            <section class="leader-lock">
                <div class="leader-lock-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div>
                    <h2>Dashboard leader terkunci</h2>
                    <p>Search outlet tetap aktif untuk Telegram. Analisa wilayah, peta performa, dan call to action
                        hanya tampil setelah login internal.</p>
                </div>
                <a href="{{ route('monita.leader.login') }}" class="leader-login-btn">
                    <i class="fas fa-right-to-bracket"></i> Login Dashboard
                </a>
            </section>
        @endif

        <h2 class="search-panel-title">Cari dan bedah outlet</h2>
        <!-- Search Box -->
        <div class="search-box">
            <div class="input-wrapper">
                <input type="text" id="keyword" placeholder="Ketik ID atau Nama Outlet..."
                    onkeyup="handleKeyUp(event)" onkeydown="handleKeyDown(event)" onfocus="showHistory()">
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
        let history = JSON.parse(localStorage.getItem('monita_history') || '[]');
        let selectedIndex = -1;
        const leaderAreas = @json($showLeaderDashboard ? $areas : []);
        const leaderOutletPoints = @json($showLeaderDashboard ? $dashboard['outletPoints'] ?? [] : []);
        const leaderCoveragePoints = @json($showLeaderDashboard ? $dashboard['coveragePoints'] ?? [] : []);
        const leaderCompetitionPoints = @json($showLeaderDashboard ? $dashboard['competitionPoints'] ?? [] : []);
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
                all: `Hijau berarti capai minimal salah satu target dan MoM positif/stabil. Oranye berarti capai min tapi MoM minus. Merah berarti belum capai min apa pun.`,
                st_sa: `Hijau berarti ST SA minimal ${leaderThresholds.st_sa} dan MoM positif/stabil. Oranye berarti capai min tapi MoM minus. Merah berarti belum capai min SA.`,
                st_pv: `Hijau berarti ST PV minimal ${leaderThresholds.st_pv} dan MoM positif/stabil. Oranye berarti capai min tapi MoM minus. Merah berarti belum capai min PV.`,
                trx_cvm: `Hijau berarti TRX CVM minimal ${leaderThresholds.trx_cvm} dan MoM positif/stabil. Oranye berarti capai min tapi MoM minus. Merah berarti belum capai min CVM.`
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

        window.addEventListener('load', () => {
            bindLeaderThresholds();
            initLeaderMap();
            bindLeaderMapToggles();
            bindFbShareToggles();
            bindLeaderMapZoom();
            bindActiveTableViewToggles();
        });

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
                    resultDiv.innerHTML =
                        '<div class="card" style="text-align:center; color:red;">Outlet tidak ditemukan</div>';
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
                window.scrollTo({
                    top: statsContainer.offsetTop - 10,
                    behavior: 'smooth'
                });

            } catch (err) {
                skeleton.style.display = 'none';
                resultDiv.innerHTML = '<div class="card" style="color:red;">Error koneksi ke server.</div>';
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
                    name: "ST SA",
                    m1: outlet.m1_stsa,
                    mtd: outlet.m_stsa,
                    mom: outlet.mom_stsa,
                    update: outlet.tgl_sa
                },
                {
                    name: "ST PV",
                    m1: outlet.m1_stpv,
                    mtd: outlet.m_stpv,
                    mom: outlet.mom_stpv,
                    update: outlet.tgl_pv,
                    showDetail: true
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
                        <td>${Number(param.m1 || 0).toLocaleString()}</td>
                        <td>${Number(param.mtd || 0).toLocaleString()}</td>
                        <td><div class="mom-indicator ${momClass}">${icon} ${param.mom || "0.0%"}</div></td>
                        <td>${formatDate(param.update)}</td>
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

            resultDiv.innerHTML =
                '<div class="card" style="text-align:center;"><i class="fas fa-circle-notch fa-spin"></i> Getting GPS...</div>';
            emptyState.style.display = 'none';
            statsContainer.style.display = 'none';
            detailTable.style.display = 'none';
            mapContainer.style.display = 'none';

            navigator.geolocation.getCurrentPosition(async (pos) => {
                const {
                    latitude: lat,
                    longitude: lon
                } = pos.coords;
                const radiusSelect = document.getElementById('scan-radius');
                const radiusValue = radiusSelect.value;
                const radiusLabel = radiusSelect.options[radiusSelect.selectedIndex].text;

                resultDiv.innerHTML =
                    `<div class="card" style="text-align:center;"><i class="fas fa-satellite-dish fa-spin"></i> Scanning ${radiusLabel}...</div>`;

                try {
                    const response = await fetch(
                        `/monitadumai/nearby?latitude=${lat}&longitude=${lon}&radius=${radiusValue}`);
                    const data = await response.json();

                    if (data.length === 0) {
                        resultDiv.innerHTML =
                            `<div class="card" style="text-align:center; color:#d10000; font-weight:bold;">Tidak ada outlet dalam radius ${radiusLabel}.</div>`;
                        emptyState.style.display = 'block';
                        return;
                    }

                    mapContainer.style.display = 'block';
                    setTimeout(() => initMap(lat, lon, data), 100);

                    resultDiv.innerHTML =
                        `<h3 style="margin:20px 0 12px 10px; font-size:18px;"><i class="fas fa-map-marked-alt"></i> Outlet Nearby: (${data.length} Found)</h3>`;
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
                                    SA: ${Number(outlet.m_stsa).toLocaleString()} | PV: ${Number(outlet.m_stpv).toLocaleString()} | CVM TRX: ${Number(outlet.m_cvm).toLocaleString()}
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
                } catch (e) {
                    resultDiv.innerHTML = '<div class="card">Server Error.</div>';
                }
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
            L.circleMarker([lat, lon], {
                color: '#0d6efd',
                radius: 10,
                weight: 3,
                fillOpacity: 0.8
            }).addTo(map).bindPopup("Kamu");
            outlets.forEach(o => {
                if (o.latitude && o.longitude) {
                    const m = L.marker([o.latitude, o.longitude]).addTo(map).bindPopup(
                        `<b>${o.nama_outlet}</b><br><button onclick="selectFromMap('${o.id_outlet}')" style="margin-top:6px; background:#d10000; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; width:100%;">Pilih Outlet</button>`
                    );
                    markers.push(m);
                }
            });
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
                            <small>${item.id_outlet} • ${item.sf} • ${item.tap}</small>
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
            } catch (e) {}
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
                d.innerHTML = `<i class="fas fa-clock-rotate-left"></i> <span>${h.name}</span>`;
                d.onclick = () => {
                    document.getElementById('keyword').value = h.id;
                    searchOutlet();
                    hb.style.display = 'none';
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
                const response = await fetch(`/monitadumai/performance?id_outlet=${id}`);
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
</body>

</html>
