@php
    $sidebarPage = $activePage ?? 'dashboard';
    $sidebarUser = strtolower((string) (auth()->user()->username ?? ''));
    $stockUrl = in_array($sidebarUser, ['gm', 'gm_cluster', 'branch_dumai', 'admin_super', 'admin_cluster'], true) || str_starts_with($sidebarUser, 'asmen_')
        ? url('/sisastock')
        : url('/stocktap');
    $dashboardPanel = request('panel') === 'visit-detail' ? 'visit-detail' : 'outlet';
@endphp
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/@fontsource-variable/geist@5.2.8/index.css">
<style>.surya-sidebar-toggle{display:none;position:fixed;right:16px;bottom:16px;z-index:1200;width:46px;height:46px;border:0;border-radius:50%;background:#c8102e;color:#fff;box-shadow:0 8px 20px #17203335;font-size:18px}@media(max-width:1100px){.surya-sidebar-toggle{display:grid;place-items:center}}</style>
<style>.target-inline-form{display:flex;align-items:center;gap:5px}.target-inline-form input{width:86px!important}.target-inline-form button{padding:7px 9px!important;font-size:11px}</style>
<style>body{font-family:"Geist Variable",Inter,Arial,sans-serif!important}.search-box{display:flex;align-items:center;gap:9px;min-width:300px;padding:10px 13px;border:1px solid #dce4eb;border-radius:10px;background:#fff;color:#98a2b3}.search-box input{width:100%;border:0;outline:0;background:transparent;color:#172033}.target-toolbar{display:flex;align-items:center;justify-content:space-between;gap:18px;margin:18px 24px}.target-toolbar .search-box{margin-left:auto}@media(max-width:640px){.target-toolbar{align-items:stretch;flex-direction:column;margin:16px}.target-toolbar .search-box{min-width:0;width:100%}}</style>
<style>.table-wrap table{font-variant-numeric:tabular-nums}.table-wrap th{letter-spacing:.08em}.table-wrap td,.table-wrap th{text-transform:uppercase}.table-wrap tbody tr:nth-child(even){background:#fbfcfe}.table-wrap tbody tr:hover{background:#fff5f6}.validity-pill{min-width:72px;text-align:center}.status{min-width:98px;justify-content:center}.page-card-header h1{letter-spacing:-.02em}</style>
<style>.surya-main>.page-card{border:1px solid #dce4eb!important;border-radius:16px!important;background:#fff!important;box-shadow:0 10px 28px rgba(15,23,42,.04)!important}.surya-main>.page-card>.table-wrap{max-height:calc(100vh - 330px);min-height:520px;overflow:auto;scrollbar-gutter:stable}.surya-main>.page-card>.table-wrap table{min-width:1280px;border-collapse:collapse}.surya-main>.page-card>.table-wrap thead th{position:sticky;top:0;z-index:4;height:48px;background:#f8fafc;color:#697386;border-bottom:1px solid #dfe6ed;font-size:10px;letter-spacing:.55px;text-transform:uppercase}.surya-main>.page-card>.table-wrap td{padding:14px 12px;border-bottom:1px solid #e8edf2}.surya-main>.page-card>.table-wrap tbody tr:hover td{background:#f8fafc}.surya-main>.page-card>.table-wrap td:nth-child(n+5){text-align:right;font-variant-numeric:tabular-nums;font-weight:750}.surya-main>.page-card>.table-wrap td:last-child{text-align:left}</style>
<style>.stock-table-wrap{min-height:520px!important;max-height:calc(100vh - 360px)!important}.stock-table{width:1280px!important;border-collapse:separate!important;border-spacing:0!important}.stock-table th{position:sticky!important;top:0!important;z-index:8!important;height:48px!important}.stock-table th:nth-child(1),.stock-table td:nth-child(1){position:sticky;left:0;z-index:6;width:230px;min-width:230px;background:#fff}.stock-table th:nth-child(2),.stock-table td:nth-child(2){position:sticky;left:230px;z-index:6;width:135px;min-width:135px;background:#fff;box-shadow:8px 0 12px -12px rgba(15,23,42,.55)}.stock-table thead th:nth-child(1),.stock-table thead th:nth-child(2){z-index:12;background:#f8fafc!important}.stock-table tbody tr:hover td:nth-child(1),.stock-table tbody tr:hover td:nth-child(2){background:#f8fafc}.table-footer{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:16px 20px;border-top:1px solid #e5e7eb;background:#fff}.table-info{color:#667085;font-size:12px;font-weight:700;letter-spacing:.02em}.pagination{display:flex;align-items:center;gap:6px}.page-link{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 9px;border:1px solid #dce3ea;border-radius:9px;background:#fff;color:#475467;font-weight:800;cursor:pointer;transition:.15s}.page-link:hover:not(:disabled){border-color:#c8102e;color:#c8102e;background:#fff7f8}.page-link.active{border-color:#c8102e;background:#c8102e;color:#fff;box-shadow:0 4px 10px rgba(200,16,46,.2)}.page-link:disabled{cursor:not-allowed;opacity:.4}.pager-arrow{font-size:20px;line-height:1}.pager-dots{display:inline-flex;width:18px;justify-content:center;color:#98a2b3;font-weight:800}@media(max-width:760px){.table-footer{align-items:flex-start;flex-direction:column}.pagination{width:100%;justify-content:center;flex-wrap:wrap}}</style>
<style>.stock-toolbar{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:16px 24px;background:#fbfcfd;border-bottom:1px solid #edf1f5}.stock-toolbar .muted{padding:0!important;background:none!important}.search-box{display:flex;align-items:center;gap:9px;min-width:310px;padding:10px 13px;border:1px solid #dce4eb;border-radius:10px;background:#fff;color:#98a2b3}.search-box input{width:100%;border:0;outline:0;color:#172033;background:transparent}.validity-pill{display:inline-block;padding:5px 9px;border-radius:7px;background:#fff1f2;color:#b42318;font-weight:800}.empty-state{padding:24px;text-align:center;color:#667085}@media(max-width:640px){.stock-toolbar{align-items:stretch;flex-direction:column;padding:14px 16px}.search-box{min-width:0;width:100%}}</style>
<aside class="enterprise-sidebar" aria-label="Navigasi utama SURYA">
    <div class="sidebar-brand">
        <div class="sidebar-logo"><img src="/assets/img/surya-logo-mobile.webp" alt="SURYA"></div>
        <div class="sidebar-brand-copy"><strong>SURYA</strong><small>PERFORMANCE WORKSPACE</small></div>
        <button class="sidebar-close" id="sidebar-close" type="button" aria-label="Tutup menu"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="sidebar-label">WORKSPACE</div>
    <nav class="sidebar-nav">
        <a href="{{ route('surya.dashboard', ['panel' => 'outlet']) }}#outlet" class="sidebar-link {{ $sidebarPage === 'dashboard' && $dashboardPanel === 'outlet' ? 'active' : '' }}" @if($sidebarPage === 'dashboard') data-dashboard-tab="outlet" @endif><i class="fas fa-map-location-dot"></i><span>PETA OUTLET</span></a>
        <a href="{{ route('surya.dashboard', ['panel' => 'visit-detail']) }}#visit-detail" class="sidebar-link {{ $sidebarPage === 'dashboard' && $dashboardPanel === 'visit-detail' ? 'active' : '' }}" @if($sidebarPage === 'dashboard') data-dashboard-tab="visit-detail" @endif><i class="fas fa-clipboard-check"></i><span>DETAIL VISIT</span></a>
        <a href="{{ route('surya.outlet-detail') }}" class="sidebar-link {{ $sidebarPage === 'outlet-detail' ? 'active' : '' }}"><i class="fas fa-table-list"></i><span>DETAIL OUTLET</span></a>
        <a href="{{ $stockUrl }}" class="sidebar-link"><i class="fas fa-warehouse"></i><span>STOCK GUDANG</span></a>
        <a href="{{ route('surya.stock-on-hand') }}" class="sidebar-link {{ $sidebarPage === 'stock-on-hand' ? 'active' : '' }}"><i class="fas fa-boxes-stacked"></i><span>STOCK ON HAND</span></a>
        @if($sidebarUser === 'admin_super')
            <a href="{{ route('surya.input-target') }}" class="sidebar-link {{ $sidebarPage === 'input-target' ? 'active' : '' }}"><i class="fas fa-bullseye"></i><span>INPUT TARGET</span></a>
        @endif
        @if($sidebarUser === 'admin_super')
            <a href="{{ route('surya.transaction-upload') }}" class="sidebar-link {{ $sidebarPage === 'transaction-upload' ? 'active' : '' }}"><i class="fas fa-file-arrow-up"></i><span>UPLOAD TRANSAKSI</span></a>
        @endif
    </nav>
    <div class="sidebar-foot">
        <div class="sidebar-user"><div class="sidebar-avatar"><i class="fas fa-user-shield"></i></div><div class="sidebar-user-copy"><strong>SURYA Leader</strong><small>Secure access</small></div></div>
        <a href="{{ route('surya.leader.logout') }}" class="sidebar-logout"><i class="fas fa-arrow-right-from-bracket"></i><span>Logout</span></a>
    </div>
</aside>
<script>
document.addEventListener('DOMContentLoaded',()=>{const shell=document.querySelector('.shell');if(!shell||document.getElementById('sidebar-toggle'))return;const button=document.createElement('button');button.id='sidebar-toggle';button.className='surya-sidebar-toggle';button.innerHTML='<i class="fas fa-bars"></i>';button.setAttribute('aria-label','Buka menu');document.body.appendChild(button);button.addEventListener('click',()=>shell.classList.toggle('sidebar-open'));});
</script>
