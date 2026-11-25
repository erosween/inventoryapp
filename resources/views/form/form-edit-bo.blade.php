{{--
    Edit retur BO yang sudah tersimpan. Koreksi dikirim ke BOController.
--}}@extends('layout.layout')

@section('content')
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

                <div class="page-header">
                    <h4 class="page-title">Edit BO / Retur</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm border-warning">
                            <div class="card-header bg-warning-gradient text-white">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-edit fa-lg"></i>
                                    <div>
                                        <strong class="d-block">Form Perbaikan Data BO</strong>
                                        <small>ID Keluar: #{{ $edit->idkeluar }}</small>
                                    </div>
                                </div>
                            </div>

                            <form action="{{ url('bo/update/' . $edit->idkeluar) }}" method="POST" id="mainForm">
                                @csrf

                                <div class="card-body">
                                    <div class="row">

                                        {{-- Tanggal --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Tanggal</label>
                                                <input type="date" name="tgl" class="form-control"
                                                    value="{{ date('Y-m-d', strtotime($edit->tgl)) }}" required>
                                            </div>
                                        </div>

                                        {{-- BO (Pengirim) --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Pilih BO (Pengirim)</label>
                                                <select name="pengirim" class="form-control select2" required>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->namabo }}" {{ $edit->pengirim == $row->namabo ? 'selected' : '' }}>
                                                            {{ $row->namabo }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- TAP (Penerima) --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>TAP (Penerima)</label>
                                                <select name="penerima" class="form-control select2" required>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->idtap }}" {{ $edit->penerima == $row->idtap ? 'selected' : '' }}>
                                                            {{ $row->idtap }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Denom --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Denom</label>
                                                <select name="iddenom" class="form-control select2" required>
                                                    @foreach ($packages as $idDenom => $label)
                                                        <option value="{{ $idDenom }}" @selected(old('iddenom', $edit->iddenom) === $idDenom)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Qty --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label id="bo-qty-label">{{ $serialMode === 'broken' ? 'Qty SN Pecah' : 'Qty otomatis' }}</label>
                                                <input type="number" name="qty" id="bo-qty" class="form-control"
                                                    value="{{ $edit->qty }}" min="1" max="2000000" {{ $serialMode !== 'broken' ? 'readonly' : '' }} required>
                                            </div>
                                        </div>

                                        {{-- SN --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Identitas SN</label>
                                                <input type="hidden" name="serial_mode" id="bo-serial-mode" value="{{ $serialMode }}">
                                                <div class="btn-group btn-group-sm d-flex mb-2" role="group" aria-label="Cara input SN">
                                                    <button type="button" class="btn btn-outline-primary bo-serial-choice {{ $serialMode === 'range' ? 'active' : '' }}" data-mode="range" aria-pressed="{{ $serialMode === 'range' ? 'true' : 'false' }}">Massal</button>
                                                    <button type="button" class="btn btn-outline-primary bo-serial-choice {{ $serialMode === 'broken' ? 'active' : '' }}" data-mode="broken" aria-pressed="{{ $serialMode === 'broken' ? 'true' : 'false' }}">SN Pecah</button>
                                                </div>
                                                <div id="bo-serial-fields" class="d-flex align-items-center {{ $serialMode === 'broken' ? 'd-none' : '' }}">
                                                    <input type="text" name="sn_start" id="bo-sn-start" class="form-control" inputmode="numeric" autocomplete="off" value="{{ $snStart }}" placeholder="SN awal" {{ $serialMode === 'range' ? 'required' : '' }}>
                                                    <span class="px-2">–</span>
                                                    <input type="text" name="sn_end" id="bo-sn-end" class="form-control" inputmode="numeric" autocomplete="off" value="{{ $snEnd }}" placeholder="SN akhir (opsional)">
                                                </div>
                                                <small id="bo-serial-hint" class="form-text text-muted" aria-live="polite">@if($serialMode === 'broken')Qty dicatat manual; SN pecah tidak dicatat satu per satu.@else Massal: isi SN awal dan opsional SN akhir. Qty dihitung otomatis.@endif</small>
                                            </div>
                                        </div>

                                        {{-- Keterangan --}}
                                        <div class="col-md-12">
                                            <div class="form-group mb-1">
                                                <label>Keterangan</label>
                                                <input type="text" name="tambahanket" class="form-control"
                                                    value="{{ $edit->tambahanket }}">
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('bo') }}" class="btn btn-light">Kembali</a>
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
            const productByDenom = @json($productByDenom);
            $('select[name="iddenom"] option').each(function() {
                $(this).attr('data-product', productByDenom[this.value] || '');
            });

            function serialRangeQty(start, end) {
                if (!/^\d+$/.test(start) || !/^\d+$/.test(end) || start.length !== end.length || end < start) return 0;
                const count = Number(end) - Number(start) + 1;
                return Number.isSafeInteger(count) && count > 0 && count <= 2000000 ? count : 0;
            }
            function updateSerialQty() {
                const mode = $('#bo-serial-mode').val() || 'range';
                const start = String($('#bo-sn-start').val() || '').trim();
                const endInput = String($('#bo-sn-end').val() || '').trim();
                const end = endInput || start;
                const isSa = String($('select[name="iddenom"] option:selected').data('product') || '').toUpperCase() === 'SA';
                const pattern = isSa ? /^0\d{15}$/ : /^\d{12}$/;
                const length = isSa ? 16 : 12;
                const expected = isSa ? 'SN SA harus 16 digit dan diawali 0.' : 'SN PV harus tepat 12 digit.';
                $('#bo-sn-start, #bo-sn-end').attr('maxlength', length);
                let qty = '', error = '';
                if (mode === 'broken') {
                    qty = String(parseInt($('#bo-qty').val(), 10) || '');
                    if (!$('select[name="iddenom"]').val()) error = 'Pilih produk terlebih dahulu.';
                    else if (!qty || Number(qty) < 1 || Number(qty) > 2000000) error = 'Qty SN pecah harus antara 1 dan 2.000.000.';
                } else {
                    if (!$('select[name="iddenom"]').val()) error = 'Pilih produk terlebih dahulu.';
                    else if (!pattern.test(start) || (endInput && !pattern.test(endInput))) error = expected;
                    else {
                        const count = serialRangeQty(start, end);
                        if (count) qty = String(count);
                        else error = 'SN akhir harus berurutan setelah SN awal; maksimal 2.000.000 SN.';
                    }
                    $('#bo-qty').val(qty);
                }
                $('#bo-sn-start, #bo-sn-end').toggleClass('is-invalid', Boolean(error));
                $('#bo-serial-hint').toggleClass('text-danger', Boolean(error)).toggleClass('text-success', !error && Boolean(qty))
                    .text(error || (mode === 'broken'
                        ? `${Number(qty || 0).toLocaleString('id-ID')} pcs dicatat manual; SN tidak dicatat satu per satu.`
                        : (qty ? (endInput ? `${Number(qty).toLocaleString('id-ID')} SN dihitung dari rentang.` : '1 pcs dihitung dari SN awal.') : 'SN awal wajib diisi.')));
                return Boolean(qty) && !error;
            }
            $('.bo-serial-choice').on('click', function() {
                const mode = String($(this).data('mode'));
                $('#bo-serial-mode').val(mode);
                $('.bo-serial-choice').removeClass('active').attr('aria-pressed', 'false');
                $(this).addClass('active').attr('aria-pressed', 'true');
                const broken = mode === 'broken';
                $('#bo-serial-fields').toggleClass('d-none', broken);
                $('#bo-sn-start').prop('required', !broken);
                $('#bo-qty').prop('readonly', !broken).val('');
                $('#bo-qty-label').text(broken ? 'Qty SN Pecah' : 'Qty otomatis');
                $('#bo-sn-start, #bo-sn-end').val('').removeClass('is-invalid');
                $('#bo-serial-hint').removeClass('text-danger text-success')
                    .text(broken ? 'Masukkan qty; SN pecah tidak dicatat satu per satu.' : 'Massal: isi SN awal dan opsional SN akhir. Qty dihitung otomatis.');
                updateSerialQty();
            });
            $('#bo-sn-start, #bo-sn-end').on('input', function() {
                if ($('#bo-serial-mode').val() === 'broken') return;
                this.value = String(this.value || '').replace(/\D/g, '').slice(0, Number(this.maxLength) || 16);
                updateSerialQty();
            }).on('change', updateSerialQty);
            $('select[name="iddenom"]').on('change', updateSerialQty);
            $('#bo-qty').on('input', function() { if ($('#bo-serial-mode').val() === 'broken') updateSerialQty(); });

            $('.select2').each(function() {
                $(this).select2({
                    placeholder: 'Pilih / Cari…',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(this).closest('.card-body')
                });
            });

            let submitting = false;
            $('#mainForm').on('submit', function() {
                if (!updateSerialQty()) {
                    Swal.fire({ icon: 'error', title: 'Periksa Nomor Seri', text: 'Pastikan format SN sesuai produk dan rentangnya berurutan.' });
                    return false;
                }
                if (submitting) return false;
                submitting = true;
                $('#submitBtn').prop('disabled', true).text('Memproses...');
            });
            updateSerialQty();
        });
    </script>
@endpush
