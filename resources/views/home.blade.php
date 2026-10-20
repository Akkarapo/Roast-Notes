<x-app-layout>
    <section class="bg-amber-950 text-amber-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <p class="text-sm uppercase tracking-widest text-amber-300">Specialty Coffee Beans &amp; Drip Bags</p>
            <h1 class="mt-3 text-4xl font-semibold">{{ __('Find beans that match your taste') }}</h1>
            <p class="mt-4 max-w-2xl text-amber-100">{{ __('Tell us the flavor notes you love and every bean shows how well it matches you.') }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('products.index') }}" class="px-5 py-2.5 rounded-md bg-amber-50 text-amber-950 font-medium hover:bg-white">{{ __('Shop beans') }}</a>
                @guest
                    <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-md border border-amber-200 text-amber-50 hover:bg-amber-900">{{ __('Register') }}</a>
                @endguest
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-12">
        {{-- Sections below are filled in by later modules: catalog (new lots), ordering (best sellers), taste-profile (for you) --}}
        <section id="new-lots">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('Freshly roasted lots') }}</h2>
            <p class="mt-2 text-gray-500">{{ __('No beans yet.') }}</p>
        </section>
    </div>
</x-app-layout>
