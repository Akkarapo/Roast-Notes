@props(['rows', 'label', 'series' => null])
{{--
    Horizontal bars for rankings and shares (server-rendered, no JS).
    rows: list of ['label' => 'Light', 'value' => float, 'display' => '42%', 'value2' => ?float, 'display2' => ?string]
    series: null for one series (the section title names it), or [name1, name2] for paired bars + legend.
--}}
@php
    $rows = array_values($rows);
    $paired = is_array($series);
    $max = max(array_merge(array_column($rows, 'value'), $paired ? array_column($rows, 'value2') : [0]) ?: [0]) ?: 1;
    $colors = ['#2338C4', '#B86E14']; // validated pair (light surface #FBF8F1): CVD ΔE 31.7, contrast ≥ 3:1
@endphp
<figure {{ $attributes->merge(['class' => 'rounded-card border-2 border-cobalt bg-paper p-5']) }} role="img" aria-label="{{ $label }}" data-chart="hbars">
    @if ($paired)
        <div class="mb-3 flex flex-wrap gap-4 text-xs" aria-hidden="true">
            @foreach ($series as $i => $name)
                <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-4 rounded-sm" style="background: {{ $colors[$i] }}"></span>{{ $name }}</span>
            @endforeach
        </div>
    @endif
    <div class="space-y-2.5">
        @foreach ($rows as $row)
            <div class="grid grid-cols-[minmax(90px,30%)_1fr] items-center gap-3 text-sm" title="{{ $row['label'] }}: {{ $row['display'] }}{{ $paired ? ' / '.$row['display2'] : '' }}">
                <span class="truncate">{{ $row['label'] }}</span>
                <div class="space-y-[2px]">
                    @foreach ($paired ? [[$row['value'], $row['display'], 0], [$row['value2'], $row['display2'], 1]] : [[$row['value'], $row['display'], 0]] as [$value, $display, $i])
                        <div class="flex items-center gap-2">
                            <span class="block {{ $paired ? 'h-2.5' : 'h-3.5' }} rounded-r-[4px]" style="width: {{ max(0.5, $value / $max * 85) }}%; background: {{ $colors[$i] }}"></span>
                            <span class="shrink-0 text-xs font-semibold tabular-nums">{{ $display }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</figure>
