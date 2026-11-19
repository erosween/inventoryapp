{{--
    Edit transaksi DO yang sudah tersimpan. Koreksi dikirim ke InputDOController.
--}}@extends('layout.layout')

@section('content')
    <style>
        .do-serial-fields { display:grid; grid-template-columns:minmax(0,1fr) 24px minmax(0,1fr); align-items:start; gap:8px; }
        .do-serial-separator { display:flex; align-items:center; justify-content:center; height:38px; color:#8b95a5; font-size:13px; font-weight:800; }
        .do-serial-hint { display:block; min-height:16px; margin-top:6px; color:#718096; font-size:11px; }
        .do-serial-hint.is-invalid { color:#d11a3f; }
        .do-serial-hint.is-valid { color:#059669; }
        .do-serial-fields .is-invalid { border-color:#e11d48 !important; }
        @media (max-width:575.98px) { .do-serial-fields { grid-template-columns:minmax(0,1fr) 16px minmax(0,1fr); gap:5px; } }
    </style>
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

                <div class="page-header">
                    <h4 class="page-title">Edit Input DO</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm border-warning">
                            <div class="card-header bg-warning-gradient text-white">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-file-invoice fa-lg"></i>
                                    <div>
                                        <strong class="d-block">Form Perbaikan Data DO</strong>
                                        <small>Pastikan stok SF belum terpakai untuk Qty baru</small>
                                    </div>
                                </div>
                            </div>

                            <form action="{{ url('DO/update/' . $edit->idmasuk) }}" method="POST" id="mainForm">
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

                                        {{-- No DO --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Nomor DO</label>
                                                <input type="text" name="nomordo" class="form-control"
                                                    value="{{ $edit->nomor_do }}" required>
                                            </div>
                                        </div>

                                        {{-- Week --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Week</label>
                                                <input type="text" name="week" class="form-control"
                                                    value="{{ $edit->week }}" required>
                                            </div>
                                        </div>

                                        {{-- TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Pilih TAP</label>
                                                <select name="idtap" id="idtap" class="form-control select2" required>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->idtap }}" {{ $edit->idtappenerima == $row->idtap ? 'selected' : '' }}>
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
                                                <select name="kategorisegel" class="form-control select2" required>
                                                    @foreach ($denom as $row)
                                                        <option value="{{ $row->iddenom }}" data-product="{{ $row->product }}" {{ $edit->iddenom == $row->iddenom ? 'selected' : '' }}>
                                                            {{ $row->denom }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- SF (Penerima) --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Penerima (SF)</label>
                                                <select name="penerima" id="penerima" class="form-control select2" required>
                                                    {{-- Diisi via AJAX --}}
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Qty --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Quantity</label>
                                                <input type="number" name="qty" id="do-qty" class="form-control"
                                                    value="{{ $edit->qty }}" min="1" readonly required>
                                            </div>
                                        </div>

                                        {{-- SN --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                @php($serialParts = explode('-', (string) $edit->sn, 2))
                                                <label>Nomor Seri</label>
                                                <div class="do-serial-fields">
                                                    <input type="text" name="sn_start" id="do-sn-start" class="form-control" inputmode="numeric" autocomplete="off" placeholder="SN awal" aria-label="SN awal" value="{{ $serialParts[0] ?? '' }}" required>
                                                    <span class="do-serial-separator" aria-hidden="true">–</span>
                                                    <input type="text" name="sn_end" id="do-sn-end" class="form-control" inputmode="numeric" autocomplete="off" placeholder="SN akhir (opsional)" aria-label="SN akhir (opsional)" value="{{ $serialParts[1] ?? '' }}">
                                                </div>
                                                <small id="do-serial-hint" class="do-serial-hint" role="status" aria-live="polite"></small>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('DO') }}" class="btn btn-light">Kembali</a>
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
            function serialRangeQty(start, end) {
                if (!/^\d+$/.test(start) || !/^\d+$/.test(end) || start.length !== end.length || end < start) return 0;
                let borrow = 0;
                let difference = '';
                for (let i = start.length - 1; i >= 0; i--) {
                    let digit = Number(end[i]) - Number(start[i]) - borrow;
                    borrow = digit < 0 ? 1 : 0;
                    if (digit < 0) digit += 10;
                    difference = String(digit) + difference;
                }
                const count = parseInt(difference, 10) + 1;
                return count > 0 && count <= 2000000 ? count : 0;
            }
            function updateDoQuantity() {
                const start = ($('#do-sn-start').val() || '').trim();
                const endInput = ($('#do-sn-end').val() || '').trim();
                const end = endInput || start;
                const $option = $('select[name="kategorisegel"] option:selected');
                const product = String($option.data('product') || '').toUpperCase();
                const denomId = String($('select[name="kategorisegel"]').val() || '').toUpperCase();
                const isStarterPack = ['PV', 'SA'].includes(product) ? product === 'SA' : denomId.startsWith('SP');
                const pattern = isStarterPack ? /^0\d{15}$/ : /^\d{12}$/;
                const snLength = isStarterPack ? 16 : 12;
                $('#do-sn-start, #do-sn-end').attr('maxlength', snLength);
                const expected = isStarterPack ? 'SN perdana tepat 16 digit dan diawali 0.' : 'SN voucher tepat 12 digit.';
                let qty = '';
                let error = '';
                if (! $('select[name="kategorisegel"]').val()) error = 'Pilih produk terlebih dahulu.';
                else if (!pattern.test(start) || (endInput && !pattern.test(endInput))) error = expected;
                else {
                    const count = serialRangeQty(start, end);
                    if (count) qty = String(count);
                    else error = 'SN akhir harus berurutan setelah SN awal; maksimal 2.000.000 SN.';
                }
                $('#do-qty').val(qty);
                $('#do-sn-start, #do-sn-end').toggleClass('is-invalid', Boolean(error));
                $('#do-serial-hint').toggleClass('is-invalid', Boolean(error)).toggleClass('is-valid', !error && Boolean(qty))
                    .text(error || (qty ? (endInput ? `${Number(qty).toLocaleString('id-ID')} SN dihitung dari rentang.` : '1 pcs dihitung dari SN awal.') : 'SN awal wajib. SN akhir opsional untuk input satuan.'));
                return Boolean(qty) && !error;
            }
            $('#do-sn-start, #do-sn-end').on('input', function() {
                const maxDigits = Number(this.maxLength) || 12;
                this.value = String(this.value || '').replace(/\D/g, '').slice(0, maxDigits);
                updateDoQuantity();
            }).on('change', updateDoQuantity);
            $('select[name="kategorisegel"]').on('change', updateDoQuantity);
            updateDoQuantity();
            $('.select2').each(function() {
                $(this).select2({
                    placeholder: 'Pilih / Cari…',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(this).closest('.card-body')
                });
            });

            // Load SF berdasarkan TAP
            $('#idtap').on('change', function() {
                loadSF($(this).val(), "{{ $edit->penerima }}");
            });

            // Init SF
            loadSF($('#idtap').val(), "{{ $edit->penerima }}");

            function loadSF(idtap, selectedSf) {
                if (!idtap) return;
                $.post("{{ route('ajax.get-sf-masuk') }}", {
                    idtap: idtap,
                    _token: "{{ csrf_token() }}"
                }, function(res) {
                    $('#penerima').html(res).trigger('change');
                    if (selectedSf) {
                        $('#penerima').val(selectedSf).trigger('change');
                    }
                });
            }

            let submitting = false;
            $('#mainForm').on('submit', function() {
                if (submitting) return false;
                if (!updateDoQuantity()) {
                    Swal.fire({ icon:'error', title:'Periksa Nomor Seri', text:'Pastikan format SN sesuai produk dan rentang SN berurutan.' });
                    return false;
                }
                submitting = true;
                $('#submitBtn').prop('disabled', true).text('Memproses...');
            });
        });
    </script>
@endpush
