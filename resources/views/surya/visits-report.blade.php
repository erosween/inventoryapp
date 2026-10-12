@extends('layout.layout')

@section('content')
<div class="main-panel"><div class="content"><div class="page-inner">
    <div class="card premium-card">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h4 class="card-title mb-1 fw-bold">SURYA · Reporting Visit</h4><small class="text-muted">Visit terakhir serta foto tampak depan dan dalam outlet</small></div>
            <form class="d-flex gap-2" method="GET">
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                <button class="btn btn-primary btn-sm">Filter</button>
            </form>
        </div>
        <div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover mb-0 align-middle"><thead><tr><th>Outlet</th><th>TAP</th><th>SF</th><th>Visit Terakhir</th><th>Status GPS</th><th>Jarak</th><th>Tampak Depan</th><th>Tampak Dalam</th><th>Aksi</th></tr></thead><tbody>
            @forelse($visits as $visit)
                <tr><td><strong>{{ $visit->nama_outlet ?? $visit->id_outlet }}</strong><br><small class="text-muted">{{ $visit->id_outlet }}</small></td><td>{{ $visit->idtap }}</td><td>{{ $visit->namasf ?? $visit->idsf }}</td><td>{{ \Carbon\Carbon::parse($visit->visited_at)->format('d M Y H:i') }}</td><td><span class="badge {{ in_array($visit->gps_status, ['valid', 'approved']) ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($visit->gps_status) }}</span></td><td>{{ $visit->distance_meters !== null ? number_format($visit->distance_meters, 0) . ' m' : 'Belum ada koordinat' }}</td><td>@php($frontPhoto = $visit->photo_front_path ?? $visit->photo_path)<span>@if($frontPhoto)<a href="{{ asset('storage/' . $frontPhoto) }}" target="_blank"><img src="{{ asset('storage/' . $frontPhoto) }}" alt="Foto outlet tampak depan" style="width:72px;height:54px;object-fit:cover;border-radius:8px"></a>@else—@endif</span></td><td>@if($visit->photo_inside_path)<a href="{{ asset('storage/' . $visit->photo_inside_path) }}" target="_blank"><img src="{{ asset('storage/' . $visit->photo_inside_path) }}" alt="Foto outlet tampak dalam" style="width:72px;height:54px;object-fit:cover;border-radius:8px"></a>@else—@endif</td><td>@if($visit->gps_status === 'pending_admin')<form method="POST" action="{{ route('surya.visits.approve', $visit->id) }}">@csrf<button class="btn btn-sm btn-success">Setujui &amp; Perbarui Koordinat</button></form>@else—@endif</td></tr>
            @empty <tr><td colspan="9" class="text-center text-muted py-5">Belum ada data visit.</td></tr>@endforelse
            </tbody></table>
        </div></div><div class="card-footer bg-white">{{ $visits->links() }}</div>
    </div>
</div></div></div>
@endsection
