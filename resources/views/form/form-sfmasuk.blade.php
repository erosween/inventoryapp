@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                {{-- Header --}}
                <div class="page-header">
                    <h4 class="page-title">Input Stok Masuk SF</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Input</strong>
                                <div class="text-muted small">
                                    Input data stok masuk untuk Sales Force
                                </div>
                            </div>

                            <form action="{{ url('sf-masuk') }}" method="POST" id="formSfMasuk">
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

                                        {{-- TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>TAP</label>
                                                <select name="idtap" id="kategoritap" class="form-control select2"
                                                    required>
                                                    <option></option>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->idtap }}">
                                                            {{ $row->idtap }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- SF --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Sales Force</label>
                                                <select name="idsf" id="idsf" class="form-control select2" disabled
                                                    required>
                                                    <option></option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Denom --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Denom</label>
                                                <select name="iddenom" id="iddenom" class="form-control select2" required>

                                                    <option></option>
                                                    @foreach ($denom as $row)
                                                        <option value="{{ $row->iddenom }}">
                                                            {{ $row->denom }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Quantity --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Quantity</label>
                                                <input type="number" name="qty" class="form-control"
                                                    id="qty"min="1" required>
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
                                        {{-- Stok --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Stok Tersedia</label>
                                                <input type="text" id="stok_info" class="form-control mb-1" readonly>
                                                <div class="text-danger small d-none" id="stok_warning" style="font-weight: 600;">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> Quantity melebihi stok tersedia
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                {{-- Footer --}}
                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('sf-masuk') }}" class="btn btn-light">
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

            let currentStock = 0;
            let tapStockData = {}; // Cache data stok TAP

            /* ================= SELECT2 ================= */
            $('.select2').each(function() {
                $(this).select2({
                    placeholder: 'Pilih / Cari…',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(this).closest('.card-body')
                });
            });

            $(document).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-search__field')?.focus();
                }, 50);
            });

            /* ================= TAP → SF ================= */
            $('#kategoritap').on('change', function() {
                const idtap = $(this).val();
                const $sf = $('#idsf');

                $sf.prop('disabled', true).empty().trigger('change');

                if (!idtap) return;

                $.post('{{ route('ajax.get-sf-masuk') }}', {
                        idtap,
                        _token: '{{ csrf_token() }}'
                    })
                    .done(res => {
                        let options = '<option value="">-- Pilih SF --</option>';
                        res.forEach(item => {
                            options += `<option value="${item.idsf}">${item.namasf}</option>`;
                        });
                        $sf.html(options)
                            .prop('disabled', false)
                            .trigger('change');
                    });

                // Bulk load TAP stock
                $.post('{{ route('ajax.get-all-stock-tap-sfmasuk') }}', {
                    idtap: idtap,
                    _token: '{{ csrf_token() }}'
                }).done(res => {
                    tapStockData = res;
                    // Trigger change on iddenom if already selected
                    $('#iddenom').trigger('change');
                });
            });

            /* ================= RESET SAAT SF GANTI ================= */
            $('#idsf').on('change', function() {
                $('#iddenom').val(null).trigger('change');
                $('#qty').val('');
                $('#tambahanket').val('');
                $('#stok_info').val('');
                $('#stok_warning').addClass('d-none');
                $('#submitBtn').prop('disabled', true);
            });

            /* ================= DENOM → LOAD STOK ================= */
            $('#iddenom').on('change', function() {
                const iddenom = $(this).val();

                if (!iddenom) {
                    currentStock = 0;
                    $('#stok_info').val('');
                    return;
                }

                // Ambil dari cache lokal (cepat)
                currentStock = parseInt(tapStockData[iddenom]) || 0;

                if (currentStock <= 0) {
                    $('#stok_info').val('Stok TAP habis');
                } else {
                    $('#stok_info').val(currentStock + ' pcs (Stok TAP)');
                }
                
                // Re-validate qty with new stock
                validateQty();
            });

            function validateQty() {
                const qty = parseInt($('#qty').val()) || 0;

                if (qty > currentStock) {
                    $('#qty').addClass('is-invalid');
                    $('#stok_warning').removeClass('d-none');
                    $('#submitBtn').prop('disabled', true);
                } else {
                    $('#qty').removeClass('is-invalid');
                    $('#stok_warning').addClass('d-none');
                    $('#submitBtn').prop('disabled', false);
                }
            }

            /* ================= VALIDASI QTY ================= */
            $('#qty').on('input', validateQty);

            /* ================= ANTI DOUBLE SUBMIT ================= */
            let submitting = false;

            $('#formSfMasuk').on('submit', function(e) {

                if (submitting) {
                    e.preventDefault();
                    return;
                }

                submitting = true;

                $('#submitBtn')
                    .prop('disabled', true)
                    .text('Menyimpan...');

            });


            // Tanggal maksimal hari ini dan minimal sebulan yang lalu
            document.addEventListener("DOMContentLoaded", function() {
                const inputDate = document.getElementById('date');

                const today = new Date();

                // H+1 (besok)
                const tomorrow = new Date(today);
                tomorrow.setDate(today.getDate() + 1);

                // H-1 bulan
                const monthAgo = new Date(today);
                monthAgo.setMonth(today.getMonth() - 1);

                // Max: besok (H+1)
                inputDate.max = tomorrow.toISOString().split('T')[0];

                // Min: 1 bulan lalu
                inputDate.min = monthAgo.toISOString().split('T')[0];
            });
        });
    </script>
@endpush
