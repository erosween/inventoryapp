@php
    $sidebarPage = $activePage ?? 'dashboard';
    $sidebarUser = strtolower((string) (auth()->user()->username ?? ''));
    $stockUrl = route('surya.stock-gudang');
    $dashboardPanel = in_array($sidebarPage, ['visit-detail', 'visit-report', 'field-reports'], true) || request('panel') === 'visit-detail'
        ? 'visit-detail'
        : 'outlet';
@endphp
<aside class="enterprise-sidebar" id="surya-sidebar" aria-label="Navigasi utama SURYA">
    <div class="sidebar-brand">
        <div class="sidebar-logo"><img src="/assets/img/surya-logo-mobile.webp" alt="SURYA"></div>
        <div class="sidebar-brand-copy"><strong>SURYA</strong><small>PERFORMANCE WORKSPACE</small></div>
        <button class="sidebar-close" id="sidebar-close" type="button" aria-label="Tutup menu"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="sidebar-label">WORKSPACE</div>
    <nav class="sidebar-nav">
        <a href="{{ route('surya.inventory-dashboard') }}" class="sidebar-link {{ $sidebarPage === 'inventory-dashboard' ? 'active' : '' }}"><i class="fas fa-chart-line"></i><span>DASHBOARD</span></a>
        <a href="{{ route('surya.dashboard', ['panel' => 'outlet']) }}#outlet" class="sidebar-link {{ $sidebarPage === 'dashboard' && $dashboardPanel === 'outlet' ? 'active' : '' }}" @if($sidebarPage === 'dashboard') data-dashboard-tab="outlet" @endif><i class="fas fa-map-location-dot"></i><span>PETA OUTLET</span></a>
        <a href="{{ route('surya.dashboard', ['panel' => 'visit-detail']) }}#visit-detail" class="sidebar-link {{ in_array($sidebarPage, ['dashboard', 'visit-detail', 'visit-report', 'field-reports'], true) && $dashboardPanel === 'visit-detail' ? 'active' : '' }}" @if($sidebarPage === 'dashboard') data-dashboard-tab="visit-detail" @endif><i class="fas fa-clipboard-check"></i><span>DETAIL VISIT</span></a>
        <a href="{{ route('surya.outlet-detail') }}" class="sidebar-link {{ $sidebarPage === 'outlet-detail' ? 'active' : '' }}"><i class="fas fa-table-list"></i><span>DETAIL OUTLET</span></a>
        <details class="sidebar-taskforce" @if($sidebarPage === 'push-so') open @endif>
            <summary class="sidebar-link"><i class="fas fa-people-group"></i><span>TASKFORCE</span><i class="fas fa-chevron-down taskforce-chevron"></i></summary>
            <a href="{{ route('surya.taskforce.push-so') }}" class="sidebar-link {{ $sidebarPage === 'push-so' ? 'active' : '' }}" style="padding-left:42px"><i class="fas fa-bullseye"></i><span>PUSH SO</span></a>
        </details>
        <a href="{{ $stockUrl }}" class="sidebar-link {{ $sidebarPage === 'stock-gudang' ? 'active' : '' }}"><i class="fas fa-warehouse"></i><span>STOCK GUDANG</span></a>
        <a href="{{ route('surya.stock-on-hand') }}" class="sidebar-link {{ $sidebarPage === 'stock-on-hand' ? 'active' : '' }}"><i class="fas fa-boxes-stacked"></i><span>STOCK ON HAND</span></a>
        @if($sidebarUser === 'admin_super')
            <a href="{{ route('surya.input-target') }}" class="sidebar-link {{ $sidebarPage === 'input-target' ? 'active' : '' }}"><i class="fas fa-bullseye"></i><span>INPUT TARGET</span></a>
        @endif
        @if($sidebarUser === 'admin_super')
            <a href="{{ route('surya.transaction-upload') }}" class="sidebar-link {{ $sidebarPage === 'transaction-upload' ? 'active' : '' }}"><i class="fas fa-file-arrow-up"></i><span>UPLOAD TRANSAKSI</span></a>
            <a href="{{ route('home') }}" class="sidebar-link"><i class="fas fa-arrow-up-right-from-square"></i><span>WEB INVENTORY</span></a>
        @endif
    </nav>
    <div class="sidebar-foot">
        <div class="sidebar-user"><div class="sidebar-avatar"><i class="fas fa-user-shield"></i></div><div class="sidebar-user-copy"><strong>SURYA Leader</strong><small>Secure access</small></div></div>
        <a href="{{ route('surya.leader.logout') }}" class="sidebar-logout"><i class="fas fa-arrow-right-from-bracket"></i><span>Logout</span></a>
    </div>
</aside>
