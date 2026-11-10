@foreach($metrics as $metric => $label)
    @foreach($columns as $key => $heading)
        @php
            $value = $series[$metric][$key] ?? null;
            $percent = str_starts_with($key, 'mom_pct_');
            $gap = $percent || str_starts_with($key, 'gap_');
            $tone = $value === null ? 'missing' : ($value < 0 ? 'negative' : ($value > 0 ? 'positive' : 'flat'));
        @endphp
        <td class="{{ $gap ? 'ngrs-gap is-'.$tone : (['total' => 'ngrs-total', 'compare' => 'ngrs-mom-helper'][$key] ?? '') }}">
            @if($value === null)
                —
            @elseif($percent)
                {{ ($value > 0 ? '▲ +' : ($value < 0 ? '▼ -' : '')).number_format(abs($value), 1).'%' }}
            @else
                {{ number_format($value) }}
            @endif
        </td>
    @endforeach
@endforeach
