{{--
    Input pengembalian stok SF ke TAP. Kirim ke ReturSfController.
--}}@extends('layout.layout')

@push('styles')
<style>
    .retur-destination-cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .retur-destination-card{position:relative;display:flex;align-items:center;gap:12px;min-height:82px;margin:0;padding:14px 16px;border:1px solid #dce2ec;border-radius:12px;background:#fff;cursor:pointer;transition:border-color .15s,background .15s,box-shadow .15s}
    .retur-destination-card:hover{border-color:#8b83f5;background:#fafaff}
    .retur-destination-card:has(input:checked){border-color:#5145cd;background:#f4f3ff;box-shadow:0 0 0 2px rgba(81,69,205,.12)}
    .retur-destination-card input{position:absolute;opacity:0;pointer-events:none}
    .retur-destination-card:focus-within{outline:3px solid rgba(81,69,205,.25);outline-offset:2px}
    .retur-destination-card:has(input:checked) .retur-destination-icon{background:#5145cd;color:#fff}
    .retur-destination-indicator{display:grid;place-items:center;flex:0 0 20px;width:20px;height:20px;border:2px solid #aab2c0;border-radius:50%;background:#fff}
    .retur-destination-card:has(input:checked) .retur-destination-indicator{border:6px solid #5145cd}
    .retur-destination-icon{display:grid;place-items:center;width:40px;height:40px;border-radius:10px;background:#f1f0ff;color:#5145cd}
    .retur-destination-copy{display:grid;gap:3px}
    .retur-destination-copy strong{color:#20283a;font-size:14px}
    .retur-destination-copy small{color:#748097;font-size:12px}
    @media(max-width:575px){.retur-destination-cards{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

                {{-- Error --}}
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                {{-- Header --}}
                <div class="page-header">
                    <h4 class="page-title">Input Retur SF</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Input</strong>
                                <div class="text-muted small">
                                    Returkan ke gudang TAP atau pindahkan langsung ke Sales Force lain.
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

                                        <div class="col-md-12 mt-3">
                                            <label class="d-block mb-2">Tujuan retur</label>
                                            <div class="retur-destination-cards">
                                                <label class="retur-destination-card">
                                                    <input type="radio" name="return_type" value="warehouse" @checked(old('return_type', 'warehouse') === 'warehouse')>
                                                    <span class="retur-destination-indicator"></span><span class="retur-destination-icon"><i class="fas fa-warehouse"></i></span>
                                                    <span class="retur-destination-copy"><strong>Retur ke gudang</strong></span>
                                                </label>
                                                <label class="retur-destination-card">
                                                    <input type="radio" name="return_type" value="sf" @checked(old('return_type') === 'sf')>
                                                    <span class="retur-destination-indicator"></span><span class="retur-destination-icon"><i class="fas fa-people-arrows"></i></span>
                                                    <span class="retur-destination-copy"><strong>Retur ke SF</strong></span>
                                                </label>
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
                                                <label>Stok Dari SF</label>
                                                <select name="idsf" id="idsf" class="form-control select2" disabled
                                                    required>
                                                    <option></option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6 d-none" id="destination-sf-wrap">
                                            <div class="form-group mb-1">
                                                <label>SF Tujuan</label>
                                                <select name="destination_idsf" id="destination_idsf" class="form-control select2" disabled>
                                                    <option></option>
                                                    @foreach($salesForces as $target)
                                                        <option value="{{ $target->idsf }}" data-tap="{{ $target->idtap }}" @selected(old('destination_idsf') === $target->idsf)>{{ $target->namasf }}</option>
                                                    @endforeach
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
                                                            <th colspan="2">Rentang Serial Number (wajib)</th>
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

            function updateReturnDestination() {
                const toSf = $('input[name="return_type"]:checked').val() === 'sf';
                $('#destination-sf-wrap').toggleClass('d-none', !toSf);
                $('#destination_idsf').prop('disabled', !toSf).prop('required', toSf);
                if (!toSf) $('#destination_idsf').val(null).trigger('change');
            }
            $('input[name="return_type"]').on('change', updateReturnDestination);
            updateReturnDestination();

            $(document).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-search__field')?.focus();
                }, 50);
            });

            /* ================= TAP → SF ================= */
            function loadSalesForce(idtap, selectedSf = '') {
                const $sf = $('#idsf');

                $sf.prop('disabled', true).empty().trigger('change');

                if (!idtap) return;

                $.post('{{ route('ajax.get-sf-masuk') }}', {
                    idtap,
                    _token: '{{ csrf_token() }}'
                }).done(res => {
                    let options = '<option value="">-- Pilih SF --</option>';
                    res.forEach(item => {
                        const selected = String(item.idsf) === String(selectedSf) ? ' selected' : '';
                        options += `<option value="${$('<div>').text(item.idsf).html()}" data-tap="${$('<div>').text(item.idtap).html()}"${selected}>${$('<div>').text(item.namasf).html()}</option>`;
                    });
                    $sf.html(options)
                        .prop('disabled', false)
                        .trigger('change');
                });
            }
            $('#kategoritap').on('change', function() {
                $('#destination_idsf').val(null).trigger('change');
                filterDestinationSalesForces();
                loadSalesForce($(this).val());
            });
            function filterDestinationSalesForces() {
                const selectedTap = String($('#kategoritap').val() || '').trim().toUpperCase();
                $('#destination_idsf option').each(function() {
                    if (!this.value) return;
                    const sameTap = String($(this).data('tap') || '').trim().toUpperCase() === selectedTap;
                    $(this).prop('disabled', !sameTap).toggle(sameTap);
                });
                if ($('#destination_idsf option:selected').length && String($('#destination_idsf option:selected').data('tap') || '').trim().toUpperCase() !== selectedTap) {
                    $('#destination_idsf').val(null).trigger('change');
                }
            }
            filterDestinationSalesForces();
            $('#idsf').on('change', function() {
                const sourceSf = String($(this).val() || '');
                const sourceTap = String($('#kategoritap').val() || '');
                $('#destination_idsf option').each(function() {
                    if (!this.value) return;
                    const sameTap = String($(this).data('tap') || '').trim().toUpperCase() === sourceTap.trim().toUpperCase();
                    $(this).prop('disabled', this.value === sourceSf || !sameTap).toggle(sameTap);
                });
                if ($('#destination_idsf').val() === sourceSf) $('#destination_idsf').val(null).trigger('change');
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
                    const requested = {};
                    $('#bulk-rows tr').each(function() {
                        const denom = $(this).find('.bulk-denom').val();
                        const qty = parseInt($(this).find('.bulk-qty').val()) || 0;
                        const start = ($(this).find('.retur-sn-start').val() || '').trim();
                        const end = ($(this).find('.retur-sn-end').val() || '').trim();
                        if (!denom || qty < 1 || qty > 2000000 || !start || !end) {
                            valid = false;
                            return;
                        }
                        requested[denom] = (requested[denom] || 0) + qty;
                    });
                    Object.entries(requested).forEach(([denom, qty]) => {
                        if (qty > (parseInt(sfStockData[denom]) || 0)) valid = false;
                    });
                    if (!valid) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Periksa Daftar Denom',
                            text: 'Lengkapi denom dan input SN. Qty harus valid dan tidak boleh melebihi stok SF.'
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
                    <td><span class="bulk-qty-view text-muted">—</span><input type="hidden" name="items[${index}][qty]" class="bulk-qty"></td>
                    <td colspan="2"><div class="serial-range d-flex align-items-center"><input type="text" name="items[${index}][sn_start]" class="form-control form-control-sm retur-sn-start" inputmode="numeric" placeholder="SN awal" required><span class="px-1">–</span><input type="text" name="items[${index}][sn_end]" class="form-control form-control-sm retur-sn-end" inputmode="numeric" placeholder="SN akhir" required></div><small class="text-muted">Isi kedua batas SN. Qty dihitung otomatis dari rentang.</small></td>
                    <td><input type="text" name="items[${index}][tambahket]" class="form-control form-control-sm" required></td>
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

            $('#add-bulk-row').on('click', addBulkRow);
            $('#bulk-rows').on('click', '.remove-bulk-row', function() {
                if ($('#bulk-rows tr').length > 1) $(this).closest('tr').remove();
            }).on('change', '.bulk-denom', function() {
                const stock = parseInt(sfStockData[$(this).val()]) || 0;
                $(this).closest('tr').find('.bulk-stock').text(stock.toLocaleString('id-ID'));
                updateRangeQty($(this).closest('tr'));
            }).on('input', '.retur-sn-start, .retur-sn-end', function() {
                updateRangeQty($(this).closest('tr'));
            });

            function updateRangeQty($row) {
                const start = ($row.find('.retur-sn-start').val() || '').trim();
                const endInput = ($row.find('.retur-sn-end').val() || '').trim();
                if (!start || !endInput) { $row.find('.bulk-qty').val(''); $row.find('.bulk-qty-view').text('—'); return; }
                const end = endInput;
                if (!/^\d+$/.test(start) || !/^\d+$/.test(end) || start.length !== end.length || end < start) {
                    $row.find('.bulk-qty').val(''); $row.find('.bulk-qty-view').text('—'); return;
                }
                const qty = Number(BigInt(end) - BigInt(start) + 1n);
                $row.find('.bulk-qty').val(Number.isSafeInteger(qty) ? qty : '');
                $row.find('.bulk-qty-view').text(Number.isSafeInteger(qty) ? qty.toLocaleString('id-ID') : '—');
            }
            addBulkRow();

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
