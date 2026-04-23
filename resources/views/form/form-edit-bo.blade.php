@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">

                <div class="page-header">
                    <h4 class="page-title">Edit BO / Retur</h4>
                </div>

                <div class="row">
                    <div class="col-xl-7 col-lg-8 col-md-11">

                        <div class="card shadow-sm border-warning">
                            <div class="card-header bg-warning-gradient text-white">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-edit fa-lg"></i>
                                    <div>
                                        <strong class="d-block">Form Perbaikan Data BO</strong>
                                        <small>ID Keluar: #{{ $edit->idkeluar }}</small>
                                    </div>
                                </div>
                            </div>

                            <form action="{{ url('bo/update/' . $edit->idkeluar) }}" method="POST" id="mainForm">
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

                                        {{-- BO (Pengirim) --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>Pilih BO (Pengirim)</label>
                                                <select name="pengirim" class="form-control select2" required>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->namabo }}" {{ $edit->pengirim == $row->namabo ? 'selected' : '' }}>
                                                            {{ $row->namabo }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- TAP (Penerima) --}}
                                        <div class="col-md-6">
                                            <div class="form-group mb-1">
                                                <label>TAP (Penerima)</label>
                                                <select name="penerima" class="form-control select2" required>
                                                    @foreach ($data as $row)
                                                        <option value="{{ $row->idtap }}" {{ $edit->penerima == $row->idtap ? 'selected' : '' }}>
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
                                                <select name="iddenom" class="form-control select2" required>
                                                    <option value="1" {{ $edit->iddenom == 1 ? 'selected' : '' }}>5K</option>
                                                    <option value="2" {{ $edit->iddenom == 2 ? 'selected' : '' }}>10K</option>
                                                    <option value="3" {{ $edit->iddenom == 3 ? 'selected' : '' }}>20K</option>
                                                    <option value="4" {{ $edit->iddenom == 4 ? 'selected' : '' }}>25K</option>
                                                    <option value="5" {{ $edit->iddenom == 5 ? 'selected' : '' }}>50K</option>
                                                    <option value="6" {{ $edit->iddenom == 6 ? 'selected' : '' }}>100K</option>
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
                                                    value="{{ $edit->sn }}" placeholder="SN Awal - SN Akhir">
                                            </div>
                                        </div>

                                        {{-- Keterangan --}}
                                        <div class="col-md-12">
                                            <div class="form-group mb-1">
                                                <label>Keterangan</label>
                                                <input type="text" name="tambahanket" class="form-control"
                                                    value="{{ $edit->tambahanket }}">
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-end gap-2">
                                    <a href="{{ url('bo') }}" class="btn btn-light">Kembali</a>
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
