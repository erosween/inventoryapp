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
