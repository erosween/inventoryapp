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
									<div class="d-flex align-items-center">
										<h4 class="card-title">Stock All</h4>  
									</div>
								</div>
								<div class="card-body">
									<div class="col-auto mb-3">
										<a href="{{ url('exportexcelall') }}" class="btn btn-success btn-sm btn-rounded"><i class='fas fa-file-export'></i>Export</a>
									</div>
									<div class="table-responsive">
										<table
											id="stock"
											class="display table table-striped table-hover"
										>
											<thead>
												<tr>
													<th>NO</th>
													<th>TAP</th>
													<th>SEGEL</th>
													<th>RoaMAX SEGEL</th>
													<th>1GB/1hari</th>
													<th>2GB/3hari ZONA 1</th>
													<th>2GB/3hari ZONA 2</th>
													<th>4.5GB/3hari</th>
													<th>5GB/5hari</th>
													<th>2.5GB/5hari ZONA 1</th>
													<th>3GB/5hari</th>
													<th>7GB/7hari</th>
													<th>10GB/7hari</th>
													<th>VOICE 30 HARI</th>
													<th>BYU SEGEL</th>
													<th>BYU 2GB/3hari</th>
													<th>BYU 2.5GB/5hari</th>
													<th>BYU 5GB/7hari</th>
													<th>BYU KAGET 4GB/30hari</th>
													<th>BYU KAGET 9GB/30hari</th>
													<th>BYU KAGET 14GB/30hari</th>
													<th>BYU KAGET 20GB/30hari</th>
												</tr>
											</thead>
											<tbody>
												@php
												$tsegel = 0;
												$tV1 = 0;
												$tV2 = 0;
												$tV3 = 0;
												$tV4 = 0;
												$tV5 = 0;
												$tV6 = 0;
												$tV7 = 0;
												$tV8 = 0;
												$tV9 = 0;
												$tV10 = 0;
												$tV11 = 0;
												$tV12 = 0;
												$tV13 = 0;
												$tV14 = 0;
												$tV15 = 0;
												$tV16 = 0;
												$tV17 = 0;
												$tV18 = 0;
												$tV19 = 0;
												$tV20 = 0;
												$tV21 = 0;
												$tV22 = 0;
												$tV23 = 0;
												$tV24 = 0;
												$tV25 = 0;
												$tV26 = 0;
												$tV27 = 0;
												$tV28 = 0;
												$tV29 = 0;
												$tV30 = 0;
												$tV31 = 0;
												$tV32 = 0;
												$tV33 = 0;
												$tV34 = 0;
												$tV35 = 0;
												$tV36 = 0;
												$tV37 = 0;
												$tV38 = 0;
												$tV39 = 0;
												$tV40 = 0;
											
											  @endphp
												@foreach ($data as $row )
												<tr>
													<td>{{ $loop -> iteration }}</td>
													<td> {{ $row -> idtap }}</td>
													<td> {{ number_format($row -> SEGEL )}}</td>
													<td> {{ number_format($row -> V16 )}}</td>
													<td> {{ number_format($row -> V32 )}}</td>
													<td> {{ number_format($row -> V2 )}}</td>
													<td> {{ number_format($row -> V24 )}}</td>
													<td> {{ number_format($row -> V3 )}}</td>
													<td> {{ number_format($row -> V7 )}}</td>
													<td> {{ number_format($row -> V5 )}}</td>
													<td> {{ number_format($row -> V31 )}}</td>
													<td> {{ number_format($row -> V28 )}}</td>
													<td> {{ number_format($row -> V29 )}}</td>
													<td> {{ number_format($row -> V15 )}}</td>
													<td> {{ number_format($row -> V33 )}}</td>
													<td> {{ number_format($row -> V34 )}}</td>
													<td> {{ number_format($row -> V35 )}}</td>
													<td> {{ number_format($row -> V36 )}}</td>
													<td> {{ number_format($row -> V37 )}}</td>
													<td> {{ number_format($row -> V38 )}}</td>
													<td> {{ number_format($row -> V39 )}}</td>
													<td> {{ number_format($row -> V40 )}}</td>
													
												</tr>
												@php
													$tsegel += $row-> SEGEL;
													$tV1 += $row-> V1;
													$tV2 += $row-> V2;
													$tV3 += $row-> V3;
													$tV4 += $row-> V4;
													$tV5 += $row-> V5;
													$tV6 += $row-> V6;
													$tV7 += $row-> V7;
													$tV8 += $row-> V8;
													$tV9 += $row-> V9;
													$tV10 += $row-> V10;
													$tV11 += $row-> V11;
													$tV12 += $row-> V12;
													$tV13 += $row-> V13;
													$tV14 += $row-> V14;
													$tV15 += $row-> V15;
													$tV16 += $row-> V16;
													$tV17 += $row-> V17;
													$tV18 += $row-> V18;
													$tV19 += $row-> V19;
													$tV20 += $row-> V20;
													$tV21 += $row-> V21;
													$tV22 += $row-> V22;
													$tV23 += $row-> V23;
													$tV24 += $row-> V24;
													$tV25 += $row-> V25;
													$tV26 += $row-> V26;
													$tV27 += $row-> V27;
													$tV28 += $row-> V28;
													$tV29 += $row-> V29;
													$tV30 += $row-> V30;
													$tV31 += $row-> V31;
													$tV32 += $row-> V32;
													$tV33 += $row-> V33;
													$tV34 += $row-> V34;
													$tV35 += $row-> V35;
													$tV36 += $row-> V36;
													$tV37 += $row-> V37;
													$tV38 += $row-> V38;
													$tV39 += $row-> V39;
													$tV40 += $row-> V40;
												@endphp
												@endforeach
											</tbody>
											<tfoot>
												<tr>
													<td colspan='2'><strong>TOTAL</strong></td>
													<td><strong>{{ number_format($tsegel) }}</strong></td>
													<td><strong>{{ number_format($tV16) }}</strong></td>
													<td><strong>{{ number_format($tV32) }}</strong></td>
													<td><strong>{{ number_format($tV2) }}</strong></td>
													<td><strong>{{ number_format($tV24) }}</strong></td>
													<td><strong>{{ number_format($tV3) }}</strong></td>
													<td><strong>{{ number_format($tV7) }}</strong></td>
													<td><strong>{{ number_format($tV5) }}</strong></td>
													<td><strong>{{ number_format($tV31) }}</strong></td>
													<td><strong>{{ number_format($tV28) }}</strong></td>
													<td><strong>{{ number_format($tV29) }}</strong></td>
													<td><strong>{{ number_format($tV15) }}</strong></td>
													<td><strong>{{ number_format($tV33) }}</strong></td>
													<td><strong>{{ number_format($tV34) }}</strong></td>
													<td><strong>{{ number_format($tV35) }}</strong></td>
													<td><strong>{{ number_format($tV36) }}</strong></td>
													<td><strong>{{ number_format($tV37) }}</strong></td>
													<td><strong>{{ number_format($tV38) }}</strong></td>
													<td><strong>{{ number_format($tV39) }}</strong></td>
													<td><strong>{{ number_format($tV40) }}</strong></td>		
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