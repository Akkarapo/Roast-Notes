<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <section class="mt-7 grid lg:grid-cols-[1.1fr_.9fr] overflow-hidden rounded-hero bg-cobalt text-paper px-8 sm:px-14 pt-12 sm:pt-14">
            <div class="pb-10 sm:pb-12">
                <h1 class="text-4xl sm:text-5xl leading-tight font-semibold">
                    {{ __('Find beans that match your taste') }}
                    <span class="block font-script font-normal text-5xl sm:text-7xl mt-1">sip your notes!</span>
                </h1>
                <p class="mt-5 max-w-md text-lg text-paper/90">{{ __('Tell us the flavor notes you love and every bean shows how well it matches you.') }}</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('products.index') }}" class="px-6 py-2.5 rounded-full border-2 border-paper bg-paper text-cobalt font-semibold hover:bg-cream">{{ __('Shop beans') }} →</a>
                    @guest
                        <a href="{{ route('register') }}" class="px-6 py-2.5 rounded-full border-2 border-paper text-paper font-semibold hover:bg-cobalt-ink">{{ __('Register') }}</a>
                    @endguest
                </div>
                <p class="mt-10 text-sm text-paper/80">{{ __('Fresh roasted every week · Shipping nationwide') }}<br>{{ __('Whole beans · Drip bags · Single origin') }}</p>
            </div>
            <x-illustrations.pour-over class="w-full max-w-md self-end justify-self-center text-paper" />
        </section>

        {{-- Sections filled in by later modules: catalog (new lots), ordering (best sellers), taste-profile (for you) --}}
        <section id="new-lots" class="mt-14">
            <x-page-title as="h2" script="fresh roast">{{ __('Freshly roasted lots') }}</x-page-title>
            <p class="mt-1 mb-6 text-cobalt/70">{{ __('Roasted within the last 7 days') }}</p>
            <x-empty-state illustration="bean-bag">{{ __('No beans yet.') }}</x-empty-state>
        </section>
    </div>
</x-app-layout>
