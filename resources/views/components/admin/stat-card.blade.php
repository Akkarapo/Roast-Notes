@props(['label', 'value', 'href' => null, 'tone' => 'default'])
@php
    $classes = 'flex flex-col gap-1 rounded-card border-2 p-5 '.match ($tone) {
        'alert' => 'border-brick bg-paper text-brick',
        'strong' => 'border-cobalt bg-cobalt text-paper',
        default => 'border-cobalt bg-paper',
    };
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes.' hover:-translate-y-0.5 transition']) }}>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
@endif
    <span class="text-sm opacity-80">{{ $label }}</span>
    <span class="font-script text-4xl leading-tight">{{ $value }}</span>
    @if ($slot->isNotEmpty())<span class="text-xs opacity-80">{{ $slot }}</span>@endif
@if ($href)
    </a>
@else
    </div>
@endif
