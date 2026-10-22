@use('App\Enums\Grind')
@use('App\Support\Money')

<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <a href="{{ route('orders.index') }}" class="text-sm font-semibold underline-offset-4 hover:underline">‹ {{ __('My orders') }}</a>

        <article class="mt-5 rounded-card border-2 border-cobalt bg-paper p-6 sm:p-8">
            <header class="flex flex-wrap items-start justify-between gap-4 border-b-2 border-dashed border-cobalt pb-5">
                <div>
                    <p class="text-sm text-cobalt/70">{{ __('Order') }}</p>
                    <h1 class="font-script text-4xl">{{ $order->order_number }}</h1>
                    <p class="mt-1 text-sm">{{ $order->placed_at->translatedFormat('j M Y · H:i') }}</p>
                </div>
                <x-order-status-badge :status="$order->status" class="text-sm" />
            </header>

            @if ($order->statusChanges->isNotEmpty())
                <section class="border-b-2 border-dashed border-cobalt py-5">
                    <h2 class="mb-3 text-sm font-semibold">{{ __('Order status') }}</h2>
                    <x-order-timeline :changes="$order->statusChanges" />
                </section>
            @endif

            <section class="grid gap-6 border-b-2 border-dashed border-cobalt py-5 sm:grid-cols-2">
                <div>
                    <h2 class="text-sm font-semibold">{{ __('Ship to') }}</h2>
                    <p class="mt-1">{{ $order->recipient_name }} · {{ $order->phone }}</p>
                    <p class="whitespace-pre-line text-sm text-cobalt/80">{{ $order->shipping_address }}</p>
                </div>
                @if ($order->note)
                    <div>
                        <h2 class="text-sm font-semibold">{{ __('Note') }}</h2>
                        <p class="mt-1 text-sm text-cobalt/80">{{ $order->note }}</p>
                    </div>
                @endif
            </section>

            <table class="mt-5 w-full text-sm">
                <thead class="text-left text-cobalt/70">
                    <tr>
                        <th class="pb-2 font-medium">{{ __('Item') }}</th>
                        <th class="pb-2 text-right font-medium">{{ __('Qty') }}</th>
                        <th class="pb-2 text-right font-medium">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y-[1.5px] divide-dashed divide-cobalt/30">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="py-2.5">
                                <span class="font-semibold">{{ $item->product_name }}</span>
                                <span class="block text-cobalt/70">{{ \App\Enums\BagSize::from($item->size)->label() }}@if ($item->grind !== Grind::None) · {{ $item->grind->label() }}@endif · {{ Money::baht($item->unit_price) }}</span>
                                @if ($order->status === \App\Enums\OrderStatus::Delivered)
                                    @if ($item->review)
                                        <a href="{{ route('reviews.edit', $item->review) }}" class="mt-1.5 inline-flex items-center gap-2 text-xs font-semibold underline-offset-4 hover:underline" data-review-edit>
                                            <x-star-rating :rating="$item->review->rating" /> {{ __('Edit review') }}
                                        </a>
                                    @else
                                        <a href="{{ route('reviews.create', $item) }}" class="mt-1.5 inline-flex rounded-full border-[1.5px] border-cobalt px-3 py-1 text-xs font-semibold hover:bg-cobalt hover:text-paper" data-review-create>★ {{ __('Write a review') }}</a>
                                    @endif
                                @endif
                            </td>
                            <td class="py-2.5 text-right">{{ $item->quantity }}</td>
                            <td class="py-2.5 text-right font-semibold">{{ Money::baht($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <dl class="mt-5 ms-auto max-w-xs space-y-2 border-t-2 border-dashed border-cobalt pt-4 text-sm">
                <div class="flex justify-between"><dt>{{ __('Subtotal') }}</dt><dd class="font-semibold">{{ Money::baht($order->subtotal) }}</dd></div>
                @foreach ($order->discounts as $discount)
                    <div class="flex justify-between gap-4"><dt>{{ $discount->label }}</dt><dd class="font-semibold">−{{ Money::baht($discount->amount) }}</dd></div>
                @endforeach
                <div class="flex justify-between"><dt>{{ __('Shipping') }}</dt><dd class="font-semibold">{{ (float) $order->shipping_fee === 0.0 ? __('Free') : Money::baht($order->shipping_fee) }}</dd></div>
                <div class="flex items-baseline justify-between pt-2"><dt class="font-semibold">{{ __('Total') }}</dt><dd class="font-script text-3xl">{{ Money::baht($order->total) }}</dd></div>
            </dl>

            @if ($order->status->isCancellableByMember())
                <form method="POST" action="{{ route('orders.cancel', $order) }}" class="mt-6 text-right"
                      x-data @submit="if (! window.confirm(@js(__('Cancel this order?')))) $event.preventDefault()">
                    @csrf
                    <x-secondary-button type="submit">{{ __('Cancel order') }}</x-secondary-button>
                    <x-input-error :messages="$errors->get('order')" class="mt-2" />
                </form>
            @endif
        </article>
    </div>
</x-app-layout>
