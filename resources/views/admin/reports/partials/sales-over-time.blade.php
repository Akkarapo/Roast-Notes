@use('App\Support\Money')
@if (collect($rows)->sum('orders') === 0)
    <x-empty-state>{{ __('No sales in this period.') }}</x-empty-state>
@else
    <x-chart.bars :items="$bars" prefix="฿" :label="__('Sales per :unit', ['unit' => $granularity === 'day' ? __('day') : __('month')])" />
    <details class="mt-2 text-sm">
        <summary class="cursor-pointer font-semibold">{{ __('Table') }}</summary>
        <x-admin.table class="mt-2">
            <thead><tr><th>{{ $granularity === 'day' ? __('Day') : __('Month') }}</th><th class="text-right">{{ __('Orders') }}</th><th class="text-right">{{ __('Sales') }}</th></tr></thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr><td>{{ $row['title'] }}</td><td class="text-right">{{ $row['orders'] }}</td><td class="text-right">{{ Money::bahtFromSatang($row['revenue']) }}</td></tr>
                @endforeach
            </tbody>
        </x-admin.table>
    </details>
@endif
