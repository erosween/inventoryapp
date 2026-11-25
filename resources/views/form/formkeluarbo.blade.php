{{--
    Form pengembalian barang ke BO. Penyimpanan transaksi ditangani BOController.
--}}@extends('layout.layout')

@section('content')
    <style>
        .bo-denom-section{overflow:hidden;border:1px solid #e1e6ef;border-radius:14px;background:#fff}.bo-denom-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;border-bottom:1px solid #e1e6ef;background:#fbfbfd}.bo-denom-toolbar h5{margin:0 0 3px;color:#27354a;font-size:13px;font-weight:700}.bo-denom-toolbar small{color:#718096;font-size:11px}.bo-add-btn{padding:9px 14px!important;border:1px solid #d9d7fb!important;border-radius:9px!important;background:#f1f0ff!important;color:#4338ca!important;font-size:12px!important;font-weight:700}.bo-items-list{display:grid;gap:12px;padding:14px}.bo-item-card{position:relative;display:grid;grid-template-columns:minmax(160px,1.1fr) 90px 110px minmax(280px,1.8fr) minmax(160px,1fr);align-items:start;gap:12px;padding:14px 54px 14px 14px;border:1px solid #e8ebf2;border-radius:13px;background:#fff}.bo-item-card>*{min-width:0}.bo-item-label{display:block;margin-bottom:6px;color:#788296;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase}.bo-item-stock{display:flex;align-items:center;min-height:40px;color:#39445a;font-size:13px;font-weight:700;font-variant-numeric:tabular-nums}.bo-item-card .form-control,.bo-item-card .select2-container .select2-selection--single{height:40px!important;border-radius:9px!important;font-size:12px}.bo-item-card .select2-container .select2-selection--single{padding:5px 8px!important}.bo-qty-display{display:flex;align-items:center;justify-content:center;min-height:40px;border:1px solid #e3e7ef;border-radius:9px;background:#f8f9fc;color:#334155;font-size:14px;font-weight:700;font-variant-numeric:tabular-nums}.bo-qty-display.is-empty{color:#9aa3b2}.bo-serial-choices{display:flex;gap:6px;margin-bottom:8px}.bo-serial-choices .btn{flex:1;padding:7px 8px;font-size:11px}.bo-serial-fields{display:grid;grid-template-columns:minmax(0,1fr) 20px minmax(0,1fr);align-items:start;gap:6px}.bo-serial-separator{display:flex;align-items:center;justify-content:center;height:40px;color:#8b95a5}.bo-serial-hint{display:block;min-height:15px;margin-top:6px;color:#718096;font-size:10px;line-height:1.4}.bo-serial-hint.text-danger{color:#d11a3f!important}.bo-serial-hint.text-success{color:#059669!important}.bo-item-note .form-control{min-height:40px}.bo-item-remove{position:absolute;top:12px;right:12px}.bo-item-remove .remove-bo-item{width:32px;height:32px;padding:0;border-radius:8px;font-size:20px;line-height:1}.bo-item-card .is-invalid{border-color:#e11d48!important;background:#fff7f8}.bo-denom-section .select2-container{width:100%!important}
        @media(max-width:1100px){.bo-item-card{grid-template-columns:minmax(160px,1fr) 90px 110px minmax(260px,1.5fr);}.bo-item-note{grid-column:1/4}.bo-item-remove{grid-column:4;grid-row:1;justify-self:end}}
        @media(max-width:700px){.bo-item-card{grid-template-columns:minmax(0,1fr) minmax(100px,.6fr) minmax(100px,.7fr);padding:42px 12px 12px}.bo-item-serial{grid-column:1/4}.bo-item-note{grid-column:1/4}.bo-item-remove{position:absolute;top:8px;right:8px}.bo-denom-toolbar{align-items:flex-start}.bo-add-btn{white-space:nowrap}}
    </style>
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

                {{-- HEADER --}}
                <div class="page-header">
                    <h4 class="page-title">Input Stok Keluar BO</h4>
                </div>

                <div class="row">
                    <div class="col-12">

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Form Keluar BO</strong>
                                    <div class="text-muted small">
                                    Pilih Massal untuk rentang SN atau SN Pecah untuk input qty manual. Keduanya tetap dicek terhadap stok BO.
                                </div>
                            </div>

                            <form action="{{ route('bo.store') }}" method="POST" id="formKeluarBO">
                                @csrf

                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6"><div class="form-group mb-2"><label>Tanggal</label><input type="date" name="tgl" id="date" class="form-control" value="{{ date('Y-m-d') }}" required></div></div>
                                        <div class="col-md-6"><div class="form-group mb-2"><label>BO (Pengirim)</label><select name="pengirim" id="kategorisegelmasuk" class="form-control select2" required><option></option>@foreach ($data as $row)<option value="{{ $row->namabo }}">{{ $row->namabo }}</option>@endforeach</select></div></div>
                                        <div class="col-md-6"><div class="form-group mb-1"><label>TAP (Penerima)</label><select name="penerima" id="tappenerima" class="form-control select2" required><option></option></select></div></div>
                                        <div class="col-md-12 mt-3">
                                            <div class="bo-denom-section"><div class="bo-denom-toolbar"><div><h5>Daftar Denom & Nomor Seri</h5><small>Qty Cash dihitung dari rentang SN atau isi manual untuk SN Pecah. Stok tersedia mengikuti stok BO.</small></div><button type="button" class="btn bo-add-btn" id="add-bo-item"><i class="fas fa-plus mr-1"></i>Tambah Denom</button></div><div id="bo-items" class="bo-items-list"></div></div>
                                            <div id="bo-batch-error" class="text-danger small mt-2" role="alert" aria-live="polite"></div>
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
            const packages = @json($packages);
            const productByDenom = @json($productByDenom);
            let rowIndex = 0;
            let submitting = false;
            const stockCache = {};

            function escapeHtml(value) {
                return String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
            }
            function initSelect2($select) {
                $select.select2({ placeholder: 'Pilih / Cari…', width: '100%', allowClear: true, dropdownParent: $select.closest('.card-body') });
            }
            function serialRangeQty(start, end) {
                if (!/^\d+$/.test(start) || !/^\d+$/.test(end) || start.length !== end.length || end < start) return 0;
                const count = BigInt(end) - BigInt(start) + 1n;
                return count > 0n && count <= 2000000n ? Number(count) : 0;
            }
            function addBoItem() {
                const index = rowIndex++;
                const options = Object.entries(packages).map(([id, label]) => `<option value="${escapeHtml(id)}" data-product="${productByDenom[id]}">${escapeHtml(label)}</option>`).join('');
                const row = $(`<div class="bo-item-card">
                    <div class="bo-item-product"><label class="bo-item-label">Produk</label><select name="items[${index}][iddenom]" class="form-control bo-denom" required><option value="">Pilih / cari denom</option>${options}</select></div>
                    <div class="bo-item-stock-wrap"><label class="bo-item-label">Stok tersedia</label><div class="bo-item-stock">—</div></div>
                    <div class="bo-item-qty-wrap"><label class="bo-item-label bo-qty-label">Qty otomatis</label><div class="bo-qty-display is-empty">—</div><input type="hidden" name="items[${index}][qty]" class="bo-qty"><input type="number" class="form-control bo-manual-qty d-none" min="1" max="2000000" placeholder="Qty" inputmode="numeric"><div class="bo-row-error small text-danger" role="alert" aria-live="polite"></div></div>
                    <div class="bo-item-serial"><label class="bo-item-label">Identitas penjualan</label><input type="hidden" name="items[${index}][serial_mode]" class="bo-serial-mode" value="range">
                        <div class="bo-serial-choices"><button type="button" class="btn btn-sm btn-outline-primary active bo-serial-choice" data-mode="range">Massal</button><button type="button" class="btn btn-sm btn-outline-primary bo-serial-choice" data-mode="broken">SN Pecah</button></div>
                        <div class="bo-serial-fields"><input type="text" name="items[${index}][sn_start]" class="form-control bo-sn-start" inputmode="numeric" autocomplete="off" placeholder="SN awal" required><span class="bo-serial-separator">–</span><input type="text" name="items[${index}][sn_end]" class="form-control bo-sn-end" inputmode="numeric" autocomplete="off" placeholder="SN akhir · kosongkan untuk 1 pcs"></div>
                        <small class="bo-serial-hint" aria-live="polite">SN awal wajib. Isi SN akhir untuk rentang; kosongkan untuk 1 pcs.</small></div>
                    <div class="bo-item-note"><label class="bo-item-label">Keterangan (opsional)</label><input type="text" name="items[${index}][tambahanket]" class="form-control" maxlength="500" placeholder="Tambahkan catatan"></div>
                    <div class="bo-item-remove"><button type="button" class="btn btn-link text-danger remove-bo-item" aria-label="Hapus baris">×</button></div>
                </div>`);
                $('#bo-items').append(row);
                initSelect2(row.find('.bo-denom'));
                row.find('.bo-denom').on('change', function() {
                    const denom = $(this).val();
                    const $item = $(this).closest('.bo-item-card');
                    $item.find('.bo-sn-start, .bo-sn-end').val('');
                    $item.find('.bo-item-stock').text('—');
                    if (denom) loadBoStock(denom, $item);
                    validateBoItems();
                });
                validateBoItems();
            }
            function loadBoStock(denom, $row) {
                const pengirim = $('#kategorisegelmasuk').val();
                if (!pengirim) return;
                const key = `${pengirim}|${denom}`;
                if (Object.prototype.hasOwnProperty.call(stockCache, key)) {
                    $row.find('.bo-item-stock').text(stockCache[key].toLocaleString('id-ID'));
                    validateBoItems();
                    return;
                }
                $row.find('.bo-item-stock').text('Memuat…');
                $.post('{{ route('bo.stock') }}', { _token: '{{ csrf_token() }}', pengirim, iddenom: denom })
                    .done(res => {
                        stockCache[key] = Math.max(0, parseInt(res.stock, 10) || 0);
                        $(`#bo-items .bo-item-card`).each(function() {
                            if ($(this).find('.bo-denom').val() === denom && $('#kategorisegelmasuk').val() === pengirim) {
                                $(this).find('.bo-item-stock').text(stockCache[key].toLocaleString('id-ID'));
                            }
                        });
                        validateBoItems();
                    }).fail(() => {
                        $row.find('.bo-item-stock').text('Gagal memuat');
                        validateBoItems();
                    });
            }
            function validateBoItems() {
                let valid = $('#bo-items .bo-item-card').length > 0;
                const ranges = [];
                const requested = {};
                $('#bo-items .bo-item-card').each(function() {
                    const $row = $(this);
                    const denom = $row.find('.bo-denom').val();
                    const mode = $row.find('.bo-serial-mode').val();
                    const $qty = $row.find('.bo-qty');
                    const $manualQty = $row.find('.bo-manual-qty');
                    const start = String($row.find('.bo-sn-start').val() || '').trim();
                    const endInput = String($row.find('.bo-sn-end').val() || '').trim();
                    const end = endInput || start;
                    const isSa = productByDenom[denom] === 'SA';
                    const pattern = isSa ? /^0\d{15}$/ : /^\d{12}$/;
                    let qty = 0;
                    let error = '';
                    const stockKey = `${$('#kategorisegelmasuk').val()}|${denom}`;
                    const stock = stockCache[stockKey];
                    if (!denom) error = 'Pilih denom.';
                    else if (mode === 'broken') {
                        const rawQty = String($manualQty.val() || '');
                        qty = /^\d+$/.test(rawQty) ? Number(rawQty) : 0;
                        if (qty < 1 || qty > 2000000) error = 'Qty SN pecah harus 1–2.000.000.';
                    } else if (!pattern.test(start) || (endInput && !pattern.test(endInput))) {
                        error = isSa ? 'SN SA harus 16 digit dan diawali 0.' : 'SN PV harus tepat 12 digit.';
                    } else {
                        qty = serialRangeQty(start, end);
                        if (!qty) error = 'Rentang SN tidak valid atau melebihi 2.000.000 SN.';
                        for (const range of ranges) {
                            if (start.length === range.start.length && start <= range.end && end >= range.start) error = 'Rentang SN bertumpang tindih dengan baris lain.';
                        }
                        ranges.push({start, end});
                        $qty.val(qty || '');
                    }
                    if (mode === 'broken') $qty.val(qty || '');
                    $row.find('.bo-qty-display').text(qty ? qty.toLocaleString('id-ID') : '—').toggleClass('is-empty', !qty);
                    requested[denom] = (requested[denom] || 0) + qty;
                    if (stock === undefined && denom && !error) error = 'Stok belum termuat; pilih ulang denom atau BO.';
                    if (stock !== undefined && requested[denom] > stock) error = `Qty total melebihi stok tersedia (${stock.toLocaleString('id-ID')}).`;
                    $row.find('.bo-serial-hint').toggleClass('text-danger', Boolean(error)).toggleClass('text-success', !error && Boolean(qty))
                        .text(error || (mode === 'broken' ? `${qty.toLocaleString('id-ID')} pcs dicatat manual; SN tidak dicatat satu per satu.` : (endInput ? `${qty.toLocaleString('id-ID')} SN dihitung dari rentang.` : '1 pcs dihitung dari SN awal.')));
                    $row.find('.bo-sn-start, .bo-sn-end').toggleClass('is-invalid', Boolean(error) && mode === 'range');
                    $qty.toggleClass('is-invalid', Boolean(error) && mode === 'broken');
                    $row.find('.bo-row-error').text(error && stock !== undefined && requested[denom] > stock ? error : '');
                    if (!denom || error || qty < 1) valid = false;
                });
                const canSave = valid && Boolean($('#kategorisegelmasuk').val()) && Boolean($('#tappenerima').val());
                $('#submitBtn').prop('disabled', !canSave);
                return valid;
            }

            $('#add-bo-item').on('click', addBoItem);
            $('#bo-items').on('click', '.remove-bo-item', function() {
                if ($('#bo-items .bo-item-card').length > 1) $(this).closest('.bo-item-card').remove();
                validateBoItems();
            }).on('click', '.bo-serial-choice', function() {
                const $row = $(this).closest('.bo-item-card');
                const mode = String($(this).data('mode'));
                const broken = mode === 'broken';
                $row.find('.bo-serial-mode').val(mode);
                $row.find('.bo-serial-choice').removeClass('active');
                $(this).addClass('active').attr('aria-pressed', 'true');
                $row.find('.bo-serial-fields').toggleClass('d-none', broken);
                $row.find('.bo-sn-start').prop('required', !broken).val('');
                $row.find('.bo-sn-end').val('');
                $row.find('.bo-manual-qty').toggleClass('d-none', !broken).val('');
                $row.find('.bo-qty-display').toggleClass('d-none', broken).text('—').toggleClass('is-empty', !broken);
                $row.find('.bo-qty').val('');
                $row.find('.bo-qty-label').text(broken ? 'Qty SN Pecah' : 'Qty otomatis');
                $row.find('.bo-serial-hint').removeClass('text-danger text-success').text(broken ? 'Masukkan qty; SN tidak dicatat satu per satu.' : 'Massal: isi SN awal dan opsional SN akhir. Qty dihitung otomatis.');
                validateBoItems();
            }).on('input', '.bo-sn-start, .bo-sn-end', function() {
                const $row = $(this).closest('.bo-item-card');
                const isSa = productByDenom[$row.find('.bo-denom').val()] === 'SA';
                this.value = String(this.value || '').replace(/\D/g, '').slice(0, isSa ? 16 : 12);
                validateBoItems();
            }).on('input', '.bo-manual-qty', validateBoItems);

            $('#formKeluarBO').on('submit', function(event) {
                if (submitting) { event.preventDefault(); return; }
                if (!validateBoItems() || !$('#kategorisegelmasuk').val() || !$('#tappenerima').val()) {
                    event.preventDefault();
                    Swal.fire({ icon: 'error', title: 'Periksa form BO', text: 'Lengkapi BO, TAP, denom, dan SN atau qty pada setiap baris.' });
                    return;
                }
                submitting = true;
                $('#submitBtn').prop('disabled', true).text('Processing...');
            });

            $('.select2').each(function() { initSelect2($(this)); });

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
                Object.keys(stockCache).forEach(key => { if (!key.startsWith(`${idtap}|`)) delete stockCache[key]; });
                $('#bo-items .bo-item-card').each(function() {
                    const denom = $(this).find('.bo-denom').val();
                    $(this).find('.bo-item-stock').text('—');
                    if (denom && idtap) loadBoStock(denom, $(this));
                });
                validateBoItems();
                $('#tappenerima').empty().trigger('change');
                if (!idtap) return;

                $('#tappenerima').html('<option>Loading...</option>');

                $.post('{{ url('form/formkeluarbo') }}', {
                    idtap: idtap,
                    _token: '{{ csrf_token() }}'
                }).done(function(res) {
                    $('#tappenerima').html(res).trigger('change');
                });
            });
            $('#tappenerima').on('change', validateBoItems);

            addBoItem();

        });
    </script>
@endpush
