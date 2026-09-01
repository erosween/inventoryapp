@extends('layout.layout')

@section('content')
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

                {{-- Header --}}
                <div class="page-header">
                    <h4 class="page-title">Input Inject Voucher Fisik Byu</h4>
                    <div class="text-muted">
                        Inject VF akan mengurangi stok segel TAP
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Inject VF</strong>
                                <div class="text-muted small">
                                    Pastikan qty sesuai stok segel TAP
                                </div>
                            </div>

                            <form action="{{ url('form/forminjectbyu') }}" method="POST" id="formInject">

                                @csrf

                                <div class="card-body">
                                    <div class="row">

                                        {{-- Tanggal --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Tanggal</label>
                                                <input type="date" name="tgl" id="date" class="form-control"
                                                    value="{{ old('tgl', date('Y-m-d')) }}"
                                                    max="{{ date('Y-m-d') }}" required>
                                            </div>
                                        </div>

                                        {{-- TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>TAP</label>
                                                <select name="idtap" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->idtap }}" {{ old('idtap') === $row->idtap ? 'selected' : '' }}>{{ $row->idtap }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Stok TAP --}}
                                        <div class="col-md-12">
                                            <div class="form-group mb-1">
                                                <label>Stok Segel BYU TAP</label>
                                                <input type="text" id="stok_segel_info" class="form-control mb-1" readonly>
                                                <div class="text-danger small d-none" id="stok_warning" style="font-weight: 600;">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    <span>Total quantity melebihi stok TAP</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-12" id="bulk-entry">
                                            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                                <div>
                                                    <strong>Daftar Denom</strong>
                                                    <div class="text-muted small">Total quantity: <strong id="bulk-total-qty">0</strong></div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-bulk-row">
                                                    <i class="fas fa-plus mr-1"></i> Tambah Denom
                                                </button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Denom Inject</th>
                                                            <th width="130">Qty</th>
                                                            <th>SN</th>
                                                            <th width="50"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="bulk-rows"></tbody>
                                                </table>
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
            let stockLoaded = false;
            let bulkIndex = 0;
            const bulkDenoms = @json($denom->map(fn ($d) => ['id' => $d->iddenom, 'name' => $d->denom])->values());

            function escapeHtml(value) {
                return $('<div>').text(value ?? '').html();
            }

            $('.select2').each(function() {
                $(this).select2({
                    placeholder: 'Pilih / Cari…',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(this).closest('.card-body')
                });
            });

            $(document).on('select2:open', () => {
                setTimeout(function() {
                    document.querySelector('.select2-search__field')?.focus();
                }, 50);
            });

            function addBulkRow(item = {}) {
                const index = bulkIndex++;
                const options = bulkDenoms.map(denom => {
                    const selected = String(item.iddenom || '') === String(denom.id) ? ' selected' : '';
                    return `<option value="${escapeHtml(denom.id)}"${selected}>${escapeHtml(denom.name)}</option>`;
                }).join('');
                const row = $(`
                    <tr>
                        <td>
                            <select name="items[${index}][iddenom]" class="form-control form-control-sm bulk-denom" required>
                                <option value="">Pilih / cari denom</option>${options}
                            </select>
                        </td>
                        <td>
                            <input type="number" name="items[${index}][qty]" class="form-control form-control-sm bulk-qty"
                                min="1" value="${escapeHtml(item.qty || '')}" required>
                        </td>
                        <td>
                            <input type="text" name="items[${index}][sn]" class="form-control form-control-sm"
                                value="${escapeHtml(item.sn || '')}" placeholder="SN Awal - SN Akhir" required>
                        </td>
                        <td><button type="button" class="btn btn-sm btn-link text-danger remove-bulk-row" aria-label="Hapus denom">×</button></td>
                    </tr>
                `);
                $('#bulk-rows').append(row);
                row.find('.bulk-denom').select2({
                    placeholder: 'Pilih / cari denom',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(document.body)
                });
                validateBulkQty();
            }

            function bulkTotalQty() {
                let total = 0;
                $('.bulk-qty').each(function() {
                    total += parseInt($(this).val(), 10) || 0;
                });
                return total;
            }

            function validateBulkQty() {
                const total = bulkTotalQty();
                const invalid = !stockLoaded || currentSegelStock <= 0 || total > currentSegelStock;
                $('#bulk-total-qty').text(total.toLocaleString('id-ID'));
                $('.bulk-qty').toggleClass('is-invalid', stockLoaded && total > currentSegelStock);
                $('#stok_warning').toggleClass('d-none', !invalid);
                $('#stok_warning span').text(
                    !stockLoaded ? 'Pilih TAP untuk memuat stok segel By.U' :
                    currentSegelStock <= 0 ? 'Stok segel By.U TAP habis' :
                    `Total quantity (${total.toLocaleString('id-ID')}) melebihi stok tersedia (${currentSegelStock.toLocaleString('id-ID')})`
                );
                $('#submitBtn').prop('disabled', invalid || !$('#bulk-rows tr').length);
                return !invalid;
            }

            function loadSegelStock() {
                const idtap = $('select[name="idtap"]').val();
                stockLoaded = false;
                currentSegelStock = 0;
                if (!idtap) {
                    $('#stok_segel_info').val('Pilih TAP terlebih dahulu');
                    validateBulkQty();
                    return;
                }

                $('#stok_segel_info').val('Loading...');
                validateBulkQty();

                $.post('{{ route('ajax.get-stock-byu-tap') }}', {
                        idtap
                    })
                    .done(res => {
                        currentSegelStock = parseInt(res.stock) || 0;
                        stockLoaded = true;
                        $('#stok_segel_info').val(currentSegelStock > 0
                            ? currentSegelStock.toLocaleString('id-ID') + ' pcs'
                            : 'Stok segel By.U habis');
                        validateBulkQty();
                    })
                    .fail(() => {
                        $('#stok_segel_info').val('Gagal memuat stok');
                        validateBulkQty();
                    });
            }

            $('select[name="idtap"]').on('change', loadSegelStock);
            $('#add-bulk-row').on('click', () => addBulkRow());
            $('#bulk-rows').on('input change', '.bulk-qty', validateBulkQty)
                .on('click', '.remove-bulk-row', function() {
                    if ($('#bulk-rows tr').length > 1) {
                        $(this).closest('tr').remove();
                        validateBulkQty();
                    }
                });

            const oldItems = @json(old('items', []));
            if (oldItems.length) {
                oldItems.forEach(addBulkRow);
            } else {
                addBulkRow();
            }
            const oldTap = @json(old('idtap'));
            if (oldTap) {
                $('select[name="idtap"]').val(oldTap).trigger('change');
            } else {
                validateBulkQty();
            }

            $('#formInject').on('submit', function(event) {
                if (!validateBulkQty()) {
                    event.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Stok Tidak Cukup',
                        text: 'Total quantity seluruh denom tidak boleh melebihi stok segel By.U TAP.'
                    });
                    return false;
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
