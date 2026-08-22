@extends('layout.layout')

@section('content')
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

                {{-- Error --}}
                @if ($errors->has('error'))
                    <div class="alert alert-danger">
                        {{ $errors->first('error') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Page Header --}}
                <div class="page-header">
                    <h4 class="page-title">Input Voucher Rusak TAP</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Input</strong>
                                <div class="text-muted small">
                                    Input data voucher rusak / mati dari TAP
                                </div>
                            </div>

                            <form action="{{ url('vrusak') }}" method="POST" id="mainForm">
                                @csrf

                                <div class="card-body">
                                    <div class="row">

                                        {{-- Tanggal --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Tanggal</label>
                                                <input type="date" id="date" name="tgl" class="form-control"
                                                    value="{{ date('Y-m-d') }}" required>
                                            </div>
                                        </div>

                                        {{-- TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Pengirim (TAP)</label>
                                                <select name="pengirim" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach ($tap as $row)
                                                        <option value="{{ $row->idtap }}">
                                                            {{ $row->idtap }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                                <div>
                                                    <strong>Daftar Voucher Rusak</strong>
                                                    <div class="text-muted small">Setiap baris akan mengurangi stok TAP sesuai denom.</div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-bulk-row">
                                                    <i class="fas fa-plus mr-1"></i> Tambah Baris
                                                </button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th style="min-width:170px">Denom</th>
                                                            <th width="115">Stok Ready</th>
                                                            <th width="105">Qty</th>
                                                            <th style="min-width:180px">SN Awal - SN Akhir</th>
                                                            <th style="min-width:130px">Status</th>
                                                            <th style="min-width:200px">Keterangan Tambahan</th>
                                                            <th width="50"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="bulk-rows"></tbody>
                                                </table>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                {{-- FOOTER --}}
                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('vrusak') }}" class="btn btn-light">
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

            const denoms = @json($denom->map(fn ($row) => ['id' => $row->iddenom, 'name' => $row->denom])->values());
            let rowIndex = 0;
            let tapStocks = {};

            function availableDenomOptions() {
                return denoms
                    .filter(denom => Number(tapStocks[denom.id] || 0) > 0)
                    .map(denom => `<option value="${denom.id}">${denom.name}</option>`)
                    .join('');
            }

            function refreshDenomOptions(stockLoaded = false) {
                const hasTap = Boolean($('select[name="pengirim"]').val());
                const options = availableDenomOptions();

                $('#bulk-rows select[name$="[iddenom]"]').each(function() {
                    const select = $(this);
                    const previous = select.val();
                    select.empty().append('<option value="">Pilih / cari denom</option>').append(options);
                    if (previous && Number(tapStocks[previous] || 0) > 0) {
                        select.val(previous);
                    }
                    select.prop('disabled', !hasTap || !stockLoaded).trigger('change.select2').trigger('change');
                });
            }

            function addRow(values = {}) {
                const index = rowIndex++;
                const denomOptions = availableDenomOptions();
                const denomDisabled = Object.keys(tapStocks).length ? '' : 'disabled';
                const row = $(`
                    <tr>
                        <td><select name="items[${index}][iddenom]" class="form-control form-control-sm row-select" required ${denomDisabled}><option value="">Pilih / cari denom</option>${denomOptions}</select></td>
                        <td class="stock-ready text-right align-middle font-weight-bold">-</td>
                        <td><input type="number" name="items[${index}][qty]" class="form-control form-control-sm" min="1" required></td>
                        <td><input type="text" name="items[${index}][sn]" class="form-control form-control-sm" maxlength="255" required></td>
                        <td><select name="items[${index}][ketvf]" class="form-control form-control-sm row-select" required><option value="">Pilih status</option><option value="RUSAK">RUSAK</option><option value="MATI">MATI</option></select></td>
                        <td><input type="text" name="items[${index}][tambahanket]" class="form-control form-control-sm" maxlength="500" required></td>
                        <td><button type="button" class="btn btn-sm btn-link text-danger remove-bulk-row" aria-label="Hapus baris">&times;</button></td>
                    </tr>
                `);
                $('#bulk-rows').append(row);
                row.find('.row-select').select2({
                    width: '100%',
                    dropdownParent: $(document.body)
                });
            }

            $('#add-bulk-row').on('click', () => addRow());
            $('#bulk-rows').on('click', '.remove-bulk-row', function() {
                if ($('#bulk-rows tr').length > 1) {
                    $(this).closest('tr').remove();
                }
            }).on('change', 'select[name$="[iddenom]"]', function() {
                const stock = Number(tapStocks[$(this).val()] || 0);
                $(this).closest('tr').find('.stock-ready').text(stock.toLocaleString('id-ID'));
            });

            $('select[name="pengirim"]').on('change', function() {
                const idtap = $(this).val();
                tapStocks = {};
                $('.stock-ready').text('-');
                refreshDenomOptions();
                if (!idtap) return;

                $.ajax({
                    url: @json(route('vrusak.stock-tap')),
                    method: 'POST',
                    data: { idtap: idtap, _token: @json(csrf_token()) },
                    success: function(stocks) {
                        tapStocks = stocks || {};
                        refreshDenomOptions(true);
                    },
                    error: function() {
                        Swal.fire('Gagal', 'Stok TAP tidak dapat dimuat. Silakan coba kembali.', 'error');
                    }
                });
            });
            addRow();

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
