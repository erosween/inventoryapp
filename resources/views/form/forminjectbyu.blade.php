@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                <div class="page-header">
                    <h4 class="page-title">INPUT INJECT VF BYU</h4>
                    @if ($errors->has('error'))
                        <div class="alert alert-danger ml-auto">
                            {{ $errors->first('error') }}
                        </div>
                    @endif
                </div>
                <div class="row">
                    <div class="col-md-6 offset-md-2">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Form Input</div>
                            </div>
                            <div class="card-body">

                                <form action="{{ url('form/forminjectbyu') }}" method="post">
                                    @csrf
                                    <label for="">Tanggal :</label>
                                    <input type="date" name="tgl" class="form-control mb-2" id="date" required>

                                    <label for="">Pilih TAP:</label>

                                    <select name="idtap" class="form-control mb-2">
                                        <option value="">--Pilih Tap--</option>
                                        @foreach ($data as $row)
                                            <option value="{{ $row->idtap }}">{{ $row->idtap }}</option>
                                        @endforeach

                                    </select>

                                    <label for="iddenom">Pilih Denom Inject:</label>
                                    <select name="iddenom" id="iddenom" class="form-control mb-2">
                                        <option value="">--Pilih--</option>

                                        @foreach ($denom as $denom)
                                            <option value="{{ $denom->iddenom }}"> {{ $denom->denom }}</option>
                                        @endforeach

                                    </select>

                                    <input type="number" name="qty" class="form-control mb-2" placeholder="Quantity"
                                        required>

                                    <input type="text" name="sn" placeholder="Sn Awal - Sn Akhir"
                                        class="form-control mb-2" required>

                                    <button type="submit" class="btn btn-primary" name="addbaranginject">Submit</button>
                                    <a href="{{ url('injectvf') }}" type="submit" class="btn btn-danger">Back</a>
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
