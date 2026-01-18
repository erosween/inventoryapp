@php
    $idtap = session('idtap');
@endphp

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
    <!-- SELECT2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <style>
        /* Samakan tinggi Select2 dengan Bootstrap */
        .select2-container .select2-selection--single {
            height: 38px;
            padding: 5px 10px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 26px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        /* Biar dropdown tidak ketutup */
        .select2-container {
            width: 100% !important;
            z-index: 1055;
        }

        /* kolom validasi qty */
        .is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, .25);
        }
    </style>
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
                {{-- <button class="topbar-toggler more"><i class="fa fa-ellipsis-v"></i></button> --}}
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
                            <li class="nav-item {{ request()->is('form/form-nocan') ? 'active' : '' }}">
                                <a href="{{ url('form/form-nocan') }}">
                                    <i class="fas fa-book" aria-hidden="true"></i>
                                    <p>FORM ORDER</p>
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
                                <div class="collapse {{ request()->is('DO', 'masuk', 'keluar', 'injectvf', 'vrusak', 'bo') ? 'show' : '' }}"
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
                                            <li class="nav-item {{ request()->is('bo') ? 'active' : '' }}">
                                                <a href="{{ url('bo') }}">
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
        {{-- JQUERY --}}
        <script src="{{ asset('assets/js/core/jquery.3.2.1.min.js') }}"></script>

        <script>
            /* ================= CSRF ================= */
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        </script>

        {{-- BOOTSTRAP --}}
        <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
        <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">


        {{-- AZZARA PLUGINS --}}
        <script src="{{ asset('assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js') }}"></script>
        <script src="{{ asset('assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js') }}"></script>
        <script src="{{ asset('assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js') }}"></script>
        <script src="{{ asset('assets/js/plugin/datatables/datatables.min.js') }}"></script>
        <script src="{{ asset('assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js') }}"></script>
        <script src="{{ asset('assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js') }}"></script>
        <script src="{{ asset('assets/js/plugin/sweetalert/sweetalert.min.js') }}"></script>

        {{-- SELECT2 (INI PENTING) --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

        {{-- AZZARA CORE --}}
        <script src="{{ asset('assets/js/ready.min.js') }}"></script>
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

        {{-- SCRIPT PER VIEW --}}
        @stack('scripts')

    </div>
    {{-- ===============================
   GLOBAL SWEET ALERT
=============================== --}}
    <script>
        /* ===============================
                           DELETE CONFIRM (GLOBAL)
                        =============================== */
        $(document).on('submit', '.form-delete', function(e) {
            e.preventDefault();

            let form = this;

            Swal.fire({
                title: 'Yakin hapus data?',
                text: 'Data akan dihapus dan stok akan disesuaikan',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        /* ===============================
           SUCCESS MESSAGE
        =============================== */
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false
            });
        @endif

        /* ===============================
           ERROR MESSAGE
        =============================== */
        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: '{{ session('error') }}'
            });
        @endif
    </script>


</body>

</html>
