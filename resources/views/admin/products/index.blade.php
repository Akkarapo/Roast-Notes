@use('App\Enums\ProductType')
@use('App\Support\Money')
<x-admin-layout :title="__('Products')">
    <x-slot name="actions">
        <a href="{{ route('admin.products.create') }}" class="inline-flex rounded-full border-2 border-cobalt bg-cobalt px-5 py-2 text-sm font-semibold text-paper hover:bg-cobalt-ink">+ {{ __('New product') }}</a>
    </x-slot>

    <form method="GET" class="flex flex-wrap items-center gap-2">
        @foreach (['' => __('All')] + collect(ProductType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all() as $value => $label)
            <x-filter-chip type="radio" name="type" :value="$value" :checked="($type?->value ?? '') === $value" onchange="this.form.submit()">{{ $label }}</x-filter-chip>
        @endforeach
        <span class="mx-2"></span>
        @foreach (['' => __('Any status'), 'active' => __('On sale'), 'inactive' => __('Deactivated'), 'rare' => __('Rare lots'), 'low' => __('Low stock')] as $value => $label)
            <x-filter-chip type="radio" name="state" :value="$value" :checked="($state ?? '') === $value" onchange="this.form.submit()">{{ $label }}</x-filter-chip>
        @endforeach
        <span class="ms-auto flex gap-2">
            <x-text-input name="q" :value="$search" placeholder="{{ __('Name or country') }}" class="w-56" />
            <x-secondary-button type="submit">{{ __('Search') }}</x-secondary-button>
        </span>
    </form>

    <x-admin.table class="mt-6">
        <thead>
            <tr><th>{{ __('Product') }}</th><th>{{ __('Type') }}</th><th>{{ __('Roast') }}</th><th class="text-right">{{ __('From') }}</th><th>{{ __('Stock by size') }}</th><th class="text-right">{{ __('Sold') }}</th><th>{{ __('Status') }}</th></tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>
                        <a href="{{ route('admin.products.edit', $product) }}" class="font-semibold underline-offset-4 hover:underline">{{ $product->name }}</a>
                        <span class="block text-xs text-cobalt/70">{{ $product->origin->country }} · {{ $product->nickname }}</span>
                    </td>
                    <td>{{ $product->type->label() }}</td>
                    <td>{{ $product->roast_level->label() }}</td>
                    <td class="text-right">{{ Money::baht($product->variants_min_price) }}</td>
                    <td class="whitespace-nowrap">
                        @foreach ($product->variants->sortBy(fn ($v) => $v->size->grams()) as $variant)
                            <span @class(['me-2', 'font-semibold text-brick' => $variant->stock <= $lowStock]) data-stock>{{ $variant->size->label() }}: {{ $variant->stock }}</span>
                        @endforeach
                    </td>
                    <td class="text-right">{{ (int) $product->units_sold }}</td>
                    <td class="space-x-1 whitespace-nowrap">
                        @if ($product->is_active)
                            <span class="rounded-full bg-cobalt px-2.5 py-0.5 text-xs font-semibold text-paper">{{ __('On sale') }}</span>
                        @else
                            <span class="rounded-full border-[1.5px] border-cobalt/50 px-2.5 py-0.5 text-xs font-semibold text-cobalt/60">{{ __('Deactivated') }}</span>
                        @endif
                        @if ($product->isExclusiveNow())
                            <span class="rounded-full bg-cobalt-ink px-2.5 py-0.5 text-xs font-semibold text-paper">✦ {{ $product->exclusive_until->translatedFormat('j M') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-8 text-center text-cobalt/70">{{ __('No products match.') }}</td></tr>
            @endforelse
        </tbody>
    </x-admin.table>
    <div class="mt-4">{{ $products->links() }}</div>
</x-admin-layout>
