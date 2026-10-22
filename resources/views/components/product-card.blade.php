@props(['product', 'match' => null, 'featured' => false])

@use('App\Enums\ProductType')
@use('App\Support\Money')
@inject('promotionBadges', 'App\Support\Promotions\PromotionBadges')

@php
    $match ??= $product->matchPercent(); // set when the query used withTasteMatch()
    $soldOut = $product->isSoldOut();
    $isDrip = $product->type === ProductType::DripBag;
    $fromPrice = $product->variants_min_price ?? $product->variants->min('price');
    // featured cards are always inverted; normal cards invert on hover
    $surface = $featured
        ? 'bg-cobalt text-paper'
        : 'bg-paper text-cobalt group-hover:bg-cobalt group-hover:text-paper';
    $tag = $featured
        ? 'bg-paper text-cobalt'
        : 'bg-cobalt text-paper group-hover:bg-paper group-hover:text-cobalt';
@endphp

{{-- wrapper so the heart form can sit on the card without being nested inside the link --}}
<div {{ $attributes->merge(['class' => 'group relative flex']) }}>
<a href="{{ route('products.show', $product) }}"
   class="{{ "relative flex w-full flex-col rounded-card border-2 border-cobalt p-[18px] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-cobalt focus-visible:ring-offset-2 focus-visible:ring-offset-cream $surface".($soldOut ? ' opacity-60' : '') }}">

    @if ($match !== null)
        <span class="absolute right-3.5 top-3.5 grid h-[62px] w-[62px] rotate-[8deg] place-items-center rounded-full border-2 border-current text-center leading-none">
            <span><span class="block font-script text-[22px]">{{ $match }}%</span><span class="block text-[9px]">{{ __('match') }}</span></span>
        </span>
    @endif

    @if ($product->isExclusiveNow())
        <span class="mb-1.5 self-start rounded-full bg-cobalt-ink px-2.5 py-0.5 text-xs font-semibold text-paper {{ $match !== null ? 'mr-16' : '' }}" data-early-access>✦ {{ __(':tier first', ['tier' => \App\Models\MemberTier::earlyAccessName()]) }}</span>
    @endif
    @foreach ($promotionBadges->for($product) as $badge)
        <span class="mb-1.5 self-start rounded-full border-[1.5px] border-dashed border-current px-2.5 py-0.5 text-xs font-semibold {{ $match !== null ? 'mr-16' : '' }}" data-promo-badge>{{ $badge }}</span>
    @endforeach
    <span class="text-[13px] opacity-75 {{ $match !== null ? 'pr-16' : '' }}">
        {{ $product->origin->country }} · {{ $isDrip ? $product->type->label() : $product->process->label() }}
    </span>
    <span class="mt-0.5 text-[19px] font-semibold leading-snug {{ $match !== null ? 'pr-16' : '' }}">
        {{ $product->name }}
        <span class="block font-script text-[28px] font-normal leading-tight">{{ $product->nickname }}</span>
    </span>

    <x-dynamic-component :component="$isDrip ? 'illustrations.drip-bag' : 'illustrations.bean-bag'" class="mx-auto my-2 h-36 w-auto" />

    <span class="mb-3.5 flex flex-wrap items-center gap-2 text-[13px]">
        @if ($product->rating_count)
            <x-star-rating :rating="$product->rating_avg" class="text-xs font-semibold" data-card-rating>{{ number_format($product->rating_avg, 1) }} ({{ $product->rating_count }})</x-star-rating>
        @endif
        <span class="rounded-full border-[1.5px] border-current px-2 py-0.5 text-xs font-semibold">{{ $product->roast_level->label() }}</span>
        <span>{{ $product->flavorNotes->take(3)->pluck('name')->join(' · ') }}</span>
    </span>

    <span class="mt-auto flex items-center justify-between border-t-[1.5px] border-dashed border-current pt-3">
        @if ($soldOut)
            <span class="text-sm font-semibold">{{ __('Sold out for now') }}</span>
        @else
            <span class="-rotate-[4deg] rounded-[10px] px-2.5 font-script text-2xl {{ $tag }}">
                <span class="font-sans text-xs align-middle">{{ __('from') }}</span> {{ Money::baht($fromPrice) }}
            </span>
        @endif
        <span class="pr-11 text-sm font-semibold">{{ __('View beans') }} ›</span>
    </span>
</a>
<x-wishlist-button :product="$product" class="absolute bottom-[14px] right-[16px]" />
</div>
