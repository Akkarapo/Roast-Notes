@use('App\Support\Money')
@if (! $rows)
    <x-empty-state>{{ __('No sales in this period.') }}</x-empty-state>
@else
    @php
        $max = max(array_column($rows, 'units')) ?: 1;
    @endphp
    <x-admin.table data-top-products>
        <thead><tr><th class="w-10">#</th><th>{{ __('Product') }}</th><th class="w-2/5">{{ __('Units') }}</th><th class="text-right">{{ __('Sales') }}</th></tr></thead>
        <tbody>
            @foreach ($rows as $i => $row)
                <tr>
                    <td class="font-script text-xl">{{ $i + 1 }}</td>
                    <td>
                        <a href="{{ route('products.show', $row['slug']) }}" class="font-semibold underline-offset-4 hover:underline" target="_blank">{{ $row['name'] }}</a>
                        <span class="block text-xs text-cobalt/70">{{ $row['type']->label() }} · {{ $row['roast']->label() }}</span>
                    </td>
                    <td>
                        <span class="flex items-center gap-2" title="{{ $row['name'] }}: {{ $row['units'] }}">
                            <span class="block h-3 rounded-r-[4px] bg-cobalt" style="width: {{ max(1, $row['units'] / $max * 85) }}%"></span>
                            <span class="text-xs font-semibold tabular-nums">{{ number_format($row['units']) }}</span>
                        </span>
                    </td>
                    <td class="text-right tabular-nums">{{ Money::bahtFromSatang($row['revenue']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </x-admin.table>
@endif
