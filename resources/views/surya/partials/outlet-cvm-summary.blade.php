{{--
    Fragmen rekap TAP CVM untuk pemuatan terpisah dari halaman utama.
--}}    <section class="ngrs-card" aria-label="Rekap CVM per TAP">
        <div class="ngrs-scroll" tabindex="0">
            <table class="ngrs-summary cvm-transactions-table" data-transaction-table="summary" data-report-end-month="{{ $cutoff->month }}" data-report-default-range="12">
                @include('surya.partials.outlet-cvm-head', ['labels' => ['tap' => 'TAP']])
                <tbody>
                    @foreach($summary as $name => $series)
                        <tr><th scope="row">{{ $name ?: 'Tanpa TAP' }}</th>@include('surya.partials.outlet-cvm-values')</tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @foreach($clusters as $name => $series)
                        <tr class="ngrs-cluster"><th scope="row"><small>Cluster</small>{{ $name }}</th>@include('surya.partials.outlet-cvm-values')</tr>
                    @endforeach
                    <tr class="ngrs-grand">
                        <th scope="row">Grand Total</th>
                        @include('surya.partials.outlet-cvm-values', ['series' => $grand])
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
