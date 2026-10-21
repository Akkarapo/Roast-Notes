@props(['quote', 'title' => null])

@use('App\Support\Money')

{{-- Same numbers on cart and checkout: both render one Quote. Amounts are satang. --}}
<aside {{ $attributes->merge(['class' => 'rounded-card border-2 border-cobalt bg-paper p-6']) }}>
    <h2 class="text-xl font-semibold">{{ $title ?? __('Summary') }}</h2>

    <dl class="mt-4 space-y-2.5 text-sm">
        <div class="flex justify-between">
            <dt>{{ __('Items (:count)', ['count' => $quote->itemCount()]) }}</dt>
            <dd class="font-semibold">{{ Money::bahtFromSatang($quote->subtotal) }}</dd>
        </div>

        @forelse ($quote->discounts as $discount)
            <div class="flex justify-between gap-4" data-discount="{{ $discount->source }}">
                <dt>{{ $discount->label }}</dt>
                <dd class="font-semibold">−{{ Money::bahtFromSatang($discount->amount) }}</dd>
            </div>
        @empty
            <div class="flex justify-between text-cobalt/60">
                <dt>{{ __('Discounts') }}</dt>
                <dd>—</dd>
            </div>
        @endforelse

        <div class="flex justify-between">
            <dt>{{ __('Shipping') }}</dt>
            <dd class="font-semibold">{{ $quote->shippingFee === 0 ? __('Free') : Money::bahtFromSatang($quote->shippingFee) }}</dd>
        </div>
    </dl>

    <div class="mt-4 flex items-baseline justify-between border-t-2 border-dashed border-cobalt pt-4">
        <span class="font-semibold">{{ __('Total') }}</span>
        <span class="font-script text-4xl" data-total>{{ Money::bahtFromSatang($quote->total) }}</span>
    </div>

    @if ($quote->freeShippingGap() !== null && ! $quote->isEmpty())
        <p class="mt-2 text-sm text-cobalt/75">{{ __(':amount more for free shipping', ['amount' => Money::bahtFromSatang($quote->freeShippingGap())]) }}</p>
    @endif

    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</aside>
