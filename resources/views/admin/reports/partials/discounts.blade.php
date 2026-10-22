@use('App\Support\Money')
@use('App\Support\Reports\Discounts')
@if (! $rows)
    <x-empty-state>{{ __('No discounts in this period.') }}</x-empty-state>
@else
    <x-admin.table data-discounts>
        <thead><tr><th>{{ __('Kind') }}</th><th>{{ __('Promotion') }}</th><th class="text-right">{{ __('Orders') }}</th><th class="text-right">{{ __('Discount') }}</th></tr></thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ Discounts::sourceLabel($row['source']) }}</td>
                    <td><span class="font-semibold">{{ $row['name'] }}</span>@if ($row['label'] !== $row['name']) <span class="block text-xs text-cobalt/70">{{ $row['label'] }}</span>@endif</td>
                    <td class="text-right tabular-nums">{{ $row['orders'] }}</td>
                    <td class="text-right font-semibold tabular-nums">−{{ Money::bahtFromSatang($row['amount']) }}</td>
                </tr>
            @endforeach
            <tr class="font-semibold"><td colspan="2">{{ __('All discounts') }}</td><td></td><td class="text-right tabular-nums">−{{ Money::bahtFromSatang(array_sum(array_column($rows, 'amount'))) }}</td></tr>
        </tbody>
    </x-admin.table>
@endif
