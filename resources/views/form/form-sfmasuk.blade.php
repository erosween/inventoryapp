@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                {{-- Header --}}
                <div class="page-header">
                    <h4 class="page-title">Input Stok Masuk SF</h4>
                </div>

                <div class="row justify-content-center">
                    <div class="col-xl-8 col-lg-9 col-md-11">

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
                                                    required>
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
                                                <input type="text" id="stok_info" class="form-control" readonly>
                                            </div>
                                        </div>
                                        <div class="alert alert-danger d-none" id="stok_warning">
                                            Quantity melebihi stok tersedia
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

            /* ================= SELECT2 ================= */
            $('.select2').select2({
                placeholder: 'Pilih / Cari…',
                allowClear: true,
                width: '100%'
            });

            $(document).on('select2:open', function() {
                document.querySelector('.select2-search__field').focus();
            });

            /* ================= TAP → SF ================= */
            $('#kategoritap').on('change', function() {
                const idtap = $(this).val();
                const $sf = $('#idsf');

                $sf.prop('disabled', true).empty().trigger('change');

                if (!idtap) return;

                $.post('{{ route('ajax.get-sf') }}', {
                        idtap
                    })
                    .done(res => {
                        $sf.html(res)
                            .prop('disabled', false)
                            .trigger('change');
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
                const idtap = $('#kategoritap').val();

                if (!iddenom || !idtap) return;

                $('#stok_info').val('Loading...');

                $.post('{{ route('ajax.get-stock-tap') }}', {
                    iddenom,
                    idtap
                }).done(res => {
                    currentStock = parseInt(res.stock) || 0;

                    if (currentStock <= 0) {
                        $('#stok_info').val('Stok TAP habis');
                        $('#stok_warning').removeClass('d-none');
                    } else {
                        $('#stok_info').val(currentStock + ' pcs (stok TAP)');
                        $('#stok_warning').addClass('d-none');
                    }
                });
            });

            /* ================= VALIDASI QTY ================= */
            $('#qty').on('input', function() {
                const qty = parseInt($(this).val()) || 0;

                if (qty > currentStock) {
                    $(this).addClass('is-invalid');
                    $('#stok_warning').removeClass('d-none');
                    $('#submitBtn').prop('disabled', true);
                } else {
                    $(this).removeClass('is-invalid');
                    $('#stok_warning').addClass('d-none');
                    $('#submitBtn').prop('disabled', false);
                }
            });

            /* ================= ANTI DOUBLE SUBMIT ================= */
            let submitting = false;
            $('#mainForm').on('submit', function() {
                if (submitting) return false;
                submitting = true;

                $('#submitBtn')
                    .prop('disabled', true)
                    .text('Menyimpan...');
            });

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
    </script>
@endpush
