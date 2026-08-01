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

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

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

                                        <input type="hidden" id="input_mode" value="bulk">

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

                                        <div class="col-md-12" id="bulk-entry">
                                            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                                <strong>Daftar Denom Retur</strong>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-bulk-row">
                                                    <i class="fas fa-plus mr-1"></i> Tambah Denom
                                                </button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Denom</th>
                                                            <th width="115">Stok SF</th>
                                                            <th width="110">Qty</th>
                                                            <th>SN</th>
                                                            <th>Keterangan</th>
                                                            <th width="50"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="bulk-rows"></tbody>
                                                </table>
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

                $.post('{{ route('ajax.get-sf-keluar') }}', {
                    idtap,
                    _token: '{{ csrf_token() }}'
                }).done(res => {
                    let options = '<option value="">-- Pilih SF --</option>';
                    res.forEach(item => {
                        options += `<option value="${item.idsf}">${item.namasf}</option>`;
                    });
                    $sf.html(options)
                        .prop('disabled', false)
                        .trigger('change');
                });
            });

            /* ================= RESET SAAT SF GANTI ================= */
            let currentStock = 0;
            let sfStockData = {}; // Cache data stok SF

            $('#idsf').on('change', function() {
                const idsf = $(this).val();

                $('#iddenom, .bulk-denom').prop('disabled', true).empty().trigger('change');
                $('#qty').val('');
                $('#stok_info').val('');
                $('#stok_warning').addClass('d-none');
                $('#submitBtn').prop('disabled', true);

                sfStockData = {}; // Clear cache

                if (!idsf) return;

                // Bulk load SF stock
                $.post('{{ route('ajax.get-all-stock-sf-retur') }}', {
                    idsf: idsf,
                    _token: '{{ csrf_token() }}'
                }).done(res => {
                    sfStockData = res;
                    refreshAvailableDenoms();
                    if ($('#input_mode').val() === 'bulk') $('#submitBtn').prop('disabled', false);
                });
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
                currentStock = parseInt(sfStockData[iddenom]) || 0;

                if (currentStock <= 0) {
                    $('#stok_info').val('Stok SF kosong');
                } else {
                    $('#stok_info').val(currentStock + ' pcs');
                }
                
                // Re-validate qty
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
            $('#mainForm').on('submit', function(e) {
                if (submitting) return false;

                if ($('#input_mode').val() === 'bulk') {
                    let valid = true;
                    $('#bulk-rows tr').each(function() {
                        const denom = $(this).find('.bulk-denom').val();
                        const qty = parseInt($(this).find('.bulk-qty').val()) || 0;
                        if (!denom || qty < 1 || qty > (parseInt(sfStockData[denom]) || 0)) valid = false;
                    });
                    if (!valid) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Periksa Daftar Denom',
                            text: 'Qty salah satu denom melebihi stok petugas atau data belum lengkap.'
                        });
                        return false;
                    }
                }
                submitting = true;

                $('#submitBtn')
                    .prop('disabled', true)
                    .text('Menyimpan...');
            });

            const bulkDenoms = @json($denom->map(fn($d) => ['id' => $d->iddenom, 'name' => $d->denom])->values());
            let bulkIndex = 0;

            function availableDenoms() {
                return bulkDenoms.filter(d => (parseInt(sfStockData[d.id]) || 0) > 0);
            }

            function denomOptions() {
                return availableDenoms().map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            }

            function refreshAvailableDenoms() {
                const options = denomOptions();
                const bulk = $('#input_mode').val() === 'bulk';
                $('#iddenom')
                    .html(`<option value="">Pilih / cari denom</option>${options}`)
                    .prop('disabled', !$('#idsf').val() || bulk)
                    .val(null)
                    .trigger('change');
                $('.bulk-denom').each(function() {
                    $(this)
                        .html(`<option value="">Pilih / cari denom</option>${options}`)
                        .prop('disabled', !$('#idsf').val() || !bulk)
                        .val(null)
                        .trigger('change');
                });
            }

            function addBulkRow() {
                const index = bulkIndex++;
                const disabled = $('#idsf').val() ? '' : 'disabled';
                const row = $(`<tr>
                    <td><select name="items[${index}][iddenom]" class="form-control form-control-sm bulk-denom" required ${disabled}><option value="">Pilih / cari denom</option>${denomOptions()}</select></td>
                    <td class="bulk-stock text-right align-middle">-</td>
                    <td><input type="number" name="items[${index}][qty]" class="form-control form-control-sm bulk-qty" min="1" required></td>
                    <td><input type="text" name="items[${index}][sn]" class="form-control form-control-sm" required></td>
                    <td><input type="text" name="items[${index}][tambahket]" class="form-control form-control-sm" required></td>
                    <td><button type="button" class="btn btn-sm btn-link text-danger remove-bulk-row">×</button></td>
                </tr>`);
                $('#bulk-rows').append(row);
                row.find('.bulk-denom').select2({
                    placeholder: 'Pilih / cari denom',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#bulk-entry')
                });
            }

            $('#input_mode').on('change', function() {
                const bulk = this.value === 'bulk';
                $('.single-entry').toggleClass('d-none', bulk).find(':input').prop('disabled', bulk);
                $('#bulk-entry').toggleClass('d-none', !bulk).find(':input').prop('disabled', !bulk);
                if (bulk && !$('#bulk-rows tr').length) addBulkRow();
                $('.bulk-denom').prop('disabled', !bulk || !$('#idsf').val());
                $('#iddenom').prop('disabled', bulk || !$('#idsf').val());
                if (bulk) $('#submitBtn').prop('disabled', false);
            });

            $('#add-bulk-row').on('click', addBulkRow);
            $('#bulk-rows').on('click', '.remove-bulk-row', function() {
                if ($('#bulk-rows tr').length > 1) $(this).closest('tr').remove();
            }).on('change', '.bulk-denom', function() {
                const stock = parseInt(sfStockData[$(this).val()]) || 0;
                $(this).closest('tr').find('.bulk-stock').text(stock.toLocaleString('id-ID'));
            });
            $('#input_mode').trigger('change');

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
