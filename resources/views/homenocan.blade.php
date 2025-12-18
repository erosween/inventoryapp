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
                    {{-- penjualan bulanan --}}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center tex-center">
                                        <h4 class="card-title">PENJUALAN BULANAN</h4>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <form action="#" method="GET">
                                            <label for="tahun" class="mt-2">Filter Tahun:</label>
                                            <select name="tahun" id="tahun" class="form-control">
                                                <option value="">--Pilih Tahun--</option>
                                                <?php
                                                $selectedYear = request('tahun'); // Mendapatkan tahun yang dipilih
                                                $years = [2024, 2025]; // Daftar tahun yang tersedia
                                                ?>
                                                @foreach ($years as $year)
                                                    <option value="{{ $year }}"
                                                        {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-primary mt-2">Filter</button>
                                        </form>
                                    </div>

                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="add-row3" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>TAP</th>
                                                    @foreach ($months as $month)
                                                        <th>{{ $month }}</th>
                                                    @endforeach
                                                    <th>Total</th> <!-- Total Penjualan untuk semua bulan -->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($result as $tap => $sales)
                                                    <tr>
                                                        <td>{{ $tap }}</td>
                                                        @foreach ($months as $month)
                                                            <td>{{ $sales[$month] }}</td>
                                                        @endforeach
                                                        <td>{{ array_sum($sales) }}</td>
                                                        <!-- Penjumlahan semua bulan -->
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>Total</th>
                                                    @foreach ($totalFooter as $total)
                                                        <th>{{ $total }}</th>
                                                    @endforeach
                                                    <th>{{ array_sum($totalFooter) }}</th> <!-- Total dari semua bulan -->
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

                    {{-- end penjualan bulanan --}}

                    {{-- <div class="row">
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
                                                        @if ($grandTotals->target > 0)
                                                            {{ number_format(($grandTotals->total_sold / $grandTotals->target) * 100, 2) }}%
                                                        @else
                                                            0.00%
                                                        @endif
                                                    </th>
                                                    <th>{{ number_format($grandTotals->total_insentif) }}</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        
                                    </div>
                                </div>
                                
                            </div>
                            
                        </div>

                    </div> --}}

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center tex-center">
                                        <h4 class="card-title">DETAIL PENJUALAN</h4>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="add-row1" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>TAP</th>
                                                    <th>STOK AWAL</th>
                                                    <th>KARYAWAN</th>
                                                    <th>JUAL KE OUTLET</th>
                                                    <th>DS</th>
                                                    <th>TOTAL PENJUALAN</th>
                                                    <th>SISA STOK</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($datadetail as $row)
                                                    <tr>
                                                        <td>{{ $row->tap }}</td>
                                                        <td>{{ number_format($row->total_nomor) }}</td>
                                                        <td>{{ $row->total_karyawan }}</td>
                                                        <td>{{ $row->total_penjualan }}</td>
                                                        <td>{{ $row->total_ds }}</td>
                                                        <td>{{ number_format($row->total_karyawan + $row->total_penjualan + $row->total_ds) }}
                                                        </td>
                                                        <td>{{ number_format($row->total_nomor - ($row->total_karyawan + $row->total_penjualan + $row->total_ds)) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>Grand Total</th>
                                                    <th>{{ number_format($grandTotalNomor) }}</th>
                                                    <th>{{ $grandTotalKaryawan }}</th>
                                                    <th>{{ $grandTotalPenjualan }}</th>
                                                    <th>{{ $grandTotalDS }}</th>
                                                    <th>{{ number_format($grandTotalKaryawan + $grandTotalPenjualan + $grandTotalDS) }}
                                                    </th>
                                                    <th>{{ number_format($grandTotalNomor - ($grandTotalKaryawan + $grandTotalPenjualan + $grandTotalDS)) }}
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

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center tex-center">
                                        <h4 class="card-title">STATUS PERDANA</h4>
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
                                                        <td>{{ number_format($row->total_status_booking) }}</td>
                                                        <td>{{ number_format($row->total_status_sold) }}</td>
                                                        <td>{{ number_format($row->total_status_paid) }}</td>
                                                        <td>{{ number_format($row->total_status_sold + $row->total_status_paid + $row->total_status_booking) }}
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
                                        <table id="add-row4" class="display table table-striped table-hover">
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

            $("#add-row4").DataTable({
                pageLength: 10,
                order: [
                    [0, "asc"]
                ]
            });

            $("#add-row3").DataTable({
                pageLength: 10,
                order: [
                    [0, "asc"]
                ]
            });
        </script>
    @endpush
