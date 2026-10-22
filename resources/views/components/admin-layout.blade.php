@props(['title', 'script' => 'back of house'])
{{-- Back-office pages: the shop layout + a sub-menu of admin sections --}}
@php
    $sections = [
        'admin.index' => [__('Overview'), 'admin.index'],
        'admin.orders.index' => [__('Orders'), 'admin.orders.*'],
        'admin.products.index' => [__('Products'), 'admin.products.*'],
        'admin.promotions.index' => [__('Promotions'), 'admin.promotions.*'],
        'admin.users.index' => [__('Members'), 'admin.users.*'],
        'admin.reviews.index' => [__('Reviews'), 'admin.reviews.*'],
        'admin.reports.index' => [__('Sales report'), 'admin.reports.*'],
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <x-page-title :script="$script">{{ $title }}</x-page-title>
            @isset($actions)
                <div class="flex flex-wrap gap-2">{{ $actions }}</div>
            @endisset
        </div>
        <nav class="mt-6 flex gap-1 overflow-x-auto border-b-2 border-cobalt" aria-label="{{ __('Admin sections') }}" data-admin-nav>
            @foreach ($sections as $route => [$label, $pattern])
                @if (Route::has($route))
                    <a href="{{ route($route) }}" @class([
                        '-mb-0.5 shrink-0 rounded-t-[14px] border-2 px-4 py-2 text-sm font-semibold',
                        'border-cobalt border-b-cream bg-cream' => request()->routeIs($pattern),
                        'border-transparent text-cobalt/70 hover:text-cobalt' => ! request()->routeIs($pattern),
                    ])>{{ $label }}</a>
                @endif
            @endforeach
        </nav>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </div>
</x-app-layout>
