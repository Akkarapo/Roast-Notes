@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-[3px] border-cobalt font-semibold leading-5 text-cobalt focus:outline-none transition'
            : 'inline-flex items-center px-1 pt-1 border-b-[3px] border-transparent font-medium leading-5 text-cobalt/70 hover:text-cobalt hover:border-cobalt/30 focus:outline-none focus:border-cobalt/30 transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
