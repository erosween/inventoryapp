@extends('layout.layoutcuan')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                <div class="page-header">
                </div>
                <div class="row">
                    <div class="col-md-6 offset-md-2">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Form Order</div>
                                @if ($errors->has('error'))
                                    <div class="alert alert-danger ml-auto">
                                        {{ $errors->first('error') }}
                                    </div>
                                @endif
                            </div>
                            <div class="card-body">
                                <form id="myForm" action="{{ url('nocanproses') }}" method="post">
                                    @csrf
                                    <label for="select2">NOMOR :</label>
                                    <select class="form-control" id="select2" name="nomor">
                                        <option value="">Pilih Nomor</option>
                                        @foreach ($data as $row)
                                            <option value="{{ $row->nomor }}">{{ $row->nomor }}</option>
                                        @endforeach
                                    </select>

                                    <label for="tgl" class="mt-3">TANGGAL :</label>
                                    <input type="date" class="form-control mb-3" name="tgl" required>

                                    <label for="outlet" class="mt-3">ID OUTLET (JIKA JUAL KE OUTLET) :</label>
                                    <input type="number" class="form-control mb-3" name="outlet">

                                    <label for="tap">TAP :</label>
                                    <select class="form-control" id="select3" name="tap">
                                        <option value="">Pilih TAP</option>
                                        <option value="DUMAI">DUMAI</option>
                                        <option value="DURI">DURI</option>
                                        <option value="BENGKALIS">BENGKALIS</option>
                                        <option value="SEI PAKNING">SEI PAKNING</option>
                                        <option value="RUPAT">RUPAT</option>
                                        <option value="BAGAN BATU">BAGAN BATU</option>
                                        <option value="BAGAN SIAPI-API">BAGAN SIAPI-API</option>
                                        <option value="UJUNG TANJUNG">UJUNG TANJUNG</option>
                                        <option value="AEK KANOPAN">AEK KANOPAN</option>
                                        <option value="AJAMU">AJAMU</option>
                                        <option value="KUALUH LEIDONG">KUALUH LEIDONG</option>
                                        <option value="PANIGORAN">PANIGORAN</option>
                                        <option value="RANTAU PRAPAT">RANTAU PRAPAT</option>
                                        <option value="GUNUNG TUA">GUNUNG TUA</option>
                                        <option value="KOTA PINANG">KOTA PINANG</option>
                                        <option value="POD CIKAMPAK">POD CIKAMPAK</option>
                                        <option value="POD SOSA">POD SOSA</option>
                                        <option value="POD LANGGA PAYUNG">POD LANGGA PAYUNG</option>
                                        <option value="SIBUHUAN">SIBUHUAN</option>
                                        <option value="BATANG TORU">BATANG TORU</option>
                                        <option value="PADANGSIDIMPUAN">PADANGSIDIMPUAN</option>
                                        <option value="PANYABUNGAN">PANYABUNGAN</option>
                                        <option value="POD KOTANOPAN">POD KOTANOPAN</option>
                                        <option value="POD SIPIROK">POD SIPIROK</option>
                                        <option value="SINUNUKAN">SINUNUKAN</option>
                                    </select>

                                    <label for="penjual" class="mt-3">PENJUAL :</label>
                                    <input id="penjual" name="penjual" type="text" class="form-control mb-3" required>

                                    <label for="status">STATUS :</label>
                                    <select name="status" class="form-control mb-3" required>
                                        <option value="">--PILIH--</option>
                                        <option value="BOOKING">BOOKING</option>
                                        <option value="SOLD">SOLD</option>
                                    </select>

                                    {{-- <label for="harga">HARGA :</label>
                                    <input type="text" id="harga" name="harga" class="form-control mb-3"> --}}

                                    <button type="submit" class="btn btn-primary">SUBMIT</button>
                                    <a href="{{ url('nocan') }}" class="btn btn-danger">Back</a>

                                </form>
                            </div>
                            {{-- card body --}}
                        </div>
                        {{-- card --}}
                    </div>
                    {{-- col md --}}
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const penjualInput = document.getElementById('penjual');
            const hargaInput = document.getElementById('harga');

            // Mengubah input penjual menjadi huruf besar
            penjualInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });
    </script>
    <!-- JavaScript untuk memformat input harga -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hargaInput = document.getElementById('harga');
            const form = document.getElementById('myForm');

            // Event listener untuk memformat angka saat pengguna mengetik
            hargaInput.addEventListener('input', function(e) {
                // Hapus karakter non-digit
                let value = this.value.replace(/[^\d]/g, '');
                // Format menjadi ribuan
                this.value = formatRibuan(value);
            });

            // Event listener untuk menghapus pemformatan sebelum form disubmit
            form.addEventListener('submit', function(e) {
                // Hapus semua tanda koma
                hargaInput.value = hargaInput.value.replace(/,/g, '');
            });

            // Fungsi untuk memformat angka menjadi format ribuan
            function formatRibuan(angka) {
                return angka.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#select2').select2({
                width: '100%',

            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#select3').select2({
                width: '100%',

            });
        });
    </script>
@endpush
