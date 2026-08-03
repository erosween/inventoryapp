@extends('layout.layout')

@section('content')
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

                {{-- Page Header --}}
                <div class="page-header">
                    <h4 class="page-title">Edit Stok Keluar SF</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Edit</strong>
                                <div class="text-muted small">
                                    Perubahan data stok keluar Sales Force #{{ $edit->idkeluar }}
                                </div>
                            </div>

                            <form action="{{ url('sf-keluar/update/' . $edit->idkeluar) }}" method="POST" id="mainForm">
                                @csrf

                                <div class="card-body">
                                    <div class="row">

                                        {{-- Tanggal --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Tanggal</label>
                                                <input type="date" id="date" name="tgl" class="form-control"
                                                    value="{{ old('tgl', date('Y-m-d', strtotime($edit->tgl))) }}"
                                                    min="{{ now()->startOfMonth()->toDateString() }}"
                                                    max="{{ date('Y-m-d') }}" required>
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
                                                        <option value="{{ $row->idtap }}"
                                                            {{ $edit->idtap == $row->idtap ? 'selected' : '' }}>
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
                                                    <option value="{{ $edit->idsf }}" selected>
                                                        {{ $selectedSf->namasf ?? $edit->idsf }}
                                                    </option>
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
                                                        <option value="{{ $row->iddenom }}"
                                                            {{ $edit->iddenom == $row->iddenom ? 'selected' : '' }}>
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
                                                <input type="number" name="qty" id="qty" class="form-control"
                                                    value="{{ old('qty', $edit->qty) }}" min="1" required>
                                                @error('qty')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>SN Awal - SN Akhir</label>
                                                <input type="text" name="tambahanket" id="tambahanket"
                                                    value="{{ $edit->tambahanket }}" class="form-control"
                                                    placeholder="SN Awal - SN Akhir">
                                            </div>
                                        </div>

                                        {{-- Stok --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Stok Tersedia (Termasuk Qty Ini)</label>
                                                <input type="text" id="stok_info" class="form-control mb-1" readonly>
                                                <div class="text-danger small d-none" id="stok_warning"
                                                    style="font-weight: 600;">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> Quantity melebihi stok
                                                    tersedia
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                {{-- FOOTER --}}
                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('sf-keluar') }}" class="btn btn-light">Batal</a>
                                    <button type="submit" id="submitBtn" class="btn btn-primary">
                                        Simpan Perubahan
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
            let sfStockData = {};
            const originalIdSf = "{{ $edit->idsf }}";
            const originalIdDenom = "{{ $edit->iddenom }}";
            const originalQty = parseInt("{{ $edit->qty }}") || 0;

            /* ================= SELECT2 ================= */
            $('.select2').each(function() {
                $(this).select2({
                    placeholder: 'Pilih / Cari…',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(this).closest('.card-body')
                });
            });

            /* ================= INITIAL LOAD SF LIST ================= */
            const initialTap = $('#kategoritap').val();
            if (initialTap) {
                $.post('{{ route('ajax.get-sf-keluar') }}', {
                    idtap: initialTap,
                    _token: '{{ csrf_token() }}'
                }).done(res => {
                    let options = '<option value=""></option>';
                    res.forEach(item => {
                        options += `<option value="${item.idsf}">${item.namasf}</option>`;
                    });
                    $('#idsf').html(options).val(originalIdSf).trigger('change');
                });
            }

            /* ================= TAP → SF ================= */
            $('#kategoritap').on('change', function() {
                const idtap = $(this).val();
                const $sf = $('#idsf');

                $sf.prop('disabled', true).empty().trigger('change');

                if (!idtap) return;

                $.post('{{ route('ajax.get-sf-keluar') }}', {
                        idtap: idtap,
                        _token: '{{ csrf_token() }}'
                    })
                    .done(res => {
                        let options = '<option value=""></option>';
                        res.forEach(item => {
                            options += `<option value="${item.idsf}">${item.namasf}</option>`;
                        });
                        $sf.html(options)
                            .prop('disabled', false)
                            .trigger('change');
                    });
            });

            /* ================= RESET SAAT SF GANTI ================= */
            $('#idsf').on('change', function() {
                const idsf = $(this).val();

                sfStockData = {}; // Clear cache

                if (!idsf) return;

                $.post('{{ route('ajax.get-all-stock') }}', {
                        idsf: idsf,
                        _token: '{{ csrf_token() }}'
                    })
                    .done(res => {
                        sfStockData = res;
                        $('#iddenom').trigger('change');
                    });
            });

            /* ================= DENOM → LOAD STOK ================= */
            $('#iddenom').on('change', function() {
                const iddenom = $(this).val();
                const idsf = $('#idsf').val();

                if (!iddenom) {
                    currentStock = 0;
                    return;
                }

                let dbStock = parseInt(sfStockData[iddenom]) || 0;
                
                if (iddenom === originalIdDenom && idsf === originalIdSf) {
                    currentStock = dbStock + originalQty;
                } else {
                    currentStock = dbStock;
                }

                if (currentStock <= 0) {
                    $('#stok_info').val('Stok habis');
                } else {
                    $('#stok_info').val(currentStock + ' pcs');
                }
                
                validateQty();
            });

            /* ================= VALIDASI QTY ================= */
            $('#qty').on('input', validateQty);

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
    </script>
@endpush
