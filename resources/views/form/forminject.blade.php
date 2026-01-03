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
                    <h4 class="page-title">Input Inject Voucher Fisik</h4>
                    <div class="text-muted">
                        Inject VF akan mengurangi stok segel TAP
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-xl-8 col-lg-9 col-md-11">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Inject VF</strong>
                                <div class="text-muted small">
                                    Pastikan qty sesuai stok segel TAP
                                </div>
                            </div>

                            <form action="{{ url('form/forminject') }}" method="POST" id="formInject">
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
                                                <select name="idtap" class="form-control select2" required>
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
                                                <label>Denom Inject</label>
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
                                                <input type="number" name="qty" id="qty" class="form-control"
                                                    min="1" required>
                                            </div>
                                        </div>

                                        {{-- Stok TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Stok Segel TAP</label>
                                                <input type="text" id="stok_segel_info" class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="alert alert-danger d-none" id="stok_warning">
                                                Quantity melebihi stok TAP
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

                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('injectvf') }}" class="btn btn-light">
                                        Kembali
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
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

            let currentSegelStock = 0;

            $('.select2').select2({
                placeholder: 'Pilih / Cari…',
                allowClear: true,
                width: '100%'
            });

            $(document).on('select2:open', () => {
                document.querySelector('.select2-search__field')?.focus();
            });

            function loadSegelStock() {
                const idtap = $('select[name="idtap"]').val();
                if (!idtap) return;

                $('#stok_segel_info').val('Loading...');

                $.post('{{ route('ajax.get-stock-segel-tap') }}', {
                        idtap
                    })
                    .done(res => {
                        currentSegelStock = parseInt(res.stock) || 0;

                        if (currentSegelStock <= 0) {
                            $('#stok_segel_info').val('Stok segel habis');
                            $('#stok_warning').removeClass('d-none');
                            $('#submitBtn').prop('disabled', true);
                        } else {
                            $('#stok_segel_info').val(currentSegelStock + ' pcs');
                            $('#stok_warning').addClass('d-none');
                            $('#submitBtn').prop('disabled', false);
                        }
                    });
            }

            $('select[name="idtap"]').on('change', loadSegelStock);

            $('#qty').on('input', function() {
                const qty = parseInt($(this).val()) || 0;

                if (qty > currentSegelStock) {
                    $(this).addClass('is-invalid');
                    $('#stok_warning').removeClass('d-none');
                    $('#submitBtn').prop('disabled', true);
                } else {
                    $(this).removeClass('is-invalid');
                    $('#stok_warning').addClass('d-none');
                    $('#submitBtn').prop('disabled', false);
                }
            });

        });
    </script>
    <script>
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
