@extends('layout.layout')

@section('content')

    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                <div class="page-header">
                    <h4 class="page-title">Dashboard</h4>
                </div>

                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body ">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <strong>
                                                    <p class="card-category">STOCK SEGEL</p>
                                                </strong>
                                                <h4 class="card-title">{{ number_format($segel) }} Pcs</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                                <i class="far fa-newspaper"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <strong>
                                                    <p class="card-category">STOCK INJECT</p>
                                                </strong>

                                                <h4 class="card-title">{{ number_format($inject) }} Pcs</h4>


                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                                <i class="far fa-chart-bar"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <strong>
                                                    <p class="card-category">SALES {{ strtoupper($month) }}</p>
                                                </strong>
                                                <h4 class="card-title">{{ number_format($sales) }} Pcs</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- row --}}

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center tex-center">
                                        <h4 class="card-title">MONTHLY SALES</h4>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="stock" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>NO</th>
                                                    <th>TAP</th>
                                                    <th>{{ strtoupper($month1) }} ({{ $tanggal }})</th>
                                                    <th>{{ strtoupper($month) }} ({{ $tanggal }})</th>
                                                    <th>MOM</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($penjualan as $tapId => $data)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $tapId }}</td>
                                                        <td>{{ number_format($data['sales1']) }}</td>
                                                        <td>{{ number_format($data['salesnow']) }}</td>
                                                        <td>
                                                            @if ($data['sales1'] != 0)
                                                                {{ number_format(($data['salesnow'] / $data['sales1'] - 1) * 100, 2) }}%
                                                            @else
                                                                0
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan='2'><strong>TOTAL</strong></td>
                                                    <td><strong>{{ number_format(array_sum(array_column($penjualan, 'sales1'))) }}</strong>
                                                    </td>
                                                    <td><strong>{{ number_format(array_sum(array_column($penjualan, 'salesnow'))) }}</strong>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $sales1Total = array_sum(
                                                                array_column($penjualan, 'sales1'),
                                                            );
                                                            $salesNowTotal = array_sum(
                                                                array_column($penjualan, 'salesnow'),
                                                            );

                                                            // Memastikan $sales1Total tidak sama dengan nol sebelum melakukan pembagian
                                                            $percentage =
                                                                $sales1Total != 0
                                                                    ? ($salesNowTotal / $sales1Total - 1) * 100
                                                                    : 0;
                                                        @endphp

                                                        <strong>{{ number_format($percentage, 2) }}%</strong>
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        {{-- table responsive --}}
                                    </div>
                                </div>
                                {{-- card body --}}
                            </div>
                            {{-- card --}}
                        </div>
                        {{-- col md 12 --}}


                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center tex-center">
                                        <h4 class="card-title">LAST UPDATE</h4>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="stock" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>NO</th>
                                                    <th>TAP</th>
                                                    <th>Tanggal Masuk SF</th>
                                                    <th>Tanggal Penjualan SF</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($tglUpload as $tapId => $data)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $tapId }}</td>
                                                        <td>{{ $data['masuk'] }}</td>
                                                        <td>{{ $data['keluar'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        {{-- table responsive --}}
                                    </div>
                                </div>
                                {{-- card body --}}
                            </div>
                            {{-- card --}}
                        </div>

                        {{-- login sbp dumai --}}
                        @if (session('idtap') == 'SBP_DUMAI')
                            {{-- sales per cluster --}}
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center tex-center">
                                            <h4 class="card-title">SALES PRODUCT DUMAI BENGKALIS</h4>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="stock1" class="display table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>DENOM</th>
                                                        <th>QUANTITY</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($denomdumai as $data)
                                                        <tr>
                                                            <td>{{ $data->denom }}</td>
                                                            <td>{{ number_format($data->qty) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td><strong>GRAND TOTAL</strong></td>
                                                        <td><strong>{{ number_format($grandTotaldb) }}</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                            {{-- table responsive --}}
                                        </div>
                                    </div>
                                    {{-- card body --}}
                                </div>
                                {{-- card --}}
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center tex-center">
                                            <h4 class="card-title">SALES PRODUCT ROKAN HILIR</h4>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="stock2" class="display table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>DENOM</th>
                                                        <th>QUANTITY</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($denomrohil as $data)
                                                        <tr>
                                                            <td>{{ $data->denom }}</td>
                                                            <td>{{ number_format($data->qty) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td><strong>GRAND TOTAL</strong></td>
                                                        <td><strong>{{ number_format($grandTotalrh) }}</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                            {{-- table responsive --}}
                                        </div>
                                    </div>
                                    {{-- card body --}}
                                </div>
                                {{-- card --}}
                            </div>
                        @else
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center tex-center">
                                            <h4 class="card-title">SALES PRODUCT {{ $idtap }}</h4>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="stock1" class="display table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>DENOM</th>
                                                        <th>QTY</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($salesdenom as $data)
                                                        <tr>
                                                            <td>{{ $data->denom }}</td>
                                                            <td>{{ number_format($data->qty) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td><strong>GRAND TOTAL</strong></td>
                                                        <td><strong>{{ number_format($grandTotal) }}</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                            {{-- table responsive --}}
                                        </div>
                                    </div>
                                    {{-- card body --}}
                                </div>
                                {{-- card --}}
                            </div>
                        @endif

                        {{-- col md 12 --}}
                    </div>
                </div>
                {{-- container fluid --}}
            </div>
            {{-- page inner --}}
        </div>
        {{-- content --}}
    </div>
    {{-- main panel --}}



@endsection
@push('scripts')
    <!--   Core JS Files   -->
    <script src="assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery UI -->
    <script src="assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

    <!-- Moment JS -->
    {{-- <script src="assets/js/plugin/moment/moment.min.js"></script> --}}

    <!-- Chart JS -->
    {{-- <script src="assets/js/plugin/chart.js/chart.min.js"></script> --}}

    <!-- jQuery Sparkline -->
    {{-- <script src="assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script> --}}

    <!-- Chart Circle -->
    {{-- <script src="assets/js/plugin/chart-circle/circles.min.js"></script> --}}

    <!-- Datatables -->
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>

    <!-- Bootstrap Notify -->
    <script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

    <!-- Bootstrap Toggle -->
    <script src="assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

    <!-- jQuery Vector Maps -->
    {{-- <script src="assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script> --}}

    <!-- Google Maps Plugin -->
    {{-- <script src="assets/js/plugin/gmaps/gmaps.js"></script> --}}

    <!-- Sweet Alert -->
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- Azzara JS -->
    <script src="assets/js/ready.min.js"></script>



    <script>
        // Add Row
        $("#add-row").DataTable({
            pageLength: 5,
            order: [
                [0, "desc"]
            ]
        });
    </script>

    <script>
        // Add Row untuk total disetiap menu
        $("#add-row1").DataTable({
            searching: false,
            paging: false,
            info: false,
            order: [
                [1, "desc"]
            ]
        });
    </script>

    <script>
        // Add Row
        $("#stock").DataTable({
            pageLength: 10,
        });
    </script>

    {{-- table penjualan perdenom di home --}}
    <script>
        // Add Row
        $("#stock1").DataTable({
            searching: false,
            paging: false,
            info: false,
            order: [
                [1, "desc"]
            ]
        });
    </script>

    <script>
        // Add Row
        $("#stock2").DataTable({
            searching: false,
            paging: false,
            info: false,
            order: [
                [1, "desc"]
            ]
        });
    </script>
@endpush
