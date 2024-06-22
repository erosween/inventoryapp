@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                @if ($errors->has('error'))
                    <div class="alert alert-danger">
                        {{ $errors->first('error') }}
                    </div>
                @endif
                <div class="page-header">
                    <h4 class="page-title">INPUT VOUCHER RUSAK TAP</h4>
                </div>
                <div class="row">
                    <div class="col-md-6 offset-md-2">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Form Input</div>
                            </div>
                            <div class="card-body">

                                <form action="{{ url('vrusak') }}" method="post">
                                    @csrf
                                    <label for="">Tanggal :</label>
                                    <input type="date" name="tgl" class="form-control mb-2" id="date" required>

                                    <label for="">Pengirim :</label>
                                    <select name="pengirim" class="form-control mb-2" id="kategorisegelmasuk" required>
                                        <option value="">-- Pilih --</option>

                                        @foreach ($data as $row)
                                            <option value="{{ $row->idtap }}">{{ $row->idtap }}</option>
                                        @endforeach
                                    </select>

                                    <select type="text" name="iddenom" placeholder="Denom" class="form-control mb-2"
                                        required>

                                        <option value="">--Pilih--</option>

                                        @foreach ($denom as $denom)
                                            <option value="{{ $denom->iddenom }}">{{ $denom->denom }}</option>
                                        @endforeach
                                    </select>

                                    <input type="number" name="qty" class="form-control mb-2" placeholder="Quantity"
                                        required>

                                    <input type="text" name="sn" placeholder="Sn Awal - Sn Akhir"
                                        class="form-control mb-2" required>

                                    <label for="">Status Voucher:</label>
                                    <select name="ketvf" id="" class="form-control mb-2" required>
                                        <option value="">--Pilih--</option>
                                        <option value="RUSAK">RUSAK</option>
                                        <option value="MATI">MATI</option>
                                    </select>

                                    <input type="text" name="tambahanket" placeholder="Tambah Keterangan"
                                        class="form-control mb-2" required>

                                    <button type="submit" class="btn btn-primary" name="addbarangmasuk">Submit</button>
                                    <a href="{{ url('vrusak') }}" type="submit" class="btn btn-danger">Back</a>
                            </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!--   Core JS Files   -->
    <script src="../assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery UI -->
    <script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

    <!-- Moment JS -->
    <script src="../assets/js/plugin/moment/moment.min.js"></script>

    <!-- Chart JS -->
    <script src="../assets/js/plugin/chart.js/chart.min.js"></script>

    <!-- jQuery Sparkline -->
    <script src="../assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

    <!-- Chart Circle -->
    <script src="../assets/js/plugin/chart-circle/circles.min.js"></script>

    <!-- Datatables -->
    <script src="../assets/js/plugin/datatables/datatables.min.js"></script>

    <!-- Bootstrap Notify -->
    <script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

    <!-- Bootstrap Toggle -->
    <script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

    <!-- jQuery Vector Maps -->
    <script src="../assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
    <script src="../assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>

    <!-- Google Maps Plugin -->
    <script src="../assets/js/plugin/gmaps/gmaps.js"></script>

    <!-- Sweet Alert -->
    <script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- Azzara JS -->
    <script src="../assets/js/ready.min.js"></script>
@endpush
