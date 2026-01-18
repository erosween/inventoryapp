@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                {{-- Error --}}
                @if ($errors->has('error'))
                    <div class="alert alert-danger">
                        {{ $errors->first('error') }}
                    </div>
                @endif

                {{-- Header --}}
                <div class="page-header">
                    <h4 class="page-title">Input Retur SF (Masuk TAP)</h4>
                </div>

                <div class="row justify-content-center">
                    <div class="col-xl-8 col-lg-9 col-md-11">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Input</strong>
                                <div class="text-muted small">
                                    Input barang retur dari Sales Force ke TAP
                                </div>
                            </div>

                            <form action="{{ url('retursf') }}" method="POST" id="mainForm">
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
                                                    @foreach ($tap as $row)
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
                                                <select name="iddenom" class="form-control select2" required>
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
                                                <input type="number" name="qty" class="form-control" min="1"
                                                    required>
                                            </div>
                                        </div>

                                        {{-- Status VF --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Status Voucher</label>
                                                <select name="ketvf" class="form-control select2" required>
                                                    <option></option>
                                                    <option value="OK">OK</option>
                                                    <option value="RUSAK">RUSAK</option>
                                                    <option value="MATI">MATI</option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- SN --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>SN Awal - SN Akhir</label>
                                                <input type="text" name="sn" class="form-control"
                                                    placeholder="SN Awal - SN Akhir" required>
                                            </div>
                                        </div>

                                        {{-- Ket Lain --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Keterangan Tambahan</label>
                                                <input type="text" name="tambahket" class="form-control"
                                                    placeholder="Keterangan tambahan" required>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                {{-- Footer --}}
                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('retursf') }}" class="btn btn-light">
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
                }).done(res => {
                    $sf.html(res)
                        .prop('disabled', false)
                        .trigger('change');
                });
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

        /* ================= DATE LIMIT ================= */
        document.addEventListener("DOMContentLoaded", function() {
            const inputDate = document.getElementById('date');
            const today = new Date();
            const monthAgo = new Date(today);
            monthAgo.setMonth(today.getMonth() - 1);

            inputDate.max = today.toISOString().split('T')[0];
            inputDate.min = monthAgo.toISOString().split('T')[0];
        });
    </script>
@endpush
