@props(['script' => null, 'as' => 'h1'])

<{{ $as }} {{ $attributes->merge(['class' => 'flex flex-wrap items-baseline gap-x-3 text-3xl font-semibold text-cobalt']) }}>
    <span>{{ $slot }}</span>
    @if ($script)
        <span class="font-script font-normal text-4xl">{{ $script }}</span>
    @endif
</{{ $as }}>
