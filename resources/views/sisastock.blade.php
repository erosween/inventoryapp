@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif
                        <div class="card">
                            <div class="card-header">
                                <ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="{{ url('stock') }}" role="tab">Stock All</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ url('stocktap') }}" role="tab">Stock Tap</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ url('stocksf') }}" role="tab">Stock SF</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="col-auto mb-3">
                                    <a href="{{ url('exportexcelall') }}" class="btn btn-success btn-sm btn-rounded"><i
                                            class='fas fa-file-export'></i>Export</a>
                                </div>
                                <div class="table-responsive">
                                    <table id="stock" class="display table table-striped table-hover"
                                        style="
											div.dataTables_wrapper {
												width: 500px;
												margin: 0 auto;
											}">
                                        <thead>
                                            <tr>
                                                <thead>
                                                    <tr>
                                                        <th>ID Tap</th>
                                                        @for ($i = 1; $i <= 42; $i++)
                                                            <th>V{{ $i }}</th>
                                                        @endfor
                                                    </tr>
                                                </thead>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($idtaps as $idtap)
                                                <tr>
                                                    <td>{{ $idtap }}</td>
                                                    @for ($j = 1; $j <= 42; $j++)
                                                        <td>{{ $stocks[$idtap][$j] }}</td>
                                                    @endfor
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>



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
        $("#stock").DataTable({
            pageLength: 10,
        });
    </script>
@endpush
