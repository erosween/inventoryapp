@extends('layout.layout')

@section('content')
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

                {{-- Header --}}
                <div class="page-header">
                    <h4 class="page-title">
                        Input Stok Keluar TAP
                    </h4>
                    <div class="text-muted">
                        Kirim barang ke TAP lain (menunggu approval)
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Pengiriman TAP</strong>
                                <div class="text-muted small">
                                    Stok akan berpindah setelah disetujui TAP penerima
                                </div>
                            </div>

                            <form action="{{ url('form/formkeluartap') }}" method="POST" id="formKeluarTap">
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

                                        {{-- Pengirim --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>PENGIRIM</label>
                                                <select name="pengirim" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->idtap }}">{{ $row->idtap }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <input type="hidden" id="input_mode" value="bulk">

                                        {{-- Penerima --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Penerima</label>
                                                <select name="penerima" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach ($tappenerima as $tap)
                                                        <option value="{{ $tap->idtap }}">{{ $tap->idtap }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Keterangan --}}
                                        <div class="col-md-12">
                                            <div class="form-group mb-1">
                                                <label>Tambahan Keterangan (Opsional)</label>
                                                <input type="text" name="tambahket" class="form-control">
                                            </div>
                                        </div>

                                        <div class="col-md-12" id="bulk-entry">
                                            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                                <strong>Daftar Denom</strong>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-bulk-row">
                                                    <i class="fas fa-plus mr-1"></i> Tambah Denom
                                                </button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Denom</th>
                                                            <th width="130">Stok</th>
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

                                {{-- Footer --}}
                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('keluar') }}" class="btn btn-light">
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

            $('.select2').each(function() {
                $(this).select2({
                    placeholder: 'Pilih / Cari…',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(this).closest('.card-body')
                });
            });

            // autofocus search
            $(document).on('select2:open', () => {
                setTimeout(function() {
                    document.querySelector('.select2-search__field')?.focus();
                }, 50);
            });

        });

        let currentTapStock = 0;
        let tapStockData = {}; // Cache data stok TAP pengirim

        function loadTapStock() {
            const iddenom = $('select[name="iddenom"]').val();

            if (!iddenom) {
                currentTapStock = 0;
                $('#stok_tap_info').val('');
                return;
            }

            // Ambil dari cache lokal (cepat)
            currentTapStock = parseInt(tapStockData[iddenom]) || 0;

            if (currentTapStock <= 0) {
                $('#stok_tap_info').val('Stok TAP habis');
                $('#stok_tap_warning').removeClass('d-none');
            } else {
                $('#stok_tap_info').val(currentTapStock + ' pcs');
                $('#stok_tap_warning').addClass('d-none');
            }
            
            // Re-validate qty
            validateQty();
        }

        $('select[name="pengirim"]').on('change', function() {
            const idtap = $(this).val();
            
            tapStockData = {}; // Clear cache
            $('#stok_tap_info').val('');
            $('select[name="iddenom"], .bulk-denom').prop('disabled', true).val(null).trigger('change');

            if (!idtap) return;

            // Bulk load TAP sender stock
            $.post('{{ route('ajax.get-all-stock-tap-keluar') }}', {
                idtap: idtap,
                _token: '{{ csrf_token() }}'
            }).done(res => {
                tapStockData = res;
                const bulk = $('#input_mode').val() === 'bulk';
                $('select[name="iddenom"]').prop('disabled', bulk);
                $('.bulk-denom').prop('disabled', !bulk);
                // Trigger refresh on denom to update display
                loadTapStock();
            });
        });

        $('select[name="iddenom"]').on('change', loadTapStock);

        function validateQty() {
            const qty = parseInt($('input[name="qty"]').val()) || 0;

            if (qty > currentTapStock || currentTapStock <= 0) {
                $('input[name="qty"]').addClass('is-invalid');
                $('#stok_tap_warning').removeClass('d-none');
                $('#submitBtn').prop('disabled', true);
            } else {
                $('input[name="qty"]').removeClass('is-invalid');
                $('#stok_tap_warning').addClass('d-none');
                $('#submitBtn').prop('disabled', false);
            }
        }

        $('input[name="qty"]').on('input', validateQty);

        // 🔒 BLOCK submit if qty > stock (belt-and-suspenders)
        $('#formKeluarTap').on('submit', function(e) {
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
                    Swal.fire({ icon: 'error', title: 'Periksa Daftar Denom', text: 'Pastikan denom, qty, SN, dan stok setiap baris valid.' });
                    return false;
                }
                return true;
            }
            const qty = parseInt($('#qty').val()) || 0;
            if (qty > currentTapStock || currentTapStock <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Stok Tidak Cukup',
                    text: 'Quantity (' + qty + ') melebihi stok TAP (' + currentTapStock + ')'
                });
                return false;
            }
        });

        const bulkDenoms = @json($denom->map(fn($d) => ['id' => $d->iddenom, 'name' => $d->denom])->values());
        let bulkIndex = 0;

        function addBulkRow() {
            const index = bulkIndex++;
            const options = bulkDenoms.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            const disabled = $('select[name="pengirim"]').val() ? '' : 'disabled';
            const row = $(`
                <tr>
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
                dropdownParent: $('#bulk-entry')
            });
        }

        $('#input_mode').on('change', function() {
            const bulk = this.value === 'bulk';
            $('.single-entry').toggleClass('d-none', bulk).find(':input').prop('disabled', bulk);
            $('#bulk-entry').toggleClass('d-none', !bulk).find(':input').prop('disabled', !bulk);
            if (bulk && !$('#bulk-rows tr').length) addBulkRow();
            $('.bulk-denom').prop('disabled', !bulk || !$('select[name="pengirim"]').val());
            $('select[name="iddenom"]').prop('disabled', bulk || !$('select[name="pengirim"]').val());
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
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const inputDate = document.getElementById('date');
            const today = new Date();
            const monthAgo = new Date(today);
            monthAgo.setMonth(today.getMonth() - 1);

            // Mengatur tanggal maksimal hingga hari ini
            inputDate.max = today.toISOString().split('T')[0];
            // Mengatur tanggal minimal ke satu bulan yang lalu
            inputDate.min = monthAgo.toISOString().split('T')[0];
        });
    </script>
@endpush
