@use('App\Support\Money')

<x-app-layout>
    <x-slot name="header">
        <x-page-title script="almost there">{{ __('Checkout') }}</x-page-title>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid items-start gap-8 lg:grid-cols-[1fr_380px]">
            <form id="checkout-form" method="POST" action="{{ route('checkout.store') }}" class="space-y-5 rounded-card border-2 border-cobalt bg-paper p-6">
                @csrf
                <h2 class="text-xl font-semibold">{{ __('Shipping details') }}</h2>

                <div>
                    <x-input-label for="recipient_name" :value="__('Recipient name')" />
                    <x-text-input id="recipient_name" name="recipient_name" class="mt-1 block w-full" :value="old('recipient_name', $user->name)" required maxlength="100" autocomplete="name" />
                    <x-input-error :messages="$errors->get('recipient_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="phone" :value="__('Phone')" />
                    <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->phone)" required maxlength="20" autocomplete="tel" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="shipping_address" :value="__('Shipping Address')" />
                    <x-textarea-input id="shipping_address" name="shipping_address" rows="3" class="mt-1 block w-full" required maxlength="500" autocomplete="street-address">{{ old('shipping_address', $user->address) }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('shipping_address')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="note" :value="__('Note to the roaster (optional)')" />
                    <x-textarea-input id="note" name="note" rows="2" class="mt-1 block w-full" maxlength="500">{{ old('note') }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('note')" class="mt-2" />
                </div>

                <p class="text-sm text-cobalt/70">{{ __('No online payment yet — the shop confirms your order and contacts you.') }}</p>
            </form>

            <x-order-summary :quote="$quote" class="lg:sticky lg:top-6">
                <ul class="mb-4 space-y-2 border-b-2 border-dashed border-cobalt/40 pb-4 text-sm">
                    @foreach ($quote->lines as $line)
                        <li class="flex justify-between gap-3">
                            <span>{{ $line->product->name }} · {{ $line->variant->size->label() }}@if ($line->grind !== \App\Enums\Grind::None) · {{ $line->grind->label() }}@endif × {{ $line->quantity }}</span>
                            <span class="shrink-0 font-semibold">{{ Money::bahtFromSatang($line->lineTotal) }}</span>
                        </li>
                    @endforeach
                </ul>

                <x-slot name="action">
                    @if ($couponCode)
                        <p class="mb-3 text-sm" data-coupon-applied>{{ __('Coupon :code applied', ['code' => $couponCode]) }} · <a href="{{ route('cart.index') }}#coupon" class="underline underline-offset-4">{{ __('change in cart') }}</a></p>
                    @endif
                    <x-primary-button form="checkout-form" class="w-full">{{ __('Place order') }}</x-primary-button>
                    <a href="{{ route('cart.index') }}" class="mt-3 block text-center text-sm underline underline-offset-4">‹ {{ __('Back to cart') }}</a>
                </x-slot>
            </x-order-summary>
        </div>
    </div>
</x-app-layout>
