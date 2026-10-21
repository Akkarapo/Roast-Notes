@use('App\Enums\ProductType')
@use('App\Support\Money')

<x-app-layout>
    <x-slot name="header">
        <x-page-title script="your bag">{{ __('Cart') }}</x-page-title>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if ($errors->has('cart'))
            <div class="mb-6 rounded-[14px] border-2 border-brick bg-paper px-5 py-3 text-brick" role="alert">
                <p class="font-semibold">{{ __('We could not place your order:') }}</p>
                <ul class="mt-1 list-disc ps-5 text-sm">
                    @foreach ($errors->get('cart') as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($quote->isEmpty())
            <x-empty-state>
                {{ __('Your cart is empty.') }}
                <x-slot name="action">
                    <a href="{{ route('products.index') }}" class="inline-flex px-6 py-2.5 rounded-full border-2 border-cobalt bg-cobalt text-paper font-semibold hover:bg-cobalt-ink">{{ __('Shop beans') }} →</a>
                </x-slot>
            </x-empty-state>
        @else
            <div class="grid items-start gap-8 lg:grid-cols-[1fr_360px]">
                <ul class="divide-y-2 divide-dashed divide-cobalt/40 rounded-card border-2 border-cobalt bg-paper">
                    @foreach ($quote->lines as $line)
                        @php
                            $item = $line->item;
                            $isDrip = $line->product->type === ProductType::DripBag;
                        @endphp
                        <li class="flex gap-4 p-5" data-cart-item="{{ $item->id }}">
                            <a href="{{ route('products.show', $line->product) }}" class="grid h-20 w-20 shrink-0 place-items-center rounded-[14px] bg-cobalt text-paper">
                                <x-dynamic-component :component="$isDrip ? 'illustrations.drip-bag' : 'illustrations.bean-bag'" class="h-14 w-auto" />
                            </a>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <a href="{{ route('products.show', $line->product) }}" class="font-semibold hover:underline">{{ $line->product->name }}</a>
                                        <p class="text-sm text-cobalt/75">{{ $line->variant->size->label() }} · {{ Money::bahtFromSatang($line->unitPrice) }}</p>
                                    </div>
                                    <p class="font-semibold">{{ Money::bahtFromSatang($line->lineTotal) }}</p>
                                </div>

                                <form method="POST" action="{{ route('cart.items.update', $item) }}" x-data x-on:change="$el.requestSubmit()" class="mt-3 flex flex-wrap items-center gap-3">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="_item" value="{{ $item->id }}">
                                    @unless ($isDrip)
                                        <select name="grind" aria-label="{{ __('Grind') }}" class="rounded-full border-2 border-cobalt/40 bg-paper py-1.5 pl-4 pr-9 text-sm text-cobalt focus:border-cobalt focus:ring-0">
                                            @foreach (\App\Enums\Grind::forBeans() as $grind)
                                                <option value="{{ $grind->value }}" @selected($line->grind === $grind)>{{ $grind->label() }}</option>
                                            @endforeach
                                        </select>
                                    @endunless
                                    <x-quantity-stepper :value="$line->quantity" :max="min(\App\Models\CartItem::MAX_QUANTITY, max(1, $line->variant->stock))" autosubmit />
                                    <noscript><x-secondary-button type="submit">{{ __('Update') }}</x-secondary-button></noscript>
                                </form>

                                @if (! $line->product->is_active)
                                    <p class="mt-2 text-sm text-brick">{{ __('This product is no longer available.') }}</p>
                                @elseif ($item->exceedsStock())
                                    <p class="mt-2 text-sm text-brick">{{ $line->variant->stock === 0 ? __('This size is sold out.') : __('Only :stock left in stock.', ['stock' => $line->variant->stock]) }}</p>
                                @endif
                                @if ($errors->has('quantity') && old('_item') == $item->id)
                                    <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                                @endif

                                <form method="POST" action="{{ route('cart.items.destroy', $item) }}" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-cobalt/70 underline underline-offset-4 hover:text-cobalt">{{ __('Remove') }}</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <x-order-summary :quote="$quote" class="lg:sticky lg:top-6">
                    <x-slot name="action">
                        @if ($canCheckout)
                            <a href="{{ route('checkout.create') }}" class="flex w-full justify-center rounded-full border-2 border-cobalt bg-cobalt px-6 py-2.5 font-semibold text-paper hover:bg-cobalt-ink">{{ __('Go to checkout') }} →</a>
                        @else
                            <x-primary-button class="w-full" disabled>{{ __('Go to checkout') }}</x-primary-button>
                            <p class="mt-2 text-sm text-brick">{{ __('Fix the items marked above first.') }}</p>
                        @endif
                    </x-slot>
                </x-order-summary>
            </div>
        @endif
    </div>
</x-app-layout>
