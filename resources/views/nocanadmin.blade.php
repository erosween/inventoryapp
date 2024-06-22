@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                <div class="row">
                    <div class="col-md-8 offset-md-2">
                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <h4 class="card-title">REKAPAN NOCAN MSP</h4>
                                </div>

                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="col col-md-6 justify-content">
                                            <form action="#" method="GET">
                                                <label for="cluster">Filter Cluster:</label>
                                                <select name="cluster" class="form-control">
                                                    @if (session('idtap') == 'SB DUMAI')
                                                        <option value="">--PILIH CLUSTER--</option>
                                                        <option value="DUMAI BENGKALIS"
                                                            {{ $cluster == 'DUMAI BENGKALIS' ? 'selected' : '' }}>DUMAI
                                                            BENGKALIS
                                                        </option>
                                                    @else
                                                        <option value="">--PILIH CLUSTER--</option>
                                                        <option value="SIDEMPUAN"
                                                            {{ $cluster == 'SIDEMPUAN' ? 'selected' : '' }}>SIDEMPUAN
                                                        </option>
                                                        <option value="LABUHAN BATU"
                                                            {{ $cluster == 'LABUHAN BATU' ? 'selected' : '' }}>LABUHAN BATU
                                                        </option>
                                                        <option value="PADANG LAWAS"
                                                            {{ $cluster == 'PADANG LAWAS' ? 'selected' : '' }}>PADANG LAWAS
                                                        </option>
                                                    @endif
                                                </select>
                                                <button type="submit" class="btn btn-primary mt-2">Filter</button>
                                            </form>
                                        </div>
                                        {{-- col col-md-6 --}}
                                    </div>
                                    {{-- row --}}
                                </div>
                                {{-- container-fluid --}}
                            </div>
                            {{-- card header --}}

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
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data as $row)
                                            <tr>
                                                <td>{{ $row->tanggal }}</td>
                                                {{-- <td> {{ $row->cluster }}</td> --}}
                                                <td> {{ $row->tap }}</td>
                                                <td> {{ $row->nomor }}</td>
                                                <td> {{ $row->booked }}</td>
                                                <td> {{ number_format($row->harga) }}</td>
                                                <td> {{ $row->status }}</td>
                                                <td> {{ $row->outlet }}</td>
                                                <td class="d-flex justify-content-between">
                                                    <button class="btn btn-warning btn-round btn-sm" data-toggle="modal"
                                                        data-target="#addRowModal{{ $row->id }}">
                                                        Edit
                                                    </button>
                                                    <button class="btn btn-danger btn-round btn-sm" data-toggle="modal"
                                                        data-target="#addRowModalreset{{ $row->id }}">
                                                        Reset
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            {{-- table --}}
                            <!-- Modal edit -->
                            @foreach ($data as $row)
                                <div class="modal fade" id="addRowModal{{ $row->id }}" role="dialog"
                                    aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header no-bd">
                                                <h5 class="modal-title">
                                                    EDIT DATA
                                                </h5>
                                            </div>
                                            <div class="modal-body">
                                                <form action="nocanadmin/{{ $row->id }}" method="post">
                                                    @csrf
                                                    <label for="">NOMOR :</label>
                                                    <input type="number" name="nomor" value="{{ $row->nomor }}"
                                                        class="form-control mb-1" disabled required>
                                                    <label for="">HARGA :</label>
                                                    <input type="number" name="harga" value="{{ $row->harga }}"
                                                        class="form-control mb-1" required>
                                                    <label for="">TAP :</label>
                                                    <input type="text" name="tap" value="{{ $row->tap }}"
                                                        class="form-control mb-1" required>
                                                    <label for="">PENJUAL :</label>
                                                    <input type="text" name="penjual" value="{{ $row->booked }}"
                                                        class="form-control mb-1" required>

                                                    <label for="">OUTLET :</label>
                                                    <input type="number" name="outlet" value="{{ $row->outlet }}"
                                                        class="form-control mb-1">

                                                    <label for="">STATUS
                                                        :</label>
                                                    <select class="form-control mb-2" name="status" required>
                                                        <option value="">--PILIH--</option>
                                                        <option value="PAID">PAID</option>
                                                        <option value="SOLD">SOLD</option>
                                                        <option value="BOOKING">BOOKING</option>
                                                    </select>

                                                    <label for="">UPDATE TANGGAL (JIKA BAYAR) :</label>
                                                    <input type="date" name="tanggal" value="{{ $row->tanggal }}"
                                                        class="form-control mb-1" required>

                                                    <button type="submit" class="btn btn-danger">Submit</button>
                                                    <button type="button" class="btn btn-primary"
                                                        data-dismiss="modal">Close</button>
                                                </form>
                                            </div>
                                            {{-- modal-body --}}
                                        </div>
                                        {{-- modal content --}}
                                    </div>
                                    {{-- modal-dialog --}}
                                </div>
                                {{-- modal --}}
                            @endforeach

                            <!-- Modal reset-->
                            @foreach ($data as $row)
                                <div class="modal fade" id="addRowModalreset{{ $row->id }}" role="dialog"
                                    aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header no-bd">
                                                <h3 class="modal-title">
                                                    Status nomor ini akan diupdate menjadi "READY"</h3>
                                            </div>
                                            <div class="modal-body">
                                                <form action="reset/{{ $row->id }}" method="post">
                                                    @csrf
                                                    <label for="">NOMOR :</label>
                                                    <input type="number" name="nomor" value="{{ $row->nomor }}"
                                                        class="form-control mb-1" disabled required>
                                                    <label for="">HARGA :</label>
                                                    <input type="number" name="harga" value="{{ $row->harga }}"
                                                        class="form-control mb-1" disabled required>
                                                    <label for="">TAP :</label>
                                                    <input type="text" name="tap" value="{{ $row->tap }}"
                                                        class="form-control mb-1" disabled required>
                                                    <label for="">PENJUAL :</label>
                                                    <input type="text" name="penjual" value="{{ $row->booked }}"
                                                        class="form-control mb-1" disabled required>
                                                    <label for="">ID OUTLET :</label>
                                                    <input type="number" name="outlet" value="{{ $row->outlet }}"
                                                        class="form-control mb-1" disabled required>
                                                    <label for="">STATUS :</label>
                                                    <input type="text" value="{{ $row->status }}"
                                                        class="form-control mb-1"disabled required>
                                                    <button type="submit" class="btn btn-danger">Submit</button>
                                                    <button type="button" class="btn btn-primary"
                                                        data-dismiss="modal">Close</button>
                                                </form>
                                            </div>
                                            {{-- modal-body --}}
                                        </div>
                                        {{-- modal content --}}
                                    </div>
                                    {{-- modal-dialog --}}
                                </div>
                                {{-- modal --}}
                            @endforeach
                        </div>
                        {{-- card --}}
                    </div>
                    {{-- colmd offset --}}

                </div>
                {{-- row --}}
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
@endpush
