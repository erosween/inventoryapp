{{-- Input penyaluran stok TAP ke SF dengan serial number wajib. --}}
@extends('layout.layout')

@section('content')
    <style>
        .sf-in-page { --sf-primary:#4f46e5; --sf-ink:#172033; --sf-muted:#718096; --sf-line:#e7eaf1; background:radial-gradient(circle at 94% 6%,rgba(99,102,241,.09),transparent 26rem),#f7f8fc; min-height:calc(100vh - 62px); }
        .sf-in-page .content { padding:0; }
        .sf-in-page .page-inner { max-width:1184px; margin:auto; padding:22px 32px 30px; }
        .sf-in-heading { margin-bottom:14px; }
        .sf-in-eyebrow { display:inline-flex; align-items:center; gap:8px; margin-bottom:4px; color:var(--sf-primary); font-size:11px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
        .sf-in-eyebrow:before { content:''; width:7px; height:7px; border-radius:50%; background:var(--sf-primary); box-shadow:0 0 0 5px rgba(79,70,229,.1); }
        .sf-in-title { margin:0; color:var(--sf-ink); font-size:25px; font-weight:700; }
        .sf-in-subtitle { margin:3px 0 0; color:var(--sf-muted); font-size:12px; }
        .sf-in-card { overflow:hidden; border:1px solid rgba(226,232,240,.9)!important; border-radius:18px!important; background:rgba(255,255,255,.96); box-shadow:0 22px 60px rgba(30,41,59,.09)!important; }
        .sf-in-card .card-header { display:flex; align-items:center; gap:14px; padding:14px 22px; border-bottom:1px solid var(--sf-line); background:#fff; }
        .sf-in-icon { display:grid; flex:0 0 38px; width:38px; height:38px; place-items:center; border-radius:12px; background:linear-gradient(145deg,#6259e8,#4338ca); color:#fff; }
        .sf-in-card-title { margin:0 0 3px; color:var(--sf-ink); font-size:15px; font-weight:700; }
        .sf-in-card-copy { color:var(--sf-muted); font-size:11px; }
        .sf-in-card .card-body { padding:17px 22px 18px; }
        .sf-in-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; padding-bottom:14px; }
        .sf-in-field label,.sf-in-item-label { display:block; margin-bottom:6px; color:#364152; font-size:12px; font-weight:700; }
        .sf-in-field label i { margin-right:6px; color:#9299aa; }
        .sf-in-field .form-control,.sf-in-item .form-control,.sf-in-page .select2-container .select2-selection--single { height:40px!important; border:1px solid #dfe3eb!important; border-radius:10px!important; background:#fff; color:#283245; font-size:13px; box-shadow:none!important; }
        .sf-in-field .form-control { padding:0 14px; }
        .sf-in-page .select2-container .select2-selection--single { padding:5px 12px!important; }
        .sf-in-page .select2-container--default .select2-selection--single .select2-selection__rendered { line-height:28px; }
        .sf-in-page .select2-container--default .select2-selection--single .select2-selection__arrow { height:38px; }
        .sf-in-page .select2-container--disabled .select2-selection--single,.sf-in-field .form-control:disabled { background:#f4f5f8!important; color:#9ca3af; }
        .sf-in-denoms { overflow:hidden; border:1px solid var(--sf-line); border-radius:14px; background:#fff; }
        .sf-in-toolbar { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:12px 16px; border-bottom:1px solid var(--sf-line); background:#fbfbfd; }
        .sf-in-toolbar strong { display:block; margin-bottom:3px; color:var(--sf-ink); font-size:13px; }
        .sf-in-hint { margin:0; color:var(--sf-muted); font-size:11px; }
        .sf-in-add { padding:9px 14px!important; border:1px solid #d9d7fb!important; border-radius:9px!important; background:#f1f0ff!important; color:#3730a3!important; font-size:12px!important; font-weight:700; white-space:nowrap; }
        .sf-in-list { display:grid; gap:12px; padding:14px; }
        .sf-in-item { position:relative; display:grid; grid-template-columns:minmax(160px,1.1fr) 90px 110px minmax(260px,1.8fr); align-items:start; gap:12px; padding:14px 54px 14px 14px; border:1px solid #e8ebf2; border-radius:13px; background:#fff; }
        .sf-in-item>* { min-width:0; }
        .sf-in-item-label { color:#788296; font-size:10px; letter-spacing:.07em; text-transform:uppercase; }
        .sf-in-stock { display:flex; align-items:center; min-height:40px; color:#39445a; font-size:13px; font-weight:750; font-variant-numeric:tabular-nums; }
        .sf-in-item .form-control,.sf-in-item .select2-container .select2-selection--single { height:40px!important; font-size:12px; }
        .sf-in-qty { display:flex; align-items:center; justify-content:center; min-height:40px; border:1px solid #e3e7ef; border-radius:9px; background:#f8f9fc; color:#334155; font-size:14px; font-weight:800; font-variant-numeric:tabular-nums; }
        .sf-in-qty.is-empty { color:#9aa3b2; }
        .sf-in-serial-fields { display:grid; grid-template-columns:minmax(0,1fr) 24px minmax(0,1fr); align-items:start; gap:8px; }
        .sf-in-serial-fields>div { min-width:0; }
        .sf-in-separator { display:flex; align-items:center; justify-content:center; height:40px; color:#8b95a5; font-size:12px; font-weight:800; }
        .sf-in-serial-hint { display:block; min-height:15px; margin-top:6px; color:#718096; font-size:10px; line-height:1.4; }
        .sf-in-serial-hint.is-invalid,.sf-in-error { color:#d11a3f; }
        .sf-in-serial-hint.is-valid { color:#059669; }
        .sf-in-remove { position:absolute; top:14px; right:14px; }
        .sf-in-remove button { width:32px; height:32px; padding:0!important; border:1px solid #ffe0e5; border-radius:8px; background:#fff1f2!important; color:#e11d48!important; font-size:22px; line-height:1; }
        .sf-in-error { display:none; margin-top:7px; font-size:10px; font-weight:600; }
        .sf-in-error.is-visible { display:block; }
        .sf-in-card .card-footer { padding:12px 22px; border-top:1px solid var(--sf-line); background:#fbfbfd; }
        .sf-in-btn { min-width:100px; padding:9px 16px!important; border-radius:9px!important; font-size:11px; font-weight:700; }
        .sf-in-back { border:1px solid #dfe3eb!important; background:#fff!important; color:#536076!important; }
        .sf-in-save { border:0!important; background:linear-gradient(135deg,#5b54e8,#4338ca)!important; box-shadow:0 9px 20px rgba(79,70,229,.24); }
        @media(max-width:991.98px) { .sf-in-page .page-inner{padding:28px 22px 40px}.sf-in-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.sf-in-item{grid-template-columns:minmax(0,1fr) 82px 130px 36px}.sf-in-product{grid-column:1/3}.sf-in-stock-wrap{grid-column:3}.sf-in-qty-wrap{grid-column:1/3;grid-row:2}.sf-in-serial{grid-column:1/-1;grid-row:3} }
        @media(max-width:575.98px) { .sf-in-page .page-inner{padding:22px 14px 32px}.sf-in-grid{grid-template-columns:1fr;gap:16px}.sf-in-card .card-header,.sf-in-card .card-body,.sf-in-card .card-footer{padding-left:14px;padding-right:14px}.sf-in-toolbar{gap:8px;padding:12px}.sf-in-hint{max-width:230px;line-height:1.4}.sf-in-list{padding:10px}.sf-in-item{grid-template-columns:minmax(0,1fr) 78px 34px;gap:10px;padding:12px}.sf-in-product{grid-column:1/3}.sf-in-stock-wrap{grid-column:1;grid-row:2}.sf-in-qty-wrap{grid-column:2;grid-row:2}.sf-in-serial{grid-column:1/-1;grid-row:3}.sf-in-item{padding-right:48px}.sf-in-serial-fields{gap:5px;grid-template-columns:minmax(0,1fr) 18px minmax(0,1fr)}.sf-in-serial-fields .form-control{min-width:0;padding:0 9px}.sf-in-card .card-footer{justify-content:stretch!important}.sf-in-btn{flex:1} }
    </style>

    <div class="main-panel sf-in-page">
        <div class="content"><div class="page-inner">
            <div class="sf-in-heading">
                <div class="sf-in-eyebrow">Transaksi Sales Force</div>
                <h1 class="sf-in-title">Input Stok Masuk SF</h1>
                <p class="sf-in-subtitle">Catat serial number saat stok disalurkan dari TAP ke Sales Force.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger"><strong>Data belum bisa disimpan.</strong><ul class="mb-0 mt-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <div class="card sf-in-card">
                <div class="card-header"><div class="sf-in-icon"><i class="fas fa-dolly-flatbed"></i></div><div><div class="sf-in-card-title">Detail Distribusi</div><div class="sf-in-card-copy">Pilih TAP, Sales Force, denom, lalu masukkan SN awal dan SN akhir jika berupa rentang.</div></div></div>
                <form action="{{ url('sf-masuk') }}" method="POST" id="formSfMasuk">
                    @csrf
                    <div class="card-body">
                        <div class="sf-in-grid">
                            <div class="sf-in-field"><label for="date"><i class="far fa-calendar-alt"></i>Tanggal Transaksi</label><input type="date" id="date" name="tgl" class="form-control" value="{{ old('tgl', now('Asia/Jakarta')->toDateString()) }}" max="{{ now('Asia/Jakarta')->addDay()->toDateString() }}" required></div>
                            <div class="sf-in-field"><label for="kategoritap"><i class="fas fa-building"></i>Lokasi TAP</label><select name="idtap" id="kategoritap" class="form-control select2" required><option></option>@foreach ($data as $row)<option value="{{ $row->idtap }}" {{ old('idtap') === $row->idtap ? 'selected' : '' }}>{{ $row->idtap }}</option>@endforeach</select></div>
                            <div class="sf-in-field"><label for="idsf"><i class="fas fa-user"></i>Sales Force</label><select name="idsf" id="idsf" class="form-control select2" disabled required><option></option></select></div>
                        </div>
                        <div class="sf-in-denoms">
                            <div class="sf-in-toolbar"><div><strong>Daftar Denom & Nomor Seri</strong><p class="sf-in-hint">SN PV/voucher wajib 12 digit. SN SA/perdana wajib 16 digit dan diawali 0. SN akhir opsional untuk satuan; Qty dihitung otomatis.</p></div><button type="button" class="btn sf-in-add" id="add-bulk-row"><i class="fas fa-plus mr-1"></i>Tambah Denom</button></div>
                            <div id="bulk-rows" class="sf-in-list" aria-live="polite"></div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <a href="{{ url('sf-masuk') }}" class="btn sf-in-btn sf-in-back mr-2"><i class="fas fa-arrow-left mr-1"></i>Kembali</a>
                        <button type="submit" id="submitBtn" class="btn btn-primary sf-in-btn sf-in-save" disabled><i class="far fa-save mr-1"></i>Simpan Data</button>
                    </div>
                </form>
            </div>
        </div></div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const bulkDenoms = @json($bulkDenomMetadata);
    let tapStockData = {};
    let bulkIndex = 0;

    $('.select2').each(function() {
        $(this).select2({ placeholder:'Pilih / Cari…', allowClear:true, width:'100%', dropdownParent:$(this).closest('.card-body') });
    });
    $(document).on('select2:open', function() { setTimeout(() => document.querySelector('.select2-search__field')?.focus(), 50); });

    function isStarterPack(iddenom) {
        const denom = bulkDenoms.find(item => String(item.id) === String(iddenom));
        if (!denom) return false;
        const id = String(denom.id || '').trim().toUpperCase();
        const product = String(denom.product || '').trim().toUpperCase();
        if (['PV','SA'].includes(product)) return product === 'SA';
        return ['SA','SP'].includes(String(denom.product_group || '').trim().toUpperCase())
            || String(denom.kategori_inject || '').trim().toUpperCase() === 'SA'
            || String(denom.group_name || '').trim().toUpperCase() === 'SA'
            || id.startsWith('SP');
    }
    function rangeQty(start, end, starterPack) {
        const pattern = starterPack ? /^0\d{15}$/ : /^\d{12}$/;
        if (!pattern.test(start || '') || !pattern.test(end || '')) return 0;
        const qty = BigInt(end) - BigInt(start) + 1n;
        return qty > 0n && qty <= 2000000n ? Number(qty) : 0;
    }
    function rangesOverlap(a, b) {
        return a.start.length === b.start.length && a.start <= b.end && a.end >= b.start;
    }
    function denomOptions() {
        return bulkDenoms.filter(d => (parseInt(tapStockData[d.id], 10) || 0) > 0)
            .map(d => `<option value="${d.id}">${d.name}</option>`).join('');
    }
    function refreshDenomOptions() {
        const options = denomOptions();
        $('.bulk-denom').each(function() {
            $(this).html(`<option value="">Pilih / cari denom</option>${options}`).prop('disabled', !$('#idsf').val()).val(null).trigger('change');
        });
        validateRows();
    }
    function validateRows() {
        const requested = {};
        const serialRanges = [];
        let incomplete = false, serialError = false, stockError = false, duplicateError = false;

        $('#bulk-rows .sf-in-item').each(function() {
            const $row = $(this), denom = $row.find('.bulk-denom').val();
            const $qty = $row.find('.bulk-qty'), $qtyView = $row.find('.sf-in-qty');
            const $hint = $row.find('.sf-in-serial-hint'), $error = $row.find('.sf-in-error');
            const mode = $row.find('.bulk-serial-mode').val() || 'range';
            const start = String($row.find('.bulk-sn-start').val() || '');
            let end = String($row.find('.bulk-sn-end').val() || '');
            const stock = parseInt(tapStockData[denom], 10) || 0;
            let qty = 0, message = '';

            $qty.val(''); $qtyView.text('—').addClass('is-empty');
            $hint.removeClass('is-invalid is-valid').text(''); $error.removeClass('is-visible').text('');
            $row.find('.bulk-sn-start,.bulk-sn-end').removeClass('is-invalid').removeAttr('aria-invalid');
            if (!denom) { incomplete = true; return; }

            const starterPack = isStarterPack(denom);
            const snLength = starterPack ? 16 : 12;
            $row.find('.bulk-sn-start,.bulk-sn-end').attr('maxlength',snLength);
            const expected = starterPack ? 'SN perdana tepat 16 digit, diawali 0.' : 'SN voucher tepat 12 digit.';
            if (mode === 'broken') {
                qty = parseInt($row.find('.bulk-manual-qty').val(), 10) || 0;
                if (qty < 1 || qty > 2000000) message = 'Qty SN pecah harus antara 1 dan 2.000.000.';
            } else if (!start) message = `SN awal wajib diisi. ${expected}`;
            else {
                if (!end) end = start;
                qty = rangeQty(start, end, starterPack);
                if (!qty) message = `${expected} Rentang harus berurutan dan maksimal 2.000.000 SN.`;
            }
            if (message) {
                serialError = true;
                if (mode !== 'broken') $row.find('.bulk-sn-start,.bulk-sn-end').addClass('is-invalid').attr('aria-invalid','true');
                $hint.text(message).addClass('is-invalid');
            } else {
                $qty.val(qty); $qtyView.text(qty.toLocaleString('id-ID')).removeClass('is-empty');
                $hint.text(mode === 'broken' ? 'SN tidak dicatat satu per satu.' : ($row.find('.bulk-sn-end').val() ? `${qty.toLocaleString('id-ID')} SN terhitung dari rentang.` : '1 pcs terhitung dari SN awal.')).addClass('is-valid');
                if (mode !== 'broken') {
                    const serial = { start, end, denom };
                    if (serialRanges.some(existing => rangesOverlap(existing, serial))) {
                        duplicateError = true;
                        $hint.text('Rentang SN ini bertabrakan dengan baris lain.').removeClass('is-valid').addClass('is-invalid');
                        $row.find('.bulk-sn-start,.bulk-sn-end').addClass('is-invalid').attr('aria-invalid','true');
                    }
                    serialRanges.push(serial);
                }
            }

            requested[denom] = (requested[denom] || 0) + qty;
            if (qty > stock || requested[denom] > stock) {
                stockError = true;
                $qtyView.addClass('is-invalid');
                $error.text(`Qty total tidak boleh melebihi stok TAP (${stock.toLocaleString('id-ID')}).`).addClass('is-visible');
            }
        });

        const canSave = Boolean($('#idsf').val()) && Object.keys(requested).length > 0 && !incomplete && !serialError && !stockError && !duplicateError;
        $('#submitBtn').prop('disabled', !canSave);
        return canSave;
    }
    function addRow() {
        const index = bulkIndex++;
        const options = denomOptions();
        const disabled = $('#idsf').val() ? '' : 'disabled';
        const row = $(`<div class="sf-in-item">
            <div class="sf-in-product"><label class="sf-in-item-label">Produk / Denom</label><select name="items[${index}][iddenom]" class="form-control bulk-denom" required ${disabled}><option value="">Pilih / cari denom</option>${options}</select></div>
            <div class="sf-in-stock-wrap"><label class="sf-in-item-label">Stok TAP</label><div class="sf-in-stock">—</div></div>
            <div class="sf-in-qty-wrap"><label class="sf-in-item-label qty-label">Qty otomatis</label><div class="sf-in-qty is-empty">—</div><input type="hidden" name="items[${index}][qty]" class="bulk-qty"><input type="hidden" name="items[${index}][serial_mode]" class="bulk-serial-mode" value="range"><input type="number" class="form-control bulk-manual-qty d-none" min="1" max="2000000" placeholder="Qty" inputmode="numeric"><div class="sf-in-error" role="alert"></div></div>
            <div class="sf-in-serial"><label class="sf-in-item-label">Cara input</label><div class="serial-mode-choices mb-2"><button type="button" class="btn btn-sm btn-outline-primary active" data-mode="range">Massal</button> <button type="button" class="btn btn-sm btn-outline-primary" data-mode="broken">SN Pecah</button></div><div class="serial-fields"><div class="sf-in-serial-fields"><div><label class="sr-only" for="sn-start-${index}">SN awal</label><input type="text" id="sn-start-${index}" name="items[${index}][sn_start]" class="form-control bulk-sn-start" inputmode="numeric" autocomplete="off" placeholder="SN awal" required></div><span class="sf-in-separator" aria-hidden="true">–</span><div><label class="sr-only" for="sn-end-${index}">SN akhir (opsional)</label><input type="text" id="sn-end-${index}" name="items[${index}][sn_end]" class="form-control bulk-sn-end" inputmode="numeric" autocomplete="off" placeholder="SN akhir · kosong untuk satuan"></div></div></div><small class="sf-in-serial-hint" role="status" aria-live="polite">SN awal wajib. Isi SN akhir untuk rentang; kosongkan untuk 1 pcs.</small></div>
            <div class="sf-in-remove"><button type="button" class="remove-bulk-row" aria-label="Hapus baris">×</button></div>
        </div>`);
        $('#bulk-rows').append(row);
        row.find('.bulk-denom').select2({ placeholder:'Pilih / cari denom', allowClear:true, width:'100%', dropdownParent:$('.sf-in-page') });
    }

    $('#kategoritap').on('change', function() {
        const idtap = $(this).val();
        const $sf = $('#idsf');
        $sf.prop('disabled',true).empty().trigger('change');
        tapStockData = {};
        $('.bulk-denom').html('<option value="">Pilih / cari denom</option>').prop('disabled',true).val(null).trigger('change');
        $('#submitBtn').prop('disabled',true);
        if (!idtap) return;

        $.post('{{ route('ajax.get-sf-masuk') }}', { idtap, _token:'{{ csrf_token() }}' }).done(res => {
            let options = '<option value="">-- Pilih SF --</option>';
            res.forEach(item => { options += `<option value="${item.idsf}">${item.namasf}</option>`; });
            $sf.html(options).prop('disabled',false).trigger('change');
        });
        $.post('{{ route('ajax.get-all-stock-tap-sfmasuk') }}', { idtap, _token:'{{ csrf_token() }}' }).done(res => {
            tapStockData = res;
            refreshDenomOptions();
        });
    });
    $('#idsf').on('change', function() {
        $('.bulk-denom').prop('disabled',!$(this).val());
        validateRows();
    });
    $('#add-bulk-row').on('click', addRow);
    $('#bulk-rows').on('click','.remove-bulk-row',function() {
        if ($('#bulk-rows .sf-in-item').length > 1) { $(this).closest('.sf-in-item').remove(); validateRows(); }
    }).on('click','.serial-mode-choices button',function() {
        const $row=$(this).closest('.sf-in-item'), mode=$(this).data('mode');
        $row.find('.bulk-serial-mode').val(mode);
        $row.find('.serial-mode-choices button').removeClass('active'); $(this).addClass('active');
        $row.find('.serial-fields').toggleClass('d-none',mode==='broken');
        $row.find('.bulk-sn-start').prop('required',mode!=='broken');
        $row.find('.bulk-manual-qty').toggleClass('d-none',mode!=='broken');
        $row.find('.sf-in-qty').toggleClass('d-none',mode==='broken');
        $row.find('.qty-label').text(mode==='broken'?'Qty SN Pecah':'Qty otomatis');
        validateRows();
    }).on('change','.bulk-denom',function() {
        const stock = parseInt(tapStockData[$(this).val()],10) || 0;
        const $row = $(this).closest('.sf-in-item');
        $row.find('.sf-in-stock').text(stock.toLocaleString('id-ID'));
        $row.find('.bulk-sn-start,.bulk-sn-end').val('');
        validateRows();
    }).on('input','.bulk-sn-start,.bulk-sn-end',function() {
        const denom = $(this).closest('.sf-in-item').find('.bulk-denom').val();
        const maxDigits = isStarterPack(denom) ? 16 : 12;
        this.value = String(this.value || '').replace(/\D/g,'').slice(0,maxDigits);
        validateRows();
    }).on('change','.bulk-sn-start,.bulk-sn-end',validateRows);
    $('#bulk-rows').on('input','.bulk-manual-qty',function(){
        if ((parseInt(this.value,10)||0)>2000000) this.value='2000000';
        validateRows();
    });

    let submitting = false;
    $('#formSfMasuk').on('submit',function(e) {
        if (submitting) { e.preventDefault(); return; }
        if (!$('#kategoritap').val() || !$('#idsf').val() || !validateRows()) {
            e.preventDefault();
            Swal.fire({ icon:'error', title:'Periksa Daftar Denom', text:'Pilih TAP dan Sales Force, isi SN dengan format produk yang benar, lalu pastikan Qty sesuai rentang SN dan stok TAP.' });
            return;
        }
        submitting = true;
        $('#submitBtn').prop('disabled',true).text('Menyimpan...');
    });

    if (!$('#bulk-rows .sf-in-item').length) addRow();
    if ($('#kategoritap').val()) $('#kategoritap').trigger('change');
});
</script>
@endpush
