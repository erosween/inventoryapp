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

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Pengiriman TAP</strong>
                                <div class="text-muted small">
                                    Stok akan berpindah setelah disetujui TAP penerima
                                </div>
                            </div>

                            <form action="{{ url('form/formkeluartap') }}" method="POST" id="formKeluarTap">
                                @csrf

                                <div class="card-body">
                                    <div class="row">

                                        {{-- Tanggal --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Tanggal</label>
                                                <input type="date" name="tgl" id="date" class="form-control"
                                                    value="{{ date('Y-m-d') }}" required>
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
                                                <input type="number" name="qty" id="qty" class="form-control" min="1"
                                                    required>
                                            </div>
                                        </div>

                                        {{-- Stok TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Stok TAP Pengirim</label>
                                                <input type="text" id="stok_tap_info" class="form-control mb-1" readonly>
                                                <div class="text-danger small d-none" id="stok_tap_warning" style="font-weight: 600;">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> Quantity melebihi stok TAP pengirim
                                                </div>
                                            </div>
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
                                    <button type="submit" id="submitBtn" class="btn btn-primary">
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

            $('.select2').each(function() {
                $(this).select2({
                    placeholder: 'Pilih / Cari…',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(this).closest('.card-body')
                });
            });

            // autofocus search
            $(document).on('select2:open', () => {
                setTimeout(function() {
                    document.querySelector('.select2-search__field')?.focus();
                }, 50);
            });

        });

        let currentTapStock = 0;
        let tapStockData = {}; // Cache data stok TAP pengirim

        function loadTapStock() {
            const iddenom = $('select[name="iddenom"]').val();

            if (!iddenom) {
                currentTapStock = 0;
                $('#stok_tap_info').val('');
                return;
            }

            // Ambil dari cache lokal (cepat)
            currentTapStock = parseInt(tapStockData[iddenom]) || 0;

            if (currentTapStock <= 0) {
                $('#stok_tap_info').val('Stok TAP habis');
                $('#stok_tap_warning').removeClass('d-none');
            } else {
                $('#stok_tap_info').val(currentTapStock + ' pcs');
                $('#stok_tap_warning').addClass('d-none');
            }
            
            // Re-validate qty
            validateQty();
        }

        $('select[name="pengirim"]').on('change', function() {
            const idtap = $(this).val();
            
            tapStockData = {}; // Clear cache
            $('#stok_tap_info').val('');

            if (!idtap) return;

            // Bulk load TAP sender stock
            $.post('{{ route('ajax.get-all-stock-tap-keluar') }}', {
                idtap: idtap,
                _token: '{{ csrf_token() }}'
            }).done(res => {
                tapStockData = res;
                // Trigger refresh on denom to update display
                loadTapStock();
            });
        });

        $('select[name="iddenom"]').on('change', loadTapStock);

        function validateQty() {
            const qty = parseInt($('input[name="qty"]').val()) || 0;

            if (qty > currentTapStock || currentTapStock <= 0) {
                $('input[name="qty"]').addClass('is-invalid');
                $('#stok_tap_warning').removeClass('d-none');
                $('#submitBtn').prop('disabled', true);
            } else {
                $('input[name="qty"]').removeClass('is-invalid');
                $('#stok_tap_warning').addClass('d-none');
                $('#submitBtn').prop('disabled', false);
            }
        }

        $('input[name="qty"]').on('input', validateQty);

        // 🔒 BLOCK submit if qty > stock (belt-and-suspenders)
        $('#formKeluarTap').on('submit', function(e) {
            const qty = parseInt($('#qty').val()) || 0;
            if (qty > currentTapStock || currentTapStock <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Stok Tidak Cukup',
                    text: 'Quantity (' + qty + ') melebihi stok TAP (' + currentTapStock + ')'
                });
                return false;
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
