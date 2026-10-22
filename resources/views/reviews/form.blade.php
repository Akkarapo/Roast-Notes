@php
    $editing = $review->exists;
    $product = $item->variant->product;
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-page-title :script="$editing ? 'edit your notes' : 'how was it?'">{{ $editing ? __('Edit review') : __('Write a review') }}</x-page-title>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <a href="{{ route('orders.show', $item->order) }}" class="text-sm font-semibold underline-offset-4 hover:underline">‹ {{ $item->order->order_number }}</a>

        <div class="mt-4 rounded-card border-2 border-cobalt bg-paper p-6 sm:p-8">
            <div class="flex items-center gap-4 border-b-2 border-dashed border-cobalt pb-5">
                <div class="grid h-16 w-16 shrink-0 place-items-center rounded-[16px] bg-cobalt text-paper">
                    <x-dynamic-component :component="$product->type === \App\Enums\ProductType::DripBag ? 'illustrations.drip-bag' : 'illustrations.bean-bag'" class="h-11 w-11" />
                </div>
                <div>
                    <a href="{{ route('products.show', $product) }}" class="text-lg font-semibold underline-offset-4 hover:underline">{{ $item->product_name }}</a>
                    <p class="text-sm text-cobalt/70">{{ __('Bought') }} · {{ \App\Enums\BagSize::from($item->size)->label() }}@if ($item->grind !== \App\Enums\Grind::None) · {{ $item->grind->label() }}@endif</p>
                </div>
            </div>

            <form method="POST" action="{{ $editing ? route('reviews.update', $review) : route('reviews.store', $item) }}" class="mt-6 space-y-6">
                @csrf
                @if ($editing) @method('PATCH') @endif

                <div>
                    <x-star-input :value="old('rating', $review->rating)" />
                    <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                </div>

                <fieldset>
                    <legend class="text-sm font-semibold">{{ __('How did you brew it?') }}</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (\App\Enums\BrewMethod::cases() as $method)
                            <x-filter-chip type="radio" name="brew_method" :value="$method->value" required
                                :checked="old('brew_method', $review->brew_method?->value) === $method->value">{{ $method->label() }}</x-filter-chip>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('brew_method')" class="mt-2" />
                </fieldset>

                <div x-data="{ length: {{ mb_strlen((string) old('comment', $review->comment)) }} }">
                    <label for="comment" class="text-sm font-semibold">{{ __('Notes (optional)') }}</label>
                    <x-textarea-input id="comment" name="comment" rows="5" maxlength="{{ \App\Models\Review::COMMENT_MAX }}" class="mt-2 block w-full"
                        placeholder="{{ __('What did you taste? Grind, ratio, water temperature…') }}" @input="length = $event.target.value.length">{{ old('comment', $review->comment) }}</x-textarea-input>
                    <p class="mt-1 text-right text-xs text-cobalt/60"><span x-text="length">{{ mb_strlen((string) old('comment', $review->comment)) }}</span>/{{ \App\Models\Review::COMMENT_MAX }}</p>
                    <x-input-error :messages="$errors->get('comment')" class="mt-1" />
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <x-primary-button>{{ $editing ? __('Save changes') : __('Post review') }}</x-primary-button>
                    <a href="{{ route('orders.show', $item->order) }}" class="text-sm font-semibold underline-offset-4 hover:underline">{{ __('Cancel') }}</a>
                </div>
            </form>

            @if ($editing)
                <form method="POST" action="{{ route('reviews.destroy', $review) }}" class="mt-6 border-t-2 border-dashed border-cobalt/40 pt-4">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-semibold text-brick underline-offset-4 hover:underline">{{ __('Delete this review') }}</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
