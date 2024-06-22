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
                                                    <p class="card-category">BOOKING</p>
                                                </strong>
                                                <h4 class="card-title">
                                                    {{ number_format($booking) }} Pcs
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                                    <p class="card-category">SOLD</p>
                                                </strong>
                                                <h4 class="card-title">
                                                    {{ number_format($sold) }} Pcs
                                                </h4>
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
                                                    <p class="card-category">PAID</p>
                                                </strong>

                                                <h4 class="card-title">
                                                    {{ number_format($paid) }} Pcs
                                                </h4>
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
                                                    <p class="card-category">READY</p>
                                                </strong>
                                                <h4 class="card-title">
                                                    {{ number_format($ready) }} Pcs
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- row --}}

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <strong class="mb-3">Export Data</strong>
                                    <div class="d-flex align-items-center tex-center fluid mt-2">
                                        <a href="/exportnocan" class="btn btn-success btn-sm btn-rounded">
                                            <i class="fas fa-file-export"></i> Export
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center tex-center">
                                        <h4 class="card-title">TARGET JUALAN KE OUTLET</h4>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="add-row2" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>TAP</th>
                                                    <th>TARGET</th>
                                                    <th>GRADE A</th>
                                                    <th>GRADE B</th>
                                                    <th>GRADE C</th>
                                                    <th>GRADE D</th>
                                                    <th>TOTAL REALISASI</th>
                                                    <th>GAP</th>
                                                    <th>%ACH</th>
                                                    <th>TOTAL INSENTIF</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($datas as $row)
                                                    <tr>
                                                        <td>{{ $row->tap }}</td>
                                                        <td>{{ $row->target }}</td>
                                                        <td>{{ $row->grades['A'] }}</td>
                                                        <td>{{ $row->grades['B'] }}</td>
                                                        <td>{{ $row->grades['C'] }}</td>
                                                        <td>{{ $row->grades['D'] }}</td>
                                                        <td>{{ $row->total_sold }}</td>
                                                        <td>{{ $row->target - $row->total_sold }}</td>
                                                        <td>
                                                            @if ($row->target > 0)
                                                                {{ number_format(($row->total_sold / $row->target) * 100, 2) }}%
                                                            @else
                                                                0.00%
                                                            @endif
                                                        </td>
                                                        <td>{{ number_format($row->total_insentif) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>GRAND TOTAL</th>
                                                    <th>{{ number_format($grandTotals->target) }}</th>
                                                    <th>{{ $grandTotals->grades['A'] }}</th>
                                                    <th>{{ $grandTotals->grades['B'] }}</th>
                                                    <th>{{ $grandTotals->grades['C'] }}</th>
                                                    <th>{{ $grandTotals->grades['D'] }}</th>
                                                    <th>{{ $grandTotals->total_sold }}</th>
                                                    <th>{{ number_format($grandTotals->target - $grandTotals->total_sold) }}
                                                    </th>
                                                    <th>
                                                        @if ($row->target > 0)
                                                            {{ number_format(($row->total_sold / $row->target) * 100, 2) }}%
                                                        @else
                                                            0.00%
                                                        @endif
                                                    </th>
                                                    <th>{{ number_format($grandTotals->total_insentif) }}</th>
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

                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center tex-center">
                                        <h4 class="card-title">PENJUALAN ALL PER-TAP</h4>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="add-row" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>TAP</th>
                                                    <th>BOOKING</th>
                                                    <th>SOLD</th>
                                                    <th>PAID</th>
                                                    <th>TOTAL</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($data as $row)
                                                    <tr>
                                                        <td>{{ $row->tap }}</td>
                                                        <td>{{ $row->total_status_booking }}</td>
                                                        <td>{{ $row->total_status_sold }}</td>
                                                        <td>{{ $row->total_status_paid }}</td>
                                                        <td>{{ $row->total_status_sold + $row->total_status_paid + $row->total_status_booking }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>Grand Total</th>
                                                    <th>{{ $grandTotalBooking }}</th>
                                                    <th>{{ $grandTotalSold }}</th>
                                                    <th>{{ $grandTotalPaid }}</th>
                                                    <td><strong>{{ $grandTotalSold + $grandTotalPaid + $grandTotalBooking }}</strong>
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

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center tex-center">
                                        <h4 class="card-title">PENJUALAN PER SALES</h4>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="add-row1" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>TAP</th>
                                                    <th>PENJUAL</th>
                                                    <th>BOOKING</th>
                                                    <th>SOLD</th>
                                                    <th>PAID</th>
                                                    <th>TOTAL</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($dataSF as $row)
                                                    <tr>
                                                        <td>{{ $row->tap }}</td>
                                                        <td>{{ $row->booked }}</td>
                                                        <td>{{ $row->total_status_booking }}</td>
                                                        <td>{{ $row->total_status_sold }}</td>
                                                        <td>{{ $row->total_status_paid }}</td>
                                                        <td>{{ $row->total_status_paid + $row->total_status_booking + $row->total_status_sold }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>Grand Total</th>
                                                    <th></th>
                                                    <th>{{ $grandTotalBookingsf }}</th>
                                                    <th>{{ $grandTotalSoldsf }}</th>
                                                    <th>{{ $grandTotalPaidsf }}</th>
                                                    <th>{{ $grandTotalPaidsf + $grandTotalBookingsf + $grandTotalSoldsf }}
                                                    </th>
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


                    </div>
                    {{-- row --}}

                    {{-- row --}}
                </div>

            </div>

        </div>
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


        <!-- Datatables -->
        <script src="assets/js/plugin/datatables/datatables.min.js"></script>

        <!-- Bootstrap Notify -->
        <script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

        <!-- Bootstrap Toggle -->
        <script src="assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

        <!-- Sweet Alert -->
        <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

        <!-- Azzara JS -->
        <script src="assets/js/ready.min.js"></script>



        <script>
            // Add Row
            $("#add-row").DataTable({
                pageLength: 20,
                order: [
                    [1, "desc"]
                ]
            });

            $("#add-row1").DataTable({
                pageLength: 10,
                order: [
                    [0, "asc"]
                ]
            });

            $("#add-row2").DataTable({
                pageLength: 10,
                order: [
                    [0, "asc"]
                ]
            });
        </script>
    @endpush
