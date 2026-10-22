@use('App\Enums\OrderStatus')
@use('App\Support\Money')
<x-admin-layout :title="__('Orders')">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <nav class="flex flex-wrap gap-2" data-status-tabs>
            <a href="{{ route('admin.orders.index', array_filter(['q' => $search])) }}" @class(['rounded-full border-[1.5px] border-cobalt px-3.5 py-1.5 text-sm font-medium', 'bg-cobalt text-paper' => ! $status, 'hover:bg-paper' => $status])>
                {{ __('All') }} ({{ $counts->sum() }})
            </a>
            @foreach (OrderStatus::cases() as $case)
                <a href="{{ route('admin.orders.index', array_filter(['status' => $case->value, 'q' => $search])) }}" @class(['rounded-full border-[1.5px] border-cobalt px-3.5 py-1.5 text-sm font-medium', 'bg-cobalt text-paper' => $status === $case, 'hover:bg-paper' => $status !== $case])>
                    {{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})
                </a>
            @endforeach
        </nav>
        <form method="GET" class="flex gap-2">
            @if ($status)<input type="hidden" name="status" value="{{ $status->value }}">@endif
            <x-text-input name="q" :value="$search" placeholder="{{ __('Order no., name, phone') }}" class="w-64" />
            <x-secondary-button type="submit">{{ __('Search') }}</x-secondary-button>
        </form>
    </div>

    @if ($orders->isEmpty())
        <x-empty-state class="mt-6">{{ __('No orders match.') }}</x-empty-state>
    @else
        <x-admin.table class="mt-6">
            <thead>
                <tr><th>{{ __('Order') }}</th><th>{{ __('Date') }}</th><th>{{ __('Customer') }}</th><th class="text-right">{{ __('Items') }}</th><th class="text-right">{{ __('Total') }}</th><th>{{ __('Status') }}</th></tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold underline-offset-4 hover:underline">{{ $order->order_number }}</a></td>
                        <td class="whitespace-nowrap">{{ $order->placed_at->translatedFormat('j M Y · H:i') }}</td>
                        <td>{{ $order->user->name }}</td>
                        <td class="text-right">{{ $order->items_sum_quantity }}</td>
                        <td class="text-right font-semibold">{{ Money::baht($order->total) }}</td>
                        <td><x-order-status-badge :status="$order->status" /></td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
</x-admin-layout>
