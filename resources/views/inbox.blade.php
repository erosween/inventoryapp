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

							@if($errors->has('error'))
							<div class="alert alert-danger">
								{{ $errors->first('error') }}
							</div>
							@endif

							<div class="card">
								<div class="card-header">
									<div class="d-flex align-items-center">
										<h4 class="card-title">Terima Stock Dari TAP lain</h4>  
									</div>	
								</div>
								{{-- card header --}}
								<div class="card-body">
									<div class="table-responsive">
										<table id="add-row" class="display table table-striped table-hover" >
											<thead>
												<tr>
													<th>Tanggal</th>
													<th>Denom</th>
													<th>Quantity</th>
													<th>Pengirim</th>
													<th>Penerima</th>
													<th>Sn</th>
													<th style="width: 10%">Status</th>
												</tr>
											</thead>
											<tbody>
												@foreach ($data as $row )
												<tr>
													<td> {{ $row -> tgl }}</td>
													<td> {{ $row -> denom }}</td>
													<td> {{ number_format($row -> qty )}}</td>
													<td> {{ $row -> idtap }}</td>
													<td> {{ $row -> penerima }}</td>
													<td> {{ $row -> sn }}</td>
													<td>
														@if($row->status == 0)
														<span class="badge badge-success">Approved</span>
															@else
														<button class="btn btn-warning btn-round ml-auto btn-sm" data-toggle="modal" data-target="#addRowModal{{$row->idkeluar}}">
															{{-- <i class="fa fa-trash"></i> --}}
															Wait for Approval
														</button>
													@endif
													</td>
												</tr>
												@endforeach
											</tbody>
											<!-- Modal -->
											@foreach ($data as $row)
											<div class="modal fade" id="addRowModal{{ $row -> idkeluar }}" role="dialog" aria-hidden="true">
												<div class="modal-dialog" role="document">
													<div class="modal-content">
														<div class="modal-header no-bd">
															<h5 class="modal-title">
																Terima Stok Masuk dari  <strong>TAP {{$row->idtap}}</strong>
															</h5>
														</div>
														<div class="modal-body">
															<form action="masuk/{{ $row -> idkeluar }}" method="post">
																@csrf
																<h5>Pastikan Barang yang diterima sudah sesuai, stok <strong>{{ $row -> denom }}</strong> dengan Quantity <strong>{{ number_format($row->qty) }}</strong> </h5>
																<input type="hidden" name="iddenom" value="{{ $row -> iddenom }}" class="form-control mb-1">
																<input type="hidden" name="pengirim" value="{{ $row -> idtap }}" class="form-control mb-1">
																<input type="hidden" name="penerima" value="{{ $row -> penerima }}" class="form-control mb-1">
																<input type="hidden" name="status" value="{{ $row -> status }}" class="form-control mb-1">
																<input type="hidden" name="qty" value="{{ $row -> qty }}" class="form-control mb-1">
																<div class="modal-footer no-bd">
																	<button type="submit" class="btn btn-primary">Terima</button>
																</div>
															</form>
														</div>
													</div>
												</div>
											</div>
										</div>
										@endforeach
									</table>
								</div>
							</div>
							{{-- card --}}
						</div>
						{{-- col-md 12 --}}
					</div>
					{{-- row --}}
				</div>
				{{-- page inner --}}
			</div>
			{{-- content --}}
		</div>
		{{-- main panel --}}

			@endsection
			@push('scripts')
	<!--   Core JS Files   -->
	<script src="assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="assets/js/core/popper.min.js"></script>
	<script src="assets/js/core/bootstrap.min.js"></script>

	<!-- jQuery UI -->
	<script src="assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

	<!-- jQuery Scrollbar -->
	<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

	<!-- Moment JS -->
	{{-- <script src="assets/js/plugin/moment/moment.min.js"></script> --}}

	<!-- Chart JS -->
	{{-- <script src="assets/js/plugin/chart.js/chart.min.js"></script> --}}

	<!-- jQuery Sparkline -->
	{{-- <script src="assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script> --}}

	<!-- Chart Circle -->
	{{-- <script src="assets/js/plugin/chart-circle/circles.min.js"></script> --}}

	<!-- Datatables -->
	<script src="assets/js/plugin/datatables/datatables.min.js"></script>

	<!-- Bootstrap Notify -->
	<script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<!-- Bootstrap Toggle -->
	<script src="assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

	<!-- jQuery Vector Maps -->
	{{-- <script src="assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script> --}}

	<!-- Google Maps Plugin -->
	{{-- <script src="assets/js/plugin/gmaps/gmaps.js"></script> --}}

	<!-- Sweet Alert -->
	<script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

	<!-- Azzara JS -->
	<script src="assets/js/ready.min.js"></script>



	<script>
	// Add Row
	$("#add-row").DataTable({
		pageLength: 5,
		order: [[0, "desc"]]
	});
	</script>

	<script>

	// Add Row untuk total disetiap menu
	$("#add-row1").DataTable({
		searching : false,
		paging : false,
		info : false,
		order: [[1, "desc"]]
	});
	</script>

	<script>
		// Add Row
		$("#stock").DataTable({
			pageLength: 10,
		});
	</script>

	{{-- table penjualan perdenom di home --}}
	<script>
		// Add Row
		$("#stock1").DataTable({
			searching : false,
			paging : false,
			info : false,
			order: [[1, "desc"]]
		});
	</script>

	<script>
		// Add Row
		$("#stock2").DataTable({
			searching : false,
			paging : false,
			info : false,
			order: [[1, "desc"]]
		});
	</script>

 @endpush