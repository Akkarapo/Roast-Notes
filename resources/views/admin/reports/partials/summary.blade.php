<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5" data-summary>
    @foreach ($metrics as $metric)
        <div @class(['flex flex-col gap-1 rounded-card border-2 border-cobalt p-5', 'bg-cobalt text-paper' => $loop->first, 'bg-paper' => ! $loop->first]) data-metric="{{ $metric['key'] }}">
            <span class="text-sm opacity-80">{{ $metric['label'] }}</span>
            <span class="font-script text-3xl leading-tight">{{ $metric['display'] }}</span>
            <span class="text-xs opacity-80">
                @if ($metric['change'] === null)
                    {{ $metric['current'] > 0 ? __('new — nothing in the period before') : '—' }}
                @else
                    {{ $metric['change'] > 0 ? '▲' : ($metric['change'] < 0 ? '▼' : '•') }} {{ number_format(abs($metric['change']), 1) }}% {{ __('vs period before') }}
                @endif
            </span>
        </div>
    @endforeach
</div>
