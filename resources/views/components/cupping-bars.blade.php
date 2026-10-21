@props(['cupping'])

@php
    $labels = [
        'aroma' => 'Aroma · กลิ่นหอม',
        'flavor' => 'Flavor · รสชาติ',
        'aftertaste' => 'Aftertaste · รสค้างในปาก',
        'acidity' => 'Acidity · ความเปรี้ยวสดชื่น',
        'body' => 'Body · ความเข้มข้น',
        'balance' => 'Balance · ความกลมกล่อม',
    ];
    // bars use a 6–10 scale so differences between specialty lots are visible
    $width = fn ($score) => max(0, min(100, ((float) $score - 6) / 4 * 100));
@endphp

<div {{ $attributes->merge(['class' => 'rounded-card border-2 border-cobalt bg-paper p-6']) }}>
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-xl font-semibold">{{ __('Cupping score') }}</h2>
        <p><span class="font-script text-4xl">{{ number_format((float) $cupping->total_points, 2) }}</span> <span class="text-sm">/ 100</span></p>
    </div>

    <dl class="mt-5 space-y-3">
        @foreach (\App\Models\CuppingScore::DISPLAYED as $attribute)
            <div>
                <div class="flex justify-between text-sm">
                    <dt>{{ $labels[$attribute] }}</dt>
                    <dd class="font-semibold">{{ number_format((float) $cupping->$attribute, 2) }}</dd>
                </div>
                <div class="mt-1 h-2.5 rounded-full border-[1.5px] border-cobalt" role="presentation">
                    <div class="h-full rounded-full bg-cobalt" style="width: {{ $width($cupping->$attribute) }}%"></div>
                </div>
            </div>
        @endforeach
    </dl>

    <p class="mt-5 text-xs text-cobalt/70">
        {{ __('Scored by Coffee Quality Institute Q-graders') }}@if ($cupping->graded_on) · {{ $cupping->graded_on->translatedFormat('j M Y') }}@endif.
        {{ __('Each attribute is out of 10; bars show the 6–10 range.') }}
    </p>
</div>
