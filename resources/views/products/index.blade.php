<x-app-layout>
    <x-slot name="header">
        <x-page-title script="all beans">{{ __('Products') }}</x-page-title>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <x-empty-state illustration="bean-bag">{{ __('No beans yet.') }}</x-empty-state>
    </div>
</x-app-layout>
