<style>
    .form-premium-page {
        --form-primary: #4f46e5;
        --form-primary-dark: #3730a3;
        --form-ink: #172033;
        --form-muted: #718096;
        --form-line: #e7eaf1;
        background: radial-gradient(circle at 94% 6%, rgba(99, 102, 241, .09), transparent 26rem), #f7f8fc;
        min-height: calc(100vh - 62px);
    }

    .form-premium-page > .content { padding: 0; }
    .form-premium-page .page-inner { padding: 22px 32px 30px; }
    .form-premium-page .page-header {
        display: block;
        max-width: 1120px;
        min-height: 0;
        margin: 0 0 14px;
        padding: 0;
        border: 0;
    }

    .form-premium-page .page-header::before {
        content: 'TRANSAKSI INVENTORY';
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
        color: var(--form-primary);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .12em;
    }

    .form-premium-page .page-header .page-title {
        margin: 0;
        color: var(--form-ink);
        font-size: 25px;
        font-weight: 700;
        letter-spacing: -.025em;
        line-height: 1.2;
    }
    .form-premium-page .page-header .text-muted { margin-top: 3px; font-size: 12px; line-height: 1.35; }

    .form-premium-page .page-inner > .row { max-width: 1120px; margin: 0; }
    .form-premium-page .page-inner > .row > [class*='col-'] { flex: 0 0 100%; max-width: 100%; padding: 0; }
    .form-premium-page .card {
        overflow: hidden;
        margin-bottom: 0;
        border: 1px solid rgba(226, 232, 240, .9) !important;
        border-radius: 18px !important;
        background: rgba(255, 255, 255, .97);
        box-shadow: 0 22px 60px rgba(30, 41, 59, .09) !important;
    }

    .form-premium-page .card-header {
        position: relative;
        min-height: 68px;
        padding: 15px 22px 14px 70px !important;
        border: 0 !important;
        border-bottom: 1px solid var(--form-line) !important;
        background: #fff !important;
        color: var(--form-ink) !important;
    }

    .form-premium-page .card-header::before {
        content: '\f1d8';
        position: absolute;
        top: 14px;
        left: 22px;
        display: grid;
        width: 38px;
        height: 38px;
        place-items: center;
        border-radius: 12px;
        background: linear-gradient(145deg, #6259e8, #4338ca);
        color: #fff;
        font-family: 'Font Awesome 5 Solid';
        font-size: 15px;
        box-shadow: 0 8px 20px rgba(79, 70, 229, .25);
    }

    .form-premium-page .card-header strong { display: block; margin-bottom: 2px; font-size: 14px; font-weight: 700; }
    .form-premium-page .card-header .small { color: var(--form-muted) !important; font-size: 11px; }
    .form-premium-page .card-body { padding: 17px 22px 18px; }
    .form-premium-page .card-body > .row { margin: -6px; }
    .form-premium-page .card-body > .row > [class*='col-'] { padding: 6px; }
    .form-premium-page .form-group { margin: 0; padding: 0; }
    .form-premium-page .form-group label {
        margin-bottom: 6px;
        color: #364152;
        font-size: 12px;
        font-weight: 700;
    }

    .form-premium-page .form-control,
    .form-premium-page .select2-container .select2-selection--single {
        height: 40px !important;
        border: 1px solid #dfe3eb !important;
        border-radius: 10px !important;
        background-color: #fff;
        color: #283245;
        font-size: 13px;
        box-shadow: none !important;
        transition: border-color .2s ease, box-shadow .2s ease !important;
    }

    .form-premium-page textarea.form-control { height: auto !important; min-height: 72px; padding: 10px 14px; }
    .form-premium-page input.form-control { padding: 0 14px; }
    .form-premium-page .select2-container .select2-selection--single { padding: 5px 12px !important; }
    .form-premium-page .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 28px; }
    .form-premium-page .select2-container--default .select2-selection--single .select2-selection__arrow { right: 7px; height: 38px; }
    .form-premium-page .select2-container--focus .select2-selection--single,
    .form-premium-page .form-control:focus {
        border-color: #7771ea !important;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, .1) !important;
    }
    .form-premium-page .select2-container--disabled .select2-selection--single,
    .form-premium-page .form-control:disabled { background: #f4f5f8 !important; color: #9ca3af; }

    .form-premium-page .table-responsive {
        overflow: hidden;
        margin-top: 4px;
        border: 1px solid var(--form-line);
        border-radius: 14px;
    }
    .form-premium-page .table { margin: 0; }
    .form-premium-page .table thead th {
        padding: 9px 13px !important;
        border: 0 !important;
        border-bottom: 1px solid var(--form-line) !important;
        background: #fbfbfd;
        color: #788296;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .form-premium-page .table tbody td { padding: 8px 13px !important; border-color: #edf0f4 !important; vertical-align: middle; }
    .form-premium-page .table .form-control { height: 36px !important; font-size: 12px; }
    .form-premium-page .table .select2-selection--single { height: 36px !important; padding: 3px 8px !important; }
    .form-premium-page #bulk-entry > .d-flex { margin-top: 10px !important; margin-bottom: 7px !important; }

    .form-premium-page .btn-outline-primary {
        padding: 9px 14px !important;
        border: 1px solid #d9d7fb !important;
        border-radius: 9px !important;
        background: #f1f0ff !important;
        color: var(--form-primary-dark) !important;
        font-size: 12px !important;
        font-weight: 700;
    }
    .form-premium-page .card-footer {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        padding: 12px 22px;
        border-top: 1px solid var(--form-line);
        background: #fbfbfd;
    }
    .form-premium-page .card-footer .btn { min-width: 100px; padding: 9px 16px !important; border-radius: 9px !important; font-size: 11px; font-weight: 700; }
    .form-premium-page .card-footer .btn-light { border: 1px solid #dfe3eb !important; background: #fff !important; color: #536076 !important; }
    .form-premium-page .card-footer .btn-primary {
        border: 0 !important;
        background: linear-gradient(135deg, #5b54e8, #4338ca) !important;
        box-shadow: 0 9px 20px rgba(79, 70, 229, .24);
    }

    @media (max-width: 991.98px) {
        .form-premium-page .page-inner { padding: 28px 22px 40px; }
    }
    @media (max-width: 575.98px) {
        .form-premium-page .page-inner { padding: 22px 14px 32px; }
        .form-premium-page .page-header .page-title { font-size: 23px; }
        .form-premium-page .card-header { padding-right: 18px !important; }
        .form-premium-page .card-body, .form-premium-page .card-footer { padding-left: 18px; padding-right: 18px; }
        .form-premium-page .table-responsive { overflow-x: auto; }
        .form-premium-page .card-footer .btn { flex: 1; min-width: 0; }
    }
</style>
