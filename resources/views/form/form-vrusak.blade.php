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
