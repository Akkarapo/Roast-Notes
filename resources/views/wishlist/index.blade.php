<x-app-layout>
    <x-slot name="header">
        <x-page-title script="saved for later">{{ __('Wish List') }}</x-page-title>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if ($products->isEmpty())
            <x-empty-state>
                {{ __('Nothing saved yet. Tap the heart on any bean to keep it here.') }}
                <x-slot name="action">
                    <a href="{{ route('products.index') }}" class="inline-flex px-6 py-2.5 rounded-full border-2 border-cobalt bg-cobalt text-paper font-semibold hover:bg-cobalt-ink">{{ __('Shop beans') }} →</a>
                </x-slot>
            </x-empty-state>
        @else
            <p class="mb-4 text-sm text-cobalt/80">{{ __(':count saved', ['count' => $products->count()]) }}</p>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4" data-wishlist>
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
