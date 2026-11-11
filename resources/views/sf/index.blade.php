{{--
    Halaman master sales force dan pengelolaan akun mobile. Data dikelola oleh SfController.
--}}@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                <div class="page-header">
                    <h4 class="page-title">Manajemen Sales Force (SF)</h4>
                    <ul class="breadcrumbs">
                        <li class="nav-home">
                            <a href="{{ url('home') }}">
                                <i class="flaticon-home"></i>
                            </a>
                        </li>
                        <li class="separator">
                            <i class="flaticon-right-arrow"></i>
                        </li>
                        <li class="nav-item">
                            <a href="#">Master Data</a>
                        </li>
                        <li class="separator">
                            <i class="flaticon-right-arrow"></i>
                        </li>
                        <li class="nav-item">
                            <a href="#">Sales Force</a>
                        </li>
                    </ul>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card premium-card">
                             <div class="card-header bg-white border-bottom py-3">
                                 <div class="d-flex align-items-center">
                                     <h4 class="card-title text-indigo font-weight-bold">
                                         <i class="fas fa-users mr-2"></i>Daftar Sales Force (SF)
                                     </h4>
                                     <button class="btn btn-primary btn-round ml-auto" data-toggle="modal"
                                         data-target="#addRowModal">
                                         <i class="fa fa-plus"></i>
                                         Tambah SF Baru
                                     </button>
                                     <a href="{{ route('sf.sync-login') }}" class="btn btn-outline-primary btn-round ml-2">
                                         <i class="fas fa-sync-alt"></i>
                                         Sinkronkan Login SF
                                     </a>
                                     @if(auth()->user()?->isSuperAdmin())
                                         <a href="{{ route('sf.export') }}" class="btn btn-success btn-round ml-2">
                                             <i class="fas fa-download"></i>
                                             Download SF
                                         </a>
                                     @endif
                                 </div>
                             </div>
                            <div class="card-body">


                                <div class="table-responsive">
                                    <table id="add-row" class="table table-indigo table-hover w-100">
                                        <thead>
                                            <tr>
                                                <th>TAP</th>
                                                <th>ID SF</th>
                                                <th>Nama SF</th>
                                                <th>ID SF Digipos</th>
                                                <th>Status Tampilan</th>
                                                <th>Login Code (Mobile)</th>
                                                <th style="width: 10%">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($sfs as $sf)
                                                <tr>
                                                    <td>{{ $sf->idtap }}</td>
                                                    <td>{{ $sf->idsf }}</td>
                                                    <td>{{ $sf->namasf }}</td>
                                                    <td>{{ $sf->digipos_id ?? '—' }}</td>
                                                    <td><span class="badge {{ ($sf->is_hidden ?? false) ? 'badge-secondary' : 'badge-success' }}">{{ ($sf->is_hidden ?? false) ? 'Disembunyikan' : 'Ditampilkan' }}</span></td>
                                                    <td><code class="text-primary font-weight-bold">{{ $sf->login_code }}</code></td>
                                                    <td>
                                                        <div class="form-button-action d-flex align-items-center" style="gap:14px">
                                                            <button type="button" data-toggle="modal"
                                                                data-target="#editModal{{ $loop->index }}"
                                                                class="btn btn-link text-primary p-0 action-edit-icon"
                                                                title="Edit" aria-label="Edit">
                                                                <i class="fas fa-edit" aria-hidden="true"></i> Edit
                                                            </button>
                                                            <form action="{{ url('sf/' . rawurlencode($sf->idsf)) }}" method="POST" class="m-0" onsubmit="return confirm('Hapus SF ini? SF yang masih memiliki stok atau riwayat tidak dapat dihapus.')">
                                                                @csrf
                                                                <button type="submit" class="btn btn-link text-danger p-0" title="Hapus SF {{ $sf->namasf }}" aria-label="Hapus SF {{ $sf->namasf }}"><i class="fas fa-trash-alt" aria-hidden="true"></i> Delete</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <!-- Modal Tambah SF -->
    <div class="modal fade" id="addRowModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header no-bd">
                    <h5 class="modal-title">
                        <span class="fw-mediumbold">Tambah</span>
                        <span class="fw-light">Sales Force</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ url('sf') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="small">Isikan informasi identitas SF baru di bawah ini. Saldo awal stok SF ini akan otomatis diisi 0.</p>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group form-group-default">
                                    <label>Kode TAP (Area Operasional)</label>
                                    <select class="form-control" name="idtap" required>
                                        <option value="">Pilih TAP...</option>
                                        @foreach ($taps as $tap)
                                            <option value="{{ $tap->idtap }}">{{ $tap->idtap }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group form-group-default">
                                    <label>ID SF</label>
                                    <input id="idsf" type="text" class="form-control" name="idsf" placeholder="Contoh: SF-T01" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group form-group-default">
                                    <label>Nama SF</label>
                                    <input id="namasf" type="text" class="form-control" maxlength="100" name="namasf" placeholder="Masukkan nama..." required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group px-4">
                        <label>ID SF Digipos</label>
                        <input type="text" name="digipos_id" class="form-control" maxlength="50" value="{{ old('digipos_id') }}" placeholder="ID SF dari Digipos (opsional)">
                    </div>
                    <div class="modal-footer no-bd">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Tutup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($sfs as $sf)
        <!-- Edit Modal -->
        <div class="modal fade" id="editModal{{ $loop->index }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header no-bd">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">Edit</span>
                            <span class="font-weight-light">Sales Force</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ url('sf/update/' . $sf->idsf) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>ID SF (Kunci)</label>
                                        <input type="text" class="form-control" value="{{ $sf->idsf }}" disabled>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Kode TAP (Area Operasional)</label>
                                        <select class="form-control" name="idtap" required>
                                            @foreach ($taps as $tap)
                                                <option value="{{ $tap->idtap }}" {{ $tap->idtap == $sf->idtap ? 'selected' : '' }}>{{ $tap->idtap }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Nama SF</label>
                                        <input type="text" name="namasf" maxlength="100" class="form-control" value="{{ $sf->namasf }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group px-4">
                            <label>ID SF Digipos</label>
                            <input type="text" name="digipos_id" class="form-control" maxlength="50" value="{{ $sf->digipos_id ?? '' }}" placeholder="ID SF dari Digipos (opsional)">
                        </div>
                        <div class="form-group px-4">
                            <label class="d-flex align-items-center" style="gap:10px"><input type="checkbox" name="is_hidden" value="1" {{ ($sf->is_hidden ?? false) ? 'checked' : '' }}> Sembunyikan SF</label>
                            <small class="text-muted">Tidak muncul di daftar stok SF dan pilihan input SF Masuk/Keluar. Riwayat tetap tersimpan.</small>
                        </div>
                        <div class="modal-footer no-bd">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#add-row').DataTable({
                "pageLength": 10,
            });

            // 🔍 Real-time ID SF Check
            $('input[name="idsf"]').on('input', function() {
                var id = $(this).val();
                var $input = $(this);
                var $feedback = $('#idsf-feedback');

                if (!$feedback.length) {
                    $input.after('<small id="idsf-feedback" class="form-text"></small>');
                    $feedback = $('#idsf-feedback');
                }

                if (id.length < 2) {
                    $input.removeClass('is-invalid is-valid');
                    $feedback.text('').removeClass('text-danger text-success');
                    return;
                }

                $.get('{{ url("ajax/check-sf") }}/' + id, function(data) {
                    if (data.exists) {
                        $input.addClass('is-invalid').removeClass('is-valid');
                        $feedback.text('⚠️ ID SF ini sudah terdaftar!').addClass('text-danger').removeClass('text-success');
                        $('button[type="submit"]').prop('disabled', true);
                    } else {
                        $input.addClass('is-valid').removeClass('is-invalid');
                        $feedback.text('✅ ID SF tersedia.').addClass('text-success').removeClass('text-danger');
                        $('button[type="submit"]').prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
