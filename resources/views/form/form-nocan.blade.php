@extends('layout.layoutcuan')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">
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
                                    <!-- Divisi -->
                                    <div class="mb-4">
                                        <label class="block text-gray-700 font-medium">DIVISI</label>
                                        <select class ="form-control "name="divisi" id="divisi"
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-300">
                                            <option value="outlet">Outlet</option>
                                            <option value="ds">DS</option>
                                            <option value="karyawan">Karyawan</option>
                                        </select>
                                    </div>

                                    <label for="select2">NOMOR</label>
                                    <select class="form-control" id="select2" name="nomor" required>
                                        <option value="">Pilih Nomor</option>
                                        @foreach ($data as $row)
                                            <option value="{{ $row->nomor }}" data-tap="{{ $row->tap }}">
                                                {{ $row->nomor }}</option>
                                        @endforeach
                                    </select>

                                    <label class="block text-gray-700 font-medium mt-3">LOKASI NOCAN</label>
                                    <select class="form-control" id="lokasi" name="tap" disabled>
                                        <option value="">Pilih Nomor dulu</option>
                                    </select>

                                    <label for="tgl" class="mt-3">TANGGAL</label>
                                    <input type="date" class="form-control mb-3" name="tgl" id="date"
                                        value="{{ date('Y-m-d') }}" required>

                                    <label class="block text-gray-700 font-medium">ID OUTLET</label>
                                    <input type="number" name="outlet" id="id_outlet"
                                        class="form-control mb-3 w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-300"
                                        required>

                                    <label for="tap">TAP PEMBELI:</label>
                                    <select class="form-control" id="select3" name="tap" required>
                                        <option value="">Pilih TAP</option>
                                        <option value="DUMAI">DUMAI</option>
                                        <option value="DURI">DURI</option>
                                        <option value="BENGKALIS">BENGKALIS</option>
                                        <option value="SEI PAKNING">SEI PAKNING</option>
                                        <option value="RUPAT">RUPAT</option>
                                        <option value="BAGAN BATU">BAGAN BATU</option>
                                        <option value="BAGAN SIAPI-API">BAGAN SIAPI-API</option>
                                        <option value="UJUNG TANJUNG">UJUNG TANJUNG</option>
                                    </select>

                                    <label for="penjual" class="mt-3">PENJUAL :</label>
                                    <input id="penjual" name="penjual" type="text" class="form-control mb-3" required>

                                    <label for="status">STATUS :</label>
                                    <select name="status" class="form-control mb-3" required>
                                        <option value="">--PILIH--</option>
                                        <option value="BOOKING">BOOKING</option>
                                        <option value="SOLD">SOLD</option>
                                    </select>

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
    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
    <script>
        $(document).ready(function() {
            $('#select2').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var tapValue = selectedOption.data('tap');

                if (tapValue) {
                    $('#lokasi').html('<option value="' + tapValue + '">' + tapValue + '</option>');
                    $('#lokasi').prop('disabled', false);
                } else {
                    $('#lokasi').html('<option value="">Pilih Nomor dulu</option>');
                    $('#lokasi').prop('disabled', true);
                }
            });
        });
    </script>
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
            $('#select2, #select3').each(function() {
                $(this).select2({
                    width: '100%',
                    dropdownParent: $(this).closest('.card-body')
                });
            });

            $(document).on('select2:open', () => {
                setTimeout(function() {
                    document.querySelector('.select2-search__field')?.focus();
                }, 50);
            });
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

    <script>
        const divisiSelect = document.getElementById("divisi");
        const idOutletInput = document.getElementById("id_outlet");
        const karyawanInput = document.getElementById("karyawan");

        divisiSelect.addEventListener("change", function() {
            if (this.value === "ds") {
                idOutletInput.value = "123";
                idOutletInput.readOnly = true;
            } else if (this.value === "karyawan") {
                idOutletInput.value = "1";
                idOutletInput.readOnly = true;
            } else {
                idOutletInput.value = "";
                idOutletInput.readOnly = false;
            }
        });
    </script>
@endpush
