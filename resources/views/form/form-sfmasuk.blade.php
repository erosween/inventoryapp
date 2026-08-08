@extends('layout.layout')

@section('content')
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

                {{-- Header --}}
                <div class="page-header">
                    <h4 class="page-title">Input Stok Masuk SF</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

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

                                        <input type="hidden" id="input_mode" value="bulk">

                                        <div class="col-md-12" id="bulk-entry">
                                            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                                <strong>Daftar Denom</strong>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-bulk-row">+ Tambah Denom</button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead><tr><th>Denom</th><th width="130">Stok TAP</th><th width="130">Qty</th><th>SN</th><th width="50"></th></tr></thead>
                                                    <tbody id="bulk-rows"></tbody>
                                                </table>
                                            </div>
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
            let tapStockData = {}; // Cache data stok TAP

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

                $.post('{{ route('ajax.get-sf-masuk') }}', {
                        idtap,
                        _token: '{{ csrf_token() }}'
                    })
                    .done(res => {
                        let options = '<option value="">-- Pilih SF --</option>';
                        res.forEach(item => {
                            options += `<option value="${item.idsf}">${item.namasf}</option>`;
                        });
                        $sf.html(options)
                            .prop('disabled', false)
                            .trigger('change');
                    });

                // Bulk load TAP stock
                $.post('{{ route('ajax.get-all-stock-tap-sfmasuk') }}', {
                    idtap: idtap,
                    _token: '{{ csrf_token() }}'
                }).done(res => {
                    tapStockData = res;
                    // Trigger change on iddenom if already selected
                    $('#iddenom').trigger('change');
                    if ($('#input_mode').val() === 'bulk') $('#submitBtn').prop('disabled', false);
                });
            });

            /* ================= RESET SAAT SF GANTI ================= */
            $('#idsf').on('change', function() {
                const hasSf = Boolean($(this).val());
                const bulk = $('#input_mode').val() === 'bulk';
                $('#iddenom').val(null).trigger('change');
                $('#iddenom').prop('disabled', !hasSf || bulk);
                $('.bulk-denom').prop('disabled', !hasSf || !bulk);
                $('#qty').val('');
                $('#tambahanket').val('');
                $('#stok_info').val('');
                $('#stok_warning').addClass('d-none');
                $('#submitBtn').prop('disabled', $('#input_mode').val() !== 'bulk');
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
                currentStock = parseInt(tapStockData[iddenom]) || 0;

                if (currentStock <= 0) {
                    $('#stok_info').val('Stok TAP habis');
                } else {
                    $('#stok_info').val(currentStock + ' pcs (Stok TAP)');
                }
                
                // Re-validate qty with new stock
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

            $('#formSfMasuk').on('submit', function(e) {

                if ($('#input_mode').val() === 'bulk') {
                    let valid = true;
                    const requested = {};
                    $('#bulk-rows tr').each(function() {
                        const denom = $(this).find('.bulk-denom').val();
                        const qty = parseInt($(this).find('.bulk-qty').val()) || 0;
                        if (!denom || qty < 1) {
                            valid = false;
                            return;
                        }
                        requested[denom] = (requested[denom] || 0) + qty;
                    });
                    Object.entries(requested).forEach(([denom, qty]) => {
                        if (qty > (parseInt(tapStockData[denom]) || 0)) valid = false;
                    });
                    if (!valid) {
                        e.preventDefault();
                        submitting = false;
                        Swal.fire({ icon: 'error', title: 'Periksa Daftar Denom', text: 'Qty salah satu denom melebihi stok TAP atau data belum lengkap.' });
                        return;
                    }
                }

                if (submitting) {
                    e.preventDefault();
                    return;
                }

                submitting = true;

                $('#submitBtn')
                    .prop('disabled', true)
                    .text('Menyimpan...');

            });

            const bulkDenoms = @json($denom->map(fn($d) => ['id' => $d->iddenom, 'name' => $d->denom])->values());
            let bulkIndex = 0;
            function addBulkRow() {
                const index = bulkIndex++;
                const options = bulkDenoms.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
                const disabled = $('#idsf').val() ? '' : 'disabled';
                const row = $(`<tr>
                    <td><select name="items[${index}][iddenom]" class="form-control form-control-sm bulk-denom" required ${disabled}><option value="">Pilih / cari denom</option>${options}</select></td>
                    <td class="bulk-stock text-right align-middle">-</td>
                    <td><input type="number" name="items[${index}][qty]" class="form-control form-control-sm bulk-qty" min="1" required></td>
                    <td><input type="text" name="items[${index}][sn]" class="form-control form-control-sm" required></td>
                    <td><button type="button" class="btn btn-sm btn-link text-danger remove-bulk-row">×</button></td>
                </tr>`);
                $('#bulk-rows').append(row);
                row.find('.bulk-denom').select2({
                    placeholder: 'Pilih / cari denom',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(document.body)
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
                const stock = parseInt(tapStockData[$(this).val()]) || 0;
                $(this).closest('tr').find('.bulk-stock').text(stock.toLocaleString('id-ID'));
            });
            $('#input_mode').trigger('change');


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
        });
    </script>
@endpush
