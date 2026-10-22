<x-app-layout>
    <x-slot name="header">
        <x-page-title script="hello, {{ Auth::user()->name }}">{{ __('Member Info') }}</x-page-title>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <x-tier-card :status="$loyalty" />
        <section id="taste-profile" class="rounded-card border-2 border-cobalt bg-paper p-6 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold">Taste Profile</h2>
                    @if ($user->hasTasteProfile())
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($user->tastePreferences as $category)
                                <span class="rounded-full bg-cobalt px-3 py-1 text-sm text-paper">{{ $category->name }}</span>
                            @endforeach
                        </div>
                        <p class="mt-3 text-sm text-cobalt/80">
                            {{ __('Roast') }}: {{ $user->preferred_roast_level ? $user->preferred_roast_level->label().' · '.$user->preferred_roast_level->thaiLabel() : __('Not sure') }}
                            · {{ __('Brew method') }}: {{ $user->preferred_brew_method?->label() ?? '—' }}
                        </p>
                    @else
                        <p class="mt-2 text-cobalt/80">{{ __('Take the 3-question Taste Quiz and we will tell you which beans match your taste.') }}</p>
                    @endif
                </div>
                <a href="{{ route('taste-quiz.edit') }}" class="inline-flex rounded-full border-2 border-cobalt px-5 py-2 text-sm font-semibold {{ $user->hasTasteProfile() ? 'hover:bg-cobalt hover:text-paper' : 'bg-cobalt text-paper hover:bg-cobalt-ink' }}">
                    {{ $user->hasTasteProfile() ? __('Edit') : __('Take the Taste Quiz') }}
                </a>
            </div>
        </section>

        <section id="order-history">
            <div class="mb-3 flex items-baseline justify-between">
                <h2 class="text-xl font-semibold">{{ __('Order history') }}</h2>
                @if ($orderCount > $orders->count())
                    <a href="{{ route('orders.index') }}" class="text-sm font-semibold underline-offset-4 hover:underline">{{ __('View all (:count)', ['count' => $orderCount]) }} →</a>
                @endif
            </div>
            @if ($orders->isEmpty())
                <x-empty-state mascot="bag">
                    {{ __('No orders yet.') }}
                    <x-slot name="action">
                        <a href="{{ route('products.index') }}" class="inline-flex px-6 py-2.5 rounded-full border-2 border-cobalt bg-cobalt text-paper font-semibold hover:bg-cobalt-ink">{{ __('Shop beans') }} →</a>
                    </x-slot>
                </x-empty-state>
            @else
                <x-order-history :orders="$orders" />
            @endif
        </section>

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
