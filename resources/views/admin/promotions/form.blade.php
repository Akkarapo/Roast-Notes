@use('App\Enums\PromotionType')
@php
    $editing = $promotion->exists;
    $isCoupon = $promotion->type === PromotionType::Coupon;
    $selected = collect(old('products', $promotion->relationLoaded('products') ? $promotion->products->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all();
    $field = 'mt-1 block w-full';
@endphp
<x-admin-layout :title="$editing ? $promotion->name : ($isCoupon ? __('New coupon') : __('New buy X get Y'))" :script="$isCoupon ? 'coupon' : 'buy x get y'">
    <a href="{{ route('admin.promotions.index') }}" class="text-sm font-semibold underline-offset-4 hover:underline">‹ {{ __('Promotions') }}</a>

    <form method="POST" action="{{ $editing ? route('admin.promotions.update', $promotion) : route('admin.promotions.store') }}" class="mt-4 grid items-start gap-6 lg:grid-cols-[1fr_1fr]"
          x-data="{ selected: @js($selected) }">
        @csrf
        @if ($editing) @method('PUT') @else <input type="hidden" name="type" value="{{ $promotion->type->value }}"> @endif

        <section class="grid gap-4 rounded-card border-2 border-cobalt bg-paper p-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" :value="old('name', $promotion->name)" required maxlength="100" class="{{ $field }}" />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="description" :value="__('Description')" />
                <x-text-input id="description" name="description" :value="old('description', $promotion->description)" maxlength="255" class="{{ $field }}" />
            </div>

            @if ($isCoupon)
                <div>
                    <x-input-label for="code" :value="__('Code')" />
                    <x-text-input id="code" name="code" :value="old('code', $promotion->coupon?->code)" required maxlength="30" class="{{ $field }} font-mono uppercase" />
                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="percent_off" :value="__('% off')" />
                    <x-text-input id="percent_off" name="percent_off" type="number" min="1" max="100" :value="old('percent_off', $promotion->coupon?->percent_off)" required class="{{ $field }}" />
                    <x-input-error :messages="$errors->get('percent_off')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="min_subtotal" :value="__('Minimum spend on participating items (฿)')" />
                    <x-text-input id="min_subtotal" name="min_subtotal" type="number" min="0" step="0.01" :value="old('min_subtotal', $promotion->coupon?->min_subtotal)" class="{{ $field }}" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="usage_limit" :value="__('Uses in total')" />
                        <x-text-input id="usage_limit" name="usage_limit" type="number" min="1" :value="old('usage_limit', $promotion->coupon?->usage_limit)" class="{{ $field }}" placeholder="∞" />
                    </div>
                    <div>
                        <x-input-label for="per_user_limit" :value="__('Per member')" />
                        <x-text-input id="per_user_limit" name="per_user_limit" type="number" min="1" :value="old('per_user_limit', $promotion->coupon?->per_user_limit)" class="{{ $field }}" placeholder="∞" />
                    </div>
                </div>
            @else
                <div>
                    <x-input-label for="buy_quantity" :value="__('Buy (X)')" />
                    <x-text-input id="buy_quantity" name="buy_quantity" type="number" min="1" max="100" :value="old('buy_quantity', $promotion->buyXGetY?->buy_quantity)" required class="{{ $field }}" />
                    <x-input-error :messages="$errors->get('buy_quantity')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="free_quantity" :value="__('Get free (Y)')" />
                    <x-text-input id="free_quantity" name="free_quantity" type="number" min="1" max="100" :value="old('free_quantity', $promotion->buyXGetY?->free_quantity)" required class="{{ $field }}" />
                    <x-input-error :messages="$errors->get('free_quantity')" class="mt-1" />
                </div>
            @endif

            <div>
                <x-input-label for="starts_at" :value="__('Starts')" />
                <x-text-input id="starts_at" name="starts_at" type="datetime-local" :value="old('starts_at', $promotion->starts_at?->format('Y-m-d\TH:i'))" required class="{{ $field }}" />
                <x-input-error :messages="$errors->get('starts_at')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="ends_at" :value="__('Ends (empty = no end)')" />
                <x-text-input id="ends_at" name="ends_at" type="datetime-local" :value="old('ends_at', $promotion->ends_at?->format('Y-m-d\TH:i'))" class="{{ $field }}" />
                <x-input-error :messages="$errors->get('ends_at')" class="mt-1" />
            </div>
            <label class="flex items-center gap-2 font-semibold sm:col-span-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $promotion->is_active)) class="rounded border-cobalt text-cobalt focus:ring-cobalt">
                {{ __('Switched on') }}
            </label>
            <div class="sm:col-span-2 flex flex-wrap items-center gap-3">
                <x-primary-button>{{ $editing ? __('Save changes') : __('Create promotion') }}</x-primary-button>
                <span class="text-sm text-cobalt/70" x-text="@js(__('Products selected')) + ': ' + selected.length"></span>
            </div>
        </section>

        <section class="rounded-card border-2 border-cobalt bg-paper p-6" data-product-picker>
            <h2 class="text-lg font-semibold">{{ __('Participating products') }}</h2>
            <x-input-error :messages="$errors->get('products')" class="mt-1" />
            <div class="mt-3 max-h-[640px] space-y-4 overflow-y-auto pe-2">
                @foreach ($productGroups as $group => $products)
                    <div x-data="{ ids: @js($products->pluck('id')->all()) }">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold">{{ $group }} ({{ $products->count() }})</h3>
                            <button type="button" class="text-xs font-semibold underline-offset-4 hover:underline"
                                    @click="selected = ids.every(id => selected.includes(id)) ? selected.filter(id => ! ids.includes(id)) : [...new Set([...selected, ...ids])]">
                                {{ __('Select all / none') }}
                            </button>
                        </div>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            @foreach ($products as $product)
                                <x-filter-chip name="products[]" :value="$product->id" x-model.number="selected" :checked="in_array($product->id, $selected, true)">
                                    {{ $product->name }}@unless ($product->is_active) · {{ __('off') }}@endunless
                                </x-filter-chip>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </form>

    @if ($editing && ! $promotion->hasBeenUsed())
        <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}" class="mt-6" data-delete onsubmit="return confirm(@js(__('Delete this promotion?')))">
            @csrf
            @method('DELETE')
            <button class="text-sm font-semibold text-brick underline-offset-4 hover:underline">{{ __('Delete promotion (never used)') }}</button>
        </form>
    @elseif ($editing)
        <p class="mt-6 text-sm text-cobalt/70">{{ __('Used in orders, so it can only be switched off.') }}</p>
    @endif
</x-admin-layout>
