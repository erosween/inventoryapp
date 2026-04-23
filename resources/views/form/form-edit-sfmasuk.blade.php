@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                {{-- Header --}}
                <div class="page-header">
                    <h4 class="page-title">Edit Stok Masuk SF</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm border-warning">
                            <div class="card-header bg-warning-gradient text-white">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-edit fa-lg"></i>
                                    <div>
                                        <strong class="d-block">Form Perbaikan Data</strong>
                                        <small>Pastikan data yang diubah sudah benar untuk menjaga akurasi stok</small>
                                    </div>
                                </div>
                            </div>

                            <form action="{{ url('sf-masuk/update/' . $data->idmasuk) }}" method="POST" id="formEditSfMasuk">
                                @csrf

                                <div class="card-body">
                                    <div class="row">

                                        {{-- Tanggal --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Tanggal</label>
                                                <input type="date" name="tgl" id="date" class="form-control"
                                                    value="{{ $data->tgl }}" required>
                                            </div>
                                        </div>

                                        {{-- TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>TAP</label>
                                                <select name="idtap" id="kategoritap" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach ($kodetap as $row)
                                                        <option value="{{ $row->idtap }}" {{ $data->idtap == $row->idtap ? 'selected' : '' }}>
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
                                                <select name="idsf" id="idsf" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach ($idsf as $row)
                                                        <option value="{{ $row->idsf }}" {{ $data->idsf == $row->idsf ? 'selected' : '' }}>
                                                            {{ $row->namasf }}
                                                        </option>
                                                    @endforeach
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
                                                        <option value="{{ $row->iddenom }}" {{ $data->iddenom == $row->iddenom ? 'selected' : '' }}>
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
                                                    id="qty" min="1" value="{{ $data->qty }}" required>
                                                <input type="hidden" id="old_qty" value="{{ $data->qty }}">
                                            </div>
                                        </div>

                                        {{-- SN --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>SN</label>
                                                <input type="text" name="sn" class="form-control"
                                                    value="{{ $data->sn }}" placeholder="SN Awal - SN Akhir" required>
                                            </div>
                                        </div>

                                        {{-- Stok Info --}}
                                        <div class="col-md-12 mt-2">
                                            <div class="alert alert-info py-2 px-3 mb-0" id="stok_info_alert">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fas fa-info-circle"></i>
                                                    <span id="stok_info_text">Memuat informasi stok...</span>
                                                </div>
                                            </div>
                                            <div class="text-danger small d-none mt-1" id="stok_warning" style="font-weight: 600;">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Quantity baru melebihi batas (Stok TAP + Qty Lama)
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                {{-- Footer --}}
                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('sf-masuk') }}" class="btn btn-light">
                                        Kembali
                                    </a>
                                    <button type="submit" id="submitBtn" class="btn btn-warning text-white font-weight-bold">
                                        Update Data
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
            let tapStockReal = 0;
            let oldQty = parseInt($('#old_qty').val()) || 0;
            let tapStockData = {};

            /* ================= SELECT2 ================= */
            $('.select2').each(function() {
                $(this).select2({
                    placeholder: 'Pilih / Cari…',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(this).closest('.card-body')
                });
            });

            /* ================= INITIAL LOAD ================= */
            function loadTapStock() {
                const idtap = $('#kategoritap').val();
                if (!idtap) return;

                $.post('{{ route('ajax.get-all-stock-tap-sfmasuk') }}', {
                    idtap: idtap,
                    _token: '{{ csrf_token() }}'
                }).done(res => {
                    tapStockData = res;
                    updateStockDisplay();
                });
            }

            loadTapStock();

            /* ================= CHANGE HANDLERS ================= */
            $('#kategoritap').on('change', function() {
                const idtap = $(this).val();
                if (!idtap) return;

                // Reload SF list
                $.post('{{ route('ajax.get-sf-masuk') }}', { idtap, _token: '{{ csrf_token() }}' })
                .done(res => {
                    let options = '<option value=""></option>';
                    res.forEach(item => {
                        options += `<option value="${item.idsf}">${item.namasf}</option>`;
                    });
                    $('#idsf').html(options).trigger('change');
                });

                loadTapStock();
            });

            $('#iddenom').on('change', updateStockDisplay);

            function updateStockDisplay() {
                const iddenom = $('#iddenom').val();
                const $infoBox = $('#stok_info_alert');
                const $infoText = $('#stok_info_text');
                
                if (!iddenom) {
                    $infoText.text('Pilih Denom untuk melihat stok');
                    return;
                }

                tapStockReal = parseInt(tapStockData[iddenom]) || 0;
                
                // Jika TAP/Denom masih sama dengan data lama, maka batasnya adalah (Stok sekarang + Qty lama)
                // Jika sudah diganti, maka batasnya hanya (Stok sekarang)
                const isSameTarget = ($('#kategoritap').val() == "{{ $data->idtap }}" && iddenom == "{{ $data->iddenom }}");
                const maxAvailable = isSameTarget ? (tapStockReal + oldQty) : tapStockReal;

                $infoText.html(`Stok TAP: <b>${tapStockReal}</b> | Batas Maksimal (setelah rollback): <b>${maxAvailable}</b>`);
                validateQty();
            }

            function validateQty() {
                const qty = parseInt($('#qty').val()) || 0;
                const iddenom = $('#iddenom').val();
                const isSameTarget = ($('#kategoritap').val() == "{{ $data->idtap }}" && iddenom == "{{ $data->iddenom }}");
                const maxAvailable = isSameTarget ? (tapStockReal + oldQty) : tapStockReal;

                if (qty > maxAvailable) {
                    $('#qty').addClass('is-invalid');
                    $('#stok_warning').removeClass('d-none');
                    $('#submitBtn').prop('disabled', true);
                } else {
                    $('#qty').removeClass('is-invalid');
                    $('#stok_warning').addClass('d-none');
                    $('#submitBtn').prop('disabled', false);
                }
            }

            $('#qty').on('input', validateQty);

            /* ================= ANTI DOUBLE SUBMIT ================= */
            let submitting = false;
            $('#formEditSfMasuk').on('submit', function(e) {
                if (submitting) {
                    e.preventDefault();
                    return;
                }
                submitting = true;
                $('#submitBtn').prop('disabled', true).text('Memproses...');
            });
        });
    </script>
@endpush
