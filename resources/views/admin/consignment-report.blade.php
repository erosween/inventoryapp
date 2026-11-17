{{-- Rekap harian nomor konsinyasi SURYA untuk admin Web Inventory. --}}
@extends('layout.layout')

@section('content')
<div class="main-panel">
    <div class="content">
        <div class="page-inner">
            <div class="page-header">
                <h4 class="page-title text-indigo fw-bold">Rekap Konsinyasi</h4>
                <ul class="breadcrumbs">
                    <li class="nav-home"><a href="{{ url('home') }}"><i class="flaticon-home text-indigo"></i></a></li>
                    <li class="separator"><i class="flaticon-right-arrow"></i></li>
                    <li class="nav-item">Sales Force</li>
                    <li class="separator"><i class="flaticon-right-arrow"></i></li>
                    <li class="nav-item">Rekap Konsinyasi</li>
                </ul>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card premium-card card-round border-0 shadow-sm">
                        <div class="card-header border-bottom py-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 12px;">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light-indigo p-2 rounded-3 me-3 text-indigo"><i class="fas fa-sim-card"></i></div>
                                    <div>
                                        <h5 class="fw-bold mb-0">Nomor Konsinyasi Harian</h5>
                                        <small class="text-muted">{{ number_format($totalNumbers, 0, ',', '.') }} nomor unik pada tanggal terpilih</small>
                                    </div>
                                </div>
                                <form method="get" action="{{ route('admin.consignment-report') }}" class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                                    <label for="consignment-date" class="mb-0 small fw-bold text-muted">Tanggal input</label>
                                    <input type="date" id="consignment-date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}" max="{{ now()->toDateString() }}" style="width: 165px;">
                                    <button type="submit" class="btn btn-indigo btn-sm btn-round"><i class="fas fa-filter me-1"></i> Tampilkan</button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-indigo w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>WAKTU INPUT</th>
                                            <th>TAP</th>
                                            <th>ID OUTLET</th>
                                            <th>NAMA OUTLET</th>
                                            <th>SALES FORCE</th>
                                            <th>DENOM</th>
                                            <th>NOMOR SERIAL</th>
                                            <th>STATUS</th>
                                            <th>CATATAN</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($rows as $row)
                                            <tr>
                                                <td class="text-nowrap">{{ \Carbon\Carbon::parse($row->reported_at)->format('H:i') }}</td>
                                                <td>{{ $row->idtap }}</td>
                                                <td class="text-nowrap">{{ $row->id_outlet }}</td>
                                                <td>{{ $row->nama_outlet ?: '—' }}</td>
                                                <td>{{ $row->namasf ?: $row->idsf }}</td>
                                                <td>{{ $row->denom ?: $row->iddenom ?: '—' }}</td>
                                                <td class="font-monospace fw-bold">{{ $row->serial_number }}</td>
                                                <td><span class="badge badge-{{ $row->status === 'approved' ? 'success' : ($row->status === 'consigned' ? 'info' : 'secondary') }}">{{ strtoupper($row->status) }}</span></td>
                                                <td>{{ $row->notes ?: '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="9" class="text-center text-muted py-5">Belum ada input konsinyasi pada tanggal ini.</td></tr>
                                        @endforelse
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
