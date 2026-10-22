@props(['tier'])
{{-- Small loyalty tier pill: Bean outline, Barista filled, Roaster Master ink + script --}}
@php
    $style = match ($tier->slug) {
        'roaster-master' => 'bg-cobalt-ink text-paper border-cobalt-ink',
        'barista' => 'bg-cobalt text-paper border-cobalt',
        default => 'bg-paper text-cobalt border-cobalt',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full border-2 px-2.5 py-0.5 text-xs font-semibold leading-4 $style"]) }} data-tier-badge="{{ $tier->slug }}">
    <svg viewBox="0 0 16 16" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><ellipse cx="8" cy="8" rx="4.6" ry="6.4" transform="rotate(30 8 8)" /><path d="M5.6 12.6c2.6-2 2-7.2 4.8-9.2" /></svg>
    {{ $tier->name }}
</span>
