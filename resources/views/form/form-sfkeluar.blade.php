@extends('layout.layout')

@section('content')
    <style>
        .sf-out-page {
            --sf-primary: #4f46e5;
            --sf-primary-dark: #3730a3;
            --sf-ink: #172033;
            --sf-muted: #718096;
            --sf-line: #e7eaf1;
            --sf-soft: #f7f8fc;
            background:
                radial-gradient(circle at 94% 6%, rgba(99, 102, 241, .09), transparent 26rem),
                #f7f8fc;
            min-height: calc(100vh - 62px);
        }

        .sf-out-page .content { padding: 0; }
        .sf-out-page .page-inner { padding: 22px 32px 30px; }
        .sf-out-shell { max-width: 1120px; }

        .sf-out-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 14px;
        }

        .sf-out-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
            color: var(--sf-primary);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .sf-out-eyebrow::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--sf-primary);
            box-shadow: 0 0 0 5px rgba(79, 70, 229, .1);
        }

        .sf-out-title {
            margin: 0;
            color: var(--sf-ink);
            font-size: 25px;
            font-weight: 700;
            letter-spacing: -.025em;
        }

        .sf-out-subtitle {
            margin: 3px 0 0;
            color: var(--sf-muted);
            font-size: 12px;
        }

        .sf-out-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
            padding: 7px 11px;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: rgba(255, 255, 255, .78);
            color: #536076;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .sf-out-card {
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, .9) !important;
            border-radius: 18px !important;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 22px 60px rgba(30, 41, 59, .09) !important;
        }

        .sf-out-card .card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 22px;
            border-bottom: 1px solid var(--sf-line);
            background: #fff;
        }

        .sf-out-card .card-header-icon {
            display: grid;
            flex: 0 0 38px;
            width: 38px;
            height: 38px;
            place-items: center;
            border-radius: 12px;
            background: linear-gradient(145deg, #6259e8, #4338ca);
            color: #fff;
            box-shadow: 0 8px 20px rgba(79, 70, 229, .25);
        }

        .sf-out-card .card-header-title {
            margin: 0 0 3px;
            color: var(--sf-ink);
            font-size: 15px;
            font-weight: 700;
        }

        .sf-out-card .card-header-copy { color: var(--sf-muted); font-size: 11px; }
        .sf-out-card .card-body { padding: 17px 22px 18px; }

        .sf-form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            padding-bottom: 14px;
        }

        .sf-field .form-group { margin: 0; padding: 0; }
        .sf-field label {
            margin-bottom: 6px;
            color: #364152;
            font-size: 12px;
            font-weight: 700;
        }

        .sf-field label i { margin-right: 6px; color: #9299aa; }
        .sf-field .form-control,
        .sf-out-table .form-control,
        .sf-out-page .select2-container .select2-selection--single {
            height: 40px !important;
            border: 1px solid #dfe3eb !important;
            border-radius: 10px !important;
            background-color: #fff;
            color: #283245;
            font-size: 13px;
            box-shadow: none !important;
            transition: border-color .2s ease, box-shadow .2s ease !important;
        }

        .sf-field .form-control { padding: 0 14px; }
        .sf-out-page .select2-container .select2-selection--single { padding: 5px 12px !important; }
        .sf-out-page .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 28px; }
        .sf-out-page .select2-container--default .select2-selection--single .select2-selection__arrow { height: 38px; right: 7px; }
        .sf-out-page .select2-container--focus .select2-selection--single,
        .sf-field .form-control:focus,
        .sf-out-table .form-control:focus {
            border-color: #7771ea !important;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .1) !important;
        }

        .sf-out-page .select2-container--disabled .select2-selection--single,
        .sf-field .form-control:disabled { background: #f4f5f8 !important; color: #9ca3af; }

        .sf-denom-section {
            overflow: hidden;
            border: 1px solid var(--sf-line);
            border-radius: 14px;
            background: #fff;
        }

        .sf-denom-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--sf-line);
            background: #fbfbfd;
        }

        .sf-denom-title { margin: 0 0 3px; color: var(--sf-ink); font-size: 13px; font-weight: 700; }
        .sf-denom-hint { margin: 0; color: var(--sf-muted); font-size: 11px; }
        .sf-add-btn {
            padding: 9px 14px !important;
            border: 1px solid #d9d7fb !important;
            border-radius: 9px !important;
            background: #f1f0ff !important;
            color: var(--sf-primary-dark) !important;
            font-size: 12px !important;
            font-weight: 700;
        }
        .sf-add-btn:hover { border-color: #c3bffa !important; background: #e9e7ff !important; }

        .sf-out-table { min-width: 780px; margin: 0 !important; }
        .sf-out-table thead th {
            padding: 9px 13px !important;
            border: 0 !important;
            border-bottom: 1px solid var(--sf-line) !important;
            background: #fff;
            color: #788296;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .sf-out-table tbody td { padding: 8px 13px !important; border-color: #edf0f4 !important; vertical-align: middle; }
        .sf-out-table tbody tr:last-child td { border-bottom: 0; }
        .sf-out-table .form-control { height: 36px !important; font-size: 12px; }
        .sf-out-table .select2-container .select2-selection--single { height: 36px !important; padding: 3px 8px !important; }
        .sf-out-table .select2-container--default .select2-selection--single .select2-selection__arrow { height: 38px; }
        .sf-out-table .bulk-stock { color: #39445a; font-size: 13px; font-weight: 700; }
        .sf-out-table .bulk-qty.is-invalid {
            border-color: #e11d48 !important;
            background: #fff7f8;
            box-shadow: 0 0 0 4px rgba(225, 29, 72, .08) !important;
        }
        .sf-out-table .bulk-error {
            display: none;
            margin-top: 7px;
            color: #d11a3f;
            font-size: 10px;
            font-weight: 600;
            line-height: 1.35;
        }
        .sf-out-table .bulk-error.is-visible { display: flex; align-items: flex-start; gap: 5px; }
        .sf-out-table .bulk-error i { margin-top: 1px; }
        .sf-out-table .remove-bulk-row {
            width: 32px;
            height: 32px;
            padding: 0 !important;
            border-radius: 8px;
            background: #fff1f2;
            color: #e11d48 !important;
            font-size: 18px;
            line-height: 32px;
        }

        .sf-out-card .card-footer {
            padding: 12px 22px;
            border-top: 1px solid var(--sf-line);
            background: #fbfbfd;
        }
        .sf-back-btn, .sf-save-btn { min-width: 100px; padding: 9px 16px !important; border-radius: 9px !important; font-size: 11px; font-weight: 700; }
        .sf-back-btn { border: 1px solid #dfe3eb !important; background: #fff !important; color: #536076 !important; }
        .sf-save-btn { border: 0 !important; background: linear-gradient(135deg, #5b54e8, #4338ca) !important; box-shadow: 0 9px 20px rgba(79, 70, 229, .24); }
        .sf-save-btn:hover { transform: translateY(-1px); box-shadow: 0 12px 24px rgba(79, 70, 229, .3); }

        @media (max-width: 991.98px) {
            .sf-out-page .page-inner { padding: 28px 22px 40px; }
            .sf-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575.98px) {
            .sf-out-page .page-inner { padding: 22px 14px 32px; }
            .sf-out-heading { display: block; }
            .sf-out-title { font-size: 23px; }
            .sf-out-status { margin-top: 16px; }
            .sf-out-card .card-header, .sf-out-card .card-body, .sf-out-card .card-footer { padding-left: 18px; padding-right: 18px; }
            .sf-form-grid { grid-template-columns: 1fr; gap: 16px; }
            .sf-denom-toolbar { align-items: flex-start; flex-direction: column; }
            .sf-add-btn { width: 100%; }
            .sf-out-card .card-footer { justify-content: stretch !important; }
            .sf-back-btn, .sf-save-btn { flex: 1; }
        }
    </style>

    <div class="main-panel sf-out-page">
        <div class="content">
            <div class="page-inner">
                <div class="sf-out-shell">
                    <div class="sf-out-heading">
                        <div>
                            <div class="sf-out-eyebrow">Transaksi Sales Force</div>
                            <h1 class="sf-out-title">Input Stok Keluar SF</h1>
                            <p class="sf-out-subtitle">Catat distribusi stok ke Sales Force secara cepat dan akurat.</p>
                        </div>
                        <div class="sf-out-status"><i class="fas fa-shield-alt"></i> Transaksi tercatat otomatis</div>
                    </div>

                    <div class="card sf-out-card">
                            <div class="card-header">
                                <div class="card-header-icon"><i class="fas fa-dolly-flatbed"></i></div>
                                <div>
                                    <div class="card-header-title">Detail Distribusi</div>
                                    <div class="card-header-copy">Lengkapi tujuan distribusi dan daftar denom yang dikeluarkan.</div>
                                </div>
                            </div>

                            <form action="{{ url('sf-keluar') }}" method="POST" id="mainForm">
                                @csrf

                                @php use Illuminate\Support\Str; @endphp
                                <input type="hidden" name="trx_id" value="{{ Str::uuid() }}">


                                <div class="card-body">
                                    <div class="sf-form-grid">

                                        {{-- Tanggal --}}
                                        <div class="sf-field">
                                            <div class="form-group mb-1">
                                                <label for="date"><i class="far fa-calendar-alt"></i>Tanggal Transaksi</label>
                                                <input type="date" id="date" name="tgl" class="form-control"
                                                    value="{{ date('Y-m-d') }}" required>
                                            </div>
                                        </div>

                                        {{-- TAP --}}
                                        <div class="sf-field">
                                            <div class="form-group mb-1">
                                                <label for="kategoritap"><i class="fas fa-building"></i>Lokasi TAP</label>
                                                <select name="idtap" id="kategoritap" class="form-control select2"
                                                    required>
                                                    <option></option>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->idtap }}">{{ $row->idtap }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- SF --}}
                                        <div class="sf-field">
                                            <div class="form-group mb-1">
                                                <label for="idsf"><i class="fas fa-user"></i>Sales Force</label>
                                                <select name="idsf" id="idsf" class="form-control select2" disabled
                                                    required>
                                                    <option></option>
                                                </select>
                                            </div>
                                        </div>

                                        <input type="hidden" id="input_mode" value="bulk">

                                    </div>

                                    <div id="bulk-entry">
                                        <div class="sf-denom-section">
                                            <div class="sf-denom-toolbar">
                                                <div>
                                                    <div class="sf-denom-title">Daftar Denom</div>
                                                    <p class="sf-denom-hint">Pilih denom lalu masukkan jumlah stok yang akan dikeluarkan.</p>
                                                </div>
                                                <button type="button" class="btn sf-add-btn" id="add-bulk-row"><i class="fas fa-plus mr-1"></i> Tambah Denom</button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table sf-out-table">
                                                    <thead><tr><th>Denom</th><th width="130">Stok Tersedia</th><th width="130">Jumlah Keluar</th><th>SN / Keterangan</th><th width="64"><span class="sr-only">Aksi</span></th></tr></thead>
                                                    <tbody id="bulk-rows"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                {{-- FOOTER --}}
                                <div class="card-footer d-flex justify-content-end">
                                    <a href="{{ url('sf-keluar') }}" class="btn sf-back-btn mr-2"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>

                                    {{-- tombol preview --}}
                                    <button type="submit" id="submitBtn" class="btn btn-primary sf-save-btn">
                                        <i class="far fa-save mr-1"></i> Simpan Data
                                    </button>
                                </div>
                            </form>
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
            let sfStockData = {}; // Cache data stok SF

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
            });

            /* ================= RESET SAAT SF GANTI ================= */
            $('#idsf').on('change', function() {
                const idsf = $(this).val();

                $('#iddenom').val(null).trigger('change');
                $('#qty').val('');
                $('#tambahanket').val('');
                $('#stok_info').val('');
                $('#stok_warning').addClass('d-none');
                $('#submitBtn').prop('disabled', true);
                $('#iddenom, .bulk-denom').prop('disabled', true).empty().trigger('change');
                
                sfStockData = {}; // Clear cache

                if (!idsf) return;

                // Load all stock for this SF once
                $.post('{{ route('ajax.get-all-stock') }}', {
                        idsf: idsf,
                        _token: '{{ csrf_token() }}'
                    })
                    .done(res => {
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
                    return;
                }

                // Ambil dari cache lokal (cepat)
                currentStock = parseInt(sfStockData[iddenom]) || 0;

                if (currentStock <= 0) {
                    $('#stok_info').val('Stok habis');
                    $('#stok_warning').removeClass('d-none');
                    $('#submitBtn').prop('disabled', true);
                } else {
                    $('#stok_info').val(currentStock + ' pcs');
                    $('#stok_warning').addClass('d-none');
                    $('#submitBtn').prop('disabled', false);
                }
            });

            /* ================= VALIDASI QTY ================= */
            $('#qty').on('input', function() {
                const qty = parseInt($(this).val()) || 0;

                if (qty > currentStock) {
                    $(this).addClass('is-invalid');
                    $('#stok_warning').removeClass('d-none');
                    $('#submitBtn').prop('disabled', true);
                } else {
                    $(this).removeClass('is-invalid');
                    $('#stok_warning').addClass('d-none');
                    $('#submitBtn').prop('disabled', false);
                }
            });

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
                        if (!denom || qty < 1) {
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
                        Swal.fire({ icon: 'error', title: 'Periksa Daftar Denom', text: 'Qty salah satu denom melebihi stok petugas atau data belum lengkap.' });
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
                validateBulkRows();
            }
            function validateBulkRows() {
                const requested = {};
                let hasStockError = false;

                $('#bulk-rows tr').each(function() {
                    const $row = $(this);
                    const denom = $row.find('.bulk-denom').val();
                    const $qty = $row.find('.bulk-qty');
                    const $error = $row.find('.bulk-error');
                    const qty = parseInt($qty.val(), 10) || 0;
                    const stock = parseInt(sfStockData[denom], 10) || 0;

                    $qty.removeClass('is-invalid').removeAttr('aria-invalid');
                    $error.removeClass('is-visible').empty();

                    if (!denom || qty < 1) return;

                    requested[denom] = (requested[denom] || 0) + qty;
                    if (qty > stock || requested[denom] > stock) {
                        hasStockError = true;
                        $qty.addClass('is-invalid').attr('aria-invalid', 'true');
                        $error
                            .html(`<i class="fas fa-exclamation-circle"></i><span>Qty tidak boleh melebihi stok tersedia (${stock.toLocaleString('id-ID')}).</span>`)
                            .addClass('is-visible');
                    }
                });

                $('#submitBtn').prop('disabled', hasStockError || !$('#idsf').val());
                return !hasStockError;
            }
            function addBulkRow() {
                const index = bulkIndex++;
                const options = denomOptions();
                const disabled = $('#idsf').val() ? '' : 'disabled';
                const row = $(`<tr>
                    <td><select name="items[${index}][iddenom]" class="form-control form-control-sm bulk-denom" required ${disabled}><option value="">Pilih / cari denom</option>${options}</select></td>
                    <td class="bulk-stock text-right align-middle">-</td>
                    <td><input type="number" name="items[${index}][qty]" class="form-control form-control-sm bulk-qty" min="1" required><div class="bulk-error" role="alert" aria-live="polite"></div></td>
                    <td><input type="text" name="items[${index}][tambahanket]" class="form-control form-control-sm"></td>
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
                if ($('#bulk-rows tr').length > 1) {
                    $(this).closest('tr').remove();
                    validateBulkRows();
                }
            }).on('change', '.bulk-denom', function() {
                const stock = parseInt(sfStockData[$(this).val()]) || 0;
                $(this).closest('tr').find('.bulk-stock').text(stock.toLocaleString('id-ID'));
                validateBulkRows();
            }).on('input change', '.bulk-qty', function() {
                validateBulkRows();
            });
            $('#input_mode').trigger('change');

        });

        // Tanggal maksimal hari ini dan minimal sebulan yang lalu
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
