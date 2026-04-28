@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                <div class="page-header">
                    <h4 class="page-title">Manajemen Denom (Produk)</h4>
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
                            <a href="#">Denom</a>
                        </li>
                    </ul>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card premium-card">
                             <div class="card-header bg-white border-bottom py-3">
                                 <div class="d-flex align-items-center">
                                     <h4 class="card-title text-indigo font-weight-bold">
                                         <i class="fas fa-layer-group mr-2"></i>Daftar Denom
                                     </h4>
                                     <button class="btn btn-primary btn-round ml-auto" data-toggle="modal"
                                         data-target="#addRowModal">
                                         <i class="fa fa-plus"></i>
                                         Tambah Denom Baru
                                     </button>
                                 </div>
                             </div>
                            <div class="card-body">


                                <div class="table-responsive">
                                    <table id="add-row" class="table table-indigo table-hover w-100">
                                        <thead>
                                            <tr>
                                                <th>ID Denom</th>
                                                <th>Nama Denom</th>
                                                <th>Grup</th>
                                                <th>Kategori Inject</th>
                                                <th>Harga Jual</th>
                                                <th style="width: 10%">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($denoms as $denom)
                                                <tr>
                                                    <td>{{ $denom->iddenom }}</td>
                                                    <td>{{ $denom->denom }}</td>
                                                    <td>
                                                        <span class="badge badge-info">{{ $denom->group_name }}</span>
                                                    </td>
                                                    <td>
                                                        @if($denom->kategori_inject)
                                                            <span class="badge badge-{{ $denom->kategori_inject == 'SEGEL' ? 'primary' : ($denom->kategori_inject == 'BYU' ? 'success' : 'warning') }}">{{ $denom->kategori_inject }}</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($denom->harga_jual > 0)
                                                            <span class="font-weight-bold text-success">Rp {{ number_format($denom->harga_jual, 0, ',', '.') }}</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="form-button-action">
                                                            <button type="button" data-toggle="modal"
                                                                data-target="#editModal{{ $denom->iddenom }}"
                                                                class="btn btn-link btn-primary btn-lg">
                                                                <i class="fa fa-edit"></i>
                                                            </button>

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
    <!-- Add Modal -->
    <div class="modal fade" id="addRowModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header no-bd">
                    <h5 class="modal-title">
                        <span class="fw-mediumbold">Denom</span>
                        <span class="font-weight-light">Baru</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('denoms.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="small text-muted">Menambah denom baru akan otomatis membuat saldo awal "0" untuk SEMUA
                            TAP dan SALES FORCE.</p>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>ID Denom (Unik)</label>
                                    <input type="text" name="iddenom" class="form-control"
                                        placeholder="Contoh: {{ $suggestedId }}" value="{{ $suggestedId }}" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>Nama Denom</label>
                                    <input type="text" name="denom" class="form-control"
                                        placeholder="Contoh: 2GB/1hari" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>Grup</label>
                                    <select name="group_name" class="form-control" required>
                                        @foreach ($groups as $g)
                                            <option value="{{ $g }}" {{ $g == 'LAINNYA' ? 'selected' : '' }}>
                                                {{ $g }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>Kategori Inject</label>
                                    <select name="kategori_inject" class="form-control">
                                        <option value="">- Tidak Ada -</option>
                                        @foreach ($kategoriInjects as $ki)
                                            <option value="{{ $ki }}">{{ $ki }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>Harga Jual (Rp)</label>
                                    <input type="number" name="harga_jual" class="form-control" placeholder="Contoh: 5000" min="0" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer no-bd">
                        <button type="submit" class="btn btn-primary">Simpan & Inisialisasi</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($denoms as $denom)
        <!-- Edit Modal -->
        <div class="modal fade" id="editModal{{ $denom->iddenom }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header no-bd">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">Edit</span>
                            <span class="font-weight-light">Denom</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ url('denoms/' . $denom->iddenom) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label>ID Denom (Kunci)</label>
                                        <input type="text" class="form-control" value="{{ $denom->iddenom }}" disabled>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label>Nama Denom</label>
                                        <input type="text" name="denom" class="form-control"
                                            value="{{ $denom->denom }}" required>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label>Grup</label>
                                        <select name="group_name" class="form-control" required>
                                            @foreach ($groups as $g)
                                                <option value="{{ $g }}"
                                                    {{ $denom->group_name == $g ? 'selected' : '' }}>
                                                    {{ $g }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label>Kategori Inject</label>
                                        <select name="kategori_inject" class="form-control">
                                            <option value="">- Tidak Ada -</option>
                                            @foreach ($kategoriInjects as $ki)
                                                <option value="{{ $ki }}"
                                                    {{ $denom->kategori_inject == $ki ? 'selected' : '' }}>
                                                    {{ $ki }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label>Harga Jual (Rp)</label>
                                        <input type="number" name="harga_jual" class="form-control" value="{{ $denom->harga_jual ?? 0 }}" min="0">
                                    </div>
                                </div>
                            </div>
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

            // 🔍 Real-time ID Denom Check
            $('input[name="iddenom"]').on('input', function() {
                var id = $(this).val();
                var $input = $(this);
                var $feedback = $('#iddenom-feedback');

                if (!$feedback.length) {
                    $input.after('<small id="iddenom-feedback" class="form-text"></small>');
                    $feedback = $('#iddenom-feedback');
                }

                if (id.length < 2) {
                    $input.removeClass('is-invalid is-valid');
                    $feedback.text('').removeClass('text-danger text-success');
                    return;
                }

                $.get('{{ url("ajax/check-denom") }}/' + id, function(data) {
                    if (data.exists) {
                        $input.addClass('is-invalid').removeClass('is-valid');
                        $feedback.text('⚠️ ID Denom ini sudah terdaftar!').addClass('text-danger').removeClass('text-success');
                        $('button[type="submit"]').prop('disabled', true);
                    } else {
                        $input.addClass('is-valid').removeClass('is-invalid');
                        $feedback.text('✅ ID Denom tersedia.').addClass('text-success').removeClass('text-danger');
                        $('button[type="submit"]').prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
