<x-app-layout>
    <x-slot name="header">
        <x-page-title script="order history">{{ __('My orders') }}</x-page-title>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if ($orders->isEmpty())
            <x-empty-state mascot="bag">{{ __('No orders yet.') }}</x-empty-state>
        @else
            <x-order-history :orders="$orders" />
            <div class="mt-8">{{ $orders->links('pagination.roast') }}</div>
        @endif
    </div>
</x-app-layout>
