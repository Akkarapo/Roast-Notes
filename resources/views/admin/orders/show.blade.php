@use('App\Enums\OrderStatus')
@use('App\Enums\Grind')
@use('App\Support\Money')
@php
    $next = $order->status->next();
    $action = match ($next) {
        OrderStatus::Confirmed => __('Confirm order'),
        OrderStatus::Shipped => __('Mark as shipped'),
        OrderStatus::InTransit => __('Mark as in transit'),
        OrderStatus::Delivered => __('Mark as delivered'),
        default => null,
    };
@endphp
<x-admin-layout :title="$order->order_number" script="order">
    <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold underline-offset-4 hover:underline">‹ {{ __('Orders') }}</a>

    <div class="mt-4 grid items-start gap-6 lg:grid-cols-[1fr_360px]">
        <article class="rounded-card border-2 border-cobalt bg-paper p-6">
            <header class="flex flex-wrap items-start justify-between gap-4 border-b-2 border-dashed border-cobalt pb-4">
                <div>
                    <p class="text-sm text-cobalt/70">{{ $order->placed_at->translatedFormat('j M Y · H:i') }}</p>
                    <p class="mt-1 font-semibold">
                        @if (Route::has('admin.users.show'))
                            <a href="{{ route('admin.users.show', $order->user) }}" class="underline-offset-4 hover:underline">{{ $order->user->name }}</a>
                        @else
                            {{ $order->user->name }}
                        @endif
                        <x-tier-badge :tier="$order->user->memberTier" class="ms-1" />
                    </p>
                    <p class="text-sm text-cobalt/80">{{ $order->user->email }}</p>
                </div>
                <x-order-status-badge :status="$order->status" class="text-sm" />
            </header>

            <section class="grid gap-4 border-b-2 border-dashed border-cobalt py-4 sm:grid-cols-2 text-sm">
                <div>
                    <h2 class="font-semibold">{{ __('Ship to') }}</h2>
                    <p class="mt-1">{{ $order->recipient_name }} · {{ $order->phone }}</p>
                    <p class="whitespace-pre-line text-cobalt/80">{{ $order->shipping_address }}</p>
                </div>
                @if ($order->note)
                    <div><h2 class="font-semibold">{{ __('Note') }}</h2><p class="mt-1 whitespace-pre-line">{{ $order->note }}</p></div>
                @endif
            </section>

            <table class="mt-4 w-full text-sm">
                <tbody class="divide-y-[1.5px] divide-dashed divide-cobalt/30">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="py-2">
                                <span class="font-semibold">{{ $item->product_name }}</span>
                                <span class="block text-cobalt/70">{{ \App\Enums\BagSize::from($item->size)->label() }}@if ($item->grind !== Grind::None) · {{ $item->grind->label() }}@endif · {{ Money::baht($item->unit_price) }}</span>
                            </td>
                            <td class="py-2 text-right">× {{ $item->quantity }}</td>
                            <td class="py-2 text-right font-semibold">{{ Money::baht($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <dl class="mt-4 ms-auto max-w-xs space-y-1.5 border-t-2 border-dashed border-cobalt pt-3 text-sm">
                <div class="flex justify-between"><dt>{{ __('Subtotal') }}</dt><dd class="font-semibold">{{ Money::baht($order->subtotal) }}</dd></div>
                @foreach ($order->discounts as $discount)
                    <div class="flex justify-between gap-4"><dt>{{ $discount->label }}</dt><dd class="font-semibold">−{{ Money::baht($discount->amount) }}</dd></div>
                @endforeach
                <div class="flex justify-between"><dt>{{ __('Shipping') }}</dt><dd class="font-semibold">{{ (float) $order->shipping_fee ? Money::baht($order->shipping_fee) : __('Free') }}</dd></div>
                <div class="flex items-baseline justify-between pt-1"><dt class="font-semibold">{{ __('Total') }}</dt><dd class="font-script text-3xl">{{ Money::baht($order->total) }}</dd></div>
            </dl>
        </article>

        <aside class="space-y-6">
            @if ($next)
                <form method="POST" action="{{ route('admin.orders.advance', $order) }}" class="space-y-3 rounded-card border-2 border-cobalt bg-paper p-5" data-advance>
                    @csrf
                    <input type="hidden" name="to" value="{{ $next->value }}">
                    <h2 class="text-sm font-semibold">{{ __('Next step') }}: {{ $next->label() }}</h2>
                    <x-text-input name="note" class="w-full" maxlength="255" placeholder="{{ $next === OrderStatus::Shipped ? __('Tracking number (optional)') : __('Note (optional)') }}" />
                    <x-input-error :messages="$errors->get('status')" />
                    <x-primary-button class="w-full justify-center">{{ $action }} →</x-primary-button>
                </form>
            @endif

            @if ($order->status->canTransitionTo(OrderStatus::Cancelled))
                <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="space-y-3 rounded-card border-2 border-dashed border-brick bg-paper p-5" data-cancel
                      onsubmit="return confirm(@js(__('Cancel this order and return the stock?')))">
                    @csrf
                    <h2 class="text-sm font-semibold text-brick">{{ __('Cancel order') }}</h2>
                    <x-text-input name="reason" class="w-full" maxlength="255" required placeholder="{{ __('Reason (shown to the customer)') }}" />
                    <x-input-error :messages="$errors->get('reason')" />
                    <x-danger-button class="w-full justify-center">{{ __('Cancel and return stock') }}</x-danger-button>
                </form>
            @endif

            <section class="rounded-card border-2 border-cobalt bg-paper p-5">
                <h2 class="mb-3 text-sm font-semibold">{{ __('History') }}</h2>
                <x-order-timeline :changes="$order->statusChanges" show-who />
            </section>
        </aside>
    </div>
</x-admin-layout>
