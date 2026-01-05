@extends('layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content">
            <div class="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif
                        <div class="card">
                            <div class="card-header">
                                <ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="{{ url('stock') }}" role="tab">Stock All</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ url('stocktap') }}" role="tab">Stock Tap</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ url('stocksf') }}" role="tab">Stock SF</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="col-auto mb-3">
                                    <a href="{{ url('exportexcelall') }}" class="btn btn-success btn-sm btn-rounded"><i
                                            class='fas fa-file-export'></i>Export</a>
                                </div>
                                <div class="table-responsive">
                                    <table id="stock" class="display table table-striped table-hover"
                                        style="
											div.dataTables_wrapper {
												width: 500px;
												margin: 0 auto;
											}">
                                        <thead>
                                            <tr>
                                                <thead>
                                                    <tr>
                                                        <th>ID Tap</th>
                                                        @for ($i = 1; $i <= 42; $i++)
                                                            <th>V{{ $i }}</th>
                                                        @endfor
                                                    </tr>
                                                </thead>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($idtaps as $idtap)
                                                <tr>
                                                    <td>{{ $idtap }}</td>
                                                    @for ($j = 1; $j <= 42; $j++)
                                                        <td>{{ $stocks[$idtap][$j] }}</td>
                                                    @endfor
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                            </tr>
                                        </tfoot>
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
@push('scripts')
    <script>
        // Add Row
        $("#stock").DataTable({
            pageLength: 10,
        });
    </script>
@endpush
