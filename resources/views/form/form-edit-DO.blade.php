{{--
    Edit transaksi DO yang sudah tersimpan. Koreksi dikirim ke InputDOController.
--}}@extends('layout.layout')

@section('content')
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

                <div class="page-header">
                    <h4 class="page-title">Edit Input DO</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm border-warning">
                            <div class="card-header bg-warning-gradient text-white">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-file-invoice fa-lg"></i>
                                    <div>
                                        <strong class="d-block">Form Perbaikan Data DO</strong>
                                        <small>Pastikan stok SF belum terpakai untuk Qty baru</small>
                                    </div>
                                </div>
                            </div>

                            <form action="{{ url('DO/update/' . $edit->idmasuk) }}" method="POST" id="mainForm">
                                @csrf

                                <div class="card-body">
                                    <div class="row">

                                        {{-- Tanggal --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Tanggal</label>
                                                <input type="date" name="tgl" class="form-control"
                                                    value="{{ date('Y-m-d', strtotime($edit->tgl)) }}" required>
                                            </div>
                                        </div>

                                        {{-- No DO --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Nomor DO</label>
                                                <input type="text" name="nomordo" class="form-control"
                                                    value="{{ $edit->nomor_do }}" required>
                                            </div>
                                        </div>

                                        {{-- Week --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Week</label>
                                                <input type="text" name="week" class="form-control"
                                                    value="{{ $edit->week }}" required>
                                            </div>
                                        </div>

                                        {{-- TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Pilih TAP</label>
                                                <select name="idtap" id="idtap" class="form-control select2" required>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->idtap }}" {{ $edit->idtappenerima == $row->idtap ? 'selected' : '' }}>
                                                            {{ $row->idtap }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Denom --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Denom</label>
                                                <select name="kategorisegel" class="form-control select2" required>
                                                    @foreach ($denom as $row)
                                                        <option value="{{ $row->iddenom }}" {{ $edit->iddenom == $row->iddenom ? 'selected' : '' }}>
                                                            {{ $row->denom }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- SF (Penerima) --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Penerima (SF)</label>
                                                <select name="penerima" id="penerima" class="form-control select2" required>
                                                    {{-- Diisi via AJAX --}}
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Qty --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Quantity</label>
                                                <input type="number" name="qty" id="do-qty" class="form-control"
                                                    value="{{ $edit->qty }}" min="1" readonly required>
                                            </div>
                                        </div>

                                        {{-- SN --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                @php($serialParts = explode('-', (string) $edit->sn, 2))
                                                <label>SN Awal</label>
                                                <input type="text" name="sn_start" id="do-sn-start" class="form-control" inputmode="numeric" autocomplete="off" value="{{ $serialParts[0] ?? '' }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>SN Akhir <small class="text-muted">(kosongkan untuk satuan)</small></label>
                                                <input type="text" name="sn_end" id="do-sn-end" class="form-control" inputmode="numeric" autocomplete="off" value="{{ $serialParts[1] ?? '' }}">
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('DO') }}" class="btn btn-light">Kembali</a>
                                    <button type="submit" id="submitBtn" class="btn btn-warning text-white font-weight-bold">
                                        Update Data
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
            function updateDoQuantity() {
                const start = ($('#do-sn-start').val() || '').trim();
                const endInput = ($('#do-sn-end').val() || '').trim();
                const end = endInput || start;
                let qty = '';
                if (/^\d+$/.test(start) && /^\d+$/.test(end)) {
                    const count = BigInt(end) - BigInt(start) + 1n;
                    if (count > 0n && count <= 2000000n) qty = count.toString();
                }
                $('#do-qty').val(qty);
            }
            $('#do-sn-start, #do-sn-end').on('input change', updateDoQuantity);
            updateDoQuantity();
            $('.select2').each(function() {
                $(this).select2({
                    placeholder: 'Pilih / Cari…',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(this).closest('.card-body')
                });
            });

            // Load SF berdasarkan TAP
            $('#idtap').on('change', function() {
                loadSF($(this).val(), "{{ $edit->penerima }}");
            });

            // Init SF
            loadSF($('#idtap').val(), "{{ $edit->penerima }}");

            function loadSF(idtap, selectedSf) {
                if (!idtap) return;
                $.post("{{ route('ajax.get-sf-masuk') }}", {
                    idtap: idtap,
                    _token: "{{ csrf_token() }}"
                }, function(res) {
                    $('#penerima').html(res).trigger('change');
                    if (selectedSf) {
                        $('#penerima').val(selectedSf).trigger('change');
                    }
                });
            }

            let submitting = false;
            $('#mainForm').on('submit', function() {
                if (submitting) return false;
                submitting = true;
                $('#submitBtn').prop('disabled', true).text('Memproses...');
            });
        });
    </script>
@endpush
