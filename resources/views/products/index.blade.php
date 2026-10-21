<x-app-layout>
    <x-slot name="header">
        <x-page-title script="all beans">{{ __('Products') }}</x-page-title>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <form method="GET" action="{{ route('products.index') }}" x-data x-on:change="$el.requestSubmit()" class="space-y-3">
            @php $row = 'flex items-center gap-2 overflow-x-auto pb-1 -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap sm:overflow-visible'; @endphp

            <div class="{{ $row }}">
                <span class="w-24 shrink-0 text-sm font-semibold">{{ __('Flavor') }}</span>
                @foreach ($flavorCategories as $category)
                    <x-filter-chip name="flavor[]" :value="$category->slug" :checked="in_array($category->slug, $filters['flavor'], true)">{{ $category->name }}</x-filter-chip>
                @endforeach
            </div>

            <div class="{{ $row }}">
                <span class="w-24 shrink-0 text-sm font-semibold">{{ __('Roast') }}</span>
                @foreach (\App\Enums\RoastLevel::cases() as $level)
                    <x-filter-chip name="roast[]" :value="$level->value" :checked="in_array($level, $filters['roast'], true)">{{ $level->label() }}</x-filter-chip>
                @endforeach

                <span class="ml-4 w-auto shrink-0 text-sm font-semibold">{{ __('Type') }}</span>
                <x-filter-chip type="radio" name="type" value="" :checked="$filters['type'] === null">{{ __('All') }}</x-filter-chip>
                @foreach (\App\Enums\ProductType::cases() as $type)
                    <x-filter-chip type="radio" name="type" :value="$type->value" :checked="$filters['type'] === $type">{{ $type->label() }}</x-filter-chip>
                @endforeach
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-1">
                <label class="flex items-center gap-2 text-sm font-semibold">
                    {{ __('Country') }}
                    <select name="country" class="rounded-full border-2 border-cobalt/40 bg-paper py-1.5 pl-4 pr-9 text-sm text-cobalt focus:border-cobalt focus:ring-0">
                        <option value="">{{ __('All countries') }}</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country }}" @selected($filters['country'] === $country)>{{ $country }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="flex items-center gap-2 text-sm font-semibold sm:ml-auto">
                    {{ __('Sort by') }}
                    <select name="sort" class="rounded-full border-2 border-cobalt/40 bg-paper py-1.5 pl-4 pr-9 text-sm text-cobalt focus:border-cobalt focus:ring-0">
                        @foreach (($canSortByMatch ? ['match' => __('Matches your taste')] : []) + ['newest' => __('Freshest roast'), 'price_asc' => __('Price: low to high'), 'price_desc' => __('Price: high to low'), 'score' => __('Cupping score')] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <noscript><x-primary-button>{{ __('Filter') }}</x-primary-button></noscript>
            </div>
        </form>

        <p class="mt-6 mb-4 text-sm text-cobalt/80">
            {{ __('Found :count items', ['count' => $products->total()]) }}
            @if ($hasFilters)
                · <a href="{{ route('products.index') }}" class="font-semibold underline underline-offset-4">{{ __('Clear filters') }}</a>
            @endif
        </p>

        @if ($products->isEmpty())
            <x-empty-state illustration="bean-bag">
                {{ __('No beans match these filters.') }}
                <x-slot name="action">
                    <a href="{{ route('products.index') }}" class="inline-flex px-6 py-2.5 rounded-full border-2 border-cobalt bg-cobalt text-paper font-semibold hover:bg-cobalt-ink">{{ __('Clear filters') }}</a>
                </x-slot>
            </x-empty-state>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $products->links('pagination.roast') }}
            </div>
        @endif
    </div>
</x-app-layout>
