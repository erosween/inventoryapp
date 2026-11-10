<thead>
    <tr>
        <th scope="colgroup" colspan="{{ count($labels) }}" data-report-group="{{ count($labels) }}">{{ count($labels) === 1 ? 'Wilayah' : 'Profil Outlet' }}</th>
        @foreach($metrics as $label)
            <th scope="colgroup" colspan="{{ count($columns) }}" data-report-group="{{ count($columns) }}">{{ $label }}</th>
        @endforeach
    </tr>
    <tr data-report-columns>
        @foreach($labels as $key => $label)
            <th scope="col" data-report-key="{{ $key }}">{{ $label }}</th>
        @endforeach
        @foreach($metrics as $metric => $label)
            @foreach($columns as $key => $heading)
                <th scope="col" data-report-key="{{ $key }}" aria-label="{{ $label }} {{ $heading }}">{{ $heading }}</th>
            @endforeach
        @endforeach
    </tr>
</thead>
