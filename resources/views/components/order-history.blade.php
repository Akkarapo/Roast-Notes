@props(['orders', 'route' => 'orders.show'])

@use('App\Support\Money')

<div class="overflow-x-auto rounded-card border-2 border-cobalt bg-paper">
    <table class="w-full min-w-[560px] text-sm">
        <thead class="border-b-2 border-cobalt text-left">
            <tr>
                <th class="px-5 py-3 font-semibold">{{ __('Order') }}</th>
                <th class="px-5 py-3 font-semibold">{{ __('Date') }}</th>
                <th class="px-5 py-3 font-semibold">{{ __('Status') }}</th>
                <th class="px-5 py-3 text-right font-semibold">{{ __('Items') }}</th>
                <th class="px-5 py-3 text-right font-semibold">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y-[1.5px] divide-dashed divide-cobalt/30">
            @foreach ($orders as $order)
                <tr class="hover:bg-cream">
                    <td class="px-5 py-3"><a href="{{ route($route, $order) }}" class="font-semibold underline-offset-4 hover:underline">{{ $order->order_number }}</a></td>
                    <td class="px-5 py-3">{{ $order->placed_at->translatedFormat('j M Y') }}</td>
                    <td class="px-5 py-3"><x-order-status-badge :status="$order->status" /></td>
                    <td class="px-5 py-3 text-right">{{ (int) $order->items_sum_quantity }}</td>
                    <td class="px-5 py-3 text-right font-semibold">{{ Money::baht($order->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
