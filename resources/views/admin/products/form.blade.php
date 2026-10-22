@use('App\Enums\BagSize')
@use('App\Enums\ProcessMethod')
@use('App\Enums\ProductType')
@use('App\Enums\RoastLevel')
@php
    $editing = $product->exists;
    $variants = $product->variants?->keyBy(fn ($v) => $v->size->value) ?? collect();
    $selectedNotes = collect(old('flavor_notes', $product->flavorNotes?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all();
    $field = 'mt-1 block w-full';
    $select = 'mt-1 block w-full bg-paper border-2 border-cobalt/40 text-cobalt rounded-[14px] focus:border-cobalt focus:ring-0';
@endphp
<x-admin-layout :title="$editing ? $product->name : __('New product')" :script="$editing ? 'edit lot' : 'new lot'">
    <a href="{{ route('admin.products.index') }}" class="text-sm font-semibold underline-offset-4 hover:underline">‹ {{ __('Products') }}</a>

    <form method="POST" action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}"
          x-data="{ type: @js(old('type', $product->type?->value ?? 'bean')) }" class="mt-4 grid items-start gap-6 lg:grid-cols-[1fr_380px]">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6">
            <section class="grid gap-4 rounded-card border-2 border-cobalt bg-paper p-6 sm:grid-cols-2">
                <h2 class="sm:col-span-2 text-lg font-semibold">{{ __('About this lot') }}</h2>
                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" name="name" :value="old('name', $product->name)" required maxlength="120" class="{{ $field }}" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="nickname" :value="__('Nickname (handwritten)')" />
                    <x-text-input id="nickname" name="nickname" :value="old('nickname', $product->nickname)" required maxlength="60" class="{{ $field }} font-script text-xl" />
                    <x-input-error :messages="$errors->get('nickname')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="slug" :value="__('URL (slug) — blank = from name')" />
                    <x-text-input id="slug" name="slug" :value="old('slug', $product->slug)" maxlength="140" class="{{ $field }}" />
                    <x-input-error :messages="$errors->get('slug')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="origin_id" :value="__('Origin')" />
                    <select id="origin_id" name="origin_id" class="{{ $select }}" required>
                        @foreach ($origins as $origin)
                            <option value="{{ $origin->id }}" @selected((int) old('origin_id', $product->origin_id) === $origin->id)>{{ $origin->country }} · {{ $origin->region }}</option>
                        @endforeach
                    </select>
                </div>
                <fieldset class="sm:col-span-2">
                    <legend class="text-sm font-semibold">{{ __('Type') }}</legend>
                    <div class="mt-1 flex gap-2">
                        @foreach (ProductType::cases() as $case)
                            <x-filter-chip type="radio" name="type" :value="$case->value" x-model="type" :checked="old('type', $product->type?->value) === $case->value">{{ $case->label() }}</x-filter-chip>
                        @endforeach
                    </div>
                </fieldset>
                <div>
                    <x-input-label for="farm_name" :value="__('Farm')" />
                    <x-text-input id="farm_name" name="farm_name" :value="old('farm_name', $product->farm_name)" maxlength="150" class="{{ $field }}" />
                </div>
                <div>
                    <x-input-label for="variety" :value="__('Variety')" />
                    <x-text-input id="variety" name="variety" :value="old('variety', $product->variety)" maxlength="60" class="{{ $field }}" />
                </div>
                <div>
                    <x-input-label for="process" :value="__('Process')" />
                    <select id="process" name="process" class="{{ $select }}">
                        @foreach (ProcessMethod::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('process', $product->process?->value) === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="altitude_m" :value="__('Altitude (m)')" />
                    <x-text-input id="altitude_m" name="altitude_m" type="number" min="0" max="3000" :value="old('altitude_m', $product->altitude_m)" class="{{ $field }}" />
                    <x-input-error :messages="$errors->get('altitude_m')" class="mt-1" />
                </div>
                <fieldset>
                    <legend class="text-sm font-semibold">{{ __('Roast') }}</legend>
                    <div class="mt-1 flex gap-2">
                        @foreach (RoastLevel::cases() as $case)
                            <x-filter-chip type="radio" name="roast_level" :value="$case->value" :checked="old('roast_level', $product->roast_level?->value) === $case->value">{{ $case->label() }}</x-filter-chip>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('roast_level')" class="mt-1" />
                </fieldset>
                <div>
                    <x-input-label for="roast_date" :value="__('Roasted on')" />
                    <x-text-input id="roast_date" name="roast_date" type="date" :value="old('roast_date', $product->roast_date?->toDateString())" required class="{{ $field }}" />
                    <x-input-error :messages="$errors->get('roast_date')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="description" :value="__('Description')" />
                    <x-textarea-input id="description" name="description" rows="4" maxlength="2000" class="{{ $field }}">{{ old('description', $product->description) }}</x-textarea-input>
                </div>
            </section>

            <section class="rounded-card border-2 border-cobalt bg-paper p-6">
                <h2 class="text-lg font-semibold">{{ __('Flavor notes') }} <span class="text-sm font-normal text-cobalt/70">({{ __('pick 1–5') }})</span></h2>
                <x-input-error :messages="$errors->get('flavor_notes')" class="mt-1" />
                <div class="mt-3 space-y-3">
                    @foreach ($noteGroups as $group => $notes)
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-cobalt/70">{{ $group }}</h3>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                @foreach ($notes as $note)
                                    <x-filter-chip name="flavor_notes[]" :value="$note->id" :checked="in_array($note->id, $selectedNotes, true)">{{ $note->name }}</x-filter-chip>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-6">
            <section class="rounded-card border-2 border-cobalt bg-paper p-5" data-variants>
                <h2 class="text-lg font-semibold">{{ __('Sizes, price & stock') }}</h2>
                <x-input-error :messages="$errors->get('variants')" class="mt-1" />
                <div class="mt-3 space-y-3">
                    @foreach (BagSize::cases() as $size)
                        @php
                            $variant = $variants->get($size->value);
                            $old = old("variants.{$size->value}");
                            $enabled = $old !== null ? ! empty($old['enabled']) : (bool) $variant;
                        @endphp
                        <div class="grid grid-cols-[90px_1fr_1fr] items-center gap-2"
                             x-show="{{ $size === BagSize::Sachet ? "type === 'drip_bag'" : "type === 'bean'" }}">
                            <label class="flex items-center gap-2 text-sm font-semibold">
                                <input type="hidden" name="variants[{{ $size->value }}][enabled]" value="0">
                                <input type="checkbox" name="variants[{{ $size->value }}][enabled]" value="1" @checked($enabled) class="rounded border-cobalt text-cobalt focus:ring-cobalt">
                                {{ $size->label() }}
                            </label>
                            <x-text-input type="number" step="0.01" min="1" name="variants[{{ $size->value }}][price]" :value="$old['price'] ?? $variant?->price" placeholder="{{ __('Price ฿') }}" class="w-full" />
                            <x-text-input type="number" min="0" name="variants[{{ $size->value }}][stock]" :value="$old['stock'] ?? $variant?->stock" placeholder="{{ __('Stock') }}" class="w-full" />
                        </div>
                        <x-input-error :messages="array_merge($errors->get('variants.'.$size->value.'.price'), $errors->get('variants.'.$size->value.'.stock'))" />
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-cobalt/70">{{ __('A size that has been ordered cannot be removed — set its stock to 0.') }}</p>
            </section>

            <section class="space-y-4 rounded-card border-2 border-cobalt bg-paper p-5">
                <label class="flex items-center gap-2 font-semibold">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="rounded border-cobalt text-cobalt focus:ring-cobalt">
                    {{ __('On sale') }}
                </label>
                <div>
                    <x-input-label for="exclusive_until" :value="__('Rare lot: Roaster Master only until')" />
                    <x-text-input id="exclusive_until" name="exclusive_until" type="datetime-local" :value="old('exclusive_until', $product->exclusive_until?->format('Y-m-d\TH:i'))" class="{{ $field }}" />
                    <p class="mt-1 text-xs text-cobalt/70">{{ __('Leave empty for a normal product.') }}</p>
                </div>
                <x-primary-button class="w-full justify-center">{{ $editing ? __('Save changes') : __('Create product') }}</x-primary-button>
                @if ($editing)
                    <a href="{{ route('products.show', $product) }}" class="block text-center text-sm font-semibold underline-offset-4 hover:underline" target="_blank">{{ __('View in shop') }} ↗</a>
                @endif
            </section>
        </aside>
    </form>

    @if ($editing && ! $ordered)
        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="mt-6" data-delete
              onsubmit="return confirm(@js(__('Delete this product for good?')))">
            @csrf
            @method('DELETE')
            <button class="text-sm font-semibold text-brick underline-offset-4 hover:underline">{{ __('Delete product (never ordered)') }}</button>
        </form>
    @elseif ($editing)
        <p class="mt-6 text-sm text-cobalt/70">{{ __('This product has orders, so it can only be deactivated (untick "On sale").') }}</p>
    @endif
</x-admin-layout>
