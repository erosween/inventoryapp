{{--
    Form pengembalian barang ke BO. Penyimpanan transaksi ditangani BOController.
--}}@extends('layout.layout')

@section('content')
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

                {{-- HEADER --}}
                <div class="page-header">
                    <h4 class="page-title">Input Stok Keluar BO</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Keluar BO</strong>
                                    <div class="text-muted small">
                                    Isi SN sesuai produk. Qty dihitung dari rentang SN dan dicek terhadap stok BO.
                                </div>
                            </div>

                            <form action="{{ route('bo.store') }}" method="POST" id="formKeluarBO">
                                @csrf

                                <div class="card-body">
                                    <div class="row">

                                        {{-- TANGGAL --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-2">
                                                <label>Tanggal</label>
                                                <input type="date" name="tgl" id="date" class="form-control"
                                                    value="{{ date('Y-m-d') }}" required>
                                            </div>
                                        </div>

                                        {{-- KATEGORI --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-2">
                                                <label>Kategori Produk</label>
                                                <select name="iddenom" id="bo-denom" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach ($packages as $idDenom => $label)
                                                        <option value="{{ $idDenom }}" data-product="{{ in_array($idDenom, ['SP6', 'SP7', 'SP1', 'SP9'], true) ? 'SA' : 'PV' }}" @selected(old('iddenom') === $idDenom)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- BO --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-2">
                                                <label>BO (Pengirim)</label>
                                                <select name="pengirim" id="kategorisegelmasuk" class="form-control select2"
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
                                            <div class="form-group mb-1">
                                                <label>TAP (Penerima)</label>
                                                <select name="penerima" id="tappenerima" class="form-control select2"
                                                    required>
                                                    <option></option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- QTY --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Quantity</label>
                                                <input type="number" name="qty" id="bo-qty" class="form-control" min="1" readonly required>
                                            </div>
                                        </div>

                                        {{-- SN --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>SN</label>
                                                <div class="d-flex align-items-center">
                                                    <input type="text" name="sn_start" id="bo-sn-start" class="form-control" inputmode="numeric" autocomplete="off" placeholder="SN awal" required>
                                                    <span class="px-2">–</span>
                                                    <input type="text" name="sn_end" id="bo-sn-end" class="form-control" inputmode="numeric" autocomplete="off" placeholder="SN akhir (opsional)">
                                                </div>
                                                <small id="bo-serial-hint" class="form-text text-muted" aria-live="polite">Pilih produk lalu isi SN awal. SN akhir opsional untuk rentang.</small>
                                            </div>
                                        </div>

                                        {{-- KETERANGAN --}}
                                        <div class="col-md-12">
                                            <div class="form-group mb-1">
                                                <label>Keterangan Tambahan (Opsional)</label>
                                                <input type="text" name="tambahanket" class="form-control">
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('bo') }}" class="btn btn-light">
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

            function serialRangeQty(start, end) {
                if (!/^\d+$/.test(start) || !/^\d+$/.test(end) || start.length !== end.length || end < start) return 0;
                const count = Number(end) - Number(start) + 1;
                return Number.isSafeInteger(count) && count > 0 && count <= 2000000 ? count : 0;
            }

            function updateSerialQty() {
                const start = String($('#bo-sn-start').val() || '').trim();
                const endInput = String($('#bo-sn-end').val() || '').trim();
                const end = endInput || start;
                const product = String($('#bo-denom option:selected').data('product') || '').toUpperCase();
                const isSa = product === 'SA';
                const pattern = isSa ? /^0\d{15}$/ : /^\d{12}$/;
                const length = isSa ? 16 : 12;
                const expected = isSa ? 'SN SA harus 16 digit dan diawali 0.' : 'SN PV harus tepat 12 digit.';
                $('#bo-sn-start, #bo-sn-end').attr('maxlength', length);
                let qty = '', error = '';
                if (!$('#bo-denom').val()) error = 'Pilih produk terlebih dahulu.';
                else if (!pattern.test(start) || (endInput && !pattern.test(endInput))) error = expected;
                else {
                    const count = serialRangeQty(start, end);
                    if (count) qty = String(count);
                    else error = 'SN akhir harus berurutan setelah SN awal; maksimal 2.000.000 SN.';
                }
                $('#bo-qty').val(qty);
                $('#bo-sn-start, #bo-sn-end').toggleClass('is-invalid', Boolean(error));
                $('#bo-serial-hint').toggleClass('text-danger', Boolean(error)).toggleClass('text-success', !error && Boolean(qty))
                    .text(error || (qty ? (endInput ? `${Number(qty).toLocaleString('id-ID')} SN dihitung dari rentang.` : '1 pcs dihitung dari SN awal.') : 'SN awal wajib diisi.'));
                return Boolean(qty) && !error;
            }

            $('#bo-sn-start, #bo-sn-end').on('input', function() {
                const maxDigits = Number(this.maxLength) || 16;
                this.value = String(this.value || '').replace(/\D/g, '').slice(0, maxDigits);
                updateSerialQty();
            }).on('change', updateSerialQty);
            $('#bo-denom').on('change', updateSerialQty);
            $('#formKeluarBO').on('submit', function(event) {
                if (!updateSerialQty()) {
                    event.preventDefault();
                    Swal.fire({ icon: 'error', title: 'Periksa Nomor Seri', text: 'Pastikan format SN sesuai produk dan rentangnya berurutan.' });
                }
            });

            /* =====================
               SELECT2
            ===================== */
            $('.select2').each(function() {
                $(this).select2({
                    placeholder: 'Pilih / Cari…',
                    width: '100%',
                    allowClear: true,
                    dropdownParent: $(this).closest('.card-body')
                });
            });

            $(document).on('select2:open', () => {
                setTimeout(function() {
                    document.querySelector('.select2-search__field')?.focus();
                }, 50);
            });

            /* =====================
               DATE LIMIT (H-30)
            ===================== */
            const inputDate = document.getElementById('date');
            const today = new Date();
            const monthAgo = new Date(today);
            monthAgo.setMonth(today.getMonth() - 1);

            inputDate.max = today.toISOString().split('T')[0];
            inputDate.min = monthAgo.toISOString().split('T')[0];

            /* =====================
               AJAX TAP
            ===================== */
            $('#kategorisegelmasuk').on('change', function() {
                let idtap = $(this).val();
                if (!idtap) return;

                $('#tappenerima').html('<option>Loading...</option>');

                $.post('{{ url('form/formkeluarbo') }}', {
                    idtap: idtap,
                    _token: '{{ csrf_token() }}'
                }).done(function(res) {
                    $('#tappenerima').html(res).trigger('change');
                });
            });

            /* =====================
               PREVENT DOUBLE SUBMIT
            ===================== */
            $('#formKeluarBO').on('submit', function() {
                $('#submitBtn').prop('disabled', true)
                    .text('Processing...');
            });

        });
    </script>
@endpush
