@extends('layout.layout')

@section('content')
<style>
    .visit-photo-thumb {
        width: 72px;
        height: 54px;
        object-fit: cover;
        border-radius: 8px;
        display: block;
    }

    .visit-photo-open {
        appearance: none;
        padding: 0;
        border: 0;
        border-radius: 8px;
        background: transparent;
        cursor: zoom-in;
        box-shadow: 0 0 0 1px rgba(15, 23, 42, .08);
    }

    .visit-photo-open:focus-visible {
        outline: 3px solid rgba(78, 115, 223, .3);
        outline-offset: 3px;
    }

    .visit-photo-viewer[hidden] {
        display: none;
    }

    .visit-photo-viewer {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: grid;
        grid-template-rows: auto minmax(0, 1fr);
        background: rgba(10, 15, 25, .94);
    }

    .visit-photo-viewer__toolbar {
        min-height: 62px;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #fff;
        background: #111827;
    }

    .visit-photo-viewer__title {
        min-width: 0;
        margin-right: auto;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-weight: 700;
    }

    .visit-photo-viewer__stage {
        min-width: 0;
        min-height: 0;
        padding: 20px;
        overflow: auto;
        display: flex;
        align-items: flex-start;
        justify-content: center;
    }

    .visit-photo-viewer__image {
        width: auto;
        max-width: 100%;
        max-height: calc(100vh - 102px);
        object-fit: contain;
        border-radius: 10px;
        background: #fff;
        transform-origin: top center;
        transition: transform .15s ease;
    }

    body.visit-photo-viewer-open {
        overflow: hidden !important;
    }
</style>

<div class="main-panel">
    <div class="content">
        <div class="page-inner">
            <div class="card premium-card">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h4 class="card-title mb-1 fw-bold">SURYA · Reporting Visit</h4>
                        <small class="text-muted">Visit terakhir serta foto tampak depan dan dalam outlet</small>
                    </div>
                    <form class="d-flex gap-2" method="GET">
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                        <button class="btn btn-primary btn-sm">Filter</button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Outlet</th><th>TAP</th><th>SF</th><th>Visit Terakhir</th><th>Status GPS</th><th>Jarak</th><th>Tampak Depan</th><th>Tampak Dalam</th><th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($visits as $visit)
                                    <tr>
                                        <td><strong>{{ $visit->nama_outlet ?? $visit->id_outlet }}</strong><br><small class="text-muted">{{ $visit->id_outlet }}</small></td>
                                        <td>{{ $visit->idtap }}</td>
                                        <td>{{ $visit->namasf ?? $visit->idsf }}</td>
                                        <td>{{ \Carbon\Carbon::parse($visit->visited_at)->format('d M Y H:i') }}</td>
                                        <td><span class="badge {{ in_array($visit->gps_status, ['valid', 'approved']) ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($visit->gps_status) }}</span></td>
                                        <td>{{ $visit->distance_meters !== null ? number_format($visit->distance_meters, 0) . ' m' : 'Belum ada koordinat' }}</td>
                                        <td>
                                            @if($visit->front_photo_url)
                                                <button type="button" class="visit-photo-open" data-photo-src="{{ $visit->front_photo_url }}" data-photo-title="{{ $visit->nama_outlet ?? $visit->id_outlet }} · Tampak Depan">
                                                    <img src="{{ $visit->front_thumbnail_url }}" loading="lazy" decoding="async" alt="Foto outlet tampak depan" class="visit-photo-thumb">
                                                </button>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if($visit->inside_photo_url)
                                                <button type="button" class="visit-photo-open" data-photo-src="{{ $visit->inside_photo_url }}" data-photo-title="{{ $visit->nama_outlet ?? $visit->id_outlet }} · Tampak Dalam">
                                                    <img src="{{ $visit->inside_thumbnail_url }}" loading="lazy" decoding="async" alt="Foto outlet tampak dalam" class="visit-photo-thumb">
                                                </button>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if($visit->gps_status === 'pending_admin')
                                                <form method="POST" action="{{ route('surya.visits.approve', $visit->id) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success">Setujui &amp; Perbarui Koordinat</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-5">Belum ada data visit.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white">{{ $visits->links() }}</div>
            </div>
        </div>
    </div>
</div>

<div id="visit-photo-viewer" class="visit-photo-viewer" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-label="Pratinjau foto visit">
    <div class="visit-photo-viewer__toolbar">
        <span id="visit-photo-viewer-title" class="visit-photo-viewer__title">Foto Visit</span>
        <button type="button" class="btn btn-sm btn-light" data-photo-zoom-out aria-label="Perkecil foto"><i class="fas fa-search-minus"></i></button>
        <button type="button" class="btn btn-sm btn-light" data-photo-reset>100%</button>
        <button type="button" class="btn btn-sm btn-light" data-photo-zoom-in aria-label="Perbesar foto"><i class="fas fa-search-plus"></i></button>
        <button type="button" class="btn btn-sm btn-danger" data-photo-close aria-label="Tutup pratinjau"><i class="fas fa-times"></i></button>
    </div>
    <div class="visit-photo-viewer__stage">
        <img id="visit-photo-viewer-image" class="visit-photo-viewer__image" alt="Foto visit">
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const viewer = document.getElementById('visit-photo-viewer');
    const image = document.getElementById('visit-photo-viewer-image');
    const title = document.getElementById('visit-photo-viewer-title');
    const resetButton = viewer.querySelector('[data-photo-reset]');
    let zoom = 1;

    const applyZoom = function () {
        image.style.transform = 'scale(' + zoom + ')';
        resetButton.textContent = Math.round(zoom * 100) + '%';
    };

    const closeViewer = function () {
        viewer.hidden = true;
        viewer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('visit-photo-viewer-open');
        image.removeAttribute('src');
        zoom = 1;
        applyZoom();
    };

    document.addEventListener('click', function (event) {
        const opener = event.target.closest('.visit-photo-open');
        if (!opener) return;

        zoom = 1;
        image.src = opener.dataset.photoSrc;
        image.alt = opener.dataset.photoTitle || 'Foto visit';
        title.textContent = opener.dataset.photoTitle || 'Foto Visit';
        viewer.hidden = false;
        viewer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('visit-photo-viewer-open');
        applyZoom();
    });

    viewer.querySelector('[data-photo-zoom-in]').addEventListener('click', function () {
        zoom = Math.min(3, zoom + .25);
        applyZoom();
    });
    viewer.querySelector('[data-photo-zoom-out]').addEventListener('click', function () {
        zoom = Math.max(.5, zoom - .25);
        applyZoom();
    });
    resetButton.addEventListener('click', function () {
        zoom = 1;
        applyZoom();
    });
    viewer.querySelector('[data-photo-close]').addEventListener('click', closeViewer);
    viewer.addEventListener('click', function (event) {
        if (event.target === viewer) closeViewer();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !viewer.hidden) closeViewer();
    });
});
</script>
@endpush
