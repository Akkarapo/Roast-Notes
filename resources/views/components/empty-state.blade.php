@props(['mascot' => 'nap'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center text-center gap-3 py-12 px-6 bg-paper border-2 border-dashed border-cobalt/50 rounded-card']) }}>
    <x-mascot :pose="$mascot" class="h-28 w-28 text-cobalt" />
    <p class="text-cobalt/80">{{ $slot }}</p>
    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
