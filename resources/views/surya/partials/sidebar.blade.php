@php
    $sidebarPage = $activePage ?? 'dashboard';
    $sidebarUser = strtolower((string) (auth()->user()->username ?? ''));
    $stockUrl = in_array($sidebarUser, ['gm', 'admin_super', 'admin_cluster'], true) || str_starts_with($sidebarUser, 'asmen_')
        ? url('/sisastock')
        : url('/stocktap');
    $dashboardPanel = request('panel') === 'visit-detail' ? 'visit-detail' : 'outlet';
@endphp
<aside class="enterprise-sidebar" aria-label="Navigasi utama SURYA">
    <div class="sidebar-brand">
        <div class="sidebar-logo"><img src="/assets/img/surya-logo-mobile.webp" alt="SURYA"></div>
        <div class="sidebar-brand-copy"><strong>SURYA</strong><small>PERFORMANCE WOK</small></div>
        <button class="sidebar-close" id="sidebar-close" type="button" aria-label="Tutup menu"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="sidebar-label">WORKSPACE</div>
    <nav class="sidebar-nav">
        <a href="{{ route('surya.dashboard', ['panel' => 'outlet']) }}#outlet" class="sidebar-link {{ $sidebarPage === 'dashboard' && $dashboardPanel === 'outlet' ? 'active' : '' }}" @if($sidebarPage === 'dashboard') data-dashboard-tab="outlet" @endif><i class="fas fa-map-location-dot"></i><span>PETA OUTLET</span></a>
        <a href="{{ route('surya.dashboard', ['panel' => 'visit-detail']) }}#visit-detail" class="sidebar-link {{ $sidebarPage === 'dashboard' && $dashboardPanel === 'visit-detail' ? 'active' : '' }}" @if($sidebarPage === 'dashboard') data-dashboard-tab="visit-detail" @endif><i class="fas fa-clipboard-check"></i><span>DETAIL VISIT</span></a>
        @if($sidebarUser === 'admin_super')
            <a href="{{ route('surya.outlet-list') }}" class="sidebar-link {{ $sidebarPage === 'outlet-list' ? 'active' : '' }}"><i class="fas fa-list"></i><span>LIST OUTLET</span></a>
        @endif
        <a href="{{ $stockUrl }}" class="sidebar-link"><i class="fas fa-warehouse"></i><span>STOCK GUDANG</span></a>
        @if($sidebarUser === 'admin_super')
            <a href="{{ route('surya.transaction-upload') }}" class="sidebar-link {{ $sidebarPage === 'transaction-upload' ? 'active' : '' }}"><i class="fas fa-file-arrow-up"></i><span>UPLOAD TRANSAKSI</span></a>
        @endif
    </nav>
    <div class="sidebar-foot">
        <div class="sidebar-user"><div class="sidebar-avatar"><i class="fas fa-user-shield"></i></div><div class="sidebar-user-copy"><strong>SURYA Leader</strong><small>Secure access</small></div></div>
        <a href="{{ route('surya.leader.logout') }}" class="sidebar-logout"><i class="fas fa-arrow-right-from-bracket"></i><span>Logout</span></a>
    </div>
</aside>
