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
												<a class="nav-link " href="{{url('stock')}}" role="tab" >Stock All</a>
											</li>
											<li class="nav-item">
												<a class="nav-link " href="{{url('stocktap')}}" role="tab" >Stock Tap</a>
											</li>
											<li class="nav-item">
												<a class="nav-link active" href="{{url('stocksf')}}" role="tab" >Stock SF</a>
											</li>
										</ul>
									</div>
								<div class="card-body">
									<div class="col-auto mb-3">
										<a href="{{ url('exportexcelsf') }}" class="btn btn-success btn-sm btn-rounded"><i class='fas fa-file-export'></i>Export</a>
									</div>
									<div class="table-responsive">
										<table id="stock" class="display table table-striped table-hover" >
											<thead>
												<tr>
													<th>NO</th>
													<th>TAP</th>
													<th>SF</th>
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
													<th>GRAND TOTAL</th>
												</tr>
											</thead>
											<tbody>
												@foreach ($data as $row )
												<tr>
													<td>{{ $loop -> iteration }}</td>
													<td> {{ $row -> idtap }}</td>
													<td> {{ $row -> namasf }}</td>
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
													<td> {{number_format($row -> totalbaris)}} </td>
												</tr>
												@endforeach
											</tbody>
											<tfoot>
												<tr>
													<td colspan='3'><strong>TOTAL</strong></td>
													<td><strong>{{ number_format($gTotal['SEGEL']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V16']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V32']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V2']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V24']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V3']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V7']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V5'])}}</strong></td>
													<td><strong>{{ number_format($gTotal['V31']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V28']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V29']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V15']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V33']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V34']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V35']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V36']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V37']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V38']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V39']) }}</strong></td>
													<td><strong>{{ number_format($gTotal['V40']) }}</strong></td>																									
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