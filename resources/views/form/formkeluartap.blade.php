{{--
    Form pengiriman barang antar-TAP. Penerimaan di sisi tujuan memakai halaman Masuk.
--}}@extends('layout.layout')

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
                                                            <th style="min-width:360px">Identitas SN</th>
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
        const bulkDenoms = @json($denom->map(fn($d) => ['id' => $d->iddenom, 'name' => $d->denom, 'product' => $d->product, 'product_group' => $d->product_group, 'kategori_inject' => $d->kategori_inject, 'group_name' => $d->group_name])->values());
        let bulkIndex = 0;
        let tapStockData = {};

        $('.select2').select2({placeholder:'Pilih / Cari…',allowClear:true,width:'100%',dropdownParent:$('.card')});
        function isSa(id) {
            const d = bulkDenoms.find(x => String(x.id) === String(id));
            if (!d) return false;
            const product = String(d.product || '').trim().toUpperCase();
            if (product === 'PV' || product === 'SA') return product === 'SA';
            return ['SA','SP'].includes(String(d.product_group || '').trim().toUpperCase()) || String(d.kategori_inject || '').trim().toUpperCase() === 'SA' || String(d.group_name || '').trim().toUpperCase() === 'SA' || String(d.id).toUpperCase().startsWith('SP');
        }
        function qtyFor(start, end, sa) {
            const re = sa ? /^0\d{15}$/ : /^\d{12}$/;
            if (!re.test(start) || !re.test(end)) return 0;
            const n = BigInt(end) - BigInt(start) + 1n;
            return n > 0n && n <= 2000000n ? Number(n) : 0;
        }
        function validateRows() {
            const requested = {}; let valid = Boolean($('select[name="pengirim"]').val()) && Boolean($('select[name="penerima"]').val()) && $('#bulk-rows tr').length > 0;
            $('#bulk-rows tr').each(function() {
                const $r=$(this), denom=$r.find('.bulk-denom').val(), mode=$r.find('.bulk-serial-mode').val();
                const start=String($r.find('.bulk-sn-start').val()||''), rawEnd=String($r.find('.bulk-sn-end').val()||'');
                let qty=0;
                if (mode==='broken') qty=parseInt($r.find('.bulk-manual-qty').val(),10)||0;
                else qty=qtyFor(start, rawEnd||start, isSa(denom));
                $r.find('.bulk-qty').val(qty||'');
                $r.find('.bulk-qty-view').text(qty ? qty.toLocaleString('id-ID') : '—');
                if (!denom || qty<1) valid=false;
                requested[denom]=(requested[denom]||0)+qty;
                if (qty > (parseInt(tapStockData[denom],10)||0)) valid=false;
            });
            Object.entries(requested).forEach(([d,q])=>{if(q>(parseInt(tapStockData[d],10)||0))valid=false;});
            $('#submitBtn').prop('disabled',!valid);
            return valid;
        }
        $('select[name="pengirim"]').on('change', function() {
            tapStockData={}; $('#bulk-rows').empty(); $('#add-bulk-row').prop('disabled',true);
            const idtap=$(this).val(); if(!idtap) return;
            $.post('{{ route('ajax.get-all-stock-tap-keluar') }}',{idtap,_token:'{{ csrf_token() }}'}).done(res=>{tapStockData=res||{};addBulkRow();$('#add-bulk-row').prop('disabled',false);validateRows();}).fail(()=>Swal.fire({icon:'error',title:'Stok gagal dimuat',text:'Silakan pilih kembali TAP pengirim.'}));
        });
        $('select[name="penerima"]').on('change',validateRows);

        function initBulkDenomSelect(select) {
            select.select2({placeholder:'Pilih / cari denom',allowClear:true,width:'100%',dropdownParent:$('.card')});
        }

        function refreshBulkDenomOptions(target) {
            const selects = target ? $(target) : $('.bulk-denom');
            const senderSelected = Boolean($('select[name="pengirim"]').val());

            selects.each(function() {
                const select = $(this);
                const selectedValue = select.val();
                const alreadyInitialized = select.hasClass('select2-hidden-accessible');

                select.empty().append(new Option('Pilih / cari denom', ''));
                bulkDenoms.forEach(denom => {
                    const stock = parseInt(tapStockData[denom.id]) || 0;
                    if (stock <= 0) return;
                    select.append(new Option(
                        denom.name,
                        denom.id,
                        false,
                        denom.id === selectedValue
                    ));
                });

                select.prop('disabled', !senderSelected);
                if (alreadyInitialized) {
                    select.trigger('change.select2');
                } else {
                    initBulkDenomSelect(select);
                }

                if (!select.val()) {
                    select.closest('tr').find('.bulk-stock').text('-');
                }
            });
        }

        function addBulkRow() {
            const index = bulkIndex++;
            const disabled = $('select[name="pengirim"]').val() ? '' : 'disabled';
            const row = $(`
                <tr>
                    <td><select name="items[${index}][iddenom]" class="form-control form-control-sm bulk-denom" required ${disabled}><option value="">Pilih / cari denom</option></select></td>
                    <td class="bulk-stock text-right align-middle">-</td>
                    <td><span class="bulk-qty-view">—</span><input type="hidden" name="items[${index}][qty]" class="bulk-qty"><input type="hidden" name="items[${index}][serial_mode]" class="bulk-serial-mode" value="range"><input type="number" name="items[${index}][qty]" class="form-control form-control-sm bulk-manual-qty d-none" min="1" max="2000000" placeholder="Qty" disabled></td>
                    <td><div class="btn-group btn-group-sm mb-1 serial-mode-choices"><button type="button" class="btn btn-outline-primary active" data-mode="range">Massal</button><button type="button" class="btn btn-outline-primary" data-mode="broken">SN Pecah</button></div><div class="serial-range d-flex align-items-center"><input type="text" name="items[${index}][sn_start]" class="form-control form-control-sm bulk-sn-start" inputmode="numeric" placeholder="SN awal"><span class="px-1">–</span><input type="text" name="items[${index}][sn_end]" class="form-control form-control-sm bulk-sn-end" inputmode="numeric" placeholder="SN akhir"></div></td>
                    <td><button type="button" class="btn btn-sm btn-link text-danger remove-bulk-row">×</button></td>
                </tr>`);
            $('#bulk-rows').append(row);
            refreshBulkDenomOptions(row.find('.bulk-denom'));
        }

        $('#add-bulk-row').on('click', addBulkRow);
        $('#bulk-rows').on('click', '.remove-bulk-row', function() {
            if ($('#bulk-rows tr').length > 1) { $(this).closest('tr').remove(); validateRows(); }
        }).on('change', '.bulk-denom', function() {
            const stock = parseInt(tapStockData[$(this).val()]) || 0;
            $(this).closest('tr').find('.bulk-stock').text(stock.toLocaleString('id-ID'));
            validateRows();
        }).on('click','.serial-mode-choices button',function(){
            const $r=$(this).closest('tr'),mode=$(this).data('mode');$r.find('.bulk-serial-mode').val(mode);$r.find('.serial-mode-choices button').removeClass('active');$(this).addClass('active');$r.find('.serial-range').toggleClass('d-none',mode==='broken');$r.find('.bulk-sn-start,.bulk-sn-end').prop('disabled',mode==='broken');$r.find('.bulk-qty').prop('disabled',mode==='broken');$r.find('.bulk-manual-qty').toggleClass('d-none',mode!=='broken').prop('disabled',mode!=='broken');validateRows();
        }).on('input','.bulk-sn-start,.bulk-sn-end',function(){
            const sa=isSa($(this).closest('tr').find('.bulk-denom').val()),max=sa?16:12;this.value=String(this.value||'').replace(/\D/g,'').slice(0,max);validateRows();
        }).on('input','.bulk-manual-qty',validateRows);
        $('#formKeluarTap').on('submit',function(e){if(!validateRows()){e.preventDefault();Swal.fire({icon:'error',title:'Periksa input',text:'Pastikan TAP, denom, SN atau qty, dan stok tersedia sudah benar.'});return;}if($(this).data('submitting')){e.preventDefault();return;}$(this).data('submitting',true);$('#submitBtn').prop('disabled',true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');});
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
