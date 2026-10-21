@use('App\Enums\ProductType')
@use('App\Support\Money')

@php
    $isDrip = $product->type === ProductType::DripBag;
    $firstInStock = $product->variants->first(fn ($v) => $v->inStock());
    $match = $taster ? $product->matchPercentFor($taster) : null;
    $likedByCategory = $taster ? $product->likedNotesFor($taster)->groupBy(fn ($note) => $note->category->parent->name) : collect();
    $defaultGrind = auth()->user()?->defaultGrind()->value ?? 'whole_bean';
@endphp

<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        <a href="{{ route('products.index') }}" class="text-sm font-semibold underline-offset-4 hover:underline">‹ {{ __('All products') }}</a>

        <div class="mt-5 grid gap-8 lg:grid-cols-2">
            <div class="relative grid min-h-72 place-items-center rounded-hero bg-cobalt p-10 text-paper">
                @if ($match !== null)
                    <span class="absolute right-6 top-6 grid h-28 w-28 rotate-[8deg] place-items-center rounded-full border-[3px] border-paper text-center leading-none" data-match="{{ $match }}">
                        <span><span class="block font-script text-4xl">{{ $match }}%</span><span class="mt-1 block text-xs">{{ __('match') }}</span></span>
                    </span>
                @elseif (auth()->check())
                    <a href="{{ route('taste-quiz.edit') }}" class="absolute right-6 top-6 rounded-full border-2 border-paper px-4 py-2 text-sm font-semibold hover:bg-paper hover:text-cobalt">{{ __('Does it match your taste?') }} →</a>
                @endif
                <x-dynamic-component :component="$isDrip ? 'illustrations.drip-bag' : 'illustrations.bean-bag'" class="h-64 w-auto sm:h-80" />
            </div>

            <div>
                <p class="text-sm text-cobalt/80">{{ $product->origin->country }} · {{ $product->origin->region }}</p>
                <h1 class="mt-1 text-3xl font-semibold sm:text-4xl">
                    {{ $product->name }}
                    <span class="mt-1 block font-script text-4xl font-normal sm:text-5xl">{{ $product->nickname }}</span>
                </h1>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                    <span class="rounded-full border-[1.5px] border-cobalt px-3 py-1 font-semibold">{{ $product->roast_level->label() }} · {{ $product->roast_level->thaiLabel() }}</span>
                    <span>{{ __('Roasted on :date', ['date' => $product->roast_date->translatedFormat('j M Y')]) }}</span>
                    @if ($taster?->preferred_roast_level === $product->roast_level)
                        <span class="rounded-full bg-cobalt px-3 py-1 font-semibold text-paper">{{ __('Your favourite roast') }}</span>
                    @endif
                </div>

                <div class="mt-5">
                    <h2 class="text-sm font-semibold">{{ __('Flavor notes') }}</h2>
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach ($product->flavorNotes as $note)
                            <li class="rounded-full bg-cobalt px-3 py-1 text-sm text-paper">
                                {{ $note->name }} <span class="opacity-70">· {{ $note->category->parent->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if ($taster)
                    <p class="mt-4 text-sm" data-liked-notes>
                        @if ($likedByCategory->isEmpty())
                            {{ __('None of these notes are in the flavors you picked.') }}
                        @else
                            <span class="font-semibold">{{ __('Matches what you like:') }}</span>
                            {{ $likedByCategory->map(fn ($notes, $category) => $category.' ('.$notes->pluck('name')->join(', ').')')->join(' · ') }}
                        @endif
                    </p>
                @endif

                @if ($product->description)
                    <p class="mt-5 leading-relaxed text-cobalt/90">{{ $product->description }}</p>
                @endif

                <form method="POST" action="{{ route('cart.items.store') }}" class="mt-6 rounded-card border-2 border-cobalt bg-paper p-5 space-y-5">
                    @csrf
                    <div>
                        <h2 class="text-sm font-semibold">{{ $isDrip ? __('Pack') : __('Bag size') }}</h2>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($product->variants as $variant)
                                <x-filter-chip type="radio" name="variant_id" :value="$variant->id"
                                    :checked="old('variant_id') ? (int) old('variant_id') === $variant->id : $firstInStock?->is($variant)"
                                    :disabled="! $variant->inStock()">
                                    {{ $variant->size->label() }} · {{ Money::baht($variant->price) }}
                                    @unless ($variant->inStock()) ({{ __('Sold out') }}) @endunless
                                </x-filter-chip>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('variant_id')" class="mt-2" />
                    </div>

                    @unless ($isDrip)
                        <div>
                            <h2 class="text-sm font-semibold">{{ __('Grind') }}</h2>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach (\App\Enums\Grind::forBeans() as $grind)
                                    <x-filter-chip type="radio" name="grind" :value="$grind->value" :checked="old('grind', $defaultGrind) === $grind->value">{{ $grind->label() }}</x-filter-chip>
                                @endforeach
                            </div>
                        </div>
                    @endunless

                    <div class="flex flex-wrap items-center gap-4">
                        <x-quantity-stepper :value="old('quantity', 1)" :max="\App\Models\CartItem::MAX_QUANTITY" />
                        <x-primary-button :disabled="! $firstInStock">{{ __('Add to cart') }}</x-primary-button>
                    </div>
                    <x-input-error :messages="$errors->get('quantity')" />

                    @guest
                        <p class="text-sm text-cobalt/70">{{ __('You will be asked to log in first, then brought back here.') }}</p>
                    @endguest
                </form>
            </div>
        </div>

        <div class="mt-12 grid gap-8 lg:grid-cols-2">
            <div class="rounded-card border-2 border-cobalt bg-paper p-6">
                <h2 class="text-xl font-semibold">{{ __('About this lot') }}</h2>
                <dl class="mt-4 divide-y-[1.5px] divide-dashed divide-cobalt/40 text-sm">
                    @foreach ([
                        __('Country') => $product->origin->country,
                        __('Region') => $product->origin->region,
                        __('Farm') => $product->farm_name,
                        __('Variety') => $product->variety,
                        __('Altitude') => $product->altitude_m ? number_format($product->altitude_m).' '.__('m') : null,
                        __('Process') => $product->process->label(),
                        __('Roast') => $product->roast_level->label(),
                    ] as $label => $value)
                        @if ($value)
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-cobalt/75">{{ $label }}</dt>
                                <dd class="text-right font-semibold">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </div>

            @if ($product->cupping)
                <x-cupping-bars :cupping="$product->cupping" />
            @endif
        </div>

        @if ($similar->isNotEmpty())
            <section class="mt-14">
                <x-page-title as="h2" script="you may also like">{{ __('Similar beans') }}</x-page-title>
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($similar as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
