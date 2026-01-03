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
                    <h4 class="page-title">INPUT STOK RETUR SF</h4>
                </div>
                <div class="row">
                    <div class="col-md-6 offset-md-2">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Form Input</div>
                            </div>
                            <div class="card-body">

                                <form action="{{ url('retursf') }}" method="post">
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

                                    <label for="">Status Voucher:</label>
                                    <select name="ketvf" id="" class="form-control mb-2" required>
                                        <option value="">--Pilih--</option>
                                        <option value="OK">OK</option>
                                        <option value="RUSAK">RUSAK</option>
                                        <option value="MATI">MATI</option>
                                    </select>

                                    <label for="">SN :</label>
                                    <input type="text" name="sn" placeholder="Sn Awal - Sn Akhir"
                                        class="form-control mb-2" required>

                                    <label for="">Ket Lain</label>
                                    <input type="text" name="tambahket" placeholder="Keterangan Tambahan"
                                        class="form-control mb-2" required>

                                    <button type="submit" class="btn btn-primary">Submit</button>
                                    <a href="{{ url('retursf') }}" type="submit" class="btn btn-danger">Back</a>
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
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $(function() {
                $('#kategoritap').on('change', function() {
                    let idtap = $('#kategoritap').val();

                    $.ajax({
                        type: 'POST',
                        url: '/form/form-retursf',
                        data: {
                            idtap: idtap
                        },
                        cache: false,

                        success: function(msg) {
                            $('#idsf').html(msg);
                        },
                    })


                })
            })
        });
    </script>

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
