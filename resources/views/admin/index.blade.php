@use('App\Support\Money')
<x-admin-layout :title="__('Admin Dashboard')">
    <x-slot name="actions">
        @if (Route::has('admin.reports.index'))
            <a href="{{ route('admin.reports.index') }}" class="inline-flex rounded-full border-2 border-cobalt px-5 py-2 text-sm font-semibold hover:bg-paper">{{ __('Sales report') }} →</a>
        @endif
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5" data-stats>
        <x-admin.stat-card :label="__('Sales today')" :value="Money::bahtFromSatang($today['total'])" tone="strong">{{ __(':count orders', ['count' => $today['count']]) }}</x-admin.stat-card>
        <x-admin.stat-card :label="__('Sales this month')" :value="Money::bahtFromSatang($month['total'])">{{ __(':count orders', ['count' => $month['count']]) }}</x-admin.stat-card>
        <x-admin.stat-card :label="__('Waiting for confirmation')" :value="$pending" :href="route('admin.orders.index', ['status' => 'pending'])" :tone="$pending ? 'alert' : 'default'" data-stat-pending />
        <x-admin.stat-card :label="__('Sizes running low (≤ 5)')" :value="$lowStockCount" :href="route('admin.products.index', ['state' => 'low'])" :tone="$lowStockCount ? 'alert' : 'default'" data-stat-low />
        <x-admin.stat-card :label="__('New members this month')" :value="$newMembers" :href="route('admin.users.index')" />
    </div>
    <p class="mt-2 text-xs text-cobalt/60">{{ __('Sales count every order that was not cancelled, by order date.') }}</p>

    <div class="mt-8 grid items-start gap-6 lg:grid-cols-[1fr_380px]">
        <section>
            <div class="mb-3 flex items-baseline justify-between">
                <h2 class="text-xl font-semibold">{{ __('Latest orders') }}</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold underline-offset-4 hover:underline">{{ __('All orders') }} →</a>
            </div>
            <x-admin.table>
                <tbody>
                    @foreach ($recent as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold underline-offset-4 hover:underline">{{ $order->order_number }}</a></td>
                            <td>{{ $order->user->name }}</td>
                            <td class="whitespace-nowrap text-xs">{{ $order->placed_at->diffForHumans() }}</td>
                            <td class="text-right font-semibold">{{ Money::baht($order->total) }}</td>
                            <td><x-order-status-badge :status="$order->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-admin.table>
        </section>

        <section>
            <h2 class="mb-3 text-xl font-semibold">{{ __('Running low') }}</h2>
            <div class="divide-y-[1.5px] divide-dashed divide-cobalt/30 rounded-card border-2 border-cobalt bg-paper px-5" data-low-stock>
                @forelse ($lowStock as $variant)
                    <a href="{{ route('admin.products.edit', $variant->product) }}" class="flex items-center justify-between gap-3 py-3 text-sm hover:underline">
                        <span><span class="font-semibold">{{ $variant->product->name }}</span> · {{ $variant->size->label() }}</span>
                        <span @class(['font-semibold', 'text-brick' => $variant->stock === 0])>{{ $variant->stock === 0 ? __('Sold out') : __(':n left', ['n' => $variant->stock]) }}</span>
                    </a>
                @empty
                    <p class="py-4 text-sm text-cobalt/70">{{ __('Every size is well stocked.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-admin-layout>
