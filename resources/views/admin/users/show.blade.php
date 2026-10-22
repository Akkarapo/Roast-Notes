@use('App\Enums\UserRole')
<x-admin-layout :title="$user->name" script="member">
    <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold underline-offset-4 hover:underline">‹ {{ __('Members') }}</a>

    <div class="mt-4 grid items-start gap-6 lg:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            <x-tier-card :status="$loyalty" />

            <section>
                <h2 class="mb-3 text-xl font-semibold">{{ __('Orders') }} ({{ $orders->count() }})</h2>
                @if ($orders->isEmpty())
                    <x-empty-state>{{ __('No orders yet.') }}</x-empty-state>
                @else
                    <x-order-history :orders="$orders" route="admin.orders.show" />
                @endif
            </section>

            <section>
                <h2 class="mb-3 text-xl font-semibold">{{ __('Reviews') }} ({{ $reviews->count() }})</h2>
                @if ($reviews->isNotEmpty())
                    <div class="divide-y-[1.5px] divide-dashed divide-cobalt/30 rounded-card border-2 border-cobalt bg-paper px-5">
                        @foreach ($reviews as $review)
                            <div class="py-3 text-sm">
                                <x-star-rating :rating="$review->rating" /> <span class="font-semibold">{{ $review->orderItem->product_name }}</span> · {{ $review->brew_method->label() }}
                                @if ($review->comment)<p class="mt-1 text-cobalt/80">{{ $review->comment }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <section class="space-y-1 rounded-card border-2 border-cobalt bg-paper p-5 text-sm">
                <h2 class="mb-2 text-sm font-semibold">{{ __('Contact') }}</h2>
                <p>{{ $user->email }}</p>
                <p>{{ $user->phone ?? '—' }}</p>
                <p class="whitespace-pre-line text-cobalt/80">{{ $user->address ?? '—' }}</p>
                <p class="pt-2 text-xs text-cobalt/60">{{ __('Joined') }} {{ $user->created_at->translatedFormat('j M Y') }}</p>
            </section>

            <section class="rounded-card border-2 border-cobalt bg-paper p-5 text-sm">
                <h2 class="mb-2 text-sm font-semibold">Taste Profile</h2>
                @if ($user->hasTasteProfile())
                    <p>{{ $user->tastePreferences->pluck('name')->join(', ') }}</p>
                    <p class="text-cobalt/80">{{ $user->preferred_roast_level?->label() ?? __('Not sure') }} · {{ $user->preferred_brew_method?->label() ?? '—' }}</p>
                @else
                    <p class="text-cobalt/70">{{ __('Has not taken the Taste Quiz.') }}</p>
                @endif
                @if ($user->wishlist->isNotEmpty())
                    <h3 class="mt-3 text-sm font-semibold">Wish List ({{ $user->wishlist->count() }})</h3>
                    <p class="text-cobalt/80">{{ $user->wishlist->pluck('name')->join(', ') }}</p>
                @endif
            </section>

            <form method="POST" action="{{ route('admin.users.tier', $user) }}" class="space-y-2 rounded-card border-2 border-cobalt bg-paper p-5" data-tier-form>
                @csrf
                @method('PATCH')
                <h2 class="text-sm font-semibold">{{ __('Set tier manually') }}</h2>
                <select name="member_tier_id" class="block w-full rounded-[14px] border-2 border-cobalt/40 bg-paper text-cobalt focus:border-cobalt focus:ring-0">
                    @foreach ($tiers as $option)
                        <option value="{{ $option->id }}" @selected($user->member_tier_id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-cobalt/60">{{ __('Delivered orders can still move the member up automatically later.') }}</p>
                <x-secondary-button type="submit">{{ __('Save tier') }}</x-secondary-button>
            </form>

            <form method="POST" action="{{ route('admin.users.role', $user) }}" class="space-y-2 rounded-card border-2 border-dashed border-cobalt bg-paper p-5" data-role-form>
                @csrf
                @method('PATCH')
                <h2 class="text-sm font-semibold">{{ __('Role') }}: {{ $user->role->label() }}</h2>
                <input type="hidden" name="role" value="{{ $user->isAdmin() ? UserRole::Member->value : UserRole::Admin->value }}">
                <x-input-error :messages="$errors->get('role')" />
                <x-secondary-button type="submit">{{ $user->isAdmin() ? __('Remove admin rights') : __('Make admin') }}</x-secondary-button>
            </form>
        </aside>
    </div>
</x-admin-layout>
