{{--
    Edit retur dari SF ke TAP. Perubahan saldo diproses di ReturSfController.
--}}@extends('layout.layout')

@section('content')
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

                @if ($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <div class="page-header">
                    <h4 class="page-title">Edit Retur SF</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm border-warning">
                            <div class="card-header bg-warning-gradient text-white">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-undo fa-lg"></i>
                                    <div>
                                        <strong class="d-block">Form Perbaikan Retur SF</strong>
                                        <small>Pastikan stok TAP mencukupi untuk rollback jika Qty diperkecil</small>
                                    </div>
                                </div>
                            </div>

                            <form action="{{ url('retursf/update/' . $edit->idretur) }}" method="POST" id="mainForm">
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

                                        {{-- TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Pilih TAP</label>
                                                <select name="idtap" id="idtap" class="form-control select2" required>
                                                    @foreach ($tap as $row)
                                                        <option value="{{ $row->idtap }}" {{ $edit->idtap == $row->idtap ? 'selected' : '' }}>
                                                            {{ $row->idtap }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- SF --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Pilih SF</label>
                                                <select name="idsf" id="idsf" class="form-control select2" required>
                                                    {{-- Diisi via AJAX --}}
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-12 mt-3">
                                            <label class="d-block mb-2">Tujuan retur</label>
                                            <label class="mr-3"><input type="radio" name="return_type" value="warehouse" @checked(($edit->return_type ?? 'warehouse') === 'warehouse')> Retur ke gudang</label>
                                            <label><input type="radio" name="return_type" value="sf" @checked(($edit->return_type ?? 'warehouse') === 'sf')> Retur ke SF lain</label>
                                        </div>
                                        <div class="col-md-6 {{ ($edit->return_type ?? 'warehouse') === 'sf' ? '' : 'd-none' }}" id="destination-sf-wrap">
                                            <div class="form-group mb-1">
                                                <label>Sales Force tujuan</label>
                                                <select name="destination_idsf" id="destination_idsf" class="form-control select2" {{ ($edit->return_type ?? 'warehouse') === 'sf' ? 'required' : 'disabled' }}>
                                                    <option></option>
                                                    @foreach($salesForces as $target)
                                                        @if($target->idsf !== $edit->idsf)
                                                            <option value="{{ $target->idsf }}" @selected(($edit->destination_idsf ?? '') === $target->idsf)>{{ $target->namasf }} · {{ $target->idsf }} · {{ $target->idtap }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Denom --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Denom</label>
                                                <select name="iddenom" class="form-control select2" required>
                                                    @foreach ($denom as $row)
                                                        <option value="{{ $row->iddenom }}" {{ $edit->iddenom == $row->iddenom ? 'selected' : '' }}>
                                                            {{ $row->denom }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Qty --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Quantity</label>
                                            <input type="number" name="qty" id="qty" class="form-control"
                                                    value="{{ $edit->qty }}" min="1" readonly required>
                                            </div>
                                        </div>

                                        {{-- SN --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>SN Awal</label>
                                                <input type="text" name="sn_start" id="sn_start" class="form-control" inputmode="numeric"
                                                    value="{{ $edit->sn_awal ?? explode('-', (string) $edit->sn)[0] }}" required>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>SN Akhir</label>
                                                <input type="text" name="sn_end" id="sn_end" class="form-control" inputmode="numeric"
                                                    value="{{ $edit->sn_akhir ?? (str_contains((string) $edit->sn, '-') ? explode('-', (string) $edit->sn)[1] : explode('-', (string) $edit->sn)[0]) }}" required>
                                            </div>
                                        </div>

                                        {{-- Ket VF --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Keterangan VF</label>
                                                <input type="text" name="ketvf" class="form-control"
                                                    value="{{ $edit->ketvf }}">
                                            </div>
                                        </div>

                                        {{-- Ket Lain --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Keterangan Lain</label>
                                                <input type="text" name="tambahket" class="form-control"
                                                    value="{{ $edit->tambahket }}">
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('retursf') }}" class="btn btn-light">Kembali</a>
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

            // Load SF berdasarkan TAP
            $('#idtap').on('change', function() {
                loadSF($(this).val(), "{{ $edit->idsf }}");
            });

            // Init SF
            loadSF($('#idtap').val(), "{{ $edit->idsf }}");

            function loadSF(idtap, selectedSf) {
                if (!idtap) return;
                $.post("{{ route('ajax.get-sf-masuk') }}", {
                    idtap: idtap,
                    _token: "{{ csrf_token() }}"
                }, function(res) {
                    const options = ['<option value="">-- Pilih SF --</option>'];
                    res.forEach(item => options.push(`<option value="${$('<div>').text(item.idsf).html()}"${String(item.idsf) === String(selectedSf) ? ' selected' : ''}>${$('<div>').text(item.namasf).html()}</option>`));
                    $('#idsf').html(options.join('')).trigger('change');
                });
            }

            function updateRangeQty() {
                const start = ($('#sn_start').val() || '').trim();
                const endInput = ($('#sn_end').val() || '').trim();
                if (!start || !endInput) { $('#qty').val(''); return; }
                const end = endInput;
                if (!/^\d+$/.test(start) || !/^\d+$/.test(end) || start.length !== end.length || end < start) { $('#qty').val(''); return; }
                const qty = Number(BigInt(end) - BigInt(start) + 1n);
                $('#qty').val(Number.isSafeInteger(qty) ? qty : '');
            }
            $('#sn_start, #sn_end').on('input', updateRangeQty);
            updateRangeQty();

            let submitting = false;
            $('#mainForm').on('submit', function() {
                if (submitting) return false;
                submitting = true;
                $('#submitBtn').prop('disabled', true).text('Memproses...');
            });
        });
    </script>
@endpush
