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
            <x-mascot pose="pour" class="w-full max-w-sm self-end justify-self-center text-paper" />
        </section>

        @if ($taster && $forYou->isNotEmpty())
            <section id="for-you" class="mt-14">
                <x-page-title as="h2" script="picked for you">{{ __('Recommended for you') }}</x-page-title>
                <p class="mt-1 text-cobalt/70">
                    {{ __('Based on your taste profile:') }}
                    {{ $taster->tastePreferences->pluck('name')->join(' · ') }}@if ($taster->preferred_roast_level) · {{ $taster->preferred_roast_level->label() }}@endif
                    · <a href="{{ route('taste-quiz.edit') }}" class="underline underline-offset-4">{{ __('Edit') }}</a>
                </p>
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($forYou as $product)
                        <x-product-card :product="$product" :featured="$loop->first" />
                    @endforeach
                </div>
            </section>
        @elseif (! $taster)
            <section id="taste-quiz-prompt" class="mt-14 grid items-center gap-6 rounded-card border-2 border-dashed border-cobalt bg-paper p-6 sm:grid-cols-[auto_1fr_auto] sm:p-8">
                <x-mascot pose="search" class="h-24 w-24 text-cobalt" />
                <div>
                    <p class="font-script text-3xl">what's your taste?</p>
                    <p class="mt-1">{{ __('Take the 3-question Taste Quiz and we will tell you which beans match your taste.') }}</p>
                </div>
                <a href="{{ $showQuizPrompt ? route('taste-quiz.edit') : route('register') }}" class="inline-flex justify-center rounded-full border-2 border-cobalt bg-cobalt px-6 py-2.5 font-semibold text-paper hover:bg-cobalt-ink">
                    {{ $showQuizPrompt ? __('Take the Taste Quiz') : __('Sign up & take the quiz') }} →
                </a>
            </section>
        @endif

        @if ($bestSellers->isNotEmpty())
            <section id="best-sellers" class="mt-14">
                <x-page-title as="h2" script="crowd favourites">{{ __('Best sellers') }}</x-page-title>
                <p class="mt-1 text-cobalt/70">{{ __('What our members order most') }}</p>
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($bestSellers as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif

        <section id="new-lots" class="mt-14">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <x-page-title as="h2" script="fresh roast">{{ __('Freshly roasted lots') }}</x-page-title>
                    <p class="mt-1 text-cobalt/70">{{ __('Our most recent roasts') }}</p>
                </div>
                <a href="{{ route('products.index', ['sort' => 'newest']) }}" class="font-semibold underline-offset-4 hover:underline">{{ __('View all') }} →</a>
            </div>

            @if ($newLots->isEmpty())
                <x-empty-state mascot="bag" class="mt-6">{{ __('No beans yet.') }}</x-empty-state>
            @else
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($newLots as $product)
                        <x-product-card :product="$product" :featured="$loop->first" />
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
