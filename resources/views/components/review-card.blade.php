@props(['review'])
{{-- One review on the product page: who (short name), stars, brew method, what they bought, when, comment --}}
@php
    $item = $review->orderItem;
@endphp
<article class="py-5" data-review>
    <header class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
        <x-star-rating :rating="$review->rating" />
        <span class="rounded-full bg-cobalt px-2.5 py-0.5 text-xs font-semibold text-paper" data-review-brew>{{ $review->brew_method->label() }}</span>
        <span class="rounded-full border-[1.5px] border-dashed border-cobalt px-2.5 py-0.5 text-xs font-semibold">
            ✓ {{ __('Bought') }} · {{ \App\Enums\BagSize::from($item->size)->label() }}@if ($item->grind !== \App\Enums\Grind::None) · {{ $item->grind->label() }}@endif
        </span>
    </header>
    @if ($review->comment)
        <p class="mt-2.5 whitespace-pre-line leading-relaxed">{{ $review->comment }}</p>
    @endif
    <p class="mt-2 text-sm text-cobalt/70">
        <span class="font-semibold text-cobalt">{{ $item->order->user->shortName() }}</span>
        · {{ $review->created_at->translatedFormat('j M Y') }}
        @if ($review->wasEdited()) {{ __('(edited)') }} @endif
    </p>
</article>
