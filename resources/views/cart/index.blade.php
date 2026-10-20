<x-app-layout>
    <x-slot name="header">
        <x-page-title script="your bag">{{ __('Cart') }}</x-page-title>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <x-empty-state>
            {{ __('Your cart is empty.') }}
            <x-slot name="action">
                <a href="{{ route('products.index') }}" class="inline-flex px-6 py-2.5 rounded-full border-2 border-cobalt bg-cobalt text-paper font-semibold hover:bg-cobalt-ink">{{ __('Shop beans') }} →</a>
            </x-slot>
        </x-empty-state>
    </div>
</x-app-layout>
