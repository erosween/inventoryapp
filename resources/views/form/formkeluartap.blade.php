@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                {{-- Header --}}
                <div class="page-header">
                    <h4 class="page-title">
                        Input Stok Keluar TAP
                    </h4>
                    <div class="text-muted">
                        Kirim barang ke TAP lain (menunggu approval)
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-xl-8 col-lg-9 col-md-11">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Pengiriman TAP</strong>
                                <div class="text-muted small">
                                    Stok akan berpindah setelah disetujui TAP penerima
                                </div>
                            </div>

                            <form action="{{ url('form/formkeluartap') }}" method="POST">
                                @csrf

                                <div class="card-body">
                                    <div class="row">

                                        {{-- Tanggal --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Tanggal</label>
                                                <input type="date" name="tgl" id="date" class="form-control"
                                                    required>
                                            </div>
                                        </div>

                                        {{-- Pengirim --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>PENGIRIM</label>
                                                <select name="pengirim" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->idtap }}">{{ $row->idtap }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Denom --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>DENOM</label>
                                                <select name="iddenom" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach ($denom as $d)
                                                        <option value="{{ $d->iddenom }}">{{ $d->denom }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Qty --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Quantity</label>
                                                <input type="number" name="qty" class="form-control" min="1"
                                                    required>
                                            </div>
                                        </div>

                                        {{-- Stok TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label>Stok TAP Pengirim</label>
                                                <input type="text" id="stok_tap_info" class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="alert alert-danger d-none" id="stok_tap_warning">
                                            Quantity melebihi stok TAP pengirim
                                        </div>

                                        {{-- SN --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>SN</label>
                                                <input type="text" name="sn" class="form-control"
                                                    placeholder="SN Awal - SN Akhir" required>
                                            </div>
                                        </div>

                                        {{-- Penerima --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Penerima</label>
                                                <select name="penerima" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach ($tappenerima as $tap)
                                                        <option value="{{ $tap->idtap }}">{{ $tap->idtap }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Keterangan --}}
                                        <div class="col-md-12">
                                            <div class="form-group mb-1">
                                                <label>Tambahan Keterangan (Opsional)</label>
                                                <input type="text" name="tambahket" class="form-control">
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                {{-- Footer --}}
                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('keluar') }}" class="btn btn-light">
                                        Kembali
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        Simpan
                                    </button>
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
        $(document).ready(function() {

            $('.select2').select2({
                placeholder: 'Pilih / Cari…',
                allowClear: true,
                width: '100%'
            });

            // autofocus search
            $(document).on('select2:open', () => {
                document.querySelector('.select2-search__field')?.focus();
            });

        });

        let currentTapStock = 0;

        function loadTapStock() {
            const idtap = $('select[name="pengirim"]').val();
            const iddenom = $('select[name="iddenom"]').val();

            if (!idtap || !iddenom) return;

            $('#stok_tap_info').val('Loading...');

            $.post('{{ route('ajax.get-stock-tap-pengirim') }}', {
                idtap,
                iddenom
            }).done(res => {
                currentTapStock = parseInt(res.stock) || 0;

                if (currentTapStock <= 0) {
                    $('#stok_tap_info').val('Stok TAP habis');
                    $('#stok_tap_warning').removeClass('d-none');
                } else {
                    $('#stok_tap_info').val(currentTapStock + ' pcs');
                    $('#stok_tap_warning').addClass('d-none');
                }
            });
        }

        $('select[name="pengirim"], select[name="iddenom"]').on('change', loadTapStock);

        $('input[name="qty"]').on('input', function() {
            const qty = parseInt($(this).val()) || 0;

            if (qty > currentTapStock) {
                $(this).addClass('is-invalid');
                $('#stok_tap_warning').removeClass('d-none');
            } else {
                $(this).removeClass('is-invalid');
                $('#stok_tap_warning').addClass('d-none');
            }
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
