<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    @if (session('idtap') == 'SB DUMAI' || session('idtap') == 'SB SIDEMPUAN')
        <title>NOCAN MSP</title>
    @else
        <title>INVENTORY MSP</title>
    @endif
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="icon" href="/assets/img/MSP5.png" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="/assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {
                "families": ["Open+Sans:300,400,600,700"]
            },
            custom: {
                "families": ["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands"],
                urls: ['/assets/css/fonts.css']
            },
            active: function() {
                sessionStorage.fonts = true;
            }
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/azzara.min.css">


</head>

<body>
    <div class="wrapper">
        <!--
    Tip 1: You can change the background color of the main header using: data-background-color="blue | purple | light-blue | green | orange | red"
  -->
        <div class="main-header" data-background-color="purple">
            <!-- Logo Header -->
            <div class="logo-header">
                @if (session('idtap') == 'SB DUMAI' || session('idtap') == 'SB SIDEMPUAN')
                    <a href="{{ url('home') }}" class="logo" style="color:white">
                        NOCAN MSP
                    </a>
                @else
                    <a href="{{ url('home') }}" class="logo" style="color:white">
                        INVENTORY MSP
                    </a>
                @endif

                <button class="navbar-toggler sidenav-toggler ml-auto" type="button" data-toggle="collapse"
                    data-target="collapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon">
                        <i class="fa fa-bars"></i>
                    </span>
                </button>
                <button class="topbar-toggler more"><i class="fa fa-ellipsis-v"></i></button>
                <div class="navbar-minimize">
                    <button class="btn btn-minimize btn-rounded">
                        <i class="fa fa-bars"></i>
                    </button>
                </div>
            </div>
            <!-- End Logo Header -->

            <!-- Navbar Header -->
            <nav class="navbar navbar-header navbar-expand-lg">
            </nav>
            <!-- End Navbar -->
        </div>
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-background"></div>
            <div class="sidebar-wrapper scrollbar-inner">
                <div class="sidebar-content">
                    <div class="user">
                        <div class="avatar-sm float-left mr-2">
                            <img src="/assets/img/MSP5.png" alt="..." class="avatar-img rounded-circle">
                        </div>
                        <div class="info">
                            <a data-toggle="collapse" href="#collapseExample" aria-expanded="true">
                                <span>
                                    <strong>{{ $idtap }}</strong>
                                    <span class="user-level">Administrator</span>
                                </span>
                            </a>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                    <ul class="nav">
                        @if (session('idtap') == 'SB DUMAI' || session('idtap') == 'SB SIDEMPUAN')
                            <li class="nav-item {{ request()->is('homenocan') ? 'active' : '' }}">
                                <a href="{{ url('homenocan') }}">
                                    <i class="fas fa-home"></i>
                                    <p>DASHBOARD</p>
                                </a>
                            </li>
                            <li class="nav-item {{ request()->is('nocanadmin') ? 'active' : '' }}">
                                <a href="{{ url('nocanadmin') }}">
                                    <i class="fas fa-book" aria-hidden="true"></i>
                                    <p>LIST NOCAN</p>
                                </a>
                            </li>
                        @else
                            <li class="nav-item {{ request()->is('home') ? 'active' : '' }}">
                                <a href="{{ url('home') }}">
                                    <i class="fas fa-home"></i>
                                    <p>DASHBOARD</p>
                                </a>
                            </li>
                            <li class="nav-item {{ request()->is('inbox') ? 'active' : '' }}">
                                <a href="{{ url('inbox') }}" style="position: relative; display: inline-block;">
                                    <i class="fa fa-bell"></i>
                                    <p style="margin-bottom: 0;">KOTAK MASUK
                                        <span class="notification"
                                            style="
									display: inline-block;
									border-radius: 50%;
									background-color: red;
									color: white;
									width: 20px;
									height: 20px;
									text-align: center;
									line-height: 20px;
									font-size: 12px;
									position: absolute;
									margin-left : 5px;
									margin-top : 1px;
								">{{ $notif }}</span>
                                    </p>
                                </a>
                            </li>
                            <li class="nav-item {{ request()->is('stock') ? 'active' : '' }}">
                                <a href="{{ url('stock') }}">
                                    <i class="fas fa-book"></i>
                                    <p>STOCK GUDANG</p>
                                </a>
                            </li>
                            {{-- <li class="nav-item {{ request()->is('sisastock') ? 'active' : '' }}">
                                <a href="{{ url('sisastock') }}">
                                    <i class="fas fa-book"></i>
                                    <p>CEK STOCK DAILY</p>
                                </a>
                            </li> --}}
                            <li class="nav-section">
                                <span class="sidebar-mini-icon">
                                    <i class="fa fa-ellipsis-h"></i>
                                </span>
                                <h4 class="text-section">INPUT STOK</h4>
                            </li>
                            <li class="nav-item">
                                <a data-toggle="collapse" href="#base"
                                    class="{{ request()->is('DO') ? '' : 'collapsed' }}">
                                    <i class="fa fa-building" aria-hidden="true"></i>
                                    <p>TAP</p>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse {{ request()->is('DO', 'masuk', 'keluar', 'injectvf', 'vrusak', 'BO') ? 'show' : '' }}"
                                    id="base">
                                    <ul class="nav nav-collapse">
                                        @if (session('idtap') == 'SBP_DUMAI' ||
                                                session('idtap') == 'DUMAI' ||
                                                session('idtap') == 'DURI' ||
                                                session('idtap') == 'BENGKALIS' ||
                                                session('idtap') == 'BAGAN BATU' ||
                                                session('idtap') == 'BAGAN SIAPI-API')
                                            <li class="nav-item {{ request()->is('DO') ? 'active' : '' }}">
                                                <a href="{{ url('DO') }}">
                                                    <span class="sub-item">DO Masuk</span>
                                                </a>
                                            </li>
                                        @endif
                                        <li class="nav-item {{ request()->is('masuk') ? 'active' : '' }}">
                                            <a href="{{ url('masuk') }}">
                                                <span class="sub-item">Stok Masuk</span>
                                            </a>
                                        </li>
                                        <li class="nav-item {{ request()->is('keluar') ? 'active' : '' }}">
                                            <a href="{{ url('keluar') }}">
                                                <span class="sub-item">Stok Keluar</span>
                                            </a>
                                        </li>
                                        @if (session('idtap') == 'SBP_DUMAI' ||
                                                session('idtap') == 'DUMAI' ||
                                                session('idtap') == 'DURI' ||
                                                session('idtap') == 'BENGKALIS' ||
                                                session('idtap') == 'BAGAN BATU' ||
                                                session('idtap') == 'BAGAN SIAPI-API')
                                            <li class="nav-item {{ request()->is('BO') ? 'active' : '' }}">
                                                <a href="{{ url('BO') }}">
                                                    <span class="sub-item">Retur BO</span>
                                                </a>
                                            </li>
                                        @endif
                                        <li class="nav-item {{ request()->is('injectvf') ? 'active' : '' }}">
                                            <a href="{{ url('injectvf') }}">
                                                <span class="sub-item">Inject VF</span>
                                            </a>
                                        </li>
                                        <li class="nav-item {{ request()->is('vrusak') ? 'active' : '' }}">
                                            <a href="{{ url('vrusak') }}">
                                                <span class="sub-item">Voucher Rusak</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a data-toggle="collapse" href="#SF">
                                    <i class="fas fa-user" aria-hidden="true"></i>
                                    <p>SALES FORCE</p>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse {{ request()->is('sf*', 'retursf') ? 'show' : '' }}"
                                    id="SF">
                                    <ul class="nav nav-collapse">
                                        <li class="nav-item {{ request()->is('sf-masuk') ? 'active' : '' }}">
                                            <a href="{{ url('sf-masuk') }}">
                                                <span class="sub-item">Stok Masuk</span>
                                            </a>
                                        </li>
                                        <li class="nav-item {{ request()->is('sf-keluar') ? 'active' : '' }}">
                                            <a href="{{ url('sf-keluar') }}">
                                                <span class="sub-item">Stok Keluar</span>
                                            </a>
                                        </li>
                                        <li class="nav-item {{ request()->is('retursf') ? 'active' : '' }}">
                                            <a href="{{ url('retursf') }}">
                                                <span class="sub-item">Retur SF</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            {{-- <li class="nav-item {{ request()->is('detail') ? 'active' : ''}}">
							<a href="{{ url('detail') }}">
								<i class="fas fa-book"></i>
								<p>CEK STOK DAILY</p>
							</a>
						</li> --}}

                        @endif
                        <li class="nav-item mt-5">
                            <a href="{{ route('logout') }}">
                                <i class="fas fa-power-off"></i>
                                <p>LOGOUT</p>
                            </a>
                        </li>

                    </ul>
                </div>
            </div>
        </div>

        @yield('content')

        @stack('scripts')

</body>

</html>
