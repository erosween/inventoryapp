@extends('layout.layout')

@section('content')
    <div class="main-panel form-premium-page">
        <div class="content">
            <div class="page-inner">

                <div class="page-header">
                    <h4 class="page-title">Edit Voucher Rusak</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm border-warning">
                            <div class="card-header bg-warning-gradient text-white">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-edit fa-lg"></i>
                                    <div>
                                        <strong class="d-block">Form Perbaikan Data</strong>
                                        <small>Pastikan data yang diubah sudah benar</small>
                                    </div>
                                </div>
                            </div>

                            <form action="{{ url('vrusak/update/' . $data->idrusak) }}" method="POST" id="mainForm">
                                @csrf

                                <div class="card-body">
                                    <div class="row">

                                        {{-- Tanggal --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Tanggal</label>
                                                <input type="date" name="tgl" class="form-control"
                                                    value="{{ $data->tgl }}" required>
                                            </div>
                                        </div>

                                        {{-- TAP --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>TAP</label>
                                                <select name="idtap" id="idtap" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach ($tap as $row)
                                                        <option value="{{ $row->idtap }}" {{ $data->idtap == $row->idtap ? 'selected' : '' }}>
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
                                                <select name="iddenom" id="iddenom" class="form-control select2" required>
                                                    <option></option>
                                                    @foreach ($denom as $row)
                                                        <option value="{{ $row->iddenom }}" {{ $data->iddenom == $row->iddenom ? 'selected' : '' }}>
                                                            {{ $row->denom }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Quantity --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Quantity</label>
                                                <input type="number" name="qty" class="form-control"
                                                    value="{{ $data->qty }}" min="1" required>
                                            </div>
                                        </div>

                                        {{-- SN --}}
                                        <div class="col-md-12">
                                            <div class="form-group mb-1">
                                                <label>SN</label>
                                                <input type="text" name="sn" class="form-control"
                                                    value="{{ $data->sn }}" placeholder="SN Awal - SN Akhir" required>
                                            </div>
                                        </div>

                                        {{-- Ket VF --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Ket VF</label>
                                                <select name="ketvf" class="form-control select2" required>
                                                    <option value="FISIK RUSAK" {{ $data->ketvf == 'FISIK RUSAK' ? 'selected' : '' }}>FISIK RUSAK</option>
                                                    <option value="SCRATCH TIDAK JELAS" {{ $data->ketvf == 'SCRATCH TIDAK JELAS' ? 'selected' : '' }}>SCRATCH TIDAK JELAS</option>
                                                    <option value="HOLOGRAM RUSAK" {{ $data->ketvf == 'HOLOGRAM RUSAK' ? 'selected' : '' }}>HOLOGRAM RUSAK</option>
                                                    <option value="LAINNYA" {{ $data->ketvf == 'LAINNYA' ? 'selected' : '' }}>LAINNYA</option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Ket Lain --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Keterangan Lain</label>
                                                <input type="text" name="tambahanket" class="form-control"
                                                    value="{{ $data->ketlain }}">
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('vrusak') }}" class="btn btn-light">Kembali</a>
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

            let submitting = false;
            $('#mainForm').on('submit', function() {
                if (submitting) return false;
                submitting = true;
                $('#submitBtn').prop('disabled', true).text('Memproses...');
            });
        });
    </script>
@endpush
