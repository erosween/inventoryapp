@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                @if ($errors->has('error'))
                    <div class="alert alert-danger ml-auto">
                        {{ $errors->first('error') }}
                    </div>
                @endif

                <div class="page-header">
                    <h4 class="page-title">INPUT STOK KELUAR SF</h4>
                </div>
                <div class="row">
                    <div class="col-md-6 offset-md-2">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Form Input</div>
                            </div>
                            <div class="card-body">

                                <form action="{{ url('sf-keluar') }}" method="post">
                                    @csrf
                                    <label for="">Tanggal :</label>
                                    <input type="date" name="tgl" class="form-control mb-2" id="date" required>

                                    <label for="">TAP :</label>
                                    <select name="idtap" class="form-control mb-2" id="kategoritap" required>
                                        <option value="">-- Pilih --</option>

                                        @foreach ($data as $row)
                                            <option value="{{ $row->idtap }}">{{ $row->idtap }}</option>
                                        @endforeach
                                    </select>

                                    <select name="idsf" id="idsf" class="form-control mb-2"> </select>

                                    <label for="">Denom :</label>
                                    <select name="iddenom" id="" class="form-control mb-2" required>

                                        <option value="">--Pilih Denom--</option>

                                        @foreach ($denom as $row)
                                            <option value="{{ $row->iddenom }}">{{ $row->denom }}</option>
                                        @endforeach

                                    </select>

                                    <input type="number" name="qty" class="form-control mb-2" placeholder="Quantity"
                                        required>

                                    <label for="">Ket Lain(boleh tidak di isi)</label>
                                    <input type="text" name="tambahanket" placeholder="Sn Awal - Sn Akhir"
                                        class="form-control mb-2">

                                    <button type="submit" class="btn btn-primary" name="addbarangmasuk">Submit</button>
                                    <a href="{{ url('sf-keluar') }}" type="submit" class="btn btn-danger">Back</a>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"
        integrity="sha512-pumBsjNRGGqkPzKHndZMaAG+bir374sORyzM3uulLV14lN5LyykqNk8eEeUlUkB3U0M4FApyaHraT65ihJhDpQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        $(document).ready(function() {

            /* CSRF */
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            /* TAP → SF */
            $('#kategoritap').on('change', function() {
                const idtap = $(this).val();

                if (!idtap) {
                    $('#idsf').html('<option value="">-- Pilih SF --</option>');
                    return;
                }

                $('#idsf').html('<option>Loading...</option>');

                $.post('{{ url('/ajax/get-sf') }}', {
                        idtap
                    })
                    .done(function(res) {
                        $('#idsf').html(res);
                    })
                    .fail(function() {
                        $('#idsf').html('<option value="">Gagal load SF</option>');
                    });
            });

            /* ANTI DOUBLE SUBMIT */
            let isSubmitting = false;

            $('form').on('submit', function() {
                if (isSubmitting) return false;

                isSubmitting = true;

                $(this)
                    .find('button[type=submit]')
                    .prop('disabled', true)
                    .text('Submitting...');
            });

            /* BATAS TANGGAL */
            const inputDate = document.getElementById('date');
            if (inputDate) {
                const today = new Date();
                const monthAgo = new Date();
                monthAgo.setMonth(today.getMonth() - 1);

                inputDate.max = today.toISOString().split('T')[0];
                inputDate.min = monthAgo.toISOString().split('T')[0];
            }

        });
    </script>
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
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const inputDate = document.getElementById('date');
            const today = new Date();
            const monthAgo = new Date(today);
            monthAgo.setMonth(today.getMonth() - 1);

            // Mengatur tanggal maksimal hingga hari ini
            inputDate.max = today.toISOString().split('T')[0];
            // Mengatur tanggal minimal ke satu bulan yang lalu
            inputDate.min = monthAgo.toISOString().split('T')[0];
        });
    </script>
@endpush
