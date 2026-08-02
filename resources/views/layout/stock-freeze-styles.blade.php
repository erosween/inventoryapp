<style>
    /* Shared freeze-pane contract for Stock All, Stock Gudang, and Stock SF. */
    .stock-freeze {
        --freeze-no-width: 48px;
        --freeze-tap-width: 148px;
        --freeze-sf-width: 190px;
        position: relative;
        isolation: isolate;
        overflow: auto;
        max-width: 100%;
        overscroll-behavior-x: contain;
        scrollbar-gutter: stable;
        -webkit-overflow-scrolling: touch;
    }

    /* Compact navigation and controls shared by all three stock views. */
    .stock-card > .card-header {
        padding: 14px 18px !important;
    }

    .stock-card .nav-pills { gap: 8px !important; }
    .stock-card .nav-pills .nav-link {
        padding: 9px 16px !important;
        border-radius: 10px !important;
        font-size: 12px;
        box-shadow: none !important;
    }

    .stock-table-toolbar {
        position: relative;
        z-index: 5;
        min-height: 62px;
        padding: 12px 18px;
        border-bottom: 1px solid #e7eaf1;
        background: #fff;
    }

    .stock-table-toolbar .top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        width: 100%;
    }

    .stock-table-toolbar .dt-buttons { display: flex; float: none !important; gap: 7px; }
    .stock-table-toolbar .dt-button {
        margin: 0 !important;
        padding: 8px 12px !important;
        border: 1px solid #dfe3eb !important;
        border-radius: 8px !important;
        background: #fff !important;
        color: #536076 !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        box-shadow: none !important;
    }
    .stock-table-toolbar .dt-button:hover { border-color: #c8c4fa !important; background: #f4f3ff !important; color: #4f46e5 !important; }
    .stock-table-toolbar .dataTables_filter { float: none !important; margin: 0; }
    .stock-table-toolbar .dataTables_filter label { display: flex; align-items: center; gap: 8px; margin: 0; color: #7b8497; font-size: 11px; font-weight: 600; }
    .stock-table-toolbar .dataTables_filter input { width: 210px; height: 38px; margin: 0 !important; padding: 0 12px !important; }

    .stock-table-footer {
        position: relative;
        z-index: 5;
        min-height: 64px;
        padding: 12px 18px;
        border-top: 1px solid #e7eaf1;
        background: #fff;
    }

    .stock-table-footer .bottom {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 16px;
        width: 100%;
    }
    .stock-table-footer .dataTables_length,
    .stock-table-footer .dataTables_info,
    .stock-table-footer .dataTables_paginate { float: none !important; margin: 0 !important; padding: 0 !important; }
    .stock-table-footer .dataTables_info { justify-self: center; color: #8a93a4; font-size: 11px; }
    .stock-table-footer .dataTables_paginate { justify-self: end; }
    .stock-table-footer .dataTables_length label { margin: 0; color: #7b8497; font-size: 11px; }
    .stock-table-footer .dataTables_length select { height: 36px; margin: 0 5px; padding: 0 24px 0 9px; }

    .stock-freeze #stock {
        width: max-content !important;
        min-width: 100% !important;
        table-layout: auto;
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }

    .stock-freeze #stock th,
    .stock-freeze #stock td {
        box-sizing: border-box;
        white-space: nowrap !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
        background-clip: padding-box;
    }

    /* Fixed dimensions: offsets below always equal the preceding column width. */
    .stock-freeze #stock .sticky-no {
        left: 0 !important;
        width: var(--freeze-no-width) !important;
        min-width: var(--freeze-no-width) !important;
        max-width: var(--freeze-no-width) !important;
        text-align: center !important;
    }

    .stock-freeze #stock .sticky-tap {
        left: var(--freeze-no-width) !important;
        width: var(--freeze-tap-width) !important;
        min-width: var(--freeze-tap-width) !important;
        max-width: var(--freeze-tap-width) !important;
    }

    .stock-freeze--three #stock .sticky-sf {
        left: calc(var(--freeze-no-width) + var(--freeze-tap-width)) !important;
        width: var(--freeze-sf-width) !important;
        min-width: var(--freeze-sf-width) !important;
        max-width: var(--freeze-sf-width) !important;
    }

    /* Vertical header grid uses explicit row heights to prevent overlap while scrolling. */
    .stock-freeze #stock thead tr.voucher-header th { top: 0 !important; height: 30px; }
    .stock-freeze #stock thead tr.group-header th { top: 30px !important; height: 30px; }
    .stock-freeze #stock thead tr.sub-header th { top: 60px !important; height: 38px; }

    .stock-freeze #stock thead tr.group-header th.th-group {
        background: #655edb !important;
        color: #fff !important;
        border-color: rgba(255, 255, 255, .2) !important;
    }

    .stock-freeze #stock .voucher-label,
    .stock-freeze #stock .validity-label {
        position: sticky;
        left: calc(var(--freeze-no-width) + var(--freeze-tap-width) + 12px);
        display: inline-flex;
        align-items: center;
        width: max-content;
        min-height: 20px;
        padding: 0 8px;
        border-radius: 5px;
        color: #fff;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .06em;
        line-height: 20px;
        text-transform: uppercase;
    }

    .stock-freeze--three #stock .voucher-label,
    .stock-freeze--three #stock .validity-label {
        left: calc(var(--freeze-no-width) + var(--freeze-tap-width) + var(--freeze-sf-width) + 12px);
    }

    .stock-freeze #stock .voucher-label { background: rgba(15, 23, 42, .28); }
    .stock-freeze #stock .validity-label { background: rgba(255, 255, 255, .13); }

    /* Daily stock uses one stable leaf header per data column. */
    .stock-freeze--daily #sisastock {
        width: max-content !important;
        min-width: 100% !important;
        table-layout: auto;
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }
    .stock-freeze--daily #sisastock th,
    .stock-freeze--daily #sisastock td {
        box-sizing: border-box;
        white-space: nowrap !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
        background-clip: padding-box;
    }
    .stock-freeze--daily #sisastock .sticky-no {
        left: 0 !important;
        width: var(--freeze-no-width) !important;
        min-width: var(--freeze-no-width) !important;
        max-width: var(--freeze-no-width) !important;
        text-align: center !important;
    }
    .stock-freeze--daily #sisastock .sticky-tap {
        left: var(--freeze-no-width) !important;
        width: var(--freeze-tap-width) !important;
        min-width: var(--freeze-tap-width) !important;
        max-width: var(--freeze-tap-width) !important;
    }
    .stock-freeze--daily.has-sf-freeze #sisastock .sticky-sf {
        position: sticky !important;
        left: calc(var(--freeze-no-width) + var(--freeze-tap-width)) !important;
        width: var(--freeze-sf-width) !important;
        min-width: var(--freeze-sf-width) !important;
        max-width: var(--freeze-sf-width) !important;
    }
    .stock-freeze--daily #sisastock thead tr.stock-leaf-header th {
        top: 0 !important;
        height: 72px;
        min-width: 104px;
        z-index: 40;
        padding: 7px 8px !important;
        background:#5149c9 !important;
        color:#fff !important;
        text-align:center;
        border-right:1px solid rgba(255,255,255,.14) !important;
    }
    .stock-freeze--daily #sisastock thead th.th-voucher-byu { background:#5d55d5 !important; }
    .stock-freeze--daily #sisastock thead th.th-voucher-sa { background:#655edb !important; }
    .stock-freeze--daily #sisastock .stock-leaf-category,
    .stock-freeze--daily #sisastock .stock-leaf-validity,
    .stock-freeze--daily #sisastock .stock-leaf-denom { display:block; white-space:nowrap; }
    .stock-freeze--daily #sisastock .stock-leaf-category {
        width:max-content;
        margin:0 auto 4px;
        padding:2px 7px;
        border-radius:5px;
        background:rgba(255,255,255,.14);
        font-size:8px;
        font-weight:800;
        letter-spacing:.08em;
    }
    .stock-freeze--daily #sisastock .stock-leaf-validity { font-size:8px; opacity:.82; margin-bottom:2px; }
    .stock-freeze--daily #sisastock .stock-leaf-denom { font-size:9px; font-weight:800; }
    .stock-freeze--daily #sisastock thead .sticky-no,
    .stock-freeze--daily #sisastock thead .sticky-tap {
        position: sticky !important;
        top: 0 !important;
        z-index: 90 !important;
        background: #5149c9 !important;
        color: #fff !important;
    }
    .stock-freeze--daily.has-sf-freeze #sisastock thead .sticky-sf {
        top: 0 !important;
        z-index: 91 !important;
        background: #5149c9 !important;
        color: #fff !important;
    }
    .stock-freeze--daily #sisastock tbody .sticky-no,
    .stock-freeze--daily #sisastock tbody .sticky-tap {
        position: sticky !important;
        z-index: 24 !important;
        background: #fff !important;
        color: #172033 !important;
    }
    .stock-freeze--daily.has-sf-freeze #sisastock tbody .sticky-sf {
        z-index: 25 !important;
        background: #fff !important;
        color: #172033 !important;
    }
    .stock-freeze--daily #sisastock tbody tr:nth-child(even) .sticky-no,
    .stock-freeze--daily #sisastock tbody tr:nth-child(even) .sticky-tap { background: #f8fafc !important; }
    .stock-freeze--daily.has-sf-freeze #sisastock tbody tr:nth-child(even) .sticky-sf { background:#f8fafc !important; }
    .stock-freeze--daily #sisastock tbody tr:hover .sticky-no,
    .stock-freeze--daily #sisastock tbody tr:hover .sticky-tap { background: #f4f3ff !important; }
    .stock-freeze--daily #sisastock tfoot th { position: sticky !important; bottom: 0; z-index: 60; }
    .stock-freeze--daily #sisastock tfoot .sticky-no,
    .stock-freeze--daily #sisastock tfoot .sticky-tap {
        z-index: 80 !important;
        background: #25224f !important;
        color: #fff !important;
    }
    .stock-freeze--daily.has-sf-freeze #sisastock tfoot .sticky-sf {
        z-index: 81 !important;
        background: #25224f !important;
        color: #fff !important;
    }
    .stock-freeze--daily #sisastock .sticky-tap {
        border-right: 1px solid #d9ddea !important;
        box-shadow: 10px 0 16px -14px rgba(23, 32, 51, .75) !important;
    }
    .stock-freeze--daily.has-sf-freeze #sisastock .sticky-tap { border-right:0 !important; box-shadow:none !important; }
    .stock-freeze--daily.has-sf-freeze #sisastock .sticky-sf {
        border-right:1px solid #d9ddea !important;
        box-shadow:10px 0 16px -14px rgba(23,32,51,.75) !important;
    }
    .stock-freeze--daily #sisastock .sticky-total,
    .stock-freeze--daily #sisastock .sticky-total-col,
    .stock-freeze--daily #sisastock .sticky-total-footer { right: 0 !important; }

    .daily-date-filter {
        max-width: 250px;
        overflow: hidden;
        border: 1px solid #dfe3eb;
        border-radius: 10px;
        background: #f8f9fc;
        box-shadow: none !important;
    }
    .daily-date-filter .form-control { height: 42px; color: #334155; }

    /* Daily validity subtotals keep the original yellow accounting cue. */
    .stock-freeze--daily #sisastock thead tr.stock-leaf-header th.th-validity-total {
        background:#eee6c7 !important;
        color:#292a35 !important;
        border-color:#dfd3a5 !important;
    }
    .stock-freeze--daily #sisastock tbody td.validity-total {
        background:#fbf8ec !important;
        color:#292a35 !important;
    }
    .stock-freeze--daily #sisastock tfoot th.validity-total-footer {
        background:#e9e1c2 !important;
        color:#292a35 !important;
    }
    .stock-freeze--daily #sisastock thead th.daily-validity-summary {
        min-width:104px;
        background:#4338ca !important;
        color:#fff !important;
        border-color:rgba(255,255,255,.18) !important;
    }
    .stock-freeze--daily #sisastock thead th.daily-validity-summary.summary-byu {
        background:#5d55d5 !important;
    }
    .stock-freeze--daily #sisastock .summary-category-start {
        border-left:4px solid #c7c3ff !important;
    }
    .stock-freeze--daily #sisastock thead .summary-category-start {
        box-shadow:inset 3px 0 0 rgba(255,255,255,.3);
    }
    .stock-freeze--daily #sisastock tbody td.daily-validity-summary-cell {
        background:#f3f2ff !important;
        color:#3730a3 !important;
    }
    .stock-freeze--daily #sisastock tbody td.daily-validity-summary-cell.summary-byu {
        background:#f7f5ff !important;
        color:#4c3fb1 !important;
    }
    .stock-freeze--daily #sisastock tfoot th.daily-validity-summary-footer {
        background:#e6e4ff !important;
        color:#312e81 !important;
    }
    .stock-freeze--daily #sisastock tfoot th.daily-validity-summary-footer.summary-byu {
        background:#ece9ff !important;
        color:#40358f !important;
    }

    .stock-freeze #stock thead th {
        position: sticky;
        z-index: 40;
    }

    /* Intersections must always sit above horizontally moving cells. */
    .stock-freeze #stock thead .sticky-no,
    .stock-freeze #stock thead .sticky-tap,
    .stock-freeze--three #stock thead .sticky-sf {
        position: sticky !important;
        top: 0 !important;
        z-index: 90 !important;
        background: #5149c9 !important;
        color: #fff !important;
    }

    .stock-freeze #stock tbody .sticky-no,
    .stock-freeze #stock tbody .sticky-tap,
    .stock-freeze--three #stock tbody .sticky-sf {
        position: sticky !important;
        z-index: 24 !important;
        background: #fff !important;
        color: #172033 !important;
    }

    .stock-freeze #stock tbody tr:nth-child(even) .sticky-no,
    .stock-freeze #stock tbody tr:nth-child(even) .sticky-tap,
    .stock-freeze--three #stock tbody tr:nth-child(even) .sticky-sf {
        background: #f8fafc !important;
    }

    .stock-freeze #stock tbody tr:hover .sticky-no,
    .stock-freeze #stock tbody tr:hover .sticky-tap,
    .stock-freeze--three #stock tbody tr:hover .sticky-sf {
        background: #f4f3ff !important;
    }

    .stock-freeze #stock tfoot th { position: sticky !important; bottom: 0; z-index: 60; }
    .stock-freeze #stock tfoot .sticky-no,
    .stock-freeze #stock tfoot .sticky-tap,
    .stock-freeze--three #stock tfoot .sticky-sf {
        z-index: 80 !important;
        background: #25224f !important;
        color: #fff !important;
    }

    /* One clean boundary after the final frozen column prevents moving cells bleeding through. */
    .stock-freeze--two #stock .sticky-tap,
    .stock-freeze--three #stock .sticky-sf {
        border-right: 1px solid #d9ddea !important;
        box-shadow: 10px 0 16px -14px rgba(23, 32, 51, .75) !important;
    }

    .stock-freeze--two #stock thead .sticky-tap,
    .stock-freeze--three #stock thead .sticky-sf {
        border-right-color: rgba(255, 255, 255, .38) !important;
    }

    .stock-freeze--two #stock tfoot .sticky-tap,
    .stock-freeze--three #stock tfoot .sticky-sf {
        border-right-color: rgba(255, 255, 255, .22) !important;
    }

    @media (max-width: 767.98px) {
        .stock-freeze {
            --freeze-no-width: 44px;
            --freeze-tap-width: 132px;
            --freeze-sf-width: 170px;
        }
        .stock-table-toolbar .top { align-items: stretch; flex-direction: column; }
        .stock-table-toolbar .dataTables_filter label { justify-content: space-between; }
        .stock-table-toolbar .dataTables_filter input { flex: 1; width: auto; }
        .stock-table-footer .bottom { grid-template-columns: 1fr auto; }
        .stock-table-footer .dataTables_info { display: none; }
    }
</style>
