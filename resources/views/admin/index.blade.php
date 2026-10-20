<x-app-layout>
    <x-slot name="header">
        <x-page-title script="back of house">{{ __('Admin Dashboard') }}</x-page-title>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-paper border-2 border-cobalt rounded-card p-6">
            {{ __('Welcome, :name', ['name' => auth()->user()->name]) }}
        </div>
    </div>
</x-app-layout>
