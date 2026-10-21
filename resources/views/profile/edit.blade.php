<x-app-layout>
    <x-slot name="header">
        <x-page-title script="hello, {{ Auth::user()->name }}">{{ __('Member Info') }}</x-page-title>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Tier (loyalty) and taste profile (taste-profile) are added here later --}}
        <section id="order-history">
            <div class="mb-3 flex items-baseline justify-between">
                <h2 class="text-xl font-semibold">{{ __('Order history') }}</h2>
                @if ($orderCount > $orders->count())
                    <a href="{{ route('orders.index') }}" class="text-sm font-semibold underline-offset-4 hover:underline">{{ __('View all (:count)', ['count' => $orderCount]) }} →</a>
                @endif
            </div>
            @if ($orders->isEmpty())
                <x-empty-state>
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
