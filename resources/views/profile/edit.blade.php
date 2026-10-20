<x-app-layout>
    <x-slot name="header">
        <x-page-title script="hello, {{ Auth::user()->name }}">{{ __('Member Info') }}</x-page-title>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Order history (ordering module), tier (loyalty) and taste profile (taste-profile) are added here later --}}
        <div class="p-6 sm:p-8 bg-paper border-2 border-cobalt rounded-card">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-6 sm:p-8 bg-paper border-2 border-cobalt rounded-card">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="p-6 sm:p-8 bg-paper border-2 border-dashed border-cobalt/50 rounded-card">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
