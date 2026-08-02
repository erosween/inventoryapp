<style>
    :root {
        --app-primary: #4f46e5;
        --app-primary-dark: #3730a3;
        --app-primary-soft: #eeedff;
        --app-accent: #7c73ee;
        --app-ink: #172033;
        --app-text: #39445a;
        --app-muted: #7b8497;
        --app-bg: #f7f8fc;
        --app-surface: #ffffff;
        --app-line: #e7eaf1;
        --app-success: #169b70;
        --app-danger: #e11d48;
        --app-warning: #d99114;
        --app-font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        --premium-indigo: var(--app-primary);
        --premium-indigo-dark: var(--app-primary-dark);
        --premium-slate: var(--app-bg);
        --premium-border: var(--app-line);
        --premium-shadow: 0 18px 48px rgba(30, 41, 59, .08);
    }

    html, body { background: var(--app-bg); color: var(--app-text); }
    body,
    h1, h2, h3, h4, h5, h6,
    p,
    a,
    label,
    td,
    th,
    button,
    input,
    select,
    textarea,
    .navbar,
    .brand,
    .alert,
    .btn,
    .td-name,
    .form-control,
    .custom-select,
    .select2-container,
    .dataTables_wrapper,
    .table { font-family: var(--app-font) !important; }
    body { font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11'; -webkit-font-smoothing: antialiased; }
    a { transition: color .18s ease, background-color .18s ease, border-color .18s ease, box-shadow .18s ease, transform .18s ease; }

    /* App chrome */
    .main-header { box-shadow: 0 1px 0 rgba(255, 255, 255, .12), 0 8px 24px rgba(55, 48, 163, .12); }
    .main-header[data-background-color='purple'],
    .main-header[data-background-color='purple'] .logo-header,
    .main-header[data-background-color='purple'] .navbar-header {
        background: linear-gradient(110deg, #4b43bf 0%, #6259d8 55%, #756ee2 100%) !important;
    }
    .logo-header .logo { font-size: 17px !important; font-weight: 700; letter-spacing: -.02em; }
    .logo-header .btn-minimize, .logo-header .navbar-toggler { color: rgba(255, 255, 255, .92) !important; }
    .btn-new-window { border-color: rgba(255, 255, 255, .3) !important; background: rgba(255, 255, 255, .1) !important; }

    .sidebar { border-right: 1px solid var(--app-line); box-shadow: 8px 0 30px rgba(30, 41, 59, .035); }
    .sidebar .sidebar-content { padding-top: 4px; }
    .sidebar .user { margin: 12px 14px 8px; padding: 14px 10px 17px; border-bottom-color: var(--app-line); }
    .sidebar .user .info a > span { color: var(--app-ink); }
    .sidebar .user .info .user-level { margin-top: 3px; color: var(--app-muted); }
    .sidebar .nav > .nav-item { margin: 3px 10px; }
    .sidebar .nav > .nav-item > a {
        min-height: 44px;
        padding: 10px 14px;
        border: 1px solid transparent;
        border-radius: 11px;
        transition: background-color .18s ease, border-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }
    .sidebar .nav > .nav-item > a i { color: #969eac; font-size: 16px; }
    .sidebar .nav > .nav-item > a p { color: #697386; font-size: 12px; font-weight: 600; letter-spacing: .01em; }
    .sidebar .nav > .nav-item > a:hover { background: #f4f3ff; }
    .sidebar .nav > .nav-item > a:hover i,
    .sidebar .nav > .nav-item > a:hover p { color: var(--app-primary); }
    .sidebar .nav > .nav-item.active > a,
    .sidebar .nav > .nav-item.submenu.active > a {
        background: linear-gradient(135deg, #f0efff, #f7f6ff) !important;
        border-color: rgba(79, 70, 229, .09);
        box-shadow: 0 5px 14px rgba(79, 70, 229, .07);
    }
    /* Disable the legacy Azzara active marker; the active surface above is the sole indicator. */
    .sidebar .nav > .nav-item.active > a::before,
    .sidebar .nav > .nav-item.active:hover > a::before,
    .sidebar .nav > .nav-item > a[data-toggle='collapse'][aria-expanded='true']::before {
        display: none !important;
        width: 0 !important;
        content: none !important;
    }
    .sidebar .nav > .nav-item.active > a i,
    .sidebar .nav > .nav-item.active > a p { color: var(--app-primary) !important; }
    .sidebar .nav-section { margin: 18px 22px 7px; }
    .sidebar .nav-section .text-section { color: #9ca3af; font-size: 10px; font-weight: 700; letter-spacing: .1em; }
    .sidebar .nav-collapse li a { margin: 1px 10px; padding: 8px 18px 8px 42px; border-radius: 9px; color: #7a8394; }
    .sidebar .nav-collapse li.active > a,
    .sidebar .nav-collapse li a:hover { background: #f4f3ff; color: var(--app-primary); }

    /* Page surfaces */
    .main-panel { background: radial-gradient(circle at 94% 4%, rgba(99, 102, 241, .07), transparent 25rem), var(--app-bg); }
    .main-panel > .content { padding-top: 0; }
    .page-inner { padding: 30px 30px 44px; }
    .page-header { margin-bottom: 22px; }
    .page-header .page-title,
    .page-title { color: var(--app-ink); font-weight: 700; letter-spacing: -.025em; }
    .page-header .page-title { font-size: 25px; }
    .page-header .breadcrumbs { color: var(--app-muted); }

    .card {
        border: 1px solid rgba(226, 232, 240, .92);
        border-radius: 14px;
        background: var(--app-surface);
        box-shadow: 0 12px 36px rgba(30, 41, 59, .065);
    }
    .card.premium-card { border: 1px solid rgba(226, 232, 240, .92); border-radius: 14px; box-shadow: 0 12px 36px rgba(30, 41, 59, .065); }
    .card-header { padding: 18px 20px; border-bottom: 1px solid var(--app-line); background: #fff; }
    .card-header:first-child { border-radius: 14px 14px 0 0; }
    .card-title { color: var(--app-ink); font-weight: 700; letter-spacing: -.015em; }
    .card-subtitle, .text-muted { color: var(--app-muted) !important; }
    .card-body { padding: 20px; }
    .card-footer { border-top: 1px solid var(--app-line); background: #fbfbfd; }

    /* Controls and actions */
    .form-group label { color: #414b5f; font-size: 12px; font-weight: 700; }
    .form-control,
    .custom-select,
    .select2-container .select2-selection--single {
        border-color: #dfe3eb !important;
        border-radius: 9px !important;
        background-color: #fff;
        color: var(--app-text);
        box-shadow: none !important;
    }
    .form-control:focus,
    .custom-select:focus,
    .select2-container--focus .select2-selection--single {
        border-color: #7771ea !important;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, .1) !important;
    }
    .form-control:disabled,
    .select2-container--disabled .select2-selection--single { background: #f3f4f7 !important; color: #9aa1af; }
    .input-group-text { border-color: #dfe3eb; background: #f8f9fc; color: var(--app-muted); }

    .btn { border-radius: 9px; font-weight: 600; transition: all .18s ease; }
    .btn:hover { transform: translateY(-1px); }
    .btn-primary,
    .btn-secondary:not(.dropdown-toggle),
    .btn-info {
        border-color: transparent !important;
        background: linear-gradient(135deg, #5b54e8, #4338ca) !important;
        color: #fff !important;
        box-shadow: 0 7px 16px rgba(79, 70, 229, .2);
    }
    .btn-primary:hover,
    .btn-secondary:not(.dropdown-toggle):hover,
    .btn-info:hover { box-shadow: 0 10px 22px rgba(79, 70, 229, .28); }
    .btn-light, .btn-outline-secondary { border-color: #dfe3eb !important; background: #fff !important; color: #536076 !important; }
    .btn-outline-primary { border-color: #cecafa !important; background: #f5f4ff; color: var(--app-primary) !important; }
    .btn-danger { border-color: transparent; background: #e93b61; box-shadow: 0 6px 14px rgba(225, 29, 72, .16); }
    .btn-success { border-color: transparent; background: var(--app-success); box-shadow: 0 6px 14px rgba(22, 155, 112, .16); }
    .btn-warning { border-color: transparent; background: #eba82c; color: #fff; }
    .btn-link { color: var(--app-primary); }
    .action-edit-icon {
        display:inline-flex !important;
        align-items:center;
        justify-content:center;
        width:32px;
        height:32px;
        margin:0 !important;
        padding:0 !important;
        border:0 !important;
        border-radius:8px !important;
        background:transparent !important;
        color:#2583f8 !important;
        box-shadow:none !important;
        line-height:1;
    }
    .action-edit-icon:hover,
    .action-edit-icon:focus {
        background:#eef6ff !important;
        color:#1269d3 !important;
        transform:none !important;
        outline:none;
    }

    /* Navigation, tables and data tools */
    .nav-pills .nav-link { border-radius: 9px !important; color: #667085; }
    .nav-pills .nav-link.active,
    .nav-pills.nav-secondary .nav-link.active { background: var(--app-primary) !important; color: #fff !important; box-shadow: 0 6px 14px rgba(79, 70, 229, .2); }
    .table { color: var(--app-text); }
    .table thead th { color: #667085; font-size: 11px; font-weight: 700; letter-spacing: .035em; border-bottom-color: var(--app-line); }
    .table td, .table th { border-color: #edf0f4; }
    .table-hover tbody tr:hover { background: #fafaff; }
    .table-indigo thead th { background: linear-gradient(105deg, #5149c9, #6259d8) !important; }
    .table-indigo tbody tr:hover { background: #f8f7ff !important; }
    .table-responsive, .table-scroll { scrollbar-color: #c8cad6 transparent; scrollbar-width: thin; }
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input { border: 1px solid #dfe3eb !important; border-radius: 9px !important; background: #fff !important; }
    .dataTables_wrapper .dataTables_info { color: var(--app-muted); font-size: 12px; }
    .page-item .page-link { margin: 0 2px; border-color: var(--app-line); border-radius: 8px !important; color: #606a7c; }
    .page-item.active .page-link { border-color: var(--app-primary); background: var(--app-primary); box-shadow: 0 5px 12px rgba(79, 70, 229, .18); }

    /* Status UI */
    .badge { padding: 5px 8px; border-radius: 7px; font-weight: 700; }
    .badge-primary, .badge-info { background: var(--app-primary); }
    .badge-success { background: var(--app-success); }
    .badge-danger { background: var(--app-danger); }
    .alert { border-width: 1px; border-radius: 11px; }
    .alert-primary, .alert-info { border-color: #d7d4ff; background: #f2f1ff; color: var(--app-primary-dark); }
    .dropdown-menu { overflow: hidden; border: 1px solid var(--app-line); border-radius: 11px; box-shadow: 0 18px 42px rgba(30, 41, 59, .14); }
    .dropdown-item { color: var(--app-text); }
    .dropdown-item:hover, .dropdown-item:focus { background: #f3f2ff; color: var(--app-primary); }
    .modal-content { border: 0; border-radius: 16px; box-shadow: 0 24px 70px rgba(30, 41, 59, .2); }
    .modal-header { border-bottom-color: var(--app-line); }
    .modal-footer { border-top-color: var(--app-line); background: #fbfbfd; }
    .swal2-popup { border-radius: 18px !important; }
    .swal2-confirm { border-radius: 9px !important; background: var(--app-primary) !important; }

    @media (max-width: 991.98px) {
        .page-inner { padding: 24px 20px 38px; }
        .card { border-radius: 12px; }
    }
    @media (max-width: 575.98px) {
        .page-inner { padding: 20px 14px 32px; }
        .page-header .page-title { font-size: 22px; }
        .card-header, .card-body { padding-left: 16px; padding-right: 16px; }
    }
</style>
