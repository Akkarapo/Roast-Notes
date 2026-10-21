@props(['status'])

@php
    $style = match ($status) {
        \App\Enums\OrderStatus::Delivered => 'bg-cobalt text-paper border-cobalt',
        \App\Enums\OrderStatus::Cancelled => 'border-brick text-brick line-through decoration-1',
        default => 'border-cobalt text-cobalt',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border-[1.5px] px-3 py-0.5 text-xs font-semibold whitespace-nowrap $style"]) }}>
    {{ $status->label() }}
</span>
