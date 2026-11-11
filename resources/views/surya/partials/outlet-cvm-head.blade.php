{{--
    Header bertingkat untuk rekap dan detail CVM. Urutan metrik mengikuti kolom pada outlet-cvm-values.
--}}<thead>
    <tr>
        <th scope="colgroup" colspan="{{ count($labels) }}" data-report-group="{{ count($labels) }}">{{ count($labels) === 1 ? 'Wilayah' : 'Profil Outlet' }}</th>
        @foreach($metrics as $label)
            <th scope="colgroup" colspan="{{ count($columns) }}" data-report-group="{{ count($columns) }}">{{ $label }}</th>
        @endforeach
    </tr>
    <tr data-report-columns>
        @foreach($labels as $key => $label)
            <th scope="col" data-report-key="{{ $key }}" @class([($filterable ?? false) ? ($frozenColumns[$key] ?? '') : ''])>
                @if(($filterable ?? false) && $key !== 'row_number')
                    @include('surya.partials.outlet-column-heading', ['column' => $key])
                @else
                    {{ $label }}
                @endif
            </th>
        @endforeach
        @foreach($metrics as $metric => $label)
            @foreach($columns as $key => $heading)
                <th scope="col" data-report-key="{{ $key }}" aria-label="{{ $label }} {{ $heading }}">
                    @if($filterable ?? false)
                        @include('surya.partials.outlet-column-heading', [
                            'column' => $metric.'_'.$key,
                            'filterLabel' => $label.' '.$heading,
                            'label' => $heading,
                        ])
                    @else
                        {{ $heading }}
                    @endif
                </th>
            @endforeach
        @endforeach
    </tr>
</thead>
