@extends('layout.layout')

@section('content')
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

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
                                                <input type="number" name="qty" class="form-control"
                                                    value="{{ $edit->qty }}" min="1" required>
                                            </div>
                                        </div>

                                        {{-- SN --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>SN</label>
                                                <input type="text" name="sn" class="form-control"
                                                    value="{{ $edit->sn }}">
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
                    $('#idsf').html(res).trigger('change');
                    if (selectedSf) {
                        $('#idsf').val(selectedSf).trigger('change');
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
