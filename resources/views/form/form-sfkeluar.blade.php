{{--
    Input penjualan manual SF. Pilihan sales, outlet, dan stok dipasok oleh SfkeluarController.
--}}@extends('layout.layout')

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
        .sf-item-card .form-control,
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
        .sf-item-card .form-control:focus {
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

        .sf-item-list { display:grid; gap:12px; padding:14px; }
        .sf-item-card { display:grid; grid-template-columns:minmax(190px,1.1fr) 92px 128px minmax(380px,1.8fr) minmax(220px,1fr) 34px; align-items:start; gap:12px; padding:14px; border:1px solid #e8ebf2; border-radius:13px; background:#fff; }
        .sf-item-card > * { min-width:0; }
        .sf-item-label { display:block; margin-bottom:6px; color:#788296; font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; }
        .sf-item-stock { display:flex; align-items:center; min-height:40px; color:#39445a; font-size:13px; font-weight:750; font-variant-numeric:tabular-nums; }
        .sf-item-card .form-control,
        .sf-item-card .select2-container .select2-selection--single { height:40px !important; font-size:12px; }
        .sf-item-card .select2-container .select2-selection--single { padding:5px 8px !important; }
        .sf-item-card .select2-container--default .select2-selection--single .select2-selection__arrow { height:38px; }
        .sf-qty-display { display:flex; align-items:center; justify-content:center; min-height:40px; border:1px solid #e3e7ef; border-radius:9px; background:#f8f9fc; color:#334155; font-size:14px; font-weight:800; font-variant-numeric:tabular-nums; }
        .sf-qty-display.is-empty { color:#9aa3b2; }
        .sf-serial-top { display:flex; align-items:center; justify-content:space-between; gap:10px; min-height:12px; margin-bottom:6px; }
        .sf-serial-fields { display:grid; grid-template-columns:minmax(0,1fr) 24px minmax(0,1fr); align-items:start; gap:8px; }
        .sf-serial-field { min-width:0; }
        .sf-serial-field-label { display:none; }
        .sf-serial-separator { display:flex; align-items:center; justify-content:center; height:40px; color:#8b95a5; font-size:12px; font-weight:800; text-align:center; }
        .sf-serial-hint { display:block; min-height:15px; margin-top:6px; color:#718096; font-size:10px; line-height:1.4; }
        .sf-serial-hint.is-invalid { color:#d11a3f; }
        .sf-serial-hint.is-valid { color:#059669; }
        .sf-item-note { grid-column:5; grid-row:1; padding-top:0; }
        .sf-item-note .sf-item-label { margin-bottom:8px; }
        .sf-item-note .form-control { min-height:40px; }
        .sf-item-card .bulk-qty.is-invalid {
            border-color: #e11d48 !important;
            background: #fff7f8;
            box-shadow: 0 0 0 4px rgba(225, 29, 72, .08) !important;
        }
        .sf-item-card .bulk-error {
            display: none;
            margin-top: 7px;
            color: #d11a3f;
            font-size: 10px;
            font-weight: 600;
            line-height: 1.35;
        }
        .sf-item-card .bulk-error.is-visible { display: flex; align-items: flex-start; gap: 5px; }
        .sf-item-card .bulk-error i { margin-top: 1px; }
        .sf-item-card .remove-bulk-row {
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
            .sf-denom-toolbar { align-items: flex-start; flex-direction: row; }
            .sf-add-btn { flex:0 0 auto; }
            .sf-out-card .card-footer { justify-content: stretch !important; }
            .sf-back-btn, .sf-save-btn { flex: 1; }
        }
        @media (max-width: 1100px) {
            .sf-item-card { grid-template-columns:minmax(0,1fr) 82px 130px 36px; }
            .sf-item-product { grid-column:1 / 3; }
            .sf-item-stock-wrap { grid-column:3; }
            .sf-item-qty-wrap { grid-column:1 / 3; grid-row:2; }
            .sf-item-remove { grid-column:4; grid-row:1; }
            .sf-item-serial { grid-column:1 / -1; grid-row:3; }
            .sf-item-note { grid-column:1 / -1; grid-row:4; }
            .sf-item-note .sf-item-label { margin-bottom:6px; }
        }
        @media (max-width: 575.98px) {
            .sf-out-page .card-header,.sf-out-page .card-body,.sf-out-page .card-footer { padding-left:14px; padding-right:14px; }
            .sf-denom-toolbar { gap:8px; padding:12px; }
            .sf-denom-hint { max-width:230px; line-height:1.4; }
            .sf-add-btn { white-space:nowrap; padding:9px 10px!important; }
            .sf-item-list { padding:10px; }
            .sf-item-card { grid-template-columns:minmax(0,1fr) 78px 34px; gap:10px; padding:12px; }
            .sf-item-product { grid-column:1 / 3; }
            .sf-item-stock-wrap { grid-column:1; grid-row:2; }
            .sf-item-qty-wrap { grid-column:2; grid-row:2; }
            .sf-item-remove { grid-column:3; grid-row:1; }
            .sf-item-serial { grid-column:1 / -1; grid-row:3; }
            .sf-item-note { grid-column:1 / -1; grid-row:4; }
            .sf-serial-fields { gap:5px; grid-template-columns:minmax(0,1fr) 18px minmax(0,1fr); }
            .sf-serial-fields .form-control { min-width:0; padding:0 9px; }
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
                                                    value="{{ old('tgl', date('Y-m-d')) }}"
                                                    max="{{ date('Y-m-d') }}" required>
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

                                        {{-- OUTLET --}}
                                        <div class="sf-field">
                                            <div class="form-group mb-1">
                                                <label for="id_outlet"><i class="fas fa-store"></i>ID Outlet (Opsional)</label>
                                                <select name="id_outlet" id="id_outlet" class="form-control select2" disabled>
                                                    <option value="">-- Pilih Outlet --</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>

                                    <div id="bulk-entry">
                                        <div class="sf-denom-section">
                                            <div class="sf-denom-toolbar">
                                                <div>
                                                    <div class="sf-denom-title">Daftar Denom & Nomor Seri</div>
                                                <p class="sf-denom-hint">Isi SN awal. SN akhir opsional untuk rentang; kosongkan untuk 1 pcs. Qty dihitung otomatis.</p>
                                                </div>
                                                <button type="button" class="btn sf-add-btn" id="add-bulk-row"><i class="fas fa-plus mr-1"></i> Tambah Denom</button>
                                            </div>
                                            <div id="bulk-rows" class="sf-item-list" aria-live="polite"></div>
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
                const $outlet = $('#id_outlet');

                $sf.prop('disabled', true).empty().trigger('change');
                $outlet.prop('disabled', true).empty().append('<option value="">-- Pilih Outlet --</option>').trigger('change');

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

                $.post('{{ route('ajax.get-outlets-keluar') }}', { idtap, _token: '{{ csrf_token() }}' })
                    .done(res => {
                        let options = '<option value="">-- Pilih Outlet --</option>';
                        res.forEach(item => { options += `<option value="${item.id_outlet}">${item.id_outlet} · ${item.nama_outlet || item.id_outlet}</option>`; });
                        $outlet.html(options).prop('disabled', false).trigger('change');
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

            /* ================= SERIAL INPUT ================= */
            const bulkDenoms = @json($bulkDenomMetadata);
            let bulkIndex = 0;
            function bulkStarterPack(iddenom) {
                const denom = bulkDenoms.find(item => String(item.id) === String(iddenom));
                if (!denom) return false;
                const id = String(denom.id || '').trim().toUpperCase();
                const masterProduct = String(denom.product || '').trim().toUpperCase();
                if (['PV', 'SA'].includes(masterProduct)) return masterProduct === 'SA';
                return ['SA', 'SP'].includes(String(denom.product_group || '').trim().toUpperCase())
                    || ['SA', 'SEGEL'].includes(String(denom.kategori_inject || '').trim().toUpperCase())
                    || ['SA', 'SEGEL'].includes(String(denom.group_name || '').trim().toUpperCase())
                    || id.startsWith('SP');
            }
            function bulkSerialRangeQty(start, end, starterPack) {
                const pattern = starterPack ? /^0\d{15}$/ : /^\d{12}$/;
                if (!pattern.test(start || '') || !pattern.test(end || '')) return 0;
                const qty = BigInt(end) - BigInt(start) + 1n;
                return qty > 0n && qty <= 2000000n ? Number(qty) : 0;
            }
            function availableDenoms() {
                return bulkDenoms.filter(d => (parseInt(sfStockData[d.id]) || 0) > 0);
            }
            function denomOptions() {
                return availableDenoms().map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            }
            function refreshAvailableDenoms() {
                const options = denomOptions();
                $('.bulk-denom').each(function() {
                    $(this)
                        .html(`<option value="">Pilih / cari denom</option>${options}`)
                        .prop('disabled', !$('#idsf').val())
                        .val(null)
                        .trigger('change');
                });
                validateBulkRows();
            }
            function validateBulkRows() {
                const requested = {};
                let hasStockError = false;
                let hasSerialError = false;
                let hasIncompleteRow = false;

                $('#bulk-rows .sf-item-card').each(function() {
                    const $row = $(this);
                    const denom = $row.find('.bulk-denom').val();
                    const $qty = $row.find('.bulk-qty');
                    const $qtyDisplay = $row.find('.sf-qty-display');
                    const $error = $row.find('.bulk-error');
                    const $hint = $row.find('.sf-serial-hint');
                    const stock = parseInt(sfStockData[denom], 10) || 0;
                    const starterPack = bulkStarterPack(denom);
                    let qty = 0;
                    let error = '';

                    $qty.val('');
                    $qtyDisplay.text('—').addClass('is-empty');
                    $qty.removeClass('is-invalid').removeAttr('aria-invalid');
                    $error.removeClass('is-visible').empty();
                    $row.find('.bulk-serial-value, .bulk-sn-start, .bulk-sn-end').removeClass('is-invalid').removeAttr('aria-invalid');
                    $hint.removeClass('is-invalid is-valid').text('');

                    if (!denom) {
                        hasIncompleteRow = true;
                        return;
                    }

                    const start = String($row.find('.bulk-sn-start').val() || '');
                    const end = String($row.find('.bulk-sn-end').val() || '');
                    const expected = starterPack ? 'SN perdana tepat 16 digit, diawali 0.' : 'SN voucher tepat 12 digit.';
                    if (!start) error = `SN awal wajib diisi. ${expected}`;
                    else {
                        qty = bulkSerialRangeQty(start, end || start, starterPack);
                        if (!qty) error = `${expected} Rentang harus berurutan dan maksimal 2.000.000 SN.`;
                    }

                    if (error) {
                        hasSerialError = true;
                        $row.find('.bulk-sn-start, .bulk-sn-end').addClass('is-invalid').attr('aria-invalid', 'true');
                        $hint.text(error).addClass('is-invalid');
                    } else if (qty > 0) {
                        $qty.val(qty);
                        $qtyDisplay.text(qty.toLocaleString('id-ID')).removeClass('is-empty');
                        $hint.text(end ? `${qty.toLocaleString('id-ID')} SN terhitung dari rentang.` : '1 pcs terhitung dari SN awal.').addClass('is-valid');
                    }

                    requested[denom] = (requested[denom] || 0) + qty;
                    if (qty > stock || requested[denom] > stock) {
                        hasStockError = true;
                        $qty.addClass('is-invalid').attr('aria-invalid', 'true');
                        $error
                            .html(`<i class="fas fa-exclamation-circle"></i><span>Qty tidak boleh melebihi stok tersedia (${stock.toLocaleString('id-ID')}).</span>`)
                            .addClass('is-visible');
                    }
                });

                const canSave = $('#idsf').val() && Object.keys(requested).length > 0 && !hasIncompleteRow && !hasStockError && !hasSerialError;
                $('#submitBtn').prop('disabled', !canSave);
                return Boolean(canSave);
            }
            function addBulkRow() {
                const index = bulkIndex++;
                const options = denomOptions();
                const disabled = $('#idsf').val() ? '' : 'disabled';
                const row = $(`<div class="sf-item-card">
                    <div class="sf-item-product"><label class="sf-item-label">Produk</label><select name="items[${index}][iddenom]" class="form-control form-control-sm bulk-denom" required ${disabled}><option value="">Pilih / cari denom</option>${options}</select></div>
                    <div class="sf-item-stock-wrap"><label class="sf-item-label">Stok</label><div class="sf-item-stock">—</div></div>
                    <div class="sf-item-qty-wrap"><label class="sf-item-label">Qty otomatis</label><div class="sf-qty-display is-empty">—</div><input type="hidden" name="items[${index}][qty]" class="bulk-qty"><div class="bulk-error" role="alert" aria-live="polite"></div></div>
                    <div class="sf-item-serial"><div class="sf-serial-top"><label class="sf-item-label mb-0">Identitas penjualan</label></div>
                        <input type="hidden" name="items[${index}][serial_mode]" value="range">
                        <input type="hidden" name="items[${index}][identifier_type]" value="sn">
                        <div class="sf-serial-fields"><div class="sf-serial-field"><label class="sf-serial-field-label" for="sn-start-${index}">SN awal</label><input type="text" id="sn-start-${index}" name="items[${index}][sn_start]" class="form-control bulk-sn-start" inputmode="numeric" autocomplete="off" aria-label="SN awal" placeholder="SN awal"></div><span class="sf-serial-separator" aria-hidden="true">–</span><div class="sf-serial-field"><label class="sf-serial-field-label" for="sn-end-${index}">SN akhir (opsional)</label><input type="text" id="sn-end-${index}" name="items[${index}][sn_end]" class="form-control bulk-sn-end" inputmode="numeric" autocomplete="off" aria-label="SN akhir (opsional)" placeholder="SN akhir · kosongkan untuk 1 pcs"></div></div>
                        <small class="sf-serial-hint" role="status" aria-live="polite">SN awal wajib. Isi SN akhir untuk rentang; kosongkan untuk 1 pcs.</small></div>
                    <div class="sf-item-remove"><button type="button" class="btn btn-link text-danger remove-bulk-row" aria-label="Hapus baris">×</button></div>
                    <div class="sf-item-note"><label class="sf-item-label" for="item-note-${index}">Keterangan <span class="text-muted font-weight-normal">(opsional)</span></label><input type="text" id="item-note-${index}" name="items[${index}][tambahanket]" class="form-control" maxlength="255" placeholder="Tambahkan catatan"></div>
                </div>`);
                $('#bulk-rows').append(row);
                row.find('.bulk-denom').select2({
                    placeholder: 'Pilih / cari denom',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('.sf-out-page')
                });
            }
            $('#add-bulk-row').on('click', addBulkRow);
            $('#bulk-rows').on('click', '.remove-bulk-row', function() {
                if ($('#bulk-rows .sf-item-card').length > 1) {
                    $(this).closest('.sf-item-card').remove();
                    validateBulkRows();
                }
            }).on('change', '.bulk-denom', function() {
                const stock = parseInt(sfStockData[$(this).val()]) || 0;
                const $row = $(this).closest('.sf-item-card');
                $row.find('.sf-item-stock').text(stock.toLocaleString('id-ID'));
                $row.find('.bulk-sn-start, .bulk-sn-end').val('');
                validateBulkRows();
            }).on('input change', '.bulk-sn-start, .bulk-sn-end', function() {
                validateBulkRows();
            });
            let submitting = false;
            $('#mainForm').on('submit', function(e) {
                if (submitting) return false;
                if (!$('#idsf').val() || !validateBulkRows()) {
                    e.preventDefault();
                    Swal.fire({ icon: 'error', title: 'Periksa Daftar Denom', text: 'Pilih denom dan isi SN awal dengan format yang benar. SN akhir opsional untuk rentang; Qty dihitung otomatis dan tidak boleh melebihi stok.' });
                    return false;
                }
                submitting = true;
                $('#submitBtn').prop('disabled', true).text('Menyimpan...');
            });
            // Load outlets immediately when the TAP is preselected (e.g. admin Dumai).
            $('#kategoritap').trigger('change');
            if (!$('#bulk-rows .sf-item-card').length) addBulkRow();

        });

    </script>
@endpush
