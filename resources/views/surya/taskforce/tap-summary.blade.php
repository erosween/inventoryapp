<section class="tf-tap-summary card" aria-labelledby="tf-tap-summary-title">
    <header><div><h2 id="tf-tap-summary-title">Rekap Visit per TAP</h2><p>Sesuai filter · setiap outlet dihitung sekali.</p></div></header>
    <div class="tf-summary-scroll">
        <table>
            <thead><tr><th scope="col">TAP</th><th scope="col">Total Outlet</th><th scope="col">Sudah Visit</th><th scope="col">Belum Visit</th><th scope="col">Pencapaian</th></tr></thead>
            <tbody>
            @forelse($tapSummary as $row)
                <tr><th scope="row">{{ $row->tap ?: 'Tanpa TAP' }}</th><td>{{ number_format($row->total,0,',','.') }}</td><td class="tf-summary-done">{{ number_format($row->visited,0,',','.') }}</td><td class="tf-summary-pending">{{ number_format($row->pending,0,',','.') }}</td><td><div class="tf-summary-progress"><progress max="100" value="{{ $row->percentage }}" aria-label="Pencapaian visit {{ $row->tap }}"></progress><strong>{{ number_format($row->percentage,1,',','.') }}%</strong></div></td></tr>
            @empty
                <tr><td colspan="5">Belum ada outlet taskforce sesuai filter.</td></tr>
            @endforelse
            </tbody>
            @if($tapSummary->isNotEmpty())
            @php($total=$tapSummary->sum('total'))
            @php($visited=$tapSummary->sum('visited'))
            <tfoot><tr><th scope="row">GRAND TOTAL</th><td>{{ number_format($total,0,',','.') }}</td><td>{{ number_format($visited,0,',','.') }}</td><td>{{ number_format($total-$visited,0,',','.') }}</td><td>{{ number_format($total>0?$visited/$total*100:0,1,',','.') }}%</td></tr></tfoot>
            @endif
        </table>
    </div>
</section>
