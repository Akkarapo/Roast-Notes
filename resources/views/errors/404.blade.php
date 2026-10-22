<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
        <x-mascot pose="search" class="mx-auto h-48 w-48" />
        <p class="mt-4 font-script text-5xl">404</p>
        <h1 class="mt-2 text-2xl font-semibold">{{ __("We couldn't find that page.") }}</h1>
        <p class="mt-2 text-cobalt/80">{{ __('Maybe it moved, or the link has a typo.') }}</p>
        <a href="{{ route('home') }}" class="mt-6 inline-flex rounded-full border-2 border-cobalt bg-cobalt px-6 py-2.5 font-semibold text-paper hover:bg-cobalt-ink">{{ __('Back to the shop') }} →</a>
    </div>
</x-app-layout>
