@use('App\Enums\PromotionType')
@use('App\Support\Money')
@php
    $states = [
        'active' => ['bg-cobalt text-paper border-cobalt', __('Running')],
        'scheduled' => ['border-cobalt text-cobalt', __('Not started')],
        'expired' => ['border-cobalt/40 text-cobalt/60', __('Expired')],
        'off' => ['border-brick text-brick', __('Switched off')],
    ];
@endphp
<x-admin-layout :title="__('Promotions')">
    <x-slot name="actions">
        <a href="{{ route('admin.promotions.create', ['type' => 'coupon']) }}" class="inline-flex rounded-full border-2 border-cobalt bg-cobalt px-5 py-2 text-sm font-semibold text-paper hover:bg-cobalt-ink">+ {{ __('Coupon') }}</a>
        <a href="{{ route('admin.promotions.create', ['type' => 'buy_x_get_y']) }}" class="inline-flex rounded-full border-2 border-cobalt px-5 py-2 text-sm font-semibold hover:bg-paper">+ {{ __('Buy X get Y') }}</a>
    </x-slot>

    <x-input-error :messages="$errors->get('promotion')" class="mb-4" />

    <x-admin.table>
        <thead>
            <tr><th>{{ __('Promotion') }}</th><th>{{ __('Deal') }}</th><th>{{ __('Period') }}</th><th class="text-right">{{ __('Products') }}</th><th class="text-right">{{ __('Used') }}</th><th>{{ __('Status') }}</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($promotions as $promotion)
                @php
                    [$style, $label] = $states[$promotion->state()];
                @endphp
                <tr data-promotion="{{ $promotion->id }}">
                    <td><a href="{{ route('admin.promotions.edit', $promotion) }}" class="font-semibold underline-offset-4 hover:underline">{{ $promotion->name }}</a></td>
                    <td class="whitespace-nowrap">
                        @if ($promotion->type === PromotionType::Coupon)
                            <span class="rounded-md border-[1.5px] border-dashed border-cobalt px-2 py-0.5 font-mono text-xs font-semibold">{{ $promotion->coupon->code }}</span>
                            {{ __(':percent% off', ['percent' => $promotion->coupon->percent_off]) }}
                            @if ($promotion->coupon->min_subtotal) <span class="block text-xs text-cobalt/70">{{ __('min :amount', ['amount' => Money::baht($promotion->coupon->min_subtotal)]) }}</span> @endif
                        @else
                            {{ __('Buy :buy get :free', ['buy' => $promotion->buyXGetY->buy_quantity, 'free' => $promotion->buyXGetY->free_quantity]) }}
                        @endif
                    </td>
                    <td class="whitespace-nowrap text-xs">{{ $promotion->starts_at->translatedFormat('j M Y') }} – {{ $promotion->ends_at?->translatedFormat('j M Y') ?? __('no end') }}</td>
                    <td class="text-right">{{ $promotion->products_count }}</td>
                    <td class="text-right">{{ $promotion->times_used }}@if ($promotion->coupon?->usage_limit)/{{ $promotion->coupon->usage_limit }}@endif</td>
                    <td><span class="rounded-full border-[1.5px] px-2.5 py-0.5 text-xs font-semibold {{ $style }}" data-state="{{ $promotion->state() }}">{{ $label }}</span></td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('admin.promotions.toggle', $promotion) }}">
                            @csrf
                            <button class="text-xs font-semibold underline-offset-4 hover:underline">{{ $promotion->is_active ? __('Switch off') : __('Switch on') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-8 text-center text-cobalt/70">{{ __('No promotions yet.') }}</td></tr>
            @endforelse
        </tbody>
    </x-admin.table>
    <div class="mt-4">{{ $promotions->links() }}</div>
</x-admin-layout>
