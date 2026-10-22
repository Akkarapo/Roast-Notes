@props(['rating', 'count' => null, 'size' => 'sm'])
{{-- Read-only stars: rating may be fractional (average). Screen readers get the number. --}}
@php
    $value = round((float) $rating, 1);
    $box = $size === 'lg' ? 'h-6 w-6' : 'h-4 w-4';
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }} data-stars="{{ number_format($value, 1) }}">
    <span class="sr-only">{{ __(':rating out of 5', ['rating' => number_format($value, 1)]) }}</span>
    <span class="inline-flex" aria-hidden="true">
        @for ($i = 1; $i <= 5; $i++)
            @php
                $fill = max(0, min(1, $value - $i + 1));
            @endphp
            <svg viewBox="0 0 24 24" class="{{ $box }}">
                <defs><linearGradient id="star-{{ $i }}-{{ (int) ($fill * 100) }}"><stop offset="{{ $fill * 100 }}%" stop-color="currentColor" /><stop offset="{{ $fill * 100 }}%" stop-color="transparent" /></linearGradient></defs>
                <path d="M12 2.8l2.8 5.9 6.4.8-4.7 4.4 1.2 6.4L12 17.1l-5.7 3.2 1.2-6.4-4.7-4.4 6.4-.8z" fill="url(#star-{{ $i }}-{{ (int) ($fill * 100) }})" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
            </svg>
        @endfor
    </span>
    {{ $slot }}
</span>
