@extends('layout.layoutcuan')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                <div class="row">
                    <div class="col-md-6 offset-md-2">
                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->has('error'))
                            <div class="alert alert-danger">
                                {{ $errors->first('error') }}
                            </div>
                        @endif
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <h4 class="card-title">REKAPAN NOCAN MSP</h4>
                                    <a href="{{ url('form/form-nocan') }}" class="btn btn-primary btn-round ml-auto">
                                        <i class="fa fa-plus"> </i>
                                        ORDER
                                    </a>
                                </div>

                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="col col-md-6 justify-content">
                                            <form action="#" method="GET">
                                                <label for="cluster">Filter Cluster:</label>
                                                <select name="cluster" class="form-control">
                                                    <option value="">--PILIH CLUSTER--</option>
                                                    <option value="DUMAI BENGKALIS"
                                                        {{ $cluster == 'DUMAI BENGKALIS' ? 'selected' : '' }}>DUMAI
                                                        BENGKALIS
                                                    </option>
                                                    <option value="SIDEMPUAN"
                                                        {{ $cluster == 'SIDEMPUAN' ? 'selected' : '' }}>SIDEMPUAN
                                                    </option>
                                                    <option value="LABUHAN BATU"
                                                        {{ $cluster == 'LABUHAN BATU' ? 'selected' : '' }}>LABUHAN BATU
                                                    </option>
                                                    <option value="PADANG LAWAS"
                                                        {{ $cluster == 'PADANG LAWAS' ? 'selected' : '' }}>PADANG LAWAS
                                                    </option>
                                                </select>
                                                <button type="submit" class="btn btn-primary mt-2">Filter</button>
                                            </form>
                                        </div>
                                    </div>
                                    {{-- row --}}
                                </div>
                                {{-- container-fluid --}}
                            </div>

                            <div class="card-body">
                                <ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ url('nocan') }}" role="tab">LIST READY
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link active" href="{{ url('booking') }}" role="tab">LIST
                                            BOOKING</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ url('jual') }}" role="tab">LIST TERJUAL</a>
                                    </li>
                                </ul>

                            </div>
                            <div class="table-responsive">
                                <table id="add-row" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Tap</th>
                                            <th>Nomor</th>
                                            <th>Booked By</th>
                                            <th>Harga</th>
                                            <th>Status</th>
                                            <th>ID Outlet</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data as $row)
                                            <tr>
                                                <td>{{ $row->tanggal }}</td>
                                                <td> {{ $row->tap }}</td>
                                                <td> {{ $row->nomor }}</td>
                                                <td> {{ $row->booked }}</td>
                                                <td> {{ number_format($row->harga) }}</td>
                                                <td> {{ $row->status }}</td>
                                                <td> {{ $row->outlet }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
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

    <!-- Select2 -->
    <script src="assets/js/plugin/select2/select2.full.min.js"></script>
    <!-- Sweet Alert -->
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- Azzara JS -->
    <script src="assets/js/ready.min.js"></script>

    <script>
        // Add Row
        $("#add-row").DataTable({
            pageLength: 10,
            order: [
                [0, "desc"]
            ],
            columnDefs: [{
                targets: 4, // Kolom "harga"
                searchable: false
            }]
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
