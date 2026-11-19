{{--
    Input barang dari DO. Pilihan penerima dan penyimpanan mengikuti InputDOController.
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
                    <h4 class="page-title">Input DO Masuk BO</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">
                        <div class="card shadow-sm">

                            <div class="card-header">
                                <strong>Form Input</strong>
                                <div class="text-muted small">
                                    Input DO masuk ke BO
                                </div>
                            </div>

                            <form action="{{ url('DO') }}" method="POST" id="formDO">
                                @csrf

                                <div class="card-body">
                                    <div class="row">

                                        {{-- Tanggal --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Tanggal</label>
                                                <input type="date" name="tgl" id="date" class="form-control"
                                                    value="{{ date('Y-m-d') }}" required>
                                            </div>
                                        </div>

                                        {{-- Kategori Segel --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Kategori Produk</label>
                                                <select name="kategorisegel" id="do-denom" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach($denom as $item)
                                                    <option value="{{ $item->iddenom }}" data-product="{{ $item->product }}">{{ $item->denom }} · {{ $item->iddenom }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- BO / Penerima --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>BO / Penerima</label>
                                                <select name="penerima" id="bo" class="form-control select2"
                                                    required>
                                                    <option></option>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->namabo }}">
                                                            {{ $row->namabo }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>TAP Penerima</label>
                                                <select name="idtap" id="tappenerima" class="form-control select2"
                                                    disabled required>
                                                    <option></option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- No DO --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>No DO</label>
                                                <input type="text" name="nomordo" class="form-control" required>
                                            </div>
                                        </div>

                                        {{-- Week --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Minggu</label>
                                                <select name="week" class="form-control" required>
                                                    <option value="W1">W1</option>
                                                    <option value="W2">W2</option>
                                                    <option value="W3">W3</option>
                                                    <option value="W4">W4</option>
                                                    <option value="W5">W5</option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Qty --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Quantity</label>
                                                <input type="number" name="qty" id="do-qty" class="form-control" min="1" readonly
                                                    required>
                                            </div>
                                        </div>

                                        {{-- SN --}}
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Nomor Seri</label>
                                                <div class="do-serial-fields">
                                                    <input type="text" name="sn_start" id="do-sn-start" class="form-control" inputmode="numeric" autocomplete="off" placeholder="SN awal" aria-label="SN awal" required>
                                                    <span class="do-serial-separator" aria-hidden="true">–</span>
                                                    <input type="text" name="sn_end" id="do-sn-end" class="form-control" inputmode="numeric" autocomplete="off" placeholder="SN akhir (opsional)" aria-label="SN akhir (opsional)">
                                                </div>
                                                <small id="do-serial-hint" class="do-serial-hint" role="status" aria-live="polite">Pilih produk, lalu isi SN awal. SN akhir opsional untuk input satuan.</small>
                                            </div>
                                        </div>

                                        <input type="hidden" name="pengirim" value="DO">

                                    </div>
                                </div>

                                <div class="card-footer text-end">
                                    <a href="{{ url('DO') }}" class="btn btn-light">Back</a>
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
                const $option = $('#do-denom option:selected');
                const product = String($option.data('product') || '').toUpperCase();
                const denomId = String($('#do-denom').val() || '').toUpperCase();
                const isStarterPack = ['PV', 'SA'].includes(product) ? product === 'SA' : denomId.startsWith('SP');
                const pattern = isStarterPack ? /^0\d{15}$/ : /^\d{12}$/;
                const expected = isStarterPack ? 'SN perdana tepat 16 digit dan diawali 0.' : 'SN voucher tepat 12 digit.';
                let qty = '';
                let error = '';
                if (!$('#do-denom').val()) error = 'Pilih produk terlebih dahulu.';
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
            $('#do-sn-start, #do-sn-end').on('input change', updateDoQuantity);
            $('#do-denom').on('change', updateDoQuantity);
            $('#formDO').on('submit', function(event) {
                if (!updateDoQuantity()) {
                    event.preventDefault();
                    Swal.fire({ icon:'error', title:'Periksa Nomor Seri', text:'Pastikan format SN sesuai produk dan rentang SN berurutan.' });
                }
            });

            $('.select2').each(function() {
                $(this).select2({
                    placeholder: 'Pilih / Cari...',
                    width: '100%',
                    dropdownParent: $(this).closest('.card-body')
                });
            });

            $(document).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-search__field')?.focus();
                }, 50);
            });

            /* BO → TAP */
            $('#bo').on('change', function() {

                let bo = $(this).val();
                let $tap = $('#tappenerima');

                $tap.prop('disabled', true).empty().trigger('change');

                if (!bo) return;

                $.post('{{ route('do.get-tap') }}', {
                    bo: bo,
                    _token: '{{ csrf_token() }}'
                }).done(function(res) {
                    $tap.html(res)
                        .prop('disabled', false)
                        .trigger('change');
                });

            });

        });

        /* limit tanggal */
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
