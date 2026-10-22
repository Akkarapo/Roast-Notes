@use('App\Enums\UserRole')
@use('App\Support\Money')
<x-admin-layout :title="__('Members')">
    <form method="GET" class="flex flex-wrap items-center gap-2">
        @foreach (['' => __('Everyone')] + collect(UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all() as $value => $label)
            <x-filter-chip type="radio" name="role" :value="$value" :checked="($role?->value ?? '') === $value" onchange="this.form.submit()">{{ $label }}</x-filter-chip>
        @endforeach
        <span class="mx-2"></span>
        @foreach (['' => __('All tiers')] + $tiers->pluck('name', 'slug')->all() as $value => $label)
            <x-filter-chip type="radio" name="tier" :value="$value" :checked="($tier?->slug ?? '') === $value" onchange="this.form.submit()">{{ $label }}</x-filter-chip>
        @endforeach
        <span class="ms-auto flex gap-2">
            <x-text-input name="q" :value="$search" placeholder="{{ __('Name, email, phone') }}" class="w-56" />
            <x-secondary-button type="submit">{{ __('Search') }}</x-secondary-button>
        </span>
    </form>

    <x-admin.table class="mt-6">
        <thead>
            <tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Tier') }}</th><th class="text-right">{{ __('Total spend') }}</th><th class="text-right">{{ __('Orders') }}</th><th>{{ __('Joined') }}</th></tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>
                        <a href="{{ route('admin.users.show', $user) }}" class="font-semibold underline-offset-4 hover:underline">{{ $user->name }}</a>
                        @if ($user->isAdmin())<span class="ms-1 rounded-full border-[1.5px] border-cobalt px-2 py-0.5 text-xs font-semibold">{{ __('Admin') }}</span>@endif
                    </td>
                    <td>{{ $user->email }}</td>
                    <td><x-tier-badge :tier="$user->memberTier" /></td>
                    <td class="text-right">{{ Money::baht($user->spend ?? 0) }}</td>
                    <td class="text-right">{{ $user->orders_count }}</td>
                    <td class="whitespace-nowrap">{{ $user->created_at->translatedFormat('j M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center text-cobalt/70">{{ __('No members match.') }}</td></tr>
            @endforelse
        </tbody>
    </x-admin.table>
    <div class="mt-4">{{ $users->links() }}</div>
</x-admin-layout>
